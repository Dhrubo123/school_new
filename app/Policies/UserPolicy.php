<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasRole('super-admin', 'admin');
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->id !== $user->id && ($actor->hasRole('super-admin') || ($actor->hasRole('admin') && $user->hasRole('teacher', 'guardian', 'student')));
    }

    public function create(User $actor): bool
    {
        return $actor->hasRole('super-admin', 'admin');
    }

    public function update(User $actor, User $user): bool
    {
        return $this->view($actor, $user);
    }

    public function delete(User $actor, User $user): bool
    {
        return $this->view($actor, $user);
    }
}
