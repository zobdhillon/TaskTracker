<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

// use Illuminate\Auth\Access\Response;

class CategoryPolicy
{
    public function manage(User $user, Category $category): bool
    {
        return $category->user->is($user);
    }
}
