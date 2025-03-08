<?php

namespace App\Services\Payment;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * پردازش درخواست پرداخت و ایجاد لینک پرداخت
     *
     * @param Payment $payment
     * @return array اطلاعات پرداخت شامل لینک پرداخت
     */
    public function processPayment(Payment $payment): array;
    
    /**
     * پردازش بازگشت از درگاه پرداخت
     *
     * @param array $requestData داده‌های دریافتی از درگاه پرداخت
     * @return array نتیجه پردازش بازگشت
     */
    public function processCallback(array $requestData): array;
    
    /**
     * بررسی موفقیت‌آمیز بودن پرداخت
     *
     * @param array $requestData داده‌های دریافتی از درگاه پرداخت
     * @return bool
     */
    public function isSuccessful(array $requestData): bool;
    
    /**
     * دریافت شناسه تراکنش از پاسخ درگاه
     *
     * @param array $requestData داده‌های دریافتی از درگاه پرداخت
     * @return string|null
     */
    public function getTransactionId(array $requestData): ?string;
    
    /**
     * دریافت جزئیات تراکنش از پاسخ درگاه
     *
     * @param array $requestData داده‌های دریافتی از درگاه پرداخت
     * @return array
     */
    public function getTransactionDetails(array $requestData): array;
} 