<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorStatus;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    /**
     * Display checkout information.
     */
    public function index()
    {
        $user = auth()->user();
        $cart = $user->cart;
        
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'سبد خرید شما خالی است.'
            ], 400);
        }
        
        // بررسی موجودی محصولات
        $outOfStockItems = [];
        foreach ($cart->items as $item) {
            if ($item->quantity > $item->product->stock) {
                $outOfStockItems[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'requested_quantity' => $item->quantity,
                    'available_stock' => $item->product->stock
                ];
            }
        }
        
        if (!empty($outOfStockItems)) {
            return response()->json([
                'status' => 'error',
                'message' => 'برخی از محصولات سبد خرید شما موجودی کافی ندارند.',
                'out_of_stock_items' => $outOfStockItems
            ], 400);
        }
        
        // گروه‌بندی محصولات بر اساس فروشنده
        $itemsByVendor = $cart->getItemsByVendor();
        
        // محاسبه قیمت‌ها
        $subtotal = $cart->subtotal;
        $discountAmount = $cart->discount_amount;
        $total = $cart->total;
        
        // اطلاعات کوپن
        $coupon = $cart->coupon;
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'cart_id' => $cart->id,
                'items_by_vendor' => $itemsByVendor,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'coupon' => $coupon
            ]
        ]);
    }
    
    /**
     * Process the order.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $cart = $user->cart;
        
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'سبد خرید شما خالی است.'
            ], 400);
        }
        
        // بررسی موجودی محصولات
        $outOfStockItems = [];
        foreach ($cart->items as $item) {
            if ($item->quantity > $item->product->stock) {
                $outOfStockItems[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'requested_quantity' => $item->quantity,
                    'available_stock' => $item->product->stock
                ];
            }
        }
        
        if (!empty($outOfStockItems)) {
            return response()->json([
                'status' => 'error',
                'message' => 'برخی از محصولات سبد خرید شما موجودی کافی ندارند.',
                'out_of_stock_items' => $outOfStockItems
            ], 400);
        }
        
        // محاسبه قیمت‌ها
        $subtotal = $cart->subtotal;
        $discountAmount = $cart->discount_amount;
        $total = $cart->total;
        
        try {
            DB::beginTransaction();
            
            // ایجاد سفارش
            $order = new Order([
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total_price' => $total,
                'status' => Order::STATUS_PENDING,
            ]);
            
            // اضافه کردن کوپن به سفارش اگر وجود داشته باشد
            if ($cart->coupon_id) {
                $order->coupon_id = $cart->coupon_id;
            }
            
            $order->save();
            
            // ایجاد آیتم‌های سفارش
            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;
                
                $orderItem = new OrderItem([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'vendor_id' => $product->vendor_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $product->price,
                    'total_price' => $cartItem->quantity * $product->price,
                ]);
                
                $orderItem->save();
                
                // کاهش موجودی محصول
                $product->stock -= $cartItem->quantity;
                $product->save();
            }
            
            // ایجاد وضعیت سفارش برای هر فروشنده
            $vendorIds = $order->items->pluck('vendor_id')->unique();
            foreach ($vendorIds as $vendorId) {
                $orderVendorStatus = new OrderVendorStatus([
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId,
                    'status' => OrderVendorStatus::STATUS_PENDING,
                ]);
                
                $orderVendorStatus->save();
            }
            
            // اعمال کوپن در جدول coupon_user اگر وجود داشته باشد
            if ($cart->coupon_id) {
                $coupon = $cart->coupon;
                $coupon->applyForUser($user, $order, $discountAmount);
            }
            
            // حذف سبد خرید
            foreach ($cart->items as $item) {
                $item->delete();
            }
            $cart->delete();
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'سفارش شما با موفقیت ثبت شد.',
                'data' => [
                    'order_id' => $order->id,
                    'total_price' => $order->total_price,
                    'status' => $order->status
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ثبت سفارش. لطفا دوباره تلاش کنید.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
