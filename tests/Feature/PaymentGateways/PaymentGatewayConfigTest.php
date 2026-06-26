<?php

namespace Tests\Feature\PaymentGateways;

use App\Models\PaymentGatewayConfig;
use App\Payments\Gateways\CreditCardGateway;
use Tests\ApiTestCase;

class PaymentGatewayConfigTest extends ApiTestCase
{
    public function test_can_list_payment_gateways(): void
    {
        $this->authenticate();

        PaymentGatewayConfig::factory()->create(['name' => 'credit_card']);

        $response = $this->getJson('/api/payment-gateways');

        $response->assertOk()
            ->assertJsonStructure(['body' => ['payment_gateways']]);
    }

    public function test_can_create_payment_gateway(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/payment-gateways', [
            'name' => 'stripe',
            'driver_class' => CreditCardGateway::class,
            'is_active' => true,
            'config' => [
                'api_key' => 'sk_test_123456789',
                'secret' => 'whsec_test',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Data created successfully.');

        $this->assertDatabaseHas('payment_gateways', ['name' => 'stripe']);
    }

    public function test_can_update_payment_gateway_config(): void
    {
        $this->authenticate();

        $gateway = PaymentGatewayConfig::factory()->create([
            'name' => 'credit_card',
            'config' => ['api_key' => 'old_key'],
        ]);

        $response = $this->putJson("/api/payment-gateways/{$gateway->id}", [
            'config' => ['api_key' => 'new_key_from_db'],
        ]);

        $response->assertOk();

        $this->assertSame(
            'new_key_from_db',
            $gateway->fresh()->config['api_key'],
        );
    }

    public function test_can_deactivate_payment_gateway(): void
    {
        $this->authenticate();

        $gateway = PaymentGatewayConfig::factory()->create(['is_active' => true]);

        $response = $this->putJson("/api/payment-gateways/{$gateway->id}", [
            'is_active' => false,
        ]);

        $response->assertOk();
        $this->assertFalse($gateway->fresh()->is_active);
    }

    public function test_can_delete_payment_gateway(): void
    {
        $this->authenticate();

        $gateway = PaymentGatewayConfig::factory()->create();

        $response = $this->deleteJson("/api/payment-gateways/{$gateway->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('payment_gateways', ['id' => $gateway->id]);
    }

    public function test_sensitive_config_values_are_masked_in_response(): void
    {
        $this->authenticate();

        $gateway = PaymentGatewayConfig::factory()->create([
            'config' => ['api_key' => 'sk_live_secret_key_12345'],
        ]);

        $response = $this->getJson("/api/payment-gateways/{$gateway->id}");

        $response->assertOk()
            ->assertJsonPath('body.payment_gateway.config.api_key', fn ($value) => str_contains($value, 'sk_l') && str_contains($value, '*'));
    }

    public function test_create_rejects_invalid_driver_class(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/payment-gateways', [
            'name' => 'invalid',
            'driver_class' => 'App\\NonExistent\\Gateway',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['driver_class']);
    }
}
