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
use Illuminate\Support\Facades\Mail;


Route::get('/', function () {
    return view('welcome');
});

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

Route::get('test', [TestController::class, 'index']);
Route::get('/dev/test-mail', function() {
    Mail::raw('Testing mail via a quick route.', function($msg) {
        $msg->to('neoscar10@gmail.com')   // your admin email
            ->subject('Route Mail Test');
    });
    return 'Mail sent (or queued)! Check your inbox/Mailtrap.';
});