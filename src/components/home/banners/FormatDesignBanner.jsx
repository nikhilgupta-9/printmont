import React from "react";
import useHomeBanners from "../hooks/useHomeBanners";
import BannerImage from "./BannerImage";
import normalizeBanner from "../utils/normalizeBanner";

/**
 * Renders Desktop Format Designs 1 through 6 from diagram structure.
 * @param {string} [apiUrl] API endpoint
 * @param {Array} [banners] Static list of banner objects
 * @param {string} [format='desktop_format_1'] Format type (desktop_format_1 to 6)
 * @param {string} [sectionKey] Section key payload
 */
export default function FormatDesignBanner({
  apiUrl,
  banners: propBanners,
  format = "desktop_format_1",
  sectionKey = ""
}) {
  const { banners: fetchedBanners, loading, error } = useHomeBanners(apiUrl, sectionKey);
  const rawBanners = propBanners || fetchedBanners || [];
  const banners = rawBanners.map(b => normalizeBanner(b)).filter(Boolean);

  if (loading && !propBanners) {
    return (
      <section className="home-banner-section my-2">
        <div className="shimmer-bg skeleton-banner-hero w-100" />
      </section>
    );
  }

  if (error && !propBanners) {
    return <div className="text-center p-3 text-danger">Error loading banner: {error}</div>;
  }

  if (banners.length === 0) {
    return null;
  }

  // Format 1: Single Square Offer Banner (1:1)
  if (format === "desktop_format_1") {
    return (
      <section className="home-banner-section my-2">
        <div className="banner-format-container banner-format-1" style={{ maxWidth: "600px", margin: "0 auto" }}>
          <BannerImage
            large={banners[0]?.large}
            small={banners[0]?.small}
            target={banners[0]?.target}
            alt={banners[0]?.alt}
            className="aspect-square"
          />
        </div>
      </section>
    );
  }

  // Format 2: Square + Tall Offer Banner (Side by Side)
  if (format === "desktop_format_2") {
    return (
      <section className="home-banner-section my-2">
        <div className="banner-format-grid banner-format-2">
          <div className="format-col">
            <BannerImage
              large={banners[0]?.large}
              small={banners[0]?.small}
              target={banners[0]?.target}
              alt={banners[0]?.alt}
              className="aspect-square"
            />
          </div>
          {banners[1] && (
            <div className="format-col">
              <BannerImage
                large={banners[1]?.large}
                small={banners[1]?.small}
                target={banners[1]?.target}
                alt={banners[1]?.alt}
                className="aspect-tall"
              />
            </div>
          )}
        </div>
      </section>
    );
  }

  // Format 3: 2 Square top + 1 Wide Offer Banner bottom
  if (format === "desktop_format_3") {
    return (
      <section className="home-banner-section my-2">
        <div className="banner-format-grid banner-format-3">
          <div className="format-row-top d-flex gap-2 mb-2">
            {banners[0] && (
              <div className="flex-fill">
                <BannerImage
                  large={banners[0]?.large}
                  small={banners[0]?.small}
                  target={banners[0]?.target}
                  alt={banners[0]?.alt}
                  className="aspect-square"
                />
              </div>
            )}
            {banners[1] && (
              <div className="flex-fill">
                <BannerImage
                  large={banners[1]?.large}
                  small={banners[1]?.small}
                  target={banners[1]?.target}
                  alt={banners[1]?.alt}
                  className="aspect-square"
                />
              </div>
            )}
          </div>
          {banners[2] && (
            <div className="format-row-bottom w-100">
              <BannerImage
                large={banners[2]?.large}
                small={banners[2]?.small}
                target={banners[2]?.target}
                alt={banners[2]?.alt}
                className="aspect-wide"
              />
            </div>
          )}
        </div>
      </section>
    );
  }

  // Format 4: 2 Columns x 3 Rows Square Grid (6 items)
  if (format === "desktop_format_4") {
    return (
      <section className="home-banner-section my-2">
        <div className="banner-grid-2col-3row">
          {banners.slice(0, 6).map((b, idx) => (
            <BannerImage
              key={idx}
              large={b.large}
              small={b.small}
              target={b.target}
              alt={b.alt}
              className="aspect-square"
            />
          ))}
        </div>
      </section>
    );
  }

  // Format 5: Single Small Strip Banner
  if (format === "desktop_format_5") {
    return (
      <section className="home-banner-section my-2">
        <div className="banner-format-container banner-format-5">
          <BannerImage
            large={banners[0]?.large}
            small={banners[0]?.small}
            target={banners[0]?.target}
            alt={banners[0]?.alt}
            className="aspect-strip"
          />
        </div>
      </section>
    );
  }

  // Format 6: 3 Columns x 2 Rows Square Grid (6 items)
  if (format === "desktop_format_6") {
    return (
      <section className="home-banner-section my-2">
        <div className="banner-grid-3col-2row">
          {banners.slice(0, 6).map((b, idx) => (
            <BannerImage
              key={idx}
              large={b.large}
              small={b.small}
              target={b.target}
              alt={b.alt}
              className="aspect-square"
            />
          ))}
        </div>
      </section>
    );
  }

  // Default fallback to single banner
  return (
    <section className="home-banner-section my-2">
      <BannerImage
        large={banners[0]?.large}
        small={banners[0]?.small}
        target={banners[0]?.target}
        alt={banners[0]?.alt}
      />
    </section>
  );
}
