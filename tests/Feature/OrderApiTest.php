<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorStatus;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
    \Spatie\Permission\Models\Role::create(['name' => 'vendor', 'guard_name' => 'sanctum']);
    
    // Create a vendor
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $vendorUser = User::factory()->create();
    $vendorUser->assignRole('vendor');
    
    $this->vendor = Vendor::create([
        'user_id' => $vendorUser->id,
        'name' => 'Test Vendor',
        'description' => 'Test Vendor Description',
        'balance' => 0,
        'admin_created_by' => $admin->id,
    ]);
    
    // Create products for the vendor
    $this->product1 = Product::create([
        'vendor_id' => $this->vendor->id,
        'name' => 'Test Product 1',
        'description' => 'Test Product 1 Description',
        'price' => 100,
        'stock' => 10,
    ]);
    
    $this->product2 = Product::create([
        'vendor_id' => $this->vendor->id,
        'name' => 'Test Product 2',
        'description' => 'Test Product 2 Description',
        'price' => 200,
        'stock' => 20,
    ]);
    
    // Create a user
    $this->user = User::factory()->create();
    
    // Create an order for the user
    $this->order = Order::create([
        'user_id' => $this->user->id,
        'total_price' => 800,
        'status' => 'pending',
    ]);
    
    // Create order items
    OrderItem::create([
        'order_id' => $this->order->id,
        'product_id' => $this->product1->id,
        'vendor_id' => $this->vendor->id,
        'quantity' => 2,
        'price' => 100,
    ]);
    
    OrderItem::create([
        'order_id' => $this->order->id,
        'product_id' => $this->product2->id,
        'vendor_id' => $this->vendor->id,
        'quantity' => 3,
        'price' => 200,
    ]);
    
    // Create vendor status
    OrderVendorStatus::create([
        'order_id' => $this->order->id,
        'vendor_id' => $this->vendor->id,
        'status' => 'pending',
    ]);
    
    // Create another order for testing list
    $this->order2 = Order::create([
        'user_id' => $this->user->id,
        'total_price' => 300,
        'status' => 'pending',
    ]);
    
    OrderItem::create([
        'order_id' => $this->order2->id,
        'product_id' => $this->product1->id,
        'vendor_id' => $this->vendor->id,
        'quantity' => 3,
        'price' => 100,
    ]);
    
    OrderVendorStatus::create([
        'order_id' => $this->order2->id,
        'vendor_id' => $this->vendor->id,
        'status' => 'pending',
    ]);
});

test('guest cannot access orders', function () {
    $response = $this->getJson('/api/orders');
    
    $response->assertStatus(401);
});

test('authenticated user can view their orders', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/orders');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'total_price',
                    'status',
                    'items',
                    'vendor_statuses',
                ]
            ]
        ]);
    
    // Should return 2 orders
    expect(count($response->json('data')))->toBe(2);
});

test('authenticated user can view a specific order', function () {
    $response = $this->actingAs($this->user)
        ->getJson("/api/orders/{$this->order->id}");
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'order' => [
                    'id',
                    'user_id',
                    'total_price',
                    'status',
                    'items',
                    'vendor_statuses',
                ],
                'items_by_vendor',
            ]
        ]);
    
    expect($response->json('data.order.id'))->toBe($this->order->id);
    expect((float)$response->json('data.order.total_price'))->toBe(800.00);
    expect(count($response->json('data.order.items')))->toBe(2);
});

test('user cannot view another users order', function () {
    $anotherUser = User::factory()->create();
    
    $response = $this->actingAs($anotherUser)
        ->getJson("/api/orders/{$this->order->id}");
    
    $response->assertStatus(404);
});

test('user can cancel their order', function () {
    // Set initial product stock
    $initialStock1 = $this->product1->stock;
    $initialStock2 = $this->product2->stock;
    
    $response = $this->actingAs($this->user)
        ->postJson("/api/orders/{$this->order->id}/cancel");
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data',
        ]);
    
    // Check that the order status was updated
    $this->order->refresh();
    expect($this->order->status)->toBe('cancelled');
    
    // Check that all vendor statuses were updated
    foreach ($this->order->vendorStatuses as $vendorStatus) {
        expect($vendorStatus->status)->toBe('cancelled');
    }
    
    // Check that product stock was restored
    $this->product1->refresh();
    $this->product2->refresh();
    expect($this->product1->stock)->toBe($initialStock1 + 2);
    expect($this->product2->stock)->toBe($initialStock2 + 3);
});

test('completed order cannot be cancelled', function () {
    // Set order to completed
    $this->order->status = 'completed';
    $this->order->save();
    
    $response = $this->actingAs($this->user)
        ->postJson("/api/orders/{$this->order->id}/cancel");
    
    $response->assertStatus(400)
        ->assertJsonFragment([
            'message' => 'Completed orders cannot be cancelled'
        ]);
}); 