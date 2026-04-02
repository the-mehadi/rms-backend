<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\ListCategoriesRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService)
    {
        //
    }

    // GET /api/categories (public)
    public function index(ListCategoriesRequest $request): JsonResponse
    {
        $perPage = $request->validated('per_page') ?? 10;

        $paginator = $this->categoryService->getPaginatedCategories($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully.',
            'data' => [
                'items' => CategoryResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ], 200);
    }

    // POST /api/categories (admin)
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => CategoryResource::make($category),
        ], 201);
    }

    // PATCH /api/categories/{id} (admin)
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $updated = $this->categoryService->updateCategory($category, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => CategoryResource::make($updated),
        ], 200);
    }

    // DELETE /api/categories/{id} (admin)
    public function destroy(Category $category): JsonResponse
    {
        $this->categoryService->deleteCategory($category);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
            'data' => null,
        ], 200);
    }
}

