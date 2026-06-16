# Printmont API Documentation

## Base URLs

### Local Development
`http://localhost/printmont-admin/api/`

### Temporary/Staging
`https://mediumvioletred-pelican-783174.hostingersite.com/api/`

### Production
`https://printmont.com/api/`

---

# Authentication

- POST `/user-api.php?action=register`
- POST `/user-api.php?action=login`
- GET `/user-api.php?action=profile`
- POST `/user-api.php?action=update_profile`
- PUT `/router.php?action=change_password`

# User Addresses

- GET `/user-api.php?action=get_addresses`
- POST `/user-api.php?action=add_address`
- POST `/user-api.php?action=update_address`
- POST `/user-api.php?action=delete_address`
- POST `/user-api.php?action=set_default_address`

# Orders

- GET `/user-api.php?action=get_orders`
- GET `/user-api.php?action=get_order&id={id}`
- POST `/user-api.php?action=create_order`
- GET `/router.php?action=get_customer_orders&user_id={id}`
- PUT `/router.php?action=update_order_status&id={id}`
- PUT `/router.php?action=update_payment_status&id={id}`
- GET `/router.php?action=get_dashboard_stats`

# Products

- GET `/product-api.php`
- GET `/product-api.php?id={id}`
- GET `/product-api.php?status=deactive`
- GET `/related_products.php?id={id}`
- GET `/search-api.php`

# Home Product Sections

- GET `/home-product-api.php?action={action}`
- GET `/top-rated-products.php`
- POST `/top-rated-products.php`
- GET `/top-selection-products.php`
- POST `/top-selection-products.php`
- GET `/bestseller-products.php`
- POST `/bestseller-products.php`

# Categories

- GET `/category-api.php`

# Cart

- GET `/cart-api.php`
- POST `/cart-api.php`
- PUT `/cart-api.php`
- DELETE `/cart-api.php?item_id={id}`

# Wishlist

- GET `/wishlist-api.php`
- POST `/wishlist-api.php`
- DELETE `/wishlist-api.php?product_id={id}`

# Banners

- GET `/banner_api.php`
- GET `/blog-page-banner-api.php`

# Blog

- GET `/blog-api.php/posts`
- GET `/blog-api.php/posts/{id|slug}`
- GET `/blog-single.php?slug={slug}`
- GET `/blog-api.php/categories`
- GET `/blog-api.php/categories/{id}`
- GET `/blog-api.php/recent`
- GET `/blog-api.php/popular`

# Careers

- GET `/career-get-api.php`
- GET `/career-get-api.php?id={id}`
- POST `/career-post-api.php`

# CMS & Content

- GET `/about-api.php`
- GET `/contact-api.php`
- GET `/faq-api.php`
- GET `/help-center-api.php`
- GET `/policies-api.php`
- GET `/logo-api.php`

## Environment Variables

```env
VITE_API_URL=http://localhost/printmont-admin/api
# OR
VITE_API_URL=https://mediumvioletred-pelican-783174.hostingersite.com/api
# OR
VITE_API_URL=https://printmont.com/api
```
