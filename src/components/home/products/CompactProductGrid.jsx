import React from "react";
import { Link } from "react-router-dom";
import { MdKeyboardArrowRight } from "react-icons/md";
import useHomeProducts from "../hooks/useHomeProducts";
import ProductCard from "./ProductCard";
import ThreeColProductCard from "./ThreeColProductCard";

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
  columns = 2,
  limit = 8,
  showViewAll = true,
  showBottomViewAll = true,
  viewAllUrl
}) {

  const { products, loading, error } = useHomeProducts(apiUrl, initialProducts, limit);

  const containerStyle = {
    backgroundImage: bgImage ? `url(${bgImage})` : "none",
    backgroundSize: "cover",
    backgroundPosition: "center",
    padding: bgImage ? "1rem" : "0",
    borderRadius: bgImage ? "0.375rem" : "0"
  };

  const is3Col = Number(columns) === 3;
  const colClass = is3Col ? "card-grid-item-3col" : (Number(columns) === 1 ? "card-grid-item-1col" : "card-grid-item-2col");

  if (loading && products.length === 0) {
    return (
      <div className="w-100 m-0" style={containerStyle}>
        <div className="pm-home-section-header mb-2">
          <h3 className="pm-home-section-title" style={{ color: bgImage ? "white" : "#1e293b" }}>{title}</h3>
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
    <div className="w-100 m-0" style={containerStyle}>
      {(title || showViewAll) && (
        <div className="pm-home-section-header mb-2" style={{ paddingLeft: bgImage ? "8px" : "0", paddingRight: bgImage ? "8px" : "0" }}>
          {title && (
            <h3 
              className="pm-home-section-title" 
              style={{ color: bgImage ? "white" : "#1e293b", paddingTop: bgImage ? "8px" : "0" }}
            >
              {title}
            </h3>
          )}
          {showViewAll && (
            <Link
              to={`/allproducts?q=${encodeURIComponent(title || '')}`}
              className="pm-home-view-all-btn"
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
            {is3Col ? (
              <ThreeColProductCard product={product} />
            ) : (
              <ProductCard
                product={product}
                variant="compact-grid"
              />
            )}
          </div>
        ))}
      </div>

      {showBottomViewAll && (
        <div className="p-1 mt-2">
          <div className="d-flex w-100 justify-content-center align-items-center border bd rounded bg-light">
            <Link
              to={viewAllUrl || `/allproducts?q=${encodeURIComponent(title || '')}`}
              className="w-100 py-2 text-center text-decoration-none text-dark fs-6 fw-semibold d-flex align-items-center justify-content-center"
            >
              View all <MdKeyboardArrowRight size={18} />
            </Link>
          </div>
        </div>
      )}
    </div>
  );
}

