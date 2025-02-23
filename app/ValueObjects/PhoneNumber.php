<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class PhoneNumber
{
    protected $phone;

    public function __construct(?string $phone)
    {
        if (is_null($phone) || !preg_match('/^09[0-9]{9}$/', $phone)) {
            throw new InvalidArgumentException('شماره موبایل نامعتبر است.');
        }

        $this->phone = $phone;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }
}
