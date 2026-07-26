import React from "react";
import { Link } from "react-router-dom";

/**
 * Renders a standard, responsive banner image using <picture> for desktop/mobile variants.
 * Handles internal react-router-dom links or external anchors automatically.
 */
export default function BannerImage({ large, small, target, alt, className = "", borderRadius = "4px", aspectRatio }) {
  const isSquare = className.includes("aspect-square") || aspectRatio === "1/1" || aspectRatio === "1 / 1";
  const pictureStyle = {
    display: "block",
    width: "100%",
    borderRadius: borderRadius,
    overflow: "hidden",
    ...(aspectRatio ? { aspectRatio } : {})
  };
  const imgStyle = {
    width: "100%",
    height: (className.includes("h-100") || isSquare || aspectRatio) ? "100%" : "auto",
    objectFit: "cover",
    borderRadius: borderRadius
  };

  const imageElement = (
    <picture className="home-banner-picture" style={pictureStyle}>
      {large && <source media="(min-width: 768px)" srcSet={large} />}
      <img
        src={small || large}
        alt={alt || "Banner"}
        className={`home-banner-img ${className}`}
        style={imgStyle}
        loading="lazy"
      />
    </picture>
  );

  if (target && target !== "#") {
    const isExternal = target.startsWith("http") || target.startsWith("//") || target.includes(".php");
    if (isExternal) {
      return (
        <a href={target} target="_blank" rel="noopener noreferrer" className="d-block w-100 home-banner-link">
          {imageElement}
        </a>
      );
    }
    return (
      <Link to={target} className="d-block w-100 home-banner-link">
        {imageElement}
      </Link>
    );
  }

  return <div className="w-100">{imageElement}</div>;
}
