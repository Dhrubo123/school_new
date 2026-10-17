<?php

namespace App\Policies;

use App\Models\FeeInvoice;
use App\Models\User;

class FeeInvoicePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, FeeInvoice $invoice): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, FeeInvoice $invoice): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, FeeInvoice $invoice): bool
    {
        return $user->hasRole('admin');
    }
}
