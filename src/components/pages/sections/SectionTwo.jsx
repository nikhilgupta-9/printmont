import React, { useEffect, useState } from "react";

const SectionTwo = ({ apiurl }) => {
  const [images, setImages] = useState([]); // fetched image data
  const [loading, setLoading] = useState(true); // loading state
  const [error, setError] = useState(null); // error message

  useEffect(() => {
    if (!apiurl) return; // safeguard if no URL is passed

    const fetchImages = async () => {
      try {
        const res = await fetch(apiurl);
        if (!res.ok) throw new Error(`Failed to fetch (${res.status})`);
        const data = await res.json();

        let bannerList = [];
        if (data && data.success && data.data) {
          if (data.data.home_mid_section_3 && data.data.home_mid_section_3.banners) {
            bannerList = data.data.home_mid_section_3.banners;
          } else if (Array.isArray(data.data)) {
            bannerList = data.data;
          }
        } else if (Array.isArray(data)) {
          bannerList = data;
        }

        const formattedImages = bannerList.map((item) => {
          let large = '';
          let small = '';
          if (item.large && item.small) {
            large = item.large;
            small = item.small;
          } else if (item.images) {
            large = item.images.desktop || '';
            small = item.images.mobile || '';
          } else {
            large = item.image_url_desktop || '';
            small = item.image_url_mobile || '';
          }
          return {
            large: large,
            small: small,
          };
        });

        setImages(formattedImages);
      } catch (err) {
        console.error("Error fetching images:", err);
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchImages();
  }, [apiurl]); // ✅ re-run when URL changes

  if (loading) {
    return (
      <div className="container-fluid p-0">
        <div className="shimmer-bg skeleton-banner-hero w-100" style={{ maxHeight: "290px" }}></div>
      </div>
    );
  }
  if (error) return <p className="text-center text-danger p-3">{error}</p>;

  return (
    <div
      className="container-fluid d-flex flex-column align-items-center justify-content-center gap-3 bg-gray rounded-md p-0"
      style={{ maxHeight: "350px" }}
    >
      {images.length > 0 ? (
        images.map((imgSrc, index) => (
          <picture key={index} className="col-12">
            {/* Large screen image */}
            <source media="(min-width: 768px)" srcSet={imgSrc.large} />
            {/* Small screen fallback */}
            <img
              src={imgSrc.small}
              alt={`Section Banner ${index + 1}`}
              width="100%"
              style={{ maxHeight: "290px", objectFit: "contain" }}
              className="rounded section-two-img"
            />
          </picture>
        ))
      ) : (
        <div className="text-center text-muted p-4">
          No section images available
        </div>
      )}
    </div>
  );
};

export default SectionTwo;
