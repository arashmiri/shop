<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    /**
     * Display a listing of active coupons (for vendors).
     */
    public function index()
    {
        $user = auth()->user();
        
        if (!$user->vendor) {
            return response()->json([
                'status' => 'error',
                'message' => 'شما دسترسی به این بخش را ندارید.'
            ], 403);
        }
        
        $coupons = Coupon::where('vendor_id', $user->vendor->id)->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $coupons
        ]);
    }
    
    /**
     * Store a newly created coupon (for vendors).
     */
    public function store(Request $request)
    {
        // بررسی دسترسی فروشنده
        $user = auth()->user();
        if (!$user->vendor) {
            return response()->json([
                'status' => 'error',
                'message' => 'شما دسترسی به این بخش را ندارید.'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:' . Coupon::TYPE_PERCENTAGE . ',' . Coupon::TYPE_FIXED,
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'product_id' => 'nullable|exists:products,id',
            'usage_limit' => 'nullable|integer|min:0',
            'usage_limit_per_user' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'اطلاعات وارد شده نامعتبر است.',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // بررسی مالکیت محصول
        if ($request->has('product_id')) {
            $product = Product::find($request->product_id);
            if (!$product || $product->vendor_id != $user->vendor->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'شما مالک این محصول نیستید.'
                ], 403);
            }
        }
        
        // ایجاد کد کوپن منحصر به فرد
        $code = $request->input('code');
        if (empty($code)) {
            $code = strtoupper(Str::random(8));
            while (Coupon::where('code', $code)->exists()) {
                $code = strtoupper(Str::random(8));
            }
        } elseif (Coupon::where('code', $code)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'کد کوپن تکراری است.'
            ], 422);
        }
        
        $coupon = new Coupon($request->all());
        $coupon->code = $code;
        $coupon->vendor_id = $user->vendor->id;
        $coupon->used_count = 0;
        $coupon->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'کوپن با موفقیت ایجاد شد.',
            'data' => $coupon
        ], 201);
    }
    
    /**
     * Display the specified coupon.
     */
    public function show($id)
    {
        $coupon = Coupon::findOrFail($id);
        
        // بررسی دسترسی فروشنده
        $user = auth()->user();
        if (!$user->vendor || $coupon->vendor_id != $user->vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'شما دسترسی به این کوپن را ندارید.'
            ], 403);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $coupon
        ]);
    }
    
    /**
     * Update the specified coupon (for vendors).
     */
    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);
        
        // بررسی دسترسی فروشنده
        $user = auth()->user();
        if (!$user->vendor || $coupon->vendor_id != $user->vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'شما دسترسی به این کوپن را ندارید.'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'type' => 'in:' . Coupon::TYPE_PERCENTAGE . ',' . Coupon::TYPE_FIXED,
            'value' => 'numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'product_id' => 'nullable|exists:products,id',
            'usage_limit' => 'nullable|integer|min:0',
            'usage_limit_per_user' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'اطلاعات وارد شده نامعتبر است.',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // بررسی مالکیت محصول
        if ($request->has('product_id')) {
            $product = Product::find($request->product_id);
            if (!$product || $product->vendor_id != $user->vendor->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'شما مالک این محصول نیستید.'
                ], 403);
            }
        }
        
        // بررسی کد کوپن
        if ($request->has('code') && $request->code != $coupon->code) {
            if (Coupon::where('code', $request->code)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'کد کوپن تکراری است.'
                ], 422);
            }
        }
        
        $coupon->update($request->all());
        
        return response()->json([
            'status' => 'success',
            'message' => 'کوپن با موفقیت به‌روزرسانی شد.',
            'data' => $coupon
        ]);
    }
    
    /**
     * Remove the specified coupon (for vendors).
     */
    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        
        // بررسی دسترسی فروشنده
        $user = auth()->user();
        if (!$user->vendor || $coupon->vendor_id != $user->vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'شما دسترسی به این کوپن را ندارید.'
            ], 403);
        }
        
        $coupon->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'کوپن با موفقیت حذف شد.'
        ]);
    }
    
    /**
     * Apply a coupon to the user's cart.
     */
    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:coupons,code',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'کد کوپن نامعتبر است.',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = auth()->user();
        $cart = $user->cart;
        
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'سبد خرید شما خالی است.'
            ], 400);
        }
        
        $coupon = Coupon::where('code', $request->code)->first();
        
        // بررسی اعتبار کوپن
        if (!$coupon->isValidForUser($user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'این کوپن معتبر نیست یا منقضی شده است.'
            ], 400);
        }
        
        // بررسی حداقل مبلغ سفارش
        if ($coupon->min_order_amount && $cart->subtotal < $coupon->min_order_amount) {
            return response()->json([
                'status' => 'error',
                'message' => "حداقل مبلغ سفارش برای استفاده از این کوپن {$coupon->min_order_amount} تومان است."
            ], 400);
        }
        
        // بررسی محدودیت محصول
        if ($coupon->product_id) {
            $hasProduct = $cart->items->contains(function ($item) use ($coupon) {
                return $item->product_id == $coupon->product_id;
            });
            
            if (!$hasProduct) {
                $product = Product::find($coupon->product_id);
                return response()->json([
                    'status' => 'error',
                    'message' => "این کوپن فقط برای محصول {$product->name} قابل استفاده است."
                ], 400);
            }
        }
        
        // بررسی محدودیت فروشنده
        if ($coupon->vendor_id) {
            $hasVendorProduct = $cart->items->contains(function ($item) use ($coupon) {
                return $item->product->vendor_id == $coupon->vendor_id;
            });
            
            if (!$hasVendorProduct) {
                $vendor = $coupon->vendor;
                return response()->json([
                    'status' => 'error',
                    'message' => "این کوپن فقط برای محصولات فروشنده {$vendor->name} قابل استفاده است."
                ], 400);
            }
        }
        
        // اعمال کوپن به سبد خرید
        $cart->coupon_id = $coupon->id;
        $cart->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'کوپن با موفقیت اعمال شد.',
            'data' => [
                'cart' => $cart,
                'discount_amount' => $cart->discount_amount,
                'total' => $cart->total
            ]
        ]);
    }
    
    /**
     * Remove a coupon from the user's cart.
     */
    public function remove()
    {
        $user = auth()->user();
        $cart = $user->cart;
        
        if (!$cart) {
            return response()->json([
                'status' => 'error',
                'message' => 'سبد خرید شما خالی است.'
            ], 400);
        }
        
        if (!$cart->coupon_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'هیچ کوپنی روی سبد خرید شما اعمال نشده است.'
            ], 400);
        }
        
        $cart->coupon_id = null;
        $cart->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'کوپن با موفقیت حذف شد.',
            'data' => [
                'cart' => $cart,
                'total' => $cart->total
            ]
        ]);
    }
}
