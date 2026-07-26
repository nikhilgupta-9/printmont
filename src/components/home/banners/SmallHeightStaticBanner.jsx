import React from "react";
import useHomeBanners from "../hooks/useHomeBanners";
import BannerImage from "./BannerImage";
import normalizeBanner from "../utils/normalizeBanner";

/**
 * SmallHeightStaticBanner — Dedicated component for a single slim/small height offer banner strip.
 * Features a noticeably compact height (aspect ratio ~16:3 or 21:5) in comparison to standard banners.
 *
 * @param {string} [apiUrl] The API endpoint to fetch banners from.
 * @param {Array} [banners] Static list of banner objects.
 * @param {string} [basePath] Base path for relative image URLs.
 * @param {string} [sectionKey='small_height_banner'] Section key for API lookup.
 * @param {string} [maxHeight='140px'] Max height constraint.
 */
export default function SmallHeightStaticBanner({
  apiUrl,
  banners: propBanners,
  basePath = "",
  sectionKey = "small_height_banner",
  maxHeight = "140px"
}) {
  const { banners: fetchedBanners, loading, error } = useHomeBanners(apiUrl, sectionKey, basePath);
  const rawBanners = (fetchedBanners && fetchedBanners.length > 0) ? fetchedBanners : (propBanners || []);
  const banners = rawBanners.map(b => normalizeBanner(b, basePath)).filter(Boolean);

  if (loading && !propBanners) {
    return (
      <div className="w-100 p-2">
        <div 
          className="shimmer-bg rounded border" 
          style={{ height: maxHeight, width: "100%" }} 
        />
      </div>
    );
  }

  if (error && !propBanners) {
    return <div className="text-center p-3 text-danger">Error loading small banner: {error}</div>;
  }

  if (banners.length === 0) {
    return null;
  }

  const banner = banners[0];

  return (
    <div className="w-100 home-banner-section p-1 p-md-2">
      <div 
        className="small-height-banner-container rounded border overflow-hidden shadow-sm bg-white"
        style={{ width: "100%", maxHeight: maxHeight }}
      >
        <div className="w-100 position-relative" style={{ height: maxHeight, overflow: "hidden" }}>
          <BannerImage
            large={banner.large}
            small={banner.small}
            target={banner.target}
            alt={banner.alt || "Small Offer Banner Strip"}
            className="w-100 h-100 object-fit-cover"
          />
        </div>
      </div>
    </div>
  );
}
