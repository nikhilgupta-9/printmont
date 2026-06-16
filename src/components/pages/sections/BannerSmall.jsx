import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';

const BannerSmall = ({ apiUrl, sliceStart, sliceEnd }) => {
  const [images, setImages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Fetch data when apiUrl changes
  useEffect(() => {
    if (!apiUrl) return;

    const fetchImages = async () => {
      try {
        const response = await fetch(apiUrl);
        if (!response.ok) throw new Error(`Failed to fetch: ${response.status}`);
        const data = await response.json();

        let bannerList = [];
        if (data && data.success && data.data) {
          if (Array.isArray(data.data)) {
            bannerList = data.data;
          } else {
            const keys = Object.keys(data.data);
            if (keys.length > 0) {
              const firstKey = keys[0];
              if (data.data[firstKey] && data.data[firstKey].banners) {
                bannerList = data.data[firstKey].banners;
              } else if (Array.isArray(data.data[firstKey])) {
                bannerList = data.data[firstKey];
              }
            }
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
        console.error("Error fetching banners:", err);
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchImages();
  }, [apiUrl]);

  // Loading & error states
  if (loading) {
    return (
      <div className="container-fluid m-0 p-0">
        <div className="row g-1 m-0 px-1 container-fluid d-flex">
          {[1, 2].map((item) => (
            <div className="col-md-6" style={{ maxHeight: "150px", maxWidth: '100%' }} key={item}>
              <div className="shimmer-bg skeleton-banner-hero w-100" style={{ height: "150px" }}></div>
            </div>
          ))}
        </div>
      </div>
    );
  }
  if (error) return <p className="text-center text-danger p-3">{error}</p>;

  return (
    <div className="container-fluid m-0 p-0">
      <div className="row g-1 m-0 px-1 container-fluid d-flex">
        {images.length > 0 ? (
          images.slice(sliceStart || 0, sliceEnd || images.length).map((image, index) => (
            <div className="col-md-6" style={{ maxWidth: '100%', maxHeight: "180px", overflow: 'hidden' }} key={index}>
              <img
                src={image.src}
                alt={image.alt || `banner-${index + 1}`}
                className="rounded carousel-img w-100"
                style={{ height: '100%', objectFit: 'cover' }}
              />
            </div>
          ))
        ) : (
          <div className="text-center text-muted p-4">No banners available</div>
        )}
      </div>
    </div>
  );
};

BannerSmall.propTypes = {
  apiUrl: PropTypes.string.isRequired,
  sliceStart: PropTypes.number,
  sliceEnd: PropTypes.number
};

export default BannerSmall;
