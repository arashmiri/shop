<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'description', 'balance', 'admin_created_by'];

    /**
     * رابطه با مدل User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_created_by');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    
    /**
     * Get all order items associated with this vendor.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    /**
     * Get all order statuses for this vendor.
     */
    public function orderStatuses()
    {
        return $this->hasMany(OrderVendorStatus::class);
    }
    
    /**
     * Get all orders associated with this vendor through order items.
     */
    public function orders()
    {
        return Order::whereHas('items', function ($query) {
            $query->where('vendor_id', $this->id);
        });
    }
}
