const isLocalhost = typeof window !== 'undefined' && (
  window.location.hostname === 'localhost' ||
  window.location.hostname === '127.0.0.1' ||
  window.location.hostname.endsWith('.local')
);

const LOCAL_API_URL = '/api';
const LIVE_API_URL = 'https://mediumvioletred-pelican-783174.hostingersite.com/api';
const LOCAL_ASSET_URL = 'http://localhost/printmont/printmont-backend/';
const LIVE_ASSET_URL = 'https://mediumvioletred-pelican-783174.hostingersite.com/';

export const BASE_URL = (import.meta.env.VITE_API_URL || (isLocalhost ? LOCAL_API_URL : LIVE_API_URL)).replace(/\/+$/, '');

// printmont-backend serves the homepage layout (printmont_db.home_sections) — the same
// data home-layout-manager.php edits.
export const BACKEND_URL = (import.meta.env.VITE_BACKEND_API_URL || (isLocalhost ? '/backend-api' : LIVE_API_URL)).replace(/\/+$/, '');
export const ASSET_URL = (import.meta.env.VITE_ASSET_URL || (isLocalhost ? LOCAL_ASSET_URL : LIVE_ASSET_URL)).replace(/\/+$/, '') + '/';
export const API_ENDPOINTS = {
  // Authentication
  REGISTER: `${BASE_URL}/user-api.php?action=register`,
  LOGIN: `${BASE_URL}/user-api.php?action=login`,
  PROFILE: `${BASE_URL}/user-api.php?action=profile`,
  UPDATE_PROFILE: `${BASE_URL}/user-api.php?action=update_profile`,
  CHANGE_PASSWORD: `${BASE_URL}/auth/change_password.php`,
  FORGOT_PASSWORD: `${BASE_URL}/user-api.php?action=forgot_password`,

  // User Addresses
  GET_ADDRESSES: `${BASE_URL}/user-api.php?action=get_addresses`,
  ADD_ADDRESS: `${BASE_URL}/user-api.php?action=add_address`,
  UPDATE_ADDRESS: `${BASE_URL}/user-api.php?action=update_address`,
  DELETE_ADDRESS: `${BASE_URL}/user-api.php?action=delete_address`,
  SET_DEFAULT_ADDRESS: `${BASE_URL}/user-api.php?action=set_default_address`,

  // Orders
  GET_ORDERS: `${BASE_URL}/orders/orders.php?action=list`,
  GET_ORDER: (id) => `${BASE_URL}/orders/orders.php?action=detail&id=${id}`,
  CREATE_ORDER: `${BASE_URL}/orders/orders.php?action=create`,
  GET_CUSTOMER_ORDERS: (id) => `${BASE_URL}/orders/orders.php?action=customer_orders&user_id=${id}`,
  UPDATE_ORDER_STATUS: (id) => `${BASE_URL}/orders/orders.php?action=update_status&id=${id}`,
  UPDATE_PAYMENT_STATUS: (id) => `${BASE_URL}/orders/orders.php?action=update_payment&id=${id}`,
  GET_DASHBOARD_STATS: `${BASE_URL}/orders/dashboard.php`,

  // Products
  PRODUCTS: `${BASE_URL}/product-api.php`,
  PRODUCT_BY_ID: (id) => `${BASE_URL}/product-api.php?id=${id}`,
  PRODUCTS_DEACTIVE: `${BASE_URL}/product-api.php?status=deactive`,
  RELATED_PRODUCTS: (id) => `${BASE_URL}/product-api.php?id=${id}`,
  SEARCH: `${BASE_URL}/product-api.php?action=search`,

  // Home Product Sections
  HOME_PRODUCT_SECTIONS: (action) => `${BASE_URL}/products/products.php?action=${action}`,
  TOP_RATED: `${BASE_URL}/products/products.php?action=top_rated`,
  TOP_SELECTION: `${BASE_URL}/products/products.php?action=top_selection`,
  BESTSELLER: `${BASE_URL}/products/products.php?action=bestseller`,

  // Categories & Layout
  CATEGORIES: `${BASE_URL}/category-api.php`,
  HOME_LAYOUT: (target = 'desktop') => `${BACKEND_URL}/home-layout-api.php?target=${target}`,

  // Cart
  CART: `${BASE_URL}/cart-api.php`,
  CART_DELETE: (id) => `${BASE_URL}/cart-api.php?item_id=${id}`,

  // Wishlist
  WISHLIST: `${BASE_URL}/wishlist-api.php`,
  WISHLIST_DELETE: (id) => `${BASE_URL}/wishlist-api.php?product_id=${id}`,

  // Banners
  BANNERS: `${BASE_URL}/banners/banners.php`,
  BLOG_BANNER: `${BASE_URL}/banners/banners.php?page=blog`,

  // Blog
  BLOG_POSTS: `${BASE_URL}/blog/posts.php/posts`,
  BLOG_POST_DETAIL: (idOrSlug) => `${BASE_URL}/blog/posts.php/posts/${idOrSlug}`,
  BLOG_SINGLE: (slug) => `${BASE_URL}/blog/posts.php/posts/${slug}`,
  BLOG_CATEGORIES: `${BASE_URL}/blog/categories.php`,
  BLOG_CATEGORY_DETAIL: (id) => `${BASE_URL}/blog/posts.php/categories/${id}`,
  BLOG_RECENT: `${BASE_URL}/blog/posts.php/recent`,
  BLOG_POPULAR: `${BASE_URL}/blog/posts.php/popular`,

  // Careers
  CAREER_GET: `${BASE_URL}/pages/careers.php?action=list`,
  CAREER_DETAIL: (id) => `${BASE_URL}/pages/careers.php?action=detail&id=${id}`,
  CAREER_POST: `${BASE_URL}/pages/careers.php?action=apply`,

  // CMS & Content (Logo, Header, Footer, etc.)
  ABOUT: `${BASE_URL}/pages/about.php`,
  CONTACT: `${BASE_URL}/pages/contact.php`,
  FAQ: `${BASE_URL}/pages/faq.php`,
  HELP_CENTER: `${BASE_URL}/pages/help-center.php`,
  POLICIES: `${BASE_URL}/pages/policies.php`,
  // The backend exposes the public logo endpoint directly under /api.
  LOGO: `${BASE_URL}/logo-api.php`,
};

export const resolveImageUrl = (imagePath) => {
  if (!imagePath) return '/default-img.jpg';
  if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
    return imagePath;
  }

  const normPath = imagePath.startsWith('/') ? imagePath : '/' + imagePath;
  const localAssetPrefixes = [
    '/electro/', '/men_shirt/', '/women-dress/', '/girl-product-img/',
    '/banners/', '/card/', '/crouselimages/', '/first-carousel-img/',
    '/kid-dress/', '/section-img/', '/sq/', '/sqtopdeals/', '/top-deals/',
    '/bg/', '/blog/', '/icons/', '/default-img.jpg', '/Asured.png', '/PrintLogo.png'
  ];

  if (localAssetPrefixes.some(prefix => normPath.startsWith(prefix))) {
    return normPath;
  }

  const cleanPath = imagePath.startsWith('/') ? imagePath.substring(1) : imagePath;
  return `${ASSET_URL}${cleanPath}`;
};
