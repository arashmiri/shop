<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderVendorStatus;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    /**
     * Create an order from a cart.
     *
     * @param int $cartId
     * @param array $data Additional order data
     * @return \App\Models\Order
     */
    public function createOrderFromCart($cartId, array $data = [])
    {
        $cart = Cart::with('items.product.vendor')->findOrFail($cartId);
        
        // Check if cart belongs to the authenticated user
        if (Auth::id() != $cart->user_id) {
            throw new \Exception('Cart does not belong to the authenticated user');
        }
        
        // Check if cart has items
        if ($cart->items->isEmpty()) {
            throw new \Exception('Cart is empty');
        }
        
        // Group cart items by vendor
        $itemsByVendor = $cart->getItemsByVendor();
        
        // Calculate total price
        $totalPrice = $cart->items->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });
        
        // Start a database transaction
        return DB::transaction(function () use ($cart, $totalPrice, $itemsByVendor, $data) {
            // Create the order
            $order = Order::create([
                'user_id' => Auth::id(),
                'total_price' => $totalPrice,
                'status' => Order::STATUS_PENDING,
            ]);
            
            // Create order items and vendor statuses
            foreach ($itemsByVendor as $vendorId => $vendorData) {
                $vendor = $vendorData['vendor'];
                
                // Create vendor status for this order
                OrderVendorStatus::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendor->id,
                    'status' => OrderVendorStatus::STATUS_PENDING,
                ]);
                
                // Create order items for this vendor
                foreach ($vendorData['items'] as $item) {
                    $product = $item->product;
                    
                    // Check stock availability
                    if ($product->stock < $item->quantity) {
                        throw new \Exception("Not enough stock for product: {$product->name}");
                    }
                    
                    // Create order item
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'vendor_id' => $vendor->id,
                        'quantity' => $item->quantity,
                        'price' => $product->price,
                    ]);
                    
                    // Decrease product stock
                    $product->stock -= $item->quantity;
                    $product->save();
                }
            }
            
            // Clear the cart
            $cart->items()->delete();
            
            return $order->load('items.product', 'vendorStatuses.vendor');
        });
    }
    
    /**
     * Update the status of an order.
     *
     * @param int $orderId
     * @param string $status
     * @return \App\Models\Order
     */
    public function updateOrderStatus($orderId, $status)
    {
        $order = Order::findOrFail($orderId);
        
        // Check if the status is valid
        if (!in_array($status, [
            Order::STATUS_PENDING,
            Order::STATUS_PAID,
            Order::STATUS_SHIPPED,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ])) {
            throw new \Exception('Invalid order status');
        }
        
        $order->status = $status;
        $order->save();
        
        return $order;
    }
    
    /**
     * Update the status of a vendor's part of an order.
     *
     * @param int $orderId
     * @param int $vendorId
     * @param string $status
     * @param string|null $notes
     * @return \App\Models\OrderVendorStatus
     */
    public function updateVendorOrderStatus($orderId, $vendorId, $status, $notes = null)
    {
        $vendorStatus = OrderVendorStatus::where('order_id', $orderId)
            ->where('vendor_id', $vendorId)
            ->firstOrFail();
        
        // Check if the status is valid
        if (!in_array($status, [
            OrderVendorStatus::STATUS_PENDING,
            OrderVendorStatus::STATUS_PROCESSING,
            OrderVendorStatus::STATUS_SHIPPED,
            OrderVendorStatus::STATUS_COMPLETED,
            OrderVendorStatus::STATUS_CANCELLED,
        ])) {
            throw new \Exception('Invalid vendor order status');
        }
        
        $vendorStatus->status = $status;
        
        if ($notes) {
            $vendorStatus->notes = $notes;
        }
        
        $vendorStatus->save();
        
        // Check if all vendor statuses are completed or cancelled
        $order = Order::find($orderId);
        $allVendorStatuses = $order->vendorStatuses;
        
        $allCompleted = $allVendorStatuses->every(function ($status) {
            return in_array($status->status, [
                OrderVendorStatus::STATUS_COMPLETED,
                OrderVendorStatus::STATUS_CANCELLED,
            ]);
        });
        
        if ($allCompleted) {
            $allCancelled = $allVendorStatuses->every(function ($status) {
                return $status->status === OrderVendorStatus::STATUS_CANCELLED;
            });
            
            if ($allCancelled) {
                $order->status = Order::STATUS_CANCELLED;
            } else {
                $order->status = Order::STATUS_COMPLETED;
            }
            
            $order->save();
        }
        
        return $vendorStatus;
    }
    
    /**
     * Cancel an order and restore product stock.
     *
     * @param int $orderId
     * @return \App\Models\Order
     */
    public function cancelOrder($orderId)
    {
        $order = Order::with('items.product')->findOrFail($orderId);
        
        // Check if the order can be cancelled
        if ($order->status === Order::STATUS_COMPLETED) {
            throw new \Exception('Completed orders cannot be cancelled');
        }
        
        if ($order->status === Order::STATUS_CANCELLED) {
            throw new \Exception('Order is already cancelled');
        }
        
        // Start a database transaction
        return DB::transaction(function () use ($order) {
            // Restore product stock
            foreach ($order->items as $item) {
                $product = $item->product;
                $product->stock += $item->quantity;
                $product->save();
            }
            
            // Update all vendor statuses to cancelled
            foreach ($order->vendorStatuses as $vendorStatus) {
                $vendorStatus->status = OrderVendorStatus::STATUS_CANCELLED;
                $vendorStatus->save();
            }
            
            // Update order status
            $order->status = Order::STATUS_CANCELLED;
            $order->save();
            
            return $order->fresh();
        });
    }
} 