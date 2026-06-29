import React, { useState, useEffect } from "react";
import BannerImage from "./BannerImage";
import normalizeBanner from "../utils/normalizeBanner";

/**
 * Side-by-side banners on desktop, auto-scrolling carousel on mobile.
 * Replaces BannerTwo and BannerSmall.
 * @param {string} [apiUrl] API endpoint to fetch banners from.
 * @param {Array} [banners] Static banner list.
 */
export default function ResponsiveBannerSet({ apiUrl, banners: propBanners }) {
  const [desktopBanners, setDesktopBanners] = useState([]);
  const [mobileBanners, setMobileBanners] = useState([]);
  const [loading, setLoading] = useState(!!apiUrl);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!apiUrl) {
      if (propBanners) {
        const normalized = propBanners.map(b => normalizeBanner(b)).filter(Boolean);
        setDesktopBanners(normalized);
        setMobileBanners(normalized);
      }
      setLoading(false);
      return;
    }

    let isMounted = true;
    const fetchBanners = async () => {
      try {
        setLoading(true);
        const response = await fetch(apiUrl);
        if (!response.ok) {
          throw new Error(`HTTP Error: ${response.status}`);
        }
        const data = await response.json();

        if (!isMounted) return;

        let dList = [];
        let mList = [];

        // Check if data is array and has "type" field (BannerTwo format)
        if (Array.isArray(data) && data.length > 0 && (data[0].type || data[0].images)) {
          const dItem = data.find(item => item.type === "desktop");
          const mItem = data.find(item => item.type === "mobile");
          if (dItem || mItem) {
            dList = (dItem?.images || []).map(b => normalizeBanner(b)).filter(Boolean);
            mList = (mItem?.images || []).map(b => normalizeBanner(b)).filter(Boolean);
          } else {
            const normalized = data.map(b => normalizeBanner(b)).filter(Boolean);
            dList = normalized;
            mList = normalized;
          }
        } else {
          // Standard structure
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

          const normalized = bannerList.map(b => normalizeBanner(b)).filter(Boolean);
          dList = normalized;
          mList = normalized;
        }

        setDesktopBanners(dList);
        setMobileBanners(mList);
        setError(null);
      } catch (err) {
        if (isMounted) {
          setError(err.message);
        }
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    };

    fetchBanners();

    return () => {
      isMounted = false;
    };
  }, [apiUrl, propBanners]);

  if (loading && !propBanners) {
    return (
      <section className="home-banner-section">
        {/* Desktop Skeleton */}
        <div className="container-fluid bg-none d-none d-md-flex align-items-center justify-content-center gap-1 mx-0 px-0 py-0 m-0">
          {[1, 2, 3].map((item) => (
            <div key={item} className="col p-0 rounded">
              <div className="shimmer-bg skeleton-banner-hero w-100" />
            </div>
          ))}
        </div>
        {/* Mobile Skeleton */}
        <div className="container-fluid px-0 d-md-none">
          <div className="shimmer-bg skeleton-slider-mobile w-100" />
        </div>
      </section>
    );
  }

  if (error && !propBanners) {
    return <div className="text-center p-3 text-danger">Error: {error}</div>;
  }

  const finalDesktopBanners = propBanners ? desktopBanners : desktopBanners;
  const finalMobileBanners = propBanners ? mobileBanners : mobileBanners;

  if (finalDesktopBanners.length === 0 && finalMobileBanners.length === 0) {
    return null;
  }

  return (
    <section className="home-banner-section">
      {/* Desktop Grid Layout */}
      <div className="container-fluid bg-none d-none d-md-flex align-items-center justify-content-center gap-1 mx-0 px-0 py-0 m-0">
        {finalDesktopBanners.map((banner, index) => (
          <div key={index} className="col p-0 rounded" style={{ flex: "1 1 0px", minWidth: 0 }}>
            <BannerImage
              large={banner.large}
              small={banner.large}
              target={banner.target}
              alt={banner.alt || `Desktop Banner ${index + 1}`}
            />
          </div>
        ))}
      </div>

      {/* Mobile Slider Layout */}
      <div className="container-fluid px-0 d-md-none">
        <div
          id="responsiveBannerSetCarousel"
          className="carousel slide"
          data-bs-ride="carousel"
          data-bs-interval="2000"
        >
          <div className="carousel-inner">
            {finalMobileBanners.map((banner, index) => (
              <div
                key={index}
                className={`carousel-item ${index === 0 ? "active" : ""}`}
              >
                <BannerImage
                  large={banner.small}
                  small={banner.small}
                  target={banner.target}
                  alt={banner.alt || `Mobile Banner ${index + 1}`}
                />
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
