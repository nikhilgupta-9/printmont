import React from "react";

const BannerTwo = ({ desktopImages = [], mobileImages = [] }) => {
  return (
    <>
      {/* Desktop View (≥ md): Side-by-side layout */}
      <div className="container-fluid bg-none d-none d-md-flex align-items-center justify-content-center gap-1 px-1 py-0 m-0">
        {desktopImages.map((img, index) => (
          <div key={index} className="col-4 p-0 rounded">
            <img
              className="rounded"
              src={img.src}
              width="100%"
              alt={img.alt || `banner-${index}`}
              style={{ maxHeight: "350px", height: "300px" }}
            />
          </div>
        ))}
      </div>

      {/* Mobile View (< md): Auto-scroll carousel */}
      <div className="container-fluid px-0 d-md-none">
        <div
          id="bannerCarousel"
          className="carousel slide"
          data-bs-ride="carousel"
          data-bs-interval="2000"
        >
          <div className="carousel-inner">
            {mobileImages.map((img, index) => (
              <div
                key={index}
                className={`carousel-item ${index === 0 ? "active" : ""}`}
              >
                <img
                  src={img.src}
                  className="d-block w-100"
                  alt={img.alt || `mobile-banner-${index}`}
                />
              </div>
            ))}
          </div>
        </div>
      </div>
    </>
  );
};

export default BannerTwo;
