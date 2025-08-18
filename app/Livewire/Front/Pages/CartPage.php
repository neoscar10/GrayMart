<?php

namespace App\Livewire\Front\Pages;

use Livewire\Component;
use App\Support\CartManagement;

class CartPage extends Component
{
    public array $cart_items = [];
    public float $grand_total = 0.0;

    public ?string $coupon_code = '';
    public ?string $coupon_error = null;
    public float $discount = 0.0;

    protected $listeners = [
        'cart-updated' => 'refreshCart',
    ];

    public function mount(): void
    {
        // If a coupon was applied earlier in the session, hydrate it
        if (session()->has('applied_coupon.code')) {
            $this->coupon_code = session('applied_coupon.code');
            $this->discount    = (float) session('applied_coupon.discount', 0);
        }
        $this->refreshCart();
    }

    public function refreshCart(): void
    {
        // Pull items from cookie and normalize to your Blade keys
        $items = CartManagement::items();
        foreach ($items as &$i) {
            // Your blade expects 'title', but our products use 'name'
            if (!isset($i['title']) && isset($i['name'])) {
                $i['title'] = $i['name'];
            }
            $i['quantity']     = (int)   ($i['quantity']     ?? 1);
            $i['unit_amount']  = (float) ($i['unit_amount']  ?? 0);
            $i['total_amount'] = (float) ($i['total_amount'] ?? ($i['quantity'] * $i['unit_amount']));
        }
        $this->cart_items  = $items;
        $this->grand_total = (float) CartManagement::total();

        // Keep discount sane if cart changed
        if ($this->discount > 0) {
            $this->discount = min($this->discount, $this->grand_total);
        }
    }

    public function increaseQty(int $productId): void
    {
        CartManagement::increment($productId);
        $this->dispatch('cart-updated');
        $this->refreshCart();
    }

    public function decreaseQty(int $productId): void
    {
        CartManagement::decrement($productId);
        $this->dispatch('cart-updated');
        $this->refreshCart();
    }

    public function removeItem(int $productId): void
    {
        CartManagement::remove($productId);
        $this->dispatch('cart-updated');
        $this->refreshCart();
    }

    public function applyCouponIfExists(): void
    {
        $code = strtoupper(trim((string) $this->coupon_code));
        $this->coupon_error = null;
        $this->discount = 0.0;

        if ($code === '') {
            $this->coupon_error = 'Enter a coupon code.';
            return;
        }

        $total = $this->grand_total;

        // If you already have a Coupon model, use it. Otherwise fallback to a demo code.
        if (class_exists(\App\Models\Coupon::class)) {
            $coupon = \App\Models\Coupon::query()
                ->where('code', $code)
                ->where('is_active', true)
                ->first();

            if (!$coupon) {
                $this->coupon_error = 'Invalid or inactive coupon.';
                return;
            }

            // Soft-validate dates/min total only if those columns exist
            $now = now();
            if (isset($coupon->valid_from) && $coupon->valid_from && $now->lt($coupon->valid_from)) {
                $this->coupon_error = 'Coupon not yet valid.';
                return;
            }
            if (isset($coupon->valid_to) && $coupon->valid_to && $now->gt($coupon->valid_to)) {
                $this->coupon_error = 'Coupon has expired.';
                return;
            }
            if (isset($coupon->min_order_amount) && $coupon->min_order_amount && $total < $coupon->min_order_amount) {
                $this->coupon_error = 'Order total is below the minimum for this coupon.';
                return;
            }

            $type  = $coupon->discount_type ?? 'fixed'; // 'percent' or 'fixed'
            $value = (float) ($coupon->value ?? 0);

            if ($value <= 0) {
                $this->coupon_error = 'Invalid coupon value.';
                return;
            }

            $discount = $type === 'percent'
                ? round($total * ($value / 100), 2)
                : round($value, 2);

            $this->discount     = max(0, min($discount, $total));
            $this->coupon_error = null;

            session([
                'applied_coupon' => ['code' => $code, 'discount' => $this->discount],
            ]);
            return;
        }

        // Fallback: allow a test coupon if no Coupon model in this project yet
        if ($code === 'WELCOME5') {
            $this->discount = round(min($total * 0.05, $total), 2);
            $this->coupon_error = null;
            session(['applied_coupon' => ['code' => $code, 'discount' => $this->discount]]);
        } else {
            $this->coupon_error = 'Coupon not recognized.';
        }
    }

    public function render()
    {
        // Keep total fresh on every render
        $this->grand_total = (float) CartManagement::total();

        return view('livewire.front.pages.cart-page', [
            'cart_items'  => $this->cart_items,
            'grand_total' => $this->grand_total,
            'discount'    => $this->discount,
        ]);
    }
}
