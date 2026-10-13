<?php

namespace App\Policies;

use App\Models\AdmissionApplication;
use App\Models\User;

class AdmissionApplicationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, AdmissionApplication $admission): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, AdmissionApplication $admission): bool
    {
        return $user->hasRole('admin');
    }
}
