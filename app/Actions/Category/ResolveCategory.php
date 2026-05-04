<?php

namespace App\Actions\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ResolveCategory
{
    public function execute(?string $uuid, User $user): ?int
    {
        if (! $uuid) {
            return null;
        }

        $category = Category::where('uuid', $uuid)->first();

        if (! $category || $user->cannot('manage', $category)) {
            throw ValidationException::withMessages(['category_id' => 'The selected category is invalid.']);
        }

        return $category->id;
    }
}
