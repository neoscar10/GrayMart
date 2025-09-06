<?php

namespace App\Livewire\Front\Pages;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;

use App\Support\CartManagement;
use App\Models\Product;
use App\Models\VendorProfile;
use App\Models\Order;
use App\Models\OrderItem;

// PayPal SDK (srmklive/paypal ^3.x)
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class CheckoutPage extends Component
{
    // Shipping form
    public string $first_name = '';
    public string $last_name  = '';
    public string $phone      = '';
    public ?string $email     = null;
    public string $street     = '';
    public string $city       = '';
    public string $state      = '';

    // Payment
    public string $payment_method = 'paypal';

    // Cart & grouping
    public array $cart   = [];
    public array $groups = [];   // [vendor_id => ['vendor'=>[], 'items'=>[], 'subtotal'=>float]]

    // Totals
    public float $subtotal = 0;
    public float $shipping = 0;
    public float $discount = 0;
    public float $grand    = 0;

    public function mount(): void
    {
        $this->loadCart();
    }

    public function updated($prop): void
    {
        $this->computeTotals();
    }

    private function loadCart(): void
    {
        $items = CartManagement::items(); // cookie cart

        if (empty($items)) {
            session()->flash('error', 'Your cart is empty.');
            redirect()->route('shop')->send();
            return;
        }

        // Map product ids -> product (for vendor_id fallback, name, image safety)
        $productIds = array_values(array_unique(array_map(fn($i) => (int) $i['product_id'], $items)));
        $products   = Product::whereIn('id', $productIds)
            ->get(['id', 'vendor_id', 'name', 'images'])
            ->keyBy('id');

        // Build vendor groups
        $byVendor = [];
        foreach ($items as $line) {
            $pid = (int) $line['product_id'];
            $p   = $products[$pid] ?? null;

            $vendorId = (int) ($line['vendor_id'] ?? ($p?->vendor_id ?? 0));
            if ($vendorId <= 0) {
                $vendorId = 0; // fallback bucket
            }

            // Normalize snapshots for display
            $line['display_name'] = $line['name'] ?? ($p?->name ?? 'Product');
            $line['image_url']    = $line['image']
                ? Storage::url($line['image'])
                : asset('assets/images/thumbs/product-placeholder.png');

            $byVendor[$vendorId]['items'][] = $line;
        }

        // Vendor store meta (name, logo)
        $vendorIds = array_filter(array_keys($byVendor));
        $profiles  = VendorProfile::whereIn('user_id', $vendorIds)
            ->get(['user_id', 'store_name', 'slug', 'logo_path'])
            ->keyBy('user_id');

        foreach ($byVendor as $vid => &$group) {
            $vp = $profiles->get($vid);
            $group['vendor']   = [
                'id'   => $vid,
                'name' => $vp?->store_name ?? 'Store',
                'slug' => $vp?->slug ?? null,
                'logo' => $vp?->logo_url ?? null, // accessor on your model
            ];
            $group['subtotal'] = array_sum(array_map(
                fn($i) => (float) $i['total_amount'],
                $group['items'] ?? []
            ));
        }

        $this->groups   = $byVendor;
        $this->cart     = $items;
        $this->computeTotals();
    }

    private function computeTotals(): void
    {
        $this->subtotal = array_sum(array_map(fn($g) => (float) ($g['subtotal'] ?? 0), $this->groups));
        $this->shipping = 0.0; // flat 0 for now
        $this->discount = 0.0; // add coupons later
        $this->grand    = max(0.0, $this->subtotal + $this->shipping - $this->discount);
    }

    public function placeOrder()
    {
        $this->validate([
            'first_name'     => 'required|string|min:2',
            'last_name'      => 'required|string|min:2',
            'phone'          => 'required|string|min:6',
            'street'         => 'required|string|min:4|max:520',
            'city'           => 'required|string|min:2|max:255',
            'state'          => 'required|string|min:2|max:255',
            'payment_method' => 'required|in:paypal',
        ]);

        if (empty($this->cart)) {
            session()->flash('error', 'Your cart is empty.');
            return redirect()->route('shop');
        }

        $address = [
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'street'     => $this->street,
            'city'       => $this->city,
            'state'      => $this->state,
        ];

        $userId = Auth::id();

        DB::beginTransaction();
        try {
            // 1) Create ONE order per vendor group (unpaid initially)
            $createdOrders = [];
            foreach ($this->groups as $vendorId => $group) {
                $subtotal = (float) $group['subtotal'];
                $shipping = 0.0;
                $discount = 0.0;
                $total    = max(0.0, $subtotal + $shipping - $discount);

                $order = new Order();
                $order->user_id          = $userId;
                $order->vendor_id        = $vendorId ?: null;
                $order->subtotal_amount  = $subtotal;
                $order->shipping_amount  = $shipping;
                $order->discount_total   = $discount;
                $order->total_amount     = $total;
                $order->currency         = 'USD';
                $order->payment_method   = $this->payment_method;
                $order->payment_status   = 'unpaid';
                $order->status           = 'pending';
                $order->shipping_address = $address;
                $order->save();

                foreach ($group['items'] as $line) {
                    // ---- IMPORTANT: never persist negative/zero variant ids (auction sentinels) ----
                    $rawVariantId = isset($line['variant_id']) ? (int) $line['variant_id'] : null;
                    $safeVariantId = ($rawVariantId !== null && $rawVariantId > 0) ? $rawVariantId : null;

                    $item                   = new OrderItem();
                    $item->product_id       = (int) $line['product_id'];
                    $item->variant_id       = $safeVariantId; // null if not a real variant
                    $item->product_name     = (string) ($line['name'] ?? $line['display_name'] ?? 'Product');
                    $item->image            = $line['image'] ?? null; // relative path in storage
                    $item->quantity         = (int) ($line['quantity'] ?? 1);
                    $item->unit_price       = (float) $line['unit_amount'];
                    $item->total_price      = (float) $line['total_amount'];
                    $item->vendor_id        = $vendorId ?: null;

                    // Compose meta: keep variant label/slug and include auction info if present
                    $meta = [
                        'variant_label' => $line['variant_label'] ?? null,
                        'slug'          => $line['slug'] ?? null,
                    ];

                    // Accept either flattened keys or nested meta from the cart
                    if (!empty($line['auction_id'])) {
                        $meta['auction_id'] = (int) $line['auction_id'];
                    }
                    if (!empty($line['auction_mode'])) {
                        $meta['auction_mode'] = $line['auction_mode']; // 'win' | 'buy_now'
                    }
                    if (isset($line['meta']) && is_array($line['meta'])) {
                        // Merge but do not let nested meta overwrite core scalar fields above
                        foreach ($line['meta'] as $k => $v) {
                            if (!array_key_exists($k, $meta)) {
                                $meta[$k] = $v;
                            }
                        }
                    }

                    $item->meta = $meta;

                    $order->items()->save($item);
                }

                $createdOrders[] = $order;
            }

            // 2) If PayPal is configured, create a SINGLE PayPal order for the GRAND total
            $approveUrl = null;
            $paypalId   = null;
            $paypalPayload = null;

            if (!empty(config('paypal'))) {
                $provider = new PayPalClient();
                $provider->setApiCredentials(config('paypal'));
                $provider->getAccessToken();

                $returnUrl = $this->paypalReturnUrl();
                $cancelUrl = $this->paypalCancelUrl();

                $response = $provider->createOrder([
                    'intent' => 'CAPTURE',
                    'application_context' => [
                        'return_url' => $returnUrl,
                        'cancel_url' => $cancelUrl,
                    ],
                    'purchase_units' => [[
                        'amount' => [
                            'currency_code' => 'USD',
                            'value'         => number_format($this->grand, 2, '.', ''),
                        ],
                    ]],
                ]);

                if (is_array($response) && !empty($response['id'])) {
                    $paypalId     = $response['id'];
                    $paypalPayload = $response;
                    foreach ($response['links'] ?? [] as $link) {
                        if (($link['rel'] ?? '') === 'approve') {
                            $approveUrl = $link['href'];
                            break;
                        }
                    }
                }

                // Save PayPal id/payload on each vendor order
                if ($paypalId) {
                    foreach ($createdOrders as $o) {
                        $o->external_payment_id      = $paypalId;
                        $o->external_payment_payload = $paypalPayload;
                        $o->save();
                    }
                }
            }

            DB::commit();

            // 3) Clear cart
            CartManagement::clearCartItems();

            // 4) Redirect
            if ($approveUrl) {
                return redirect()->away($approveUrl);
            }

            session()->flash('success', 'Order placed. Payment is pending.');
            return redirect()->route('shop');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            session()->flash('error', $e->getMessage());
            return null;
        }
    }

    private function paypalReturnUrl(): string
    {
        if (Route::has('checkout.success')) {
            return route('checkout.success');
        }
        return url('/checkout/success');
    }

    private function paypalCancelUrl(): string
    {
        if (Route::has('checkout.cancel')) {
            return route('checkout.cancel');
        }
        return url('/checkout/cancel');
    }

    public function render()
    {
        return view('livewire.front.pages.checkout-page');
    }
}
