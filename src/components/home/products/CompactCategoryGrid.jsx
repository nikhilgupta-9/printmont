import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { MdKeyboardArrowRight } from "react-icons/md";
import { API_ENDPOINTS, ASSET_URL, resolveImageUrl } from "../../../config/apiEndpoints";

// Fallback dummy categories in case the API has no categories or fails
const fallbackCategories = [
  { id: 1, name: "Sub Categories Name", slug: "decor-1", image: "/card/home-dec-1.jpeg" },
  { id: 2, name: "Sub Categories Name", slug: "decor-2", image: "/card/home-dec-2.jpeg" },
  { id: 3, name: "Sub Categories Name", slug: "decor-3", image: "/card/home-dec-3.jpeg" },
  { id: 4, name: "Sub Categories Name", slug: "decor-4", image: "/card/home-dec-4.jpeg" },
  { id: 5, name: "Sub Categories Name", slug: "decor-5", image: "/card/home-dec-1.jpeg" },
  { id: 6, name: "Sub Categories Name", slug: "decor-6", image: "/card/home-dec-2.jpeg" }
];

const offerLabels = [
  "Under ₹299",
  "From ₹499",
  "Up to 60% Off"
];

export default function CompactCategoryGrid({
  title,
  bgImage,
  bgColor,
  columns = 3,
  limit = 6,
  showViewAll = false,
  showBottomViewAll = false,
  offerText
}) {
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchCategories = async () => {
      try {
        setLoading(true);
        const response = await fetch(API_ENDPOINTS.CATEGORIES);
        const data = await response.json();
        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
          setCategories(data.data.slice(0, limit));
        } else {
          setCategories(fallbackCategories.slice(0, limit));
        }
      } catch (err) {
        console.error("Error fetching categories in grid:", err);
        setCategories(fallbackCategories.slice(0, limit));
      } finally {
        setLoading(false);
      }
    };

    fetchCategories();
  }, [limit]);

  const hasDarkBg = bgImage || (bgColor && bgColor !== "white" && bgColor !== "transparent" && bgColor !== "#ffffff");

  const containerStyle = {
    backgroundImage: bgImage ? `url(${bgImage})` : "none",
    backgroundColor: bgColor || "transparent",
    backgroundSize: "cover",
    backgroundPosition: "center",
    padding: (bgImage || bgColor) ? "1rem" : "0",
    borderRadius: "0.375rem"
  };

  const colClass = columns === 2 ? "card-grid-item-2col" : "card-grid-item-3col";

  const getImageUrl = (img) => {
    return resolveImageUrl(img);
  };

  if (loading) {
    return (
      <div className="container-fluid px-1 px-lg-0 home-layout-gap" style={containerStyle}>
        <div className="d-flex justify-content-between align-items-center mb-1 mb-lg-3">
          <h5 className="mb-0 fw-bold" style={{ color: hasDarkBg ? "white" : "inherit" }}>{title}</h5>
        </div>
        <div className="card-grid-container">
          {Array.from({ length: limit }).map((_, idx) => (
            <div key={idx} className={colClass}>
              <div className="card text-center bd p-1 w-100 bg-white" style={{ border: "none", borderRadius: "8px" }}>
                <div className="shimmer-bg skeleton-img w-100 mb-2" style={{ aspectRatio: "1/1" }} />
                <div className="shimmer-bg skeleton-title w-75 mx-auto" />
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  if (categories.length === 0) return null;

  return (
    <div className="container-fluid px-1 px-lg-0 home-layout-gap" style={containerStyle}>
      <div 
        className="d-flex justify-content-between align-items-center mb-1 mb-lg-3" 
        style={{ paddingLeft: (bgImage || bgColor) ? "8px" : "0", paddingRight: (bgImage || bgColor) ? "8px" : "0" }}
      >
        <h5 
          className="mb-0 fw-bold" 
          style={{ color: hasDarkBg ? "white" : "inherit", paddingTop: (bgImage || bgColor) ? "8px" : "0" }}
        >
          {title}
        </h5>
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
      
      <div className="card-grid-container">
        {categories.map((category, index) => (
          <div
            key={category.id || index}
            className={colClass}
          >
            <Link 
              to={`/products?category=${category.slug}`} 
              className="card text-center bd p-1 w-100 bg-white text-decoration-none text-dark" 
              style={{ border: "none", borderRadius: "8px" }}
            >
              <div className="product-card-img-wrapper" style={{ overflow: "hidden", aspectRatio: "1/1", display: "flex", justifyContent: "center", alignItems: "center", flexShrink: 0, backgroundColor: "#ffffff" }}>
                <img 
                  src={getImageUrl(category.image || category.icon)} 
                  alt={category.name} 
                  className="product-card-img" 
                  style={{ width: "100%", height: "100%", objectFit: "cover" }} 
                />
              </div>
              <div className="p-1 text-center mt-1">
                <p 
                  className="m-0 fw-semibold text-truncate product-card-title" 
                  style={{ fontSize: "0.72rem", lineHeight: "1.2" }}
                >
                  {category.name}
                </p>
                <span 
                  className="text-success fw-bold product-card-discount-text" 
                  style={{ fontSize: "0.65rem" }}
                >
                  {offerText || offerLabels[index % offerLabels.length]}
                </span>
              </div>
            </Link>
          </div>
        ))}
      </div>

      {showBottomViewAll && (
        <div className="p-1 mt-2">
          <div className="d-flex w-100 justify-content-center align-items-center border bd rounded bg-light">
            <Link
              to="/cart"
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
