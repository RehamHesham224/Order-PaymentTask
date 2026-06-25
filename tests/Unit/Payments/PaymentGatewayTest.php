<?php

namespace Tests\Unit\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Payments\Gateways\CreditCardGateway;
use App\Payments\Gateways\PayPalGateway;
use App\Payments\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_card_gateway_supports_credit_card_method(): void
    {
        $gateway = new CreditCardGateway;

        $this->assertTrue($gateway->supports('credit_card'));
        $this->assertFalse($gateway->supports('paypal'));
    }

    public function test_credit_card_gateway_simulates_successful_payment(): void
    {
        config(['payment.gateways.credit_card.api_key' => 'test_api_key']);

        $gateway = new CreditCardGateway;
        $order = Order::factory()->confirmed()->create(['total' => 99.99]);

        $result = $gateway->process($order, ['card_number' => '4111111111111111']);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(PaymentStatus::Successful, $result->status);
        $this->assertStringStartsWith('cc_', $result->transactionReference);
    }

    public function test_credit_card_gateway_simulates_declined_payment(): void
    {
        $gateway = new CreditCardGateway;
        $order = Order::factory()->confirmed()->create();

        $result = $gateway->process($order, ['card_number' => '4111111111110000']);

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(PaymentStatus::Failed, $result->status);
        $this->assertNotNull($result->failureReason);
    }

    public function test_paypal_gateway_fails_when_email_contains_fail(): void
    {
        config(['payment.gateways.paypal.client_id' => 'test_client']);

        $gateway = new PayPalGateway;
        $order = Order::factory()->confirmed()->create();

        $result = $gateway->process($order, ['paypal_email' => 'fail@example.com']);

        $this->assertSame(PaymentStatus::Failed, $result->status);
    }

    public function test_gateway_manager_resolves_gateway_by_payment_method(): void
    {
        $manager = new PaymentGatewayManager;
        $manager->register(new CreditCardGateway);
        $manager->register(new PayPalGateway);

        $this->assertInstanceOf(CreditCardGateway::class, $manager->resolve('credit_card'));
        $this->assertInstanceOf(PayPalGateway::class, $manager->resolve('paypal'));
    }

    public function test_gateway_manager_throws_for_unsupported_method(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $manager = new PaymentGatewayManager;
        $manager->register(new CreditCardGateway);
        $manager->resolve('bank_transfer');
    }
}
