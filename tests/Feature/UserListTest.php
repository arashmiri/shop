<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // ایجاد نقش‌ها در صورت وجود نداشتن
    Role::firstOrCreate(['name' => 'admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    Sanctum::actingAs($this->admin);

    // ایجاد کاربران معمولی
    User::factory()->count(15)->create();
});


test('admin can get list of users', function () {
    $this->withoutExceptionHandling();

    $response = $this->getJson('/api/admin/users?per_page=10');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'links',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    expect($response->json('meta.total'))->toBeGreaterThan(0);
});

test('admin can filter users by phone', function () {
    $user = User::factory()->create(['phone' => '09121234567']);

    $response = $this->getJson('/api/admin/users?phone=09121234567');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});
