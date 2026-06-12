# Product Detail Page & Dynamic Features Implementation Plan

To achieve the exact layout from your PDF, along with SEO-friendly URLs and dynamic product features managed from the admin panel, we will follow these steps.

Since I cannot directly view local PDF files, **I will need you to provide screenshots of the PDF** or export it as an image and share it in the chat so I can perfectly replicate the layout in React.

In the meantime, here is the full implementation plan:

## 1. Database Updates

We need to update the database to support SEO-friendly URLs and dynamic features.

- **Add `slug` to `products` table:** This will allow URLs like `/product/men-custom-tshirt` instead of `/product?id=123`.
- **Create `product_features` table:**
  - `id` (INT, Primary Key)
  - `product_id` (INT, Foreign Key)
  - `feature_name` (VARCHAR, e.g., "Fabric", "Fit", "Print Type")
  - `feature_value` (VARCHAR, e.g., "100% Cotton", "Regular Fit", "Direct-to-Garment")
  - `display_order` (INT, to control the order on the frontend)

## 2. Backend Updates (PHP)

- **Update `ProductModel.php` & `ProductController.php`:**
  - Add a `getProductBySlugApi($slug)` method to fetch product details based on the URL slug.
  - Modify `addProduct` and `updateProduct` functions to accept an array of features and save them to the `product_features` table.
  - Modify the product fetch APIs to always include the associated features in the response payload.

## 3. Admin Panel Updates

- **Product Management Form:**
  - Add a "Product Features" section to the Add/Edit Product screens.
  - Implement dynamic inputs where admins can click "Add Feature" and provide a "Name" and "Value" for as many features as needed.

## 4. Frontend Updates (React)

- **SEO-Friendly Routing:**
  - Update `App.jsx` to change `<Route path="/product" element={<ProductDetails />} />` to `<Route path="/product/:slug" element={<ProductDetails />} />`.
- **`ProductDetails.jsx` Component Layout:**
  - Update the component to fetch the product by `slug` using `useParams`.
  - Implement the exact UI/UX from the provided PDF (once screenshots are provided).
  - Map through the dynamic `features` array returned from the API and display them in the "Features/Options" section of the layout.

## Next Steps

1.  **Please upload a screenshot (or multiple screenshots) of the `Desktop single product pages.pdf`** so I can see the exact layout you want.
2.  Let me know if you approve of this plan, and we can begin by updating the database schema and backend logic!
