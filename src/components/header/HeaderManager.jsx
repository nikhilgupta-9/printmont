import React, { useState, useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import Header from './Header'; 
import ProductPageHeader from './ProductPageHeader'; 
import useHeaderSettings from './useHeaderSettings';
import { useCheckout } from '../../context/CheckoutContext';
import { API_ENDPOINTS } from '../../config/apiEndpoints';

import InnerMobileNavbar from './InnerMobileNavbar';
import BlogHeader from '../pages/blog/BlogHeader';

const HeaderManager = () => {
  const location = useLocation();
  const currentPath = location.pathname;
  const { 
    headerType, 
    showCategoryBar, 
    categoryBarMode, 
    isSticky, 
    customBg, 
    customTextColor,
    homeDesktopDesign,
    homeMobileDesign,
    innerDesktopDesign,
    innerMobileDesign
  } = useHeaderSettings();
  const checkoutContext = useCheckout();
  const cartItems = checkoutContext ? checkoutContext.cartItems : [];
  const cartCount = cartItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
  const [categoryName, setCategoryName] = useState('');

  // Cart / checkout: full inner-page header (+ category menu bar) on desktop,
  // compact title bar on mobile.
  const [isDesktop, setIsDesktop] = useState(() => window.innerWidth >= 992);
  useEffect(() => {
    const onResize = () => setIsDesktop(window.innerWidth >= 992);
    window.addEventListener('resize', onResize);
    return () => window.removeEventListener('resize', onResize);
  }, []);

  // 🧠 Dynamically fetch and resolve category name when on /category/:slug
  useEffect(() => {
    if (currentPath.startsWith('/category/')) {
      const parts = currentPath.split('/').filter(Boolean); // e.g. ['category', 'business-cards-4', ...]
      const slug = parts[1];
      if (!slug || slug === 'all-categories') {
        setCategoryName('All Categories');
        return;
      }

      // Initial clean fallback from slug
      const fallbackTitle = slug
        .replace(/-\d+$/, '') // remove trailing id numbers if any
        .split('-')
        .map(w => w.charAt(0).toUpperCase() + w.slice(1))
        .join(' ');
      setCategoryName(fallbackTitle);

      // Fetch precise real-time category name from backend
      let isMounted = true;
      fetch(API_ENDPOINTS.CATEGORY_BY_SLUG(slug))
        .then(res => res.json())
        .then(data => {
          if (isMounted && data?.success && data.data?.name) {
            setCategoryName(data.data.name);
          }
        })
        .catch(() => {});

      return () => { isMounted = false; };
    } else {
      setCategoryName('');
    }
  }, [currentPath]);

  // 🧠 Dynamic title mapping for inner pages
  const getPageTitle = () => {
    if (currentPath.startsWith('/cart') || currentPath.startsWith('/checkout')) {
      if (checkoutContext?.checkoutStep === 1) return 'Add Delivery Address';
      if (checkoutContext?.checkoutStep === 2) return cartCount > 0 ? `Order Summary (${cartCount})` : 'Order Summary';
      if (checkoutContext?.checkoutStep === 3) return 'Payment';
      return cartCount > 0 ? `My Cart (${cartCount})` : 'My Cart';
    }
    if (currentPath.startsWith('/orders') || currentPath.includes('/orders')) return 'My Orders';
    if (currentPath.startsWith('/wishlist') || currentPath.includes('/wishlist')) return 'My Wishlist';
    if (currentPath.includes('manage-address')) return 'Saved Addresses';
    if (currentPath.includes('pan-gst') || currentPath.includes('pan-card')) return 'PAN & GST Details';
    if (currentPath.includes('change-password')) return 'Change Password';
    if (currentPath.includes('profile-edit')) return 'Personal Information';
    if (currentPath.includes('profile') || currentPath.startsWith('/user') || currentPath.startsWith('/my-account') || currentPath.startsWith('/account-setting')) return 'My Account';
    if (currentPath.startsWith('/help-center') || currentPath === '/help' || currentPath.startsWith('/help/')) return 'Help Center';
    if (currentPath.startsWith('/product')) return 'Product Details';
    if (currentPath.startsWith('/allproducts')) return 'All Products';
    if (currentPath.startsWith('/contact')) return 'Contact Us';
    if (currentPath.startsWith('/quick-links')) return 'Quick Links';
    if (currentPath.startsWith('/track-order')) return 'Track Order';
    if (currentPath.startsWith('/category')) {
      if (currentPath.includes('/reviews')) {
        return categoryName ? `${categoryName} Reviews` : 'Customer Reviews';
      }
      return categoryName || 'Categories';
    }
    if (currentPath.startsWith('/support')) return 'Support';
    if (currentPath.includes('privacy')) return 'Privacy Policy';
    if (currentPath.includes('terms-of-use')) return 'Terms of Use';
    if (currentPath.includes('terms') || currentPath.includes('terms-and-conditions')) return 'Terms and Conditions';
    if (currentPath.includes('shipping')) return 'Shipping Policy';
    if (currentPath.includes('refund') || currentPath.includes('return')) return 'Return & Refund Policy';
    if (currentPath.startsWith('/security')) return 'Security Information';
    if (currentPath.includes('policy')) return 'Company Policies & Terms';
    if (currentPath.startsWith('/bulk')) return 'Bulk Orders';
    if (currentPath.startsWith('/business-solutions')) return 'Business Solutions';
    if (currentPath.startsWith('/franchise')) return 'Franchise Partner';
    if (currentPath.startsWith('/advertising')) return 'Advertising';
    if (currentPath.startsWith('/notification-preference')) return 'Notifications';
    if (currentPath.startsWith('/app-download') || currentPath === '/app' || currentPath === '/mobile-app') return 'Printmont App';
    if (currentPath.startsWith('/affiliate')) return 'Affiliate Program';
    if (currentPath.startsWith('/become-a-seller')) return 'Sell on Printmont';
    if (currentPath.startsWith('/about')) return 'About Us';
    if (currentPath.startsWith('/careers')) return 'Careers';
    if (currentPath.startsWith('/faq')) return 'FAQs';
    if (currentPath.startsWith('/reviews')) return 'Reviews & Ratings';
    if (currentPath === '/blog') return 'Blog';
    if (currentPath.startsWith('/blog/')) return 'Blog Details';
    if (currentPath.startsWith('/login')) return 'Account Login';
    if (currentPath.startsWith('/signup') || currentPath.startsWith('/register')) return 'Account Signup';
    if (currentPath.startsWith('/forgot-password')) return 'Forgot Password';
    if (currentPath.startsWith('/sitemap')) return 'Site Map';
    if (currentPath.includes('wallet')) return 'My Wallet';
    if (currentPath.includes('giftcard')) return 'Gift Vouchers';
    if (currentPath.startsWith('/printmont-coin')) return 'Printmont Coins';

    // Check dynamic /:username account routes (e.g. /nikhil/profile, /nikhil, etc.)
    const segments = currentPath.split('/').filter(Boolean);
    if (segments.length === 1 && segments[0] !== 'allproducts' && segments[0] !== 'cart' && segments[0] !== 'orders') {
      return 'My Account';
    }

    return 'Shop';
  };

  const isAuthPage = currentPath.startsWith('/login') || 
                     currentPath.startsWith('/signup') || 
                     currentPath.startsWith('/register') || 
                     currentPath.startsWith('/forgot-password');

  const isCartPage = currentPath.startsWith('/cart') || currentPath.startsWith('/checkout');

  // If on Cart or Add Delivery Address / checkout steps:
  if (isCartPage && isDesktop) {
    return (
      <ProductPageHeader
        pageTitle={getPageTitle()}
        showCategories={showCategoryBar}
        showImages={categoryBarMode === 'with_images'}
        isSticky={isSticky}
        bg={customBg}
        color={customTextColor}
        desktopDesign={innerDesktopDesign}
        mobileDesign={innerMobileDesign}
      />
    );
  }

  if (isCartPage) {
    return (
      <InnerMobileNavbar
        pageTitle={getPageTitle()}
        showBackButton={true}
        isSticky={true}
        bg={customBg || "#085885"}
        textColor={customTextColor || "#ffffff"}
      />
    );
  }

  // If on auth pages, show standard clean header (without 2nd category bar) on desktop and standard mobile header on mobile
  if (isAuthPage) {
    return (
      <ProductPageHeader 
        pageTitle={getPageTitle()}
        showCategories={false}
        showImages={false}
        isSticky={isSticky}
        bg={customBg}
        color={customTextColor}
        desktopDesign={innerDesktopDesign}
        mobileDesign={innerMobileDesign}
      />
    );
  }

  // Dedicated Blog Header Module (Matches PDF wireframe)
  const isBlogPage = currentPath === '/blog' || currentPath.startsWith('/blog/');
  if (isBlogPage) {
    return <BlogHeader />;
  }

  // If configured as 'none', do not render any header
  if (headerType === 'none') {
    return null;
  }

  // If headerType is 'full' (or fallback for home)
  if (headerType === 'full') {
    return (
      <Header 
        showCategories={showCategoryBar}
        showImages={categoryBarMode === 'with_images'}
        isSticky={isSticky}
        bg={customBg}
        color={customTextColor}
        desktopDesign={homeDesktopDesign}
        mobileDesign={homeMobileDesign}
      />
    );
  }

  // Otherwise render ProductPageHeader (inner page header)
  return (
    <ProductPageHeader 
      pageTitle={getPageTitle()}
      showCategories={showCategoryBar}
      showImages={categoryBarMode === 'with_images'}
      isSticky={isSticky}
      bg={customBg}
      color={customTextColor}
      desktopDesign={innerDesktopDesign}
      mobileDesign={innerMobileDesign}
    />
  );
};

export default HeaderManager;
