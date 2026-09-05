import React from "react";
import { Link } from "react-router-dom";
import { getProductUrl } from "../../../utils/seo";

/**
 * Standardized Product Card supporting different style variants.
 * @param {Object} product Normalized product object { id, title, img, price, originalPrice, discount, badge, slug }
 * @param {string} [variant='carousel'] Style variant: 'carousel', 'rail', 'compact-grid', 'mosaic'
 * @param {string} [badgeText] Optional action badge text (e.g. 'Customizable')
 * @param {Object} [style] Optional extra inline style overrides
 */
export default function ProductCard({
  product,
  variant = "carousel",
  badgeText = "",
  style
}) {
  if (!product) return null;

  const url = getProductUrl(product);

  return (
    <Link
      to={url}
      className={`product-card variant-${variant} border d-flex flex-column p-1 h-100 m-0 text-decoration-none text-dark bg-white`}
      style={style}
    >
      {/* Product Image Area with Stable Square Aspect Ratio */}
      <div className="product-card-img-wrapper position-relative w-100 bg-white">
        <img
          src={product.img || "/default-img.jpg"}
          alt={product.title}
          className="product-card-img zoom-hover"
          loading="lazy"
        />
        
        {/* Product Badges (e.g., Best Seller, Top Rated, Premium) */}
        {product.badge && (
          <span 
            className="product-card-badge position-absolute top-0 start-0 m-1 badge"
            style={{
              backgroundColor: (product.badge.toLowerCase().includes('priemium') || product.badge.toLowerCase().includes('premium')) ? '#00a65a' : '#0b53a1',
              color: '#ffffff',
              fontWeight: '600',
              zIndex: 10
            }}
          >
            {product.badge}
          </span>
        )}

        {/* Customizable Banner at bottom of image */}
        {(badgeText || product.overlayTag) && (
          <div className="product-card-overlay-tag position-absolute bottom-0 start-0 w-100 text-uppercase text-white text-center fw-semibold py-1">
            {badgeText || product.overlayTag}
          </div>
        )}
      </div>

      {/* Product Content Details Area */}
      <div className="product-card-body d-flex flex-column p-1 w-100 flex-grow-1 justify-content-end text-center">
        
        <p 
          className="product-card-title mt-1 text-center mb-1 px-1"
          style={{
            display: "-webkit-box",
            WebkitLineClamp: 2,
            WebkitBoxOrient: "vertical",
            overflow: "hidden",
            maxHeight: "2.4em",
            lineHeight: "1.2",
            fontWeight: "500"
          }}
        >
          {product.title}
        </p>
        
        <div className="product-card-price mb-0 p-0 text-center text-dark d-flex align-items-center justify-content-center gap-1 flex-wrap">
          {product.price !== undefined && product.price !== null && product.price > 0 && (
            <span className="fw-bold product-card-main-price">₹{product.price}</span>
          )}
          {product.originalPrice && product.originalPrice > 0 && (
            <del className="text-muted product-card-was-price">
              ₹{product.originalPrice}
            </del>
          )}
          {product.discount && (
            <span className="text-success fw-bold product-card-discount-text">
              ({product.discount.includes("off") || product.discount.includes("%") ? product.discount : `${product.discount} off`})
            </span>
          )}
        </div>
      </div>
    </Link>
  );
}
