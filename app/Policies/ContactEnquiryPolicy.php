<?php

namespace App\Policies;

use App\Models\ContactEnquiry;
use App\Models\User;

class ContactEnquiryPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, ContactEnquiry $enquiry): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, ContactEnquiry $enquiry): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, ContactEnquiry $enquiry): bool
    {
        return $user->hasRole('admin');
    }
}
