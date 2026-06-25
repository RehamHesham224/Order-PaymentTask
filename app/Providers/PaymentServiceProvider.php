<?php

namespace App\Providers;

use App\Payments\PaymentGatewayManager;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, function () {
            $manager = new PaymentGatewayManager;

            foreach (config('payment.gateways', []) as $gatewayConfig) {
                $driverClass = $gatewayConfig['driver'] ?? null;

                if ($driverClass && class_exists($driverClass)) {
                    $manager->register(app($driverClass));
                }
            }

            return $manager;
        });
    }
}
