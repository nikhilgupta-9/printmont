import React, { useState, useEffect } from "react";
import { FaChevronRight } from "react-icons/fa";
import { MdKeyboardArrowRight } from "react-icons/md";
import { Link } from "react-router-dom";
import { getProductUrl } from "../../../utils/seo";

const SectionTen = ({ title, items: initialItems = [], apiUrl, imageColumn, backgroundImageUrl }) => {
  const [items, setItems] = useState(initialItems);
  const [loading, setLoading] = useState(!!apiUrl);

  useEffect(() => {
    if (!apiUrl) {
      setItems(initialItems);
      setLoading(false);
      return;
    }
    const fetchData = async () => {
      try {
        const res = await fetch(apiUrl);
        if (res.ok) {
          const data = await res.json();
          const rawData = data && data.success && Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
          
          const formatted = rawData.map(p => {
            let primaryImg = '/default-img.jpg';
            if (Array.isArray(p.images) && p.images.length > 0) {
              const primary = p.images.find(img => img.is_primary) || p.images[0];
              primaryImg = primary.image_url;
            } else if (p.img) {
              primaryImg = p.img;
            } else if (p.image_url) {
              primaryImg = p.image_url;
            }
            
            const hasDiscount = p.discount_price !== null && p.discount_price !== undefined && p.discount_price > 0;
            const discountText = hasDiscount ? `${Math.round(((p.price - p.discount_price) / p.price) * 100)}% Off` : (p.discount || '');

            return {
              id: p.id,
              title: p.name || p.title || '',
              image: primaryImg,
              discount: discountText
            };
          });
          setItems(formatted.slice(0, 8));
        }
      } catch (err) {
        console.error("Error fetching SectionTen products:", err);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [apiUrl, initialItems]);

  if (loading) {
    return (
      <div className="container-fluid m-0 bg-transparent p-0">
        <div className="row g-1 align-items-stretch px-1 py-2 px-1 bg-white">
          <div className="d-flex justify-content-between align-items-center px-1 pb-1">
            <h4 className="m-0 fw-semibold text-black">{title}</h4>
          </div>
          <div className="row g-1 p-0 px-0 mx-0 w-100">
            {[1, 2, 3, 4].map((idx) => (
              <div className="col-6 col-lg-3" key={idx}>
                <div className="border bg-white rounded-3 p-3 text-center h-100">
                  <div className="shimmer-bg skeleton-img w-100 mb-2" />
                  <div className="shimmer-bg skeleton-title w-75 mx-auto" />
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="container-fluid m-0 bg-transparent p-0">
      <div className="row g-1 align-items-stretch px-1 py-2 px-1 bg-white">
        <div className="d-flex justify-content-between align-items-center px-1 pb-1">
          <h4 className="m-0 fw-semibold text-black">{title}</h4>
          <button className="border-0 bg-primary text-white rounded-circle d-flex justify-content-center align-items-center fs-5 p-1">
            <FaChevronRight />
          </button>
        </div>
        {/* Mobile View More Button */}
        <div className="p-2 d-flex d-lg-none">
          <div className="w-100 border rounded bg-light">
            <Link
              to="#"
              className="w-100 py-2 text-center text-decoration-none text-dark fw-semibold d-block"
            >
              View More <MdKeyboardArrowRight size={18} />
            </Link>
          </div>
        </div>
        
        {/* --- LEFT IMAGE SECTION (col-4) --- */}
        {imageColumn && (
          <div className="col-12 col-md-4">
            <div className="border rounded-3 overflow-hidden h-100 p-2 bg-white">
              <img
                src={imageColumn.imageUrl}
                alt={imageColumn.alt || "Showcase"}
                className="w-100 h-100 rounded-3"
                style={{
                  objectFit: "cover",
                  maxHeight: "515px",
                }}
              />
            </div>
          </div>
        )}

        {/* --- RIGHT CARD GRID SECTION (col-8) --- */}
        <div className={imageColumn ? "col-12 col-md-8" : "col-12"}>
          <div
            className="border bg-white rounded-3 p-1 h-100"
            style={
              backgroundImageUrl
                ? {
                    backgroundImage: `url(${backgroundImageUrl})`,
                    backgroundSize: "cover",
                    backgroundPosition: "center",
                  }
                : {}
            }
          >
            {/* Cards Grid */}
            <div className="row g-1 p-0 px-0 mx-0 ">
              {(items || []).map((item, idx) => (
                <Link to={getProductUrl(item)} className="col-6 col-lg-3 text-decoration-none link-text-black" key={idx}>
                  <div className="border bg-white rounded-3 p-2 p-lg-1 text-center cus-bg h-100 d-flex justify-content-between align-items-center flex-column">
                    <div className="three-coontainer-img image-zoom-wrapper">
                      <img
                        src={item.image}
                        alt={item.title}
                        className="mb-2 bg-white zoom-hover"
                        style={{
                          objectFit: 'contain',
                          width: '100%',
                          height: '100%',
                        }}
                      />
                    </div>
                    <div>
                      <h6 className="fw-semibold section-product-name mb-1">{item.title}</h6>
                      <p className="text-lg-muted mb-0 section-product-name-offer text-success fw-bold">
                        {item.discount}
                      </p>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SectionTen;
