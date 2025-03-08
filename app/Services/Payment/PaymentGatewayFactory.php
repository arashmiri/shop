<?php

namespace App\Services\Payment;

use InvalidArgumentException;

class PaymentGatewayFactory
{
    /**
     * ایجاد نمونه درگاه پرداخت بر اساس نام درگاه
     *
     * @param string $gateway نام درگاه پرداخت
     * @return PaymentGatewayInterface
     * @throws InvalidArgumentException اگر درگاه پرداخت نامعتبر باشد
     */
    public static function create(string $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            'zarinpal' => new ZarinpalGateway(),
            'payir' => new PayirGateway(),
            'idpay' => new IdpayGateway(),
            default => throw new InvalidArgumentException("درگاه پرداخت '{$gateway}' پشتیبانی نمی‌شود."),
        };
    }
    
    /**
     * بررسی معتبر بودن درگاه پرداخت
     *
     * @param string $gateway نام درگاه پرداخت
     * @return bool
     */
    public static function isValidGateway(string $gateway): bool
    {
        return in_array($gateway, self::getSupportedGateways());
    }
    
    /**
     * دریافت لیست درگاه‌های پرداخت پشتیبانی شده
     *
     * @return array
     */
    public static function getSupportedGateways(): array
    {
        return [
            'zarinpal',
            'payir',
            'idpay',
        ];
    }
} 