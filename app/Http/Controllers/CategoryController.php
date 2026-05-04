<?php

namespace App\Http\Controllers;

use App\Actions\Category\CreateCategory;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $categories = $user->categories()->latest()->paginate();

        return view('categories.index', [
            'categories' => $categories->toResourceCollection(CategoryResource::class)->resolve(),
            'links' => fn () => $categories->links(),
        ]);
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(StoreCategoryRequest $request, CreateCategory $createCategory)
    {
        $createCategory->execute($request->validated(), $request->user());

        return to_route('categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', [
            'category' => (new CategoryResource($category))->resolve(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return to_route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->noContent();
    }
}
