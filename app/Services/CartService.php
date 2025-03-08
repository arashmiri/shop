<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartService
{
    /**
     * Get or create a cart for the current user or session.
     *
     * @return \App\Models\Cart
     */
    public function getCart()
    {
        if (Auth::check()) {
            // For authenticated users, get or create their cart
            $cart = Auth::user()->cart;
            
            if (!$cart) {
                $cart = Cart::create([
                    'user_id' => Auth::id(),
                ]);
            }
        } else {
            // For guests, use session ID to track their cart
            $sessionId = session()->getId();
            
            $cart = Cart::where('session_id', $sessionId)->first();
            
            if (!$cart) {
                $cart = Cart::create([
                    'session_id' => $sessionId,
                ]);
            }
        }
        
        return $cart->load('items.product.vendor');
    }
    
    /**
     * Add a product to the cart.
     *
     * @param int $productId
     * @param int $quantity
     * @return \App\Models\CartItem
     */
    public function addItem($productId, $quantity = 1)
    {
        $cart = $this->getCart();
        $product = Product::findOrFail($productId);
        
        // Check if the product is already in the cart
        $cartItem = $cart->items()->where('product_id', $productId)->first();
        
        if ($cartItem) {
            // Update quantity if the product is already in the cart
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            // Add new item to cart
            $cartItem = $cart->items()->create([
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }
        
        return $cartItem->load('product.vendor');
    }
    
    /**
     * Update the quantity of a cart item.
     *
     * @param int $cartItemId
     * @param int $quantity
     * @return \App\Models\CartItem
     */
    public function updateItemQuantity($cartItemId, $quantity)
    {
        $cart = $this->getCart();
        $cartItem = $cart->items()->findOrFail($cartItemId);
        
        $cartItem->quantity = $quantity;
        $cartItem->save();
        
        return $cartItem->load('product.vendor');
    }
    
    /**
     * Remove an item from the cart.
     *
     * @param int $cartItemId
     * @return bool
     */
    public function removeItem($cartItemId)
    {
        $cart = $this->getCart();
        $cartItem = $cart->items()->findOrFail($cartItemId);
        
        return $cartItem->delete();
    }
    
    /**
     * Clear all items from the cart.
     *
     * @return bool
     */
    public function clearCart()
    {
        $cart = $this->getCart();
        
        return $cart->items()->delete();
    }
    
    /**
     * Transfer a guest cart to a user after login.
     *
     * @param string $sessionId
     * @param int $userId
     * @return \App\Models\Cart|null
     */
    public function transferGuestCart($sessionId, $userId)
    {
        $guestCart = Cart::where('session_id', $sessionId)->first();
        
        if (!$guestCart || !$guestCart->items->count()) {
            return null;
        }
        
        $userCart = Cart::firstOrCreate(['user_id' => $userId]);
        
        // Transfer items from guest cart to user cart
        foreach ($guestCart->items as $item) {
            $existingItem = $userCart->items()->where('product_id', $item->product_id)->first();
            
            if ($existingItem) {
                $existingItem->quantity += $item->quantity;
                $existingItem->save();
            } else {
                $userCart->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ]);
            }
        }
        
        // Delete the guest cart
        $guestCart->delete();
        
        return $userCart->load('items.product.vendor');
    }
} 