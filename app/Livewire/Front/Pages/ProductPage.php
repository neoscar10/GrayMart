<?php

namespace App\Livewire\Front\Pages;

use Livewire\Component;
use App\Models\Product;
use App\Models\VendorProfile;
use App\Support\CartManagement;
use App\Livewire\FunctionalPartials\MainNavbar;

class ProductPage extends Component
{
    public string $slug;

    public ?Product $product = null;
    public array $images = [];
    public int $imageIndex = 0;

    public bool $hasVariants = false;
    public array $attributeGroups = [];   // [attribute_id => ['name'=>..., 'values'=>[['id'=>..,'label'=>..]]]]
    public array $selected = [];          // [attribute_id => attribute_value_id]
    public ?int $selectedVariantId = null;

    public int $quantity = 1;
    public ?int $stock = null;

    public ?float $price = null;          // current display price (base or selected variant)
    public ?float $priceMin = null;       // min across variants
    public ?float $priceMax = null;       // max across variants

    public ?string $storeName = null;
    public ?string $storeSlug = null;
    public ?string $storeLogo = null;

    public ?float $ratingAvg = null;
    public int $ratingCount = 0;

    /** Related products (same category, not this product) */
    public $relatedProducts;

    protected $queryString = ['slug'];

    public function mount(string $slug)
    {
        $this->slug = $slug;

        $p = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->with(['variants.attributeValues.attribute']) // variant attributes
            ->withCount([
                'reviews as reviews_count' => fn($r) => $r->where('visible', true)->where('status', 'approved'),
            ])
            ->withAvg(
                ['reviews as reviews_avg_rating' => fn($r) => $r->where('visible', true)->where('status', 'approved')],
                'rating'
            )
            ->firstOrFail();

        $this->product = $p;

        // Vendor store details
        $vp = VendorProfile::where('user_id', $p->vendor_id)->first();
        $this->storeName = $vp?->store_name;
        $this->storeSlug = $vp?->slug;
        $this->storeLogo = $vp?->logo_url; // accessor in your model

        // Images
        $this->images = is_array($p->images) ? array_values($p->images) : [];
        if (empty($this->images)) {
            $this->images = ['images/placeholder.png']; // fallback
        }

        // Ratings
        $this->ratingAvg   = $p->reviews_avg_rating ? round((float) $p->reviews_avg_rating, 1) : null;
        $this->ratingCount = (int) ($p->reviews_count ?? 0);

        // Variants?
        $this->hasVariants = $p->variants && $p->variants->count() > 0;

        if ($this->hasVariants) {
            $this->buildAttributeGroupsAndPrices();
            $this->preselectVariant();
        } else {
            $this->price = $this->effectivePrice($p->price, $p->buy_now_price);
            $this->stock = null; // non-variant flow (no stock tracking here)
        }

        // Related products (same category, newest first)
        $this->relatedProducts = Product::query()
            ->where('is_active', true)
            ->where('status', 'approved')
            ->where('category_id', $p->category_id)
            ->where('id', '!=', $p->id)
            ->with(['variants:id,product_id,price'])     // to compute min/max quickly
            ->withCount('variants')
            ->withCount([
                'reviews as reviews_count' => fn($r) => $r->where('visible', true)->where('status', 'approved'),
            ])
            ->withAvg(
                ['reviews as reviews_avg_rating' => fn($r) => $r->where('visible', true)->where('status', 'approved')],
                'rating'
            )
            ->latest('id')
            ->take(8)
            ->get();
    }

    /** Build attribute groups (for UI) & min/max variant prices. */
    private function buildAttributeGroupsAndPrices(): void
    {
        $groups = []; // [attr_id => ['name'=>..., 'values'=>[id=>label]]]
        $prices = [];

        foreach ($this->product->variants as $v) {
            $vp = $v->price ?? $this->product->price;
            if ($vp !== null) {
                $prices[] = (float) $vp;
            }
            foreach ($v->attributeValues as $val) {
                $aId = $val->attribute->id;
                $aNm = $val->attribute->name;
                $vId = $val->id;
                $vLb = $val->value;

                if (!isset($groups[$aId])) {
                    $groups[$aId] = [
                        'name'   => $aNm,
                        'values' => [],
                    ];
                }
                $groups[$aId]['values'][$vId] = $vLb; // de-duplicate
            }
        }

        // Normalize to array of ['id'=>..,'label'=>..]
        $normalized = [];
        foreach ($groups as $attrId => $data) {
            $values = [];
            foreach ($data['values'] as $vid => $label) {
                $values[] = ['id' => (int)$vid, 'label' => $label];
            }
            $normalized[$attrId] = [
                'name'   => $data['name'],
                'values' => $values,
            ];
        }
        $this->attributeGroups = $normalized;

        if (!empty($prices)) {
            $this->priceMin = min($prices);
            $this->priceMax = max($prices);
        }
    }

    /** Choose the first in-stock variant (or first at all) to prefill selections. */
    private function preselectVariant(): void
    {
        $choice = $this->product->variants->firstWhere(fn($v) => (int)($v->stock ?? 0) > 0)
            ?? $this->product->variants->first();

        if ($choice) {
            // Seed selected map from this variant
            $sel = [];
            foreach ($choice->attributeValues as $val) {
                $sel[$val->attribute->id] = $val->id;
            }
            $this->selected = $sel;

            $this->selectedVariantId = $choice->id;
            $this->stock = (int) ($choice->stock ?? 0);
            $this->price = (float) ($choice->price ?? $this->product->price);
        } else {
            // Defensive
            $this->selected = [];
            $this->selectedVariantId = null;
            $this->stock = 0;
            $this->price = $this->priceMin ?? $this->effectivePrice($this->product->price, $this->product->buy_now_price);
        }
    }

    /** When the user clicks an option. */
    public function selectOption(int $attributeId, int $valueId): void
    {
        $this->selected[$attributeId] = $valueId;
        $this->resolveVariantFromSelection();
    }

    /** Find matching variant (full match by selected attribute values). */
    private function resolveVariantFromSelection(): void
    {
        if (count($this->selected) !== count($this->attributeGroups)) {
            // Partial selection; show range/base
            $this->selectedVariantId = null;
            $this->stock = null;
            $this->price = $this->priceMin ?? $this->effectivePrice($this->product->price, $this->product->buy_now_price);
            return;
        }

        $targetValues = array_values($this->selected);
        sort($targetValues);
        $target = implode('-', $targetValues);

        $match = null;
        foreach ($this->product->variants as $v) {
            $ids = $v->attributeValues->pluck('id')->map(fn($i) => (int)$i)->sort()->implode('-');
            if ($ids === $target) { $match = $v; break; }
        }

        if ($match) {
            $this->selectedVariantId = $match->id;
            $this->stock = (int) ($match->stock ?? 0);
            $this->price = (float) ($match->price ?? $this->product->price);
        } else {
            $this->selectedVariantId = null;
            $this->stock = null;
            $this->price = $this->priceMin ?? $this->effectivePrice($this->product->price, $this->product->buy_now_price);
        }
    }

    public function setImage(int $i): void
    {
        if ($i >= 0 && $i < count($this->images)) {
            $this->imageIndex = $i;
        }
    }

    public function increaseQty(): void { $this->quantity++; }
    public function decreaseQty(): void { if ($this->quantity > 1) $this->quantity--; }

    public function addToCart(): void
    {
        if ($this->product->is_reserved) {
            session()->flash('error', 'This item is not purchasable.');
            return;
        }

        if ($this->hasVariants) {
            if (!$this->selectedVariantId) {
                session()->flash('error', 'Please choose options first.');
                return;
            }
            if ($this->stock !== null && $this->stock <= 0) {
                session()->flash('error', 'Selected option is out of stock.');
                return;
            }
            $total = CartManagement::addItemByVariant($this->product->id, $this->selectedVariantId, $this->quantity);
        } else {
            $total = CartManagement::addItem($this->product->id, $this->quantity);
        }

        $this->dispatch('update-cart-count', total_count: $total)->to(MainNavbar::class);
        session()->flash('success', 'Added to cart.');
    }

    private function effectivePrice($price, $buyNow): float
    {
        $p = (float) $price;
        $b = (float) ($buyNow ?? 0);
        return ($b > 0 && $b < $p) ? $b : $p;
    }

    public function render()
    {
        return view('livewire.front.pages.product-page');
    }
}
