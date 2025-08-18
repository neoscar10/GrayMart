<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorProfile;

class VendorProfilePolicy
{
    public function view(User $user, VendorProfile $profile): bool
    {
        return $user->role === 'admin' || $profile->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['vendor','admin']);
    }

    public function update(User $user, VendorProfile $profile): bool
    {
        return $user->role === 'admin' || $profile->user_id === $user->id;
    }
}
