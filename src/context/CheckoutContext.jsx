import React, { createContext, useState, useEffect, useMemo, useContext } from 'react';
import { BASE_URL, API_ENDPOINTS } from '../config/apiEndpoints';

export const CheckoutContext = createContext();

export const useCheckout = () => useContext(CheckoutContext);

export const CheckoutProvider = ({ children }) => {
  const [checkoutStep, setCheckoutStep] = useState(1);
  const [cartItems, setCartItems] = useState(() => {
    try {
      const saved = localStorage.getItem('printmont_cart');
      return saved ? JSON.parse(saved) : [];
    } catch (e) {
      return [];
    }
  });
  const [isLoading, setIsLoading] = useState(false);

  // Sync cart items to localStorage
  useEffect(() => {
    try {
      localStorage.setItem('printmont_cart', JSON.stringify(cartItems));
    } catch (e) {}
  }, [cartItems]);
  
  // Checkout State Data
  const [savedItems, setSavedItems] = useState([]);
  const [buyerDetails, setBuyerDetails] = useState(null);
  const [address, setAddress] = useState(null);
  const [paymentMethod, setPaymentMethod] = useState('upi');
  const [useCashCoins, setUseCashCoins] = useState(false);
  const [couponApplied, setCouponApplied] = useState(false);
  
  // Static backend config
  const API_URL = BASE_URL;
  const PRINTMONT_COINS_BALANCE = 36;
  const COUPON_DISCOUNT = 450;
  const CASH_COINS_DISCOUNT = 20;

  // 1. Fetch Cart Items on Mount (if backend cart API available)
  useEffect(() => {
    fetchCartItems();
  }, []);

  const fetchCartItems = async () => {
    // If local cart already has items, do not overwrite with backend empty state
    const saved = localStorage.getItem('printmont_cart');
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed) && parsed.length > 0) return;
      } catch (e) {}
    }

    try {
      const token = localStorage.getItem('token');
      const headers = { 'Content-Type': 'application/json' };
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }
      const response = await fetch(`${API_URL}/cart-api.php`, { headers });
      if (response.ok) {
        const data = await response.json();
        // cart-api.php returns { success, data: { items, subtotal, count } }.
        const serverItems = Array.isArray(data.data) ? data.data : data.data?.items;
        if (data.success && Array.isArray(serverItems) && serverItems.length > 0) {
          // Cart lines key off product_id server-side; the rest of checkout uses id.
          setCartItems(serverItems.map(item => ({
            ...item,
            id: item.product_id ?? item.id,
          })));
        }
      }
    } catch (error) {
      // Keep real items from localStorage or empty state
    }
  };

  // Cart Actions
  const addToCart = (product, qty = 1, options = {}) => {
    setCartItems(prev => {
      const productId = product.id || product.productId || (product.title ? product.title.toLowerCase().replace(/[^a-z0-9]/g, '-') : 'product-' + Date.now());
      const existingIndex = prev.findIndex(item => String(item.id) === String(productId));
      
      if (existingIndex > -1) {
        const updated = [...prev];
        updated[existingIndex].quantity += qty;
        return updated;
      }

      const price = Number(product.perPiecePrice || product.currentPrice || product.price || 499);
      const originalPrice = Number(product.originalPrice || (price * 1.3));
      const rawDiscount = product.discount ? parseInt(product.discount) : 20;

      let imageSrc = '/men_shirt/men-shirt-2.jpeg';
      if (product.images && product.images.length > 0) {
        imageSrc = product.images[0];
      } else if (product.image) {
        imageSrc = product.image;
      } else if (product.primary_image) {
        imageSrc = product.primary_image;
      }

      const newItem = {
        id: productId,
        name: product.title || product.name || 'Printmont Custom Product',
        price: isNaN(price) ? 499 : price,
        originalPrice: isNaN(originalPrice) ? 699 : originalPrice,
        discount: isNaN(rawDiscount) ? 20 : rawDiscount,
        quantity: qty > 0 ? qty : 1,
        image: imageSrc,
        size: options.size || 'M',
        color: options.color || 'Default',
        seller: product.brand || 'Printmont Assured',
        offers: 2,
        ...options
      };
      return [...prev, newItem];
    });
  };

  const updateQuantity = (id, newQuantity) => {
    if (newQuantity < 1) return;
    setCartItems(items => items.map(item => item.id === id ? { ...item, quantity: newQuantity } : item));
    setSavedItems(items => items.map(item => item.id === id ? { ...item, quantity: newQuantity } : item));
    // Optional: Call PUT /api/cart-api.php here
  };

  const removeItem = (id) => {
    setCartItems(items => items.filter(item => item.id !== id));
    setSavedItems(items => items.filter(item => item.id !== id));
    // Optional: Call DELETE /api/cart-api.php here
  };

  const saveForLater = (id) => {
    const itemToSave = cartItems.find(item => item.id === id);
    if (itemToSave) {
      setCartItems(items => items.filter(item => item.id !== id));
      setSavedItems(prev => [...prev, itemToSave]);
    }
  };

  const moveToCart = (id) => {
    const itemToCart = savedItems.find(item => item.id === id);
    if (itemToCart) {
      setSavedItems(items => items.filter(item => item.id !== id));
      setCartItems(prev => [...prev, itemToCart]);
    }
  };

  // Dynamic Calculations
  const cartTotals = useMemo(() => {
    const totalItems = cartItems.reduce((acc, item) => acc + item.quantity, 0);
    const originalTotalPrice = cartItems.reduce((acc, item) => acc + (item.originalPrice * item.quantity), 0);
    const totalSellingPrice = cartItems.reduce((acc, item) => acc + (item.price * item.quantity), 0);
    const totalDiscount = originalTotalPrice - totalSellingPrice;
    
    const deliveryCharges = originalTotalPrice > 1000 ? 0 : 160;
    
    let totalPayable = totalSellingPrice + deliveryCharges;
    
    if (couponApplied) {
        totalPayable -= COUPON_DISCOUNT;
    }
    
    if (useCashCoins) {
        totalPayable -= CASH_COINS_DISCOUNT;
    }

    return {
      totalItems,
      originalTotalPrice,
      totalSellingPrice,
      totalDiscount,
      deliveryCharges,
      couponApplied: couponApplied ? COUPON_DISCOUNT : 0,
      cashCoinsApplied: useCashCoins ? CASH_COINS_DISCOUNT : 0,
      totalPayable,
      totalSaved: totalDiscount + (couponApplied ? COUPON_DISCOUNT : 0) + (useCashCoins ? CASH_COINS_DISCOUNT : 0)
    };
  }, [cartItems, couponApplied, useCashCoins]);

  // Final Order Submission
  const submitOrder = async () => {
    try {
      const token = localStorage.getItem('token') || localStorage.getItem('user_token');
      const headers = { 'Content-Type': 'application/json' };
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      const response = await fetch(API_ENDPOINTS.CREATE_ORDER, {
        method: 'POST',
        headers,
        body: JSON.stringify({
          buyerDetails,
          address,
          paymentMethod,
          items: cartItems,
          totalAmount: cartTotals.totalPayable
        })
      });
      
      const data = await response.json();
      if (data.success) {
        alert("Order Placed Successfully! Order ID: " + (data.order_id || data.id || "Success"));
        // Reset cart or redirect to success page
      } else {
        alert("Failed to place order: " + (data.error || data.message || "Unknown error"));
      }
    } catch (error) {
      console.error("Order submission error:", error);
      alert("Error connecting to checkout server.");
    }
  };

  return (
    <CheckoutContext.Provider value={{
      // State
      checkoutStep, setCheckoutStep,
      cartItems, isLoading,
      savedItems, setSavedItems,
      buyerDetails, setBuyerDetails,
      address, setAddress,
      paymentMethod, setPaymentMethod,
      useCashCoins, setUseCashCoins,
      couponApplied, setCouponApplied,
      PRINTMONT_COINS_BALANCE,
      
      // Actions
      addToCart, updateQuantity, removeItem, saveForLater, moveToCart,
      submitOrder,
      
      // Computed
      cartTotals
    }}>
      {children}
    </CheckoutContext.Provider>
  );
};
