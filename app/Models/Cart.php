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
    ];
    
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
     * Calculate the total price of all items in the cart.
     */
    public function getTotalAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });
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
