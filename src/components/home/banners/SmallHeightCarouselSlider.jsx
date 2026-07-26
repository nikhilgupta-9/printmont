import React from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Pagination, Autoplay } from "swiper/modules";
import useHomeBanners from "../hooks/useHomeBanners";
import BannerImage from "./BannerImage";
import normalizeBanner from "../utils/normalizeBanner";
import "swiper/css";
import "swiper/css/pagination";

/**
 * SmallHeightCarouselSlider — Dedicated component for a carousel slider of slim/small height offer banners.
 * Uses the exact same compact height ratio as SmallHeightStaticBanner for smooth sliding of thin promotional offer strips.
 *
 * @param {string} [apiUrl] The API endpoint to fetch banners from.
 * @param {Array} [banners] Static list of banner objects.
 * @param {string} [basePath] Base path for relative image URLs.
 * @param {string} [sectionKey='small_height_carousel'] Section key for API lookup.
 * @param {string} [maxHeight='140px'] Max height constraint.
 * @param {boolean} [autoPlay=true] Enable auto-sliding.
 */
export default function SmallHeightCarouselSlider({
  apiUrl,
  banners: propBanners,
  basePath = "",
  sectionKey = "small_height_carousel",
  maxHeight = "140px",
  autoPlay = true
}) {
  const { banners: fetchedBanners, loading, error } = useHomeBanners(apiUrl, sectionKey, basePath);
  const rawBanners = (fetchedBanners && fetchedBanners.length > 0) ? fetchedBanners : (propBanners || []);
  const banners = rawBanners.map(b => normalizeBanner(b, basePath)).filter(Boolean);

  if (loading && !propBanners) {
    return (
      <div className="w-100 p-2">
        <div 
          className="shimmer-bg rounded border" 
          style={{ height: maxHeight, width: "100%" }} 
        />
      </div>
    );
  }

  if (error && !propBanners) {
    return <div className="text-center p-3 text-danger">Error loading small carousel: {error}</div>;
  }

  if (banners.length === 0) {
    return null;
  }

  const modules = [Pagination];
  if (autoPlay) modules.push(Autoplay);

  return (
    <div className="w-100 home-banner-section p-1 p-md-2">
      <div className="small-height-carousel-box rounded border overflow-hidden shadow-sm bg-white">
        <Swiper
          slidesPerView={1}
          spaceBetween={0}
          loop={banners.length > 1}
          pagination={{ clickable: true }}
          autoplay={autoPlay && banners.length > 1 ? { delay: 3500, disableOnInteraction: false } : false}
          modules={modules}
          className="small-height-swiper w-100"
        >
          {banners.map((banner, index) => (
            <SwiperSlide key={index}>
              <div 
                className="w-100 position-relative" 
                style={{ height: maxHeight, overflow: "hidden" }}
              >
                <BannerImage
                  large={banner.large}
                  small={banner.small}
                  target={banner.target}
                  alt={banner.alt || `Small Height Banner ${index + 1}`}
                  className="w-100 h-100 object-fit-cover"
                  borderRadius="4px"
                />
              </div>
            </SwiperSlide>
          ))}
        </Swiper>
      </div>
    </div>
  );
}
