<?php

namespace App\Http\Controllers\Api;

use App\Filters\Orders\OrderStatusFilter;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\Filters\GeneralSearch;
use Illuminate\Support\Facades\Auth;

class OrderController extends ApiController
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function index()
    {
        $orders = Order::with('items')
            ->filter([
                GeneralSearch::class,
                OrderStatusFilter::class,
            ])
            ->latest()
            ->paginate(request('per_page', $this->perPage));

        return self::apiBody([
            'orders' => OrderResource::paginate($orders),
        ])->apiResponse();
    }

    public function show(Order $order)
    {
        $order->load(['items', 'payments']);

        return self::apiBody([
            'order' => OrderResource::make($order),
        ])->apiResponse();
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->create(
            $request->validated(),
            Auth::guard('api')->id(),
        );

        return self::apiBody([
            'order' => OrderResource::make($order),
        ])
            ->apiMessage(__('app.messages.data_created'))
            ->apiCode(201)
            ->apiResponse();
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $order = $this->orderService->update(
            $order,
            $request->validated()
        );

        return self::apiBody([
            'order' => OrderResource::make($order),
        ])
            ->apiMessage(__('app.messages.data_updated'))
            ->apiResponse();
    }

    public function destroy(Order $order)
    {
        $this->orderService->delete($order);

        return self::apiMessage(__('app.messages.data_deleted'))->apiResponse();
    }
}
