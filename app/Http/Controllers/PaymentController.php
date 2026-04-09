<?php

namespace App\Http\Controllers;

use App\Http\Requests\Billing\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\Billing\PaymentService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $payment = $this->paymentService->makePayment($data);

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'data' => PaymentResource::make($payment),
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

