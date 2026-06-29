import React from "react";
import useHomeBanners from "../hooks/useHomeBanners";
import BannerImage from "./BannerImage";

/**
 * Renders a set of banners in a CSS grid with layout gaps.
 * @param {string} [apiUrl] The API endpoint to fetch banners from.
 * @param {Array} [banners] Static banner list if you want to bypass API fetching.
 * @param {number} [columns=2] Number of columns on desktop.
 * @param {number} [mobileColumns=1] Number of columns on mobile.
 */
export default function BannerGrid({ apiUrl, sectionKey, banners: propBanners, columns = 2, mobileColumns = 1 }) {
  const { banners: fetchedBanners, loading, error } = useHomeBanners(apiUrl, sectionKey);
  const banners = propBanners || fetchedBanners || [];

  if (loading && !propBanners) {
    const skeletonCount = propBanners ? propBanners.length : columns;
    return (
      <section className="home-banner-section">
        <div className="home-banner-grid" data-columns={columns} data-mobile-columns={mobileColumns}>
          {Array.from({ length: skeletonCount }).map((_, i) => (
            <div key={i} className="shimmer-bg skeleton-banner-hero w-100" />
          ))}
        </div>
      </section>
    );
  }

  if (error && !propBanners) {
    return <div className="text-center p-3 text-danger">Error loading banners: {error}</div>;
  }

  if (banners.length === 0) {
    return null;
  }

  return (
    <section className="home-banner-section">
      <div className="home-banner-grid" data-columns={columns} data-mobile-columns={mobileColumns}>
        {banners.map((banner, index) => (
          <BannerImage
            key={index}
            large={banner.large}
            small={banner.small}
            target={banner.target}
            alt={banner.alt}
          />
        ))}
      </div>
    </section>
  );
}
