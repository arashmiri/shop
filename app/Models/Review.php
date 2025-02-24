<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'product_id', 'rating', 'comment'];

    /**
     * رابطه با مدل User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * رابطه با مدل Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
