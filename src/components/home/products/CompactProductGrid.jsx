import React from "react";
import { Link } from "react-router-dom";
import { MdKeyboardArrowRight } from "react-icons/md";
import useHomeProducts from "../hooks/useHomeProducts";
import ProductCard from "./ProductCard";

/**
 * Replaces ProductGrid. Displays a grid of products (supports 2 or 3 columns on mobile).
 * @param {string} title Grid section title.
 * @param {Array} [products] Initial static products.
 * @param {string} [apiUrl] API endpoint to fetch products from.
 * @param {string} [bgImage] Optional background image path.
 * @param {number} [columns=3] Number of columns on mobile (2 or 3).
 * @param {number} [limit=6] Max number of products to show.
 * @param {boolean} [showViewAll=false] Display the View All link on the right of the header.
 */
export default function CompactProductGrid({
  title,
  products: initialProducts = [],
  apiUrl,
  bgImage,
  columns = 3,
  limit = 6,
  showViewAll = false
}) {
  const { products, loading, error } = useHomeProducts(apiUrl, initialProducts, limit);

  const containerStyle = {
    backgroundImage: bgImage ? `url(${bgImage})` : "none",
    backgroundSize: "cover",
    backgroundPosition: "center",
    padding: bgImage ? "1rem" : "0",
    borderRadius: bgImage ? "0.375rem" : "0"
  };

  const colClass = columns === 2 ? "card-grid-item-2col" : "card-grid-item-3col";

  if (loading && products.length === 0) {
    return (
      <div className="container-fluid px-1 px-lg-0 home-layout-gap" style={containerStyle}>
        <div className="d-flex justify-content-between align-items-center mb-1 mb-lg-3">
          <h5 className="mb-0 fw-bold" style={{ color: bgImage ? "white" : "inherit" }}>{title}</h5>
        </div>
        <div className="card-grid-container">
          {Array.from({ length: limit }).map((_, idx) => (
            <div key={idx} className={colClass}>
              <div className="card text-center bd p-1 w-100 bg-white" style={{ border: "none" }}>
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
    return <div className="text-center text-danger p-3">Error: {error}</div>;
  }

  if (products.length === 0) return null;

  return (
    <div className="container-fluid px-1 px-lg-0 home-layout-gap" style={containerStyle}>
      {(title || showViewAll) && (
        <div className="d-flex justify-content-between align-items-center mb-1 mb-lg-3" style={{ paddingLeft: bgImage ? "8px" : "0", paddingRight: bgImage ? "8px" : "0" }}>
          {title && (
            <h5 
              className="mb-0 fw-bold" 
              style={{ color: bgImage ? "white" : "inherit", paddingTop: bgImage ? "8px" : "0" }}
            >
              {title}
            </h5>
          )}
          {showViewAll && (
            <Link
              to="/cart"
              className="d-flex align-items-center justify-content-center rounded bg-theme px-2 py-1 text-white text-decoration-none me-1"
              style={{ fontSize: '0.75rem', height: '24px', whiteSpace: 'nowrap' }}
            >
              View All <MdKeyboardArrowRight size={16} />
            </Link>
          )}
        </div>
      )}
      <div className="card-grid-container">
        {products.map((product) => (
          <div
            key={product.id}
            className={colClass}
          >
            <ProductCard
              product={product}
              variant="compact-grid"
            />
          </div>
        ))}
      </div>
    </div>
  );
}
