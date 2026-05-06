<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Models\User;

class UpdateProfile
{
    public function execute(User $user, array $profile): User
    {
        $user->name = $profile['name'];
        $user->email = $profile['email'];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user;
    }
}