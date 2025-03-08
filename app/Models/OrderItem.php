<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'order_id',
        'product_id',
        'vendor_id',
        'quantity',
        'price',
    ];
    
    /**
     * Get the order that owns the item.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Get the product for this order item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    /**
     * Get the vendor for this order item.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    
    /**
     * Get the subtotal for this order item.
     */
    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->price;
    }
}
