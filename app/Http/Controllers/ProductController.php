<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {

        $products = Product::with('vendor')->get();

        return response()->json([
            'data' => $products
        ]);
    }

    public function show($id)
    {
        $product = Product::with('vendor')->findOrFail($id);
        return response()->json(['data' => $product]);
    }
}
