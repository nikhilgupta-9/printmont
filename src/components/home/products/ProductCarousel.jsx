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
  backgroundImageUrl,
  cardsToShowMobile = 2.25,
  viewAllUrl
}) {
  const scrollRef = useRef(null);
  const { products, loading, error } = useHomeProducts(apiUrl, initialProducts);
  const { canScrollLeft, canScrollRight, scroll } = useHorizontalScroll(scrollRef, [products]);
  const breakpoint = useBreakpoint();

  const computedViewAllUrl = React.useMemo(() => {
    if (viewAllUrl) return viewAllUrl;
    const lowerTitle = (title || "").toLowerCase().trim();
    if (lowerTitle.includes("bestseller") || lowerTitle.includes("best seller")) {
      return "/allproducts?filter=bestseller";
    }
    if (lowerTitle.includes("top rated") || lowerTitle.includes("top-rated")) {
      return "/allproducts?filter=top_rated";
    }
    if (lowerTitle.includes("top deal") || lowerTitle.includes("deals")) {
      return "/allproducts?filter=top_deal";
    }
    if (lowerTitle.includes("discount") || lowerTitle.includes("special offer") || lowerTitle.includes("flash sale")) {
      return "/allproducts?filter=discount";
    }
    if (apiUrl) {
      if (apiUrl.includes("action=bestseller")) return "/allproducts?filter=bestseller";
      if (apiUrl.includes("action=top_rated")) return "/allproducts?filter=top_rated";
      if (apiUrl.includes("action=top_deal")) return "/allproducts?filter=top_deal";
      if (apiUrl.includes("action=discount") || apiUrl.includes("action=special_offer")) return "/allproducts?filter=discount";
      if (apiUrl.includes("action=women_outfit") || apiUrl.includes("action=women_clothing") || apiUrl.includes("action=women")) return "/allproducts?category=Women%27s+Fashion";
      if (apiUrl.includes("action=men_clothing") || apiUrl.includes("action=men")) return "/allproducts?category=Men%27s+Fashion";
    }
    return `/allproducts?q=${encodeURIComponent(title)}`;
  }, [viewAllUrl, title, apiUrl]);

  const getResponsiveCardStyle = (size) => {
    let cardsToShow;
    switch (size) {
      case "xl": cardsToShow = 5.25; break; // 5 cards fully shown, 6th card 25% visible
      case "lg": cardsToShow = 4.25; break; // 4 cards fully shown, 5th card 25% visible
      case "md": cardsToShow = 3.25; break; // 3 cards fully shown, 4th card 25% visible
      default: cardsToShow = cardsToShowMobile; break; // configurable on mobile (e.g. 1.65 or 2.25)
    }
    return {
      width: `calc((100% - (var(--home-card-gap, 8px) * (${cardsToShow} - 1))) / ${cardsToShow})`,
      flexShrink: 0
    };
  };

  if (loading && products.length === 0) {
    return (
      <div className="horizontal-scroll-wrapper bg-white m-0 p-0">
        <div className="pm-home-section-header">
          <h3 className="pm-home-section-title">{title}</h3>
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
      className={`horizontal-scroll-wrapper position-relative m-0 p-0 product-carousel-wrapper ${
        backgroundImageUrl ? "custom-bg-image" : ""
      }`}
      style={{
        backgroundImage: backgroundImageUrl ? `url(${backgroundImageUrl})` : "none",
        backgroundColor: backgroundImageUrl ? "transparent" : "white",
        backgroundSize: "cover",
        backgroundRepeat: "no-repeat",
        border: "none",
        padding: 0
      }}
    >
      <div className="pm-home-section-header">
        <h3 className="pm-home-section-title">{title}</h3>

        {/* View All Button */}
        <Link
          to={computedViewAllUrl}
          className="pm-home-view-all-btn"
        >
          View All <MdKeyboardArrowRight size={16} />
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
        {products.map((product, index) => {
          // Override badge to Priemium for the first two products to match Figma mockup
          const mappedProduct = (title.toLowerCase().includes("arivel") || title.toLowerCase().includes("arrival") || title.toLowerCase().includes("bestseller")) && (index === 0 || index === 1)
            ? { ...product, badge: "Priemium" }
            : product;
            
          return (
            <ProductCard
              key={product.id || index}
              index={index}
              product={mappedProduct}
              variant="carousel"
              badgeText={badgeText}
              style={getResponsiveCardStyle(breakpoint)}
            />
          );
        })}
        <div style={{ width: "10px", flexShrink: 0 }}></div>
      </div>
    </div>
  );
}
