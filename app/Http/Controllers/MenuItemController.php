<?php

namespace App\Http\Controllers;

use App\Http\Requests\MenuItem\StoreMenuItemRequest;
use App\Http\Requests\MenuItem\UpdateMenuItemRequest;
use App\Http\Requests\MenuItem\ToggleAvailabilityRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Services\MenuItem\MenuItemService;
use Illuminate\Http\JsonResponse;

class MenuItemController extends Controller
{
    public function __construct(private readonly MenuItemService $menuItemService)
    {
        //
    }

    // GET /api/menu-items (public)
    public function index(): JsonResponse
    {
        $perPage = (int) request('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $categoryId = request('category_id') ? (int) request('category_id') : null;
        $isAvailable = null;
        if (request()->has('is_available')) {
            $value = request('is_available');
            if ($value === 'true' || $value === '1' || $value === 1 || $value === true) {
                $isAvailable = true;
            } elseif ($value === 'false' || $value === '0' || $value === 0 || $value === false) {
                $isAvailable = false;
            }
        }

        $paginator = $this->menuItemService->getPaginatedMenuItems($perPage, $categoryId, $isAvailable);

        return response()->json([
            'success' => true,
            'message' => 'Menu items retrieved successfully.',
            'data' => [
                'items' => MenuItemResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ], 200);
    }

    // GET /api/menu-items/{id} (public)
    public function show(int $id): JsonResponse
    {
        $menuItem = $this->menuItemService->getMenuItemById($id);

        return response()->json([
            'success' => true,
            'message' => 'Menu item retrieved successfully.',
            'data' => MenuItemResource::make($menuItem),
        ], 200);
    }

    // POST /api/menu-items (admin)
    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        $menuItem = $this->menuItemService->createMenuItem($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Menu item created successfully.',
            'data' => MenuItemResource::make($menuItem),
        ], 201);
    }

    // PATCH /api/menu-items/{id} (admin)
    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        $updated = $this->menuItemService->updateMenuItem($menuItem, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Menu item updated successfully.',
            'data' => MenuItemResource::make($updated),
        ], 200);
    }

    // DELETE /api/menu-items/{id} (admin)
    public function destroy(MenuItem $menuItem): JsonResponse
    {
        $this->menuItemService->deleteMenuItem($menuItem);

        return response()->json([
            'success' => true,
            'message' => 'Menu item deleted successfully.',
            'data' => null,
        ], 200);
    }

    // PATCH /api/menu-items/{id}/availability (admin)
    public function toggleAvailability(ToggleAvailabilityRequest $request, MenuItem $menuItem): JsonResponse
    {
        $updated = $this->menuItemService->toggleAvailability($menuItem);

        return response()->json([
            'success' => true,
            'message' => 'Menu item availability toggled successfully.',
            'data' => MenuItemResource::make($updated),
        ], 200);
    }
}
