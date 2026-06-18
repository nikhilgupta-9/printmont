export const BASE_URL = import.meta.env.VITE_API_URL || '/api';
export const ASSET_URL = import.meta.env.VITE_ASSET_URL || 'https://mediumvioletred-pelican-783174.hostingersite.com/';

export const API_ENDPOINTS = {
  // Authentication
  REGISTER: `${BASE_URL}/user-api.php?action=register`,
  LOGIN: `${BASE_URL}/user-api.php?action=login`,
  PROFILE: `${BASE_URL}/user-api.php?action=profile`,
  UPDATE_PROFILE: `${BASE_URL}/user-api.php?action=update_profile`,
  CHANGE_PASSWORD: `${BASE_URL}/router.php?action=change_password`,

  // User Addresses
  GET_ADDRESSES: `${BASE_URL}/user-api.php?action=get_addresses`,
  ADD_ADDRESS: `${BASE_URL}/user-api.php?action=add_address`,
  UPDATE_ADDRESS: `${BASE_URL}/user-api.php?action=update_address`,
  DELETE_ADDRESS: `${BASE_URL}/user-api.php?action=delete_address`,
  SET_DEFAULT_ADDRESS: `${BASE_URL}/user-api.php?action=set_default_address`,

  // Orders
  GET_ORDERS: `${BASE_URL}/user-api.php?action=get_orders`,
  GET_ORDER: (id) => `${BASE_URL}/user-api.php?action=get_order&id=${id}`,
  CREATE_ORDER: `${BASE_URL}/user-api.php?action=create_order`,
  GET_CUSTOMER_ORDERS: (id) => `${BASE_URL}/router.php?action=get_customer_orders&user_id=${id}`,
  UPDATE_ORDER_STATUS: (id) => `${BASE_URL}/router.php?action=update_order_status&id=${id}`,
  UPDATE_PAYMENT_STATUS: (id) => `${BASE_URL}/router.php?action=update_payment_status&id=${id}`,
  GET_DASHBOARD_STATS: `${BASE_URL}/router.php?action=get_dashboard_stats`,

  // Products
  PRODUCTS: `${BASE_URL}/product-api.php`,
  PRODUCT_BY_ID: (id) => `${BASE_URL}/product-api.php?id=${id}`,
  PRODUCTS_DEACTIVE: `${BASE_URL}/product-api.php?status=deactive`,
  RELATED_PRODUCTS: (id) => `${BASE_URL}/related_products.php?id=${id}`,
  SEARCH: `${BASE_URL}/search-api.php`,

  // Home Product Sections
  HOME_PRODUCT_SECTIONS: (action) => `${BASE_URL}/home-product-api.php?action=${action}`,
  TOP_RATED: `${BASE_URL}/top-rated-products.php`,
  TOP_SELECTION: `${BASE_URL}/top-selection-products.php`,
  BESTSELLER: `${BASE_URL}/bestseller-products.php`,

  // Categories
  CATEGORIES: `${BASE_URL}/category-api.php`,

  // Cart
  CART: `${BASE_URL}/cart-api.php`,
  CART_DELETE: (id) => `${BASE_URL}/cart-api.php?item_id=${id}`,

  // Wishlist
  WISHLIST: `${BASE_URL}/wishlist-api.php`,
  WISHLIST_DELETE: (id) => `${BASE_URL}/wishlist-api.php?product_id=${id}`,

  // Banners
  BANNERS: `${BASE_URL}/banner_api.php`,
  BLOG_BANNER: `${BASE_URL}/blog-page-banner-api.php`,

  // Blog
  BLOG_POSTS: `${BASE_URL}/blog-api.php/posts`,
  BLOG_POST_DETAIL: (idOrSlug) => `${BASE_URL}/blog-api.php/posts/${idOrSlug}`,
  BLOG_SINGLE: (slug) => `${BASE_URL}/blog-single.php?slug=${slug}`,
  BLOG_CATEGORIES: `${BASE_URL}/blog-api.php/categories`,
  BLOG_CATEGORY_DETAIL: (id) => `${BASE_URL}/blog-api.php/categories/${id}`,
  BLOG_RECENT: `${BASE_URL}/blog-api.php/recent`,
  BLOG_POPULAR: `${BASE_URL}/blog-api.php/popular`,

  // Careers
  CAREER_GET: `${BASE_URL}/career-get-api.php`,
  CAREER_DETAIL: (id) => `${BASE_URL}/career-get-api.php?id=${id}`,
  CAREER_POST: `${BASE_URL}/career-post-api.php`,

  // CMS & Content (Logo, Header, Footer, etc.)
  ABOUT: `${BASE_URL}/about-api.php`,
  CONTACT: `${BASE_URL}/contact-api.php`,
  FAQ: `${BASE_URL}/faq-api.php`,
  HELP_CENTER: `${BASE_URL}/help-center-api.php`,
  POLICIES: `${BASE_URL}/policies-api.php`,
  LOGO: `${BASE_URL}/logo-api.php`,
};
