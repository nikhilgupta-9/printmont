import React, { useState, useRef } from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Autoplay } from "swiper/modules";
import useHomeBanners from "../hooks/useHomeBanners";
import BannerImage from "./BannerImage";
import "swiper/css";

import normalizeBanner from "../utils/normalizeBanner";

/**
 * Mobile-only Hero / Promo Slider with Overlay Pagination Dots.
 * - Uniform slide image height across all slides
 * - Centered pill capsule dots positioned OVER the banner (not below)
 * - Auto-running speed support
 */
export default function MobileHeroSlider({
  apiUrl,
  sectionKey = "home_hero",
  banners: propBanners,
  basePath = "",
  slidesPerView = 1,
  spaceBetween = 0,
  isMobileOnly = true,
  autoPlaySpeed = 3000,
  aspectRatio = "16 / 7"
}) {
  const { banners: fetchedBanners, loading, error } = useHomeBanners(apiUrl, sectionKey, basePath);
  const rawBanners = (propBanners && propBanners.length > 0) ? propBanners : (fetchedBanners || []);
  const banners = rawBanners.map(b => normalizeBanner(b, basePath)).filter(Boolean);

  const [activeIndex, setActiveIndex] = useState(0);
  const swiperRef = useRef(null);

  if (loading && !propBanners) {
    return (
      <div className={`${isMobileOnly ? "d-block d-lg-none" : "d-block"} w-100 p-1`}>
        <div 
          className="shimmer-bg w-100" 
          style={{ borderRadius: "8px", aspectRatio: aspectRatio || "16 / 7" }} 
        />
      </div>
    );
  }

  if (error && !propBanners) {
    return null;
  }

  if (banners.length === 0) {
    return null;
  }

  return (
    <div 
      className={`${isMobileOnly ? "d-block d-lg-none" : "d-block"} mobile-hero-slider-section w-100 m-0 p-0`}
      style={{
        margin: "0 0 4px 0",
        padding: 0,
        width: "100%",
        boxSizing: "border-box"
      }}
    >
      {/* Slider Card Container with Overlay Dots */}
      <div 
        className="w-100 overflow-hidden bg-white position-relative rounded-2 shadow-sm"
        style={{ border: "none", padding: 0 }}
      >
        <Swiper
          slidesPerView={slidesPerView}
          spaceBetween={spaceBetween}
          loop={banners.length > 1}
          autoplay={{
            delay: autoPlaySpeed || 3000,
            disableOnInteraction: false
          }}
          modules={[Autoplay]}
          onSwiper={(swiper) => {
            swiperRef.current = swiper;
          }}
          onSlideChange={(swiper) => {
            setActiveIndex(swiper.realIndex);
          }}
          className="mySwiper w-100"
        >
          {banners.map((banner, index) => (
            <SwiperSlide key={index}>
              <BannerImage
                large={banner.large}
                small={banner.small}
                target={banner.target}
                alt={banner.alt || `Slide ${index + 1}`}
                aspectRatio={aspectRatio || "16 / 7"}
                className="w-100 h-100"
              />
            </SwiperSlide>
          ))}
        </Swiper>

        {/* Overlay Pagination Dots OVER the banner image */}
        {banners.length > 1 && (
          <div 
            className="position-absolute d-flex justify-content-center align-items-center"
            style={{
              bottom: "8px",
              left: "50%",
              transform: "translateX(-50%)",
              zIndex: 10,
              pointerEvents: "auto"
            }}
          >
            <div 
              className="d-inline-flex align-items-center justify-content-center"
              style={{
                backgroundColor: "rgba(0, 0, 0, 0.45)",
                backdropFilter: "blur(4px)",
                borderRadius: "9999px",
                padding: "2.5px 7px",
                gap: "5px"
              }}
            >
              {banners.map((_, dotIdx) => {
                const isActive = dotIdx === activeIndex;
                return (
                  <button
                    key={dotIdx}
                    type="button"
                    onClick={(e) => {
                      e.preventDefault();
                      e.stopPropagation();
                      if (swiperRef.current) {
                        swiperRef.current.slideToLoop(dotIdx);
                      }
                    }}
                    aria-label={`Go to slide ${dotIdx + 1}`}
                    style={{
                      width: isActive ? "14px" : "6px",
                      height: "6px",
                      borderRadius: "9999px",
                      padding: 0,
                      margin: 0,
                      cursor: "pointer",
                      border: "none",
                      backgroundColor: isActive ? "#ffffff" : "rgba(255, 255, 255, 0.55)",
                      transition: "all 0.25s ease-in-out"
                    }}
                  />
                );
              })}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
