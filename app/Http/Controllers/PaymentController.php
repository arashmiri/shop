<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * ایجاد یک پرداخت جدید برای سفارش
     */
    public function create(Request $request, $orderId)
    {
        // بررسی وجود سفارش و دسترسی کاربر
        $order = Order::findOrFail($orderId);
        
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'شما به این سفارش دسترسی ندارید'
            ], 403);
        }
        
        // بررسی وضعیت سفارش
        if ($order->isPaid()) {
            return response()->json([
                'message' => 'این سفارش قبلاً پرداخت شده است'
            ], 400);
        }
        
        if ($order->status === Order::STATUS_CANCELLED) {
            return response()->json([
                'message' => 'این سفارش لغو شده است و قابل پرداخت نیست'
            ], 400);
        }
        
        // اعتبارسنجی درگاه پرداخت
        $request->validate([
            'gateway' => 'required|string|in:zarinpal,payir,idpay',
        ]);
        
        // ایجاد رکورد پرداخت
        $payment = new Payment([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'amount' => $order->total_price,
            'status' => Payment::STATUS_PENDING,
            'gateway' => $request->gateway,
            'reference_id' => Str::random(32), // ایجاد یک شناسه مرجع منحصر به فرد
        ]);
        
        $payment->save();
        
        // بر اساس درگاه پرداخت، پردازش مناسب را انجام دهید
        switch ($request->gateway) {
            case 'zarinpal':
                return $this->processZarinpalPayment($payment);
            case 'payir':
                return $this->processPayirPayment($payment);
            case 'idpay':
                return $this->processIdpayPayment($payment);
            default:
                return response()->json([
                    'message' => 'درگاه پرداخت نامعتبر است'
                ], 400);
        }
    }
    
    /**
     * پردازش پرداخت با درگاه زرین‌پال
     */
    private function processZarinpalPayment(Payment $payment)
    {
        // در اینجا کد اتصال به API زرین‌پال قرار می‌گیرد
        // این یک نمونه ساده است و در محیط واقعی باید با API زرین‌پال ارتباط برقرار کنید
        
        // شبیه‌سازی ارتباط با زرین‌پال
        $paymentUrl = url("/payments/callback/zarinpal?reference_id={$payment->reference_id}");
        
        return response()->json([
            'message' => 'در حال انتقال به درگاه پرداخت',
            'data' => [
                'payment_id' => $payment->id,
                'reference_id' => $payment->reference_id,
                'amount' => $payment->amount,
                'payment_url' => $paymentUrl,
            ]
        ]);
    }
    
    /**
     * پردازش پرداخت با درگاه Pay.ir
     */
    private function processPayirPayment(Payment $payment)
    {
        // در اینجا کد اتصال به API Pay.ir قرار می‌گیرد
        
        // شبیه‌سازی ارتباط با Pay.ir
        $paymentUrl = url("/payments/callback/payir?reference_id={$payment->reference_id}");
        
        return response()->json([
            'message' => 'در حال انتقال به درگاه پرداخت',
            'data' => [
                'payment_id' => $payment->id,
                'reference_id' => $payment->reference_id,
                'amount' => $payment->amount,
                'payment_url' => $paymentUrl,
            ]
        ]);
    }
    
    /**
     * پردازش پرداخت با درگاه IDPay
     */
    private function processIdpayPayment(Payment $payment)
    {
        // در اینجا کد اتصال به API IDPay قرار می‌گیرد
        
        // شبیه‌سازی ارتباط با IDPay
        $paymentUrl = url("/payments/callback/idpay?reference_id={$payment->reference_id}");
        
        return response()->json([
            'message' => 'در حال انتقال به درگاه پرداخت',
            'data' => [
                'payment_id' => $payment->id,
                'reference_id' => $payment->reference_id,
                'amount' => $payment->amount,
                'payment_url' => $paymentUrl,
            ]
        ]);
    }
    
    /**
     * پردازش بازگشت از درگاه پرداخت زرین‌پال
     */
    public function callbackZarinpal(Request $request)
    {
        // بررسی و اعتبارسنجی پارامترهای بازگشتی از زرین‌پال
        $referenceId = $request->reference_id;
        $authority = $request->Authority;
        $status = $request->Status;
        
        // یافتن پرداخت بر اساس شناسه مرجع
        $payment = Payment::where('reference_id', $referenceId)
            ->where('status', Payment::STATUS_PENDING)
            ->first();
            
        if (!$payment) {
            return response()->json([
                'message' => 'پرداخت یافت نشد یا قبلاً پردازش شده است'
            ], 404);
        }
        
        // بررسی وضعیت پرداخت
        if ($status === 'OK') {
            // در اینجا باید تأیید پرداخت از زرین‌پال را انجام دهید
            // این یک شبیه‌سازی است
            
            // ثبت اطلاعات تراکنش
            $payment->markAsSuccessful($authority, [
                'authority' => $authority,
                'ref_id' => Str::random(8), // در واقعیت از زرین‌پال دریافت می‌شود
                'card_pan' => '6037xxxxxxxx' . rand(1000, 9999),
            ]);
            
            return response()->json([
                'message' => 'پرداخت با موفقیت انجام شد',
                'data' => [
                    'order_id' => $payment->order_id,
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                ]
            ]);
        } else {
            // ثبت شکست پرداخت
            $payment->markAsFailed([
                'authority' => $authority,
                'error' => 'پرداخت توسط کاربر لغو شد یا با خطا مواجه شد'
            ]);
            
            return response()->json([
                'message' => 'پرداخت ناموفق بود',
                'data' => [
                    'order_id' => $payment->order_id,
                    'payment_id' => $payment->id,
                ]
            ], 400);
        }
    }
    
    /**
     * پردازش بازگشت از درگاه پرداخت Pay.ir
     */
    public function callbackPayir(Request $request)
    {
        // مشابه callbackZarinpal با تغییرات مناسب برای Pay.ir
        $referenceId = $request->reference_id;
        $status = $request->status;
        $transId = $request->transId;
        
        $payment = Payment::where('reference_id', $referenceId)
            ->where('status', Payment::STATUS_PENDING)
            ->first();
            
        if (!$payment) {
            return response()->json([
                'message' => 'پرداخت یافت نشد یا قبلاً پردازش شده است'
            ], 404);
        }
        
        if ($status == 1) {
            $payment->markAsSuccessful($transId, [
                'trans_id' => $transId,
                'card_number' => '6037xxxxxxxx' . rand(1000, 9999),
            ]);
            
            return response()->json([
                'message' => 'پرداخت با موفقیت انجام شد',
                'data' => [
                    'order_id' => $payment->order_id,
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                ]
            ]);
        } else {
            $payment->markAsFailed([
                'error' => 'پرداخت ناموفق بود',
                'trans_id' => $transId,
            ]);
            
            return response()->json([
                'message' => 'پرداخت ناموفق بود',
                'data' => [
                    'order_id' => $payment->order_id,
                    'payment_id' => $payment->id,
                ]
            ], 400);
        }
    }
    
    /**
     * پردازش بازگشت از درگاه پرداخت IDPay
     */
    public function callbackIdpay(Request $request)
    {
        // مشابه callbackZarinpal با تغییرات مناسب برای IDPay
        $referenceId = $request->reference_id;
        $status = $request->status;
        $trackId = $request->track_id;
        
        $payment = Payment::where('reference_id', $referenceId)
            ->where('status', Payment::STATUS_PENDING)
            ->first();
            
        if (!$payment) {
            return response()->json([
                'message' => 'پرداخت یافت نشد یا قبلاً پردازش شده است'
            ], 404);
        }
        
        if ($status == 100) {
            $payment->markAsSuccessful($trackId, [
                'track_id' => $trackId,
                'card_no' => '6037xxxxxxxx' . rand(1000, 9999),
            ]);
            
            return response()->json([
                'message' => 'پرداخت با موفقیت انجام شد',
                'data' => [
                    'order_id' => $payment->order_id,
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                ]
            ]);
        } else {
            $payment->markAsFailed([
                'error' => 'پرداخت ناموفق بود',
                'track_id' => $trackId,
                'status' => $status,
            ]);
            
            return response()->json([
                'message' => 'پرداخت ناموفق بود',
                'data' => [
                    'order_id' => $payment->order_id,
                    'payment_id' => $payment->id,
                ]
            ], 400);
        }
    }
    
    /**
     * دریافت تاریخچه پرداخت‌های کاربر
     */
    public function history()
    {
        $payments = Payment::where('user_id', Auth::id())
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return response()->json([
            'data' => $payments
        ]);
    }
    
    /**
     * دریافت جزئیات یک پرداخت
     */
    public function show($paymentId)
    {
        $payment = Payment::where('id', $paymentId)
            ->where('user_id', Auth::id())
            ->with('order.items')
            ->firstOrFail();
            
        return response()->json([
            'data' => $payment
        ]);
    }
} 