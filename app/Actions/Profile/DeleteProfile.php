<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Models\User;
use Illuminate\Database\DatabaseManager;

readonly class DeleteProfile
{
    public function __construct(private DatabaseManager $db) {}

    public function execute(User $user): void
    {
        $this->db->transaction(
            function () use ($user): void {
                $user->tasks()->delete();
                $user->recurringTasks()->forceDelete();
                $user->categories()->delete();
                $user->delete();
            }
        );
    }
}
