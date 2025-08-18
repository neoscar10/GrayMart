<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Review;
use App\Models\Product;
use App\Models\Auction;
use App\Models\Certificate;
use App\Policies\ReviewPolicy;
use App\Policies\ProductPolicy;
use App\Policies\AuctionPolicy;
use App\Policies\CertificatePolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     * 
     */
    protected $policies = [
        Review::class      => ReviewPolicy::class,
        Product::class     => ProductPolicy::class,
        Auction::class     => AuctionPolicy::class,
        Certificate::class => CertificatePolicy::class,
        \App\Models\VendorProfile::class => \App\Policies\VendorProfilePolicy::class,
    ];
    public function register(): void
    {
        //
        $this->registerPolicies();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
