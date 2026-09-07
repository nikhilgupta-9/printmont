import React from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Pagination } from "swiper/modules";
import useHomeBanners from "../hooks/useHomeBanners";
import BannerImage from "./BannerImage";
import "swiper/css";
import "swiper/css/pagination";

import normalizeBanner from "../utils/normalizeBanner";

/**
 * Mobile-only Hero Slider using Swiper.
 * @param {string} [apiUrl] The API endpoint to fetch banners from.
 * @param {Array} [banners] Static banners list.
 * @param {string} [basePath=''] Base path for relative image URLs.
 */
export default function MobileHeroSlider({
  apiUrl,
  banners: propBanners,
  basePath = "",
  slidesPerView = 1,
  spaceBetween = 0,
  isMobileOnly = true
}) {
  const { banners: fetchedBanners, loading, error } = useHomeBanners(apiUrl, "home_hero", basePath);
  const rawBanners = (fetchedBanners && fetchedBanners.length > 0) ? fetchedBanners : (propBanners || []);
  const banners = rawBanners.map(b => normalizeBanner(b, basePath)).filter(Boolean);

  if (loading && !propBanners) {
    return (
      <div className={`${isMobileOnly ? "d-block d-lg-none" : "d-block"} w-100`}>
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

  const showPagination = slidesPerView === 1;

  return (
    <div className={`${isMobileOnly ? "d-block d-lg-none" : "d-block"} w-100 home-banner-section`}>
      <Swiper
        slidesPerView={slidesPerView}
        spaceBetween={spaceBetween}
        pagination={showPagination ? { clickable: true } : false}
        modules={showPagination ? [Pagination] : []}
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
