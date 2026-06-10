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

        const data = await response.json();

        let bannerList = [];
        if (data && data.success && data.data) {
          if (data.data.home_above_fold && data.data.home_above_fold.banners) {
            bannerList = data.data.home_above_fold.banners;
          } else if (Array.isArray(data.data)) {
            bannerList = data.data;
          }
        } else if (Array.isArray(data)) {
          bannerList = data;
        }

        const imageArray = bannerList.map(item => {
          if (typeof item === 'string') return item;
          if (item.url) return item.url;
          if (item.images) {
            return item.images.desktop || item.images.mobile || '';
          }
          return item.image_url_desktop || item.image_url_mobile || '';
        });

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

  if (loading) {
    return (
      <div className="container-fluid m-0 p-0">
        <div className="row g-1 m-0 px-1 container-fluid d-flex">
          {[1, 2, 3, 4].map((item) => (
            <div className="col-6 col-lg-3 rounded-md" key={item}>
              <div className="shimmer-bg skeleton-grid-3 w-100"></div>
            </div>
          ))}
        </div>
      </div>
    );
  }
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