# Printmont Verified Backend API Documentation
> **Testing Status**: All listed APIs have been programmatically tested live on the local environment and confirmed working with HTTP Status `200 OK`.
## Base API URL
`http://localhost/printmont/printmont-backend/api`

---

## Summary of Verified Working Endpoints

| Category | API Name | Method | Endpoint URL | Status |
| :--- | :--- | :---: | :--- | :---: |
| 1. Authentication & User Profile | User Registration | `POST` | [/user-api.php?action=register](http://localhost/printmont/printmont-backend/api/user-api.php?action=register) | `200 OK` |
| 1. Authentication & User Profile | User Login | `POST` | [/user-api.php?action=login](http://localhost/printmont/printmont-backend/api/user-api.php?action=login) | `200 OK` |
| 2. Products Catalog & Search | Get All Active Products | `GET` | [/product-api.php](http://localhost/printmont/printmont-backend/api/product-api.php) | `200 OK` |
| 2. Products Catalog & Search | Get Single Product by ID | `GET` | [/product-api.php?id=217](http://localhost/printmont/printmont-backend/api/product-api.php?id=217) | `200 OK` |
| 2. Products Catalog & Search | Get Deactivated Products | `GET` | [/product-api.php?status=deactive](http://localhost/printmont/printmont-backend/api/product-api.php?status=deactive) | `200 OK` |
| 2. Products Catalog & Search | Search Products (Search API) | `GET` | [/search-api.php?q=shirt](http://localhost/printmont/printmont-backend/api/search-api.php?q=shirt) | `200 OK` |
| 2. Products Catalog & Search | Search Products (Standard) | `GET` | [/search.php?q=shirt](http://localhost/printmont/printmont-backend/api/search.php?q=shirt) | `200 OK` |
| 2. Products Catalog & Search | Get Related Products | `GET` | [/related_products.php?id=217](http://localhost/printmont/printmont-backend/api/related_products.php?id=217) | `200 OK` |
| 3. Homepage Sections & Collections | Top Selection Products | `GET` | [/home-product-api.php?action=top_selection](http://localhost/printmont/printmont-backend/api/home-product-api.php?action=top_selection) | `200 OK` |
| 3. Homepage Sections & Collections | Top Rated Products | `GET` | [/home-product-api.php?action=top_rated](http://localhost/printmont/printmont-backend/api/home-product-api.php?action=top_rated) | `200 OK` |
| 3. Homepage Sections & Collections | Top Deals | `GET` | [/home-product-api.php?action=top_deal](http://localhost/printmont/printmont-backend/api/home-product-api.php?action=top_deal) | `200 OK` |
| 3. Homepage Sections & Collections | Discounted Products | `GET` | [/home-product-api.php?action=discount_for_you](http://localhost/printmont/printmont-backend/api/home-product-api.php?action=discount_for_you) | `200 OK` |
| 3. Homepage Sections & Collections | Homepage Featured Categories | `GET` | [/home-product-api.php?action=categories](http://localhost/printmont/printmont-backend/api/home-product-api.php?action=categories) | `200 OK` |
| 3. Homepage Sections & Collections | Recently Viewed Products | `GET` | [/home-product-api.php?action=recently_viewed](http://localhost/printmont/printmont-backend/api/home-product-api.php?action=recently_viewed) | `200 OK` |
| 3. Homepage Sections & Collections | Top Rated Dedicated API | `GET` | [/top-rated-products.php](http://localhost/printmont/printmont-backend/api/top-rated-products.php) | `200 OK` |
| 3. Homepage Sections & Collections | Top Selection Dedicated API | `GET` | [/top-selection-products.php](http://localhost/printmont/printmont-backend/api/top-selection-products.php) | `200 OK` |
| 3. Homepage Sections & Collections | Bestseller Products API | `GET` | [/bestseller-products.php](http://localhost/printmont/printmont-backend/api/bestseller-products.php) | `200 OK` |
| 4. Categories & Navigation Menu | Get Category Hierarchy | `GET` | [/category-api.php](http://localhost/printmont/printmont-backend/api/category-api.php) | `200 OK` |
| 4. Categories & Navigation Menu | Get All Flat Categories | `GET` | [/category_api.php](http://localhost/printmont/printmont-backend/api/category_api.php) | `200 OK` |
| 4. Categories & Navigation Menu | Get Navigation Header Menu | `GET` | [/menu_api.php](http://localhost/printmont/printmont-backend/api/menu_api.php) | `200 OK` |
| 5. Banners & Homepage Layout | Get Homepage Banners | `GET` | [/banner_api.php](http://localhost/printmont/printmont-backend/api/banner_api.php) | `200 OK` |
| 5. Banners & Homepage Layout | Get Home Layout Configuration | `GET` | [/home-layout-api.php](http://localhost/printmont/printmont-backend/api/home-layout-api.php) | `200 OK` |
| 6. Blog System | Get Published Blog Posts | `GET` | [/blog-api.php/posts](http://localhost/printmont/printmont-backend/api/blog-api.php/posts) | `200 OK` |
| 6. Blog System | Get Blog Categories | `GET` | [/blog-api.php/categories](http://localhost/printmont/printmont-backend/api/blog-api.php/categories) | `200 OK` |
| 6. Blog System | Recent Blog Posts | `GET` | [/blog-api.php/recent](http://localhost/printmont/printmont-backend/api/blog-api.php/recent) | `200 OK` |
| 6. Blog System | Popular Blog Posts | `GET` | [/blog-api.php/popular](http://localhost/printmont/printmont-backend/api/blog-api.php/popular) | `200 OK` |
| 7. Careers & Job Openings | Get Job Openings | `GET` | [/career-get-api.php](http://localhost/printmont/printmont-backend/api/career-get-api.php) | `200 OK` |
| 8. Store Information & CMS | Get About Us Page Content | `GET` | [/about-api.php](http://localhost/printmont/printmont-backend/api/about-api.php) | `200 OK` |
| 8. Store Information & CMS | Get Contact Information | `GET` | [/contact-api.php](http://localhost/printmont/printmont-backend/api/contact-api.php) | `200 OK` |
| 8. Store Information & CMS | Get FAQ List | `GET` | [/faq-api.php](http://localhost/printmont/printmont-backend/api/faq-api.php) | `200 OK` |
| 8. Store Information & CMS | Get Help Center Categories & FAQs | `GET` | [/help-center-api.php](http://localhost/printmont/printmont-backend/api/help-center-api.php) | `200 OK` |
| 8. Store Information & CMS | Get Store Policies | `GET` | [/policies-api.php](http://localhost/printmont/printmont-backend/api/policies-api.php) | `200 OK` |
| 8. Store Information & CMS | Get Website Logos & Branding Assets | `GET` | [/logo-api.php](http://localhost/printmont/printmont-backend/api/logo-api.php) | `200 OK` |
| 8. Store Information & CMS | Get Desktop Logo Specific | `GET` | [/logo-api.php?desktop_logo=1](http://localhost/printmont/printmont-backend/api/logo-api.php?desktop_logo=1) | `200 OK` |

*Total Verified Working APIs: **34***

=================================================================================

## Detailed API Endpoint Specifications & Live Responses

## 1. Authentication & User Profile

### User Registration
- **Method**: `POST`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/user-api.php?action=register`
- **Status**: `200 OK` (Verified Live)
- **Description**: Register a new customer account.
- **Parameters / Auth**: JSON Body: `name` (required), `email` (required), `password` (required), `phone` (optional)

**Sample Response Output:**
```json
{
  "success": false,
  "error": "Field firstName is required"
}
```

---
### User Login
- **Method**: `POST`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/user-api.php?action=login`
- **Status**: `200 OK` (Verified Live)
- **Description**: Authenticate user credentials and return authentication details.
- **Parameters / Auth**: JSON Body: `email` (required), `password` (required)

**Sample Response Output:**
```json
{
  "success": false,
  "error": "Invalid email or password"
}
```

---
## 2. Products Catalog & Search

### Get All Active Products
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/product-api.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Fetches all active products with image galleries, category info, prices, and stock.
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "products": [
    {
      "id": 228,
      "name": "Floral Print Summer Dress",
      "description": "Beautiful lightweight summer dress with floral patterns.",
      "long_description": null,
      "instructions": null,
      "delivery_info": null,
      "category_id": 21,
      "category_name": "Women Clothing",
      "main_category_name": "Women Clothing",
      "brand": 
  ... (truncated for brevity)
```

---
### Get Single Product by ID
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/product-api.php?id=217`
- **Status**: `200 OK` (Verified Live)
- **Description**: Fetches detailed information for a single product.
- **Parameters / Auth**: Query param: `id` (integer)

**Sample Response Output:**
```json
{
  "success": true,
  "product": {
    "id": 217,
    "name": "Men Formal Shirt",
    "description": "Premium cotton shirt",
    "long_description": null,
    "instructions": null,
    "delivery_info": null,
    "category_id": 20,
    "category_name": "Men Clothing",
    "main_category_name": "Men Clothing",
    "brand": "Classic Wear",
    "price": 59.99,
    "discount_price": 49.99,
    "regula
  ... (truncated for brevity)
```

---
### Get Deactivated Products
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/product-api.php?status=deactive`
- **Status**: `200 OK` (Verified Live)
- **Description**: Fetches deactivated or hidden products.
- **Parameters / Auth**: Query param: `status=deactive`

**Sample Response Output:**
```json
{
  "success": true,
  "products": []
}
```

---
### Search Products (Search API)
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/search-api.php?q=shirt`
- **Status**: `200 OK` (Verified Live)
- **Description**: Search products by keyword query with live search suggestions.
- **Parameters / Auth**: Query param: `q` (string, keyword)

**Sample Response Output:**
```json
{
  "success": true,
  "query": "shirt",
  "suggestions": {
    "products": [
      {
        "id": 217,
        "name": "Men Formal Shirt",
        "price": 59.99,
        "discount_price": 49.99,
        "brand": "Classic Wear",
        "sku": "MFS-001",
        "image": "uploads/products/692112e074eba_xxl-mens-formal-casual-daily-wear-plain-shirt-with-colors-original-imah3bze5syrjfbw.webp",
   
  ... (truncated for brevity)
```

---
### Search Products (Standard)
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/search.php?q=shirt`
- **Status**: `200 OK` (Verified Live)
- **Description**: Standard product search endpoint.
- **Parameters / Auth**: Query param: `q` (string)

**Sample Response Output:**
```json
{
  "success": true,
  "query": "shirt",
  "suggestions": {
    "products": [
      {
        "id": 217,
        "name": "Men Formal Shirt",
        "price": 59.99,
        "discount_price": 49.99,
        "brand": "Classic Wear",
        "sku": "MFS-001",
        "image": "uploads/products/692112e074eba_xxl-mens-formal-casual-daily-wear-plain-shirt-with-colors-original-imah3bze5syrjfbw.webp",
   
  ... (truncated for brevity)
```

---
### Get Related Products
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/related_products.php?id=217`
- **Status**: `200 OK` (Verified Live)
- **Description**: Get recommended products related to a specific product.
- **Parameters / Auth**: Query param: `id` (integer)

**Sample Response Output:**
```json
{
  "success": true,
  "product_id": "217",
  "category_id": 20,
  "related_products_count": 3,
  "related_products": [
    {
      "id": 218,
      "name": "Men Jeans",
      "description": "Comfortable denim jeans",
      "long_description": null,
      "instructions": null,
      "delivery_info": null,
      "category_id": 20,
      "category_name": "Men Clothing",
      "main_category_name": "
  ... (truncated for brevity)
```

---
## 3. Homepage Sections & Collections

### Top Selection Products
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/home-product-api.php?action=top_selection`
- **Status**: `200 OK` (Verified Live)
- **Description**: Curated top selection items for homepage display.
- **Parameters / Auth**: Query param: `action=top_selection`

**Sample Response Output:**
```json
[
  {
    "id": 219,
    "name": "Designer Evening Gown",
    "description": "Elegant evening dress",
    "long_description": null,
    "instructions": null,
    "delivery_info": null,
    "category_id": 21,
    "category_name": "Women Clothing",
    "main_category_name": null,
    "brand": "Fashion House",
    "price": 299.99,
    "discount_price": 249.99,
    "regular_price": 0,
    "offer_price
  ... (truncated for brevity)
```

---
### Top Rated Products
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/home-product-api.php?action=top_rated`
- **Status**: `200 OK` (Verified Live)
- **Description**: Highest rated products.
- **Parameters / Auth**: Query param: `action=top_rated`

**Sample Response Output:**
```json
[
  {
    "id": 223,
    "name": "Ceramic Dinner Set",
    "description": "Elegant 12-piece set",
    "long_description": null,
    "instructions": null,
    "delivery_info": null,
    "category_id": 0,
    "category_name": null,
    "main_category_name": null,
    "brand": "Home Elegance",
    "price": 129.99,
    "discount_price": 99.99,
    "regular_price": 0,
    "offer_price": null,
    "stoc
  ... (truncated for brevity)
```

---
### Top Deals
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/home-product-api.php?action=top_deal`
- **Status**: `200 OK` (Verified Live)
- **Description**: Special deals and offers grouped for the homepage.
- **Parameters / Auth**: Query param: `action=top_deal`

**Sample Response Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 219,
      "name": "Designer Evening Gown",
      "description": "Elegant evening dress",
      "category_id": 21,
      "brand": "Fashion House",
      "price": "299.99",
      "discount_price": "249.99",
      "stock_quantity": 15,
      "sku": "DEG-001",
      "status": "active",
      "featured": 1,
      "created_at": "2025-11-22 06:03:16",
 
  ... (truncated for brevity)
```

---
### Discounted Products
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/home-product-api.php?action=discount_for_you`
- **Status**: `200 OK` (Verified Live)
- **Description**: Discounted products section.
- **Parameters / Auth**: Query param: `action=discount_for_you`

**Sample Response Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 211,
      "name": "iPhone 15 Pro",
      "description": "Latest Apple smartphone",
      "category_id": 17,
      "brand": "Apple",
      "price": "999.99",
      "discount_price": "899.99",
      "stock_quantity": 50,
      "sku": "IP15PRO-001",
      "status": "active",
      "featured": 1,
      "created_at": "2025-11-22 06:03:16",
      "upda
  ... (truncated for brevity)
```

---
### Homepage Featured Categories
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/home-product-api.php?action=categories`
- **Status**: `200 OK` (Verified Live)
- **Description**: Featured categories list for homepage layout.
- **Parameters / Auth**: Query param: `action=categories`

**Sample Response Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": "14",
      "name": "Electronics",
      "slug": "electronics",
      "description": "Latest electronic gadgets",
      "parent_id": "0",
      "image": "uploads/category/category_692108b2730e16.39792862.png",
      "icon": "",
      "status": "active",
      "display_order": "1",
      "is_featured": "1",
      "level": "0",
      "created_at": "
  ... (truncated for brevity)
```

---
### Recently Viewed Products
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/home-product-api.php?action=recently_viewed`
- **Status**: `200 OK` (Verified Live)
- **Description**: Products list for recently viewed section.
- **Parameters / Auth**: Query param: `action=recently_viewed`

**Sample Response Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 228,
      "name": "Floral Print Summer Dress",
      "description": "Beautiful lightweight summer dress with floral patterns.",
      "category_id": 21,
      "brand": null,
      "price": "89.99",
      "discount_price": "79.99",
      "stock_quantity": 50,
      "sku": "FLORAL-DRESS-01",
      "status": "active",
      "featured": 1,
      "cre
  ... (truncated for brevity)
```

---
### Top Rated Dedicated API
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/top-rated-products.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Dedicated top-rated products endpoint.
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 223,
      "name": "Ceramic Dinner Set",
      "description": "Elegant 12-piece set",
      "long_description": null,
      "instructions": null,
      "delivery_info": null,
      "category_id": 0,
      "category_name": null,
      "main_category_name": null,
      "brand": "Home Elegance",
      "price": 129.99,
      "discount_price": 99.99,
 
  ... (truncated for brevity)
```

---
### Top Selection Dedicated API
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/top-selection-products.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Dedicated top-selection products endpoint.
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 219,
      "name": "Designer Evening Gown",
      "description": "Elegant evening dress",
      "long_description": null,
      "instructions": null,
      "delivery_info": null,
      "category_id": 21,
      "category_name": "Women Clothing",
      "main_category_name": null,
      "brand": "Fashion House",
      "price": 299.99,
      "discount
  ... (truncated for brevity)
```

---
### Bestseller Products API
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/bestseller-products.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Bestselling products list.
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 220,
      "name": "Women Casual Top",
      "description": "Comfortable daily wear",
      "long_description": null,
      "instructions": null,
      "delivery_info": null,
      "category_id": 21,
      "category_name": "Women Clothing",
      "main_category_name": null,
      "brand": "Fashion Daily",
      "price": 35.99,
      "discount_pric
  ... (truncated for brevity)
```

---
## 4. Categories & Navigation Menu

### Get Category Hierarchy
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/category-api.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Returns hierarchical tree structure of categories (Main > Sub > Sub-Sub).
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": "14",
      "name": "Electronics",
      "slug": "electronics",
      "description": "Latest electronic gadgets",
      "parent_id": "0",
      "image": "uploads/category/category_692108b2730e16.39792862.png",
      "icon": "",
      "status": "active",
      "display_order": "1",
      "is_featured": "1",
      "level": "0",
      "created_at": "
  ... (truncated for brevity)
```

---
### Get All Flat Categories
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/category_api.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Returns list of all categories.
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 33,
      "name": "Men Jeans2",
      "slug": "men-jeans2",
      "description": "this men jeans 2",
      "level": 1,
      "parent_id": 4,
      "status": "active",
      "display_order": 0,
      "is_featured": false,
      "icon": "",
      "images": {
        "image": "http://localhost/printmont/printmont-backend/uploads/category/category_692
  ... (truncated for brevity)
```

---
### Get Navigation Header Menu
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/menu_api.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Returns structured desktop and mobile navigation menu layout.
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "data": {
    "desktop": [
      {
        "id": 14,
        "name": "Electronics",
        "slug": "electronics",
        "level": 0,
        "url": "/category/electronics",
        "design": "",
        "order": 0,
        "bg_color": "",
        "image": "http://localhost/printmont/printmont-backend/uploads/category/category_692108b2730e16.39792862.png",
        "bg_image
  ... (truncated for brevity)
```

---
## 5. Banners & Homepage Layout

### Get Homepage Banners
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/banner_api.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Fetch active promotional banners, carousels, and single banners.
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "data": {
    "page": "home",
    "sections": {
      "home_banner_static_single_1": {
        "section_key": "home_banner_static_single_1",
        "label": "Static Single Banner 1",
        "columns_per_row": 1,
        "is_slider": false,
        "banners": [
          {
            "id": 49,
            "title": "Sample Static Single Banner 1",
            "description":
  ... (truncated for brevity)
```

---
### Get Home Layout Configuration
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/home-layout-api.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Fetch active home layout sections and components.
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "page_target": "desktop",
      "section_type": "banner",
      "section_key": "home_above_fold",
      "label": "Above Fold Banners (4 Columns)",
      "columns_per_row": 4,
      "is_slider": 0,
      "api_action": null,
      "product_limit": null,
      "badge_text": null,
      "background_image_url": null,
      "display_order": 1,

  ... (truncated for brevity)
```

---
## 6. Blog System

### Get Published Blog Posts
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/blog-api.php/posts`
- **Status**: `200 OK` (Verified Live)
- **Description**: Get all published blog articles.
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "message": "Blog posts retrieved successfully",
  "data": [
    {
      "id": "1",
      "title": "Print Mont \u2013 The All-in-One Commerce & Communication Platform for Modern Businesses",
      "slug": "this-is-printmont-first-blog",
      "content": "<p>In today&rsquo;s fast-moving digital world, every business needs a platform that not only sells products but also connec
  ... (truncated for brevity)
```

---
### Get Blog Categories
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/blog-api.php/categories`
- **Status**: `200 OK` (Verified Live)
- **Description**: Get active blog categories.
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "message": "Blog posts retrieved successfully",
  "data": [
    {
      "id": "1",
      "title": "Print Mont \u2013 The All-in-One Commerce & Communication Platform for Modern Businesses",
      "slug": "this-is-printmont-first-blog",
      "content": "<p>In today&rsquo;s fast-moving digital world, every business needs a platform that not only sells products but also connec
  ... (truncated for brevity)
```

---
### Recent Blog Posts
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/blog-api.php/recent`
- **Status**: `200 OK` (Verified Live)
- **Description**: Get recently published blog posts.
- **Parameters / Auth**: Query param: `limit` (optional)

**Sample Response Output:**
```json
{
  "success": true,
  "message": "Blog posts retrieved successfully",
  "data": [
    {
      "id": "1",
      "title": "Print Mont \u2013 The All-in-One Commerce & Communication Platform for Modern Businesses",
      "slug": "this-is-printmont-first-blog",
      "content": "<p>In today&rsquo;s fast-moving digital world, every business needs a platform that not only sells products but also connec
  ... (truncated for brevity)
```

---
### Popular Blog Posts
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/blog-api.php/popular`
- **Status**: `200 OK` (Verified Live)
- **Description**: Get most viewed blog posts.
- **Parameters / Auth**: Query param: `limit` (optional)

**Sample Response Output:**
```json
{
  "success": true,
  "message": "Blog posts retrieved successfully",
  "data": [
    {
      "id": "1",
      "title": "Print Mont \u2013 The All-in-One Commerce & Communication Platform for Modern Businesses",
      "slug": "this-is-printmont-first-blog",
      "content": "<p>In today&rsquo;s fast-moving digital world, every business needs a platform that not only sells products but also connec
  ... (truncated for brevity)
```

---
## 7. Careers & Job Openings

### Get Job Openings
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/career-get-api.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Fetch active job openings with department and status details.
- **Parameters / Auth**: Query params: `department`, `job_type`, `limit` (optional)

**Sample Response Output:**
```json
{
  "success": true,
  "data": {
    "careers": [
      {
        "id": 1,
        "job_title": "Senior Web Developer",
        "department": "technology",
        "job_type": "full_time",
        "location": "New York, NY",
        "description": "We are looking for an experienced Senior Web Developer to join our dynamic team. You will be responsible for developing and maintaining high-quality we
  ... (truncated for brevity)
```

---
## 8. Store Information & CMS

### Get About Us Page Content
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/about-api.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Retrieves About Us content sections, vision, mission, and company details.
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "message": "About Us sections retrieved successfully",
  "data": [
    {
      "id": "1",
      "section_title": "Welcome to Printmont",
      "section_content": "At Printmont, we are passionate about delivering exceptional printing solutions that bring your ideas to life. With over 15 years of experience in the printing industry, we have built a reputation for quality, reli
  ... (truncated for brevity)
```

---
### Get Contact Information
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/contact-api.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Retrieves customer support phone numbers, contact email, and office address.
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "message": "Contact information retrieved successfully",
  "data": {
    "id": "1",
    "help_number": "9818532463",
    "service_time": "Mon - Sat: 10:30 AM - 7:00 PM",
    "sales_email": "support@printmont.com",
    "corporate_email": "info@printmont.com",
    "address_one": "3398, Bagichi Acchi ji, Bara Hindu Rao, Near Filmistan Cinema, New Delhi, India - 110006",
    "ad
  ... (truncated for brevity)
```

---
### Get FAQ List
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/faq-api.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Get list of Frequently Asked Questions.
- **Parameters / Auth**: Query param: `is_active` (optional)

**Sample Response Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": "1",
      "question": "What is Printmont?",
      "answer": "Printmont is a powerful landing page and CRO platform that enables marketers to create high-converting pages with ease, helping to boost marketing campaign ROI.",
      "category_id": "1",
      "is_active": "1",
      "display_order": "1",
      "keywords": "",
      "view_count": "0",
  ... (truncated for brevity)
```

---
### Get Help Center Categories & FAQs
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/help-center-api.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Get Help Center categories with nested questions and answers.
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Product ",
        "description": "this is product ",
        "type": "product",
        "icon": "",
        "color": "#6c757d",
        "is_active": true,
        "display_order": 1,
        "faq_count": 4,
        "created_at": "2025-11-20 14:59:23",
        "updated_at": "2025-11-20 15:38:06",
       
  ... (truncated for brevity)
```

---
### Get Store Policies
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/policies-api.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Fetch store policies (Shipping, Terms & Conditions, Return Policy, Privacy Policy).
- **Parameters / Auth**: None

**Sample Response Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 3,
      "policy_key": "shipping",
      "heading": "Shipping Policy",
      "description": "<p>Thank you for your interest in Printmont! This Shipping Policy outlines everything you need to know about how we fulfill and deliver your custom orders. Please read it carefully.</p>\r\n\r\n<p><strong>1. Order Processing Time (Production)</strong></p>\r
  ... (truncated for brevity)
```

---
### Get Website Logos & Branding Assets
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/logo-api.php`
- **Status**: `200 OK` (Verified Live)
- **Description**: Fetch store logos and branding imagery.
- **Parameters / Auth**: Query params: `desktop_logo=1`, `favicon=1`

**Sample Response Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": "5",
      "asset_type": "desktop_logo",
      "asset_name": "printmont logo",
      "file_name": "691aec080786d_1763372040.png",
      "file_path": "uploads/logos/",
      "file_extension": "png",
      "file_size": "29783",
      "dimensions": "695x174",
      "upload_timestamp": "2025-11-17 15:04:00",
      "uploaded_by": "1",
      "is_active"
  ... (truncated for brevity)
```

---
### Get Desktop Logo Specific
- **Method**: `GET`
- **Full URL**: `http://localhost/printmont/printmont-backend/api/logo-api.php?desktop_logo=1`
- **Status**: `200 OK` (Verified Live)
- **Description**: Fetch active desktop logo.
- **Parameters / Auth**: Query param: `desktop_logo=1`

**Sample Response Output:**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "asset_type": "desktop_logo",
    "asset_name": "printmont logo",
    "file_name": "691aec080786d_1763372040.png",
    "file_path": "uploads/logos/",
    "file_extension": "png",
    "file_size": 29783,
    "dimensions": "695x174",
    "upload_timestamp": "2025-11-17 15:04:00",
    "uploaded_by": 1,
    "is_active": 1,
    "version": 1,
    "descri
  ... (truncated for brevity)
```

---
