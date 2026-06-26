<?php

namespace App\Http\Controllers\Api;

use App\Filters\Payments\OrderIdFilter;
use App\Filters\Payments\PaymentMethodFilter;
use App\Filters\Payments\PaymentStatusFilter;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Payments\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Support\Filters\GeneralSearch;

class PaymentController extends ApiController
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function index()
    {
        $payments = Payment::with('order')
            ->filter([
                GeneralSearch::class,
                OrderIdFilter::class,
                PaymentStatusFilter::class,
                PaymentMethodFilter::class,
            ])
            ->latest()
            ->paginate(request('per_page', $this->perPage));

        return self::apiBody([
            'payments' => PaymentResource::paginate($payments),
        ])->apiResponse();
    }

    public function show(Payment $payment)
    {
        $payment->load('order');

        return self::apiBody([
            'payment' => PaymentResource::make($payment),
        ])->apiResponse();
    }

    public function store(ProcessPaymentRequest $request, Order $order)
    {
        $payment = $$this->paymentService->process($order, $request->validated());

        return self::apiBody(['payment' => PaymentResource::make($payment)])
            ->apiMessage(__('app.messages.data_created'))
            ->apiCode(201)
            ->apiResponse();
    }
}
