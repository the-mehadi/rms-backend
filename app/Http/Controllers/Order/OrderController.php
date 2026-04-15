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
     * Get active order for a table.
     */
    public function showByTable(int $tableId): JsonResponse
    {
        $order = $this->orderService->getOrderByTable($tableId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'No active order found for this table.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully.',
            'data' => OrderResource::make($order),
        ], 200);
    }

    /**
     * Create a new order.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => OrderResource::make($order->load(['table', 'user', 'items.menuItem'])),
        ], 201);
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
        $orders = Order::with(['items.menuItem','table'])
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->whereDate('created_at', today())
            ->orderByRaw("FIELD(priority, 'rush', 'high', 'normal')")
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($orders);
    }
}
