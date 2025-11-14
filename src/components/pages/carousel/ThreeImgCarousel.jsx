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

        const text = await response.text();

        const data = JSON.parse(text);

        // Handle both [{ "src": "..." }] or [{ "url": "..." }] or ["..."]
        const formattedImages = data.map((item) => ({
          src: item.src || item.url || item,
          alt: item.alt || "",
        }));

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

  if (loading)
    return <div className="text-center p-5">Loading carousel...</div>;
  if (error)
    return <div className="text-center text-danger p-5">Error: {error}</div>;

  return (
    <div className="container-fluid mx-0 mt-2 p-0 px-1">
      <Slider {...settings} className="px-0 mx-0">
        {images.map((img, index) => (
          <div key={index} className="slide-item mx-0 px-1">
            <img
              src={img.src}
              alt={img.alt || `slide-${index}`}
              className="carousel-img"
              style={{
                width: "100%",
                height: "auto",
                borderRadius: "8px",
                objectFit: "contain",
              }}
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
