import React from "react";
import BannerImage from "./BannerImage";

/**
 * A single static/local responsive banner.
 * Replaces Bannerthree and static Banner configurations.
 */
export default function StaticResponsiveBanner({
  large = "./section-img/desktop-banner.jpg",
  small = "./section-img/mobile-banner.jpg",
  target = "#",
  alt = "Responsive Banner"
}) {
  return (
    <section className="home-banner-section">
      <BannerImage
        large={large}
        small={small}
        target={target}
        alt={alt}
      />
    </section>
  );
}
