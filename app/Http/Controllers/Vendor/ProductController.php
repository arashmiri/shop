<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    // متد برای ایجاد محصول جدید
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'شما فروشنده نیستید!'], 403);
        }

        $product = $vendor->products()->create($request->all());

        return response()->json(['message' => 'محصول با موفقیت ایجاد شد', 'product' => $product], 201);
    }

    // متد برای دریافت لیست محصولات فروشنده
    public function index()
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'شما فروشنده نیستید!'], 403);
        }

        $products = $vendor->products()->paginate(10);

        return response()->json($products);
    }

    // متد برای ویرایش محصول
    public function update(Request $request, $productId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'شما فروشنده نیستید!'], 403);
        }

        $product = $vendor->products()->find($productId);

        if (!$product) {
            return response()->json(['message' => 'محصول یافت نشد!'], 404);
        }

        // بروزرسانی اطلاعات محصول
        $product->update($request->all());

        return response()->json(['message' => 'محصول با موفقیت ویرایش شد', 'product' => $product]);
    }

    // متد برای حذف محصول
    public function destroy($productId)
    {
        $vendor = Auth::user()->vendor;

        if (!$vendor) {
            return response()->json(['message' => 'شما فروشنده نیستید!'], 403);
        }

        $product = $vendor->products()->find($productId);

        if (!$product) {
            return response()->json(['message' => 'محصول یافت نشد!'], 404);
        }

        // حذف محصول
        $product->delete();

        return response()->json(['message' => 'محصول با موفقیت حذف شد']);
    }
}
