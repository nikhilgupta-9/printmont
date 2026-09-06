import React from 'react';
import { useLocation } from 'react-router-dom';
import Header from './Header'; 
import ProductPageHeader from './ProductPageHeader'; 
import useHeaderSettings from './useHeaderSettings';

const HeaderManager = () => {
  const location = useLocation();
  const currentPath = location.pathname;
  const { headerType, showCategoryBar, categoryBarMode, isSticky, customBg, customTextColor } = useHeaderSettings();

  // 🧠 Dynamic title mapping for inner pages
  const getPageTitle = () => {
    if (currentPath.startsWith('/cart')) return 'Cart';
    if (currentPath.startsWith('/help-center')) return 'Help Center';
    if (currentPath.startsWith('/product')) return 'Product Details';
    if (currentPath.startsWith('/allproducts')) return 'All Products';
    if (currentPath.startsWith('/contact')) return 'Contact Us';
    if (currentPath.startsWith('/quick-links')) return 'Quick Links';
    if (currentPath.startsWith('/track-order')) return 'Track Order';
    if (currentPath.startsWith('/my-account')) return 'My Account';
    if (currentPath.startsWith('/category')) return 'Categories';
    if (currentPath.startsWith('/support')) return 'Support';
    if (currentPath.includes('policy') || currentPath.includes('terms') || currentPath.includes('privacy') || currentPath.includes('shipping') || currentPath.includes('refund')) return 'Company Policies';
    if (currentPath.startsWith('/bulk')) return 'Bulk Orders';
    if (currentPath.startsWith('/franchise')) return 'Franchise Partner';
    if (currentPath.startsWith('/affiliate')) return 'Affiliate Program';
    if (currentPath.startsWith('/become-a-seller')) return 'Sell on Printmont';
    if (currentPath.startsWith('/about')) return 'About Us';
    if (currentPath.startsWith('/careers')) return 'Careers';
    if (currentPath.startsWith('/faq')) return 'FAQs';
    if (currentPath.startsWith('/security')) return 'Security';
    if (currentPath.startsWith('/blog')) return 'Blog';
    if (currentPath.startsWith('/sitemap')) return 'Site Map';
    if (currentPath.startsWith('/printmont-coin') || currentPath.includes('wallet')) return 'Printmont Coins & Wallet';
    return 'Shop';
  };

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
    />
  );
};

export default HeaderManager;
