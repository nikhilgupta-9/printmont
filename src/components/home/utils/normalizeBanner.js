/**
 * Normalizes different API banner representations into a single standard format:
 * {
 *   large: string,  // URL for desktop
 *   small: string,  // URL for mobile
 *   target: string, // Link destination
 *   alt: string     // Alternative text
 * }
 */
export default function normalizeBanner(item, basePath = "") {
  if (!item) return null;

  let large = "";
  let small = "";
  let target = "#";
  let alt = "";

  if (typeof item === "string") {
    large = item;
    small = item;
  } else if (item.large || item.small) {
    large = item.large || "";
    small = item.small || large;
    target = item.target || "#";
    alt = item.alt || item.title || "";
  } else if (item.url) {
    large = item.url;
    small = item.url;
    alt = item.alt || item.title || "";
  } else if (item.src) {
    large = item.src;
    small = item.src;
    alt = item.alt || item.title || "";
  } else if (item.images) {
    large = item.images.desktop || "";
    small = item.images.mobile || large || "";
    target = item.target_url || item.target || "#";
    alt = item.alt || item.title || "";
  } else {
    // Check for image_url_desktop and image_url_mobile fields
    large = item.image_url_desktop ? `${basePath}${item.image_url_desktop}` : (item.image_url || "");
    small = item.image_url_mobile ? `${basePath}${item.image_url_mobile}` : (item.image_url || large);
    target = item.target_url || item.target || "#";
    alt = item.alt || item.title || "";
  }

  // Handle case where API targets can be literal string "0" or invalid values
  return {
    large: large,
    small: small,
    target: (target !== "0" && target) ? target : "#",
    alt: alt
  };
}
