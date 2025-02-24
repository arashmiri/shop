<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // به طور خودکار یک کاربر برای فروشنده ایجاد می‌شود
            'name' => $this->faker->company, // نام فروشنده (نام شرکت)
            'description' => $this->faker->sentence, // توضیحات فروشنده
            'balance' => $this->faker->randomFloat(2, 100, 10000), // موجودی فروشنده
            'admin_created_by' => User::factory(), // کاربری که فروشنده را ایجاد کرده است
        ];
    }
}
