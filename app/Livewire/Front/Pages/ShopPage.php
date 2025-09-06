<?php

namespace App\Livewire\Front\Pages;

use App\Livewire\FunctionalPartials\MainNavbar;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use App\Models\VendorProfile;
use App\Support\CartManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShopPage extends Component
{
    use WithPagination;

    // ===== Toolbar / search =====
    public string $search = '';
    public string $sort   = 'latest'; // latest | price
    public int $perPage   = 30;

    // ===== Price slider =====
    public int $price_range = 0;   // current slider value
    public int $priceMax    = 0;   // dynamic max bound

    // ===== Category drill-down (single path) =====
    // Bound to the query string as ?cat=ID, and also compatible with legacy ?selected_categories[0]=ID
    public ?int $currentCategoryId = null;

    // Precomputed category index for instant sidebar UX
    public array $childrenByParent = [];  // parent_id (int, root=0) => [child[], ...]
    public array $parentById       = [];  // id => parent_id|null
    public array $idToCategory     = [];  // id => ['id','name','image','parent_id']

    // ===== Variant picker modal =====
    public bool $showVariantModal = false;
    public ?int $selectedProductId = null;
    public ?int $selectedVariantId = null;
    public array $variantOptions = [];
    public ?string $variantProductName = null;

    protected $queryString = [
        'search'            => ['except' => ''],
        'sort'              => ['except' => 'latest'],
        'currentCategoryId' => ['except' => null, 'as' => 'cat'],
    ];

    public function mount(): void
    {
        $this->buildCategoryIndex();

        // Back-compat with old navbar links: /shop?selected_categories[0]=ID
        if ($this->currentCategoryId === null) {
            $legacy = request()->input('selected_categories');
            if (is_array($legacy) && !empty($legacy)) {
                $this->currentCategoryId = (int) reset($legacy);
            }
        }

        // Guard: null out if not a valid category id
        if ($this->currentCategoryId !== null && !isset($this->idToCategory[$this->currentCategoryId])) {
            $this->currentCategoryId = null;
        }

        // Price slider bounds (product and variant aware)
        $maxProduct = (int) (Product::query()
            ->where('is_active', true)
            ->where('status', 'approved')
            ->selectRaw('MAX(GREATEST(COALESCE(price,0), COALESCE(NULLIF(buy_now_price,0), COALESCE(price,0)))) as m')
            ->value('m') ?? 0);

        $maxVariant = 0;
        if (Schema::hasTable('product_variants')) {
            $maxVariant = (int) (DB::table('product_variants as pv')
                ->join('products as p', 'p.id', '=', 'pv.product_id')
                ->where('p.is_active', true)
                ->where('p.status', 'approved')
                ->selectRaw('MAX(COALESCE(pv.price, COALESCE(NULLIF(p.buy_now_price,0), COALESCE(p.price,0), 0))) as m')
                ->value('m') ?? 0);
        }

        $max = max($maxProduct, $maxVariant);
        $this->priceMax    = $max > 0 ? $max : 1000;
        $this->price_range = $this->priceMax;
    }

    public function updating($field): void
    {
        if (in_array($field, ['search','sort','price_range','currentCategoryId'], true)) {
            $this->resetPage();
        }
    }

    /* ---------------- Category Index ---------------- */

    protected function buildCategoryIndex(): void
    {
        $rows = Category::query()
            ->where('is_active', true)
            ->orderByRaw('COALESCE(order_column, 999999)')
            ->orderBy('name')
            ->get(['id','name','parent_id','image']);

        $this->childrenByParent = [];
        $this->parentById = [];
        $this->idToCategory = [];

        foreach ($rows as $c) {
            $pid = (int) ($c->parent_id ?? 0);
            $node = [
                'id'        => (int) $c->id,
                'name'      => (string) $c->name,
                'image'     => $c->image,
                'parent_id' => $c->parent_id ? (int) $c->parent_id : null,
            ];
            $this->childrenByParent[$pid][] = $node;
            $this->parentById[$node['id']] = $node['parent_id'];
            $this->idToCategory[$node['id']] = $node;
        }
    }

    // Click a category to drill down (or choose that branch)
    public function selectCategory(int $categoryId): void
    {
        if (!isset($this->idToCategory[$categoryId])) return;
        $this->currentCategoryId = $categoryId;
        $this->resetPage();
    }

    // Go to parent level
    public function goUpOne(): void
    {
        if ($this->currentCategoryId === null) return;
        $parent = $this->parentById[$this->currentCategoryId] ?? null;
        $this->currentCategoryId = $parent ?: null;
        $this->resetPage();
    }

    // Back to top
    public function goToRoot(): void
    {
        $this->currentCategoryId = null;
        $this->resetPage();
    }

    /* ---------------- Cart & Variants ---------------- */

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

    /* ---------------- Query helpers ---------------- */

    private function descendantCategoryIds(array $parentIds): array
    {
        if (empty($parentIds)) return [];
        $all = collect($parentIds)->map(fn($v) => (int) $v);
        $frontier = collect($parentIds)->map(fn($v) => (int) $v);

        do {
            $children = Category::whereIn('parent_id', $frontier)->pluck('id');
            $new      = $children->diff($all);
            $all      = $all->merge($new);
            $frontier = $new;
        } while ($frontier->isNotEmpty());

        return $all->map(fn($v) => (int)$v)->all();
    }

    public function getProductsProperty()
    {
        // Variant aggregates (min/max per product)
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

        $q = Product::query()->from('products');

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
          ->where('products.is_active', true)
          ->where('products.status', 'approved');

        // Search
        if (strlen($this->search)) {
            $s = trim($this->search);
            $q->where('products.name', 'like', "%{$s}%");
        }

        // Category filter: current node + all descendants
        if ($this->currentCategoryId !== null) {
            $ids = $this->descendantCategoryIds([$this->currentCategoryId]);
            // also include the node itself if it has no children
            $ids = array_unique([...$ids, $this->currentCategoryId]);
            $q->whereIn('products.category_id', $ids);
        }

        // Price filter (variant aware)
        $priceCap = (int) $this->price_range;
        if ($priceCap > 0) {
            $q->where(function ($qq) use ($priceCap, $pvAgg) {
                $qq->whereRaw('COALESCE(NULLIF(products.buy_now_price,0), COALESCE(products.price,0), 0) <= ?', [$priceCap]);
                if ($pvAgg) {
                    $qq->orWhereRaw('COALESCE(pv_agg.v_min_price, 0) <= ?', [$priceCap]);
                }
            });
        }

        // Reviews aggregates
        $q->withCount([
            'reviews as reviews_count' => fn($r) => $r->where('visible', true)->where('status', 'approved'),
        ])->withAvg(
            ['reviews as reviews_avg_rating' => fn($r) => $r->where('visible', true)->where('status', 'approved')],
            'rating'
        );

        // Vendor store info
        $q->addSelect([
            'store_name' => VendorProfile::query()
                ->select('store_name')->whereColumn('vendor_profiles.user_id', 'products.vendor_id')->limit(1),
            'store_slug' => VendorProfile::query()
                ->select('slug')->whereColumn('vendor_profiles.user_id', 'products.vendor_id')->limit(1),
        ]);

        // Sort
        if ($this->sort === 'price') {
            if ($pvAgg) {
                $q->orderByRaw("
                    COALESCE(
                        NULLIF(products.buy_now_price,0),
                        products.price,
                        pv_agg.v_min_price,
                        0
                    ) ASC,
                    products.id DESC
                ");
            } else {
                $q->orderByRaw('COALESCE(NULLIF(products.buy_now_price,0), products.price, 0) ASC, products.id DESC');
            }
        } else {
            $q->latest('products.id');
        }

        return $q->paginate($this->perPage)->withQueryString();
    }

    public function render()
    {
        // Current level list (children of current node; or roots when null)
        $level = $this->currentCategoryId !== null
            ? ($this->childrenByParent[$this->currentCategoryId] ?? [])
            : ($this->childrenByParent[0] ?? []);

        // Breadcrumbs for the focused node
        $breadcrumbs = [];
        $cur = $this->currentCategoryId;
        $guard = 0;
        while (!is_null($cur) && isset($this->idToCategory[$cur]) && $guard++ < 16) {
            array_unshift($breadcrumbs, $this->idToCategory[$cur]);
            $cur = $this->parentById[$cur] ?? null;
        }

        return view('livewire.front.pages.shop-page', [
            'products'         => $this->products,
            'levelCategories'  => $level,
            'childrenMap'      => $this->childrenByParent,
            'currentCategory'  => $this->currentCategoryId ? ($this->idToCategory[$this->currentCategoryId] ?? null) : null,
            'breadcrumbs'      => $breadcrumbs,
            'hasParent'        => $this->currentCategoryId !== null && ($this->parentById[$this->currentCategoryId] ?? null) !== null,
        ]);
    }
}
