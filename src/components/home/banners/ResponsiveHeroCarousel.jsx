import React from "react";
import useHomeBanners from "../hooks/useHomeBanners";
import BannerImage from "./BannerImage";
import { IoIosArrowBack, IoIosArrowForward } from "react-icons/io";

import normalizeBanner from "../utils/normalizeBanner";

/**
 * @param {string} [apiUrl] The API endpoint to fetch banners from.
 * @param {Array} [banners] Static banner list.
 * @param {string} [carouselId='heroCarousel'] DOM ID for Bootstrap carousel.
 * @param {string} [basePath=''] Base path for relative image URLs.
 */
export default function ResponsiveHeroCarousel({
  apiUrl,
  banners: propBanners,
  carouselId = "heroCarousel",
  basePath = ""
}) {
  const { banners: fetchedBanners, loading, error } = useHomeBanners(apiUrl, "home_hero", basePath);
  const rawBanners = (fetchedBanners && fetchedBanners.length > 0) ? fetchedBanners : (propBanners || []);
  const banners = rawBanners.map(b => normalizeBanner(b, basePath)).filter(Boolean);

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
      className="carousel slide container-fluid m-0 p-0 home-banner-section"
      data-bs-ride="carousel"
      data-bs-interval="3000"
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
