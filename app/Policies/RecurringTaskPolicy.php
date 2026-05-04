<?php

namespace App\Policies;

use App\Models\RecurringTask;
use App\Models\User;

class RecurringTaskPolicy
{
    public function manage(User $user, RecurringTask $recurringTask): bool
    {
        return $recurringTask->user->is($user);
    }
}
