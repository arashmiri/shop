<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected $orderService;
    
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    
    /**
     * Display a listing of the user's orders.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $orders = Auth::user()->orders()->with('items.product', 'vendorStatuses.vendor')->latest()->get();
        
        return response()->json([
            'data' => $orders
        ]);
    }
    
    /**
     * Display the specified order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $order = Auth::user()->orders()->with('items.product', 'vendorStatuses.vendor')->findOrFail($id);
        
        return response()->json([
            'data' => [
                'order' => $order,
                'items_by_vendor' => $order->getItemsByVendor(),
            ]
        ]);
    }
    
    /**
     * Cancel an order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel($id)
    {
        try {
            $order = Auth::user()->orders()->findOrFail($id);
            
            $cancelledOrder = $this->orderService->cancelOrder($order->id);
            
            return response()->json([
                'message' => 'سفارش با موفقیت لغو شد',
                'data' => $cancelledOrder
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
