<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\PaymentGateways\StorePaymentGatewayRequest;
use App\Http\Requests\PaymentGateways\UpdatePaymentGatewayRequest;
use App\Http\Resources\PaymentGatewayConfigResource;
use App\Models\PaymentGatewayConfig;
use App\Services\PaymentGatewayConfigService;

class PaymentGatewayController extends ApiController
{
    public function __construct(
        private readonly PaymentGatewayConfigService $gatewayConfigService,
    ) {}

    public function index()
    {
        $gateways = $this->gatewayConfigService->list();

        return self::apiBody([
            'payment_gateways' => PaymentGatewayConfigResource::collection($gateways),
        ])->apiResponse();
    }

    public function show(PaymentGatewayConfig $paymentGateway)
    {
        return self::apiBody([
            'payment_gateway' => PaymentGatewayConfigResource::make($paymentGateway),
        ])->apiResponse();
    }

    public function store(StorePaymentGatewayRequest $request)
    {
        $this->gatewayConfigService->create($request->validated());

        return self::apiMessage(__('app.messages.data_created'))
            ->apiCode(201)
            ->apiResponse();
    }

    public function update(UpdatePaymentGatewayRequest $request, PaymentGatewayConfig $paymentGateway)
    {
        $this->gatewayConfigService->update($paymentGateway, $request->validated());

        return self::apiMessage(__('app.messages.data_updated'))->apiResponse();
    }

    public function destroy(PaymentGatewayConfig $paymentGateway)
    {
        $this->gatewayConfigService->delete($paymentGateway);

        return self::apiMessage(__('app.messages.data_deleted'))->apiResponse();
    }
}
