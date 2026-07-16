<?php

namespace App\Policies;

use App\Models\AppleCatch;
use App\Models\User;

class CatchPolicy
{
    public function view(User $user, AppleCatch $appleCatch): bool
    {
        return $appleCatch->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AppleCatch $appleCatch): bool
    {
        return $appleCatch->user_id === $user->id;
    }

    public function delete(User $user, AppleCatch $appleCatch): bool
    {
        return $appleCatch->user_id === $user->id;
    }
}
