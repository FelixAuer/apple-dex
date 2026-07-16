<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Variety;

class VarietyPolicy
{
    /**
     * Global varieties are visible to everyone; custom varieties only to their owner.
     */
    public function view(User $user, Variety $variety): bool
    {
        return $variety->user_id === null || $variety->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the owner of a custom variety may edit it. Global varieties are read-only.
     */
    public function update(User $user, Variety $variety): bool
    {
        return $variety->user_id !== null && $variety->user_id === $user->id;
    }

    public function delete(User $user, Variety $variety): bool
    {
        return $variety->user_id !== null && $variety->user_id === $user->id;
    }
}
