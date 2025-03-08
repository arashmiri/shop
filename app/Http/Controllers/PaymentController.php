<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;

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
            'gateway' => 'required|string|in:' . implode(',', PaymentGatewayFactory::getSupportedGateways()),
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
        
        try {
            // ایجاد نمونه درگاه پرداخت با استفاده از Factory
            $gateway = PaymentGatewayFactory::create($request->gateway);
            
            // پردازش پرداخت با استفاده از درگاه
            $result = $gateway->processPayment($payment);
            
            return response()->json($result);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * پردازش بازگشت از درگاه پرداخت زرین‌پال
     */
    public function callbackZarinpal(Request $request)
    {
        try {
            $gateway = PaymentGatewayFactory::create('zarinpal');
            $result = $gateway->processCallback($request->all());
            
            return response()->json([
                'message' => $result['message'],
                'data' => $result['data'] ?? []
            ], $result['status_code'] ?? ($result['success'] ? 200 : 400));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطا در پردازش بازگشت از درگاه پرداخت: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * پردازش بازگشت از درگاه پرداخت Pay.ir
     */
    public function callbackPayir(Request $request)
    {
        try {
            $gateway = PaymentGatewayFactory::create('payir');
            $result = $gateway->processCallback($request->all());
            
            return response()->json([
                'message' => $result['message'],
                'data' => $result['data'] ?? []
            ], $result['status_code'] ?? ($result['success'] ? 200 : 400));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطا در پردازش بازگشت از درگاه پرداخت: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * پردازش بازگشت از درگاه پرداخت IDPay
     */
    public function callbackIdpay(Request $request)
    {
        try {
            $gateway = PaymentGatewayFactory::create('idpay');
            $result = $gateway->processCallback($request->all());
            
            return response()->json([
                'message' => $result['message'],
                'data' => $result['data'] ?? []
            ], $result['status_code'] ?? ($result['success'] ? 200 : 400));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطا در پردازش بازگشت از درگاه پرداخت: ' . $e->getMessage()
            ], 500);
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