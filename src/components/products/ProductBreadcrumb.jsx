import React from "react";
import { Link } from "react-router-dom";
import "./ProductBreadcrumb.css";

// Flipkart-style trail: Home › Category › Sub › Sub-sub › Brand Leaf › Product title
// `productData.breadcrumb` comes from product-api.php (category chain walked via parent_id).
const ProductBreadcrumb = ({ productData, isMobile = false }) => {
  if (!productData) return null;

  const trail = Array.isArray(productData.breadcrumb) && productData.breadcrumb.length
    ? productData.breadcrumb
    : productData.category_name
      ? [{ id: productData.category_id, name: productData.category_name, slug: null }]
      : [];

  const crumbs = [{ label: "Home", to: "/" }];
  trail.forEach((c) => {
    crumbs.push({
      label: c.name,
      to: c.slug ? `/category/${c.slug}` : `/allproducts?category=${encodeURIComponent(c.name)}`,
    });
  });

  // Brand crumb, e.g. "Fire-Boltt Smart Watches"
  const brand = (productData.brand || "").trim();
  if (brand) {
    const leaf = trail.length ? trail[trail.length - 1].name : "";
    const label = leaf ? `${brand} ${leaf}` : brand;
    crumbs.push({ label, to: `/allproducts?q=${encodeURIComponent(label)}` });
  }

  return (
    <nav aria-label="breadcrumb" className={`pm-breadcrumb ${isMobile ? "pm-breadcrumb--mobile" : ""}`}>
      {crumbs.map((c, i) => (
        <React.Fragment key={`${c.label}-${i}`}>
          <Link to={c.to} className="pm-breadcrumb__link">{c.label}</Link>
          <svg className="pm-breadcrumb__sep" width="6" height="10" viewBox="0 0 6 10" aria-hidden="true">
            <path d="M1 1l4 4-4 4" fill="none" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </React.Fragment>
      ))}
      <span className="pm-breadcrumb__current" title={productData.title}>{productData.title}</span>
    </nav>
  );
};

export default ProductBreadcrumb;
