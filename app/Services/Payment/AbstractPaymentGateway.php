<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    /**
     * ایجاد URL بازگشت از درگاه پرداخت
     *
     * @param Payment $payment
     * @param string $gateway نام درگاه پرداخت
     * @return string
     */
    protected function generateCallbackUrl(Payment $payment, string $gateway): string
    {
        return url("/api/payments/callback/{$gateway}") . "?reference_id={$payment->reference_id}";
    }
    
    /**
     * یافتن پرداخت بر اساس شناسه مرجع
     *
     * @param string $referenceId
     * @return Payment|null
     */
    protected function findPaymentByReferenceId(string $referenceId): ?Payment
    {
        return Payment::where('reference_id', $referenceId)
            ->where('status', Payment::STATUS_PENDING)
            ->first();
    }
    
    /**
     * ثبت خطا در لاگ
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error("Payment Gateway Error: {$message}", $context);
    }
    
    /**
     * ثبت اطلاعات در لاگ
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info("Payment Gateway Info: {$message}", $context);
    }
} 