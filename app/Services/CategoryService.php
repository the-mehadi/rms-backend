<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class CategoryService
{
    public function getPaginatedCategories(int $perPage): LengthAwarePaginator
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function createCategory(array $data): Category
    {
        $name = $data['name'];
        $slugBase = array_key_exists('slug', $data) && $data['slug'] !== null && $data['slug'] !== ''
            ? $data['slug']
            : $name;

        $slug = $this->makeUniqueSlug(Str::slug($slugBase));

        return Category::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'order' => $data['order'] ?? 0,
        ]);
    }

    public function updateCategory(Category $category, array $data): Category
    {
        $updateData = [];

        if (array_key_exists('name', $data)) {
            $updateData['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }

        if (array_key_exists('is_active', $data)) {
            $updateData['is_active'] = $data['is_active'];
        }

        if (array_key_exists('order', $data)) {
            $updateData['order'] = $data['order'];
        }

        $shouldRegenerateSlug =
            (array_key_exists('name', $data) && !array_key_exists('slug', $data));

        if (array_key_exists('slug', $data) || $shouldRegenerateSlug) {
            $slugBase = array_key_exists('slug', $data) && $data['slug'] !== null && $data['slug'] !== ''
                ? $data['slug']
                : ($data['name'] ?? $category->name);

            $updateData['slug'] = $this->makeUniqueSlug(
                Str::slug($slugBase),
                ignoreCategoryId: $category->id
            );
        }

        $category->update($updateData);

        return $category->fresh();
    }

    public function deleteCategory(Category $category): void
    {
        $category->delete();
    }

    private function makeUniqueSlug(string $slug, ?int $ignoreCategoryId = null): string
    {
        $base = $slug !== '' ? $slug : 'category';
        $candidate = $base;
        $suffix = 2;

        while (
            Category::withTrashed()
                ->when($ignoreCategoryId !== null, fn ($q) => $q->whereKeyNot($ignoreCategoryId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}

