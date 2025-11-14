import React, { useEffect, useState } from 'react';
import { IoIosArrowForward, IoIosArrowBack } from 'react-icons/io';
import { Link } from 'react-router';

const FirstCarousel = ({ apiUrl, carouselId = 'carouselExample', basePath = '' }) => {
  const [images, setImages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchCarouselImages = async () => {
      try {
        const response = await fetch(apiUrl);
        if (!response.ok) throw new Error(`HTTP Error! Status: ${response.status}`);
        const data = await response.json();
        console.log('Carousel API Data:', data);

        // Map API response to expected format
        const formattedData = data.map(item => ({
          large: `${basePath}${item.image_url_desktop}`,
          small: `${basePath}${item.image_url_mobile}`,
          target: item.target_url
        }));

        setImages(formattedData);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchCarouselImages();
  }, [apiUrl, basePath]);

  if (loading) return <div className="text-center p-5">Loading carousel...</div>;
  if (error) return <div className="text-center p-5 text-danger">Error: {error}</div>;

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
              <Link href={imgSrcs.target !== '0' ? imgSrcs.target : '#'} target="_blank" rel="noreferrer">
                <picture>
                  {/* Desktop Image */}
                  <source media="(min-width: 778px)" srcSet={imgSrcs.large} />
                  {/* Mobile Image */}
                  <img
                    src={imgSrcs.small}
                    className="d-block w-100"
                    alt={`Slide ${index + 1}`}
                    style={{ objectFit: 'cover', maxHeight: '500px' }}
                  />
                </picture>
              </Link>
            </div>
          ))
        ) : (
          <div className="text-center p-5 text-muted">No carousel images available</div>
        )}
      </div>

      {/* Controls */}
      <button
        className="carousel-control-prev d-flex align-items-center justify-content-start d-none d-md-flex ms-2"
        type="button"
        data-bs-target={`#${carouselId}`}
        data-bs-slide="prev"
        style={{ marginTop: '100px' }}
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
        style={{ marginTop: '100px' }}
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
