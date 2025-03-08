<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorStatus;
use App\Models\Payment;
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
    
    // Create a paid order
    $this->paidOrder = Order::create([
        'user_id' => $this->user->id,
        'total_price' => 300,
        'status' => 'paid',
    ]);
    
    OrderItem::create([
        'order_id' => $this->paidOrder->id,
        'product_id' => $this->product1->id,
        'vendor_id' => $this->vendor->id,
        'quantity' => 3,
        'price' => 100,
    ]);
    
    OrderVendorStatus::create([
        'order_id' => $this->paidOrder->id,
        'vendor_id' => $this->vendor->id,
        'status' => 'pending',
    ]);
    
    // Create a successful payment for the paid order
    $this->payment = Payment::create([
        'order_id' => $this->paidOrder->id,
        'user_id' => $this->user->id,
        'amount' => 300,
        'status' => Payment::STATUS_SUCCESSFUL,
        'transaction_id' => 'test_transaction_123',
        'reference_id' => 'test_reference_123',
        'gateway' => 'zarinpal',
        'paid_at' => now(),
        'details' => json_encode([
            'authority' => 'test_authority_123',
            'ref_id' => 'test_ref_123',
            'card_pan' => '6037xxxx1234',
        ]),
    ]);
    
    // Create a cancelled order
    $this->cancelledOrder = Order::create([
        'user_id' => $this->user->id,
        'total_price' => 400,
        'status' => 'cancelled',
    ]);
    
    OrderItem::create([
        'order_id' => $this->cancelledOrder->id,
        'product_id' => $this->product1->id,
        'vendor_id' => $this->vendor->id,
        'quantity' => 4,
        'price' => 100,
    ]);
    
    OrderVendorStatus::create([
        'order_id' => $this->cancelledOrder->id,
        'vendor_id' => $this->vendor->id,
        'status' => 'cancelled',
    ]);
});

test('guest cannot create payment', function () {
    $response = $this->postJson("/api/orders/{$this->order->id}/payments", [
        'gateway' => 'zarinpal',
    ]);
    
    $response->assertStatus(401);
});

test('user can create payment for their order', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/orders/{$this->order->id}/payments", [
            'gateway' => 'zarinpal',
        ]);
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'payment_id',
                'reference_id',
                'amount',
                'payment_url',
            ]
        ]);
    
    // Check that a payment record was created
    $payment = Payment::where('order_id', $this->order->id)->first();
    expect($payment)->not->toBeNull();
    expect($payment->status)->toBe(Payment::STATUS_PENDING);
    expect($payment->gateway)->toBe('zarinpal');
    expect((float)$payment->amount)->toBe(800.00);
});

test('user cannot create payment for another users order', function () {
    $anotherUser = User::factory()->create();
    
    $response = $this->actingAs($anotherUser)
        ->postJson("/api/orders/{$this->order->id}/payments", [
            'gateway' => 'zarinpal',
        ]);
    
    $response->assertStatus(403);
});

test('user cannot create payment for already paid order', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/orders/{$this->paidOrder->id}/payments", [
            'gateway' => 'zarinpal',
        ]);
    
    $response->assertStatus(400)
        ->assertJsonFragment([
            'message' => 'این سفارش قبلاً پرداخت شده است'
        ]);
});

test('user cannot create payment for cancelled order', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/orders/{$this->cancelledOrder->id}/payments", [
            'gateway' => 'zarinpal',
        ]);
    
    $response->assertStatus(400)
        ->assertJsonFragment([
            'message' => 'این سفارش لغو شده است و قابل پرداخت نیست'
        ]);
});

test('user must provide valid gateway', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/orders/{$this->order->id}/payments", [
            'gateway' => 'invalid_gateway',
        ]);
    
    $response->assertStatus(422);
});

test('zarinpal callback with successful payment updates order status', function () {
    // Create a pending payment
    $payment = Payment::create([
        'order_id' => $this->order->id,
        'user_id' => $this->user->id,
        'amount' => 800,
        'status' => Payment::STATUS_PENDING,
        'reference_id' => 'test_reference_456',
        'gateway' => 'zarinpal',
    ]);
    
    $response = $this->getJson("/api/payments/callback/zarinpal?reference_id=test_reference_456&Authority=test_authority_456&Status=OK");
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'order_id',
                'payment_id',
                'transaction_id',
            ]
        ]);
    
    // Check that payment was updated
    $payment->refresh();
    expect($payment->status)->toBe(Payment::STATUS_SUCCESSFUL);
    expect($payment->transaction_id)->toBe('test_authority_456');
    expect($payment->paid_at)->not->toBeNull();
    
    // Check that order status was updated
    $this->order->refresh();
    expect($this->order->status)->toBe(Order::STATUS_PAID);
});

test('zarinpal callback with failed payment', function () {
    // Create a pending payment
    $payment = Payment::create([
        'order_id' => $this->order->id,
        'user_id' => $this->user->id,
        'amount' => 800,
        'status' => Payment::STATUS_PENDING,
        'reference_id' => 'test_reference_789',
        'gateway' => 'zarinpal',
    ]);
    
    $response = $this->getJson("/api/payments/callback/zarinpal?reference_id=test_reference_789&Authority=test_authority_789&Status=NOK");
    
    $response->assertStatus(400)
        ->assertJsonStructure([
            'message',
            'data' => [
                'order_id',
                'payment_id',
            ]
        ]);
    
    // Check that payment was updated
    $payment->refresh();
    expect($payment->status)->toBe(Payment::STATUS_FAILED);
    
    // Check that order status was not updated
    $this->order->refresh();
    expect($this->order->status)->toBe('pending');
});

test('payir callback with successful payment updates order status', function () {
    // Create a pending payment
    $payment = Payment::create([
        'order_id' => $this->order->id,
        'user_id' => $this->user->id,
        'amount' => 800,
        'status' => Payment::STATUS_PENDING,
        'reference_id' => 'test_reference_payir',
        'gateway' => 'payir',
    ]);
    
    $response = $this->getJson("/api/payments/callback/payir?reference_id=test_reference_payir&status=1&transId=test_trans_payir");
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'order_id',
                'payment_id',
                'transaction_id',
            ]
        ]);
    
    // Check that payment was updated
    $payment->refresh();
    expect($payment->status)->toBe(Payment::STATUS_SUCCESSFUL);
    expect($payment->transaction_id)->toBe('test_trans_payir');
    
    // Check that order status was updated
    $this->order->refresh();
    expect($this->order->status)->toBe(Order::STATUS_PAID);
});

test('idpay callback with successful payment updates order status', function () {
    // Create a pending payment
    $payment = Payment::create([
        'order_id' => $this->order->id,
        'user_id' => $this->user->id,
        'amount' => 800,
        'status' => Payment::STATUS_PENDING,
        'reference_id' => 'test_reference_idpay',
        'gateway' => 'idpay',
    ]);
    
    $response = $this->getJson("/api/payments/callback/idpay?reference_id=test_reference_idpay&status=100&track_id=test_track_idpay");
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'order_id',
                'payment_id',
                'transaction_id',
            ]
        ]);
    
    // Check that payment was updated
    $payment->refresh();
    expect($payment->status)->toBe(Payment::STATUS_SUCCESSFUL);
    expect($payment->transaction_id)->toBe('test_track_idpay');
    
    // Check that order status was updated
    $this->order->refresh();
    expect($this->order->status)->toBe(Order::STATUS_PAID);
});

test('user can view their payment history', function () {
    // پاک کردن همه پرداخت‌های قبلی برای اطمینان از نتیجه تست
    Payment::query()->delete();
    
    // ایجاد یک پرداخت جدید برای کاربر
    $payment = Payment::create([
        'order_id' => $this->paidOrder->id,
        'user_id' => $this->user->id,
        'amount' => 300,
        'status' => Payment::STATUS_SUCCESSFUL,
        'transaction_id' => 'test_transaction_history',
        'reference_id' => 'test_reference_history',
        'gateway' => 'zarinpal',
        'paid_at' => now(),
    ]);
    
    $response = $this->actingAs($this->user)
        ->getJson('/api/payments');
    
    $response->assertStatus(200);
    
    // بررسی ساختار پاسخ
    $responseContent = $response->getContent();
    $responseData = json_decode($responseContent, true);
    
    // بررسی ساختار پاسخ paginate
    expect($responseData)->toHaveKey('data');
    expect($responseData['data'])->toHaveKey('data');
    expect($responseData['data']['data'])->toBeArray();
    
    // بررسی وجود پرداخت ایجاد شده
    $paymentItems = $responseData['data']['data'];
    $found = false;
    
    foreach ($paymentItems as $item) {
        if ($item['transaction_id'] === 'test_transaction_history') {
            $found = true;
            expect($item['reference_id'])->toBe('test_reference_history');
            expect($item['gateway'])->toBe('zarinpal');
            expect($item['status'])->toBe(Payment::STATUS_SUCCESSFUL);
            break;
        }
    }
    
    expect($found)->toBeTrue();
});

test('user can view details of their payment', function () {
    $response = $this->actingAs($this->user)
        ->getJson("/api/payments/{$this->payment->id}");
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'order_id',
                'user_id',
                'amount',
                'status',
                'gateway',
                'transaction_id',
                'reference_id',
                'paid_at',
                'details',
                'order',
            ]
        ]);
    
    expect($response->json('data.id'))->toBe($this->payment->id);
    expect($response->json('data.order_id'))->toBe($this->paidOrder->id);
    expect($response->json('data.status'))->toBe(Payment::STATUS_SUCCESSFUL);
});

test('user cannot view another users payment', function () {
    $anotherUser = User::factory()->create();
    
    $response = $this->actingAs($anotherUser)
        ->getJson("/api/payments/{$this->payment->id}");
    
    $response->assertStatus(404);
}); 