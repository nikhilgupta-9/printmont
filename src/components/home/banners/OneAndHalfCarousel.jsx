import React from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import useHomeBanners from "../hooks/useHomeBanners";
import BannerImage from "./BannerImage";
import normalizeBanner from "../utils/normalizeBanner";
import "swiper/css";

/**
 * Mobile Peeking Image Carousel Slider (shows ~1.25 slides with next slide peeking from right).
 * @param {string} [apiUrl] The API endpoint to fetch banners from.
 * @param {Array} [banners] Static or pre-fetched banners list.
 * @param {string} [sectionKey] Optional section key payload.
 * @param {boolean} [isMobileOnly] Whether to hide on desktop.
 */
export default function OneAndHalfCarousel({
  apiUrl,
  banners: propBanners,
  sectionKey = "",
  basePath = "",
  isMobileOnly = false
}) {
  const { banners: fetchedBanners, loading, error } = useHomeBanners(apiUrl, sectionKey, basePath);
  const rawBanners = (propBanners && propBanners.length > 0) ? propBanners : (fetchedBanners || []);
  const banners = rawBanners.map(b => normalizeBanner(b, basePath)).filter(Boolean);

  if (loading && (!propBanners || propBanners.length === 0)) {
    return (
      <div className={`${isMobileOnly ? "d-block d-md-none" : "d-block"} w-100 px-2 py-2`}>
        <div className="shimmer-bg skeleton-slider-mobile w-100" style={{ height: "160px", borderRadius: "10px" }} />
      </div>
    );
  }

  if (error && (!propBanners || propBanners.length === 0)) {
    return null;
  }

  if (banners.length === 0) {
    return null;
  }

  // Spacing standard (rule.md Rule 10): 8px on the mobile layout, 16px on the desktop layout
  const slideGap = typeof window !== "undefined" && window.innerWidth >= 992 ? 16 : 8;

  return (
    <div className={`${isMobileOnly ? "d-block d-md-none" : "d-block"} w-100 home-banner-section py-2 px-2`}>
      <Swiper
        slidesPerView={1.22}
        spaceBetween={slideGap}
        breakpoints={{
          480: {
            slidesPerView: 1.4
          },
          768: {
            slidesPerView: 2.2
          }
        }}
        className="mySwiper w-100"
      >
        {banners.map((banner, index) => (
          <SwiperSlide key={index}>
            <div className="overflow-hidden shadow-sm" style={{ borderRadius: "10px" }}>
              <BannerImage
                large={banner.large}
                small={banner.small}
                target={banner.target}
                alt={banner.alt || `Slide ${index + 1}`}
                borderRadius="10px"
              />
            </div>
          </SwiperSlide>
        ))}
      </Swiper>
    </div>
  );
}

