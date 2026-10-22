import React from "react";
import { Link } from "react-router-dom";
import { FaStar } from "react-icons/fa";
import { getProductUrl } from "../../../utils/seo";

/**
 * ThreeColProductCard - Dedicated card style for 3-column x 2-row (6 products) sections.
 * Matches exact layout from mockup (media_1791025553442.png):
 * - Portrait image with rounded corners
 * - Rating badge (4.1 ★) on bottom-left of image
 * - Truncated single-line title
 * - Selling price + strikethrough original price
 * - Bank offer tag in blue
 */
export default function ThreeColProductCard({ product }) {
  if (!product) return null;

  const url = getProductUrl(product);
  const sellingPrice = product.price !== undefined && product.price !== null ? product.price : (product.offer_price || product.selling_price || 0);
  const originalPrice = product.originalPrice !== undefined && product.originalPrice !== null ? product.originalPrice : (product.regular_price || 0);

  const ratingVal = product.rating || (4.0 + ((Number(product.id) || 1) % 4) * 0.1).toFixed(1);
  const bankPrice = Math.max(1, Math.round(sellingPrice * 0.85));

  return (
    <div className="three-col-card-container h-100 position-relative">
      <Link
        to={url}
        className="d-flex flex-column h-100 m-0 text-decoration-none text-dark bg-white overflow-hidden"
        style={{ borderRadius: "6px", transition: "box-shadow 0.2s" }}
      >
        {/* Product Image Wrapper (Square 1:1) */}
        <div
          className="position-relative w-100 bg-white"
          style={{
            aspectRatio: "1 / 1",
            overflow: "hidden",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            borderRadius: "6px 6px 0 0",
            padding: "4px"
          }}
        >
          <img
            src={product.img || "/default-img.jpg"}
            alt={product.title}
            className="w-100 h-100 zoom-hover"
            loading="lazy"
            style={{ objectFit: "contain", aspectRatio: "1 / 1" }}
            onError={(e) => {
              e.target.onerror = null;
              e.target.src = "/default-img.jpg";
            }}
          />

          {/* AD Tag if applicable */}
          {product.badge && product.badge.toLowerCase() === "ad" && (
            <span
              className="position-absolute top-0 start-0 m-1"
              style={{
                backgroundColor: "rgba(0,0,0,0.5)",
                color: "#ffffff",
                fontSize: "9px",
                fontWeight: "700",
                padding: "1px 4px",
                borderRadius: "2px",
                zIndex: 5
              }}
            >
              AD
            </span>
          )}

          {/* Rating Pill on Bottom-Left of Image */}
          <div
            className="position-absolute bottom-0 start-0 m-1 d-inline-flex align-items-center text-white fw-bold shadow-sm"
            style={{
              backgroundColor: "#166534",
              fontSize: "10px",
              padding: "1px 4px",
              borderRadius: "3px",
              zIndex: 5,
              lineHeight: "1.1"
            }}
          >
            {ratingVal} <FaStar size={7} className="ms-0.5" />
          </div>
        </div>

        {/* Product Details */}
        <div className="d-flex flex-column p-1.5 w-100 flex-grow-1 justify-content-between text-start bg-white">
          <div>
            {/* Title / Brand */}
            <h4
              className="text-start mb-0.5 text-dark"
              style={{
                fontSize: "12px",
                fontWeight: "500",
                color: "#1f2937",
                lineHeight: "1.2",
                whiteSpace: "nowrap",
                overflow: "hidden",
                textOverflow: "ellipsis",
                margin: 0
              }}
              title={product.title}
            >
              {product.title}
            </h4>
          </div>

          {/* Price Line */}
          <div className="mt-0.5">
            <div className="d-flex align-items-baseline gap-1 flex-nowrap text-nowrap">
              {sellingPrice > 0 && (
                <span className="fw-bold text-dark" style={{ fontSize: "13.5px", color: "#111827", fontWeight: "700" }}>
                  ₹{sellingPrice}
                </span>
              )}
              {originalPrice > sellingPrice && (
                <del className="text-muted ms-1" style={{ fontSize: "11px", color: "#9ca3af" }}>
                  ₹{originalPrice}
                </del>
              )}
            </div>

            {/* Bank Offer Line */}
            <div
              className="text-truncate mt-0.5"
              style={{ fontSize: "10px", color: "#2563eb", fontWeight: "600", lineHeight: "1.1" }}
            >
              ₹{bankPrice} with Bank offer
            </div>
          </div>
        </div>
      </Link>
    </div>
  );
}
