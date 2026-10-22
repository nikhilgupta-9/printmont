import React from "react";
import { Link } from "react-router-dom";
import { FaChevronRight } from "react-icons/fa";
import { MdKeyboardArrowRight } from "react-icons/md";
import ProductCard from "./ProductCard";
import { resolveImageUrl } from "../../../config/apiEndpoints";

/**
 * CategoryProductMosaic Component
 * Displays category blocks either as grouped columns (2x2 grid) or a single flat 2x4 grid with a featured left banner.
 * @param {string} [variant='grouped'] 'grouped' for multi-column category blocks, 'flat' for a single large category grid.
 * @param {Array} [columns] Grouped column data: Array of { title, items }
 * @param {string} [apiUrl] API endpoint for fetching flat/grouped list.
 * @param {Object} [imageColumn] Optional promotional image block: { imageUrl, alt }
 * @param {string} [backgroundImageUrl] Optional background image for grids.
 * @param {string} [title] Title used in 'flat' variant.
 * @param {boolean} [reverse=false] Reverse the order of columns on desktop.
 * @param {string} [bgColor] Background color of the container.
 */
export default function CategoryProductMosaic({
  variant = "grouped",
  columns: propColumns,
  apiUrl,
  imageColumn,
  backgroundImageUrl,
  title = "Products",
  reverse = false,
  bgColor
}) {
  const [columnsState, setColumnsState] = React.useState(propColumns || []);
  const [loading, setLoading] = React.useState(!!apiUrl);

  React.useEffect(() => {
    if (!apiUrl) {
      if (propColumns) setColumnsState(propColumns);
      setLoading(false);
      return;
    }

    const fetchData = async () => {
      try {
        const response = await fetch(apiUrl);
        const data = await response.json();
        const rawData = data && data.success && Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
        if (variant === "grouped") {
          if (Array.isArray(rawData) && rawData.length > 0 && rawData[0].items) {
            setColumnsState(rawData);
          } else if (propColumns && propColumns.length > 0) {
            setColumnsState(propColumns);
          }
        } else {
          setColumnsState(Array.isArray(rawData) && rawData.length > 0 ? rawData : (propColumns || []));
        }
      } catch (error) {
        console.error("Error fetching mosaic data:", error);
        if (propColumns) setColumnsState(propColumns);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [apiUrl, propColumns, variant]);

  const renderGroupedColumn = (colTitle, items, idx) => {
    return (
      <div
        className="border bg-white rounded-3 h-100 custom-bg-image"
        style={{
          padding: "var(--home-card-gap, 8px)",
          ...(backgroundImageUrl ? { backgroundImage: `url(${backgroundImageUrl})`, backgroundSize: "cover" } : {})
        }}
        key={idx}
      >
        <div 
          className="d-flex justify-content-between align-items-center mosaic-header-container"
          style={{ padding: "10px 6px" }}
        >
          <Link 
            to={`/allproducts?q=${encodeURIComponent(colTitle)}`}
            className="m-0 section-title mosaic-header-title fw-semibold text-black text-decoration-none"
          >
            {colTitle}
          </Link>
          <Link 
            to={`/allproducts?q=${encodeURIComponent(colTitle)}`}
            className="border-0 bg-primary text-white rounded-circle d-flex justify-content-center align-items-center shadow-sm text-decoration-none" 
            style={{ width: "26px", height: "26px" }}
            aria-label="View category"
          >
            <FaChevronRight size={13} />
          </Link>
        </div>

        <div className="card-grid-container">
          {(items || []).map((item, idx2) => {
            const rawImg = item.image || item.img || item.primary_image || item.thumbnail || (item.images && item.images[0]?.image_url) || "/default-img.jpg";
            const mappedProduct = {
              id: item.id || item.productId || "",
              title: item.title || item.name || "",
              img: resolveImageUrl(rawImg),
              price: (item.price !== undefined && item.price !== null) ? item.price : null,
              originalPrice: item.originalPrice || null,
              discount: item.discount || "",
              badge: item.badge || ""
            };

            return (
              <div className="card-grid-item-grouped" key={idx2}>
                <ProductCard
                  product={mappedProduct}
                  variant="mosaic"
                />
              </div>
            );
          })}
        </div>

        <div className="p-1 d-flex d-lg-none mt-2">
          <div className="d-flex w-100 justify-content-center align-items-center border bd rounded bg-light">
            <Link
              to={`/allproducts?q=${encodeURIComponent(colTitle)}`}
              className="w-100 py-2 text-center text-decoration-none text-dark fs-6 fw-semibold"
            >
              View More <MdKeyboardArrowRight size={18} />
            </Link>
          </div>
        </div>
      </div>
    );
  };

  if (loading && !propColumns) {
    return (
      <div className="w-100 m-0 p-0">
        <div className="row g-1 align-items-center px-1">
          {[1, 2, 3].map((col) => (
            <div className="col-12 col-sm-6 col-md-12 col-lg-4" key={col}>
              <div className="border bg-white rounded-3 p-3 h-100">
                <div className="shimmer-bg skeleton-title w-50 mb-3" />
                <div className="row g-2">
                  {[1, 2, 3, 4].map((item) => (
                    <div className="col-6" key={item}>
                      <div className="shimmer-bg skeleton-img w-100" style={{ aspectRatio: "1/1" }} />
                    </div>
                  ))}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  // FLAT MOSAIC VARIANT (Matches User's 2x4 Subcategory Grid Layout Mockup)
  if (variant === "flat") {
    const flatItems = Array.isArray(columnsState) ? columnsState : [];
    if (flatItems.length === 0) return null;

    // Determine background color
    const lowerTitle = (title || "").toLowerCase();
    const defaultBg = lowerTitle.includes("women") || lowerTitle.includes("decor") 
      ? "#ffd5dc" 
      : lowerTitle.includes("men") 
        ? "#e0e7ff" 
        : "#fce7f3";
    const sectionBg = bgColor || defaultBg;

    // Format display title - cleanly strip out (Category) or Category
    const displayTitle = (title || "Products").replace(/\s*\(?category\)?/gi, '').trim() || title;
    const viewAllLink = `/allproducts?q=${encodeURIComponent(displayTitle)}`;

    // Left promotional image fallback
    let promoImgSrc = imageColumn?.imageUrl;
    if (!promoImgSrc) {
      if (lowerTitle.includes("men")) promoImgSrc = "/corporate_apparel.png";
      else if (lowerTitle.includes("women")) promoImgSrc = "/fashion_brand.png";
      else if (lowerTitle.includes("decor") || lowerTitle.includes("home")) promoImgSrc = "/card/home-dec-1.jpeg";
      else promoImgSrc = "/corporate_apparel.png";
    }

    return (
      <div className="w-100 m-0 p-0">
        <div 
          className="border rounded-4 w-100 shadow-sm"
          style={{ 
            padding: "16px",
            backgroundColor: sectionBg,
            borderColor: "rgba(0,0,0,0.06)"
          }}
        >
          {/* Top Section Header */}
          <div className="pm-home-section-header mb-3">
            <h3 className="pm-home-section-title">
              {displayTitle}
            </h3>
            <Link
              to={viewAllLink}
              className="pm-home-view-all-btn"
            >
              <span>View All</span>
              <MdKeyboardArrowRight size={18} />
            </Link>
          </div>

          {/* Main Body: Left Featured Box + Right 2x4 Subcategories Grid */}
          <div className="row g-2 align-items-stretch">
            
            {/* Left Promotional Showcase Block */}
            <div className="col-12 col-md-4 col-lg-3">
              <Link 
                to={viewAllLink} 
                className="d-block h-100 rounded-3 overflow-hidden bg-white border shadow-sm text-decoration-none position-relative group-hover"
                style={{ 
                  minHeight: "340px",
                  borderColor: "rgba(0,0,0,0.08)",
                  transition: "transform 0.25s ease, box-shadow 0.25s ease"
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.transform = "translateY(-4px)";
                  e.currentTarget.style.boxShadow = "0 12px 24px rgba(0,0,0,0.12)";
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.transform = "translateY(0)";
                  e.currentTarget.style.boxShadow = "0 2px 8px rgba(0,0,0,0.04)";
                }}
              >
                <img
                  src={resolveImageUrl(promoImgSrc)}
                  alt={title}
                  className="w-100 h-100"
                  style={{
                    objectFit: "cover",
                    display: "block",
                    minHeight: "340px",
                    transition: "transform 0.4s ease"
                  }}
                  onMouseEnter={(e) => { e.currentTarget.style.transform = "scale(1.03)"; }}
                  onMouseLeave={(e) => { e.currentTarget.style.transform = "scale(1)"; }}
                  onError={(e) => { e.target.src = "/corporate_apparel.png"; }}
                />
              </Link>
            </div>

            {/* Right 2x4 Subcategories Grid (8 Cards) with Large High-Visibility Images */}
            <div className="col-12 col-md-8 col-lg-9">
              <div className="row g-2 h-100">
                {flatItems.slice(0, 8).map((item, idx) => {
                  const rawImg = item.image || item.img || item.primary_image || item.thumbnail || (item.images && item.images[0]?.image_url) || "/default-img.jpg";
                  const itemName = item.title || item.name || "Sub Category";
                  const discountOffer = item.discount || (item.price ? `Min. 30% off` : "Min. 30% off");
                  const itemLink = item.slug ? `/category/${item.slug}` : (item.id ? `/allproducts?q=${encodeURIComponent(itemName)}` : `/allproducts?q=${encodeURIComponent(itemName)}`);

                  return (
                    <div className="col-6 col-sm-6 col-md-3 d-flex" key={item.id || idx}>
                      <Link
                        to={itemLink}
                        className="d-flex flex-column w-100 bg-white rounded-3 border shadow-sm text-decoration-none overflow-hidden"
                        style={{
                          borderColor: "rgba(0,0,0,0.08)",
                          transition: "transform 0.25s ease, box-shadow 0.25s ease"
                        }}
                        onMouseEnter={(e) => {
                          e.currentTarget.style.transform = "translateY(-4px)";
                          e.currentTarget.style.boxShadow = "0 10px 22px rgba(0,0,0,0.12)";
                          const imgEl = e.currentTarget.querySelector('.mosaic-flat-img');
                          if (imgEl) imgEl.style.transform = "scale(1.08)";
                        }}
                        onMouseLeave={(e) => {
                          e.currentTarget.style.transform = "translateY(0)";
                          e.currentTarget.style.boxShadow = "0 2px 8px rgba(0,0,0,0.04)";
                          const imgEl = e.currentTarget.querySelector('.mosaic-flat-img');
                          if (imgEl) imgEl.style.transform = "scale(1)";
                        }}
                      >
                        {/* High-Visibility Square Image Container (1:1) */}
                        <div 
                          className="position-relative w-100 overflow-hidden bg-light d-flex align-items-center justify-content-center"
                          style={{ 
                            aspectRatio: "1 / 1",
                            backgroundColor: "#f8fafc",
                            padding: "4px"
                          }}
                        >
                          <img
                            src={resolveImageUrl(rawImg)}
                            alt={itemName}
                            className="mosaic-flat-img w-100 h-100"
                            style={{
                              objectFit: "contain",
                              aspectRatio: "1 / 1",
                              transition: "transform 0.35s cubic-bezier(0.4, 0, 0.2, 1)"
                            }}
                            onError={(e) => { 
                              e.target.onerror = null;
                              e.target.src = "/default-img.jpg"; 
                            }}
                          />
                        </div>

                        {/* Bottom Label & Discount Details */}
                        <div 
                          className="p-2 text-center bg-white border-top flex-grow-1 d-flex flex-column justify-content-center"
                          style={{ borderColor: "#f1f5f9" }}
                        >
                          <div 
                            className="fw-bold text-dark text-truncate mb-1"
                            style={{ fontSize: "0.85rem", lineHeight: "1.25" }}
                            title={itemName}
                          >
                            {itemName}
                          </div>
                          <div 
                            className="fw-bold text-success text-truncate"
                            style={{ fontSize: "0.75rem", color: "#16a34a" }}
                          >
                            {discountOffer}
                          </div>
                        </div>
                      </Link>
                    </div>
                  );
                })}
              </div>
            </div>

          </div>

          {/* View More Mobile Button */}
          <div className="d-flex d-md-none mt-3">
            <Link
              to={viewAllLink}
              className="w-100 py-2 text-center text-decoration-none text-dark fw-bold bg-white rounded-3 border shadow-sm d-block"
              style={{ fontSize: "0.85rem" }}
            >
              View More {title} <MdKeyboardArrowRight size={18} />
            </Link>
          </div>

        </div>
      </div>
    );
  }

  // GROUPED MOSAIC VARIANT (SectionFour & SectionEight style)
  if (columnsState.length === 0) return null;

  return (
    <div className="w-100 m-0 p-0">
      <div 
        className={`d-flex flex-column flex-lg-row align-items-stretch m-0 p-0 ${reverse ? "flex-lg-row-reverse" : ""}`}
        style={{ gap: "var(--home-gap, 3px)" }}
      >
        {columnsState.map((col, index) => (
          <div 
            className="col-12 col-sm-12 col-md-12 col-lg-4 p-0" 
            style={{ flex: "1 1 0px", minWidth: 0 }}
            key={index}
          >
            {renderGroupedColumn(col.title, col.items, index)}
          </div>
        ))}

        {imageColumn && (
          <div 
            className="col-12 col-sm-12 col-md-12 col-lg-4 p-0"
            style={{ flex: "1 1 0px", minWidth: 0 }}
          >
            <div className="border rounded-3 h-100 overflow-hidden bg-white" style={{ padding: "var(--home-card-gap, 8px)" }}>
              <img
                src={resolveImageUrl(imageColumn.imageUrl)}
                alt={imageColumn.alt || "Showcase"}
                className="w-100 h-100 rounded-3"
                style={{
                  objectFit: "cover",
                  display: "block"
                }}
              />
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
