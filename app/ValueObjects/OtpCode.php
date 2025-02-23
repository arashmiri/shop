<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class OtpCode
{
    private string $code;

    public function __construct(string $code)
    {
        // بررسی فرمت کد (۴ رقم)
        if (!preg_match('/^\d{4}$/', $code)) {
            throw new InvalidArgumentException('کد تأیید باید شامل ۴ رقم باشد.');
        }

        $this->code = $code;
    }

    // متد برای دریافت کد
    public function getCode(): string
    {
        return $this->code;
    }
}
