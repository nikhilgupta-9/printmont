import React from 'react';
import { useLocation } from 'react-router-dom';
import Header from './Header'; 
import ProductPageHeader from './ProductPageHeader'; 

const PRODUCT_HEADER_PATHS = [
  '/allproducts',
  '/product',
  '/cart',
  '/help-center',
  '/contact',
  '/quick-links',
  'track-order',
  '/my-account'
];

const HeaderManager = () => {
  const location = useLocation();
  const currentPath = location.pathname;

  const useProductHeader = PRODUCT_HEADER_PATHS.some(path =>
    currentPath.startsWith(path)
  );

  // 🧠 Dynamic title mapping
  const getPageTitle = () => {
    if (currentPath.startsWith('/cart')) return 'Cart';
    if (currentPath.startsWith('/help-center')) return 'Help Center';
    if (currentPath.startsWith('/product')) return 'Product Details';
    if (currentPath.startsWith('/allproducts')) return 'All Products';
    if (currentPath.startsWith('/contact')) return 'Contact Us';
    if (currentPath.startsWith('/quick-links')) return 'Quick Links';
    if (currentPath.startsWith('/track-order')) return 'Track Order';
    if (currentPath.startsWith('/my-account')) return 'My Account';
    return 'Shop';
  };

  if (useProductHeader) {
    return <ProductPageHeader pageTitle={getPageTitle()} />;
  }

  return <Header />;
};

export default HeaderManager;
