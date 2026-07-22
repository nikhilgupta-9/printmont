import React, { useEffect, useState } from "react";
import { Swiper, SwiperSlide } from "swiper/react";
import { Pagination, Autoplay } from "swiper/modules";
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
 *   apiUrl   – banner API endpoint
 *   type     – "single" | "double"  (default "single")
 *   height   – slide image height   (default uses CSS var --banner-h-mobile)
 */
const MobileBannerCarousel = ({ apiUrl, type = "single", height }) => {
  const [images, setImages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!apiUrl) return;
    const fetchBanners = async () => {
      try {
        const res = await fetch(apiUrl);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        let list = [];
        if (data?.success && data.data) {
          // structured API: try known keys first
          const keys = Object.keys(data.data);
          for (const key of keys) {
            const section = data.data[key];
            if (section?.banners) { list = section.banners; break; }
          }
          if (!list.length && Array.isArray(data.data)) list = data.data;
        } else if (Array.isArray(data)) {
          list = data;
        }

        let formatted = list.map((item) => {
          let src = "";
          if (typeof item === "string") src = item;
          else if (item.url) src = item.url;
          else if (item.src) src = item.src;
          else if (item.images) src = item.images.mobile || item.images.desktop || "";
          else src = item.image_url_mobile || item.image_url_desktop || "";
          return { src, alt: item.alt || item.title || "" };
        }).filter(img => img.src);

        if (!formatted.length) {
          if (type === "double") {
            if (apiUrl.includes("category_double_2")) {
              formatted = [
                { src: "https://picsum.photos/id/1050/600/220", alt: "Double Banner E" },
                { src: "https://picsum.photos/id/1051/600/220", alt: "Double Banner F" },
                { src: "https://picsum.photos/id/1052/600/220", alt: "Double Banner G" },
                { src: "https://picsum.photos/id/1053/600/220", alt: "Double Banner H" }
              ];
            } else if (apiUrl.includes("category_double_3")) {
              formatted = [
                { src: "https://picsum.photos/id/1060/600/220", alt: "Double Banner I" },
                { src: "https://picsum.photos/id/1061/600/220", alt: "Double Banner J" },
                { src: "https://picsum.photos/id/1062/600/220", alt: "Double Banner K" },
                { src: "https://picsum.photos/id/1063/600/220", alt: "Double Banner L" }
              ];
            } else if (apiUrl.includes("category_double_4")) {
              formatted = [
                { src: "https://picsum.photos/id/1070/600/220", alt: "Double Banner M" },
                { src: "https://picsum.photos/id/1071/600/220", alt: "Double Banner N" },
                { src: "https://picsum.photos/id/1072/600/220", alt: "Double Banner O" },
                { src: "https://picsum.photos/id/1073/600/220", alt: "Double Banner P" }
              ];
            } else if (apiUrl.includes("category_double_5")) {
              formatted = [
                { src: "https://picsum.photos/id/1080/600/220", alt: "Double Banner Q" },
                { src: "https://picsum.photos/id/1081/600/220", alt: "Double Banner R" },
                { src: "https://picsum.photos/id/1082/600/220", alt: "Double Banner S" },
                { src: "https://picsum.photos/id/1083/600/220", alt: "Double Banner T" }
              ];
            } else {
              formatted = [
                { src: "https://picsum.photos/id/1015/600/220", alt: "Double Banner A" },
                { src: "https://picsum.photos/id/1016/600/220", alt: "Double Banner B" },
                { src: "https://picsum.photos/id/1018/600/220", alt: "Double Banner C" },
                { src: "https://picsum.photos/id/1019/600/220", alt: "Double Banner D" }
              ];
            }
          } else if (apiUrl.includes("category_banner_slider_2")) {
            formatted = [
              { src: "https://picsum.photos/id/1020/1200/280", alt: "Banner and Slider A" },
              { src: "https://picsum.photos/id/1021/1200/280", alt: "Banner and Slider B" },
              { src: "https://picsum.photos/id/1022/1200/280", alt: "Banner and Slider C" }
            ];
          } else if (apiUrl.includes("category_banner_slider_3")) {
            formatted = [
              { src: "https://picsum.photos/id/1025/1200/280", alt: "Banner and Slider D" },
              { src: "https://picsum.photos/id/1026/1200/280", alt: "Banner and Slider E" },
              { src: "https://picsum.photos/id/1027/1200/280", alt: "Banner and Slider F" }
            ];
          } else if (apiUrl.includes("category_banner_slider_4")) {
            formatted = [
              { src: "https://picsum.photos/id/1031/1200/280", alt: "Banner and Slider G" },
              { src: "https://picsum.photos/id/1032/1200/280", alt: "Banner and Slider H" },
              { src: "https://picsum.photos/id/1033/1200/280", alt: "Banner and Slider I" }
            ];
          } else if (apiUrl.includes("category_banner_slider_5")) {
            formatted = [
              { src: "https://picsum.photos/id/1045/1200/280", alt: "Banner and Slider J" },
              { src: "https://picsum.photos/id/1046/1200/280", alt: "Banner and Slider K" },
              { src: "https://picsum.photos/id/1047/1200/280", alt: "Banner and Slider L" }
            ];
          } else if (apiUrl.includes("category_banner_slider")) {
            formatted = [
              { src: "https://picsum.photos/id/1039/1200/280", alt: "Banner and Slider A" },
              { src: "https://picsum.photos/id/1036/1200/280", alt: "Banner and Slider B" },
              { src: "https://picsum.photos/id/1041/1200/280", alt: "Banner and Slider C" }
            ];
          } else {
            formatted = [
              { src: "https://picsum.photos/id/1048/800/300", alt: "Slider Banner A" },
              { src: "https://picsum.photos/id/1043/800/300", alt: "Slider Banner B" },
              { src: "https://picsum.photos/id/1044/800/300", alt: "Slider Banner C" }
            ];
          }
        }

        setImages(formatted);
      } catch (err) {
        let formatted = [];
        if (type === "double") {
          if (apiUrl.includes("category_double_2")) {
            formatted = [
              { src: "https://picsum.photos/id/1050/600/220", alt: "Double Banner E" },
              { src: "https://picsum.photos/id/1051/600/220", alt: "Double Banner F" },
              { src: "https://picsum.photos/id/1052/600/220", alt: "Double Banner G" },
              { src: "https://picsum.photos/id/1053/600/220", alt: "Double Banner H" }
            ];
          } else if (apiUrl.includes("category_double_3")) {
            formatted = [
              { src: "https://picsum.photos/id/1060/600/220", alt: "Double Banner I" },
              { src: "https://picsum.photos/id/1061/600/220", alt: "Double Banner J" },
              { src: "https://picsum.photos/id/1062/600/220", alt: "Double Banner K" },
              { src: "https://picsum.photos/id/1063/600/220", alt: "Double Banner L" }
            ];
          } else if (apiUrl.includes("category_double_4")) {
            formatted = [
              { src: "https://picsum.photos/id/1070/600/220", alt: "Double Banner M" },
              { src: "https://picsum.photos/id/1071/600/220", alt: "Double Banner N" },
              { src: "https://picsum.photos/id/1072/600/220", alt: "Double Banner O" },
              { src: "https://picsum.photos/id/1073/600/220", alt: "Double Banner P" }
            ];
          } else if (apiUrl.includes("category_double_5")) {
            formatted = [
              { src: "https://picsum.photos/id/1080/600/220", alt: "Double Banner Q" },
              { src: "https://picsum.photos/id/1081/600/220", alt: "Double Banner R" },
              { src: "https://picsum.photos/id/1082/600/220", alt: "Double Banner S" },
              { src: "https://picsum.photos/id/1083/600/220", alt: "Double Banner T" }
            ];
          } else {
            formatted = [
              { src: "https://picsum.photos/id/1015/600/220", alt: "Double Banner A" },
              { src: "https://picsum.photos/id/1016/600/220", alt: "Double Banner B" },
              { src: "https://picsum.photos/id/1018/600/220", alt: "Double Banner C" },
              { src: "https://picsum.photos/id/1019/600/220", alt: "Double Banner D" }
            ];
          }
        } else if (apiUrl.includes("category_banner_slider_2")) {
          formatted = [
            { src: "https://picsum.photos/id/1020/1200/280", alt: "Banner and Slider A" },
            { src: "https://picsum.photos/id/1021/1200/280", alt: "Banner and Slider B" },
            { src: "https://picsum.photos/id/1022/1200/280", alt: "Banner and Slider C" }
          ];
        } else if (apiUrl.includes("category_banner_slider_3")) {
          formatted = [
            { src: "https://picsum.photos/id/1025/1200/280", alt: "Banner and Slider D" },
            { src: "https://picsum.photos/id/1026/1200/280", alt: "Banner and Slider E" },
            { src: "https://picsum.photos/id/1027/1200/280", alt: "Banner and Slider F" }
          ];
        } else if (apiUrl.includes("category_banner_slider_4")) {
          formatted = [
            { src: "https://picsum.photos/id/1031/1200/280", alt: "Banner and Slider G" },
            { src: "https://picsum.photos/id/1032/1200/280", alt: "Banner and Slider H" },
            { src: "https://picsum.photos/id/1033/1200/280", alt: "Banner and Slider I" }
          ];
        } else if (apiUrl.includes("category_banner_slider_5")) {
          formatted = [
            { src: "https://picsum.photos/id/1045/1200/280", alt: "Banner and Slider J" },
            { src: "https://picsum.photos/id/1046/1200/280", alt: "Banner and Slider K" },
            { src: "https://picsum.photos/id/1047/1200/280", alt: "Banner and Slider L" }
          ];
        } else if (apiUrl.includes("category_banner_slider")) {
          formatted = [
            { src: "https://picsum.photos/id/1039/1200/280", alt: "Banner and Slider A" },
            { src: "https://picsum.photos/id/1036/1200/280", alt: "Banner and Slider B" },
            { src: "https://picsum.photos/id/1041/1200/280", alt: "Banner and Slider C" }
          ];
        } else {
          formatted = [
            { src: "https://picsum.photos/id/1048/800/300", alt: "Slider Banner A" },
            { src: "https://picsum.photos/id/1043/800/300", alt: "Slider Banner B" },
            { src: "https://picsum.photos/id/1044/800/300", alt: "Slider Banner C" }
          ];
        }
        setImages(formatted);
      } finally {
        setLoading(false);
      }
    };
    fetchBanners();
  }, [apiUrl]);

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
          <SwiperSlide key={idx} className="mbc-slide mbc-slide--double-peek">
            <div className="mbc-double-img-wrap">
              <img
                src={img.src}
                alt={img.alt || `banner-${idx}`}
                className="mbc-img"
                style={height ? { height } : {}}
                loading="lazy"
              />
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
        <SwiperSlide key={idx} className="mbc-slide mbc-slide--single">
          <img
            src={img.src}
            alt={img.alt || `banner-${idx}`}
            className="mbc-img"
            style={height ? { height } : {}}
            loading="lazy"
          />
        </SwiperSlide>
      ))}
    </Swiper>
  );
};

export default MobileBannerCarousel;
