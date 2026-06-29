import React, { useEffect, useState } from "react";
import BannerGrid from "../../home/banners/BannerGrid";
import normalizeBanner from "../../home/utils/normalizeBanner";

const BannerSmall = ({ apiUrl, sliceStart, sliceEnd, ...props }) => {
  const [banners, setBanners] = useState([]);
  const [loading, setLoading] = useState(!!apiUrl);

  useEffect(() => {
    if (!apiUrl) return;
    const fetchBanners = async () => {
      try {
        const response = await fetch(apiUrl);
        if (!response.ok) throw new Error("HTTP error");
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

        const normalized = bannerList.map(b => normalizeBanner(b)).filter(Boolean);
        setBanners(normalized.slice(sliceStart || 0, sliceEnd || normalized.length));
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    };
    fetchBanners();
  }, [apiUrl, sliceStart, sliceEnd]);

  if (loading) {
    return (
      <section className="home-banner-section">
        <div className="home-banner-grid" data-columns={2}>
          <div className="shimmer-bg skeleton-banner-hero w-100" />
          <div className="shimmer-bg skeleton-banner-hero w-100" />
        </div>
      </section>
    );
  }

  return <BannerGrid banners={banners} columns={2} mobileColumns={1} {...props} />;
};

export default BannerSmall;
