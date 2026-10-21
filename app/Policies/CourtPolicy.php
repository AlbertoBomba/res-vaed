<?php

namespace App\Policies;

use App\Models\Court;
use App\Models\User;

class CourtPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator() && ($user->company_id !== null || $user->hasRole('super-admin'));
    }

    public function update(User $user, Court $court): bool
    {
        return $user->isAdministrator()
            && ($user->hasRole('super-admin') || $user->company_id === $court->company_id);
    }

    public function delete(User $user, Court $court): bool
    {
        return $this->update($user, $court);
    }

    public function restore(User $user, Court $court): bool
    {
        return $this->update($user, $court);
    }
}
