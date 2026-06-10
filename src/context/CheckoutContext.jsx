import React, { createContext, useState, useEffect, useMemo, useContext } from 'react';

export const CheckoutContext = createContext();

export const useCheckout = () => useContext(CheckoutContext);

export const CheckoutProvider = ({ children }) => {
  const [checkoutStep, setCheckoutStep] = useState(1);
  const [cartItems, setCartItems] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  
  // Checkout State Data
  const [savedItems, setSavedItems] = useState([]);
  const [buyerDetails, setBuyerDetails] = useState(null);
  const [address, setAddress] = useState(null);
  const [paymentMethod, setPaymentMethod] = useState('upi');
  const [useCashCoins, setUseCashCoins] = useState(false);
  const [couponApplied, setCouponApplied] = useState(false);
  
  // Static backend config (change to your actual dev server if needed)
  const API_URL = 'http://localhost/printmont/printmont-backend/api'; 
  const PRINTMONT_COINS_BALANCE = 36;
  const COUPON_DISCOUNT = 450;
  const CASH_COINS_DISCOUNT = 20;

  // 1. Fetch Cart Items on Mount
  useEffect(() => {
    fetchCartItems();
  }, []);

  const fetchCartItems = async () => {
    try {
      setIsLoading(true);
      // Try to fetch from backend API
      const response = await fetch(`${API_URL}/cart-api.php`);
      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data) {
           setCartItems(data.data);
           setIsLoading(false);
           return;
        }
      }
    } catch (error) {
      console.warn("Could not fetch cart from backend, using fallback data", error);
    }
    
    // Fallback Dummy Data if backend is unavailable or empty
    setCartItems([
      { id: 1, name: "Printmont Rust Brown Half-Sleeves Knitted Mens Shirt", size: "M", color: "Brown", price: 549, originalPrice: 645, discount: 35, offers: 2, quantity: 1, image: "/men_shirt/men-shirt-2.jpeg" },
      { id: 2, name: "Printmont Blue Half-Sleeves Knitted Mens Shirt", size: "L", color: "Blue", price: 699, originalPrice: 999, discount: 30, offers: 1, quantity: 1, image: "/men_shirt/men-shirt-2.jpeg" },
      { id: 3, name: "Printmont Green T-Shirt", size: "M", color: "Green", price: 349, originalPrice: 499, discount: 20, offers: 0, quantity: 1, image: "/men_shirt/men-shirt-2.jpeg" },
      { id: 4, name: "Printmont White Casual Shirt", size: "XL", color: "White", price: 799, originalPrice: 1199, discount: 40, offers: 3, quantity: 1, image: "/men_shirt/men-shirt-2.jpeg" }
    ]);
    setIsLoading(false);
  };

  // Cart Actions
  const updateQuantity = (id, newQuantity) => {
    if (newQuantity < 1) return;
    setCartItems(items => items.map(item => item.id === id ? { ...item, quantity: newQuantity } : item));
    // Optional: Call PUT /api/cart-api.php here
  };

  const removeItem = (id) => {
    setCartItems(items => items.filter(item => item.id !== id));
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
      const response = await fetch(`${API_URL}/checkout-api.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
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
        alert("Order Placed Successfully! Order ID: " + data.order_id);
        // Reset cart or redirect to success page
      } else {
        alert("Failed to place order: " + data.error);
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
      updateQuantity, removeItem, saveForLater, moveToCart,
      submitOrder,
      
      // Computed
      cartTotals
    }}>
      {children}
    </CheckoutContext.Provider>
  );
};
