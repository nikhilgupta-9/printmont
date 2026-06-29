import React, { useRef } from "react";
import { Link } from "react-router-dom";
import { IoIosArrowBack, IoIosArrowForward } from "react-icons/io";
import { MdKeyboardArrowRight } from "react-icons/md";
import useHomeProducts from "../hooks/useHomeProducts";
import useHorizontalScroll from "../hooks/useHorizontalScroll";
import useBreakpoint from "../hooks/useBreakpoint";
import ProductCard from "./ProductCard";

/**
 * Replaces SecondCarousel. Displays a horizontally scrollable list of products.
 * @param {string} [apiUrl] API endpoint to fetch products from.
 * @param {Array} [products] Initial or static products.
 * @param {string} [title="Products"] Carousel section title.
 * @param {string} [badgeText=""] Custom badge text for product cards (e.g. 'Customizable')
 */
export default function ProductCarousel({
  apiUrl,
  products: initialProducts = [],
  title = "Products",
  badgeText = "",
  backgroundImageUrl
}) {
  const scrollRef = useRef(null);
  const { products, loading, error } = useHomeProducts(apiUrl, initialProducts);
  const { canScrollLeft, canScrollRight, scroll } = useHorizontalScroll(scrollRef, [products]);
  const breakpoint = useBreakpoint();

  const getResponsiveCardStyle = (size) => {
    let cardsToShow;
    switch (size) {
      case "xl": cardsToShow = 5.25; break; // 5 cards fully shown, 6th card 25% visible
      case "lg": cardsToShow = 4.25; break; // 4 cards fully shown, 5th card 25% visible
      case "md": cardsToShow = 3.25; break; // 3 cards fully shown, 4th card 25% visible
      default: cardsToShow = 2.25; break; // 2 cards fully shown, 3rd card 25% visible
    }
    return {
      width: `calc((100% - (var(--home-card-gap, 8px) * (${cardsToShow} - 1))) / ${cardsToShow})`,
      flexShrink: 0
    };
  };

  if (loading && products.length === 0) {
    return (
      <div className="horizontal-scroll-wrapper bg-white m-0 border home-layout-gap">
        <div className="d-flex justify-content-between align-items-center">
          <p className="fw-semibold fs-5 fs-lg-4 my-2 ms-0">{title}</p>
        </div>
        <div 
          className="d-flex flex-nowrap overflow-x-hidden py-1"
          style={{ gap: "var(--home-card-gap, 8px)" }}
        >
          {Array.from({ length: 5 }).map((_, item) => (
            <div
              key={item}
              className="scroll-card border d-flex flex-column p-1 h-100 m-0"
              style={getResponsiveCardStyle(breakpoint)}
            >
              <div className="shimmer-bg skeleton-img w-100" />
              <div className="shimmer-bg skeleton-title w-75 mx-auto" />
              <div className="shimmer-bg skeleton-price w-50 mx-auto" />
            </div>
          ))}
        </div>
      </div>
    );
  }

  if (error && products.length === 0) {
    return <div className="text-center text-danger p-5">Error: {error}</div>;
  }

  if (products.length === 0) return null;

  return (
    <div
      className={`horizontal-scroll-wrapper position-relative m-0 border home-layout-gap ${
        backgroundImageUrl ? "custom-bg-image" : ""
      }`}
      style={{
        backgroundImage: backgroundImageUrl ? `url(${backgroundImageUrl})` : "none",
        backgroundColor: backgroundImageUrl ? "transparent" : "white",
        backgroundSize: "cover",
        backgroundRepeat: "no-repeat"
      }}
    >
      <div className="d-flex justify-content-between align-items-center">
        <p className="fw-semibold fs-5 fs-lg-4 my-2 ms-0">{title}</p>

        {/* View All Button */}
        <Link
          to="/cart"
          className="d-none d-lg-flex align-items-center justify-content-center rounded bg-theme px-2 py-1 text-white text-decoration-none me-1 my-2"
        >
          View All <MdKeyboardArrowRight size={19} />
        </Link>
      </div>

      {/* Scroll Arrows */}
      {canScrollLeft && (
        <button
          className="scroll-arrow left"
          onClick={() => scroll("left", 350)}
          aria-label="Scroll left"
        >
          <span className="left-arr-carousel text-black bg-white ms-3">
            <IoIosArrowBack />
          </span>
        </button>
      )}
      {canScrollRight && (
        <button
          className="scroll-arrow right d-sm-none d-lg-flex align-items-center justify-content-end"
          onClick={() => scroll("right", 350)}
          aria-label="Scroll right"
        >
          <span className="right-arr-carousel text-black bg-white">
            <IoIosArrowForward />
          </span>
        </button>
      )}

      {/* Scrollable Container */}
      <div
        className="scroll-container d-flex flex-nowrap align-items-stretch overflow-x-scroll m-0 py-1"
        ref={scrollRef}
        style={{ 
          scrollbarWidth: "none", 
          msOverflowStyle: "none",
          gap: "var(--home-card-gap, 8px)"
        }}
      >
        {products.map((product, index) => (
          <ProductCard
            key={index}
            product={product}
            variant="carousel"
            badgeText={badgeText}
            style={getResponsiveCardStyle(breakpoint)}
          />
        ))}
        <div style={{ width: "10px", flexShrink: 0 }}></div>
      </div>
    </div>
  );
}
