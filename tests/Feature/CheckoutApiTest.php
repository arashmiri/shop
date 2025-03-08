<?php

use App\Models\Cart;
use App\Models\Order;
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
    
    // Create a cart with items for the user
    $this->cart = Cart::create([
        'user_id' => $this->user->id,
    ]);
    
    $this->cart->items()->create([
        'product_id' => $this->product1->id,
        'quantity' => 2,
    ]);
    
    $this->cart->items()->create([
        'product_id' => $this->product2->id,
        'quantity' => 3,
    ]);
});

test('guest cannot access checkout', function () {
    $response = $this->getJson('/api/checkout');
    
    $response->assertStatus(401);
});

test('authenticated user can view checkout information', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/checkout');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'cart',
                'total',
                'items_by_vendor',
            ]
        ]);
    
    // Total should be 800 (2 * 100 + 3 * 200)
    expect($response->json('data.total'))->toBe(800);
});

test('user with empty cart cannot checkout', function () {
    // Clear the cart
    $this->cart->items()->delete();
    
    $response = $this->actingAs($this->user)
        ->getJson('/api/checkout');
    
    $response->assertStatus(400)
        ->assertJson([
            'message' => 'سبد خرید شما خالی است'
        ]);
});

test('authenticated user can process checkout', function () {
    $initialStock1 = $this->product1->stock;
    $initialStock2 = $this->product2->stock;
    
    $response = $this->actingAs($this->user)
        ->postJson('/api/checkout');
    
    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => [
                'order',
                'items_by_vendor',
            ]
        ]);
    
    // Check that an order was created
    $order = Order::where('user_id', $this->user->id)->first();
    expect($order)->not->toBeNull();
    expect((float)$order->total_price)->toBe(800.00);
    expect($order->status)->toBe('pending');
    
    // Check that order items were created
    expect($order->items()->count())->toBe(2);
    
    // Check that vendor status was created
    expect($order->vendorStatuses()->count())->toBe(1);
    expect($order->vendorStatuses()->first()->vendor_id)->toBe($this->vendor->id);
    expect($order->vendorStatuses()->first()->status)->toBe('pending');
    
    // Check that product stock was decreased
    $this->product1->refresh();
    $this->product2->refresh();
    expect($this->product1->stock)->toBe($initialStock1 - 2);
    expect($this->product2->stock)->toBe($initialStock2 - 3);
    
    // Check that the cart was cleared
    expect($this->cart->items()->count())->toBe(0);
});

test('checkout fails if product is out of stock', function () {
    // Set product stock to less than cart quantity
    $this->product1->stock = 1; // Cart has 2
    $this->product1->save();
    
    $response = $this->actingAs($this->user)
        ->postJson('/api/checkout');
    
    $response->assertStatus(400)
        ->assertJsonFragment([
            'message' => 'Not enough stock for product: Test Product 1'
        ]);
    
    // Check that no order was created
    expect(Order::where('user_id', $this->user->id)->count())->toBe(0);
    
    // Check that the cart was not cleared
    expect($this->cart->items()->count())->toBe(2);
}); 