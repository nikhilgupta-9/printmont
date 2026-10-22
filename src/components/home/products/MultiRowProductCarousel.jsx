import React, { useRef } from "react";
import { Link } from "react-router-dom";
import { MdKeyboardArrowRight } from "react-icons/md";
import useHomeProducts from "../hooks/useHomeProducts";
import ProductCard from "./ProductCard";

/**
 * MultiRowProductCarousel — Renders multiple independent horizontal carousel rows of products.
 * Used for Bestseller section on mobile where each of the 4 rows scrolls/swipes separately.
 *
 * @param {string} title Section title.
 * @param {Array} [products] Initial static products.
 * @param {string} [apiUrl] API endpoint to fetch products from.
 * @param {number} [rows=4] Number of independent carousel rows.
 * @param {boolean} [showViewAll=true] Display top-right View All link.
 * @param {string} [viewAllUrl] View All route destination.
 * @param {string} [badgeText] Custom badge text.
 */
export default function MultiRowProductCarousel({
  title = "Bestseller",
  products: initialProducts = [],
  apiUrl,
  rows = 4,
  showViewAll = true,
  viewAllUrl = "/allproducts?filter=bestseller",
  badgeText = ""
}) {
  const { products, loading, error } = useHomeProducts(apiUrl, initialProducts, 24);

  // Divide products into `rows` separate arrays
  const chunkedRows = React.useMemo(() => {
    if (!products || products.length === 0) return [];
    
    // Distribute products evenly across the 4 rows
    const result = Array.from({ length: rows }, () => []);
    products.forEach((prod, index) => {
      const rowIndex = index % rows;
      result[rowIndex].push(prod);
    });

    // Filter out any empty rows
    return result.filter(r => r.length > 0);
  }, [products, rows]);

  if (loading && products.length === 0) {
    return (
      <div className="container-fluid px-1 px-lg-0 my-2">
        <div className="d-flex justify-content-between align-items-center mb-2 px-1">
          <h5 className="mb-0 fw-bold">{title}</h5>
        </div>
        {Array.from({ length: rows }).map((_, rIdx) => (
          <div key={rIdx} className="d-flex flex-nowrap gap-2 overflow-hidden mb-2 px-1">
            {Array.from({ length: 3 }).map((_, cIdx) => (
              <div
                key={cIdx}
                className="shimmer-bg rounded border"
                style={{ flex: "0 0 44%", height: "180px" }}
              />
            ))}
          </div>
        ))}
      </div>
    );
  }

  if (error && products.length === 0) {
    return null;
  }

  if (products.length === 0) {
    return null;
  }

  return (
    <div className="multi-row-product-carousel my-2 px-1">
      {/* Section Header */}
      <div className="d-flex justify-content-between align-items-center mb-2 px-1">
        <h5 className="mb-0 fw-bold text-dark" style={{ fontSize: "1.05rem" }}>
          {title}
        </h5>
        {showViewAll && (
          <Link
            to={viewAllUrl}
            className="d-flex align-items-center justify-content-center rounded bg-theme px-2 py-1 text-white text-decoration-none me-1"
            style={{ fontSize: "0.75rem", height: "24px", whiteSpace: "nowrap" }}
          >
            View All <MdKeyboardArrowRight size={16} />
          </Link>
        )}
      </div>

      {/* 4 Separate Horizontal Carousel Rows */}
      <div className="d-flex flex-column">
        {chunkedRows.map((rowProducts, rowIndex) => (
          <div
            key={rowIndex}
            className="d-flex flex-nowrap align-items-stretch overflow-x-scroll py-1"
            style={{
              scrollbarWidth: "none",
              msOverflowStyle: "none",
              gap: "8px",
              WebkitOverflowScrolling: "touch",
              scrollSnapType: "x mandatory"
            }}
          >
            {rowProducts.map((product, pIndex) => (
              <div
                key={product.id || `${rowIndex}-${pIndex}`}
                style={{
                  flex: "0 0 calc((100% - 8px) / 2)",
                  width: "calc((100% - 8px) / 2)",
                  scrollSnapAlign: "start"
                }}
              >
                <ProductCard
                  product={product}
                  variant="compact-grid"
                  badgeText={badgeText}
                />
              </div>
            ))}

          </div>
        ))}
      </div>
    </div>
  );
}
