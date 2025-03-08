<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Discount extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'vendor_id',
        'product_id',
        'category_id',
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
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
    
    /**
     * Constants for discount types
     */
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed';
    
    /**
     * Get the vendor that owns the discount.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
    
    /**
     * Get the product that owns the discount.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    /**
     * Check if the discount is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        // بررسی فعال بودن تخفیف
        if (!$this->is_active) {
            return false;
        }
        
        // بررسی تاریخ شروع و پایان تخفیف
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        
        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
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
            
            // اعمال محدودیت حداکثر مبلغ تخفیف برای تخفیف‌های درصدی
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
     * Scope a query to only include active discounts.
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
     * Scope a query to only include discounts for a specific vendor.
     */
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }
    
    /**
     * Scope a query to only include discounts for a specific product.
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}
