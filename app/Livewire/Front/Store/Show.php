<?php

namespace App\Livewire\Front\Store;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\VendorProfile;
use App\Models\Product;

class Show extends Component
{
    use WithPagination;

    public VendorProfile $profile;

    public function mount(string $slug)
    {
        $this->profile = VendorProfile::with('user')->where('slug', $slug)
            ->where('published', true)
            ->firstOrFail();
    }

    public function render()
    {
        $products = Product::where('vendor_id', $this->profile->user_id)
            ->where('is_active', true)
            ->latest('id')
            ->paginate(12);

        return view('livewire.front.store.show', compact('products'))
            ->layout('components.layouts.vendor');
    }
}
