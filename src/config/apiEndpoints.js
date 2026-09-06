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
export const ROOT_URL = (import.meta.env.VITE_BASE_URL || (isLocalhost ? '/' : LIVE_ASSET_URL)).replace(/\/+$/, '') + '/';

// printmont-backend serves the homepage layout (printmont_db.home_sections) — the same
// data home-layout-manager.php edits.
export const BACKEND_URL = (import.meta.env.VITE_BACKEND_API_URL || (isLocalhost ? '/backend-api' : LIVE_API_URL)).replace(/\/+$/, '');
export const ASSET_URL = (isLocalhost ? LOCAL_ASSET_URL : (import.meta.env.VITE_ASSET_URL || LIVE_ASSET_URL)).replace(/\/+$/, '') + '/';

// NOTE: every path below must name a file that exists in printmont-backend/api.
// The backend's .htaccess rewrites unknown paths to index.php, so a wrong path
// returns the admin login page as HTTP 200 text/html — which then blows up in
// res.json() as "Unexpected token '<'" rather than surfacing as a 404.
export const API_ENDPOINTS = {
  // Authentication
  REGISTER: `${BASE_URL}/user-api.php?action=register`,
  LOGIN: `${BASE_URL}/user-api.php?action=login`,
  PROFILE: `${BASE_URL}/user-api.php?action=profile`,
  UPDATE_PROFILE: `${BASE_URL}/user-api.php?action=update_profile`,
  // Two distinct flows. RESET_PASSWORD is the OTP-based one reached from
  // forgot_password when signed out; CHANGE_PASSWORD is for a signed-in user
  // and authenticates with the current password plus a bearer token.
  RESET_PASSWORD: `${BASE_URL}/user-api.php?action=reset_password`,
  CHANGE_PASSWORD: `${BASE_URL}/user-api.php?action=change_password`,
  FORGOT_PASSWORD: `${BASE_URL}/user-api.php?action=forgot_password`,

  // User Addresses
  GET_ADDRESSES: `${BASE_URL}/user-api.php?action=get_addresses`,
  ADD_ADDRESS: `${BASE_URL}/user-api.php?action=add_address`,
  UPDATE_ADDRESS: `${BASE_URL}/user-api.php?action=update_address`,
  DELETE_ADDRESS: `${BASE_URL}/user-api.php?action=delete_address`,
  SET_DEFAULT_ADDRESS: `${BASE_URL}/user-api.php?action=set_default_address`,

  // Orders — served by user-api.php, scoped to the bearer token's user.
  // Requires an Authorization: Bearer <token> header.
  GET_ORDERS: `${BASE_URL}/user-api.php?action=get_orders`,
  GET_ORDER: (id) => `${BASE_URL}/user-api.php?action=get_order&id=${id}`,
  CREATE_ORDER: `${BASE_URL}/user-api.php?action=create_order`,
  // The id is ignored server-side — orders come from the verified token, so a
  // customer cannot read another customer's orders by changing it.
  // Optional params: status (csv), date_from, date_to, search, page, limit.
  GET_CUSTOMER_ORDERS: (params = {}) => {
    const qs = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') qs.append(k, v);
    });
    const tail = qs.toString();
    return `${BASE_URL}/user-api.php?action=get_orders${tail ? `&${tail}` : ''}`;
  },
  // Public, no token: takes { order_number, contact } where contact is the
  // email or mobile the order was placed with.
  TRACK_ORDER: `${BASE_URL}/user-api.php?action=track_order`,
  // Admin-only actions; not exposed through the public API yet.
  // UPDATE_ORDER_STATUS, UPDATE_PAYMENT_STATUS, GET_DASHBOARD_STATS

  // Products
  PRODUCTS: `${BASE_URL}/product-api.php`,
  PRODUCT_BY_ID: (id) => `${BASE_URL}/product-api.php?id=${id}`,
  PRODUCTS_DEACTIVE: `${BASE_URL}/product-api.php?status=deactive`,
  RELATED_PRODUCTS: (id) => `${BASE_URL}/related_products.php?id=${id}`,
  // product-api.php has no search handler. Callers append "?q=...", so this
  // must stay free of a query string.
  SEARCH: `${BASE_URL}/search-api.php`,

  // Reviews — GET ?product_id= for approved reviews + rating stats, POST to submit
  // a new one (goes into the same pending-approval queue the admin panel manages).
  PRODUCT_REVIEWS: (productId) => `${BASE_URL}/product-reviews-api.php?product_id=${productId}`,
  SUBMIT_REVIEW: `${BASE_URL}/product-reviews-api.php`,
  // Uploads a single customization file (logo/artwork); returns { url } to attach
  // to the cart item's `attributes` — cart-api.php's JSON field can't hold raw bytes.
  CUSTOMIZATION_UPLOAD: `${BASE_URL}/customization-upload-api.php`,

  // Home Product Sections
  HOME_PRODUCT_SECTIONS: (action) => `${BASE_URL}/products/products.php?action=${action}`,
  TOP_RATED: `${BASE_URL}/products/products.php?action=top_rated`,
  TOP_SELECTION: `${BASE_URL}/products/products.php?action=top_selection`,
  BESTSELLER: `${BASE_URL}/products/products.php?action=bestseller`,

  // Categories & Layout
  CATEGORIES: `${BASE_URL}/category-api.php`,
  // Single category lookup by slug (used to resolve a URL slug to its numeric id).
  CATEGORY_BY_SLUG: (slug) => `${BASE_URL}/category_api.php?slug=${encodeURIComponent(slug)}`,
  // Level-2 (sub) categories, used for the plain image-grid section — pass a
  // parentId to scope to one category's children, omit it for the flat sitewide list.
  // NOTE: the backend only returns children here when parentId is a level-1 category.
  // A level-2 parent's own children (level 3) must be fetched via SUBSUBCATEGORIES below.
  SUBCATEGORIES: (parentId) => `${BASE_URL}/category_api.php?action=subcategories${parentId ? `&parent_id=${parentId}` : ''}`,
  // Level-3 categories — children of a level-2 category (e.g. Bamboo Bottles under Eco Drinkware).
  SUBSUBCATEGORIES: (parentId) => `${BASE_URL}/category_api.php?action=subsubcategories&parent_id=${parentId}`,
  // Products belonging to one category-tree node (any level) — see category-products-api.php.
  // The backend resolves category_id -> the right products column (category_id /
  // sub_category_id / sub_sub_category_id) from that category's own level, so a
  // mid-level id (e.g. Eco Drinkware) correctly aggregates products from all its
  // leaf descendants too.
  CATEGORY_PRODUCTS: (categoryId, params = {}) => {
    const qs = new URLSearchParams({ category_id: categoryId, ...params }).toString();
    return `${BASE_URL}/category-products-api.php?${qs}`;
  },
  // Home page category bar (icons, shown_on_home-flagged, split desktop/mobile) vs the
  // persistent inner-page top menu (text-only, already pruned server-side). See menu_api.php.
  HOME_MENU: `${BASE_URL}/menu_api.php?type=home&device=desktop`,
  INNER_MENU: `${BASE_URL}/menu_api.php?type=inner`,
  HOME_LAYOUT: (target = 'desktop') => `${BACKEND_URL}/home-layout-api.php?target=${target}`,

  // Cart
  CART: `${BASE_URL}/cart-api.php`,
  CART_DELETE: (id) => `${BASE_URL}/cart-api.php?item_id=${id}`,

  // Wishlist
  WISHLIST: `${BASE_URL}/wishlist-api.php`,
  WISHLIST_DELETE: (id) => `${BASE_URL}/wishlist-api.php?product_id=${id}`,

  // Banners
  BANNERS: `${BASE_URL}/banner_api.php`,
  BLOG_BANNER: `${BASE_URL}/banner_api.php?page=blog`,

  // Blog — blog-api.php routes on the trailing path segment.
  BLOG_POSTS: `${BASE_URL}/blog-api.php/posts`,
  BLOG_POST_DETAIL: (idOrSlug) => `${BASE_URL}/blog-api.php/posts/${idOrSlug}`,
  BLOG_SINGLE: (slug) => `${BASE_URL}/blog-api.php/posts/${slug}`,
  BLOG_CATEGORIES: `${BASE_URL}/blog-api.php/categories`,
  BLOG_CATEGORY_DETAIL: (id) => `${BASE_URL}/blog-api.php/categories/${id}`,
  BLOG_RECENT: `${BASE_URL}/blog-api.php/recent`,
  BLOG_POPULAR: `${BASE_URL}/blog-api.php/popular`,

  // Careers — career-get-api.php returns a list, or one job when ?id= is set.
  CAREER_GET: `${BASE_URL}/career-get-api.php`,
  CAREER_DETAIL: (id) => `${BASE_URL}/career-get-api.php?id=${id}`,
  CAREER_POST: `${BASE_URL}/career-post-api.php`,

  // Marketing pages driven by page_sections (affiliate, business-solutions)
  PAGE_SECTIONS: (pageKey) => `${BASE_URL}/page-sections-api.php?page=${pageKey}`,

  // CMS & Content (Logo, Header, Footer, etc.)
  ABOUT: `${BASE_URL}/about-api.php`,
  CONTACT: `${BASE_URL}/contact-api.php`,
  FAQ: `${BASE_URL}/faq-api.php`,
  HELP_CENTER: `${BASE_URL}/help-center-api.php`,
  POLICIES: `${BASE_URL}/policies-api.php`,
  SECURITY: `${BASE_URL}/security-api.php`,
  SITEMAP: `${BASE_URL}/sitemap-api.php`,
  HEADER_SETTINGS: `${BASE_URL}/header-settings-api.php`,
  // The backend exposes the public logo endpoint directly under /api.
  LOGO: `${BASE_URL}/logo-api.php`,
};

// Picks the right children-endpoint for a category based on its own level, since
// the backend splits this across two actions (see SUBCATEGORIES/SUBSUBCATEGORIES
// above): level 1 -> subcategories, level 2 -> subsubcategories, level 3+ -> leaf.
export const getChildCategoriesUrl = (parentId, parentLevel) => {
  const level = Number(parentLevel);
  if (level >= 3) return null;
  return level === 1
    ? API_ENDPOINTS.SUBCATEGORIES(parentId)
    : API_ENDPOINTS.SUBSUBCATEGORIES(parentId);
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
