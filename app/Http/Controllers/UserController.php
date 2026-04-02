<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ListUsersRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct( private readonly UserService $userService)
    {
        //
    }

    // GET /api/users
    public function index(ListUsersRequest $request): JsonResponse
    {
        $perPage = $request->validated('per_page') ?? 10;

        $paginator = $this->userService->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data' => [
                'items' => UserResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ], 200);
    }

    // POST /api/users
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => UserResource::make($user),
        ], 201);
    }

    // PUT /api/users/{id}
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->updateUser($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => UserResource::make($updatedUser),
        ], 200);
    }

    // DELETE /api/users/{id}
    public function destroy(User $user): JsonResponse
    {
        $this->userService->deleteUser($user);

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
            'data' => null,
        ], 200);
    }
}

