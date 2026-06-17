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

        const formatted = list.map((item) => {
          let src = "";
          if (typeof item === "string") src = item;
          else if (item.url) src = item.url;
          else if (item.src) src = item.src;
          else if (item.images) src = item.images.mobile || item.images.desktop || "";
          else src = item.image_url_mobile || item.image_url_desktop || "";
          return { src, alt: item.alt || item.title || "" };
        }).filter(img => img.src);

        setImages(formatted);
      } catch (err) {
        setError(err.message);
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

  /* ── Double: group images into pairs ── */
  if (type === "double") {
    const pairs = [];
    for (let i = 0; i < images.length; i += 2) {
      pairs.push(images.slice(i, i + 2));
    }
    return (
      <Swiper
        modules={[Pagination, Autoplay]}
        pagination={{ clickable: true }}
        autoplay={{ delay: 3500, disableOnInteraction: false }}
        loop={pairs.length > 1}
        className="mbc-swiper mbc-swiper--double"
      >
        {pairs.map((pair, idx) => (
          <SwiperSlide key={idx} className="mbc-slide mbc-slide--double">
            {pair.map((img, i) => (
              <div key={i} className="mbc-double-img-wrap">
                <img
                  src={img.src}
                  alt={img.alt || `banner-${idx}-${i}`}
                  className="mbc-img"
                  style={height ? { height } : {}}
                  loading="lazy"
                />
              </div>
            ))}
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
