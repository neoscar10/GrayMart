<?php

namespace App\Livewire\Front\Store;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\VendorProfile;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class Show extends Component
{
    use WithPagination;

    public VendorProfile $profile;

    /** Filters via query string */
    public ?string $q = null;        // text search
    public ?int    $category = null; // category id
    public ?float  $minPrice = null;
    public ?float  $maxPrice = null;
    public string  $sort = 'latest'; // latest|price_asc|price_desc
    public int     $perPage = 12;

    /** Optional: focus a product id (?product=123) */
    public ?int $product = null;

    protected $queryString = [
        'q'        => ['except' => null],
        'category' => ['except' => null],
        'minPrice' => ['except' => null],
        'maxPrice' => ['except' => null],
        'sort'     => ['except' => 'latest'],
        'perPage'  => ['except' => 12],
        'product'  => ['except' => null],
    ];

    public function mount(string $slug): void
    {
        $this->profile = VendorProfile::with('user')
            ->where('slug', $slug)
            ->where('published', true)
            ->firstOrFail();

        $this->product = request()->integer('product') ?: null;
    }

    /** Reset pagination when filters change */
    public function updating($name, $value): void
    {
        if (in_array($name, ['q','category','minPrice','maxPrice','sort','perPage'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $products = $this->buildProductQuery()->paginate($this->perPage);

        // Build the view once
        $view = view('livewire.front.store.show', compact('products'));

        // If admin or the store owner: wrap with vendor/admin layout; else: plain view (no layout)
        return $this->shouldUseVendorLayout()
            ? $view->layout('components.layouts.vendor')
            : $view;
    }

    /** Query with filters */
    protected function buildProductQuery(): Builder
    {
        $query = Product::query()
            ->with(['category:id,name'])
            ->where('vendor_id', $this->profile->user_id)
            ->where('is_active', true);

        if ($this->q) {
            $q = trim($this->q);
            $query->where(function (Builder $qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                   ->orWhere('slug', 'like', "%{$q}%")
                   ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($this->category) {
            $query->where('category_id', $this->category);
        }

        if ($this->minPrice !== null) {
            $query->where('price', '>=', (float) $this->minPrice);
        }

        if ($this->maxPrice !== null) {
            $query->where('price', '<=', (float) $this->maxPrice);
        }

        switch ($this->sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc')->orderBy('id', 'desc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc')->orderBy('id', 'desc');
                break;
            case 'latest':
            default:
                $query->latest('id');
        }

        return $query;
    }

    /** Admins or the store owner get the vendor/admin layout */
    protected function shouldUseVendorLayout(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Owner
        if ((int) $user->id === (int) $this->profile->user_id) {
            return true;
        }

        // Admin (Spatie or your own guard)
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        return false;
    }
}
