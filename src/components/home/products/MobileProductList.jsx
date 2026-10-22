import React from "react";
import { Link } from "react-router-dom";
import { MdKeyboardArrowRight } from "react-icons/md";
import useHomeProducts from "../hooks/useHomeProducts";
import ProductCard from "./ProductCard";

/**
 * Flexible Product Grid Component.
 * Supports dynamic column count (1, 2, 3, or 4 columns), limits, handpicked products, and section titles.
 * @param {string} [title] Section header title.
 * @param {Array} [products] Initial static products fallback.
 * @param {string} [apiUrl] API endpoint to fetch products from.
 * @param {number} [limit=6] Max number of products to show.
 * @param {number} [columns=2] Number of cards per row (1, 2, 3, or 4).
 * @param {string} [badgeText] Optional badge text for product cards.
 * @param {string} [bgImage] Optional background image URL.
 * @param {boolean} [showViewAll=false] Display the View All link.
 * @param {string} [viewAllUrl] Custom View All URL.
 */
export default function MobileProductList({
  title,
  products: initialProducts = [],
  apiUrl,
  limit = 6,
  columns = 2,
  badgeText,
  bgImage,
  showViewAll = false,
  viewAllUrl
}) {
  const { products, loading, error } = useHomeProducts(apiUrl, initialProducts, limit);

  // Determine grid item CSS class based on columns_per_row
  let colClass = "card-grid-item-2col";
  const colNum = Number(columns) || 2;
  if (colNum === 1) {
    colClass = "card-grid-item-1col";
  } else if (colNum === 3) {
    colClass = "card-grid-item-3col";
  } else if (colNum === 4) {
    colClass = "card-grid-item-4col";
  }

  const containerStyle = {
    backgroundImage: bgImage ? `url(${bgImage})` : "none",
    backgroundSize: "cover",
    backgroundPosition: "center",
    padding: bgImage ? "1rem" : "0",
    borderRadius: bgImage ? "0.375rem" : "0"
  };

  if (loading && products.length === 0) {
    return (
      <div className="bg-white w-100 p-0 m-0" style={containerStyle}>
        {title && (
          <div className="pm-home-section-header mb-2">
            <h3 className="pm-home-section-title">{title}</h3>
          </div>
        )}
        <div className="card-grid-container">
          {Array.from({ length: Math.min(limit, 4) }).map((_, idx) => (
            <div className={colClass} key={idx}>
              <div className="border rounded p-3 text-center bg-white">
                <div className="shimmer-bg skeleton-img w-100 mb-2" style={{ aspectRatio: "1/1" }} />
                <div className="shimmer-bg skeleton-title w-75 mx-auto" />
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  if (error && products.length === 0) {
    return <div className="text-center p-3 text-danger">Error: {error}</div>;
  }

  if (products.length === 0) return null;

  return (
    <div className="bg-white w-100 p-0 m-0" style={containerStyle}>
      {Boolean(title && title.trim()) && (
        <div className="pm-home-section-header mb-2">
          <h3
            className="pm-home-section-title"
            style={{ color: bgImage ? "white" : "#1e293b" }}
          >
            {title}
          </h3>
          {showViewAll && (
            <Link
              to={viewAllUrl || `/allproducts?q=${encodeURIComponent(title || "")}`}
              className="pm-home-view-all-btn"
            >
              View All <MdKeyboardArrowRight size={16} />
            </Link>
          )}
        </div>
      )}
      <div className="card-grid-container">
        {products.map((product, index) => (
          <div
            key={product.id || index}
            className={colClass}
          >
            <ProductCard
              product={product}
              variant="compact-grid"
              badgeText={badgeText}
            />
          </div>
        ))}
      </div>
    </div>
  );
}
