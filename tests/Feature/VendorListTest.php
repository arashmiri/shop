<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    Sanctum::actingAs($this->admin);

    // ایجاد فروشندگان
    $vendorRole = Role::firstOrCreate(['name' => 'vendor']);
    User::factory()->count(5)->create()->each(fn ($user) => $user->assignRole('vendor'));
});

test('admin can get list of vendors', function () {
    $response = $this->getJson('/api/admin/vendors?per_page=5');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'links',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    expect($response->json('meta.total'))->toBeGreaterThan(0);
});
