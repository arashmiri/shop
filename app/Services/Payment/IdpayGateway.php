<?php

namespace App\Services\Payment;

use App\Models\Payment;

class IdpayGateway extends AbstractPaymentGateway
{
    /**
     * پردازش درخواست پرداخت و ایجاد لینک پرداخت
     *
     * @param Payment $payment
     * @return array اطلاعات پرداخت شامل لینک پرداخت
     */
    public function processPayment(Payment $payment): array
    {
        // در اینجا کد اتصال به API IDPay قرار می‌گیرد
        
        $callbackUrl = $this->generateCallbackUrl($payment, 'idpay');
        
        // شبیه‌سازی ارتباط با IDPay
        $this->logInfo('IDPay payment request initiated', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'callback_url' => $callbackUrl,
        ]);
        
        return [
            'message' => 'در حال انتقال به درگاه پرداخت',
            'data' => [
                'payment_id' => $payment->id,
                'reference_id' => $payment->reference_id,
                'amount' => $payment->amount,
                'payment_url' => $callbackUrl,
            ]
        ];
    }
    
    /**
     * پردازش بازگشت از درگاه پرداخت
     *
     * @param array $requestData داده‌های دریافتی از درگاه پرداخت
     * @return array نتیجه پردازش بازگشت
     */
    public function processCallback(array $requestData): array
    {
        $referenceId = $requestData['reference_id'] ?? null;
        
        if (!$referenceId) {
            $this->logError('IDPay callback missing reference_id', $requestData);
            return [
                'success' => false,
                'message' => 'شناسه مرجع پرداخت یافت نشد',
                'status_code' => 400,
            ];
        }
        
        $payment = $this->findPaymentByReferenceId($referenceId);
        
        if (!$payment) {
            $this->logError('IDPay payment not found or already processed', [
                'reference_id' => $referenceId,
            ]);
            
            return [
                'success' => false,
                'message' => 'پرداخت یافت نشد یا قبلاً پردازش شده است',
                'status_code' => 404,
            ];
        }
        
        if ($this->isSuccessful($requestData)) {
            $transactionId = $this->getTransactionId($requestData);
            $details = $this->getTransactionDetails($requestData);
            
            $payment->markAsSuccessful($transactionId, $details);
            
            $this->logInfo('IDPay payment successful', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);
            
            return [
                'success' => true,
                'message' => 'پرداخت با موفقیت انجام شد',
                'data' => [
                    'order_id' => $payment->order_id,
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                ],
                'status_code' => 200,
            ];
        } else {
            $payment->markAsFailed([
                'error' => 'پرداخت ناموفق بود',
                'track_id' => $requestData['track_id'] ?? null,
                'status' => $requestData['status'] ?? null,
            ]);
            
            $this->logError('IDPay payment failed', [
                'payment_id' => $payment->id,
                'reference_id' => $referenceId,
                'status' => $requestData['status'] ?? null,
            ]);
            
            return [
                'success' => false,
                'message' => 'پرداخت ناموفق بود',
                'data' => [
                    'order_id' => $payment->order_id,
                    'payment_id' => $payment->id,
                ],
                'status_code' => 400,
            ];
        }
    }
    
    /**
     * بررسی موفقیت‌آمیز بودن پرداخت
     *
     * @param array $requestData داده‌های دریافتی از درگاه پرداخت
     * @return bool
     */
    public function isSuccessful(array $requestData): bool
    {
        return ($requestData['status'] ?? '') == 100;
    }
    
    /**
     * دریافت شناسه تراکنش از پاسخ درگاه
     *
     * @param array $requestData داده‌های دریافتی از درگاه پرداخت
     * @return string|null
     */
    public function getTransactionId(array $requestData): ?string
    {
        return $requestData['track_id'] ?? null;
    }
    
    /**
     * دریافت جزئیات تراکنش از پاسخ درگاه
     *
     * @param array $requestData داده‌های دریافتی از درگاه پرداخت
     * @return array
     */
    public function getTransactionDetails(array $requestData): array
    {
        return [
            'track_id' => $requestData['track_id'] ?? null,
            'card_no' => '6037xxxxxxxx' . rand(1000, 9999),
        ];
    }
} 