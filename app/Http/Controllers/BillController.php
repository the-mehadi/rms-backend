<?php

namespace App\Http\Controllers;

use App\Http\Requests\Billing\StoreBillRequest;
use App\Http\Resources\BillResource;
use App\Http\Resources\ReceiptResource;
use App\Services\Billing\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class BillController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $this->billingService->getAllBills($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Bills retrieved successfully.',
            'data' => [
                'items' => BillResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ], 200);
    }

    public function show(int $id): JsonResponse
    {
        $bill = $this->billingService->getBillById($id);

        return response()->json([
            'success' => true,
            'message' => 'Bill retrieved successfully.',
            'data' => BillResource::make($bill),
        ], 200);
    }

    public function store(StoreBillRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $bill = $this->billingService->createBill(
                (int) $data['order_id'],
                (int) $request->user()->id,
                (float) ($data['discount'] ?? 0),
                (float) ($data['vat'] ?? 0),
            );

            return response()->json([
                'success' => true,
                'message' => 'Bill created successfully.',
                'data' => BillResource::make($bill),
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function receipt(int $id): JsonResponse
    {
        $bill = $this->billingService->getReceipt($id);

        return response()->json([
            'success' => true,
            'message' => 'Receipt retrieved successfully.',
            'data' => ReceiptResource::make($bill),
        ], 200);
    }
}

