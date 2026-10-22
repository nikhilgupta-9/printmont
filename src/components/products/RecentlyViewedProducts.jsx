import React, { useState, useEffect, useRef } from "react";
import { Link } from "react-router-dom";
import { FaChevronRight, FaChevronLeft, FaStar } from "react-icons/fa";
import { resolveImageUrl, API_ENDPOINTS, BASE_URL } from "../../config/apiEndpoints";
import { getProductUrl } from "../../utils/seo";
import "./RecentlyViewedProducts.css";

/**
 * Standalone Product Carousel & Recently Viewed Component
 * Used for all product carousels on the Mobile Product Details Page (and site-wide):
 * - Similar Products
 * - Discount on Similar Products
 * - You might be interested
 * - Your Recently viewed
 * 
 * Features unified card theme matching mockup:
 * - Rounded top card image with 'Bestseller' badge (top-left)
 * - Solid dark blue 'CUSTOMIZABLE' bottom overlay strip on image
 * - Truncated single-line title
 * - Bold Selling price, strikethrough MRP, green discount percentage
 * - Green rating pill (e.g. 5.5 ★) + Reviews count text
 * - Tight, cohesive margins with no excessive gap between sections
 */
export default function RecentlyViewedProducts({
  currentProductId = null,
  title = "Recently Viewed Products",
  limit = 10,
  apiUrl = null,
  isRecentlyViewed = false,
  showViewAll = true,
  viewAllUrl = "/allproducts"
}) {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const scrollRef = useRef(null);

  useEffect(() => {
    let isMounted = true;

    const loadProducts = async () => {
      // 1. Pure Recently Viewed mode (pulls from localStorage)
      if (isRecentlyViewed || (!apiUrl && title.toLowerCase().includes("recent"))) {
        let localItems = [];
        try {
          const stored = JSON.parse(localStorage.getItem("pm_recently_viewed") || "[]");
          if (Array.isArray(stored)) {
            localItems = stored.filter(
              (p) => !currentProductId || String(p.id) !== String(currentProductId)
            );
          }
        } catch (err) {
          console.error("Error reading pm_recently_viewed:", err);
        }

        if (localItems.length >= 4) {
          if (isMounted) {
            setProducts(localItems.slice(0, limit));
            setLoading(false);
          }
          return;
        }

        // Fallback fetch if not enough stored
        try {
          const fallbackUrl = apiUrl || API_ENDPOINTS.TOP_RATED || `${BASE_URL}/products/products.php?action=top_rated&limit=8`;
          const res = await fetch(fallbackUrl);
          const data = await res.json();
          let fetchedList = [];
          if (data && data.success && Array.isArray(data.data)) {
            fetchedList = data.data;
          } else if (Array.isArray(data)) {
            fetchedList = data;
          }
          const localIds = new Set(localItems.map((p) => String(p.id)));
          const additional = fetchedList.filter(
            (p) => (!currentProductId || String(p.id) !== String(currentProductId)) && !localIds.has(String(p.id))
          );
          if (isMounted) {
            setProducts([...localItems, ...additional].slice(0, limit));
            setLoading(false);
          }
        } catch (e) {
          if (isMounted) {
            setProducts(localItems);
            setLoading(false);
          }
        }
        return;
      }

      // 2. Dynamic API Carousel mode (Similar Products, Discount, You Might Like, etc.)
      try {
        const targetUrl = apiUrl || API_ENDPOINTS.TOP_RATED;
        const res = await fetch(targetUrl);
        const data = await res.json();
        let fetchedList = [];
        if (data && data.success && Array.isArray(data.data)) {
          fetchedList = data.data;
        } else if (Array.isArray(data)) {
          fetchedList = data;
        }
        const filtered = fetchedList.filter(
          (p) => !currentProductId || String(p.id) !== String(currentProductId)
        );
        if (isMounted) {
          setProducts(filtered.slice(0, limit));
          setLoading(false);
        }
      } catch (err) {
        console.error("Error fetching carousel products:", err);
        if (isMounted) {
          setLoading(false);
        }
      }
    };

    loadProducts();

    return () => {
      isMounted = false;
    };
  }, [currentProductId, limit, apiUrl, isRecentlyViewed, title]);

  const scroll = (direction) => {
    if (scrollRef.current) {
      const scrollAmount = scrollRef.current.clientWidth * 0.75;
      scrollRef.current.scrollBy({
        left: direction === "left" ? -scrollAmount : scrollAmount,
        behavior: "smooth"
      });
    }
  };

  if (loading && products.length === 0) {
    return (
      <div className="rv-section-container">
        <div className="rv-header">
          <h3 className="rv-title">{title}</h3>
        </div>
        <div className="rv-slider-track">
          {[1, 2, 3, 4].map((n) => (
            <div key={n} className="rv-card shimmer-bg" style={{ height: "240px", borderRadius: "8px" }} />
          ))}
        </div>
      </div>
    );
  }

  if (products.length === 0) {
    return null;
  }

  return (
    <div className="rv-section-container">
      {/* Header */}
      <div className="rv-header">
        <h3 className="rv-title">{title}</h3>
        {showViewAll && (
          <Link to={viewAllUrl} className="rv-view-all">
            View all <FaChevronRight size={10} />
          </Link>
        )}
      </div>

      {/* Slider Container */}
      <div className="rv-slider-container">
        <button
          type="button"
          className="rv-nav-btn rv-nav-btn-prev d-none d-md-flex"
          onClick={() => scroll("left")}
          aria-label="Previous products"
        >
          <FaChevronLeft size={12} />
        </button>

        <div className="rv-slider-track" ref={scrollRef}>
          {products.map((product, index) => {
            const rawImg =
              product.image ||
              product.primary_image ||
              product.thumbnail_image ||
              product.thumbnail ||
              (Array.isArray(product.images) ? product.images[0] : "") ||
              "/default-img.jpg";
            const imgSrc = resolveImageUrl(rawImg);
            const productTitle = product.title || product.name || "Custom Printed Product";

            const sellingPrice = Math.round(
              Number(
                product.discount_price ??
                product.selling_price ??
                product.offer_price ??
                product.perPiecePrice ??
                product.price ??
                499
              )
            );

            let originalPrice = Math.round(
              Number(
                product.originalPrice ??
                product.regular_price ??
                product.mrp ??
                (product.price > sellingPrice ? product.price : sellingPrice * 1.5)
              )
            );

            if (originalPrice <= sellingPrice) {
              originalPrice = Math.round(sellingPrice * 1.5);
            }

            let discountPct = 0;
            if (product.discount) {
              const parsed = parseInt(String(product.discount), 10);
              discountPct = !isNaN(parsed) && parsed > 0 ? parsed : Math.round(((originalPrice - sellingPrice) / originalPrice) * 100);
            } else if (originalPrice > sellingPrice) {
              discountPct = Math.round(((originalPrice - sellingPrice) / originalPrice) * 100);
            }

            const ratingVal = product.rating
              ? typeof product.rating === "number"
                ? product.rating.toFixed(1)
                : String(product.rating)
              : "5.5";

            const reviewsCount =
              product.reviews ??
              product.reviews_count ??
              (12 + ((Number(product.id || index) * 7) % 25));

            const showBestseller =
              product.badge === "Bestseller" ||
              product.is_bestseller ||
              index % 2 === 0;

            const customLabel =
              product.customizationLabel ||
              product.customization_label ||
              "CUSTOMIZABLE";

            return (
              <Link
                key={product.id || index}
                to={getProductUrl(product)}
                className="rv-card"
              >
                {/* Image Container */}
                <div className="rv-card-img-wrap">
                  <img
                    src={imgSrc}
                    alt={productTitle}
                    className="rv-img"
                    loading="lazy"
                    onError={(e) => {
                      e.target.onerror = null;
                      e.target.src = "/default-img.jpg";
                    }}
                  />

                  {/* Top-Left Bestseller Badge */}
                  {showBestseller && (
                    <span className="rv-bestseller-badge">Bestseller</span>
                  )}

                  {/* Bottom Image Overlay Bar */}
                  <div className="rv-customizable-bar">{customLabel}</div>
                </div>

                {/* Product Content Details */}
                <div className="rv-card-info">
                  {/* Title */}
                  <h4 className="rv-card-title" title={productTitle}>
                    {productTitle}
                  </h4>

                  {/* Price Row: ₹499  ₹999  35% Off */}
                  <div className="rv-price-row">
                    <span className="rv-selling-price">₹{sellingPrice}</span>
                    {originalPrice > sellingPrice && (
                      <span className="rv-original-price">₹{originalPrice}</span>
                    )}
                    {discountPct > 0 && (
                      <span className="rv-discount">{discountPct}% Off</span>
                    )}
                  </div>

                  {/* Rating Row: 5.5 ★  18 Reviews */}
                  <div className="rv-rating-row">
                    <span className="rv-rating-pill">
                      {ratingVal} <FaStar size={8} />
                    </span>
                    <span className="rv-reviews-count">
                      {reviewsCount} Reviews
                    </span>
                  </div>
                </div>
              </Link>
            );
          })}
        </div>

        <button
          type="button"
          className="rv-nav-btn rv-nav-btn-next d-none d-md-flex"
          onClick={() => scroll("right")}
          aria-label="Next products"
        >
          <FaChevronRight size={12} />
        </button>
      </div>
    </div>
  );
}
