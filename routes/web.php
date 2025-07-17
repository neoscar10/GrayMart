<?php

use App\Livewire\Admin\Dashboard\Index;
use App\Livewire\Admin\Pages\CategoryManagement;
use App\Livewire\Admin\Pages\ProductManagement;
use App\Livewire\Admin\Pages\UserManagement;
use Illuminate\Support\Facades\Route;

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
    Route::get('/dashboard', Index::class)->name('dashboard');
    Route::get('/user-management', UserManagement::class)->name('user-management');
    Route::get('/category-management', CategoryManagement::class)->name('category-management');
    Route::get('product-management', ProductManagement::class)->name('product-management');
});

