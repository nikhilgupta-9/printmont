import React from "react";
import useHomeProducts from "../hooks/useHomeProducts";
import ProductCard from "./ProductCard";

/**
 * Replaces ProductGrid. Displays a grid of up to 6 products.
 * @param {string} title Grid section title.
 * @param {Array} [products] Initial static products.
 * @param {string} [apiUrl] API endpoint to fetch products from.
 * @param {string} [bgImage] Optional background image path.
 */
export default function CompactProductGrid({
  title,
  products: initialProducts = [],
  apiUrl,
  bgImage
}) {
  const { products, loading, error } = useHomeProducts(apiUrl, initialProducts, 6);

  const containerStyle = {
    backgroundImage: bgImage ? `url(${bgImage})` : "none",
    backgroundSize: "cover",
    backgroundPosition: "center",
    padding: bgImage ? "1rem" : "0",
    borderRadius: bgImage ? "0.375rem" : "0"
  };

  if (loading && products.length === 0) {
    return (
      <div className="container-fluid px-1 px-lg-0 home-layout-gap" style={containerStyle}>
        <h5 className="mb-1 mb-lg-3">{title}</h5>
        <div className="card-grid-container">
          {Array.from({ length: 6 }).map((_, idx) => (
            <div key={idx} className="card-grid-item-3col">
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
      <h5 className="mb-1 mb-lg-3">{title}</h5>
      <div className="card-grid-container">
        {products.map((product) => (
          <div
            key={product.id}
            className="card-grid-item-3col"
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
