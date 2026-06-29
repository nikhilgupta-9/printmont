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
        
        {/* Product Badges (e.g., Best Seller, Top Rated) */}
        {product.badge && (
          <span className="product-card-badge position-absolute top-0 start-0 m-1 badge bg-primary">
            {product.badge}
          </span>
        )}
      </div>

      {/* Product Content Details Area */}
      <div className="product-card-body d-flex flex-column p-1 w-100 flex-grow-1 justify-content-end text-center">
        {badgeText && (
          <div className="product-card-action-badge d-none d-lg-flex justify-content-center align-items-center bg-theme text-uppercase text-white fw-medium mt-1 border w-100">
            {badgeText}
          </div>
        )}
        
        <p className="product-card-title text-truncate fs-6 mt-1 text-center mb-1">
          {product.title}
        </p>
        
        <p className="product-card-price mb-0 p-0 small text-center text-dark">
          <span className="fw-bold fs-6">₹{product.price}</span>
          {product.originalPrice && (
            <>
              {" "}
              <del className="text-muted" style={{ fontSize: "0.7rem" }}>
                ₹{product.originalPrice}
              </del>
            </>
          )}
          {product.discount && (
            <>
              {" "}
              <span className="text-success fw-bold small">
                {product.discount}
              </span>
            </>
          )}
        </p>
      </div>
    </Link>
  );
}
