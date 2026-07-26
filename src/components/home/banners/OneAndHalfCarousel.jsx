import React from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import useHomeBanners from "../hooks/useHomeBanners";
import BannerImage from "./BannerImage";
import "swiper/css";

/**
 * Mobile-only 1.5 Peek Image Carousel (shows 1 full image + 50% peek of the next image).
 * @param {string} [apiUrl] The API endpoint to fetch banners from.
 * @param {Array} [banners] Static banners list.
 * @param {string} [sectionKey] Optional section key payload.
 */
export default function OneAndHalfCarousel({
  apiUrl,
  banners: propBanners,
  sectionKey = "",
  isMobileOnly = true
}) {
  const { banners: fetchedBanners, loading, error } = useHomeBanners(apiUrl, sectionKey);
  const banners = propBanners || fetchedBanners || [];

  if (loading && !propBanners) {
    return (
      <div className={`${isMobileOnly ? "d-block d-md-none" : "d-block"} w-100 px-2`}>
        <div className="shimmer-bg skeleton-slider-mobile w-100" />
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
    <div className={`${isMobileOnly ? "d-block d-md-none" : "d-block"} w-100 home-banner-section p-0 m-0`}>
      <Swiper
        slidesPerView={1.25}
        spaceBetween={12}
        breakpoints={{
          480: {
            slidesPerView: 1.5,
            spaceBetween: 14
          }
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
            />
          </SwiperSlide>
        ))}
      </Swiper>
    </div>
  );
}
