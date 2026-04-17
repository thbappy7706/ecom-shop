<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wishlist;

class WishlistPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Wishlist $wishlist): bool
    {
        return $user->id === $wishlist->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, Wishlist $wishlist): bool
    {
        return $user->id === $wishlist->user_id;
    }
}
