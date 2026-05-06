<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Models\User;
use SensitiveParameter;
use Illuminate\Contracts\Hashing\Hasher;

readonly class UpdatePassword
{
    public function __construct(private Hasher $hasher) {}

    public function execute(User $user, #[SensitiveParameter] string $password): User
    {
        $user->update(['password' => $this->hasher->make($password)]);

        return $user;
    }
}
