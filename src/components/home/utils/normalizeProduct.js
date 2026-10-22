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
 *   slug: string,
 *   brand: string,
 *   rating: string|number,
 *   reviewsCount: number,
 *   isCustomizable: boolean,
 *   customizationLabel: string
 * }
 */
export default function normalizeProduct(p) {
  if (!p) return null;

  const isCustomizable = Boolean(
    p.customization_label_status ||
    p.show_customization_label ||
    p.is_customizable ||
    p.isCustomizable ||
    p.customizable ||
    (p.customization_data && (Array.isArray(p.customization_data) ? p.customization_data.length > 0 : Object.keys(p.customization_data).length > 0)) ||
    (p.customization_fields && p.customization_fields !== '[]' && p.customization_fields !== '{}' && p.customization_fields !== '') ||
    p.customization_tech ||
    (p.printing_location && (Array.isArray(p.printing_location) ? p.printing_location.length > 0 : p.printing_location !== '[]' && p.printing_location !== '')) ||
    (p.customization_label && p.customization_label.trim() !== '') ||
    (p.customizationLabel && p.customizationLabel.trim() !== '')
  );

  const customizationLabel = p.customization_label || p.customizationLabel || (isCustomizable ? "CUSTOMIZABLE" : "");

  let rawImg = "/default-img.jpg";
  if (p.img) {
    rawImg = p.img;
  } else if (p.image) {
    rawImg = Array.isArray(p.image) ? p.image[0] : p.image;
  } else if (Array.isArray(p.images) && p.images.length > 0) {
    const primary = p.images.find(img => img.is_primary) || p.images[0];
    rawImg = primary.image_url || (typeof primary === 'string' ? primary : "/default-img.jpg");
  } else if (p.primary_image) {
    rawImg = p.primary_image;
  } else if (p.thumbnail) {
    rawImg = p.thumbnail;
  }

  const resolvedImg = resolveImageUrl(rawImg);

  const priceVal = parseFloat(p.price || p.selling_price || p.discountedPrice || 0);
  const discPriceVal = parseFloat(p.discount_price || p.discounted_price || 0);
  const origPriceVal = parseFloat(p.original_price || p.originalPrice || p.mrp || p.regular_price || 0);

  let currentPrice = priceVal;
  let originalPrice = null;
  let discountText = "";

  if (origPriceVal > currentPrice && currentPrice > 0) {
    originalPrice = origPriceVal;
    discountText = `${Math.round(((origPriceVal - currentPrice) / origPriceVal) * 100)}% Off`;
  } else if (discPriceVal > 0) {
    const maxVal = Math.max(priceVal, discPriceVal);
    const minVal = Math.min(priceVal, discPriceVal);
    if (maxVal > minVal) {
      originalPrice = maxVal;
      currentPrice = minVal;
      discountText = `${Math.round(((maxVal - minVal) / maxVal) * 100)}% Off`;
    }
  } else if (p.originalPrice || p.discount || p.discountPercent) {
    originalPrice = parseFloat(p.originalPrice) || null;
    discountText = p.discount ? (p.discount.includes("off") || p.discount.includes("%") ? p.discount : `${p.discount} Off`) : (p.discountPercent ? `${p.discountPercent}% Off` : "");
  }

  // Calculate default discount if original price exists and no discount text yet
  if (originalPrice && currentPrice && originalPrice > currentPrice && !discountText) {
    discountText = `${Math.round(((originalPrice - currentPrice) / originalPrice) * 100)}% Off`;
  }

  // Badges: New Arrival / Best Seller / Top Rated / Trending / Special Offer
  let badgeText = p.badge || "";
  if (!badgeText) {
    if (p.is_new || p.new_arrival) badgeText = "New Arrival";
    else if (p.our_bestseller || p.is_bestseller || p.bestseller) badgeText = "Best Seller";
    else if (p.top_selection) badgeText = "New Arrival";
    else if (p.top_deal || p.special_offer) badgeText = "Special Offer";
  }

  // Brand / Seller name
  const brandName = p.brand || p.seller_name || p.seller || "Seller name here.";

  // Rating & Review count
  const ratingVal = p.rating || p.avg_rating || (4.5 + ((Number(p.id) || 1) % 5) * 0.1).toFixed(1);
  const reviewsCountVal = p.reviewsCount || p.reviews_count || p.reviewCount || p.total_reviews || (18 + ((Number(p.id) || 1) % 35) * 8);

  return {
    id: p.id || "",
    title: p.name || p.title || "Product Title",
    img: resolvedImg,
    price: currentPrice,
    originalPrice: originalPrice,
    discount: discountText,
    badge: badgeText,
    slug: p.slug || p.product_slug || "",
    brand: brandName,
    rating: ratingVal,
    reviewsCount: reviewsCountVal,
    isCustomizable: isCustomizable || !!p.isCustomizable,
    customizationLabel: customizationLabel,
    show_customization_label: isCustomizable,
    raw: p
  };
}
