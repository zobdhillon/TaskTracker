<?php

namespace App\Actions\Task;

use App\Actions\Category\ResolveCategory;
use App\Models\User;

class CreateTask
{
    public function __construct(private readonly ResolveCategory $resolveCategory)
    {
        //
    }

    public function execute(array $taskData, User $user)
    {
        $taskData['category_id'] = $this->resolveCategory->execute(
            $taskData['category_id'],
            $user
        );

        return $user->tasks()->create($taskData);
    }
}
