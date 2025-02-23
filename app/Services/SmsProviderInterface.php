<?php

namespace App\Services;

interface SmsProviderInterface
{
    public function sendOtp(string $phone, string $message): bool;
}
