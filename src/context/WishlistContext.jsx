import React, { createContext, useContext, useState, useEffect } from 'react';
import toast from 'react-hot-toast';
import { API_ENDPOINTS } from '../config/apiEndpoints';

const WishlistContext = createContext();

export const WishlistProvider = ({ children }) => {
  const [wishlist, setWishlist] = useState([]);
  const [loading, setLoading] = useState(false);

  const fetchWishlist = async () => {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    if (token) {
      try {
        setLoading(true);
        const res = await fetch(API_ENDPOINTS.WISHLIST, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        if (res.ok) {
          const data = await res.json();
          if (data.success && Array.isArray(data.data)) {
            setWishlist(data.data);
            return;
          }
        }
      } catch (e) {
        console.error("Wishlist API Fetch Error:", e);
      } finally {
        setLoading(false);
      }
    }

    // Guest fallback from localStorage
    try {
      const localData = localStorage.getItem('printmont_wishlist');
      if (localData) {
        setWishlist(JSON.parse(localData));
      } else {
        setWishlist([]);
      }
    } catch (e) {
      setWishlist([]);
    }
  };

  useEffect(() => {
    fetchWishlist();
  }, []);

  const saveLocalWishlist = (items) => {
    setWishlist(items);
    try {
      localStorage.setItem('printmont_wishlist', JSON.stringify(items));
    } catch (e) {}
  };

  const isInWishlist = (productId) => {
    return wishlist.some(item => (item.id == productId || item.product_id == productId));
  };

  const addToWishlist = async (product) => {
    const productId = product.id || product.product_id;
    if (isInWishlist(productId)) return;

    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    if (token) {
      try {
        const res = await fetch(API_ENDPOINTS.WISHLIST, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify({ product_id: productId })
        });
        const data = await res.json();
        if (data.success) {
          toast.success("Added to Wishlist!");
          fetchWishlist();
          return;
        }
      } catch (e) {
        console.error("AddToWishlist API Error:", e);
      }
    }

    // Local fallback
    const updated = [...wishlist, product];
    saveLocalWishlist(updated);
    toast.success("Added to Wishlist!");
  };

  const removeFromWishlist = async (productId) => {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    if (token) {
      try {
        const deleteUrl = typeof API_ENDPOINTS.WISHLIST_DELETE === 'function'
          ? API_ENDPOINTS.WISHLIST_DELETE(productId)
          : `${API_ENDPOINTS.WISHLIST}?product_id=${productId}`;

        const res = await fetch(deleteUrl, {
          method: 'DELETE',
          headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await res.json();
        if (data.success) {
          toast.error("Removed from Wishlist!");
          fetchWishlist();
          return;
        }
      } catch (e) {
        console.error("RemoveFromWishlist API Error:", e);
      }
    }

    // Local fallback
    const updated = wishlist.filter(item => (item.id != productId && item.product_id != productId));
    saveLocalWishlist(updated);
    toast.error("Removed from Wishlist!");
  };

  const toggleWishlist = (product) => {
    const productId = product.id || product.product_id;
    if (isInWishlist(productId)) {
      removeFromWishlist(productId);
    } else {
      addToWishlist(product);
    }
  };

  return (
    <WishlistContext.Provider value={{
      wishlist,
      wishlistCount: wishlist.length,
      loading,
      isInWishlist,
      addToWishlist,
      removeFromWishlist,
      toggleWishlist,
      fetchWishlist
    }}>
      {children}
    </WishlistContext.Provider>
  );
};

export const useWishlist = () => {
  const context = useContext(WishlistContext);
  if (!context) {
    throw new Error('useWishlist must be used within WishlistProvider');
  }
  return context;
};
