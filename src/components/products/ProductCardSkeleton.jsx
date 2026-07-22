import React from "react";
import "./Product.css";

export function ProductCardSkeleton() {
  return (
    <div className="ap-card ap-skeleton-card">
      <div className="ap-skeleton-img-box ap-shimmer" />
      <div className="ap-skeleton-body p-2">
        <div className="ap-skeleton-line short ap-shimmer mb-1" />
        <div className="ap-skeleton-line medium ap-shimmer mb-2" />
        <div className="ap-skeleton-line long ap-shimmer mb-2" />
        <div className="d-flex justify-content-between align-items-center mt-2">
          <div className="ap-skeleton-line short ap-shimmer" style={{ width: "40%" }} />
          <div className="ap-skeleton-line short ap-shimmer" style={{ width: "25%" }} />
        </div>
      </div>
    </div>
  );
}

export function FilterSkeleton() {
  return (
    <div className="ap-filter-skeleton p-3 bg-white">
      <div className="ap-skeleton-line medium ap-shimmer mb-3" style={{ height: "20px" }} />
      <div className="ap-skeleton-line short ap-shimmer mb-2" style={{ height: "14px" }} />
      <div className="ap-skeleton-line short ap-shimmer mb-4" style={{ height: "14px" }} />

      <hr className="my-3 text-muted opacity-25" />

      {[1, 2, 3, 4].map((i) => (
        <div key={i} className="mb-4">
          <div className="ap-skeleton-line medium ap-shimmer mb-2" style={{ height: "16px", width: "60%" }} />
          <div className="ap-skeleton-line short ap-shimmer mb-1" style={{ height: "12px", width: "80%" }} />
          <div className="ap-skeleton-line short ap-shimmer mb-1" style={{ height: "12px", width: "70%" }} />
          <div className="ap-skeleton-line short ap-shimmer" style={{ height: "12px", width: "50%" }} />
        </div>
      ))}
    </div>
  );
}

export default ProductCardSkeleton;
