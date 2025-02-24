<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // اطمینان از وجود نقش‌ها
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $vendorRole = Role::firstOrCreate(['name' => 'vendor']);

        // ایجاد ادمین
        $admin = User::factory()->create([
            'phone' => '09384409950', // یک شماره مشخص برای ادمین
            'name' => 'Admin User',
        ]);

        $admin->assignRole($adminRole);

        // ایجاد ۵ فروشنده
        User::factory(5)->create()->each(function ($user) use ($vendorRole) {
            $user->assignRole($vendorRole);
        });

        // ایجاد ۱۰ کاربر عادی بدون نقش
        User::factory(10)->create();
    }
}
