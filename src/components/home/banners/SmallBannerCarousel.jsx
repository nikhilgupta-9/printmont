import React from "react";
import { Link } from "react-router-dom";
import { Swiper, SwiperSlide } from "swiper/react";
import { Pagination, Autoplay } from "swiper/modules";
import useHomeBanners from "../hooks/useHomeBanners";
import BannerImage from "./BannerImage";
import normalizeBanner from "../utils/normalizeBanner";
import { BASE_URL, ASSET_URL } from "../../../config/apiEndpoints";
import "swiper/css";
import "swiper/css/pagination";

/**
 * SmallBannerCarousel — Renders the compact / slim promo banner slider
 * for Desktop and Mobile (immediately below Category Navbar / Header or on target pages).
 *
 * @param {string} [apiUrl] API endpoint
 * @param {Array} [banners] Static banner list fallback
 * @param {string} [basePath] Base path for relative uploads
 * @param {string} [sectionKey='small_banner_slider'] Section key
 * @param {string} [page='home'] Target page identifier (home, category, product, cart, checkout, blog, etc.)
 * @param {number} [autoPlaySpeed=3000] Autoplay delay in ms
 * @param {boolean} [isMobile=false] Mobile mode flag
 */
export default function SmallBannerCarousel({
  apiUrl,
  banners: propBanners,
  basePath = ASSET_URL,
  sectionKey = "small_banner_slider",
  page = "home",
  autoPlaySpeed = 3000,
  isMobile = false,
  height = null // optional fixed height in px (image is cropped to fill); default keeps the image's natural height
}) {
  const effectiveApiUrl = (!propBanners || propBanners.length === 0) 
    ? (apiUrl || `${BASE_URL}/banner_api.php?section=${sectionKey}&page=${page}`)
    : null;
  const { banners: fetchedBanners, loading, error } = useHomeBanners(effectiveApiUrl, sectionKey, basePath);
  
  const rawList = (propBanners && propBanners.length > 0) ? propBanners : (fetchedBanners || []);
  const banners = rawList
    .map(b => normalizeBanner(b, basePath))
    .filter(b => {
      if (!b || (!b.large && !b.small && !b.image)) return false;
      if (isMobile && b.mobile_status === 'inactive') return false;
      if (!isMobile && b.desktop_status === 'inactive') return false;
      return true;
    });

  if (loading && (!propBanners || propBanners.length === 0) && banners.length === 0) {
    return (
      <div className="container-fluid px-2 px-md-3 py-1 my-1">
        <div 
          className="shimmer-bg rounded border" 
          style={{ height: height ? `${height}px` : (isMobile ? "80px" : "110px"), width: "100%" }} 
        />
      </div>
    );
  }

  if (banners.length === 0) {
    return null;
  }

  const speed = banners[0]?.auto_play_speed || banners[0]?.autoPlaySpeed || autoPlaySpeed || 3000;
  const modules = [Autoplay];

  return (
    <div 
      className={`small-banner-carousel-container w-100 ${height ? "small-banner-carousel-container--fixed" : ""}`}
      style={{
        margin: 0,
        padding: 0,
        width: "100%",
        boxSizing: "border-box",
        ...(height ? { "--sbc-height": `${height}px` } : {})
      }}
    >
      <div 
        className="small-banner-carousel-card position-relative overflow-hidden w-100"
        style={{
          border: "none",
          outline: "none",
          boxShadow: "none",
          padding: 0,
          margin: 0,
          background: "transparent",
          borderRadius: 0
        }}
      >
        <Swiper
          slidesPerView={1}
          spaceBetween={0}
          loop={banners.length > 1}
          pagination={false}
          autoplay={banners.length > 1 ? { delay: speed, disableOnInteraction: false } : false}
          modules={modules}
          observer={true}
          observeParents={true}
          className="small-banner-swiper w-100"
          style={{ margin: 0, padding: 0 }}
        >
          {banners.map((banner, index) => {
            const targetLink = isMobile ? (banner.mobile_url || banner.target) : (banner.desktop_url || banner.target);
            const imgSrc = isMobile ? (banner.small || banner.large) : (banner.large || banner.small);
            return (
              <SwiperSlide key={banner.id || index} style={{ margin: 0, padding: 0 }}>
                <div 
                  className="small-banner-slide-inner w-100 position-relative overflow-hidden" 
                  style={{
                    borderRadius: 0,
                    margin: 0,
                    padding: 0
                  }}
                >
                  {targetLink && targetLink !== "#" ? (
                    targetLink.startsWith("http") || targetLink.startsWith("//") ? (
                      <a href={targetLink} target="_blank" rel="noopener noreferrer" className="d-block w-100 text-decoration-none">
                        <img 
                          src={imgSrc} 
                          alt={banner.alt || banner.title || `Small Banner ${index + 1}`}
                          className="w-100 d-block"
                          style={{ objectFit: 'cover', width: '100%', height: 'auto', display: 'block' }}
                        />
                      </a>
                    ) : (
                      <Link to={targetLink} className="d-block w-100 text-decoration-none">
                        <img 
                          src={imgSrc} 
                          alt={banner.alt || banner.title || `Small Banner ${index + 1}`}
                          className="w-100 d-block"
                          style={{ objectFit: 'cover', width: '100%', height: 'auto', display: 'block' }}
                        />
                      </Link>
                    )
                  ) : (
                    <img 
                      src={imgSrc} 
                      alt={banner.alt || banner.title || `Small Banner ${index + 1}`}
                      className="w-100 d-block"
                      style={{ objectFit: 'cover', width: '100%', height: 'auto', display: 'block' }}
                    />
                  )}
                </div>
              </SwiperSlide>
            );
          })}
        </Swiper>
      </div>

      <style>{`
        .small-banner-carousel-container {
          margin: 0 !important;
          padding: 0 !important;
          width: 100% !important;
          line-height: 0 !important;
          overflow: hidden !important;
        }
        .small-banner-carousel-card {
          border: none !important;
          outline: none !important;
          box-shadow: none !important;
          padding: 0 !important;
          margin: 0 !important;
          border-radius: 0 !important;
          background: transparent !important;
          line-height: 0 !important;
          overflow: hidden !important;
        }
        .small-banner-swiper {
          overflow: hidden !important;
        }
        .small-banner-swiper,
        .small-banner-swiper .swiper-wrapper,
        .small-banner-swiper .swiper-slide {
          margin: 0 !important;
          padding: 0 !important;
          line-height: 0 !important;
        }
        .small-banner-slide-inner {
          margin: 0 !important;
          padding: 0 !important;
          line-height: 0 !important;
          height: auto !important;
          min-height: 0 !important;
        }
        .small-banner-slide-inner picture,
        .small-banner-slide-inner img,
        .small-banner-slide-inner .home-banner-picture,
        .small-banner-slide-inner .home-banner-img {
          display: block !important;
          width: 100% !important;
          height: auto !important;
          margin: 0 !important;
          padding: 0 !important;
          border: none !important;
          outline: none !important;
          line-height: 0 !important;
          vertical-align: top !important;
        }
        .small-banner-carousel-container .swiper-pagination {
          display: none !important;
        }
        /* Fixed-height mode: every slide has the same height, image fills it (centre-cropped) */
        .small-banner-carousel-container--fixed .small-banner-slide-inner,
        .small-banner-carousel-container--fixed .small-banner-slide-inner > a {
          display: block !important;
          height: var(--sbc-height) !important;
          background: #f1f3f6;
        }
        .small-banner-carousel-container--fixed .small-banner-slide-inner img {
          height: 100% !important;
          object-fit: cover !important;
          object-position: center !important;
        }
      `}</style>
    </div>
  );
}
