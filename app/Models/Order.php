<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'discount_amount',
        'coupon_id',
        'subtotal',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];
    
    /**
     * The possible order statuses.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the items for the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    /**
     * Get the vendor statuses for the order.
     */
    public function vendorStatuses()
    {
        return $this->hasMany(OrderVendorStatus::class);
    }
    
    /**
     * Get the payments for the order.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    
    /**
     * Get the successful payment for the order.
     */
    public function successfulPayment()
    {
        return $this->payments()->where('status', 'successful')->latest()->first();
    }
    
    /**
     * Check if the order has been paid.
     */
    public function isPaid()
    {
        return $this->status === self::STATUS_PAID || $this->successfulPayment() !== null;
    }
    
    /**
     * Get all vendors associated with this order.
     */
    public function vendors()
    {
        return Vendor::whereIn('id', $this->items->pluck('vendor_id')->unique());
    }
    
    /**
     * Group order items by vendor.
     */
    public function getItemsByVendor()
    {
        $groupedItems = [];
        
        foreach ($this->items as $item) {
            $vendorId = $item->vendor_id;
            
            if (!isset($groupedItems[$vendorId])) {
                $groupedItems[$vendorId] = [
                    'vendor' => Vendor::find($vendorId),
                    'status' => $this->vendorStatuses->where('vendor_id', $vendorId)->first(),
                    'items' => []
                ];
            }
            
            $groupedItems[$vendorId]['items'][] = $item;
        }
        
        return $groupedItems;
    }
    
    /**
     * Get the coupon used for this order.
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
    
    /**
     * Get the coupon usage record for this order.
     */
    public function couponUsage()
    {
        return $this->hasOne(CouponUser::class);
    }
}
