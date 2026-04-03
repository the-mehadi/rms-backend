<?php

namespace App\Http\Controllers;

use App\Http\Requests\MenuItem\StoreMenuItemImageRequest;
use App\Http\Resources\MenuItemImageResource;
use App\Models\MenuItem;
use App\Models\MenuItemImage;
use App\Services\MenuItem\MenuItemImageService;
use Illuminate\Http\JsonResponse;

class MenuItemImageController extends Controller
{
    public function __construct(private readonly MenuItemImageService $menuItemImageService)
    {
        //
    }

    // GET /api/menu-items/{id}/images (public)
    public function index(MenuItem $menuItem): JsonResponse
    {
        $images = $this->menuItemImageService->getImagesByMenuItem($menuItem);

        return response()->json([
            'success' => true,
            'message' => 'Menu item images retrieved successfully.',
            'data' => MenuItemImageResource::collection($images),
        ], 200);
    }

    // POST /api/menu-items/{id}/images (admin)
    public function store(StoreMenuItemImageRequest $request, MenuItem $menuItem): JsonResponse
    {
        $validated = $request->validated();

        $image = $this->menuItemImageService->uploadImage(
            $menuItem,
            $request->file('image'),
            $validated['alt_text'] ?? null,
            $validated['order'] ?? 0
        );

        return response()->json([
            'success' => true,
            'message' => 'Menu item image uploaded successfully.',
            'data' => MenuItemImageResource::make($image),
        ], 201);
    }

    // DELETE /api/menu-items/{id}/images/{image_id} (admin)
    public function destroy(MenuItem $menuItem, MenuItemImage $image): JsonResponse
    {
        if ($image->menu_item_id !== $menuItem->id) {
            return response()->json([
                'success' => false,
                'message' => 'Image does not belong to this menu item.',
                'data' => null,
            ], 404);
        }

        $this->menuItemImageService->deleteImage($image);

        return response()->json([
            'success' => true,
            'message' => 'Menu item image deleted successfully.',
            'data' => null,
        ], 200);
    }
}
