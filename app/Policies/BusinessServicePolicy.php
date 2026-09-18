<?php

namespace App\Policies;

use App\Models\BusinessService;
use App\Models\User;

class BusinessServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProvider();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BusinessService $businessService): bool
    {
        return $user->isProvider() && $businessService->businessPlace->provider_id === $user->id;
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
    public function update(User $user, BusinessService $businessService): bool
    {
        return $user->isProvider() && $businessService->businessPlace->provider_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BusinessService $businessService): bool
    {
        return $user->isProvider() && $businessService->businessPlace->provider_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BusinessService $businessService): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BusinessService $businessService): bool
    {
        return false;
    }
}
