<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'session_id',
        'coupon_id',
    ];
    
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['total', 'subtotal', 'discount_amount'];
    
    /**
     * Get the user that owns the cart.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the items in the cart.
     */
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
    
    /**
     * Get the coupon applied to the cart.
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
    
    /**
     * Calculate the subtotal price of all items in the cart (before discount).
     */
    public function getSubtotalAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });
    }
    
    /**
     * Calculate the discount amount based on the applied coupon.
     */
    public function getDiscountAmountAttribute()
    {
        if (!$this->coupon) {
            return 0;
        }
        
        return $this->coupon->calculateDiscountAmount($this->getSubtotalAttribute());
    }
    
    /**
     * Calculate the total price of all items in the cart (after discount).
     */
    public function getTotalAttribute()
    {
        $subtotal = $this->getSubtotalAttribute();
        $discount = $this->getDiscountAmountAttribute();
        
        return max(0, $subtotal - $discount);
    }
    
    /**
     * Group cart items by vendor.
     */
    public function getItemsByVendor()
    {
        $groupedItems = [];
        
        foreach ($this->items as $item) {
            $vendorId = $item->product->vendor_id;
            
            if (!isset($groupedItems[$vendorId])) {
                $groupedItems[$vendorId] = [
                    'vendor' => $item->product->vendor,
                    'items' => []
                ];
            }
            
            $groupedItems[$vendorId]['items'][] = $item;
        }
        
        return $groupedItems;
    }
}
