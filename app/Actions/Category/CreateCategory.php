<?php

namespace App\Actions\Category;

use App\Models\Category;
use App\Models\User;

class CreateCategory
{
    public function execute(array $categoryData, User $user): Category
    {
        /** @var Category $category */
        $category = $user->categories()->create($categoryData);

        return $category;
    }
}
