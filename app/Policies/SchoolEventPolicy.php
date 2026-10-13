<?php

namespace App\Policies;

use App\Models\SchoolEvent;
use App\Models\User;

class SchoolEventPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, SchoolEvent $event): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, SchoolEvent $event): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, SchoolEvent $event): bool
    {
        return $user->hasRole('admin');
    }
}
