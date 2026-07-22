export const BASE_URL = import.meta.env.VITE_API_URL || '/api';

// printmont-backend serves the homepage layout (printmont_db.home_sections) — the same
// data home-layout-manager.php edits. Separate from BASE_URL because the two backends
// are still different hosts/databases during the merge.
export const BACKEND_URL = import.meta.env.VITE_BACKEND_API_URL || '/backend-api';
export const ASSET_URL = import.meta.env.VITE_ASSET_URL || 'https://mediumvioletred-pelican-783174.hostingersite.com/';

export const API_ENDPOINTS = {
  // Authentication
  REGISTER: `${BASE_URL}/auth/register.php`,
  LOGIN: `${BASE_URL}/auth/login.php`,
  PROFILE: `${BASE_URL}/auth/profile.php`,
  UPDATE_PROFILE: `${BASE_URL}/auth/profile.php`,
  CHANGE_PASSWORD: `${BASE_URL}/auth/change_password.php`,

  // User Addresses
  GET_ADDRESSES: `${BASE_URL}/auth/addresses.php?action=get`,
  ADD_ADDRESS: `${BASE_URL}/auth/addresses.php?action=add`,
  UPDATE_ADDRESS: `${BASE_URL}/auth/addresses.php?action=update`,
  DELETE_ADDRESS: `${BASE_URL}/auth/addresses.php?action=delete`,
  SET_DEFAULT_ADDRESS: `${BASE_URL}/auth/addresses.php?action=set_default`,

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
  CART: `${BASE_URL}/settings/cart.php`,
  CART_DELETE: (id) => `${BASE_URL}/settings/cart.php?item_id=${id}`,

  // Wishlist
  WISHLIST: `${BASE_URL}/settings/wishlist.php`,
  WISHLIST_DELETE: (id) => `${BASE_URL}/settings/wishlist.php?product_id=${id}`,

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
  LOGO: `${BASE_URL}/settings/settings.php`,
};
