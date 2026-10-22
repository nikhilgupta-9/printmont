import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { API_ENDPOINTS } from '../config/apiEndpoints';
import { useAuth } from './AuthContext';
import useRequireLogin from '../hooks/useRequireLogin';

const WishlistContext = createContext();

// Old guest wishlist kept in the browser before login became compulsory. It is no longer used:
// the wishlist always comes from the server (wishlist-api.php) for the logged-in account.
const LEGACY_LOCAL_KEY = 'printmont_wishlist';

export const WishlistProvider = ({ children }) => {
  const [wishlist, setWishlist] = useState([]);
  const [loading, setLoading] = useState(false);
  const { token } = useAuth();
  const requireLogin = useRequireLogin();

  const fetchWishlist = useCallback(async () => {
    try { localStorage.removeItem(LEGACY_LOCAL_KEY); } catch { /* storage blocked */ }

    if (!token) {
      setWishlist([]);
      return;
    }
    try {
      setLoading(true);
      const res = await fetch(API_ENDPOINTS.WISHLIST, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await res.json().catch(() => null);
      if (res.ok && data?.success && Array.isArray(data.data)) {
        setWishlist(data.data);
      } else {
        console.error('Wishlist API error:', data?.error || res.status);
      }
    } catch (e) {
      console.error('Wishlist API fetch error:', e);
    } finally {
      setLoading(false);
    }
  }, [token]);

  // Reload on login / logout so the wishlist always belongs to the current account
  useEffect(() => {
    fetchWishlist();
  }, [fetchWishlist]);

  const isInWishlist = (productId) => {
    return wishlist.some(item => (item.id == productId || item.product_id == productId));
  };

  // Login is compulsory to add to the wishlist
  const addToWishlist = async (product) => {
    if (!requireLogin('Please login to add items to your wishlist')) return;
    const productId = product.id || product.product_id;
    if (!productId || isInWishlist(productId)) return;

    try {
      const res = await fetch(API_ENDPOINTS.WISHLIST, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ product_id: productId })
      });
      const data = await res.json().catch(() => null);
      if (data?.success) {
        toast.success('Added to Wishlist!');
        fetchWishlist();
      } else {
        toast.error(data?.error || 'Could not add to wishlist. Please try again.');
      }
    } catch (e) {
      console.error('AddToWishlist API error:', e);
      toast.error('Could not add to wishlist. Please check your connection.');
    }
  };

  const removeFromWishlist = async (productId, options = {}) => {
    if (!token) return false;
    const { silent = false } = options;

    // Optimistically remove from state immediately for snappy UI
    setWishlist(prev => prev.filter(item => (item.id != productId && item.product_id != productId)));

    try {
      const deleteUrl = typeof API_ENDPOINTS.WISHLIST_DELETE === 'function'
        ? API_ENDPOINTS.WISHLIST_DELETE(productId)
        : `${API_ENDPOINTS.WISHLIST}?product_id=${productId}`;
      const res = await fetch(deleteUrl, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await res.json().catch(() => null);
      if (data?.success) {
        if (!silent) {
          toast.success('Removed from Wishlist!');
        }
        fetchWishlist();
        return true;
      } else {
        if (!silent) {
          toast.error(data?.error || 'Could not remove from wishlist. Please try again.');
        }
        fetchWishlist();
        return false;
      }
    } catch (e) {
      console.error('RemoveFromWishlist API error:', e);
      if (!silent) {
        toast.error('Could not remove from wishlist. Please check your connection.');
      }
      fetchWishlist();
      return false;
    }
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
