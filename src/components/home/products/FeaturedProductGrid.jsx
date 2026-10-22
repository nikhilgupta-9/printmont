import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { FaChevronRight } from "react-icons/fa";
import { getProductUrl } from "../../../utils/seo";
import { API_ENDPOINTS, resolveImageUrl } from "../../../config/apiEndpoints";

/**
 * FeaturedProductGrid Component
 * - On Mobile: Displays 3 products (Left large + Right 2 stacked with equal image sizes matching the left).
 * - On Desktop: Displays standard 3-column category grid blocks.
 * @param {Array} data List of categories with mainItem and sideItems.
 * @param {Object} [section] Layout section object from home_sections.
 * @param {boolean} [isMobile=false] Whether rendering on mobile view.
 */
export default function FeaturedProductGrid({ data = [], section, isMobile = false }) {
  const [gridData, setGridData] = useState(data);

  useEffect(() => {
    const fetchDynamicData = async () => {
      if (!section) {
        if (data && data.length > 0) setGridData(data);
        return;
      }

      // 1. If sub_category_ids are specified
      if (section.sub_category_ids && typeof section.sub_category_ids === 'string' && section.sub_category_ids.trim() !== '') {
        try {
          const res = await fetch(API_ENDPOINTS.LAYOUT_SUBCATEGORIES(0, section.sub_category_ids.trim()));
          const json = await res.json();
          if (json && json.success && Array.isArray(json.data) && json.data.length >= 2) {
            const subs = json.data;
            const offer = section.badge_text || "Min. 30% off";
            const main = {
              name: subs[0].name,
              img: subs[0].image_url || "/default-img.jpg",
              offer: offer,
              slug: subs[0].slug || subs[0].id
            };
            const side = subs.slice(1, 3).map(s => ({
              name: s.name,
              img: s.image_url || "/default-img.jpg",
              offer: offer,
              slug: s.slug || s.id
            }));
            setGridData([{
              title: section.label || "Top Selection (Category)",
              mainItem: main,
              sideItems: side
            }]);
            return;
          }
        } catch (e) {
          console.warn("FeaturedProductGrid subcategories fetch failed:", e);
        }
      }

      // 2. If product_ids are specified
      if (section.product_ids && typeof section.product_ids === 'string' && section.product_ids.trim() !== '') {
        try {
          const res = await fetch(`${API_ENDPOINTS.HOME_LAYOUT_API || 'http://localhost/printmont/printmont-backend/api/home-layout-api.php'}?action=get_products_by_ids&ids=${encodeURIComponent(section.product_ids.trim())}`);
          const json = await res.json();
          if (json && json.success && Array.isArray(json.data) && json.data.length >= 2) {
            const prods = json.data;
            const offer = section.badge_text || "Min. 30% off";
            const main = {
              name: prods[0].name,
              img: prods[0].thumbnail_image || "/default-img.jpg",
              offer: offer,
              id: prods[0].id
            };
            const side = prods.slice(1, 3).map(p => ({
              name: p.name,
              img: p.thumbnail_image || "/default-img.jpg",
              offer: offer,
              id: p.id
            }));
            setGridData([{
              title: section.label || "Top Selection (Category)",
              mainItem: main,
              sideItems: side
            }]);
            return;
          }
        } catch (e) {
          console.warn("FeaturedProductGrid products fetch failed:", e);
        }
      }

      if (data && data.length > 0) {
        setGridData(data);
      }
    };

    fetchDynamicData();
  }, [section, data]);

  if (!gridData || gridData.length === 0) return null;

  const getSafeLink = (item) => {
    if (!item) return "/allproducts";
    if (item.slug) return `/allproducts?category=${encodeURIComponent(item.slug)}`;
    const url = getProductUrl(item);
    return url && url !== "#" ? url : "/allproducts";
  };

  const isMobileView = isMobile || (typeof window !== 'undefined' && window.innerWidth < 992);

  // --- MOBILE VIEW LAYOUT ---
  if (isMobileView) {
    return (
      <div className="w-100 m-0 p-0">
        <div 
          className="d-flex flex-column m-0 p-0"
          style={{ gap: "var(--home-gap, 10px)" }}
        >
          {gridData.map((cat, idx) => {
            const mainItem = cat.mainItem || (cat.items && cat.items[0]);
            const sideItems = (cat.sideItems && cat.sideItems.length > 0) 
              ? cat.sideItems.slice(0, 2) 
              : ((cat.items && cat.items.slice(1, 3)) || []);

            return (
              <div key={idx} className="col-12 p-0">
                <div 
                  className="border bg-white rounded-3 d-flex flex-column shadow-sm"
                  style={{ overflow: "hidden" }}
                >
                  {/* Header */}
                  <div 
                    className="d-flex justify-content-between align-items-center border-bottom"
                    style={{ padding: "8px 12px" }}
                  >
                    <h4 
                      className="m-0 section-title fw-bold text-black" 
                      style={{ fontSize: "15px", letterSpacing: "-0.2px" }}
                    >
                      {cat.title || section?.label || "Top Selection (Category)"}
                    </h4>
                    <Link 
                      to="/allproducts"
                      className="btn btn-sm text-white text-decoration-none fw-bold d-inline-flex align-items-center shadow-sm"
                      style={{ 
                        backgroundColor: "#2e3891", 
                        borderColor: "#2e3891", 
                        fontSize: "11px", 
                        borderRadius: "4px",
                        padding: "3px 8px",
                        lineHeight: "1.2"
                      }}
                      aria-label="View all items"
                    >
                      View All <span className="ms-1" style={{ fontSize: "12px", lineHeight: "1" }}>»</span>
                    </Link>
                  </div>

                  {/* Content: 3 Products with Equal Image Dimensions */}
                  <div className="d-flex align-items-stretch m-0 p-0 flex-grow-1">
                    <div className="row w-100 m-0 p-0 align-items-stretch">
                      {/* Left Column: Main Product Card */}
                      <div className="col-6 p-2 d-flex flex-column justify-content-center align-items-center">
                        {mainItem && (
                          <Link
                            to={getSafeLink(mainItem)}
                            className="d-flex flex-column justify-content-center align-items-center text-decoration-none text-dark w-100 p-0 m-0"
                          >
                            <div 
                              className="w-100 position-relative m-0 p-0 d-flex justify-content-center align-items-center" 
                              style={{ minWidth: 0, aspectRatio: '1/1', maxHeight: '160px' }}
                            >
                              <img
                                src={resolveImageUrl(mainItem.img || mainItem.thumbnail_image || mainItem.image_url)}
                                alt={mainItem.name || mainItem.title || ""}
                                className="zoom-hover m-0 p-0"
                                loading="lazy"
                                style={{ 
                                  objectFit: "contain", 
                                  width: "100%", 
                                  height: "100%", 
                                  maxHeight: "155px",
                                  padding: "4px"
                                }}
                              />
                            </div>
                            <div className="w-100 text-center mt-2">
                              <p 
                                className="m-0 p-0 pri fw-semibold text-truncate w-100"
                                style={{ fontSize: '12px', color: '#1f2937', lineHeight: '1.25' }}
                                title={mainItem.name || mainItem.title}
                              >
                                {mainItem.name || mainItem.title}
                              </p>
                              <p 
                                className="m-0 p-0 offer fw-bold text-center w-100 mt-0.5"
                                style={{ fontSize: '12px', color: '#059669' }}
                              >
                                {mainItem.offer || section?.badge_text || "Min. 30% off"}
                              </p>
                            </div>
                          </Link>
                        )}
                      </div>

                      {/* Right Column: 2 Stacked Product Cards with Equal Image Dimensions */}
                      <div className="col-6 p-0 border-start d-flex flex-column justify-content-between">
                        {sideItems.map((item, i) => (
                          <div 
                            key={i}
                            className={`p-2 d-flex flex-column justify-content-center align-items-center ${i > 0 ? "border-top" : ""}`}
                          >
                            <Link
                              to={getSafeLink(item)}
                              className="d-flex flex-column justify-content-center align-items-center text-decoration-none text-dark w-100 p-0 m-0"
                            >
                              <div 
                                className="w-100 position-relative m-0 p-0 d-flex justify-content-center align-items-center" 
                                style={{ minWidth: 0, aspectRatio: '1/1', maxHeight: '160px' }}
                              >
                                <img
                                  src={resolveImageUrl(item.img || item.thumbnail_image || item.image_url)}
                                  alt={item.name || item.title || ""}
                                  className="zoom-hover m-0 p-0"
                                  loading="lazy"
                                  style={{ 
                                    objectFit: "contain", 
                                    width: "100%", 
                                    height: "100%", 
                                    maxHeight: "155px",
                                    padding: "4px"
                                  }}
                                />
                              </div>
                              <div className="w-100 text-center mt-2">
                                <p 
                                  className="m-0 p-0 pri text-center fw-semibold text-truncate w-100" 
                                  style={{ fontSize: '12px', color: '#1f2937', lineHeight: '1.25' }}
                                  title={item.name || item.title}
                                >
                                  {item.name || item.title}
                                </p>
                                <p 
                                  className="m-0 p-0 offer fw-bold text-center w-100 mt-0.5"
                                  style={{ fontSize: '12px', color: '#059669' }}
                                >
                                  {item.offer || section?.badge_text || "Min. 30% off"}
                                </p>
                              </div>
                            </Link>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    );
  }

  // --- DESKTOP VIEW LAYOUT (Original 3-Column Design) ---
  return (
    <div className="w-100 m-0 p-0">
      <div 
        className="d-flex flex-column flex-lg-row align-items-stretch m-0 p-0"
        style={{ gap: "var(--home-gap, 16px)" }}
      >
        {gridData.map((cat, idx) => (
          <div 
            key={idx} 
            className="col-12 col-sm-12 col-md-12 col-lg-4 p-0"
            style={{ flex: "1 1 0px", minWidth: 0 }}
          >
            <div 
              className="border bg-white rounded-3 h-100 d-flex flex-column shadow-sm"
              style={{ padding: "8px" }}
            >
              {/* Header */}
              <div 
                className="d-flex justify-content-between align-items-center mb-2"
                style={{ padding: "4px 6px" }}
              >
                <h4 className="m-0 pm-home-section-title">{cat.title}</h4>
                <Link 
                  to="/allproducts"
                  className="border-0 bg-primary text-white rounded-circle d-flex justify-content-center align-items-center text-decoration-none shadow-sm" 
                  style={{ width: "26px", height: "26px" }}
                  aria-label="View category"
                >
                  <FaChevronRight size={13} />
                </Link>
              </div>

              {/* Content */}
              <div className="border-top border-bottom d-flex align-items-center m-0 p-0 flex-grow-1">
                <div className="row w-100 m-0 p-0 align-items-center">
                  {/* Main Item */}
                  <div className="col-6 p-2 d-flex flex-column justify-content-center">
                    {cat.mainItem && (
                      <Link
                        to={getSafeLink(cat.mainItem)}
                        className="d-flex flex-column justify-content-center align-items-center text-decoration-none text-dark w-100 p-0 m-0"
                      >
                        <div className="square-container rounded-3 w-100 position-relative m-0 p-0" style={{ minWidth: 0, aspectRatio: '1/1' }}>
                          <img
                            src={resolveImageUrl(cat.mainItem.img)}
                            alt={cat.mainItem.name}
                            className="zoom-hover m-0 p-0 rounded-3"
                            loading="lazy"
                            style={{ objectFit: "contain", width: "100%", height: "100%", position: "absolute", top: 0, left: 0, padding: "4px" }}
                          />
                        </div>
                        <p className="m-0 p-0 product-name-font pri text-center mt-2 fw-semibold text-truncate w-100">
                          {cat.mainItem.name}
                        </p>
                        <p className="m-0 p-0 fs-8 offer text-success fw-bold text-center w-100">
                          {cat.mainItem.offer}
                        </p>
                      </Link>
                    )}
                  </div>

                  {/* Side Items */}
                  <div className="col-6 p-2 border-start d-flex flex-column justify-content-center gap-2">
                    {(cat.sideItems || []).map((item, i) => (
                      <Link
                        key={i}
                        to={getSafeLink(item)}
                        className="d-flex flex-column justify-content-center align-items-center text-decoration-none text-dark w-100 p-0 m-0"
                      >
                        <div className="square-container rounded-3 w-100 position-relative m-0 p-0" style={{ minWidth: 0, aspectRatio: '1/1' }}>
                          <img
                            src={resolveImageUrl(item.img)}
                            alt={item.name}
                            className="zoom-hover m-0 p-0 rounded-3"
                            loading="lazy"
                            style={{ objectFit: "contain", width: "100%", height: "100%", position: "absolute", top: 0, left: 0, padding: "4px" }}
                          />
                        </div>
                        <p className="m-0 p-0 fw- pri text-center mt-1 text-truncate w-100" style={{ fontSize: '0.85rem' }}>{item.name}</p>
                        <span className="m-0 p-0 fs-8 offer text-success fw-bold text-center w-100">
                          {item.offer}
                        </span>
                      </Link>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
