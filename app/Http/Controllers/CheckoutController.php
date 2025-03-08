<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    protected $cartService;
    protected $orderService;
    
    public function __construct(CartService $cartService, OrderService $orderService)
    {
        $this->cartService = $cartService;
        $this->orderService = $orderService;
    }
    
    /**
     * Show checkout information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $cart = $this->cartService->getCart();
        
        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'سبد خرید شما خالی است'
            ], 400);
        }
        
        return response()->json([
            'data' => [
                'cart' => $cart,
                'total' => $cart->getTotalAttribute(),
                'items_by_vendor' => $cart->getItemsByVendor(),
            ]
        ]);
    }
    
    /**
     * Process the checkout and create an order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(Request $request)
    {
        try {
            $cart = $this->cartService->getCart();
            
            if ($cart->items->isEmpty()) {
                return response()->json([
                    'message' => 'سبد خرید شما خالی است'
                ], 400);
            }
            
            // Create order from cart
            $order = $this->orderService->createOrderFromCart($cart->id);
            
            return response()->json([
                'message' => 'سفارش شما با موفقیت ثبت شد',
                'data' => [
                    'order' => $order,
                    'items_by_vendor' => $order->getItemsByVendor(),
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
