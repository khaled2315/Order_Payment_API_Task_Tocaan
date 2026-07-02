<?php

namespace Tests\Unit\PaymentGateways;

use App\DTOs\PaymentResult;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentGateways\PayPalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayPalGatewayTest extends TestCase
{
    use RefreshDatabase;

    private PayPalGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new PayPalGateway;
    }

    public function test_gateway_name(): void
    {
        $this->assertEquals('PayPal', $this->gateway->getName());
    }

    public function test_validate_returns_true_for_valid_amount(): void
    {
        $this->assertTrue($this->gateway->validate(['amount' => 100.00]));
    }

    public function test_validate_returns_false_for_zero_amount(): void
    {
        $this->assertFalse($this->gateway->validate(['amount' => 0]));
    }

    public function test_process_returns_payment_result(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $result = $this->gateway->process($payment);

        $this->assertInstanceOf(PaymentResult::class, $result);
        $this->assertIsBool($result->success);
        $this->assertIsString($result->message);
        $this->assertIsArray($result->gatewayResponse);
    }

    public function test_successful_payment_has_transaction_id(): void
    {
        config(['payment.paypal_success_rate' => 1.0]);

        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $result = $this->gateway->process($payment);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->transactionId);
        $this->assertStringStartsWith('PAYPAL-', $result->transactionId);
    }

    public function test_failed_payment_has_no_transaction_id(): void
    {
        config(['payment.paypal_success_rate' => 0.0]);

        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $result = $this->gateway->process($payment);

        $this->assertFalse($result->success);
        $this->assertNull($result->transactionId);
        $this->assertNotEmpty($result->message);
    }
}
