<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['vendor','admin']);
    }

    public function view(User $user, Certificate $certificate): bool
    {
        return $user->role === 'admin' || optional($certificate->product)->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['vendor','admin']);
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return $user->role === 'admin' || optional($certificate->product)->user_id === $user->id;
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->role === 'admin' || optional($certificate->product)->user_id === $user->id;
    }
}
