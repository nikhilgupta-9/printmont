import React from "react";
import Slider from "react-slick";
import PropTypes from "prop-types";
import { IoIosArrowBack, IoIosArrowForward } from "react-icons/io";
import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import "./carousel.css"; // Ensure your CSS is imported here!

// --- RICH DATA STRUCTURE (Passed in by the user) ---
// Note: I will use the user-provided 'twoimgcarousel' structure as the default for consistency
const carouselDataWithDetails = [
    { 
        src: "/section-img/banner1.jpg", 
        alt: "First Image", 
        heading: "Design Trends: Corporate Gifts",
        description: "Explore the latest trends in corporate branding and gifting strategies.",
        dateTime: "Nov 12, 2025 | 10:30 AM",
    },
    { 
        src: "/section-img/banner2.jpg", 
        alt: "First Image",
        heading: "Business Insights: New Gift Strategies",
        description: "How to choose the perfect business gift to impress clients and partners.",
        dateTime: "Nov 11, 2025 | 09:00 AM",
    },
    { 
        src: "/section-img/banner4.jpg", 
        alt: "First Image",
        heading: "Business Insights: New Gift Strategies",
        description: "How to choose the perfect business gift to impress clients and partners.",
        dateTime: "Nov 11, 2025 | 09:00 AM",
    },
    { 
        src: "/section-img/banner2.jpg", 
        alt: "First Image",
        heading: "Business Insights: New Gift Strategies",
        description: "How to choose the perfect business gift to impress clients and partners.",
        dateTime: "Nov 11, 2025 | 09:00 AM",
    },
];


// --- Custom Arrow Components (Used by both sliders) ---
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

// --- 1. Mobile (1.5 Image) Slider Component (PASSING showDetails) ---
const OneAndHalfImgCarousel = ({ images, showDetails }) => {
  const settings = {
    slidesToShow: 1.5,
    slidesToScroll: 1,
    infinite: false,
    dots: false,
    arrows: false,
    centerMode: false,
  };

  return (
    <div className="one-and-half-carousel-wrapper"> 
      <div className="p-0 m-0 mt-1"> 
        <Slider {...settings}>
          {images.map((img, index) => (
            <div key={index} className="slide-item-mobile pe-2"> 
              <div className="card border-0 shadow-sm">
                <img
                  src={img.src}
                  alt={img.alt || `slide-${index}`}
                  className="carousel-img-mobile rounded-0"
                  style={{ height: '150px', objectFit: 'cover' }}
                />
                
                {/* Text details for Mobile (1.5 image) */}
                {showDetails && (
                  <div className="card-body p-2">
                    <p className="text-muted small mb-1">{img.dateTime}</p>
                    <h6 className="fw-bold mb-1" style={{ fontSize: '0.9rem' }}>{img.heading}</h6>
                    <p className="text-secondary small mb-0">{img.description}</p>
                  </div>
                )}
              </div>
            </div>
          ))}
        </Slider>
      </div>
    </div>
  );
};

// --- 2. Desktop (2 Image) Slider Component (UPDATED to accept and use showDetails) ---
const MainTwoImgCarousel = ({ images, showDetails }) => {
  const settings = {
    dots: true,
    infinite: true,
    slidesToShow: 2,
    slidesToScroll: 1,
    autoplay: false,
    autoplaySpeed: 2000,
    pauseOnHover: false,
    nextArrow: <NextArrow />,
    prevArrow: <PrevArrow />,
    responsive: [
      {
        breakpoint: 576,
        settings: {
          slidesToShow: 1,
        },
      },
      {
        breakpoint: 992,
        settings: {
          slidesToShow: 2,
        },
      },
    ],
  };

  return (
    <div className="container-fluid mx-0 mt-2 p-0"> 
      <Slider {...settings} className="px-0 mx-0">
        {images.map((img, index) => (
          // Wrapped the slide content in a card/div to contain the image and text
          <div key={index} className="slide-item-desktop px-2"> 
            <div className="card border-0 shadow-sm">
              <img
                src={img.src} 
                alt={img.alt || `slide-${index}`}
                className="carousel-img"
                style={{ objectFit: 'cover' }}
              />
              
              {/* Text details for Desktop (2 image) */}
              {showDetails && (
                <div className="card-body p-3"> {/* Use p-3 for slightly larger desktop spacing */}
                  <h5 className="fw-bold mb-1">{img.heading}</h5> {/* Larger heading on desktop */}
                  <p className="text-muted small mb-1">{img.dateTime}</p>
                  <p className="text-black mb-0">{img.description}</p>
                </div>
              )}
            </div>
          </div>
        ))}
      </Slider>
    </div>
  );
};

// --- 3. Wrapper Component (Handles conditional rendering and prop passing) ---
const TwoImgCarousel = ({ images = carouselDataWithDetails, showDetails = true }) => {
    return (
        <>
            {/* 1. Mobile Slider (d-block below md) */}
            <div className="d-block d-md-none p-0 m-0">
                {/* Pass showDetails prop down */}
                <OneAndHalfImgCarousel images={images} showDetails={showDetails} />
            </div>

            {/* 2. Desktop Slider (d-block above md) */}
            <div className="d-none d-md-block p-0 m-0">
                {/* Pass showDetails prop down */}
                <MainTwoImgCarousel images={images} showDetails={showDetails} />
            </div>
        </>
    );
};

TwoImgCarousel.propTypes = {
  images: PropTypes.arrayOf(
    PropTypes.shape({
      src: PropTypes.string.isRequired,
      alt: PropTypes.string,
      heading: PropTypes.string,
      description: PropTypes.string,
      dateTime: PropTypes.string,
    })
  ),
  showDetails: PropTypes.bool,
};

export default TwoImgCarousel;