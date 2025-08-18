<?php

namespace App\Livewire\Front\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use App\Support\CartManagement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Livewire\FunctionalPartials\MainNavbar;
use App\Models\VendorProfile;

class ShopPage extends Component
{
    use WithPagination;

    // Sidebar & toolbar state
    public array $selected_categories = [];
    public string $search = '';
    public string $sort = 'latest'; // latest | price
    public int $perPage = 30;

    // Price slider
    public int $price_range = 0;     // currently selected max
    public int $priceMax = 0;        // dynamic max bound

    // Pre-fetched sidebar data
    public Collection $categories;

    protected $queryString = [
        'search' => ['except' => ''],
        'sort'   => ['except' => 'latest'],
        'selected_categories' => ['except' => []],
    ];

    public function mount()
    {
        // Parents only, active
        $this->categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderByRaw('COALESCE(order_column, 999999)')
            ->orderBy('name')
            ->get();

        // Find a reasonable max price from current catalog
        $maxPrice = Product::query()
            ->where('is_active', true)
            ->where('status', 'approved')
            ->selectRaw('MAX(GREATEST(price, COALESCE(NULLIF(buy_now_price,0), price))) as m')
            ->value('m') ?? 0;

        // Fall back to a sensible cap if empty catalog
        $this->priceMax    = max((int) ceil($maxPrice), 1000);
        $this->price_range = $this->priceMax;
    }

    public function updating($field)
    {
        // Reset to page 1 on any filter/sort/search change
        if (in_array($field, ['search','sort','selected_categories','price_range'])) {
            $this->resetPage();
        }
    }

    public function addToCart(int $productId): void
    {
        $total_count = CartManagement::addItem($productId, 1);
        $this->dispatch('update-cart-count', total_count: $total_count)->to(MainNavbar::class);
        session()->flash('success', 'Added to cart');
    }

    private function descendantCategoryIds(array $parentIds): array
    {
        // Collect all descendants (multi-level) of selected parent ids
        $all = collect($parentIds);
        $frontier = collect($parentIds);

        do {
            $children = Category::whereIn('parent_id', $frontier)->pluck('id');
            $new = $children->diff($all);
            $all = $all->merge($new);
            $frontier = $new;
        } while ($frontier->isNotEmpty());

        return $all->all();
    }

    public function getProductsProperty()
{
    $q = Product::query()
        ->select('products.*') // keep base columns
        ->where('is_active', true)
        ->where('status', 'approved');

    // Search
    if (strlen($this->search)) {
        $s = trim($this->search);
        $q->where('name', 'like', "%{$s}%");
    }

    // Category filter (parents + descendants)
    if (!empty($this->selected_categories)) {
        $ids = $this->descendantCategoryIds($this->selected_categories);
        $q->whereIn('category_id', $ids);
    }

    // Price filter on effective price
    $q->whereRaw('COALESCE(NULLIF(buy_now_price,0), price) <= ?', [$this->price_range]);

    // Review aggregates (no N+1)
    $q->withCount([
        'reviews as reviews_count' => fn($r) => $r->where('visible', true)->where('status', 'approved'),
    ])->withAvg(
        ['reviews as reviews_avg_rating' => fn($r) => $r->where('visible', true)->where('status', 'approved')],
        'rating'
    );

    // Pull vendor store name (and slug) via subselect (no N+1)
    $q->addSelect([
        'store_name' => VendorProfile::query()
            ->select('store_name')
            ->whereColumn('vendor_profiles.user_id', 'products.vendor_id')
            ->limit(1),
        // Optional: keep for future linking
        'store_slug' => VendorProfile::query()
            ->select('slug')
            ->whereColumn('vendor_profiles.user_id', 'products.vendor_id')
            ->limit(1),
    ]);

    // Sort
    if ($this->sort === 'price') {
        $q->orderByRaw('COALESCE(NULLIF(buy_now_price,0), price) ASC, id DESC');
    } else {
        $q->latest('id');
    }

    return $q->paginate($this->perPage)->withQueryString();
}


    public function render()
    {
        return view('livewire.front.pages.shop-page', [
            'products' => $this->products, // accessor above
        ]);
    }
}
