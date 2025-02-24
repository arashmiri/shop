<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_assign_role_to_user()
    {
        // 1. ایجاد نقش جدید
        $role = Role::create(['name' => 'admin']);

        // 2. ایجاد یک کاربر
        $user = User::factory()->create();

        // 3. انتساب نقش به کاربر
        $user->assignRole('admin');

        // 4. بررسی اینکه کاربر نقش admin را دارد
        $this->assertTrue($user->hasRole('admin'));

        // 5. بررسی اینکه نقش در دیتابیس به درستی ذخیره شده است
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_id' => $user->id,
            'model_type' => User::class,
        ]);
    }
}
