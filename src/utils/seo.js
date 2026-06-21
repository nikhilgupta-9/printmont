/**
 * Helper to generate SEO-friendly URLs for product detail pages.
 * Matches the route '/:productSlug' expected by ProductDetails.jsx.
 */
export const getProductUrl = (product) => {
  if (!product) return '#';
  
  // Extract ID
  const id = product.id || product.productId || product.item_id;
  if (!id) return '#';
  
  // Extract title/name/slug
  const name = product.title || product.name || product.slug || 'product';
  
  // Create a clean URL-friendly slug
  const cleanSlug = name
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-') // Replace non-alphanumeric chars with hyphens
    .replace(/(^-|-$)+/g, '');    // Remove leading/trailing hyphens
    
  return `/${cleanSlug}-p${id}`;
};
