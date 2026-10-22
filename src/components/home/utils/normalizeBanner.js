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

  const resolve = (val) => {
    if (!val || typeof val !== "string") return "";
    const trimmed = val.trim();
    if (!trimmed) return "";
    if (
      trimmed.startsWith("http://") ||
      trimmed.startsWith("https://") ||
      trimmed.startsWith("//") ||
      trimmed.startsWith("data:")
    ) {
      return trimmed;
    }
    const cleanBase = (basePath || "").replace(/\/+$/, "");
    const cleanVal = trimmed.replace(/^\/+/, "");
    return cleanBase ? `${cleanBase}/${cleanVal}` : `/${cleanVal}`;
  };

  let large = "";
  let small = "";
  let target = "#";
  let alt = "";

  if (typeof item === "string") {
    large = resolve(item);
    small = large;
  } else if (item.large || item.small) {
    large = resolve(item.large || item.small);
    small = resolve(item.small || item.large);
    target = item.target || item.target_url || item.mobile_url || item.desktop_url || "#";
    alt = item.alt || item.title || "";
  } else if (item.images && typeof item.images === "object") {
    large = resolve(item.images.desktop || item.images.mobile);
    small = resolve(item.images.mobile || item.images.desktop || large);
    target = item.target_url || item.target || item.mobile_url || item.desktop_url || "#";
    alt = item.alt || item.title || "";
  } else if (item.desktop_image || item.mobile_image) {
    large = resolve(item.desktop_image || item.mobile_image);
    small = resolve(item.mobile_image || item.desktop_image || large);
    target = item.mobile_url || item.desktop_url || item.target_url || item.target || "#";
    alt = item.alt || item.title || "";
  } else if (item.image_url_desktop || item.image_url_mobile) {
    large = resolve(item.image_url_desktop || item.image_url_mobile);
    small = resolve(item.image_url_mobile || item.image_url_desktop || large);
    target = item.mobile_url || item.desktop_url || item.target_url || item.target || "#";
    alt = item.alt || item.title || "";
  } else if (item.image_url) {
    large = resolve(item.image_url);
    small = large;
    target = item.target_url || item.target || "#";
    alt = item.alt || item.title || "";
  } else if (item.url) {
    large = resolve(item.url);
    small = large;
    target = item.target || item.target_url || "#";
    alt = item.alt || item.title || "";
  } else if (item.src) {
    large = resolve(item.src);
    small = large;
    target = item.target || item.target_url || "#";
    alt = item.alt || item.title || "";
  }

  // Handle case where API targets can be literal string "0" or invalid values
  const safeTarget = (target !== "0" && target && typeof target === "string") ? target : "#";

  return {
    id: item.id,
    title: item.title || alt || "",
    description: item.description || "",
    large: large,
    small: small,
    image: large || small,
    image_url_desktop: large,
    image_url_mobile: small,
    target: safeTarget,
    target_url: safeTarget,
    mobile_url: item.mobile_url || safeTarget,
    desktop_url: item.desktop_url || safeTarget,
    mobile_design: item.mobile_design || "small_banner_standard",
    desktop_design: item.desktop_design || "small_banner",
    auto_play_speed: item.auto_play_speed || 3000,
    mobile_status: item.mobile_status || "active",
    desktop_status: item.desktop_status || "active",
    alt: alt || item.title || "Banner",
    raw: item
  };
}
