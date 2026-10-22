import React, { useState, useEffect, useRef } from "react";
import { Link } from "react-router-dom";
import { API_ENDPOINTS, resolveImageUrl } from "../../../config/apiEndpoints";

const fallbackCategories = [
  { id: 1, name: "Electronics", slug: "electronics", image: "/electro/mobile-1.jpeg" },
  { id: 2, name: "Men's Wear", slug: "mens-wear", image: "/girl-product-img/subsubcat-104.jpeg" },
  { id: 3, name: "Earbuds", slug: "earbuds-audio", image: "/electro/buds-1.jpeg" },
  { id: 4, name: "Home Decor", slug: "home-decor", image: "/girl-product-img/plant-1.jpeg" },
  { id: 5, name: "Women's Wear", slug: "womens-wear", image: "/women-dress/women-dress-1.jpeg" },
  { id: 6, name: "Watches", slug: "smart-watches", image: "/electro/watch-1.jpeg" },
  { id: 7, name: "Stationery", slug: "stationery", image: "/section-img/pro12.jpeg" },
  { id: 8, name: "Custom Gifts", slug: "custom-gifts", image: "/men_shirt/men-shirt-2.jpeg" }
];

/**
 * CategorySlideRow — Standalone category row below banner and above bestseller.
 * Shows exactly 4 full cards and 1 half card (4.5 visible) in a horizontal sliding row.
 *
 * @param {string} [title] Optional title (default: null/hidden for pure category strip)
 * @param {Array} [categories] Custom category list override
 */
export default function CategorySlideRow({
  title,
  categories: initialCategories
}) {
  const [categories, setCategories] = useState(initialCategories || []);
  const [loading, setLoading] = useState(!initialCategories);
  const scrollRef = useRef(null);

  useEffect(() => {
    if (initialCategories && initialCategories.length > 0) {
      setCategories(initialCategories);
      setLoading(false);
      return;
    }

    let isMounted = true;
    const fetchCategories = async () => {
      try {
        setLoading(true);
        const res = await fetch(API_ENDPOINTS.CATEGORIES);
        if (!res.ok) throw new Error("Failed to fetch categories");
        const data = await res.json();

        let list = [];
        if (data && data.success && Array.isArray(data.data)) {
          list = data.data;
        } else if (Array.isArray(data)) {
          list = data;
        } else if (data && Array.isArray(data.categories)) {
          list = data.categories;
        }

        if (isMounted) {
          setCategories(list.length > 0 ? list : fallbackCategories);
        }
      } catch (e) {
        if (isMounted) {
          setCategories(fallbackCategories);
        }
      } finally {
        if (isMounted) setLoading(false);
      }
    };

    fetchCategories();
    return () => { isMounted = false; };
  }, [initialCategories]);

  return (
    <div className="category-slide-row-wrapper my-2 px-1 w-100 bg-white py-2">
      {title && (
        <div className="d-flex justify-content-between align-items-center mb-2 px-2">
          <h6 className="fw-bold mb-0 text-dark" style={{ fontSize: "0.95rem" }}>
            {title}
          </h6>
          <Link
            to="/allproducts"
            className="text-primary text-decoration-none fw-semibold"
            style={{ fontSize: "0.75rem" }}
          >
            View All &gt;
          </Link>
        </div>
      )}

      {/* 4.5 Item Horizontal Scroll Container */}
      <div
        ref={scrollRef}
        className="d-flex flex-nowrap align-items-start overflow-x-scroll px-1"
        style={{
          scrollbarWidth: "none",
          msOverflowStyle: "none",
          gap: "8px",
          WebkitOverflowScrolling: "touch",
          scrollSnapType: "x mandatory"
        }}
      >
        {(loading ? fallbackCategories : categories).map((cat, idx) => {
          const imgSrc = resolveImageUrl(cat.image || cat.icon || "/default-img.jpg");
          const catName = cat.name || cat.title || "Category";

          return (
            <Link
              key={cat.id || idx}
              to={`/allproducts?category=${encodeURIComponent(catName)}`}
              className="d-flex flex-column align-items-center text-decoration-none text-dark flex-shrink-0 pm-cat-slide-item"
              style={{
                width: "calc((100% - 32px) / 4.5)", // Exactly 4 full + 1 half card visible
                minWidth: "70px",
                maxWidth: "88px",
                scrollSnapAlign: "start",
                transition: "transform 0.2s ease"
              }}
            >
              {/* Category Circle */}
              <div
                className="bg-white rounded-circle border shadow-xs d-flex align-items-center justify-content-center p-1"
                style={{
                  width: "58px",
                  height: "58px",
                  overflow: "hidden",
                  border: "2px solid #ffffff",
                  boxShadow: "0 2px 8px rgba(0, 0, 0, 0.08)",
                  transition: "transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease"
                }}
              >
                <img
                  src={imgSrc}
                  alt={catName}
                  loading="lazy"
                  className="w-100 h-100 rounded-circle"
                  style={{ objectFit: "cover", transition: "transform 0.2s ease" }}
                  onError={(e) => {
                    e.target.onerror = null;
                    e.target.src = "/default-img.jpg";
                  }}
                />
              </div>

              {/* Category Name */}
              <span
                className="mt-1 text-center text-truncate fw-semibold text-dark w-100"
                style={{
                  fontSize: "0.75rem",
                  lineHeight: "1.2",
                  maxHeight: "2.3em",
                  color: "#1e293b",
                  letterSpacing: "-0.1px"
                }}
                title={catName}
              >
                {catName}
              </span>
            </Link>
          );
        })}
        {/* Trailing spacer for smooth scrolling */}
        <div style={{ width: "10px", flexShrink: 0 }} />
      </div>
    </div>
  );
}
