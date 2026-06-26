<?php

namespace App\Providers;

use App\Payments\PaymentGatewayConfigRepository;
use App\Payments\PaymentGatewayFactory;
use App\Payments\PaymentGatewayManager;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayConfigRepository::class);
        $this->app->singleton(PaymentGatewayFactory::class);

        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            $manager = new PaymentGatewayManager;
            $repository = $app->make(PaymentGatewayConfigRepository::class);
            $factory = $app->make(PaymentGatewayFactory::class);

            foreach ($repository->all() as $name => $gatewayConfig) {
                $manager->register($factory->make($name, $gatewayConfig));
            }

            return $manager;
        });
    }
}
