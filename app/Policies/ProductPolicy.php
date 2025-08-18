<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['vendor','admin']);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->role === 'admin' || $product->vendor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['vendor','admin']);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->role === 'admin' || $product->vendor_id === $user->id;
    }

        public function delete(User $user, Product $product): bool
    {
        return $user->role === 'admin' || $product->vendor_id === $user->id;
    }
}
