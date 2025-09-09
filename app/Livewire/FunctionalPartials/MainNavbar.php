<?php

// app/Livewire/FunctionalPartials/MainNavbar.php

namespace App\Livewire\FunctionalPartials;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Support\CartManagement;
use App\Models\Category;
use App\Models\VendorProfile;

class MainNavbar extends Component
{
    public int $total_count = 0;

    /** @var \Illuminate\Support\Collection<VendorProfile> */
    public $vendors;

    public array $rootCategories = [];
    public array $childrenByParent = [];

    public function mount(): void
    {
        // Initial count from cookie/cart store
        $this->total_count = count(CartManagement::getCartItemsFromCookie());

        // Vendors for the Shops dropdown (includes logo_path for accessor)
        $this->vendors = VendorProfile::query()
            ->where('published', true)
            ->orderBy('store_name')
            ->limit(60)
            ->get(['id','user_id','slug','store_name','logo_path']);

        // Categories (roots + direct children only)
        $roots = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderByRaw('COALESCE(order_column, 999999)')
            ->orderBy('name')
            ->get(['id','name','slug','image']);

        $this->rootCategories = $roots->map(fn($c) => [
            'id'    => (int) $c->id,
            'name'  => (string) $c->name,
            'slug'  => $c->slug,
            'image' => $c->image,
        ])->values()->all();

        $children = Category::query()
            ->whereIn('parent_id', $roots->pluck('id'))
            ->where('is_active', true)
            ->orderByRaw('COALESCE(order_column, 999999)')
            ->orderBy('name')
            ->get(['id','name','slug','image','parent_id']);

        $grouped = [];
        foreach ($children as $c) {
            $pid = (int) $c->parent_id;
            $grouped[$pid] ??= [];
            $grouped[$pid][] = [
                'id'    => (int) $c->id,
                'name'  => (string) $c->name,
                'slug'  => $c->slug,
                'image' => $c->image,
            ];
        }
        $this->childrenByParent = $grouped;
    }

    #[On('update-cart-count')]
    public function updateCartCount(int $total_count): void
    {
        // Fast path: when caller sends the number explicitly
        $this->total_count = $total_count;
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        // Fallback: recompute from cookie (covers any place that only emits a generic ping)
        $this->total_count = count(CartManagement::getCartItemsFromCookie());
    }

    public function render()
    {
        return view('livewire.functional-partials.main-navbar', [
            'rootCategories'   => $this->rootCategories,
            'childrenByParent' => $this->childrenByParent,
            'vendors'          => $this->vendors,
        ]);
    }
}
