<?php

namespace App\Policies;

use App\Models\HomeBanner;
use App\Models\User;

class HomeBannerPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, HomeBanner $banner): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, HomeBanner $banner): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, HomeBanner $banner): bool
    {
        return $user->hasRole('admin');
    }
}
