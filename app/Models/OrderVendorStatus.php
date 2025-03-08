<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderVendorStatus extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'order_id',
        'vendor_id',
        'status',
        'notes',
    ];
    
    /**
     * The possible statuses.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    /**
     * Get the order that owns the status.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Get the vendor associated with this status.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
