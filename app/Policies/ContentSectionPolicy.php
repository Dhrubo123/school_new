<?php

namespace App\Policies;

use App\Models\ContentSection;
use App\Models\User;

class ContentSectionPolicy
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

    public function update(User $user, ContentSection $content): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, ContentSection $content): bool
    {
        return $user->hasRole('admin');
    }
}
