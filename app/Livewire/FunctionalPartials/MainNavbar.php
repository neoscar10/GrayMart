<?php

namespace App\Livewire\FunctionalPartials;

use Livewire\Component;
use App\Support\CartManagement;
use App\Models\Category;
use Livewire\Attributes\On;

class MainNavbar extends Component
{
     public $total_count = 0;

    public function mount(){
        $this->total_count = count(CartManagement::getCartItemsFromCookie());
    }

    //listening to sent event from product to update dom
    #[On("update-cart-count")]
    public function UpdateCartCount($total_count){
        $this->total_count = $total_count;
    }
    public function render()
    {
        $categories = Category::whereNull('parent_id')->get();
        return view('livewire.functional-partials.main-navbar',[
            'categories' => $categories,
        ]);
    }
}
