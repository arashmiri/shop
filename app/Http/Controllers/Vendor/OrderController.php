<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderVendorStatus;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected $orderService;
    
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
        // Middleware should be applied in the routes file, not in the controller
    }
    
    /**
     * Display a listing of the vendor's orders.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $vendor = Auth::user()->vendor;
        
        if (!$vendor) {
            return response()->json([
                'message' => 'شما فروشنده نیستید'
            ], 403);
        }
        
        // Get all orders that have items from this vendor
        $orderIds = $vendor->orderItems()->pluck('order_id')->unique();
        $orders = Order::with(['items' => function ($query) use ($vendor) {
            $query->where('vendor_id', $vendor->id)->with('product');
        }, 'vendorStatuses' => function ($query) use ($vendor) {
            $query->where('vendor_id', $vendor->id);
        }, 'user'])->whereIn('id', $orderIds)->latest()->get();
        
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
        $vendor = Auth::user()->vendor;
        
        if (!$vendor) {
            return response()->json([
                'message' => 'شما فروشنده نیستید'
            ], 403);
        }
        
        // Check if the order has items from this vendor
        $order = Order::whereHas('items', function ($query) use ($vendor) {
            $query->where('vendor_id', $vendor->id);
        })->with(['items' => function ($query) use ($vendor) {
            $query->where('vendor_id', $vendor->id)->with('product');
        }, 'vendorStatuses' => function ($query) use ($vendor) {
            $query->where('vendor_id', $vendor->id);
        }, 'user'])->findOrFail($id);
        
        return response()->json([
            'data' => $order
        ]);
    }
    
    /**
     * Update the status of the vendor's part of an order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,shipped,completed,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $vendor = Auth::user()->vendor;
        
        if (!$vendor) {
            return response()->json([
                'message' => 'شما فروشنده نیستید'
            ], 403);
        }
        
        try {
            // Check if the order has items from this vendor
            $order = Order::whereHas('items', function ($query) use ($vendor) {
                $query->where('vendor_id', $vendor->id);
            })->findOrFail($id);
            
            $vendorStatus = $this->orderService->updateVendorOrderStatus(
                $order->id,
                $vendor->id,
                $request->status,
                $request->notes
            );
            
            return response()->json([
                'message' => 'وضعیت سفارش با موفقیت بروزرسانی شد',
                'data' => $vendorStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
