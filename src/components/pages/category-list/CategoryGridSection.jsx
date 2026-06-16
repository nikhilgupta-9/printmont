import React from "react";
import { Link } from "react-router-dom";
import { categoriesData } from "../../../../data/categoriesdata";
import "./CategoryGrid.css";

const CategoryGridSection = () => {
  return (
    <div className="container-fluid category-grid-section py-4 bg-white">
      <div className="row justify-content-center g-4 mx-auto" style={{ maxWidth: '1440px' }}>
        {categoriesData.map((cat, idx) => (
          <div key={idx} className="col-4 col-md-3 col-lg-2 d-flex flex-column align-items-center text-center">
            <Link to={cat.url || "#"} className="text-decoration-none text-dark w-100 d-flex flex-column align-items-center">
              <div className="cg-image-wrapper mb-2">
                {/* The pill shape background */}
                <div className="cg-bg-shape"></div>
                {/* The category image */}
                <img 
                  src={cat.img && cat.img.startsWith('./') ? cat.img.substring(1) : cat.img} 
                  alt={cat.name} 
                  className="cg-image" 
                />
              </div>
              <span className="fw-medium cg-title text-truncate w-100 px-1">{cat.name}</span>
            </Link>
          </div>
        ))}
      </div>
    </div>
  );
};

export default CategoryGridSection;
