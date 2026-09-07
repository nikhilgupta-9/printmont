import { resolveImageUrl } from "../../../config/apiEndpoints";

/**
 * Normalizes different API product shapes into a single standard format:
 * {
 *   id: string|number,
 *   title: string,
 *   img: string,
 *   price: number,
 *   originalPrice: number|null,
 *   discount: string,
 *   badge: string,
 *   slug: string // Optional slug field if present
 * }
 */
export default function normalizeProduct(p) {
  if (!p) return null;

  // If already formatted (e.g. static data passed with img and title directly)
  if (p.img && p.title && p.price !== undefined) {
    return {
      id: p.id || "",
      title: p.title,
      img: resolveImageUrl(p.img),
      price: p.price,
      originalPrice: p.originalPrice || null,
      discount: p.discount || "",
      badge: p.badge || "",
      slug: p.slug || ""
    };
  }

  let primaryImg = "/default-img.jpg";
  if (p.img) {
    primaryImg = p.img;
  } else if (p.image) {
    primaryImg = p.image;
  } else if (Array.isArray(p.images) && p.images.length > 0) {
    const primary = p.images.find(img => img.is_primary) || p.images[0];
    primaryImg = primary.image_url || "/default-img.jpg";
  } else if (p.primary_image) {
    primaryImg = p.primary_image;
  } else if (p.thumbnail) {
    primaryImg = p.thumbnail;
  }

  const priceVal = parseFloat(p.price) || 0;
  const discPriceVal = parseFloat(p.discount_price) || 0;

  let currentPrice = priceVal;
  let originalPrice = null;
  let discountText = "";

  if (discPriceVal > 0) {
    const maxVal = Math.max(priceVal, discPriceVal);
    const minVal = Math.min(priceVal, discPriceVal);
    if (maxVal > minVal) {
      originalPrice = maxVal;
      currentPrice = minVal;
      discountText = `${Math.round(((maxVal - minVal) / maxVal) * 100)}% Off`;
    }
  } else if (p.originalPrice || p.discount) {
    originalPrice = parseFloat(p.originalPrice) || null;
    discountText = p.discount || "";
  }

  const badgeText = p.badge || (p.our_bestseller ? "Best Seller" : (p.top_rated ? "Top Rated" : ""));

  return {
    id: p.id || "",
    title: p.name || p.title || "",
    img: resolveImageUrl(primaryImg),
    price: currentPrice,
    originalPrice: originalPrice,
    discount: discountText,
    badge: badgeText,
    slug: p.slug || p.product_slug || ""
  };
}
