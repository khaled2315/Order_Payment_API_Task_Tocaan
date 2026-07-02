<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentProcessingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
    }

    public function test_user_can_process_payment_for_confirmed_order(): void
    {
        config(['payment.credit_card_success_rate' => 1.0]);

        $order = Order::factory()
            ->confirmed()
            ->has(OrderItem::factory()->count(1), 'items')
            ->create([
                'user_id' => $this->user->id,
                'total_amount' => 100.00,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'credit_card',
                'amount' => 100.00,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'payment' => [
                    'id',
                    'order_id',
                    'payment_method',
                    'status',
                    'amount',
                    'transaction_id',
                    'gateway_response',
                ],
            ]);

        $this->assertEquals('successful', $response->json('payment.status'));
        $this->assertNotNull($response->json('payment.transaction_id'));
    }

    public function test_payment_can_fail(): void
    {
        config(['payment.credit_card_success_rate' => 0.0]);

        $order = Order::factory()
            ->confirmed()
            ->has(OrderItem::factory()->count(1), 'items')
            ->create([
                'user_id' => $this->user->id,
                'total_amount' => 100.00,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'credit_card',
                'amount' => 100.00,
            ]);

        $response->assertStatus(201);
        $this->assertEquals('failed', $response->json('payment.status'));
        $this->assertNull($response->json('payment.transaction_id'));
    }

    public function test_user_cannot_process_payment_for_pending_order(): void
    {
        $order = Order::factory()
            ->pending()
            ->has(OrderItem::factory()->count(1), 'items')
            ->create([
                'user_id' => $this->user->id,
                'total_amount' => 100.00,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'credit_card',
                'amount' => 100.00,
            ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_process_payment_for_cancelled_order(): void
    {
        $order = Order::factory()
            ->cancelled()
            ->has(OrderItem::factory()->count(1), 'items')
            ->create([
                'user_id' => $this->user->id,
                'total_amount' => 100.00,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'credit_card',
                'amount' => 100.00,
            ]);

        $response->assertStatus(422);
    }

    public function test_payment_amount_must_match_order_total(): void
    {
        $order = Order::factory()
            ->confirmed()
            ->has(OrderItem::factory()->count(1), 'items')
            ->create([
                'user_id' => $this->user->id,
                'total_amount' => 100.00,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'credit_card',
                'amount' => 50.00,
            ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_pay_for_another_users_order(): void
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()
            ->confirmed()
            ->create([
                'user_id' => $otherUser->id,
                'total_amount' => 100.00,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'credit_card',
                'amount' => 100.00,
            ]);

        $response->assertStatus(422);
    }

    public function test_paypal_gateway_processes_payment(): void
    {
        config(['payment.paypal_success_rate' => 1.0]);

        $order = Order::factory()
            ->confirmed()
            ->create([
                'user_id' => $this->user->id,
                'total_amount' => 100.00,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'paypal',
                'amount' => 100.00,
            ]);

        $response->assertStatus(201);
        $this->assertEquals('successful', $response->json('payment.status'));
        $this->assertStringStartsWith('PAYPAL-', $response->json('payment.transaction_id'));
    }

    public function test_stripe_gateway_processes_payment(): void
    {
        config(['payment.stripe_success_rate' => 1.0]);

        $order = Order::factory()
            ->confirmed()
            ->create([
                'user_id' => $this->user->id,
                'total_amount' => 100.00,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'stripe',
                'amount' => 100.00,
            ]);

        $response->assertStatus(201);
        $this->assertEquals('successful', $response->json('payment.status'));
        $this->assertStringStartsWith('ch_', $response->json('payment.transaction_id'));
    }

    public function test_user_can_list_their_payments(): void
    {
        $order = Order::factory()
            ->confirmed()
            ->has(Payment::factory()->count(3), 'payments')
            ->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_id', 'payment_method', 'status', 'amount'],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_user_can_filter_payments_by_order(): void
    {
        $order1 = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        $order2 = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);

        Payment::factory()->count(2)->create(['order_id' => $order1->id]);
        Payment::factory()->count(3)->create(['order_id' => $order2->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/payments?order_id='.$order1->id);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_user_can_view_payment_details(): void
    {
        $order = Order::factory()
            ->confirmed()
            ->create(['user_id' => $this->user->id]);

        $payment = Payment::factory()
            ->successful()
            ->create(['order_id' => $order->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/payments/'.$payment->id);

        $response->assertStatus(200)
            ->assertJson([
                'payment' => [
                    'id' => $payment->id,
                    'order_id' => $order->id,
                ],
            ]);
    }

    public function test_user_cannot_view_another_users_payment(): void
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->confirmed()->create(['user_id' => $otherUser->id]);
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/payments/'.$payment->id);

        $response->assertStatus(403);
    }
}
