<?php

use App\Http\Controllers\TestController;
use App\Livewire\Admin\Dashboard\Index;
use App\Livewire\Admin\Pages\AnalyticsDashboard;
use App\Livewire\Admin\Pages\AuctionManagement;
use App\Livewire\Admin\Pages\CategoryManagement;
use App\Livewire\Admin\Pages\ProductManagement;
use App\Livewire\Admin\Pages\UserManagement;
use App\Livewire\Admin\Pages\OrderManagement;
use App\Livewire\Admin\Pages\OrderDetail;
use App\Livewire\Admin\Pages\ProductVariants;
use App\Livewire\Admin\Pages\ReviewModeration;
use App\Livewire\Admin\Pages\VendorList;
use Illuminate\Support\Facades\Route;
use App\Livewire\NotificationsPage;
use App\Livewire\Vendor\Pages\AnalyticsDashboard as PagesAnalyticsDashboard;
use Illuminate\Support\Facades\Mail;

use App\Livewire\Vendor\Pages\StoreProfile as VendorStoreProfile;
use App\Livewire\Front\Store\Show as PublicStoreShow;
use App\Livewire\Vendor\Pages\Auctions;
use App\Livewire\Vendor\Pages\ProductManagement as PagesProductManagement;
use App\Livewire\Front\Pages\HomePage;
use App\Livewire\Front\Pages\ShopPage;
use App\Livewire\Front\Pages\ProductDetailPage;
use App\Livewire\Front\Pages\CartPage;

Route::get('/cart', CartPage::class)->name('home.cart');


Route::get('/product/{slug}', ProductDetailPage::class)->name('store.product');

Route::get('/shop', ShopPage::class)->name('store.shop');

Route::get('/', HomePage::class)->name('store.home');

Route::middleware([
    // 'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Admin Routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AnalyticsDashboard::class)->name('dashboard');
    Route::get('/user-management', UserManagement::class)->name('user-management');
    Route::get('/category-management', CategoryManagement::class)->name('category-management');
    Route::get('/product-management', ProductManagement::class)->name('product-management');
    Route::get('/auction-management', AuctionManagement::class)->name('auction-management');
    Route::get('order-management', OrderManagement::class)->name('order-management');
    Route::get('orders/{order}', OrderDetail::class)->name('orders.show');
    
     Route::get('/reviews', ReviewModeration::class)->middleware('can:moderate,App\Models\Review')->name('reviews');
    Route::get('/manage-variants', ProductVariants::class)->name('manage-variants');
    Route::get('view-vendors', VendorList::class)->name('view-vendors');

    Route::get('/notifications', NotificationsPage::class)
     ->name('notifications.index');
     
});

// Vendor Routes.....
Route::middleware(['auth','vendor',])
    ->prefix('vendor')
    ->name('vendor.')
    ->group(function () {
        Route::get('dashboard', PagesAnalyticsDashboard::class)->name('dashboard');
        Route::get('/store-profile', VendorStoreProfile::class)->name('store.profile');
        Route::get('/products', PagesProductManagement::class)->name('products');
         Route::get('/auctions', Auctions::class)->name('auctions.index');
        Route::get('/notifications', NotificationsPage::class)
        ->name('notifications.index');
    });


    
Route::get('/store/{slug}', PublicStoreShow::class)->name('store.show');