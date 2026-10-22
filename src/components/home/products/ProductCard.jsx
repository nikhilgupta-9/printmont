import React from "react";
import { Link } from "react-router-dom";
import { getProductUrl } from "../../../utils/seo";

/**
 * Standardized Product Card matching the exact Printmont / Bestseller UI wireframe:
 * - Top-left solid badge ("Priemium" - blue or green)
 * - Portrait image (aspect-ratio ~ 3/4)
 * - Bottom-left rating box on the image: 4.9 ★ (green star)
 * - Solid navy blue CUSTOMIZABLE full-width strip below image
 * - Product Title (single line text-truncate)
 * - Price row: ₹ 1499. ₹2999 (50% off) (selling price bold, MRP strikethrough, off in green)
 */
export default function ProductCard({
  product,
  variant = "carousel",
  badgeText = "",
  style,
  index
}) {
  if (!product) return null;

  const url = getProductUrl(product);

  const isCustomizable = Boolean(
    product.isCustomizable ||
    product.show_customization_label ||
    product.customization_label_status ||
    (product.customizationLabel && product.customizationLabel.trim() !== "") ||
    (product.customization_label && product.customization_label.trim() !== "") ||
    true
  );

  const overlayTagText = isCustomizable
    ? (product.customizationLabel || product.customization_label || "CUSTOMIZABLE")
    : (product.overlayTag || badgeText || "CUSTOMIZABLE");

  // Fallback rating
  const idNum = parseInt(product.id, 10) || 1;
  const ratingVal = product.rating 
    ? (typeof product.rating === "number" ? product.rating.toFixed(1) : product.rating)
    : (idNum % 2 === 0 ? "4.9" : "4.8");

  // Badge determination & color (alternates Blue #0056b3 and Green #00a650 for Priemium as in mockup)
  const rawBadge = product.badge || badgeText || "Priemium";
  let badge = rawBadge;
  let badgeBg = "#0056b3"; // Blue by default
  
  if (product.badge_bg || product.badgeColor) {
    badgeBg = product.badge_bg || product.badgeColor;
  } else if ((index !== undefined ? index : idNum) % 2 === 1) {
    badgeBg = "#00a650"; // Green
  } else {
    badgeBg = "#0056b3"; // Blue
  }

  const sellingPrice = product.price !== undefined && product.price !== null
    ? Math.round(Number(product.price))
    : (product.offer_price || product.selling_price || 1499);

  let originalPrice = product.originalPrice !== undefined && product.originalPrice !== null
    ? Math.round(Number(product.originalPrice))
    : (product.regular_price || product.mrp || 0);

  if (originalPrice <= sellingPrice || originalPrice === 0) {
    originalPrice = Math.round(sellingPrice * 1.5) || 2999;
  }

  let discountText = "";
  if (product.discount) {
    let raw = String(product.discount).trim();
    if (!raw.toLowerCase().includes("off")) {
      raw = raw.includes("%") ? `${raw} off` : `${raw}% off`;
    }
    discountText = raw;
  } else if (originalPrice > sellingPrice && sellingPrice > 0) {
    const pct = Math.round(((originalPrice - sellingPrice) / originalPrice) * 100);
    if (pct > 0) {
      discountText = `${pct}% off`;
    }
  }

  const imgSrc = product.image || product.img || product.thumbnail_image || product.thumbnail || product.primary_image || "/default-img.jpg";
  const title = product.title || product.name || "Navy Blue Sulphur Cotton Shirts...";

  return (
    <div className={`product-card-container h-100 position-relative ${variant ? `variant-${variant}` : ""}`} style={style}>
      <Link
        to={url}
        className="product-card d-flex flex-column h-100 m-0 text-decoration-none text-dark bg-white overflow-hidden"
        style={{
          border: "1px solid #e5e7eb",
          borderRadius: "8px",
          transition: "box-shadow 0.2s ease, transform 0.2s ease",
          boxShadow: "0 1px 3px rgba(0,0,0,0.04)"
        }}
      >
        {/* Product Image Area (Square 1:1) */}
        <div
          className="product-card-img-wrapper position-relative w-100 bg-white"
          style={{
            aspectRatio: "1 / 1",
            overflow: "hidden",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            padding: "4px"
          }}
        >
          <img
            src={imgSrc}
            alt={title}
            className="product-card-img zoom-hover"
            loading="lazy"
            style={{ width: "100%", height: "100%", maxWidth: "100%", maxHeight: "100%", objectFit: "contain", aspectRatio: "1 / 1" }}
            onError={(e) => {
              e.target.onerror = null;
              e.target.src = "/default-img.jpg";
            }}
          />

          {/* Top-Left Corner Badge (Flush to corner, solid Blue / Green "Priemium") */}
          {badge && (
            <span
              className="position-absolute top-0 start-0"
              style={{
                backgroundColor: badgeBg,
                color: "#ffffff",
                fontSize: "13px",
                fontWeight: "700",
                padding: "4px 10px",
                borderRadius: "0",
                zIndex: 4,
                lineHeight: "1.2",
                letterSpacing: "0.2px"
              }}
            >
              {badge}
            </span>
          )}

          {/* Bottom-Left Rating Box on the Image (White box with green star: 4.9 ★) */}
          <div
            className="position-absolute"
            style={{
              bottom: "8px",
              left: "8px",
              zIndex: 4,
              backgroundColor: "#ffffff",
              border: "1px solid #d1d5db",
              borderRadius: "3px",
              padding: "2px 6px",
              display: "flex",
              alignItems: "center",
              gap: "3px",
              boxShadow: "0 1px 2px rgba(0,0,0,0.08)",
              fontSize: "12.5px",
              fontWeight: "700",
              color: "#111827",
              lineHeight: "1"
            }}
          >
            <span>{ratingVal}</span>
            <span style={{ color: "#00a650", fontSize: "12px", lineHeight: "1" }}>★</span>
          </div>
        </div>

        {/* Solid Blue CUSTOMIZABLE Overlay Bar at Bottom of Image */}
        {overlayTagText && (
          <div
            className="w-100 text-uppercase text-white text-center fw-bold"
            style={{
              backgroundColor: "#233876",
              fontSize: "12.5px",
              letterSpacing: "0.5px",
              lineHeight: "1.2",
              padding: "4.5px 0",
              fontWeight: "700",
              flexShrink: 0
            }}
          >
            {overlayTagText}
          </div>
        )}

        {/* Product Content Details Area */}
        <div
          className="product-card-body d-flex flex-column w-100 flex-grow-1 justify-content-between text-start bg-white"
          style={{ padding: "8px 10px 10px 10px" }}
        >
          {/* Product Title */}
          <h3
            className="product-card-title text-start text-dark"
            style={{
              fontSize: "13.5px",
              fontWeight: "600",
              color: "#1e293b",
              lineHeight: "1.35",
              whiteSpace: "nowrap",
              overflow: "hidden",
              textOverflow: "ellipsis",
              margin: "0 0 6px 0"
            }}
            title={title}
          >
            {title}
          </h3>

          {/* Price Section: ₹ 1499. ₹2999 (50% off) */}
          <div className="d-flex align-items-baseline flex-nowrap text-nowrap gap-1.5">
            <span
              className="fw-bold text-dark"
              style={{ fontSize: "15px", color: "#0f172a", fontWeight: "800" }}
            >
              ₹{sellingPrice}
            </span>
            {originalPrice > sellingPrice && (
              <del
                className="text-muted"
                style={{ fontSize: "12px", color: "#94a3b8", textDecoration: "line-through" }}
              >
                ₹{originalPrice}
              </del>
            )}
            {discountText && (
              <span
                style={{ fontSize: "12px", color: "#16a34a", fontWeight: "600" }}
              >
                ({discountText})
              </span>
            )}
          </div>
        </div>
      </Link>
    </div>
  );
}
