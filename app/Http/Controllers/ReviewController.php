<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * ذخیره نظر برای محصول
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string',
        ]);

        $product = Product::findOrFail($productId);

        $review = new Review([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $review->save();

        return response()->json(['message' => 'نظر شما با موفقیت ثبت شد', 'review' => $review], 201);
    }

    /**
     * دریافت نظرات محصول
     */
    public function show($productId)
    {
        $product = Product::findOrFail($productId);

        $reviews = $product->reviews()->with('user')->get();

        return response()->json($reviews);
    }
}
