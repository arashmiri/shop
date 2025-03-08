<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'phone',
        'name',
        'email',
        'password',
    ];
    protected $guard_name = 'sanctum';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAuthIdentifier() {
        return $this->id; // استفاده از ID به‌جای شماره موبایل
    }

    public function getAuthIdentifierName() {
        return 'phone'; // شماره موبایل به عنوان شناسه ورود
    }

    public function vendor()
    {
        return $this->hasOne(Vendor::class, 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
    /**
     * Get the user's active cart.
     */
    public function cart()
    {
        return $this->hasOne(Cart::class)->latest();
    }
    
    /**
     * Get all carts for the user.
     */
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }
    
    /**
     * Get all orders for the user.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    
    /**
     * The coupons that have been used by the user.
     */
    public function coupons()
    {
        return $this->belongsToMany(Coupon::class)
            ->withPivot('order_id', 'discount_amount', 'used_at')
            ->withTimestamps();
    }
}
