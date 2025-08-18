<?php

namespace App\Livewire\Front\Pages;

use Livewire\Component;
use App\Models\Category;
use App\Models\Product;
use App\Models\VendorProfile;
use App\Support\CartManagement;

class HomePage extends Component
{
    public array $flashProductIds = []; // optional: to keep slider stable

    public function mount()
    {
        // Optional: snapshot product IDs to prevent reshuffle on re-render
        $this->flashProductIds = Product::query()
            ->where('is_active', true)
            ->where('status', 'approved')
            ->latest('id')
            ->limit(12)
            ->pluck('id')
            ->toArray();
    }

    public function addToCart(int $productId): void
    {
        // Assumes your cookie-based helper exists
        CartManagement::addItem($productId, 1);
        $this->dispatch('cart-updated'); // in case you show cart count in header
        session()->flash('success', 'Added to cart');
    }

    public function getParentCategoriesProperty()
    {
        return Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderByRaw('COALESCE(order_column, 999999)')
            ->orderBy('name')
            ->limit(12)
            ->get();
    }

    public function getFlashProductsProperty()
    {
        $q = Product::query()
            ->where('is_active', true)
            ->where('status', 'approved')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        if (!empty($this->flashProductIds)) {
            $q->whereIn('id', $this->flashProductIds)
              ->orderByRaw('FIELD(id,'.implode(',', $this->flashProductIds).')');
        } else {
            $q->latest('id')->limit(12);
        }

        return $q->get();
    }

    public function getBrandsProperty()
    {
        return VendorProfile::query()
            ->where('published', true)
            ->whereNotNull('logo_path')
            ->latest('id')
            ->limit(12)
            ->get();
    }

    public function render()
    {
        return view('livewire.front.pages.home-page', [
            'parentCategories' => $this->parentCategories,
            'flashProducts'    => $this->flashProducts,
            'brands'           => $this->brands,
        ]); 
    }
}
