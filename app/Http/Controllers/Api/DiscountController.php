<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller
{
    /**
     * Display a listing of active discounts.
     */
    public function index()
    {
        $discounts = Discount::active()->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $discounts
        ]);
    }
    
    /**
     * Display discounts for a specific product.
     */
    public function forProduct($productId)
    {
        $product = Product::findOrFail($productId);
        
        $discounts = Discount::active()
            ->where(function($query) use ($product) {
                $query->where('product_id', $product->id)
                    ->orWhere('vendor_id', $product->vendor_id)
                    ->orWhereNull('product_id')->whereNull('vendor_id');
            })
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $discounts
        ]);
    }
    
    /**
     * Store a newly created discount (for vendors).
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
            'type' => 'required|in:' . Discount::TYPE_PERCENTAGE . ',' . Discount::TYPE_FIXED,
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'product_id' => 'nullable|exists:products,id',
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
        
        $discount = new Discount($request->all());
        $discount->vendor_id = $user->vendor->id;
        $discount->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'تخفیف با موفقیت ایجاد شد.',
            'data' => $discount
        ], 201);
    }
    
    /**
     * Display the specified discount.
     */
    public function show($id)
    {
        $discount = Discount::findOrFail($id);
        
        // بررسی دسترسی فروشنده
        $user = auth()->user();
        if ($user->vendor && $discount->vendor_id != $user->vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'شما دسترسی به این تخفیف را ندارید.'
            ], 403);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $discount
        ]);
    }
    
    /**
     * Update the specified discount (for vendors).
     */
    public function update(Request $request, $id)
    {
        $discount = Discount::findOrFail($id);
        
        // بررسی دسترسی فروشنده
        $user = auth()->user();
        if (!$user->vendor || $discount->vendor_id != $user->vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'شما دسترسی به این تخفیف را ندارید.'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'type' => 'in:' . Discount::TYPE_PERCENTAGE . ',' . Discount::TYPE_FIXED,
            'value' => 'numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'product_id' => 'nullable|exists:products,id',
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
        
        $discount->update($request->all());
        
        return response()->json([
            'status' => 'success',
            'message' => 'تخفیف با موفقیت به‌روزرسانی شد.',
            'data' => $discount
        ]);
    }
    
    /**
     * Remove the specified discount (for vendors).
     */
    public function destroy($id)
    {
        $discount = Discount::findOrFail($id);
        
        // بررسی دسترسی فروشنده
        $user = auth()->user();
        if (!$user->vendor || $discount->vendor_id != $user->vendor->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'شما دسترسی به این تخفیف را ندارید.'
            ], 403);
        }
        
        $discount->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'تخفیف با موفقیت حذف شد.'
        ]);
    }
}
