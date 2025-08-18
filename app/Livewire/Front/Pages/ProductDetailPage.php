<?php

namespace App\Livewire\Front\Pages;

use Livewire\Component;
use App\Models\Product;
use App\Support\CartManagement;

class ProductDetailPage extends Component
{
    public Product $product;

    public function mount(string $slug)
    {
        $this->product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->with(['vendor','category'])
            ->firstOrFail();
    }

    public function addToCart(): void
    {
        CartManagement::addItem($this->product->id, 1);
        $this->dispatch('cart-updated');
        session()->flash('success', 'Added to cart');
    }

    public function render()
    {
        return view('livewire.front.pages.product-detail-page')
            ->layout('layouts.store');
    }
}
