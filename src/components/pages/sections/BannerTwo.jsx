import React, { useEffect, useState } from "react";

const BannerTwo = ({ apiUrl }) => {
  const [desktopImages, setDesktopImages] = useState([]);
  const [mobileImages, setMobileImages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
  if (!apiUrl) return;

  const fetchBannerData = async () => {
    try {
      const response = await fetch(apiUrl);
      if (!response.ok) throw new Error(`Failed to fetch: ${response.status}`);
      const data = await response.json();

      let desktopList = [];
      let mobileList = [];
      if (Array.isArray(data)) {
        const desktopData = data.find(item => item.type === "desktop")?.images || [];
        const mobileData = data.find(item => item.type === "mobile")?.images || [];
        desktopList = desktopData.map(d => ({ src: d.src || d, alt: d.alt || '' }));
        mobileList = mobileData.map(m => ({ src: m.src || m, alt: m.alt || '' }));
      } else {
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
        }
        desktopList = bannerList.map(item => ({
          src: item.images?.desktop || item.image_url_desktop || '',
          alt: item.title || ''
        }));
        mobileList = bannerList.map(item => ({
          src: item.images?.mobile || item.image_url_mobile || '',
          alt: item.title || ''
        }));
      }

      setDesktopImages(desktopList);
      setMobileImages(mobileList);
    } catch (err) {
      console.error("Error fetching banner images:", err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  fetchBannerData();
}, [apiUrl]);


  if (loading) {
    return (
      <>
        {/* Desktop View */}
        <div className="container-fluid bg-none d-none d-md-flex align-items-center justify-content-center gap-1 mx-0 py-0 m-0">
          {[1, 2, 3].map((item) => (
            <div key={item} className="col-4 p-0 rounded">
              <div className="shimmer-bg skeleton-banner-hero w-100" style={{ height: "290px" }}></div>
            </div>
          ))}
        </div>
        {/* Mobile View */}
        <div className="container-fluid px-0 d-md-none">
          <div className="shimmer-bg skeleton-slider-mobile w-100" style={{ height: "180px" }}></div>
        </div>
      </>
    );
  }
  if (error) return <p className="text-center text-danger p-3">{error}</p>;

  return (
    <>
      {/* Desktop View (≥ md): Side-by-side layout */}
      <div className="container-fluid bg-none d-none d-md-flex align-items-center justify-content-center gap-1 mx-0 py-0 m-0">
        {desktopImages.length > 0 ? (
          desktopImages.map((img, index) => (
            <div key={index} className="col-4 p-0 rounded">
              <img
                className="rounded carousel-img w-100"
                src={img.src}
                alt={img.alt || `banner-${index}`}
              />
            </div>
          ))
        ) : (
          <div className="text-center text-muted p-4">No desktop banners available</div>
        )}
      </div>

      {/* Mobile View (< md): Auto-scroll carousel */}
      <div className="container-fluid px-0 d-md-none">
        <div
          id="bannerCarousel"
          className="carousel slide"
          data-bs-ride="carousel"
          data-bs-interval="2000"
        >
          <div className="carousel-inner">
            {mobileImages.length > 0 ? (
              mobileImages.map((img, index) => (
                <div
                  key={index}
                  className={`carousel-item ${index === 0 ? "active" : ""}`}
                >
                  <img
                    src={img.src}
                    className="d-block w-100 carousel-img"
                    alt={img.alt || `mobile-banner-${index}`}
                  />
                </div>
              ))
            ) : (
              <div className="text-center text-muted p-4">No mobile banners available</div>
            )}
          </div>
        </div>
      </div>
    </>
  );
};

export default BannerTwo;
