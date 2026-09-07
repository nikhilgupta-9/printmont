import React from "react";
import Slider from "react-slick";
import useHomeBanners from "../hooks/useHomeBanners";
import BannerImage from "./BannerImage";
import { IoIosArrowBack, IoIosArrowForward } from "react-icons/io";
import "../../pages/carousel/carousel.css";

const NextArrow = ({ className, style, onClick }) => (
  <div className={`${className} arrow next`} style={{ ...style, display: "flex" }} onClick={onClick}>
    <IoIosArrowForward />
  </div>
);

const PrevArrow = ({ className, style, onClick }) => (
  <div className={`${className} arrow prev`} style={{ ...style, display: "flex" }} onClick={onClick}>
    <IoIosArrowBack />
  </div>
);

import normalizeBanner from "../utils/normalizeBanner";

/**
 * A responsive carousel that displays multiple banners per row.
 * @param {string} [apiUrl] API endpoint to fetch banner list from.
 * @param {Array} [banners] Static banner list.
 * @param {number} [columns=3] Number of banners to display on desktop.
 * @param {string} [sectionKey] Optional key for nested banner payloads.
 */
export default function MultiColumnBannerCarousel({
  apiUrl,
  banners: propBanners,
  columns = 3,
  sectionKey = ""
}) {
  const { banners: fetchedBanners, loading, error } = useHomeBanners(apiUrl, sectionKey);
  const rawBanners = (fetchedBanners && fetchedBanners.length > 0) ? fetchedBanners : (propBanners || []);
  const banners = rawBanners.map(b => normalizeBanner(b)).filter(Boolean);

  const settings = {
    dots: true,
    infinite: banners.length > columns,
    slidesToShow: columns,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 2000,
    pauseOnHover: false,
    nextArrow: <NextArrow />,
    prevArrow: <PrevArrow />,
    responsive: [
      {
        breakpoint: 992,
        settings: {
          slidesToShow: Math.min(2, columns),
          infinite: banners.length > Math.min(2, columns)
        }
      },
      {
        breakpoint: 576,
        settings: {
          slidesToShow: 1,
          infinite: banners.length > 1
        }
      }
    ]
  };

  if (loading && !propBanners) {
    return (
      <div className="home-banner-grid" data-columns={columns}>
        {Array.from({ length: columns }).map((_, i) => (
          <div key={i} className="shimmer-bg skeleton-grid-3 w-100" />
        ))}
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
    <div className="home-banner-carousel-container w-100" data-columns={columns}>
      <Slider {...settings}>
        {banners.map((banner, index) => (
          <div key={index} className="home-banner-carousel-slide p-1">
            <BannerImage
              large={banner.large}
              small={banner.small}
              target={banner.target}
              alt={banner.alt}
              aspectRatio={columns === 2 ? "1 / 1" : undefined}
            />
          </div>
        ))}
      </Slider>
    </div>
  );
}
