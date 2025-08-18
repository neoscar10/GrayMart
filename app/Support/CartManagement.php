<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Cookie;

class CartManagement
{
    // Cookie name + TTL (minutes)
    private const COOKIE = 'cart_items';
    private const TTL    = 60 * 24 * 30; // 30 days

    /** Public API used in your pages */
    public static function addItem(int $productId, int $qty = 1): int
    {
        $items = self::items();
        $key = self::findIndex($items, $productId);

        $product = Product::query()
            ->where('id', $productId)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->first(['id','slug','name','price','buy_now_price','images','is_reserved']);

        // Do not allow reserved / non-purchasable items in cart
        if (!$product || $product->is_reserved) {
            return self::count(); // no change
        }

        $unit = self::effectivePrice($product); // buy_now_price if valid & cheaper, else price
        if ($unit <= 0) {
            return self::count(); // guard against invalid price
        }

        if ($key !== null) {
            $items[$key]['quantity'] += max(1, $qty);
            $items[$key]['total_amount'] = $items[$key]['quantity'] * $items[$key]['unit_amount'];
        } else {
            $img = (is_array($product->images) && count($product->images)) ? $product->images[0] : null;
            $qty = max(1, $qty);

            $items[] = [
                'product_id'   => $product->id,
                'slug'         => $product->slug,
                'name'         => $product->name,
                // store RELATIVE storage path; render with Storage::url($image)
                'image'        => $img,
                'quantity'     => $qty,
                'unit_amount'  => $unit,
                'total_amount' => $unit * $qty,
            ];
        }

        self::put($items);
        return self::count();
    }

    public static function setQty(int $productId, int $qty): array
    {
        $items = self::items();
        $key = self::findIndex($items, $productId);
        if ($key !== null) {
            $qty = max(1, (int) $qty);
            $items[$key]['quantity'] = $qty;
            $items[$key]['total_amount'] = $qty * $items[$key]['unit_amount'];
            self::put($items);
        }
        return $items;
    }

    public static function increment(int $productId): array
    {
        $items = self::items();
        $key = self::findIndex($items, $productId);
        if ($key !== null) {
            $items[$key]['quantity']++;
            $items[$key]['total_amount'] = $items[$key]['quantity'] * $items[$key]['unit_amount'];
            self::put($items);
        }
        return $items;
    }

    public static function decrement(int $productId): array
    {
        $items = self::items();
        $key = self::findIndex($items, $productId);
        if ($key !== null && $items[$key]['quantity'] > 1) {
            $items[$key]['quantity']--;
            $items[$key]['total_amount'] = $items[$key]['quantity'] * $items[$key]['unit_amount'];
            self::put($items);
        }
        return $items;
    }

    public static function remove(int $productId): array
    {
        $items = array_values(array_filter(self::items(), fn($i) => $i['product_id'] !== $productId));
        self::put($items);
        return $items;
    }

    public static function clear(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    /** Accessors */
    public static function items(): array
    {
        return json_decode(Cookie::get(self::COOKIE), true) ?: [];
    }

    // Badge count: total quantity (not unique line items)
    public static function count(): int
    {
        return array_sum(array_map(fn($i) => (int) $i['quantity'], self::items()));
    }

    public static function countUnique(): int
    {
        return count(self::items());
    }

    public static function total(): float
    {
        return array_sum(array_map(fn($i) => (float) $i['total_amount'], self::items()));
    }

    /** ---------------- internal helpers ---------------- */
    private static function findIndex(array $items, int $productId): ?int
    {
        foreach ($items as $k => $i) {
            if ((int) $i['product_id'] === $productId) return $k;
        }
        return null;
    }

    private static function put(array $items): void
    {
        Cookie::queue(self::COOKIE, json_encode(array_values($items)), self::TTL);
    }

    private static function effectivePrice(Product $p): float
    {
        $price = (float) $p->price;
        $buy   = (float) ($p->buy_now_price ?? 0);
        return ($buy > 0 && $buy < $price) ? $buy : $price;
    }

    /** ---- compatibility with your old project method names ---- */
    public static function addItemsToCart(int $product_id): int
    {
        return self::addItem($product_id, 1);
    }
    public static function addItemsToCartwithQty(int $product_id, int $qty = 1): int
    {
        return self::addItem($product_id, $qty);
    }
    public static function incrementQuantityToCartItem(int $product_id): array
    {
        return self::increment($product_id);
    }
    public static function decrementQuantityToCartItem(int $product_id): array
    {
        return self::decrement($product_id);
    }
    public static function removeCartItem(int $product_id): array
    {
        return self::remove($product_id);
    }
    public static function getCartItemsFromCookie(): array
    {
        return self::items();
    }
    public static function addCartItemsToCookie(array $items): void
    {
        self::put($items);
    }
    public static function clearCartItems(): void
    {
        self::clear();
    }
    public static function calculateGrandTotal(array $items): float
    {
        return array_sum(array_column($items, 'total_amount'));
    }
}
