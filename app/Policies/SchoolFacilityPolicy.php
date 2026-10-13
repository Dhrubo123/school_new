<?php

namespace App\Policies;

use App\Models\SchoolFacility;
use App\Models\User;

class SchoolFacilityPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, SchoolFacility $facility): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, SchoolFacility $facility): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, SchoolFacility $facility): bool
    {
        return $user->hasRole('admin');
    }
}
