<?php

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

    /** @var array<int,array{id:int,name:string,slug?:string,image?:string}> */
    public array $rootCategories = [];

    /** @var array<int,array<int,array{id:int,name:string,slug?:string,image?:string}>> parent_id => child[] */
    public array $childrenByParent = [];

    public function mount(): void
    {
        // Cart count
        $this->total_count = count(CartManagement::getCartItemsFromCookie());

        // Vendors (as before)
        $this->vendors = VendorProfile::query()
            ->where('published', true)
            ->orderBy('store_name')
            ->limit(60)
            ->get(['id','user_id','slug','store_name']);

        // Categories: fetch roots + only their direct children (no deep recursion)
        $roots = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderByRaw('COALESCE(order_column, 999999)')
            ->orderBy('name')
            ->get(['id','name','slug','image']);

        $this->rootCategories = $roots->map(function($c){
            return [
                'id'    => (int) $c->id,
                'name'  => (string) $c->name,
                'slug'  => $c->slug,
                'image' => $c->image,
            ];
        })->values()->all();

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
        $this->total_count = $total_count;
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
