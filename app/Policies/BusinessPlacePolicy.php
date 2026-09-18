<?php

namespace App\Policies;

use App\Models\BusinessPlace;
use App\Models\User;

class BusinessPlacePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProvider();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BusinessPlace $businessPlace): bool
    {
        return $user->isProvider() && $businessPlace->provider_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isProvider();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BusinessPlace $businessPlace): bool
    {
        return $user->isProvider() && $businessPlace->provider_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BusinessPlace $businessPlace): bool
    {
        return $user->isProvider() && $businessPlace->provider_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BusinessPlace $businessPlace): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BusinessPlace $businessPlace): bool
    {
        return false;
    }
}
