<?php

namespace App\Services\MenuItem;

use App\Models\MenuItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class MenuItemService
{
    public function getPaginatedMenuItems(int $perPage, ?int $categoryId = null, ?bool $isAvailable = null): LengthAwarePaginator
    {
        return MenuItem::query()
            ->when($categoryId !== null, fn($q) => $q->where('category_id', $categoryId))
            ->when($isAvailable !== null, fn($q) => $q->where('is_available', $isAvailable))
            ->orderBy('order')
            ->orderByDesc('id')
            ->with('images')
            ->paginate($perPage);
    }

    public function getMenuItemById(int $id): MenuItem
    {
        return MenuItem::with('images')->findOrFail($id);
    }

    public function createMenuItem(array $data): MenuItem
    {
        $slugSource = array_key_exists('slug', $data) && !empty($data['slug'])
            ? $data['slug']
            : $data['name'];

        $slug = $this->makeUniqueSlug(Str::slug($slugSource));

        return MenuItem::create([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $slug,
            'price' => $data['price'],
            'description' => $data['description'] ?? null,
            'is_available' => $data['is_available'] ?? true,
            'order' => $data['order'] ?? 0,
        ]);
    }

    public function updateMenuItem(MenuItem $menuItem, array $data): MenuItem
    {
        $updateData = [];

        if (array_key_exists('category_id', $data)) {
            $updateData['category_id'] = $data['category_id'];
        }
        if (array_key_exists('name', $data)) {
            $updateData['name'] = $data['name'];
        }
        if (array_key_exists('price', $data)) {
            $updateData['price'] = $data['price'];
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }
        if (array_key_exists('is_available', $data)) {
            $updateData['is_available'] = $data['is_available'];
        }
        if (array_key_exists('order', $data)) {
            $updateData['order'] = $data['order'];
        }

        if (array_key_exists('slug', $data) || array_key_exists('name', $data)) {
            $slugSource = array_key_exists('slug', $data) && !empty($data['slug'])
                ? $data['slug']
                : ($data['name'] ?? $menuItem->name);

            $updateData['slug'] = $this->makeUniqueSlug(
                Str::slug($slugSource),
                ignoreMenuItemId: $menuItem->id
            );
        }

        $menuItem->update($updateData);

        return $menuItem->fresh();
    }

    public function deleteMenuItem(MenuItem $menuItem): void
    {
        $menuItem->delete();
    }

    public function toggleAvailability(MenuItem $menuItem): MenuItem
    {
        $menuItem->is_available = !$menuItem->is_available;
        $menuItem->save();

        return $menuItem;
    }

    private function makeUniqueSlug(string $slug, ?int $ignoreMenuItemId = null): string
    {
        $base = $slug !== '' ? $slug : 'menu-item';
        $candidate = $base;
        $suffix = 1;

        while (
            MenuItem::withTrashed()
            ->when($ignoreMenuItemId !== null, fn($q) => $q->whereKeyNot($ignoreMenuItemId))
            ->where('slug', $candidate)
            ->exists()
        ) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}
