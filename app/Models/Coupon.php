<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'vendor_id',
        'product_id',
        'category_id',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'is_active',
        'starts_at',
        'expires_at',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_limit_per_user' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
    
    /**
     * Constants for coupon types
     */
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed';
    
    /**
     * Get the vendor that owns the coupon.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
    
    /**
     * Get the product that owns the coupon.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    /**
     * The users that have used this coupon.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('order_id', 'discount_amount', 'used_at')
            ->withTimestamps();
    }
    
    /**
     * Check if the coupon is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        // بررسی فعال بودن کوپن
        if (!$this->is_active) {
            return false;
        }
        
        // بررسی تاریخ شروع و پایان کوپن
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        
        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }
        
        // بررسی محدودیت استفاده کلی
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if the coupon is valid for a specific user.
     *
     * @param User $user
     * @return bool
     */
    public function isValidForUser(User $user): bool
    {
        if (!$this->isValid()) {
            return false;
        }
        
        // بررسی محدودیت استفاده برای هر کاربر
        if ($this->usage_limit_per_user) {
            $userUsageCount = $this->users()
                ->wherePivot('user_id', $user->id)
                ->count();
                
            if ($userUsageCount >= $this->usage_limit_per_user) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Calculate the discount amount for a given price.
     *
     * @param float $price
     * @return float
     */
    public function calculateDiscountAmount(float $price): float
    {
        if (!$this->isValid()) {
            return 0;
        }
        
        // بررسی حداقل مبلغ سفارش
        if ($this->min_order_amount && $price < $this->min_order_amount) {
            return 0;
        }
        
        $discountAmount = 0;
        
        if ($this->type === self::TYPE_PERCENTAGE) {
            $discountAmount = $price * ($this->value / 100);
            
            // اعمال محدودیت حداکثر مبلغ تخفیف برای کوپن‌های درصدی
            if ($this->max_discount_amount && $discountAmount > $this->max_discount_amount) {
                $discountAmount = $this->max_discount_amount;
            }
        } else { // TYPE_FIXED
            $discountAmount = $this->value;
            
            // تخفیف ثابت نباید از قیمت محصول بیشتر باشد
            if ($discountAmount > $price) {
                $discountAmount = $price;
            }
        }
        
        return $discountAmount;
    }
    
    /**
     * Apply the coupon for a user and order.
     *
     * @param User $user
     * @param Order $order
     * @param float $discountAmount
     * @return bool
     */
    public function applyForUser(User $user, Order $order, float $discountAmount): bool
    {
        if (!$this->isValidForUser($user)) {
            return false;
        }
        
        // ثبت استفاده از کوپن
        $this->users()->attach($user->id, [
            'order_id' => $order->id,
            'discount_amount' => $discountAmount,
            'used_at' => now(),
        ]);
        
        // افزایش تعداد استفاده از کوپن
        $this->increment('used_count');
        
        return true;
    }
    
    /**
     * Scope a query to only include active coupons.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($query) {
                $now = now();
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) {
                $now = now();
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            });
    }
    
    /**
     * Scope a query to only include coupons for a specific vendor.
     */
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }
    
    /**
     * Scope a query to only include coupons for a specific product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
    
    /**
     * Scope a query to only include coupons with available usage.
     */
    public function scopeWithAvailableUsage($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('usage_limit')
                ->orWhereRaw('used_count < usage_limit');
        });
    }
}
