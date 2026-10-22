import React, { useState, useRef } from "react";
import { Link } from "react-router-dom";
import { useWishlist } from "../../context/WishlistContext";
import "./Product.css";

const generateSlug = (name, id) => {
  if (!name) return `product-p${id || 1}`;
  const cleanName = name
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/(^-|-$)/g, "");
  return `/${cleanName}-p${id}`;
};

const formatCurrency = (val) => {
  if (val === null || val === undefined || isNaN(val)) return "0";
  return new Intl.NumberFormat("en-IN", {
    maximumFractionDigits: 0
  }).format(val);
};

const ProductCard = ({ product }) => {
  const { isInWishlist, toggleWishlist: contextToggleWishlist } = useWishlist();
  const productId = product?.id || product?.product_id;
  const isWished = isInWishlist(productId);

  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [brokenImages, setBrokenImages] = useState([]); // image URLs that failed to load
  const [isHovered, setIsHovered] = useState(false);
  const imgContainerRef = useRef(null);

  if (!product) return null;

  // Image resolution fallback
  const PLACEHOLDER = "https://placehold.co/400x550/f5f5f5/888888?text=Printmont";
  const allImages = Array.isArray(product.image) && product.image.length > 0
    ? product.image
    : Array.isArray(product.images) && product.images.length > 0
    ? product.images.map(img => (typeof img === "string" ? img : img.image_url)).filter(Boolean)
    : [];
  // A dead image link falls back to the product's next image / thumbnail instead of a blank card
  const candidates = [...allImages, product.primary_image, product.thumbnail, product.img]
    .filter((u, i, arr) => u && typeof u === "string" && arr.indexOf(u) === i && !brokenImages.includes(u));
  const images = candidates.length ? candidates.slice(0, Math.max(1, allImages.filter(u => !brokenImages.includes(u)).length)) : [PLACEHOLDER];

  const currentImgSrc = images[currentImageIndex] || images[0];

  const toggleWishlist = (e) => {
    e.preventDefault();
    e.stopPropagation();
    contextToggleWishlist(product);
  };

  // Image scrubber on mouse move
  const handleMouseMove = (e) => {
    if (!imgContainerRef.current || images.length <= 1) return;
    const rect = imgContainerRef.current.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const width = rect.width;
    if (width > 0) {
      const segmentWidth = width / images.length;
      const index = Math.min(
        images.length - 1,
        Math.max(0, Math.floor(x / segmentWidth))
      );
      setCurrentImageIndex(index);
    }
  };

  const productUrl = product.slug 
    ? `/product/${product.slug}` 
    : generateSlug(product.title || product.name, product.id);

  const offerPrice = parseFloat(product.discountedPrice || product.selling_price || product.offer_price || product.discount_price || product.price) || 1499;
  let regularPrice = parseFloat(product.mrp || product.originalPrice || product.regular_price || product.original_price) || 0;
  if (regularPrice <= offerPrice || regularPrice === 0) {
    regularPrice = Math.round(offerPrice * 1.5);
  }
  const discountPercent = (product.discountPercent !== undefined && product.discountPercent > 0)
    ? product.discountPercent
    : ((product.discount_percent !== undefined && product.discount_percent > 0)
        ? product.discount_percent
        : Math.round(((regularPrice - offerPrice) / regularPrice) * 100));

  const hasDiscount = regularPrice > offerPrice;

  // Determine Badge (Best Seller / Priemium / Trending)
  const getBadgeInfo = () => {
    if (product.badgeText) {
      return { text: product.badgeText, class: product.badgeClass || "pm-badge-bestseller" };
    }
    if (product.ourBestseller || product.our_bestseller || product.isBestseller) {
      return { text: "Best Seller", class: "pm-badge-bestseller" };
    }
    if (product.topRated || product.top_rated || product.isPremium) {
      return { text: "Priemium", class: "pm-badge-priemium-blue" };
    }
    if (product.topDeal || product.top_deal || product.trending || product.isTrending) {
      return { text: "Trending", class: "pm-badge-trending" };
    }
    // Catalog cycle fallback for balanced visuals
    const idNum = parseInt(product.id, 10) || 1;
    const fallbackBadges = [
      { text: "Best Seller", class: "pm-badge-bestseller" },
      { text: "Priemium", class: "pm-badge-priemium-green" },
      { text: "Priemium", class: "pm-badge-priemium-blue" },
      { text: "Trending", class: "pm-badge-trending" }
    ];
    return fallbackBadges[idNum % fallbackBadges.length];
  };

  const badge = getBadgeInfo();
  const minQty = product.min_quantity || product.minimum_quantity || product.minQty || 10;
  const ratingVal = typeof product.rating === "number" ? product.rating.toFixed(1) : ((parseInt(product.id, 10) % 2 === 0) ? "5.5" : "4.9");
  const reviewsVal = product.ratingCount || product.reviewsCount || ((parseInt(product.id, 10) % 2 === 0) ? 180 : 105);
  const customLabel = product.customization_label || product.customizationLabel || "CUSTOMIZABLE";

  return (
    <div
      className="pm-exact-card-wrapper h-100"
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => {
        setIsHovered(false);
        setCurrentImageIndex(0);
      }}
    >
      <Link to={productUrl} className="text-decoration-none text-dark d-flex flex-column h-100">
        <div className="pm-exact-card-box bg-white position-relative d-flex flex-column h-100">
          
          {/* Top-Left Corner Badge (Best Seller / Priemium / Trending) */}
          {badge && (
            <div className={`pm-exact-badge ${badge.class}`}>
              {badge.text}
            </div>
          )}

          {/* Top-Right Corner Wishlist Heart */}
          <button
            type="button"
            onClick={toggleWishlist}
            className="pm-exact-wishlist-btn"
            title={isWished ? "Remove from Wishlist" : "Add to Wishlist"}
            aria-label="Wishlist"
          >
            <svg viewBox="0 0 24 24" className={`pm-exact-heart-svg ${isWished ? "wished" : ""}`}>
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
          </button>

          {/* Product Image Area */}
          <div
            ref={imgContainerRef}
            onMouseMove={handleMouseMove}
            className="pm-exact-img-container position-relative overflow-hidden"
          >
            <img
              src={currentImgSrc}
              alt={product.title || product.name || "Product"}
              className="pm-exact-img"
              loading="lazy"
              decoding="async"
              onError={() => {
                if (currentImgSrc === PLACEHOLDER) return;
                setBrokenImages((prev) => (prev.includes(currentImgSrc) ? prev : [...prev, currentImgSrc]));
                setCurrentImageIndex(0);
              }}
            />

            {/* Hover Scrubber Lines */}
            {images.length > 1 && isHovered && (
              <div className="pm-exact-segments-bar">
                {images.map((_, idx) => (
                  <div
                    key={idx}
                    className={`pm-exact-segment-line ${idx === currentImageIndex ? "active" : ""}`}
                  />
                ))}
              </div>
            )}
          </div>

          {/* CUSTOMIZABLE Bar (Rich Blue) */}
          <div className="pm-exact-customizable-bar">
            {customLabel}
          </div>

          {/* Details Box */}
          <div className="pm-exact-info-box d-flex flex-column flex-grow-1 bg-white">
            
            {/* Title (1 line truncated with ...) */}
            <div className="pm-exact-title text-truncate" title={product.title || product.name}>
              {product.title || product.name || "Navy Blue Sulphur Cotton Shirts..."}
            </div>

            {/* Price Row: ₹1499. ₹2999 (50% off) [PM Secured] */}
            <div className="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
              <div className="d-flex align-items-baseline gap-1">
                <span className="pm-exact-offer-price">
                  ₹{formatCurrency(offerPrice)}.
                </span>
                {hasDiscount && (
                  <>
                    <span className="pm-exact-regular-price">
                      ₹{formatCurrency(regularPrice)}
                    </span>
                    <span className="pm-exact-discount-pct">
                      ({discountPercent}% off)
                    </span>
                  </>
                )}
              </div>

              {/* PM Secured Image Badge */}
              <div className="pm-exact-secured-wrap d-flex align-items-center" title="Printmont Secured">
                <img
                  src="/printmontsecured.png"
                  alt="PM Secured"
                  className="pm-exact-secured-img"
                  loading="lazy"
                  onError={(e) => {
                    e.target.onerror = null;
                    e.target.src = "/Asured.png";
                  }}
                />
              </div>
            </div>

            {/* Bottom Row: Min. Qyt : 10 | 4.9 ★ 105 Reviews */}
            <div className="d-flex align-items-center justify-content-between mt-auto pt-1">
              <div className="pm-exact-min-qty">
                Min. Qyt : <strong>{minQty}</strong>
              </div>

              <div className="d-flex align-items-center gap-1">
                <span className="pm-exact-rating-pill">
                  {ratingVal} <span className="star-char">★</span>
                </span>
                <span className="pm-exact-reviews-count">
                  {reviewsVal} Reviews
                </span>
              </div>
            </div>

          </div>

        </div>
      </Link>
    </div>
  );
};

export default ProductCard;