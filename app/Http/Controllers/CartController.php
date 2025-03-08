<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    protected $cartService;
    
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }
    
    /**
     * Get the current user's cart.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $cart = $this->cartService->getCart();
        
        return response()->json([
            'data' => [
                'cart' => $cart,
                'total' => $cart->getTotalAttribute(),
                'items_by_vendor' => $cart->getItemsByVendor(),
            ]
        ]);
    }
    
    /**
     * Add a product to the cart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $cartItem = $this->cartService->addItem(
                $request->product_id,
                $request->quantity
            );
            
            return response()->json([
                'message' => 'محصول با موفقیت به سبد خرید اضافه شد',
                'data' => $cartItem
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Update the quantity of a cart item.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $cartItem = $this->cartService->updateItemQuantity(
                $id,
                $request->quantity
            );
            
            return response()->json([
                'message' => 'تعداد محصول با موفقیت بروزرسانی شد',
                'data' => $cartItem
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Remove an item from the cart.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeItem($id)
    {
        try {
            $this->cartService->removeItem($id);
            
            return response()->json([
                'message' => 'محصول با موفقیت از سبد خرید حذف شد'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Clear all items from the cart.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCart()
    {
        try {
            $this->cartService->clearCart();
            
            return response()->json([
                'message' => 'سبد خرید با موفقیت خالی شد'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
