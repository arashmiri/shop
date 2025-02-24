<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * ایجاد محصول جدید توسط فروشنده
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $vendor = Auth::user()->vendor; // گرفتن اطلاعات فروشنده
        // گرفتن اطلاعات فروشنده

        if (!$vendor) {
            return response()->json(['message' => 'شما فروشنده نیستید!'], 403);
        }

        $product = $vendor->products()->create($request->all());


        return response()->json(['message' => 'محصول با موفقیت ایجاد شد', 'product' => $product], 201);
    }

    /**
     * دریافت لیست محصولات فروشنده
     */
    public function index()
    {
        $vendor = Auth::user()->vendor; // گرفتن اطلاعات فروشنده


        if (!$vendor) {
            return response()->json(['message' => 'شما فروشنده نیستید!'], 403);
        }

        $products = $vendor->products()->paginate(10);

        return response()->json($products);
    }
}
