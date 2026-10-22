import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Swiper, SwiperSlide } from "swiper/react";
import { Pagination, Autoplay } from "swiper/modules";
import { resolveImageUrl } from "../../../config/apiEndpoints";
import "swiper/css";
import "swiper/css/pagination";
import "./MobileBannerCarousel.css";

/**
 * MobileBannerCarousel
 * ─────────────────────────────────────────────
 * type="single"  → 1 full-width image per slide
 * type="double"  → 2 images side-by-side per slide (swipeable)
 *
 * Props:
 *   apiUrl   – banner API endpoint (e.g. /banner_api.php?section=category_slider)
 *   type     – "single" | "double"  (default "single")
 *   height   – slide image height   (default uses CSS var --banner-h-mobile)
 */
const MobileBannerCarousel = ({ apiUrl, type = "single", height }) => {
  const [images, setImages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!apiUrl) return;
    let isMounted = true;
    setLoading(true);

    const fetchBanners = async () => {
      try {
        const res = await fetch(apiUrl);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        let list = [];
        if (data?.success && data.data) {
          if (Array.isArray(data.data.banners)) {
            list = data.data.banners;
          } else if (Array.isArray(data.data)) {
            list = data.data;
          } else if (typeof data.data === "object") {
            const keys = Object.keys(data.data);
            for (const key of keys) {
              const val = data.data[key];
              if (Array.isArray(val?.banners)) {
                list = val.banners;
                break;
              }
            }
          }
        } else if (Array.isArray(data)) {
          list = data;
        }

        let formatted = list
          .map((item) => {
            let rawSrc = "";
            if (typeof item === "string") {
              rawSrc = item;
            } else if (item.images?.mobile) {
              rawSrc = item.images.mobile;
            } else if (item.images?.desktop) {
              rawSrc = item.images.desktop;
            } else if (item.image_url_mobile) {
              rawSrc = item.image_url_mobile;
            } else if (item.image_url_desktop) {
              rawSrc = item.image_url_desktop;
            } else if (item.url) {
              rawSrc = item.url;
            } else if (item.src) {
              rawSrc = item.src;
            }

            const src = rawSrc ? resolveImageUrl(rawSrc) : "";
            const targetUrl = item.target_url || item.link || item.url || "";
            return {
              id: item.id,
              src,
              alt: item.alt || item.title || "",
              targetUrl: (targetUrl && targetUrl !== "#") ? targetUrl : ""
            };
          })
          .filter((img) => img.src);

        // Fallbacks only if API returned no valid banners
        if (!formatted.length) {
          if (type === "double") {
            if (apiUrl.includes("category_double_2")) {
              formatted = [
                { src: "https://picsum.photos/id/1050/600/220", alt: "Double Banner E", targetUrl: "" },
                { src: "https://picsum.photos/id/1051/600/220", alt: "Double Banner F", targetUrl: "" },
                { src: "https://picsum.photos/id/1052/600/220", alt: "Double Banner G", targetUrl: "" },
                { src: "https://picsum.photos/id/1053/600/220", alt: "Double Banner H", targetUrl: "" }
              ];
            } else if (apiUrl.includes("category_double_3")) {
              formatted = [
                { src: "https://picsum.photos/id/1060/600/220", alt: "Double Banner I", targetUrl: "" },
                { src: "https://picsum.photos/id/1061/600/220", alt: "Double Banner J", targetUrl: "" },
                { src: "https://picsum.photos/id/1062/600/220", alt: "Double Banner K", targetUrl: "" },
                { src: "https://picsum.photos/id/1063/600/220", alt: "Double Banner L", targetUrl: "" }
              ];
            } else if (apiUrl.includes("category_double_4")) {
              formatted = [
                { src: "https://picsum.photos/id/1070/600/220", alt: "Double Banner M", targetUrl: "" },
                { src: "https://picsum.photos/id/1071/600/220", alt: "Double Banner N", targetUrl: "" },
                { src: "https://picsum.photos/id/1072/600/220", alt: "Double Banner O", targetUrl: "" },
                { src: "https://picsum.photos/id/1073/600/220", alt: "Double Banner P", targetUrl: "" }
              ];
            } else if (apiUrl.includes("category_double_5")) {
              formatted = [
                { src: "https://picsum.photos/id/1080/600/220", alt: "Double Banner Q", targetUrl: "" },
                { src: "https://picsum.photos/id/1081/600/220", alt: "Double Banner R", targetUrl: "" },
                { src: "https://picsum.photos/id/1082/600/220", alt: "Double Banner S", targetUrl: "" },
                { src: "https://picsum.photos/id/1083/600/220", alt: "Double Banner T", targetUrl: "" }
              ];
            } else {
              formatted = [
                { src: "https://picsum.photos/id/1015/600/220", alt: "Double Banner A", targetUrl: "" },
                { src: "https://picsum.photos/id/1016/600/220", alt: "Double Banner B", targetUrl: "" },
                { src: "https://picsum.photos/id/1018/600/220", alt: "Double Banner C", targetUrl: "" },
                { src: "https://picsum.photos/id/1019/600/220", alt: "Double Banner D", targetUrl: "" }
              ];
            }
          } else if (apiUrl.includes("category_banner_slider_2")) {
            formatted = [
              { src: "https://picsum.photos/id/1020/1200/280", alt: "Banner and Slider A", targetUrl: "" },
              { src: "https://picsum.photos/id/1021/1200/280", alt: "Banner and Slider B", targetUrl: "" },
              { src: "https://picsum.photos/id/1022/1200/280", alt: "Banner and Slider C", targetUrl: "" }
            ];
          } else if (apiUrl.includes("category_banner_slider_3")) {
            formatted = [
              { src: "https://picsum.photos/id/1025/1200/280", alt: "Banner and Slider D", targetUrl: "" },
              { src: "https://picsum.photos/id/1026/1200/280", alt: "Banner and Slider E", targetUrl: "" },
              { src: "https://picsum.photos/id/1027/1200/280", alt: "Banner and Slider F", targetUrl: "" }
            ];
          } else if (apiUrl.includes("category_banner_slider_4")) {
            formatted = [
              { src: "https://picsum.photos/id/1031/1200/280", alt: "Banner and Slider G", targetUrl: "" },
              { src: "https://picsum.photos/id/1032/1200/280", alt: "Banner and Slider H", targetUrl: "" },
              { src: "https://picsum.photos/id/1033/1200/280", alt: "Banner and Slider I", targetUrl: "" }
            ];
          } else if (apiUrl.includes("category_banner_slider_5")) {
            formatted = [
              { src: "https://picsum.photos/id/1045/1200/280", alt: "Banner and Slider J", targetUrl: "" },
              { src: "https://picsum.photos/id/1046/1200/280", alt: "Banner and Slider K", targetUrl: "" },
              { src: "https://picsum.photos/id/1047/1200/280", alt: "Banner and Slider L", targetUrl: "" }
            ];
          } else if (apiUrl.includes("category_banner_slider")) {
            formatted = [
              { src: "https://picsum.photos/id/1039/1200/280", alt: "Banner and Slider A", targetUrl: "" },
              { src: "https://picsum.photos/id/1036/1200/280", alt: "Banner and Slider B", targetUrl: "" },
              { src: "https://picsum.photos/id/1041/1200/280", alt: "Banner and Slider C", targetUrl: "" }
            ];
          } else {
            formatted = [
              { src: "https://picsum.photos/id/1048/800/300", alt: "Slider Banner A", targetUrl: "" },
              { src: "https://picsum.photos/id/1043/800/300", alt: "Slider Banner B", targetUrl: "" },
              { src: "https://picsum.photos/id/1044/800/300", alt: "Slider Banner C", targetUrl: "" }
            ];
          }
        }

        if (isMounted) {
          setImages(formatted);
          setError(null);
        }
      } catch (err) {
        if (isMounted) {
          setError(err.message);
        }
      } finally {
        if (isMounted) setLoading(false);
      }
    };

    fetchBanners();
    return () => {
      isMounted = false;
    };
  }, [apiUrl, type]);

  /* ── Helper to render image or clickable linked image ── */
  const renderSlideContent = (img, idx, isDouble = false) => {
    const imageEl = (
      <img
        src={img.src}
        alt={img.alt || `banner-${idx}`}
        className="mbc-img"
        style={height ? { height } : {}}
        loading="lazy"
      />
    );

    if (img.targetUrl) {
      if (img.targetUrl.startsWith("http://") || img.targetUrl.startsWith("https://")) {
        return (
          <a href={img.targetUrl} target="_blank" rel="noopener noreferrer" className="d-block w-100 h-100 text-decoration-none">
            {imageEl}
          </a>
        );
      }
      return (
        <Link to={img.targetUrl} className="d-block w-100 h-100 text-decoration-none">
          {imageEl}
        </Link>
      );
    }

    return imageEl;
  };

  /* ── Loading skeleton ── */
  if (loading) {
    return (
      <div className="mbc-skeleton-wrap">
        {type === "double" ? (
          <div className="mbc-skeleton-double">
            <div className="mbc-skeleton-box" />
            <div className="mbc-skeleton-box" />
          </div>
        ) : (
          <div className="mbc-skeleton-box mbc-skeleton-single" />
        )}
      </div>
    );
  }

  if (error || !images.length) return null;

  /* ── Double: 1 full show and 1 half show (offset peeking) ── */
  if (type === "double") {
    return (
      <Swiper
        modules={[Autoplay]}
        slidesPerView={1.42}
        spaceBetween={12}
        slidesOffsetBefore={12}
        slidesOffsetAfter={12}
        autoplay={{ delay: 3500, disableOnInteraction: false }}
        loop={images.length > 1}
        className="mbc-swiper mbc-swiper--double"
      >
        {images.map((img, idx) => (
          <SwiperSlide key={img.id || idx} className="mbc-slide mbc-slide--double-peek">
            <div className="mbc-double-img-wrap">
              {renderSlideContent(img, idx, true)}
            </div>
          </SwiperSlide>
        ))}
      </Swiper>
    );
  }

  /* ── Single: one image per slide ── */
  return (
    <Swiper
      modules={[Pagination, Autoplay]}
      pagination={{ clickable: true }}
      autoplay={{ delay: 3000, disableOnInteraction: false }}
      loop={images.length > 1}
      className="mbc-swiper mbc-swiper--single"
    >
      {images.map((img, idx) => (
        <SwiperSlide key={img.id || idx} className="mbc-slide mbc-slide--single">
          {renderSlideContent(img, idx, false)}
        </SwiperSlide>
      ))}
    </Swiper>
  );
};

export default MobileBannerCarousel;
