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
    // Create roles if they don't exist
    if (!\Spatie\Permission\Models\Role::where('name', 'admin')->where('guard_name', 'sanctum')->exists()) {
        \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
    }
    
    if (!\Spatie\Permission\Models\Role::where('name', 'vendor')->where('guard_name', 'sanctum')->exists()) {
        \Spatie\Permission\Models\Role::create(['name' => 'vendor', 'guard_name' => 'sanctum']);
    }
    
    // Create admin user
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    
    // Create vendor user
    $this->vendorUser = User::factory()->create();
    $this->vendorUser->assignRole('vendor');
    
    // Create vendor
    $this->vendor = Vendor::create([
        'user_id' => $this->vendorUser->id,
        'name' => 'Test Vendor',
        'description' => 'Test Vendor Description',
        'balance' => 0,
        'admin_created_by' => $this->admin->id,
    ]);
    
    // Create another vendor
    $anotherVendorUser = User::factory()->create();
    $anotherVendorUser->assignRole('vendor');
    
    $this->anotherVendor = Vendor::create([
        'user_id' => $anotherVendorUser->id,
        'name' => 'Another Vendor',
        'description' => 'Another Vendor Description',
        'balance' => 0,
        'admin_created_by' => $this->admin->id,
    ]);
    
    // Create products for the vendors
    $this->product1 = Product::create([
        'vendor_id' => $this->vendor->id,
        'name' => 'Test Product 1',
        'description' => 'Test Product 1 Description',
        'price' => 100,
        'stock' => 10,
    ]);
    
    $this->product2 = Product::create([
        'vendor_id' => $this->anotherVendor->id,
        'name' => 'Test Product 2',
        'description' => 'Test Product 2 Description',
        'price' => 200,
        'stock' => 20,
    ]);
    
    // Create a customer
    $this->customer = User::factory()->create();
    
    // Create an order with items from both vendors
    $this->order = Order::create([
        'user_id' => $this->customer->id,
        'total_price' => 500,
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
        'vendor_id' => $this->anotherVendor->id,
        'quantity' => 1,
        'price' => 200,
    ]);
    
    // Create vendor statuses
    OrderVendorStatus::create([
        'order_id' => $this->order->id,
        'vendor_id' => $this->vendor->id,
        'status' => 'pending',
    ]);
    
    OrderVendorStatus::create([
        'order_id' => $this->order->id,
        'vendor_id' => $this->anotherVendor->id,
        'status' => 'pending',
    ]);
    
    // Create another order for the first vendor
    $this->order2 = Order::create([
        'user_id' => $this->customer->id,
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

test('non-vendor cannot access vendor orders', function () {
    $response = $this->actingAs($this->customer)
        ->getJson('/api/vendor/orders');
    
    $response->assertStatus(403);
});

test('vendor can view their orders', function () {
    $response = $this->actingAs($this->vendorUser)
        ->getJson('/api/vendor/orders');
    
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
                    'user',
                ]
            ]
        ]);
    
    // Should return 2 orders
    expect(count($response->json('data')))->toBe(2);
    
    // Each order should only contain items from this vendor
    foreach ($response->json('data') as $order) {
        foreach ($order['items'] as $item) {
            expect($item['vendor_id'])->toBe($this->vendor->id);
        }
    }
});

test('vendor can view a specific order', function () {
    $response = $this->actingAs($this->vendorUser)
        ->getJson("/api/vendor/orders/{$this->order->id}");
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'total_price',
                'status',
                'items',
                'vendor_statuses',
                'user',
            ]
        ]);
    
    // Order should only contain items from this vendor
    foreach ($response->json('data.items') as $item) {
        expect($item['vendor_id'])->toBe($this->vendor->id);
    }
    
    // Should only have one vendor status (for this vendor)
    expect(count($response->json('data.vendor_statuses')))->toBe(1);
    expect($response->json('data.vendor_statuses.0.vendor_id'))->toBe($this->vendor->id);
});

test('vendor cannot view order without their items', function () {
    // Create an order with items only from another vendor
    $orderWithoutVendorItems = Order::create([
        'user_id' => $this->customer->id,
        'total_price' => 200,
        'status' => 'pending',
    ]);
    
    OrderItem::create([
        'order_id' => $orderWithoutVendorItems->id,
        'product_id' => $this->product2->id,
        'vendor_id' => $this->anotherVendor->id,
        'quantity' => 1,
        'price' => 200,
    ]);
    
    OrderVendorStatus::create([
        'order_id' => $orderWithoutVendorItems->id,
        'vendor_id' => $this->anotherVendor->id,
        'status' => 'pending',
    ]);
    
    $response = $this->actingAs($this->vendorUser)
        ->getJson("/api/vendor/orders/{$orderWithoutVendorItems->id}");
    
    $response->assertStatus(404);
});

test('vendor can update order status', function () {
    $response = $this->actingAs($this->vendorUser)
        ->putJson("/api/vendor/orders/{$this->order->id}/status", [
            'status' => 'processing',
            'notes' => 'Processing your order',
        ]);
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data',
        ]);
    
    // Check that the vendor status was updated
    $vendorStatus = OrderVendorStatus::where('order_id', $this->order->id)
        ->where('vendor_id', $this->vendor->id)
        ->first();
    
    expect($vendorStatus->status)->toBe('processing');
    expect($vendorStatus->notes)->toBe('Processing your order');
    
    // Check that the order status was not changed (since the other vendor's status is still pending)
    $this->order->refresh();
    expect($this->order->status)->toBe('pending');
});

test('when all vendor statuses are completed, order status is completed', function () {
    // Update first vendor status to completed
    $this->actingAs($this->vendorUser)
        ->putJson("/api/vendor/orders/{$this->order->id}/status", [
            'status' => 'completed',
        ]);
    
    // Update second vendor status to completed
    $this->actingAs(User::find($this->anotherVendor->user_id))
        ->putJson("/api/vendor/orders/{$this->order->id}/status", [
            'status' => 'completed',
        ]);
    
    // Check that the order status was updated to completed
    $this->order->refresh();
    expect($this->order->status)->toBe('completed');
});

test('when all vendor statuses are cancelled, order status is cancelled', function () {
    // Update first vendor status to cancelled
    $this->actingAs($this->vendorUser)
        ->putJson("/api/vendor/orders/{$this->order->id}/status", [
            'status' => 'cancelled',
        ]);
    
    // Update second vendor status to cancelled
    $this->actingAs(User::find($this->anotherVendor->user_id))
        ->putJson("/api/vendor/orders/{$this->order->id}/status", [
            'status' => 'cancelled',
        ]);
    
    // Check that the order status was updated to cancelled
    $this->order->refresh();
    expect($this->order->status)->toBe('cancelled');
});

test('vendor cannot update status with invalid value', function () {
    $response = $this->actingAs($this->vendorUser)
        ->putJson("/api/vendor/orders/{$this->order->id}/status", [
            'status' => 'invalid-status',
        ]);
    
    $response->assertStatus(422);
}); 