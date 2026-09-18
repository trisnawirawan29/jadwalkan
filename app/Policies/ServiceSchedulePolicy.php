<?php

namespace App\Policies;

use App\Models\ServiceSchedule;
use App\Models\User;

class ServiceSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProvider();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceSchedule $serviceSchedule): bool
    {
        return $user->isProvider() && $serviceSchedule->businessService->businessPlace->provider_id === $user->id;
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
    public function update(User $user, ServiceSchedule $serviceSchedule): bool
    {
        return $user->isProvider() && $serviceSchedule->businessService->businessPlace->provider_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceSchedule $serviceSchedule): bool
    {
        return $user->isProvider() && $serviceSchedule->businessService->businessPlace->provider_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceSchedule $serviceSchedule): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServiceSchedule $serviceSchedule): bool
    {
        return false;
    }
}
