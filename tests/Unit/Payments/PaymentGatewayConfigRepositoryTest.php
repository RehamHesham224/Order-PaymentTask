<?php

namespace Tests\Unit\Payments;

use App\Models\PaymentGatewayConfig;
use App\Payments\Gateways\CreditCardGateway;
use App\Payments\PaymentGatewayConfigRepository;
use App\Payments\PaymentGatewayFactory;
use App\Payments\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayConfigRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_config_overrides_env_config(): void
    {
        config([
            'payment.gateways.credit_card' => [
                'driver' => CreditCardGateway::class,
                'api_key' => 'env_api_key',
            ],
        ]);

        PaymentGatewayConfig::factory()->create([
            'name' => 'credit_card',
            'config' => ['api_key' => 'database_api_key'],
        ]);

        $gateways = app(PaymentGatewayConfigRepository::class)->all();

        $this->assertSame('database_api_key', $gateways['credit_card']['api_key']);
        $this->assertSame(CreditCardGateway::class, $gateways['credit_card']['driver']);
    }

    public function test_inactive_database_gateway_is_excluded(): void
    {
        config([
            'payment.gateways.credit_card' => [
                'driver' => CreditCardGateway::class,
                'api_key' => 'env_api_key',
            ],
        ]);

        PaymentGatewayConfig::factory()->inactive()->create([
            'name' => 'credit_card',
        ]);

        $gateways = app(PaymentGatewayConfigRepository::class)->all();

        $this->assertArrayNotHasKey('credit_card', $gateways);
    }

    public function test_manager_registers_gateway_from_database_config(): void
    {
        PaymentGatewayConfig::factory()->create([
            'name' => 'credit_card',
            'config' => ['api_key' => 'db_key_for_manager'],
        ]);

        $manager = app(PaymentGatewayManager::class);
        $gateway = $manager->get('credit_card');

        $this->assertInstanceOf(CreditCardGateway::class, $gateway);
    }

    public function test_factory_creates_gateway_with_injected_config(): void
    {
        $gateway = app(PaymentGatewayFactory::class)->make('credit_card', [
            'driver' => CreditCardGateway::class,
            'api_key' => 'injected_key',
        ]);

        $this->assertInstanceOf(CreditCardGateway::class, $gateway);
    }
}
