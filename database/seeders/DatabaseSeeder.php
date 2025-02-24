<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $vendors = Vendor::factory(5)->create();

        $vendors->each(function ($vendor) {
            Product::factory(3)->create([
                'vendor_id' => $vendor->id, // انتساب محصول به فروشنده
            ]);
        });

        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
    }
}
