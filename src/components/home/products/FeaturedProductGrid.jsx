import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { FaChevronRight } from "react-icons/fa";
import { getProductUrl } from "../../../utils/seo";
import { resolveImageUrl } from "../../../config/apiEndpoints";

/**
 * Displays category block containing a large main item and stacked side items.
 * Supports both static data fallback and dynamic apiUrl data fetching.
 * @param {Array} [data] Initial/static list of categories with mainItem and sideItems.
 * @param {string} [apiUrl] API endpoint to fetch category products dynamically.
 * @param {string} [title] Optional title.
 */
export default function FeaturedProductGrid({ data = [], apiUrl, title }) {
  const [categories, setCategories] = useState(data || []);
  const [loading, setLoading] = useState(!!apiUrl);

  useEffect(() => {
    if (!apiUrl) {
      if (data && data.length > 0) setCategories(data);
      setLoading(false);
      return;
    }

    const fetchData = async () => {
      try {
        const response = await fetch(apiUrl);
        const resData = await response.json();
        const raw = resData && resData.success && resData.data ? resData.data : (Array.isArray(resData) ? resData : []);

        if (Array.isArray(raw) && raw.length > 0) {
          // Check if it's grouped category columns: [{ title, slug, items: [...] }, ...]
          if (raw[0] && Array.isArray(raw[0].items)) {
            const transformed = raw.map((col) => {
              const items = col.items || [];
              const main = items[0] || null;
              const sides = items.slice(1, 3);
              return {
                title: col.title || "Featured Category",
                slug: col.slug || "",
                mainItem: main ? {
                  id: main.id,
                  name: main.title || main.name,
                  slug: main.slug,
                  img: resolveImageUrl(main.image || main.img || main.primary_image || main.thumbnail || (main.images && main.images[0]?.image_url)),
                  offer: main.discount || "Top Deal"
                } : null,
                sideItems: sides.map((s) => ({
                  id: s.id,
                  name: s.title || s.name,
                  slug: s.slug,
                  img: resolveImageUrl(s.image || s.img || s.primary_image || s.thumbnail || (s.images && s.images[0]?.image_url)),
                  offer: s.discount || "Top Deal"
                }))
              };
            });
            setCategories(transformed.filter(c => c.mainItem !== null));
          } else {
            // Flat list of products: chunk into up to 3 columns (1 main + 2 side per column)
            const cols = [];
            const chunkSize = 3;
            for (let i = 0; i < raw.length && cols.length < 3; i += chunkSize) {
              const chunk = raw.slice(i, i + chunkSize);
              const main = chunk[0];
              const sides = chunk.slice(1);
              cols.push({
                title: main.category_name || main.sub_category_name || title || "Featured Selection",
                slug: main.category_slug || "",
                mainItem: {
                  id: main.id,
                  name: main.title || main.name,
                  slug: main.slug,
                  img: resolveImageUrl(main.image || main.img || main.primary_image || main.thumbnail || (main.images && main.images[0]?.image_url)),
                  offer: main.discount || "Top Deal"
                },
                sideItems: sides.map((s) => ({
                  id: s.id,
                  name: s.title || s.name,
                  slug: s.slug,
                  img: resolveImageUrl(s.image || s.img || s.primary_image || s.thumbnail || (s.images && s.images[0]?.image_url)),
                  offer: s.discount || "Top Deal"
                }))
              });
            }
            setCategories(cols);
          }
        } else if (data && data.length > 0) {
          setCategories(data);
        }
      } catch (err) {
        console.error("Error fetching featured grid products:", err);
        if (data && data.length > 0) setCategories(data);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [apiUrl, data, title]);

  if (categories.length === 0) return null;

  return (
    <div className="container-fluid m-0 p-0 home-layout-gap">
      <div 
        className="d-flex flex-column flex-lg-row align-items-stretch m-0 p-0"
        style={{ gap: "var(--home-gap, 3px)" }}
      >
        {categories.map((cat, idx) => {
          const catLink = cat.slug ? `/category/${cat.slug}` : "#";
          return (
            <div 
              key={idx} 
              className="col-12 col-sm-12 col-md-12 col-lg-4 p-0"
              style={{ flex: "1 1 0px", minWidth: 0 }}
            >
              <div 
                className="border bg-white rounded-3 h-100 d-flex flex-column"
                style={{ padding: "1.5px" }}
              >
                {/* Header */}
                <div 
                  className="d-flex justify-content-between align-items-center"
                  style={{ padding: "10px 6px" }}
                >
                  <Link to={catLink} className="text-decoration-none">
                    <h4 className="m-0 section-title fw-semibold text-black" style={{ paddingTop: "4px", paddingBottom: "4px" }}>
                      {cat.title}
                    </h4>
                  </Link>
                  <Link 
                    to={catLink}
                    className="border-0 bg-primary text-white rounded-circle d-flex justify-content-center align-items-center text-decoration-none shadow-sm" 
                    style={{ width: "26px", height: "26px" }}
                    aria-label="View category"
                  >
                    <FaChevronRight size={14} />
                  </Link>
                </div>

                {/* Content */}
                <div className="border-top border-bottom d-flex align-items-center m-0 p-0 flex-grow-1">
                  <div className="row w-100 m-0 p-0 align-items-center">
                    {/* Main Item */}
                    <div className="col-6 p-2 d-flex flex-column justify-content-center">
                      {cat.mainItem && (
                        <Link
                          to={getProductUrl(cat.mainItem)}
                          className="d-flex flex-column justify-content-center align-items-center text-decoration-none text-dark w-100 p-0 m-0"
                        >
                          <div className="square-container rounded-3 w-100 position-relative m-0 p-0" style={{ minWidth: 0, aspectRatio: '1/1' }}>
                            <img
                              src={cat.mainItem.img}
                              alt={cat.mainItem.name}
                              className="zoom-hover m-0 p-0 rounded-3"
                              loading="lazy"
                              style={{ objectFit: "cover", width: "100%", height: "100%", position: "absolute", top: 0, left: 0 }}
                              onError={(e) => { e.target.src = '/default-img.jpg'; }}
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
                          to={getProductUrl(item)}
                          className="d-flex flex-column justify-content-center align-items-center text-decoration-none text-dark w-100 p-0 m-0"
                        >
                          <div className="square-container rounded-3 w-100 position-relative m-0 p-0" style={{ minWidth: 0, aspectRatio: '1/1' }}>
                            <img
                              src={item.img}
                              alt={item.name}
                              className="zoom-hover m-0 p-0 rounded-3"
                              loading="lazy"
                              style={{ objectFit: "cover", width: "100%", height: "100%", position: "absolute", top: 0, left: 0 }}
                              onError={(e) => { e.target.src = '/default-img.jpg'; }}
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
          );
        })}
      </div>
    </div>
  );
}
