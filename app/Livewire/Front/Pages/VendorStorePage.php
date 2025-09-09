<?php

// app/Livewire/Front/Pages/VendorStorePage.php

namespace App\Livewire\Front\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\VendorProfile;
use App\Models\Product;
use App\Livewire\FunctionalPartials\MainNavbar;
use App\Support\CartManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VendorStorePage extends Component
{
    use WithPagination;

    public VendorProfile $vendor;

    // Variant picker
    public bool $showVariantModal = false;
    public ?int $selectedProductId = null;
    public ?int $selectedVariantId = null;
    public array $variantOptions = [];
    public ?string $variantProductName = null;

    public int $perPage = 30;

    public function mount(VendorProfile $vendor)
    {
        abort_unless($vendor->published, 404);
        $this->vendor = $vendor;
    }

    public function updating($field): void
    {
        if (in_array($field, ['perPage'], true)) {
            $this->resetPage();
        }
    }

    /* ------------ Cart / Variants ------------ */

    public function addToCart(int $productId): void
    {
        $total_count = CartManagement::addItem($productId, 1);
        $this->dispatch('cart-updated');
        $this->dispatch('update-cart-count', total_count: $total_count)->to(MainNavbar::class);
        session()->flash('success', 'Added to cart');
    }

    public function openVariantPicker(int $productId, string $productName = ''): void
    {
        $this->selectedProductId  = $productId;
        $this->variantProductName = $productName ?: (Product::find($productId)->name ?? 'Choose Options');
        $this->selectedVariantId  = null;
        $this->variantOptions     = $this->loadVariantOptions($productId);
        $this->showVariantModal   = true;
    }

    public function closeVariantPicker(): void
    {
        $this->showVariantModal   = false;
        $this->selectedVariantId  = null;
        $this->selectedProductId  = null;
        $this->variantOptions     = [];
        $this->variantProductName = null;
    }

    public function confirmVariantAddToCart(): void
    {
        if (!$this->selectedProductId || !$this->selectedVariantId) return;

        CartManagement::addItem($this->selectedProductId, 1, $this->selectedVariantId);
        $this->dispatch('cart-updated');
        $this->closeVariantPicker();
        session()->flash('success', 'Variant added to cart');
    }

    private function loadVariantOptions(int $productId): array
    {
        if (!Schema::hasTable('product_variants')) return [];

        $rows = DB::table('product_variants as pv')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->leftJoin('product_variant_attribute_value as pvav', 'pvav.variant_id', '=', 'pv.id')
            ->leftJoin('variant_attribute_values as vav', 'vav.id', '=', 'pvav.attribute_value_id')
            ->leftJoin('variant_attributes as va', 'va.id', '=', 'vav.attribute_id')
            ->where('pv.product_id', $productId)
            ->groupBy('pv.id', 'pv.price', 'p.price', 'p.buy_now_price')
            ->selectRaw("
                pv.id,
                COALESCE(pv.price, COALESCE(NULLIF(p.buy_now_price,0), COALESCE(p.price,0), 0)) as eff_price,
                GROUP_CONCAT(CONCAT(va.name, ': ', vav.value) ORDER BY va.name SEPARATOR ', ') as label
            ")
            ->orderBy('eff_price')
            ->get();

        return $rows->map(function ($r) {
            $label = $r->label ?: ('Variant #' . $r->id);
            return ['id' => (int) $r->id, 'label' => $label, 'price' => (float) $r->eff_price];
        })->values()->all();
    }

    /* ------------ Data ------------ */

    public function getProductsProperty()
    {
        // Variant aggregates
        $pvAgg = null;
        if (Schema::hasTable('product_variants')) {
            $pvAgg = DB::table('product_variants as pv')
                ->join('products as p', 'p.id', '=', 'pv.product_id')
                ->groupBy('pv.product_id')
                ->selectRaw("
                    pv.product_id,
                    COUNT(*) as v_cnt,
                    MIN(COALESCE(pv.price, COALESCE(NULLIF(p.buy_now_price,0), COALESCE(p.price,0), 0))) as v_min_price,
                    MAX(COALESCE(pv.price, COALESCE(NULLIF(p.buy_now_price,0), COALESCE(p.price,0), 0))) as v_max_price
                ");
        }

        $q = Product::query()->from('products')
            ->where('products.vendor_id', $this->vendor->user_id)
            ->where('products.is_active', true)
            ->where('products.status', 'approved');

        if ($pvAgg) {
            $q->leftJoinSub($pvAgg, 'pv_agg', function ($join) {
                $join->on('pv_agg.product_id', '=', 'products.id');
            });
        }

        $select = [
            'products.*',
            DB::raw('COALESCE(NULLIF(products.buy_now_price,0), COALESCE(products.price,0), 0) as p_eff_price'),
        ];
        if ($pvAgg) {
            $select[] = DB::raw('COALESCE(pv_agg.v_cnt,0) as variants_count');
            $select[] = DB::raw('pv_agg.v_min_price');
            $select[] = DB::raw('pv_agg.v_max_price');
        }

        $q->select($select)
          ->withCount(['reviews as reviews_count' => fn($r) => $r->where('visible', true)->where('status', 'approved')])
          ->withAvg(['reviews as reviews_avg_rating' => fn($r) => $r->where('visible', true)->where('status', 'approved')], 'rating')
          ->latest('products.id');

        return $q->paginate($this->perPage)->withQueryString();
    }

    public function render()
    {
        return view('livewire.front.pages.vendor-store-page', [
            'products' => $this->products,
        ]);
    }
}
