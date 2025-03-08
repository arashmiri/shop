<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * The possible payment statuses.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESSFUL = 'successful';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'amount',
        'status',
        'transaction_id',
        'reference_id',
        'gateway',
        'paid_at',
        'details',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'details' => 'json',
    ];

    /**
     * Get the order associated with the payment.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user associated with the payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the payment is successful.
     */
    public function isSuccessful()
    {
        return $this->status === self::STATUS_SUCCESSFUL;
    }

    /**
     * Check if the payment is pending.
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the payment has failed.
     */
    public function hasFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the payment has been refunded.
     */
    public function isRefunded()
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Mark the payment as successful.
     */
    public function markAsSuccessful($transactionId = null, $details = null)
    {
        $this->status = self::STATUS_SUCCESSFUL;
        $this->paid_at = now();
        
        if ($transactionId) {
            $this->transaction_id = $transactionId;
        }
        
        if ($details) {
            $this->details = $details;
        }
        
        $this->save();
        
        // اگر سفارش وجود داشته باشد، وضعیت آن را به پرداخت شده تغییر دهید
        if ($this->order) {
            $this->order->status = Order::STATUS_PAID;
            $this->order->save();
        }
        
        return $this;
    }

    /**
     * Mark the payment as failed.
     */
    public function markAsFailed($details = null)
    {
        $this->status = self::STATUS_FAILED;
        
        if ($details) {
            $this->details = $details;
        }
        
        $this->save();
        
        return $this;
    }

    /**
     * Mark the payment as refunded.
     */
    public function markAsRefunded($details = null)
    {
        $this->status = self::STATUS_REFUNDED;
        
        if ($details) {
            $this->details = $details;
        }
        
        $this->save();
        
        return $this;
    }
} 