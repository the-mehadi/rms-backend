<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\StoreTableRequest;
use App\Http\Requests\Tables\UpdateTableRequest;
use App\Http\Requests\Tables\UpdateTableStatusRequest;
use App\Http\Resources\TableResource;
use App\Models\Table;
use App\Services\TableService;
use Illuminate\Http\JsonResponse;

class TableController extends Controller
{
    public function __construct(
        private TableService $tableService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $tables = $this->tableService->getAll();

        return response()->json([
            'success' => true,
            'message' => 'Tables retrieved successfully',
            'data' => TableResource::collection($tables),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTableRequest $request): JsonResponse
    {
        $table = $this->tableService->store($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Table created successfully',
            'data' => new TableResource($table),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Table $table): JsonResponse
    {
        $table = $this->tableService->getById($table->id);

        return response()->json([
            'success' => true,
            'message' => 'Table retrieved successfully',
            'data' => new TableResource($table),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTableRequest $request, Table $table): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Table update is currently disabled.',
            'data' => $request->all(),
        ], 200);
        $table = $this->tableService->update($table, $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Table updated successfully',
            'data' => new TableResource($table),
        ]);
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(UpdateTableStatusRequest $request, Table $table): JsonResponse
    {
        $table = $this->tableService->updateStatus($table, $request->validated()['status']);

        return response()->json([
            'success' => true,
            'message' => 'Table status updated successfully',
            'data' => new TableResource($table),
        ]);
    }
    public function destroy(Table $table): JsonResponse
    {
        $table->delete();

        return response()->json([
            'success' => true,
            'message' => "Table delete successfully."
        ]);
    }
}
