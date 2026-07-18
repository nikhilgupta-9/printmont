import React from "react";
import { Link } from "react-router-dom";
import { FaChevronRight } from "react-icons/fa";
import { getProductUrl } from "../../../utils/seo";

/**
 * Replaces SectionGrid. Displays category block containing a large main item
 * and stacked side items.
 * @param {Array} data List of categories with mainItem and sideItems.
 */
export default function FeaturedProductGrid({ data = [] }) {
  if (!data || data.length === 0) return null;

  return (
    <div className="container-fluid m-0 p-0 home-layout-gap">
      <div 
        className="d-flex flex-column flex-lg-row align-items-stretch m-0 p-0"
        style={{ gap: "var(--home-gap, 3px)" }}
      >
        {data.map((cat, idx) => (
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
                <h4 className="m-0 section-title fw-semibold text-black" style={{ paddingTop: "4px", paddingBottom: "4px" }}>{cat.title}</h4>
                <button 
                  className="border-0 bg-primary text-white rounded-circle d-flex justify-content-center align-items-center" 
                  style={{ width: "26px", height: "26px" }}
                  aria-label="View category"
                >
                  <FaChevronRight size={14} />
                </button>
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
