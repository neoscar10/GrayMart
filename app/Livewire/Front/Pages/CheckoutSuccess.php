<?php

namespace App\Livewire\Front\Pages;

use Livewire\Component;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

use App\Models\Order;
use App\Models\VendorProfile;

class CheckoutSuccess extends Component
{
    public ?string $token = null;           // PayPal order ID from query (?token= or ?order_id=)
    public bool $done = false;              // capture / processing done
    public bool $ok = false;                // result status
    public string $message = '';

    // Display data
    public array $orders = [];              // raw orders (ids / totals)
    public float $grand = 0.0;              // total across matched orders
    public array $vendorCards = [];         // pretty vendor cards w/ logo

    public function mount(Request $request): void
    {
        // PayPal returns `token` (v2) — support both keys just in case
        $this->token = $request->query('token') ?? $request->query('order_id');

        if (!$this->token) {
            $this->done = true;
            $this->ok = false;
            $this->message = 'Missing PayPal token in the callback.';
            return;
        }

        // Load any orders tied to this token
        $orders = Order::where('external_payment_id', $this->token)->get();

        if ($orders->isEmpty()) {
            $this->done = true;
            $this->ok = false;
            $this->message = 'We could not match this payment to any pending order.';
            return;
        }

        // Optional: ensure these orders belong to the current user (if logged in)
        if (Auth::check()) {
            $userId = Auth::id();
            $orders = $orders->where('user_id', $userId);
            if ($orders->isEmpty()) {
                $this->done = true;
                $this->ok = false;
                $this->message = 'These orders belong to a different account.';
                return;
            }
        }

        // If already paid (idempotency)
        $alreadyPaid = $orders->every(fn($o) => $o->payment_status === 'paid');
        if ($alreadyPaid) {
            $this->hydrateSummary($orders);
            $this->done = true;
            $this->ok = true;
            $this->message = 'Payment already completed. Thank you!';
            return;
        }

        // Capture with PayPal
        try {
            // Guard: config present?
            $mode = config('paypal.mode');
            $creds = $mode ? (config("paypal.$mode") ?? []) : [];
            if (empty($mode) || empty($creds['client_id']) || empty($creds['client_secret'])) {
                $this->done = true;
                $this->ok = false;
                $this->message = 'PayPal is not configured. We created your orders as unpaid.';
                return;
            }

            $provider = new PayPalClient();
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $resp = $provider->capturePaymentOrder($this->token);

            // Consider COMPLETED a successful payment
            $status = $resp['status'] ?? null;
            if ($status === 'COMPLETED') {
                DB::transaction(function () use ($orders, $resp) {
                    foreach ($orders as $o) {
                        $o->payment_status = 'paid';
                        // you can move 'status' to whatever you use post-payment
                        $o->status = 'processing';
                        $o->external_payment_payload = $resp;
                        $o->save();
                    }
                });

                $this->hydrateSummary($orders);
                $this->done = true;
                $this->ok = true;
                $this->message = 'Payment successful — your orders are confirmed!';
            } else {
                // Can be PENDING, APPROVED but not captured, etc.
                $this->hydrateSummary($orders);
                $this->done = true;
                $this->ok = false;
                $this->message = 'Payment not completed yet. Status: '.($status ?? 'unknown');
            }
        } catch (\Throwable $e) {
            report($e);
            $this->done = true;
            $this->ok = false;
            $this->message = 'We could not capture your payment. If money was taken, your order will auto-reconcile shortly.';
        }
    }

    private function hydrateSummary($orders): void
    {
        $this->orders = $orders->map(fn($o) => [
            'id'    => $o->id,
            'total' => (float) $o->total_amount,
            'vendor_id' => (int) ($o->vendor_id ?? 0),
        ])->values()->all();

        $this->grand = $orders->sum('total_amount');

        // Build vendor cards
        $vendorIds = $orders->pluck('vendor_id')->filter()->unique()->values();
        $profiles = VendorProfile::whereIn('user_id', $vendorIds)->get()->keyBy('user_id');

        $cards = [];
        foreach ($orders as $o) {
            $vp = $profiles->get($o->vendor_id);
            $cards[] = [
                'order_id'   => $o->id,
                'vendor_id'  => (int) $o->vendor_id,
                'store_name' => $vp?->store_name ?? 'Store',
                'store_slug' => $vp?->slug,
                'logo'       => $vp?->logo_url,
                'total'      => (float) $o->total_amount,
            ];
        }
        $this->vendorCards = $cards;
    }

    public function render()
    {
        return view('livewire.front.pages.checkout-success');
    }
}
