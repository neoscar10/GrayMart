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
        if (session()->has('applied_coupon.code')) {
            $this->coupon_code = session('applied_coupon.code');
            $this->discount    = (float) session('applied_coupon.discount', 0);
        }
        $this->refreshCart();
    }

    public function refreshCart(): void
    {
        $items = CartManagement::items();
        foreach ($items as &$i) {
            if (!isset($i['title']) && isset($i['name'])) {
                $i['title'] = $i['name'];
            }
            $i['quantity']     = (int)   ($i['quantity']     ?? 1);
            $i['unit_amount']  = (float) ($i['unit_amount']  ?? 0);
            $i['total_amount'] = (float) ($i['total_amount'] ?? ($i['quantity'] * $i['unit_amount']));
            $i['meta']         = is_array($i['meta'] ?? null) ? $i['meta'] : [];
            $i['is_auction']   = (bool)  (($i['is_auction'] ?? false) || ($i['meta']['is_auction'] ?? false));
        }
        $this->cart_items  = $items;
        $this->grand_total = (float) CartManagement::total();

        if ($this->discount > 0) {
            $this->discount = min($this->discount, $this->grand_total);
        }
    }

    public function increaseQty(int $productId, $variantId = null): void
    {
        $line = $this->findLine($productId, $variantId);
        if ($line && ($line['is_auction'] ?? false)) {
            // Auction quantity locked to 1
            return;
        }
        CartManagement::increment($productId, is_null($variantId) ? null : (int)$variantId);
        $this->dispatch('cart-updated');
        $this->refreshCart();
    }

    public function decreaseQty(int $productId, $variantId = null): void
    {
        $line = $this->findLine($productId, $variantId);
        if ($line && ($line['is_auction'] ?? false)) {
            return;
        }
        CartManagement::decrement($productId, is_null($variantId) ? null : (int)$variantId);
        $this->dispatch('cart-updated');
        $this->refreshCart();
    }

    public function removeItem(int $productId, $variantId = null): void
    {
        CartManagement::remove($productId, is_null($variantId) ? null : (int)$variantId);
        $this->dispatch('cart-updated');
        $this->refreshCart();
    }

    private function findLine(int $productId, $variantId = null): ?array
    {
        foreach ($this->cart_items as $i) {
            $sameP = ((int)$i['product_id'] === $productId);
            $sameV = ((int)($i['variant_id'] ?? 0) === (int)($variantId ?? 0));
            if ($sameP && $sameV) return $i;
        }
        return null;
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

        if (class_exists(\App\Models\Coupon::class)) {
            $coupon = \App\Models\Coupon::query()
                ->where('code', $code)
                ->where('is_active', true)
                ->first();

            if (!$coupon) {
                $this->coupon_error = 'Invalid or inactive coupon.';
                return;
            }

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
        $this->grand_total = (float) CartManagement::total();

        return view('livewire.front.pages.cart-page', [
            'cart_items'  => $this->cart_items,
            'grand_total' => $this->grand_total,
            'discount'    => $this->discount,
        ]);
    }
}
