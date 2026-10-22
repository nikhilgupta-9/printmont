/**
 * Helper to generate SEO-friendly URLs for product detail pages.
 * Matches the route '/:productSlug' expected by ProductDetails.jsx.
 */
export const getProductUrl = (product) => {
  if (!product) return '/allproducts';
  
  // Extract ID
  const id = product.id || product.productId || product.item_id;
  const name = product.title || product.name || product.slug || 'product';

  if (!id) {
    return `/allproducts?q=${encodeURIComponent(name)}`;
  }
  
  // Create a clean URL-friendly slug
  const cleanSlug = name
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-') // Replace non-alphanumeric chars with hyphens
    .replace(/(^-|-$)+/g, '');    // Remove leading/trailing hyphens
    
  return `/product/${cleanSlug}-p${id}`;
};

/**
 * Set page dynamic SEO metadata (Title, Description, Keywords, OG, Twitter Cards, Canonical)
 * Compliant with Rule 3 in rule.md
 */
export const updatePageSEO = ({
  title = "Printmont - Custom Printing & Corporate Gifting Solutions",
  description = "Discover customized printing, corporate employee kits, personalized occasion gifts, and branding solutions with Printmont.",
  keywords = "custom printing, corporate gifts, personalized gifts, branded merchandise, printmont",
  image = "/PrintLogo.png",
  url = window.location.href,
  type = "website",
} = {}) => {
  // 1. Page Title
  const formattedTitle = title.includes("Printmont") ? title : `${title} | Printmont`;
  document.title = formattedTitle;

  // Helper to set or create meta tag
  const setMetaTag = (attrName, attrValue, content) => {
    if (!content) return;
    let element = document.querySelector(`meta[${attrName}="${attrValue}"]`);
    if (!element) {
      element = document.createElement("meta");
      element.setAttribute(attrName, attrValue);
      document.head.appendChild(element);
    }
    element.setAttribute("content", content);
  };

  // 2. Standard Meta
  setMetaTag("name", "description", description);
  setMetaTag("name", "keywords", keywords);

  // 3. OpenGraph Meta
  setMetaTag("property", "og:title", formattedTitle);
  setMetaTag("property", "og:description", description);
  setMetaTag("property", "og:type", type);
  setMetaTag("property", "og:url", url);
  if (image) {
    const fullImageUrl = image.startsWith("http") ? image : `${window.location.origin}${image.startsWith("/") ? "" : "/"}${image}`;
    setMetaTag("property", "og:image", fullImageUrl);
  }

  // 4. Twitter Cards
  setMetaTag("name", "twitter:card", "summary_large_image");
  setMetaTag("name", "twitter:title", formattedTitle);
  setMetaTag("name", "twitter:description", description);
  if (image) {
    const fullImageUrl = image.startsWith("http") ? image : `${window.location.origin}${image.startsWith("/") ? "" : "/"}${image}`;
    setMetaTag("name", "twitter:image", fullImageUrl);
  }

  // 5. Canonical Link
  let canonicalLink = document.querySelector("link[rel='canonical']");
  if (!canonicalLink) {
    canonicalLink = document.createElement("link");
    canonicalLink.setAttribute("rel", "canonical");
    document.head.appendChild(canonicalLink);
  }
  canonicalLink.setAttribute("href", url);
};

