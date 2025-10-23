import React from 'react';
import { IoIosArrowForward, IoIosArrowBack } from 'react-icons/io';

const FirstCarousel = ({ images = [], carouselId = 'carouselExample' }) => {
  return (
    <div
      id={carouselId}
      className="carousel slide container-fluid m-0 p-0 p-md-2"
      data-bs-ride="carousel"
      data-bs-interval="3000"
    >
      <div className="carousel-inner">
        {images.length > 0 ? (
          images.map((imgSrcs, index) => (
            <div
              className={`carousel-item ${index === 0 ? 'active' : ''}`}
              key={index}
            >
              <picture>
                {/* For large screens */}
                <source media="(min-width: 778px)" srcSet={imgSrcs.large} />
                {/* Default image for small screens */}
                <img
                  src={imgSrcs.small}
                  className="d-block w-100"
                  alt={`Slide ${index + 1}`}
                  style={{ objectFit: 'cover', maxHeight: '500px' }}
                />
              </picture>
            </div>
          ))
        ) : (
          <div className="text-center p-5 text-muted">No carousel images available</div>
        )}
      </div>

      {/* Carousel Controls */}
      <button
        className="carousel-control-prev d-flex align-items-center justify-content-start d-none d-md-flex ms-2"
        type="button"
        data-bs-target={`#${carouselId}`}
        data-bs-slide="prev"
      >
        <span className="left-arr-carousel text-black bg-white">
          <IoIosArrowBack />
        </span>
        <span className="visually-hidden">Previous</span>
      </button>

      <button
        className="carousel-control-next d-flex align-items-center justify-content-end me-2 d-none d-md-flex"
        type="button"
        data-bs-target={`#${carouselId}`}
        data-bs-slide="next"
      >
        <span className="right-arr-carousel text-black bg-white">
          <IoIosArrowForward />
        </span>
        <span className="visually-hidden">Next</span>
      </button>
    </div>
  );
};

export default FirstCarousel;
