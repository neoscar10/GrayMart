<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\User;

class AuctionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['vendor','admin']);
    }

    public function view(User $user, Auction $auction): bool
    {
        return $user->role === 'admin' || optional($auction->product)->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['vendor','admin']);
    }

    public function update(User $user, Auction $auction): bool
    {
        return $user->role === 'admin' || optional($auction->product)->user_id === $user->id;
    }

    public function delete(User $user, Auction $auction): bool
    {
        return $user->role === 'admin' || optional($auction->product)->user_id === $user->id;
    }
}
