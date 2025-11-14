import React from "react";
import { FaChevronRight } from "react-icons/fa";
import { MdKeyboardArrowRight } from "react-icons/md";
import { Link } from "react-router-dom";

const SectionTen = ({ title, items = [], imageColumn, backgroundImageUrl }) => {
  return (
    <div className="container-fluid m-0 bg-transparent p-2">
      <div className="row g-1 align-items-stretch px-1 py-3 px-1 bg-white">
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
        <div className="col-12 col-md-8">
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
            {/* Header */}
            

            {/* Cards Grid */}
            <div className="row g-1 p-0 px-0 mx-0 ">
              {(items || []).map((item, idx) => (
                <Link to={'#'} className="col-6 col-lg-3 text-decoration-none link-text-black" key={idx}>
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
