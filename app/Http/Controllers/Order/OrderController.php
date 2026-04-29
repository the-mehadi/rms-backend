<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AddOrderItemRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    /**
     * Get all orders (latest first).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $paginator = $this->orderService->getAllOrders($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully.',
            'data' => [
                'items' => OrderResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ], 200);
    }

    /**
     * Get a single order.
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully.',
            'data' => OrderResource::make($order),
        ], 200);
    }

    /**
     * Get all unpaid orders for a table.
     * Multiple orders can exist until payment is made.
     */
    public function showByTable(int $tableId): JsonResponse
    {
        $orders = $this->orderService->getUnpaidOrdersByTable($tableId);
        if (empty($orders)) {
            return response()->json([
                'success' => false,
                'message' => 'No unpaid orders found for this table.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Unpaid orders retrieved successfully.',
            'data' => OrderResource::collection($orders)->resolve(),
        ], 200);
    }

    /**
     * Create a new order.
     */
    // public function store(StoreOrderRequest $request): JsonResponse
    // {
    //     $order = $this->orderService->createOrder($request->validated(), $request->user());

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Order created successfully.',
    //         'data' => OrderResource::make($order->load(['table', 'user', 'items.menuItem'])),
    //     ], 201);
    // }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder(
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'data' => OrderResource::make($order->load(['table', 'user', 'items.menuItem'])),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            // Business logic errors
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Model not found
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            // Database errors
            Log::error('Database error in order creation', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Database error occurred.',
                'error' => config('app.debug') ? [
                    'code' => $e->getCode(),
                    'previous' => $e->getPrevious()?->getMessage(),
                ] : null
            ], 500);
        } catch (\Throwable $e) {
            // General errors (Laravel 13 uses Throwable)
            Log::error('Order creation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Server error occurred.',
                'error' => config('app.debug') ? [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect(explode("\n", $e->getTraceAsString()))->take(5)->toArray()
                ] : null
            ], 500);
        }
    }

    /**
     * Add item to order.
     */
    public function addItem(int $orderId, AddOrderItemRequest $request): JsonResponse
    {
        $item = $this->orderService->addItem($orderId, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Item added to order successfully.',
            'data' => OrderResource::make($item->order->load(['table', 'user', 'items.menuItem'])),
        ], 200);
    }

    /**
     * Update order status.
     */
    public function updateStatus(int $orderId, UpdateOrderStatusRequest $request): JsonResponse
    {
        $order = $this->orderService->updateStatus($orderId, $request->validated()['status']);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => OrderResource::make($order),
        ], 200);
    }

    /**
     * Cancel order.
     */
    public function destroy(int $orderId): JsonResponse
    {
        $order = $this->orderService->cancelOrder($orderId);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.',
            'data' => OrderResource::make($order),
        ], 200);
    }
    public function kitchenView()
    {
        $orders = Order::with(['items.menuItem', 'table'])
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->whereDate('created_at', today())
            ->orderByRaw("FIELD(priority, 'rush', 'high', 'normal')")
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($orders);
    }
}
