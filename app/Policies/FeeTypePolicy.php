<?php

namespace App\Policies;

use App\Models\FeeType;
use App\Models\User;

class FeeTypePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, FeeType $feeType): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, FeeType $feeType): bool
    {
        return $user->hasRole('admin');
    }
}
