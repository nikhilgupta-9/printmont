import React from "react";
import { Link } from "react-router-dom";
import { FaChevronRight } from "react-icons/fa";
import { MdKeyboardArrowRight } from "react-icons/md";
import useHomeProducts from "../hooks/useHomeProducts";
import ProductCard from "./ProductCard";

/**
 * Replaces SectionFour, SectionEight, SectionEightSingle, and SectionTen.
 * Displays category blocks either as grouped columns (2x2 grid) or a single flat grid of products.
 * @param {string} [variant='grouped'] 'grouped' for multi-column category blocks, 'flat' for a single large category grid.
 * @param {Array} [columns] Grouped column data: Array of { title, items }
 * @param {string} [apiUrl] API endpoint for fetching flat/grouped list.
 * @param {Object} [imageColumn] Optional promotional image block: { imageUrl, alt }
 * @param {string} [backgroundImageUrl] Optional background image for grids.
 * @param {string} [title] Title used in 'flat' variant.
 * @param {boolean} [reverse=false] Reverse the order of columns on desktop.
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
        setColumnsState(rawData);
      } catch (error) {
        console.error("Error fetching mosaic data:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [apiUrl, propColumns]);

  const renderGroupedColumn = (colTitle, items, idx) => {
    // Items here are usually up to 4 items in a 2x2 grid
    return (
      <div
        className="border bg-white rounded-3 h-100 custom-bg-image"
        style={{
          padding: "var(--home-card-gap, 8px)",
          ...(backgroundImageUrl ? { backgroundImage: `url(${backgroundImageUrl})`, backgroundSize: "cover" } : {})
        }}
      >
        <div 
          className="d-flex justify-content-between align-items-center mosaic-header-container"
          style={{ padding: "10px 6px" }}
        >
          <p className="m-0 section-title mosaic-header-title fw-semibold text-black">{colTitle}</p>
          <button 
            className="border-0 bg-primary text-white rounded-circle d-flex justify-content-center align-items-center shadow-sm" 
            style={{ width: "26px", height: "26px" }}
            aria-label="View category"
          >
            <FaChevronRight size={13} />
          </button>
        </div>

        <div className="card-grid-container">
          {(items || []).map((item, idx2) => {
            // Map raw item fields to standard product shape for ProductCard
            const mappedProduct = {
              id: item.id || item.productId || "",
              title: item.title || item.name || "",
              img: item.image || item.img || "/default-img.jpg",
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
              to="/cart"
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
      <div className="container-fluid p-1 m-0 home-layout-gap">
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

  if (variant === "flat") {
    const flatItems = Array.isArray(columnsState) ? columnsState : [];
    if (flatItems.length === 0) return null;

    return (
      <div className="container-fluid m-0 bg-transparent p-0 home-layout-gap">
        <div 
          className="border rounded-3 w-100"
          style={{ 
            padding: "var(--home-card-gap, 8px)",
            backgroundColor: bgColor || "#ffffff"
          }}
        >
          {/* Section Header */}
          <div className="d-flex justify-content-between align-items-center mb-2 p-0" style={{ paddingLeft: "4px", paddingRight: "4px" }}>
            <h4 className="m-0 fw-bold text-black fs-5 fs-md-4">{title}</h4>
            <Link
              to="/cart"
              className="d-flex align-items-center justify-content-center rounded bg-theme px-2 py-1 text-white text-decoration-none me-1"
              style={{ fontSize: '0.75rem', height: '24px', whiteSpace: 'nowrap' }}
            >
              View All <MdKeyboardArrowRight size={16} />
            </Link>
          </div>

          <div 
            className="d-flex flex-column flex-md-row align-items-stretch m-0 p-0"
            style={{ gap: "var(--home-card-gap, 8px)" }}
          >
            {/* Left Promotional Image Column */}
            {imageColumn && (
              <div 
                className="col-12 col-md-4 p-0"
                style={{ flex: "1 1 0px", minWidth: 0 }}
              >
                <img
                  src={imageColumn.imageUrl}
                  alt={imageColumn.alt || "Showcase"}
                  className="w-100 h-100 rounded-3"
                  style={{
                    objectFit: "cover",
                    display: "block",
                    minHeight: "100%"
                  }}
                />
              </div>
            )}

            {/* Right Product Grid Column */}
            <div 
              className={imageColumn ? "col-12 col-md-8 p-0" : "col-12 p-0"}
              style={{ flex: imageColumn ? "2 1 0px" : "1 1 0px", minWidth: 0 }}
            >
              <div className="card-grid-container">
                {flatItems.map((item, idx) => {
                  const mappedProduct = {
                    id: item.id || "",
                    title: item.title || item.name || "",
                    img: item.image || item.img || "/default-img.jpg",
                    price: (item.price !== undefined && item.price !== null) ? item.price : null,
                    originalPrice: item.originalPrice || null,
                    discount: item.discount || "",
                    badge: item.badge || ""
                  };

                  return (
                    <div className="card-grid-item-2col" key={idx}>
                      <ProductCard
                        product={mappedProduct}
                        variant="mosaic"
                      />
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          {/* View More Mobile Button */}
          <div className="d-flex d-lg-none mt-2 p-1">
            <div className="w-100 border rounded bg-light">
              <Link
                to="/cart"
                className="w-100 py-2 text-center text-decoration-none text-dark fw-semibold d-block"
              >
                View More <MdKeyboardArrowRight size={18} />
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Grouped layout (SectionFour & SectionEight style)
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
                src={imageColumn.imageUrl}
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
