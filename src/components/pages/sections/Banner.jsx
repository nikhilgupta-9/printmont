// Banner.jsx
import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';

const Banner = ({ apiUrl }) => {
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
        setImages(data);
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
  if (loading) return <p className="text-center p-3">Loading banners...</p>;
  if (error) return <p className="text-center text-danger p-3">{error}</p>;

  return (
    <div className="container-fluid m-0 p-0">
      <div className="row g-1 m-0 px-1 container-fluid d-flex">
        {images.length > 0 ? (
          images.map((image, index) => (
            <div className="col-md-6" style={{ maxHeight: "290px", maxWidth: '100%' }} key={index}>
              <img
                src={image.src}
                alt={image.alt || `banner-${index + 1}`}
                className="rounded"
                width="100%"
                height="auto"
                style={{ maxHeight: "290px", objectFit: "contain" }}
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

Banner.propTypes = {
  apiUrl: PropTypes.string.isRequired
};

export default Banner;
