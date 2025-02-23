<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = ['phone', 'code', 'expires_at'];

    public $timestamps = false;

    // تبدیل expires_at به یک شیء Carbon
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // بررسی اعتبار OTP
    public function isValid(): bool
    {
        return Carbon::parse($this->expires_at)->isFuture();
    }
}
