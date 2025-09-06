<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CartManagement
{
    // Cookie name + TTL (minutes)
    private const COOKIE = 'cart_items';
    private const TTL    = 60 * 24 * 30; // 30 days

    /** ===== Public API used across pages (NORMAL PRODUCTS) ===== */

    public static function addItem(int $productId, int $qty = 1, ?int $variantId = null): int
    {
        $items = self::items();
        $key   = self::findIndex($items, $productId, $variantId);

        $product = Product::query()
            ->where('id', $productId)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->first(['id','slug','name','price','buy_now_price','images','is_reserved','vendor_id']);

        // Do not allow reserved / non-purchasable items in normal cart
        if (!$product || $product->is_reserved) {
            return self::count(); // no change
        }

        // Resolve unit price + optional variant meta
        $unit         = null;
        $variantLabel = null;

        if ($variantId) {
            $meta = self::getVariantMeta($productId, $variantId);
            if (!$meta) {
                return self::count(); // invalid variant
            }
            $unit         = $meta['price'];
            $variantLabel = $meta['label'];
        } else {
            $unit = self::effectivePrice((float)$product->price, (float)($product->buy_now_price ?? 0));
        }

        if ($unit <= 0) {
            return self::count(); // guard against invalid price
        }

        $img = (is_array($product->images) && count($product->images)) ? $product->images[0] : null;
        $qty = max(1, (int)$qty);

        if ($key !== null) {
            $items[$key]['quantity']     += $qty;
            $items[$key]['unit_amount']   = (float) $unit; // keep latest price
            $items[$key]['total_amount']  = $items[$key]['quantity'] * $items[$key]['unit_amount'];
            $items[$key]['vendor_id']     = $product->vendor_id;
            $items[$key]['variant_label'] = $variantLabel;
            $items[$key]['slug']          = $product->slug;
            $items[$key]['name']          = $product->name;
            $items[$key]['image']         = $img;
        } else {
            $items[] = [
                'product_id'    => $product->id,
                'vendor_id'     => $product->vendor_id,
                'variant_id'    => $variantId,      // null for non-variant products
                'variant_label' => $variantLabel,   // e.g., "Color: Red, Size: M"
                'slug'          => $product->slug,
                'name'          => $product->name,
                'image'         => $img,            // store relative path; render with Storage::url()
                'quantity'      => $qty,
                'unit_amount'   => (float) $unit,
                'total_amount'  => (float) $unit * $qty,
            ];
        }

        self::put($items);
        return self::count();
    }

    public static function setQty(int $productId, int $qty, ?int $variantId = null): array
    {
        $items = self::items();
        $key   = self::findIndex($items, $productId, $variantId);
        if ($key !== null) {
            $qty = max(1, (int) $qty);
            $items[$key]['quantity']     = $qty;
            $items[$key]['total_amount'] = $qty * (float) $items[$key]['unit_amount'];
            self::put($items);
        }
        return $items;
    }

    public static function increment(int $productId, ?int $variantId = null): array
    {
        $items = self::items();
        $key   = self::findIndex($items, $productId, $variantId);
        if ($key !== null) {
            $items[$key]['quantity']++;
            $items[$key]['total_amount'] = (int)$items[$key]['quantity'] * (float)$items[$key]['unit_amount'];
            self::put($items);
        }
        return $items;
    }

    public static function decrement(int $productId, ?int $variantId = null): array
    {
        $items = self::items();
        $key   = self::findIndex($items, $productId, $variantId);
        if ($key !== null && $items[$key]['quantity'] > 1) {
            $items[$key]['quantity']--;
            $items[$key]['total_amount'] = (int)$items[$key]['quantity'] * (float)$items[$key]['unit_amount'];
            self::put($items);
        }
        return $items;
    }

    /**
     * Remove a line (product + optional variant).
     * If $variantId is null, removes ALL lines of that product (legacy behavior).
     */
    public static function remove(int $productId, ?int $variantId = null): array
    {
        $items = self::items();

        if ($variantId === null) {
            $items = array_values(array_filter($items, fn($i) => (int)$i['product_id'] !== $productId));
        } else {
            $items = array_values(array_filter($items, function ($i) use ($productId, $variantId) {
                return !((int)$i['product_id'] === $productId && (int)($i['variant_id'] ?? 0) === (int)$variantId);
            }));
        }

        self::put($items);
        return $items;
    }

    public static function clear(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    /** ===== Accessors ===== */
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

    /** ===== Auction-specific API (NEW) ===== */

    /**
     * Add a BUY-NOW auction line (qty=1) at the product's buy_now_price.
     * Stores auction metadata so Checkout can validate again.
     */
    public static function addAuctionBuyNow(int $auctionId, int $productId): int
    {
        $items = self::items();

        // For uniqueness in cart, include auction id in "variant" slot so
        // a regular listing and an auction listing don't merge accidentally.
        $variantId = 0 - abs($auctionId); // negative id sentinel
        $key       = self::findIndex($items, $productId, $variantId);

        $product = Product::query()
            ->where('id', $productId)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->first(['id','slug','name','price','buy_now_price','images','vendor_id']);

        if (!$product) return self::count();

        $price = (float) ($product->buy_now_price ?? 0);
        if ($price <= 0) return self::count();

        $img = (is_array($product->images) && count($product->images)) ? $product->images[0] : null;

        $line = [
            'product_id'    => $product->id,
            'vendor_id'     => $product->vendor_id,
            'variant_id'    => $variantId,
            'variant_label' => 'Buy Now',
            'slug'          => $product->slug,
            'name'          => $product->name,
            'image'         => $img,
            'quantity'      => 1,
            'unit_amount'   => $price,
            'total_amount'  => $price,
            'is_auction'    => true,
            'auction_id'    => $auctionId,
            'meta'          => [
                'is_auction'    => true,
                'auction_id'    => $auctionId,
                // tells checkout to trust the captured price & allow even if auction not ended
                'unit_override' => $price,
                'source'        => 'buy_now',
            ],
        ];

        $items = self::pushOrMerge($items, $key, $line, false);
        self::put($items);
        return self::count();
    }

    /**
     * Add a WINNING auction line (qty=1) at the final/winning amount.
     * You may pass $finalAmount if you already computed it on the page.
     */
    public static function addAuctionWinner(int $auctionId, int $productId, ?float $finalAmount = null): int
    {
        $items     = self::items();
        $variantId = 0 - abs($auctionId) - 1; // different sentinel from buy-now
        $key       = self::findIndex($items, $productId, $variantId);

        $product = Product::query()
            ->where('id', $productId)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->first(['id','slug','name','price','buy_now_price','images','vendor_id']);

        if (!$product) return self::count();

        $price = (float) ($finalAmount ?? 0);
        if ($price <= 0) return self::count();

        $img = (is_array($product->images) && count($product->images)) ? $product->images[0] : null;

        $line = [
            'product_id'    => $product->id,
            'vendor_id'     => $product->vendor_id,
            'variant_id'    => $variantId,
            'variant_label' => 'Winning Bid',
            'slug'          => $product->slug,
            'name'          => $product->name,
            'image'         => $img,
            'quantity'      => 1,
            'unit_amount'   => $price,
            'total_amount'  => $price,
            'is_auction'    => true,
            'auction_id'    => $auctionId,
            'meta'          => [
                'is_auction'            => true,
                'auction_id'            => $auctionId,
                'final_auction_amount'  => $price,
                'source'                => 'winner',
            ],
        ];

        $items = self::pushOrMerge($items, $key, $line, false);
        self::put($items);
        return self::count();
    }

    /** ===== Internal helpers ===== */

    private static function pushOrMerge(array $items, ?int $key, array $line, bool $mergeQty): array
    {
        if ($key !== null) {
            if ($mergeQty) {
                $items[$key]['quantity'] += (int) $line['quantity'];
            } else {
                $items[$key]['quantity'] = 1; // auctions fixed
            }
            $items[$key]['unit_amount']  = (float) $line['unit_amount'];
            $items[$key]['total_amount'] = (float) $items[$key]['quantity'] * (float) $items[$key]['unit_amount'];
            $items[$key] = array_replace($items[$key], $line);
        } else {
            $items[] = $line;
        }
        return array_values($items);
    }

    private static function findIndex(array $items, int $productId, ?int $variantId = null): ?int
    {
        foreach ($items as $k => $i) {
            $sameProduct = ((int)$i['product_id'] === $productId);
            $sameVariant = ((int)($i['variant_id'] ?? 0) === (int)($variantId ?? 0));
            if ($sameProduct && $sameVariant) return $k;
        }
        return null;
    }

    private static function put(array $items): void
    {
        Cookie::queue(self::COOKIE, json_encode(array_values($items)), self::TTL);
    }

    private static function effectivePrice(float $base, float $buyNow): float
    {
        return ($buyNow > 0 && $buyNow < $base) ? $buyNow : $base;
    }

    /**
     * Load variant metadata (effective price + label) or null if invalid.
     * Safe when product_variants.buy_now_price does not exist.
     *
     * @return array{id:int,label:string,price:float}|null
     */
    private static function getVariantMeta(int $productId, int $variantId): ?array
    {
        $hasVarBuyNow = Schema::hasColumn('product_variants', 'buy_now_price');

        $baseSelect = "
            pv.id,
            pv.price,
            " . ($hasVarBuyNow ? "pv.buy_now_price" : "NULL as buy_now_price") . ",
            GROUP_CONCAT(CONCAT(va.name, ': ', vav.value) ORDER BY va.name SEPARATOR ', ') as label
        ";

        $row = DB::table('product_variants as pv')
            ->leftJoin('product_variant_attribute_value as pvav', 'pvav.variant_id', '=', 'pv.id')
            ->leftJoin('variant_attribute_values as vav', 'vav.id', '=', 'pvav.attribute_value_id')
            ->leftJoin('variant_attributes as va', 'va.id', '=', 'vav.attribute_id')
            ->where('pv.product_id', $productId)
            ->where('pv.id', $variantId)
            ->groupBy('pv.id', 'pv.price' . ($hasVarBuyNow ? ', pv.buy_now_price' : ''))
            ->selectRaw($baseSelect)
            ->first();

        if (!$row) {
            return null;
        }

        $buyNow = (float)($row->buy_now_price ?? 0);
        $eff    = self::effectivePrice((float)$row->price, $buyNow);
        $label  = $row->label ?: ('Variant #' . $row->id);

        return [
            'id'    => (int)$row->id,
            'label' => $label,
            'price' => $eff,
        ];
    }

    /** ---- compatibility with old method names (unchanged) ---- */
    public static function addItemsToCart(int $product_id): int
    {
        return self::addItem($product_id, 1, null);
    }
    public static function addItemsToCartwithQty(int $product_id, int $qty = 1): int
    {
        return self::addItem($product_id, $qty, null);
    }
    public static function incrementQuantityToCartItem(int $product_id): array
    {
        return self::increment($product_id, null);
    }
    public static function decrementQuantityToCartItem(int $product_id): array
    {
        return self::decrement($product_id, null);
    }
    public static function removeCartItem(int $product_id): array
    {
        // legacy: removes all lines of this product (even if it has multiple variants)
        return self::remove($product_id, null);
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
