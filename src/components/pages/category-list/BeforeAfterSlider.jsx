import React, { useState, useRef } from "react";
import "./BeforeAfterSlider.css";

const BeforeAfterSlider = ({ beforeSrc, afterSrc, beforeLabel, afterLabel, height }) => {
  const [sliderPosition, setSliderPosition] = useState(50);
  const containerRef = useRef(null);

  const handleDrag = (e) => {
    if (!containerRef.current) return;
    const rect = containerRef.current.getBoundingClientRect();
    let x;
    if (e.touches && e.touches.length > 0) {
      x = e.touches[0].clientX;
    } else if (e.clientX !== undefined) {
      x = e.clientX;
    } else {
      return;
    }
    const position = ((x - rect.left) / rect.width) * 100;
    if (position >= 0 && position <= 100) {
      setSliderPosition(position);
    }
  };

  return (
    <div
      className="ba-slider-container"
      style={{ height: height || "400px" }}
      ref={containerRef}
      onMouseMove={handleDrag}
      onTouchMove={handleDrag}
    >
      <div className="ba-image-wrapper">
        <img src={beforeSrc} alt="Before" className="ba-image ba-image-before" />
        <div className="ba-label ba-label-before">{beforeLabel || "Before"}</div>
      </div>
      <div
        className="ba-image-wrapper ba-image-wrapper-after"
        style={{ clipPath: `inset(0 0 0 ${sliderPosition}%)` }}
      >
        <img src={afterSrc} alt="After" className="ba-image ba-image-after" />
        <div className="ba-label ba-label-after" style={{ left: `calc(${sliderPosition}% + 20px)` }}>{afterLabel || "After"}</div>
      </div>
      <div
        className="ba-slider-handle"
        style={{ left: `${sliderPosition}%` }}
      >
        <div className="ba-slider-line"></div>
        <div className="ba-slider-button shadow-sm border border-light">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M15 18l-6-6 6-6" />
          </svg>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M9 18l6-6-6-6" />
          </svg>
        </div>
      </div>
    </div>
  );
};

export default BeforeAfterSlider;
