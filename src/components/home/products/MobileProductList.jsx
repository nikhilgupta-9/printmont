import React from "react";
import useHomeProducts from "../hooks/useHomeProducts";
import ProductCard from "./ProductCard";

/**
 * Replaces ProductList. Displays up to 6 products in a flexible row.
 * @param {Array} [products] Initial static products.
 * @param {string} [apiUrl] API endpoint to fetch products from.
 */
export default function MobileProductList({
  products: initialProducts = [],
  apiUrl
}) {
  const { products, loading, error } = useHomeProducts(apiUrl, initialProducts, 6);

  if (loading && products.length === 0) {
    return (
      <div className="bg-white container-fluid p-0 m-0 home-layout-gap">
        <div className="card-grid-container">
          {Array.from({ length: 4 }).map((_, idx) => (
            <div className="card-grid-item-2col" key={idx}>
              <div className="border rounded p-3 text-center bg-white">
                <div className="shimmer-bg skeleton-img w-100 mb-2" style={{ height: "120px" }} />
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
    <div className="bg-white container-fluid p-0 m-0 home-layout-gap">
      <div className="card-grid-container">
        {products.map((product, index) => (
          <div
            key={index}
            className="card-grid-item-2col"
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
