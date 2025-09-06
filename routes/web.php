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
use App\Livewire\Front\Pages\ProductPage;
use App\Livewire\Front\Pages\CheckoutPage;
use App\Http\Controllers\PayPalController;

use App\Livewire\Front\Pages\CheckoutSuccess;
use App\Livewire\Front\Pages\CheckoutCancel;
use App\Livewire\Vendor\Pages\VendorOrderManagement;
use App\Http\Controllers\OrderInvoiceController;
use App\Livewire\Front\Pages\AuctionShow;
use App\Livewire\Front\Pages\MyOrdersPage;
use App\Livewire\Front\Store\Show as StoreShow;
use App\Livewire\Vendor\Pages\AuctionDetails;
use App\Http\Controllers\Dev\PayPalDiagnosticsController;




Route::get('/auctions/{auction}', AuctionShow::class)->name('store.auctions.show');
Route::get('/store/{slug}', StoreShow::class)
    ->name('store.show'); // public vendor store page

Route::get('/checkout/success', CheckoutSuccess::class)->name('checkout.success');
Route::get('/checkout/cancel',  CheckoutCancel::class)->name('checkout.cancel');


Route::get('/cart', CartPage::class)->name('home.cart');


Route::get('/product/{slug}', ProductPage::class)->name('store.product');

Route::get('/shop', ShopPage::class)->name('store.shop');

Route::get('/', HomePage::class)->name('store.home');
Route::get('/checkout', CheckoutPage::class)->name('checkout');
Route::view('/orders/thank-you', 'thank-you')->name('orders.thankyou');
Route::get('/account/orders', MyOrdersPage::class)->name('account.orders');


Route::get('/orders/{order}/invoice', [OrderInvoiceController::class, 'download'])
        ->name('orders.invoice');


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

      Route::get('/admin/orders/{order}/invoice', [OrderInvoiceController::class, 'admin'])
        ->name('orders.invoice');
     
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
        Route::get('/vendor-order-management', VendorOrderManagement::class)->name('order-management');
        Route::get('/orders/{order}/invoice', [OrderInvoiceController::class, 'download'])
        ->name('orders.invoice');

        Route::get('/auctions/{auction}', AuctionDetails::class)->name('auction-details');
    });
        
Route::get('/store/{slug}', PublicStoreShow::class)->name('store.show');





Route::get('/dev/paypal/ping', [PayPalDiagnosticsController::class, 'ping'])
     ->middleware('web');
