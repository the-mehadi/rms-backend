<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\FloorViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class FloorViewController extends Controller
{
    public function __construct(
        private readonly FloorViewService $floorViewService
    ) {}

    /**
     * Get floor view data for all tables.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $data = $this->floorViewService->getFloorViewData();

            return response()->json([
                'success' => true,
                'message' => 'Floor view data retrieved successfully.',
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            Log::error('Floor view data retrieval failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'Failed to retrieve floor view data.';

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 500);
        }
    }
}
