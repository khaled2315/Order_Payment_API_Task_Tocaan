<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
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

    public function test_user_can_create_order(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/orders', [
                'items' => [
                    [
                        'product_name' => 'Product A',
                        'quantity' => 2,
                        'unit_price' => 29.99,
                    ],
                    [
                        'product_name' => 'Product B',
                        'quantity' => 1,
                        'unit_price' => 49.99,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'order' => [
                    'id',
                    'user_id',
                    'status',
                    'total_amount',
                    'items',
                ],
            ]);

        $this->assertEquals('pending', $response->json('order.status'));
        $this->assertEquals(109.97, $response->json('order.total_amount'));
        $this->assertCount(2, $response->json('order.items'));
    }

    public function test_order_creation_validates_items(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/orders', [
                'items' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_user_can_list_their_orders(): void
    {
        Order::factory()
            ->has(OrderItem::factory()->count(2), 'items')
            ->count(3)
            ->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'user_id', 'status', 'total_amount', 'items'],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_user_can_filter_orders_by_status(): void
    {
        Order::factory()->pending()->create(['user_id' => $this->user->id]);
        Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        Order::factory()->confirmed()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/orders?status=confirmed');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_user_can_view_single_order(): void
    {
        $order = Order::factory()
            ->has(OrderItem::factory()->count(2), 'items')
            ->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/orders/'.$order->id);

        $response->assertStatus(200)
            ->assertJson([
                'order' => [
                    'id' => $order->id,
                    'user_id' => $this->user->id,
                ],
            ]);
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/orders/'.$order->id);

        $response->assertStatus(403);
    }

    public function test_user_can_update_pending_order(): void
    {
        $order = Order::factory()
            ->pending()
            ->has(OrderItem::factory()->count(1), 'items')
            ->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/orders/'.$order->id, [
                'items' => [
                    [
                        'product_name' => 'Updated Product',
                        'quantity' => 3,
                        'unit_price' => 19.99,
                    ],
                ],
            ]);

        $response->assertStatus(200);
        $this->assertEquals(59.97, $response->json('order.total_amount'));
    }

    public function test_user_cannot_update_confirmed_order(): void
    {
        $order = Order::factory()
            ->confirmed()
            ->has(OrderItem::factory()->count(1), 'items')
            ->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/orders/'.$order->id, [
                'items' => [
                    [
                        'product_name' => 'Updated Product',
                        'quantity' => 1,
                        'unit_price' => 10.00,
                    ],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_user_can_confirm_pending_order(): void
    {
        $order = Order::factory()
            ->pending()
            ->has(OrderItem::factory()->count(1), 'items')
            ->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/orders/'.$order->id.'/confirm');

        $response->assertStatus(200)
            ->assertJson([
                'order' => [
                    'id' => $order->id,
                    'status' => 'confirmed',
                ],
            ]);
    }

    public function test_user_cannot_confirm_already_confirmed_order(): void
    {
        $order = Order::factory()
            ->confirmed()
            ->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/orders/'.$order->id.'/confirm');

        $response->assertStatus(422);
    }

    public function test_user_can_delete_order_without_payments(): void
    {
        $order = Order::factory()
            ->has(OrderItem::factory()->count(1), 'items')
            ->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/orders/'.$order->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_user_cannot_delete_order_with_payments(): void
    {
        $order = Order::factory()
            ->has(OrderItem::factory()->count(1), 'items')
            ->has(Payment::factory()->count(1), 'payments')
            ->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/orders/'.$order->id);

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_user_cannot_delete_another_users_order(): void
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/orders/'.$order->id);

        $response->assertStatus(403);
    }
}
