import React from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Pagination, Autoplay } from "swiper/modules";
import useHomeBanners from "../hooks/useHomeBanners";
import BannerImage from "./BannerImage";
import normalizeBanner from "../utils/normalizeBanner";
import "swiper/css";
import "swiper/css/pagination";

/**
 * SquareSingleImageMobileSlider — Dedicated component for Item #8 from layout diagrams.
 * Renders a single square (1:1 ratio) offer banner carousel slider specifically designed for mobile.
 *
 * @param {string} [apiUrl] The API endpoint to fetch banners from.
 * @param {Array} [banners] Static list of banner objects.
 * @param {string} [basePath] Base path for relative image URLs.
 * @param {string} [sectionKey='mobile_square_slider'] Section key for API lookup.
 * @param {boolean} [isMobileOnly=true] If true, hides on desktop screens using Bootstrap classes.
 * @param {boolean} [autoPlay=true] Enable auto-sliding.
 */
export default function SquareSingleImageMobileSlider({
  apiUrl,
  banners: propBanners,
  basePath = "",
  sectionKey = "mobile_square_slider",
  isMobileOnly = true,
  autoPlay = true
}) {
  const { banners: fetchedBanners, loading, error } = useHomeBanners(apiUrl, sectionKey, basePath);
  const rawBanners = (fetchedBanners && fetchedBanners.length > 0) ? fetchedBanners : (propBanners || []);
  const banners = rawBanners.map(b => normalizeBanner(b, basePath)).filter(Boolean);

  if (loading && !propBanners) {
    return (
      <div className={`${isMobileOnly ? "d-block d-lg-none" : "d-block"} w-100 p-2`}>
        <div 
          className="shimmer-bg rounded border" 
          style={{ aspectRatio: "1 / 1", width: "100%", maxWidth: "450px", margin: "0 auto" }} 
        />
      </div>
    );
  }

  if (error && !propBanners) {
    return <div className="text-center p-3 text-danger">Error loading square slider: {error}</div>;
  }

  if (banners.length === 0) {
    return null;
  }

  const modules = [Pagination];
  if (autoPlay) modules.push(Autoplay);

  return (
    <div 
      className={`${isMobileOnly ? "d-block d-lg-none" : "d-block"} w-100 home-banner-section p-2`}
      style={{ maxWidth: "500px", margin: "0 auto" }}
    >
      <div 
        className="square-banner-slider-box border overflow-hidden shadow-sm bg-white"
        style={{ borderRadius: "4px" }}
      >
        <Swiper
          slidesPerView={1}
          spaceBetween={0}
          loop={banners.length > 1}
          pagination={{ clickable: true }}
          autoplay={autoPlay && banners.length > 1 ? { delay: 3500, disableOnInteraction: false } : false}
          modules={modules}
          className="square-swiper w-100"
        >
          {banners.map((banner, index) => (
            <SwiperSlide key={index}>
              <div 
                className="w-100 position-relative" 
                style={{ aspectRatio: "1 / 1", overflow: "hidden", borderRadius: "4px" }}
              >
                <BannerImage
                  large={banner.large}
                  small={banner.small}
                  target={banner.target}
                  alt={banner.alt || `Square Offer Banner ${index + 1}`}
                  className="w-100 h-100 object-fit-cover aspect-square"
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
