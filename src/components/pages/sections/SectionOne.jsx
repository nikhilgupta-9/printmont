import React, { useEffect, useState } from 'react';

const SectionOne = ({ apiUrl }) => {
  const [banners, setBanners] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchBanners = async () => {
      try {
        const response = await fetch(apiUrl);
        if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);

        const text = await response.text(); // for debugging

        const data = JSON.parse(text);

        // Case 1: data is [{ "url": "..." }]
        // Case 2: data is ["img1.jpg", "img2.jpg"]
        const imageArray = data.map(item => item.url || item);
        setBanners(imageArray);
      } catch (err) {
        console.error("❌ Error fetching banners:", err);
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchBanners();
  }, [apiUrl]);

  if (loading) return <div className="text-center p-5">Loading banners...</div>;
  if (error) return <div className="text-center text-danger p-5">Error: {error}</div>;

  return (
    <div className="container-fluid m-0 p-0">
      <div className="row g-1 m-0 px-1 container-fluid d-flex">
        {banners.length > 0 ? (
          banners.map((src, index) => (
            <div className="col-6 col-lg-3 rounded-md" key={index}>
              <img
                src={src}
                alt={`Banner ${index + 1}`}
                className="img-fluid rounded shadow-sm w-100"
                style={{
                  height: 'auto',
                  objectFit: 'contain',
                  maxHeight: '350px',
                }}
              />
            </div>
          ))
        ) : (
          <div className="text-center text-muted p-4 w-100">
            No banners available
          </div>
        )}
      </div>
    </div>
  );
};

export default SectionOne;