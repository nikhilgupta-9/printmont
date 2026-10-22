import React, { useState, useEffect } from "react";
import useHomeBanners from "../hooks/useHomeBanners";
import BannerImage from "./BannerImage";
import { IoIosArrowBack, IoIosArrowForward } from "react-icons/io";

import normalizeBanner from "../utils/normalizeBanner";

/**
 * Desktop Hero Carousel with Fixed Centered Active Indicator Dots.
 * @param {string} [apiUrl] The API endpoint to fetch banners from.
 * @param {Array} [banners] Static banner list.
 * @param {string} [carouselId='heroCarousel'] DOM ID for Bootstrap carousel.
 * @param {string} [basePath=''] Base path for relative image URLs.
 */
export default function ResponsiveHeroCarousel({
  apiUrl,
  banners: propBanners,
  carouselId = "heroCarousel",
  basePath = "",
  autoPlaySpeed = 3000
}) {
  const { banners: fetchedBanners, loading, error } = useHomeBanners(apiUrl, "home_hero", basePath);
  const rawBanners = (fetchedBanners && fetchedBanners.length > 0) ? fetchedBanners : (propBanners || []);
  const banners = rawBanners.map(b => normalizeBanner(b, basePath)).filter(Boolean);

  const [activeIndex, setActiveIndex] = useState(0);

  useEffect(() => {
    const el = document.getElementById(carouselId);
    if (!el) return;
    const handleSlid = (e) => {
      if (typeof e.to === "number") {
        setActiveIndex(e.to);
      }
    };
    el.addEventListener("slid.bs.carousel", handleSlid);
    return () => {
      el.removeEventListener("slid.bs.carousel", handleSlid);
    };
  }, [carouselId]);

  if (loading && !propBanners) {
    return (
      <div className="container-fluid m-0 p-0">
        <div className="shimmer-bg skeleton-banner-hero w-100" />
      </div>
    );
  }

  if (error && !propBanners) {
    return <div className="text-center p-3 text-danger">Error: {error}</div>;
  }

  if (banners.length === 0) {
    return null;
  }

  return (
    <div
      id={carouselId}
      className="carousel slide container-fluid m-0 p-0 home-banner-section position-relative"
      data-bs-ride="carousel"
      data-bs-interval={autoPlaySpeed || 3000}
    >
      <div className="carousel-inner">
        {banners.map((banner, index) => (
          <div
            className={`carousel-item ${index === 0 ? "active" : ""}`}
            key={index}
          >
            <BannerImage
              large={banner.large}
              small={banner.small}
              target={banner.target}
              alt={banner.alt || `Slide ${index + 1}`}
            />
          </div>
        ))}
      </div>

      {/* Desktop Fixed Centered Indicator Dots */}
      {banners.length > 1 && (
        <div 
          className="position-absolute bottom-0 start-50 translate-middle-x mb-2.5 d-none d-md-flex align-items-center justify-content-center"
          style={{ zIndex: 15 }}
        >
          <div 
            className="d-inline-flex align-items-center justify-content-center shadow-sm"
            style={{
              backgroundColor: "rgba(255, 255, 255, 0.9)",
              backdropFilter: "blur(4px)",
              border: "1px solid #cbd5e1",
              borderRadius: "9999px",
              padding: "4px 10px",
              gap: "8px"
            }}
          >
            {banners.map((_, idx) => {
              const isActive = idx === activeIndex;
              return (
                <button
                  key={idx}
                  type="button"
                  data-bs-target={`#${carouselId}`}
                  data-bs-slide-to={idx}
                  className={isActive ? "active" : ""}
                  aria-current={isActive ? "true" : "false"}
                  aria-label={`Slide ${idx + 1}`}
                  style={{
                    width: "9px",
                    height: "9px",
                    borderRadius: "50%",
                    padding: 0,
                    margin: 0,
                    cursor: "pointer",
                    border: isActive ? "1.5px solid #0284c7" : "1.5px solid #94a3b8",
                    backgroundColor: isActive ? "#0284c7" : "#ffffff",
                    transition: "all 0.2s ease-in-out"
                  }}
                />
              );
            })}
          </div>
        </div>
      )}

      {banners.length > 1 && (
        <>
          <button
            className="scroll-arrow left d-none d-md-flex align-items-center justify-content-start"
            type="button"
            data-bs-target={`#${carouselId}`}
            data-bs-slide="prev"
            style={{ top: '50%', height: '100%', zIndex: 10, background: 'transparent', border: 'none', borderRadius: '0', boxShadow: 'none', width: 'auto' }}
          >
            <span className="left-arr-carousel text-black bg-white">
              <IoIosArrowBack />
            </span>
            <span className="visually-hidden">Previous</span>
          </button>

          <button
            className="scroll-arrow right d-none d-md-flex align-items-center justify-content-end"
            type="button"
            data-bs-target={`#${carouselId}`}
            data-bs-slide="next"
            style={{ top: '50%', height: '100%', zIndex: 10, background: 'transparent', border: 'none', borderRadius: '0', boxShadow: 'none', width: 'auto' }}
          >
            <span className="right-arr-carousel text-black bg-white">
              <IoIosArrowForward />
            </span>
            <span className="visually-hidden">Next</span>
          </button>
        </>
      )}
    </div>
  );
}
