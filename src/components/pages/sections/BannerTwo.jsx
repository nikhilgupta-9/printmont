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

      // ✅ Handle array format like:
      // [ { type: "desktop", images: [...] }, { type: "mobile", images: [...] } ]
      const desktopData = data.find(item => item.type === "desktop")?.images || [];
      const mobileData = data.find(item => item.type === "mobile")?.images || [];

      setDesktopImages(desktopData);
      setMobileImages(mobileData);
    } catch (err) {
      console.error("Error fetching banner images:", err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  fetchBannerData();
}, [apiUrl]);


  if (loading) return <p className="text-center p-3">Loading banners...</p>;
  if (error) return <p className="text-center text-danger p-3">{error}</p>;

  return (
    <>
      {/* Desktop View (≥ md): Side-by-side layout */}
      <div className="container-fluid bg-none d-none d-md-flex align-items-center justify-content-center gap-1 mx-0 py-0 m-0">
        {desktopImages.length > 0 ? (
          desktopImages.map((img, index) => (
            <div key={index} className="col-4 p-0 rounded">
              <img
                className="rounded"
                src={img.src}
                width="100%"
                alt={img.alt || `banner-${index}`}
                style={{ maxHeight: "290px", height: "290px", objectFit: "contain" }}
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
                    className="d-block w-100"
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
