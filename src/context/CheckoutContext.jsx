import React, { createContext, useState, useEffect, useMemo, useContext, useRef, useCallback } from 'react';
import { BASE_URL, API_ENDPOINTS } from '../config/apiEndpoints';
import useRequireLogin from '../hooks/useRequireLogin';

export const CheckoutContext = createContext();

export const useCheckout = () => useContext(CheckoutContext);

export const CheckoutProvider = ({ children }) => {
  const requireLogin = useRequireLogin();
  // 0 is the cart, which is not one of the numbered steps. Checkout proper is
  // 1 Address -> 2 Order Summary -> 3 Payment.
  const [checkoutStep, setCheckoutStep] = useState(0);
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
  // Coupon code the server accepted (admin coupons.php); '' = none
  const [couponCode, setCouponCode] = useState('');
  // Server price of the cart (user-api.php?action=checkout_quote) — the amounts shown at checkout
  const [quote, setQuote] = useState(null);
  const [quoteLoading, setQuoteLoading] = useState(false);
  // Entered from the cart's "Enter Delivery Pincode" button and used to
  // pre-fill the pincode on the address step.
  const [deliveryPincode, setDeliveryPincode] = useState('');
  // Result of the pincode check for deliveryPincode (city, state, delivery date, COD)
  const [deliveryInfo, setDeliveryInfo] = useState(null);
  
  const API_URL = BASE_URL;

  // 1. Fetch Cart Items on Mount (if backend cart API available)
  useEffect(() => {
    fetchCartItems();
  }, []);

  const fetchCartItems = async () => {
    // cart-api.php requires a logged-in user (no guest carts) — skip the call
    // entirely when there's no token instead of firing a request that's
    // guaranteed to 401.
    const token = localStorage.getItem('token');
    if (!token) return;

    // If local cart already has items, do not overwrite with backend empty state
    const saved = localStorage.getItem('printmont_cart');
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed) && parsed.length > 0) return;
      } catch (e) {}
    }

    try {
      const headers = {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      };
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
  // Login is compulsory to add to cart. Returns false (and sends the guest to login) when not logged in.
  const addToCart = (product, qty = 1, options = {}) => {
    if (!requireLogin('Please login to add items to your cart')) return false;
    const productId = product.id || product.productId || product.product_id || (product.title ? product.title.toLowerCase().replace(/[^a-z0-9]/g, '-') : 'product-' + Date.now());

    // Sync to backend cart-api.php if logged in and product_id is valid
    const token = localStorage.getItem('token');
    if (token && Number(productId) > 0) {
      fetch(`${API_URL}/cart-api.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          product_id: Number(productId),
          quantity: qty > 0 ? qty : 1,
          attributes: options
        })
      }).catch(err => console.error('Cart API sync failed:', err));
    }

    setCartItems(prev => {
      const existingIndex = prev.findIndex(item => String(item.id) === String(productId));
      
      if (existingIndex > -1) {
        // Same product again: add to its quantity (new object, so React sees the change)
        return prev.map((item, i) => (i === existingIndex ? { ...item, quantity: (Number(item.quantity) || 0) + qty } : item));
      }

      const price = Number(product.perPiecePrice || product.currentPrice || product.price || product.discountedPrice || 0);
      const originalPrice = Number(product.originalPrice || product.regular_price || product.mrp || price);
      const rawDiscount = product.discount ? parseInt(product.discount) : (originalPrice > price && price > 0 ? Math.round(((originalPrice - price) / originalPrice) * 100) : 20);

      let imageSrc = '/men_shirt/men-shirt-2.jpeg';
      if (product.images && product.images.length > 0) {
        imageSrc = typeof product.images[0] === 'string' ? product.images[0] : (product.images[0]?.image_url || product.images[0]);
      } else if (product.image) {
        imageSrc = product.image;
      } else if (product.image_url) {
        imageSrc = product.image_url;
      } else if (product.primary_image) {
        imageSrc = product.primary_image;
      } else if (product.thumbnail) {
        imageSrc = product.thumbnail;
      }

      const newItem = {
        id: productId,
        name: product.title || product.name || 'Printmont Custom Product',
        slug: product.slug || '',
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
    return true;
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

  // What the server needs to price a cart line: product, quantity and the chosen options
  const linePayload = (item) => ({
    product_id: item.id || item.productId || item.product_id,
    quantity: Number(item.quantity) || 1,
    attributes: {
      size: item.size || null,
      color: item.color && item.color !== 'Default' ? item.color : null,
      customization: item.customization || {},
    },
  });

  const authToken = () => localStorage.getItem('token') || localStorage.getItem('user_token');

  const fetchQuote = useCallback(async (code = couponCode, coins = useCashCoins) => {
    if (cartItems.length === 0) return null;
    const headers = { 'Content-Type': 'application/json' };
    const token = authToken();
    if (token) headers['Authorization'] = `Bearer ${token}`;
    const res = await fetch(API_ENDPOINTS.CHECKOUT_QUOTE, {
      method: 'POST',
      headers,
      body: JSON.stringify({ items: cartItems.map(linePayload), coupon_code: code, use_coins: coins }),
    });
    return res.json();
  }, [cartItems, couponCode, useCashCoins]);

  // Re-price whenever the cart, coupon or coins choice changes
  const quoteSeq = useRef(0);
  useEffect(() => {
    if (cartItems.length === 0) {
      setQuote(null);
      return undefined;
    }
    const seq = ++quoteSeq.current;
    const timer = setTimeout(async () => {
      setQuoteLoading(true);
      try {
        const data = await fetchQuote();
        if (seq === quoteSeq.current && data && Array.isArray(data.items)) setQuote(data);
      } catch (e) {
        console.error('Checkout quote error:', e);
      } finally {
        if (seq === quoteSeq.current) setQuoteLoading(false);
      }
    }, 250);
    return () => clearTimeout(timer);
  }, [fetchQuote, cartItems.length]);

  /** Checks a coupon with the server. Returns { valid, message }. */
  const applyCoupon = async (code) => {
    const trimmed = String(code || '').trim();
    if (!trimmed) return { valid: false, message: 'Enter a coupon code' };
    try {
      const data = await fetchQuote(trimmed, useCashCoins);
      if (data?.coupon?.valid) {
        setCouponCode(data.coupon.code);
        setQuote(data);
        return { valid: true, message: `Coupon ${data.coupon.code} applied — you save ₹${Number(data.coupon.amount).toLocaleString('en-IN')}` };
      }
      return { valid: false, message: data?.coupon?.message || 'This coupon code is not valid.' };
    } catch {
      return { valid: false, message: 'Could not check the coupon. Please try again.' };
    }
  };

  const removeCoupon = () => setCouponCode('');

  // Amounts for the cart / checkout screens. The server quote is used once it arrives;
  // until then a local estimate (same rules, without coupon / coins) fills in.
  const cartTotals = useMemo(() => {
    const totalItems = cartItems.reduce((acc, item) => acc + (Number(item.quantity) || 0), 0);
    if (quote && Array.isArray(quote.items) && cartItems.length > 0) {
      return {
        totalItems: quote.item_count || totalItems,
        originalTotalPrice: Number(quote.mrp_total) || 0,
        totalSellingPrice: Number(quote.subtotal) || 0,
        totalDiscount: Number(quote.discount) || 0,
        deliveryCharges: Number(quote.delivery) || 0,
        couponApplied: quote.coupon?.valid ? Number(quote.coupon.amount) : 0,
        couponCode: quote.coupon?.valid ? quote.coupon.code : '',
        cashCoinsApplied: Number(quote.coins?.amount) || 0,
        coinsUsed: Number(quote.coins?.used) || 0,
        coinsBalance: Number(quote.coins?.balance) || 0,
        coinsUsable: Number(quote.coins?.usable) || 0,
        totalPayable: Number(quote.grand_total) || 0,
        totalSaved: Number(quote.you_save) || 0,
        errors: quote.errors || [],
        isServerPrice: true,
      };
    }
    const originalTotalPrice = cartItems.reduce((acc, item) => acc + (Number(item.originalPrice || item.price) * item.quantity), 0);
    const totalSellingPrice = cartItems.reduce((acc, item) => acc + (Number(item.price) * item.quantity), 0);
    const deliveryCharges = totalSellingPrice > 1000 || totalSellingPrice === 0 ? 0 : 160;
    return {
      totalItems,
      originalTotalPrice,
      totalSellingPrice,
      totalDiscount: Math.max(0, originalTotalPrice - totalSellingPrice),
      deliveryCharges,
      couponApplied: 0,
      couponCode: '',
      cashCoinsApplied: 0,
      coinsUsed: 0,
      coinsBalance: 0,
      coinsUsable: 0,
      totalPayable: totalSellingPrice + deliveryCharges,
      totalSaved: Math.max(0, originalTotalPrice - totalSellingPrice),
      errors: [],
      isServerPrice: false,
    };
  }, [cartItems, quote]);

  // Empties the cart itself. Saved-for-later items are deliberately left
  // alone — they were never part of the order.
  const clearCart = () => {
    setCartItems([]);
    setCouponCode('');
    setUseCashCoins(false);
    setQuote(null);
  };

  /**
   * Places the order. The server prices it again (never trusts amounts from here).
   * COD → order is placed and the cart is cleared.
   * Online → returns `razorpay` details; call verifyPayment() after Razorpay's success callback.
   */
  const submitOrder = async (extraParams = {}) => {
    try {
      const token = authToken();
      const headers = { 'Content-Type': 'application/json' };
      if (token) headers['Authorization'] = `Bearer ${token}`;

      const finalPaymentMethod = extraParams.paymentMethod || paymentMethod || 'cod';
      const recipientName = buyerDetails?.name || address?.name || '';
      const recipientEmail = buyerDetails?.email || address?.email || '';
      const recipientPhone = buyerDetails?.mobile || buyerDetails?.phone || address?.mobile || address?.phone || '';

      const orderPayload = {
        customer_name: recipientName,
        customer_email: recipientEmail,
        customer_phone: recipientPhone,
        buyerDetails: { name: recipientName, email: recipientEmail, mobile: recipientPhone, phone: recipientPhone },
        address: address || {},
        payment_method: finalPaymentMethod,
        paymentMethod: finalPaymentMethod,
        items: cartItems.map((item) => ({
          ...linePayload(item),
          name: item.name || item.title,
          image: item.image || item.product_image || '',
        })),
        coupon_code: couponCode,
        use_coins: useCashCoins,
        notes: address?.orderNotes || '',
      };

      const response = await fetch(API_ENDPOINTS.CREATE_ORDER, {
        method: 'POST',
        headers,
        body: JSON.stringify(orderPayload)
      });
      const data = await response.json().catch(() => ({}));

      if (!response.ok || !data.success) {
        return { success: false, error: data.error || data.message || 'Could not place your order.' };
      }

      // COD: done. Online: the cart stays until the payment is verified.
      if (!data.razorpay) clearCart();

      return {
        success: true,
        orderId: data.order_id ?? data.id ?? null,
        orderNumber: data.order_number ?? null,
        grandTotal: data.grand_total ?? null,
        razorpay: data.razorpay || null,
      };
    } catch (error) {
      console.error("Order submission error:", error);
      return { success: false, error: 'Could not reach the checkout server.' };
    }
  };

  /** Confirms an online payment on the server (Razorpay signature check), then clears the cart. */
  const verifyPayment = async (orderId, razorpayResponse) => {
    try {
      const res = await fetch(API_ENDPOINTS.VERIFY_PAYMENT, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${authToken()}` },
        body: JSON.stringify({ order_id: orderId, ...razorpayResponse }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.success) {
        return { success: false, error: data.error || 'Payment could not be verified.' };
      }
      clearCart();
      return { success: true, orderId: data.order_id, orderNumber: data.order_number };
    } catch {
      return { success: false, error: 'Could not reach the server to confirm your payment.' };
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
      couponCode, applyCoupon, removeCoupon,
      deliveryPincode, setDeliveryPincode,
      deliveryInfo, setDeliveryInfo,
      quote, quoteLoading,

      // Actions
      addToCart, updateQuantity, removeItem, saveForLater, moveToCart,
      clearCart, submitOrder, verifyPayment,

      // Computed
      cartTotals
    }}>
      {children}
    </CheckoutContext.Provider>
  );
};
