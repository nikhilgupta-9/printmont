import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { MdKeyboardArrowRight } from "react-icons/md";
import { API_ENDPOINTS, ASSET_URL, resolveImageUrl } from "../../../config/apiEndpoints";

// Fallback dummy categories in case the API has no categories or fails
const fallbackCategories = [
  { id: 1, name: "Home Decor", slug: "decor-1", image: "/card/home-dec-1.jpeg" },
  { id: 2, name: "Tableware", slug: "decor-2", image: "/card/home-dec-2.jpeg" },
  { id: 3, name: "Earbuds", slug: "decor-3", image: "/card/home-dec-3.jpeg" },
  { id: 4, name: "Mobiles", slug: "decor-4", image: "/card/home-dec-4.jpeg" },
  { id: 5, name: "Apparel", slug: "decor-5", image: "/card/home-dec-1.jpeg" },
  { id: 6, name: "Footwear", slug: "decor-6", image: "/card/home-dec-2.jpeg" },
  { id: 7, name: "Accessories", slug: "decor-7", image: "/card/home-dec-3.jpeg" },
  { id: 8, name: "Gifts", slug: "decor-8", image: "/card/home-dec-4.jpeg" },
  { id: 9, name: "Office Kit", slug: "decor-9", image: "/card/home-dec-1.jpeg" }
];

const offerLabels = [
  "Under ₹299",
  "From ₹499",
  "Up to 60% Off",
  "Min. 40% Off",
  "Special Deal",
  "Under ₹199",
  "Flat 50% Off",
  "From ₹399",
  "Top Rated"
];

export default function CompactCategoryGrid({
  title,
  categoryId,
  subCategoryIds,
  bgImage,
  bgColor,
  columns = 2,
  limit = 4,
  showViewAll = true,
  showBottomViewAll = true,
  offerText
}) {
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchCategories = async () => {
      try {
        setLoading(true);
        let catList = [];

        // 1. If specific subCategoryIds are configured, fetch them directly by IDs
        if (subCategoryIds && typeof subCategoryIds === 'string' && subCategoryIds.trim() !== '') {
          try {
            const idsRes = await fetch(API_ENDPOINTS.LAYOUT_SUBCATEGORIES(0, subCategoryIds.trim()));
            const idsData = await idsRes.json();
            if (idsData && idsData.success && Array.isArray(idsData.data) && idsData.data.length > 0) {
              catList = idsData.data;
            }
          } catch (e) {
            console.warn("Direct subCategoryIds fetch failed, trying category parent:", e);
          }
        }

        // 2. If categoryId is specified and no list yet, fetch its full subcategories (level 2 & 3)
        if (catList.length === 0 && categoryId) {
          try {
            const subRes = await fetch(API_ENDPOINTS.LAYOUT_SUBCATEGORIES(categoryId));
            const subData = await subRes.json();
            if (subData && subData.success && Array.isArray(subData.data) && subData.data.length > 0) {
              catList = subData.data;
            }
          } catch (e) {
            console.warn("Layout subcategories fetch error, trying legacy:", e);
          }

          if (catList.length === 0) {
            const legacyRes = await fetch(API_ENDPOINTS.SUBCATEGORIES(categoryId));
            const legacyData = await legacyRes.json();
            if (legacyData && legacyData.success && Array.isArray(legacyData.data)) {
              catList = legacyData.data;
            } else if (Array.isArray(legacyData)) {
              catList = legacyData;
            }
          }
        }

        // 3. If still empty, fetch general categories
        if (catList.length === 0) {
          const response = await fetch(API_ENDPOINTS.CATEGORIES);
          const data = await response.json();
          if (data && data.success && data.data) {
            catList = Array.isArray(data.data) ? data.data : (typeof data.data === 'object' ? Object.values(data.data) : []);
          } else if (Array.isArray(data)) {
            catList = data;
          } else if (data && data.categories) {
            catList = Array.isArray(data.categories) ? data.categories : (typeof data.categories === 'object' ? Object.values(data.categories) : []);
          } else if (data && typeof data === 'object' && !data.error) {
            catList = Object.values(data);
          }
        }

        // 4. Filter by subCategoryIds if list wasn't already filtered
        if (subCategoryIds && typeof subCategoryIds === 'string' && subCategoryIds.trim() !== '' && catList.length > 0) {
          const selectedSubIds = subCategoryIds.split(',').map(s => s.trim());
          const catMap = new Map(catList.map(c => [String(c.id), c]));
          const filteredSubs = selectedSubIds.map(id => catMap.get(id)).filter(Boolean);
          if (filteredSubs.length > 0) {
            catList = filteredSubs;
          }
        }

        if (catList.length > 0) {
          setCategories(catList.slice(0, limit));
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
  }, [categoryId, subCategoryIds, limit]);

  const is3Col = Number(columns) === 3;
  const colClass = is3Col ? "card-grid-item-3col" : (Number(columns) === 1 ? "card-grid-item-1col" : "card-grid-item-2col");

  const hasDarkBg = bgImage || (bgColor && bgColor !== "white" && bgColor !== "transparent" && bgColor !== "#ffffff");

  const containerStyle = {
    backgroundImage: bgImage ? `url(${resolveImageUrl(bgImage)})` : "none",
    backgroundColor: bgColor || (bgImage ? "transparent" : "#00a8ec"),
    backgroundSize: "cover",
    backgroundPosition: "center",
    padding: "0.75rem",
    borderRadius: "0.5rem"
  };

  const getImageUrl = (cat) => {
    if (!cat) return "/default-img.jpg";
    if (typeof cat === "string") {
      if (cat.startsWith("fa") || cat.includes("fa-")) return "/default-img.jpg";
      return resolveImageUrl(cat);
    }
    const raw = cat.image_url || cat.image || (cat.images && (cat.images.image || cat.images.desktop || cat.images.mobile)) || (cat.icon && !cat.icon.startsWith("fa") ? cat.icon : "") || "";
    return resolveImageUrl(raw);
  };

  if (loading) {
    return (
      <div className="w-100 m-0" style={containerStyle}>
        <div className="pm-home-section-header mb-2">
          <h3 className="pm-home-section-title" style={{ color: hasDarkBg ? "#ffffff" : "#1e293b" }}>{title}</h3>
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
    <div className="w-100 m-0" style={containerStyle}>
      <div className="pm-home-section-header mb-2">
        <h3 
          className="pm-home-section-title" 
          style={{ color: hasDarkBg ? "#ffffff" : "#1e293b" }}
        >
          {title}
        </h3>
        {showViewAll && (
          <Link
            to={categoryId ? `/allproducts?category=${encodeURIComponent(categoryId)}` : `/allproducts?q=${encodeURIComponent(title || '')}`}
            className={hasDarkBg ? "pm-home-view-all-pill" : "pm-home-view-all-btn"}
          >
            View All <MdKeyboardArrowRight size={14} className="ms-0.5" />
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
              to={`/allproducts?category=${encodeURIComponent(category.name || category.slug || category.id || '')}`} 
              className="card text-center bd p-1.5 w-100 bg-white text-decoration-none text-dark shadow-sm d-flex flex-column h-100" 
              style={{ border: "none", borderRadius: "8px", transition: "transform 0.15s" }}
            >
              <div className="product-card-img-wrapper" style={{ overflow: "hidden", aspectRatio: "1/1", display: "flex", justifyContent: "center", alignItems: "center", flexShrink: 0, backgroundColor: "#f8fafc", borderRadius: "6px" }}>
                <img 
                  src={getImageUrl(category)} 
                  alt={category.name} 
                  className="product-card-img zoom-hover" 
                  style={{ width: "100%", height: "100%", objectFit: "cover" }} 
                  onError={(e) => {
                    e.target.onerror = null;
                    e.target.src = "/default-img.jpg";
                  }}
                />
              </div>
              <div className="p-1 text-center mt-1 border-top" style={{ borderColor: "#f1f5f9" }}>
                <p
                  className="m-0 fw-bold text-truncate"
                  style={{ fontSize: "12px", color: "#1e293b", lineHeight: "1.2" }}
                >
                  {category.name}
                </p>
                <span className="fw-bold" style={{ fontSize: "11px", color: "#059669" }}>
                  {offerText || offerLabels[index % offerLabels.length]}
                </span>
              </div>
            </Link>
          </div>
        ))}
      </div>

      {showBottomViewAll && (
        <div className="p-0 mt-2">
          <Link
            to={categoryId ? `/allproducts?category=${encodeURIComponent(categoryId)}` : `/allproducts?q=${encodeURIComponent(title || '')}`}
            className="w-100 py-1.5 text-center text-decoration-none bg-white rounded shadow-sm fs-7 fw-bold d-flex align-items-center justify-content-center"
            style={{ color: "#334155", fontSize: "12px", border: "1px solid #e2e8f0" }}
          >
            View all <MdKeyboardArrowRight size={16} className="ms-1 text-muted" />
          </Link>
        </div>
      )}
    </div>
  );
}
