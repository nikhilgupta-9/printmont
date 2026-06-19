import React, { useEffect, useState } from "react";
import Slider from "react-slick";
import PropTypes from "prop-types";
import { IoIosArrowBack, IoIosArrowForward } from "react-icons/io";
import "./carousel.css";
import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";

// Custom next arrow
const NextArrow = ({ onClick }) => (
  <div className="arrow next" onClick={onClick}>
    <IoIosArrowForward />
  </div>
);

// Custom prev arrow
const PrevArrow = ({ onClick }) => (
  <div className="arrow prev" onClick={onClick}>
    <IoIosArrowBack />
  </div>
);

const ThreeImgCarousel = ({ apiUrl }) => {
  const [images, setImages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchImages = async () => {
      try {
        const response = await fetch(apiUrl);
        if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);

        const data = await response.json();

        let bannerList = [];
        if (data && data.success && data.data) {
          if (data.data.home_mid_section_1 && data.data.home_mid_section_1.banners) {
            bannerList = data.data.home_mid_section_1.banners;
          } else if (Array.isArray(data.data)) {
            bannerList = data.data;
          }
        } else if (Array.isArray(data)) {
          bannerList = data;
        }

        const formattedImages = bannerList.map((item) => {
          let src = '';
          if (typeof item === 'string') src = item;
          else if (item.url) src = item.url;
          else if (item.src) src = item.src;
          else if (item.images) src = item.images.desktop || item.images.mobile || '';
          else src = item.image_url_desktop || item.image_url_mobile || '';

          return {
            src: src,
            alt: item.alt || item.title || "",
          };
        });

        setImages(formattedImages);
      } catch (err) {
        console.error("❌ Fetch error:", err);
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchImages();
  }, [apiUrl]);

  const settings = {
    dots: true,
    infinite: true,
    slidesToShow: 3,
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
          slidesToShow: 2,
        },
      },
      {
        breakpoint: 576,
        settings: {
          slidesToShow: 1,
        },
      },
    ],
  };

  if (loading) {
    return (
      <div className="container-fluid mx-0 p-0 px-1">
        <div className="row g-2 m-0">
          {[1, 2, 3].map((item) => (
            <div key={item} className="col-12 col-md-4 px-1">
              <div className="shimmer-bg skeleton-grid-3 w-100"></div>
            </div>
          ))}
        </div>
      </div>
    );
  }
  if (error)
    return <div className="text-center text-danger p-5">Error: {error}</div>;

  return (
    <div className="container-fluid mx-0 p-0 px-1">
      <Slider {...settings} className="px-0 mx-0">
        {images.map((img, index) => (
          <div key={index} className="slide-item mx-0 px-1">
            <img
              src={img.src}
              alt={img.alt || `slide-${index}`}
              className="carousel-img"
              loading="lazy"
            />
          </div>
        ))}
      </Slider>
    </div>
  );
};

ThreeImgCarousel.propTypes = {
  apiUrl: PropTypes.string.isRequired,
};

export default ThreeImgCarousel;
