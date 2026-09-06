import React, { useState, useRef } from "react";
import { GoHeartFill } from "react-icons/go";
import { FaStar, FaShieldAlt, FaCaretDown } from "react-icons/fa";
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
  return new Intl.NumberFormat("en-IN", {
    maximumFractionDigits: 0
  }).format(val);
};

const ProductCard = ({ product }) => {
  const { isInWishlist, toggleWishlist: contextToggleWishlist } = useWishlist();
  const isWished = isInWishlist(product.id || product.product_id);
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [isHovered, setIsHovered] = useState(false);
  const imgContainerRef = useRef(null);

  const images = Array.isArray(product.image) && product.image.length > 0
    ? product.image
    : Array.isArray(product.images) && product.images.length > 0
    ? product.images
    : ["https://placehold.co/400x550/f5f5f5/888888?text=Printmont"];

  const toggleWishlist = (e) => {
    e.preventDefault();
    e.stopPropagation();
    contextToggleWishlist(product);
  };

  // Flipkart Interactive Mouse-Move Image Scrubber
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

  const handleMouseEnter = () => {
    setIsHovered(true);
  };

  const handleMouseLeave = () => {
    setIsHovered(false);
    setCurrentImageIndex(0);
  };

  const productUrl = generateSlug(product.title || product.name, product.id);
  const currentImgSrc = images[currentImageIndex] || images[0];
  const hasDiscount = product.discountPercent > 0;
  const isAssured = product.assured || product.ourBestseller || product.topRated;

  // Determine stock alert text (like Flipkart's "Only few left")
  const isLowStock = product.id % 2 === 0;

  return (
    <div
      className="ap-product-card-wrapper h-100"
      onMouseEnter={handleMouseEnter}
      onMouseLeave={handleMouseLeave}
    >
      <Link to={productUrl} className="text-decoration-none text-dark d-flex flex-column h-100">
        <div className="ap-card-container bg-white border position-relative overflow-hidden d-flex flex-column h-100">
          
          {/* Top Sponsored / Ad Tag */}
          {product.sponsored && (
            <span className="ap-badge-sponsored">Ad</span>
          )}

          {/* Wishlist Heart Icon */}
          <button
            type="button"
            onClick={toggleWishlist}
            className={`ap-wishlist-btn ${isWished ? "active" : ""}`}
            title={isWished ? "Remove from Wishlist" : "Add to Wishlist"}
            aria-label="Wishlist"
          >
            <GoHeartFill size={22} />
          </button>

          {/* STATIC FIXED IMAGE BOX (Does NOT move or shift on hover) */}
          <div
            ref={imgContainerRef}
            onMouseMove={handleMouseMove}
            className="ap-card-img-wrap position-relative overflow-hidden"
          >
            <img
              src={currentImgSrc}
              alt={product.title || "Product"}
              className="ap-card-img img-fluid"
              loading="lazy"
              decoding="async"
              onError={(e) => {
                e.target.onerror = null;
                e.target.src = "https://placehold.co/400x550/f5f5f5/888888?text=Printmont";
              }}
            />

            {/* Flipkart Horizontal Segment Indicators */}
            {images.length > 1 && isHovered && (
              <div className="ap-fk-segments-bar">
                {images.map((_, idx) => (
                  <div
                    key={idx}
                    className={`ap-fk-segment-line ${idx === currentImageIndex ? "active" : ""}`}
                  />
                ))}
              </div>
            )}

            {/* Seller Assured Badge — bottom-right corner of the image, like Flipkart's "Brand Authorized Seller" shield */}
            {isAssured && (
              <div className="ap-fk-assured-badge" title="Printmont Assured Quality">
                <FaShieldAlt size={11} />
                <span className="ap-fk-assured-sub">ASSURED</span>
              </div>
            )}
          </div>

          {/* BOTTOM SLIDE-UP INFO PANEL (Only this section slides UP on hover) */}
          <div className="ap-card-body ap-card-body-slide p-2 d-flex flex-column flex-grow-1 bg-white">
            <span className="ap-brand-name text-uppercase text-muted fw-bold text-truncate mb-1">
              {product.brand}
            </span>

            {/* Title (turns Flipkart Blue on Hover) */}
            <h3 className="ap-product-title text-dark mb-1" title={product.title}>
              {product.title}
            </h3>

            {/* Rating Pill */}
            {product.rating && (
              <div className="d-flex align-items-center mb-1">
                <span className="ap-rating-pill text-white px-1 py-0 rounded d-inline-flex align-items-center me-2">
                  {product.rating.toFixed(1)} <FaStar size={9} className="ms-1" />
                </span>
                <span className="ap-rating-count text-muted">
                  ({product.ratingCount || 42})
                </span>
              </div>
            )}

            {/* Price & Offer Row — discount % first, then struck-through original, then the price you pay */}
            <div className="mt-auto pt-1 d-flex align-items-baseline flex-wrap gap-1">
              {hasDiscount && (
                <>
                  <span className="ap-discount-percent fw-semibold d-inline-flex align-items-center">
                    <FaCaretDown size={11} />{product.discountPercent}%
                  </span>
                  <span className="ap-price-original text-muted text-decoration-line-through">
                    ₹{formatCurrency(product.originalPrice)}
                  </span>
                </>
              )}
              <span className="ap-price-discounted fw-bold fs-6 text-dark">
                ₹{formatCurrency(product.discountedPrice || product.price)}
              </span>
            </div>

            {/* Stock alert */}
            {isLowStock ? (
              <div className="ap-stock-alert text-danger fw-semibold mt-1">
                Only few left
              </div>
            ) : product.discountPercent >= 50 ? (
              <div className="ap-hot-deal-tag text-success fw-semibold mt-1">
                Hot Deal
              </div>
            ) : null}

              {/* SLIDE-UP REVEAL DETAILS (Product-specific sizes or category highlights) */}
              {product.sizes && product.sizes.length > 0 ? (
                <div className="ap-slideup-details mt-2 pt-1 border-top text-truncate">
                  <span className="ap-fk-sizes-text">
                    Size {product.sizes.join(", ")}
                  </span>
                </div>
              ) : product.highlightText ? (
                <div className="ap-slideup-details mt-2 pt-1 border-top text-truncate">
                  <span className="ap-fk-sizes-text text-primary">
                    {product.highlightText}
                  </span>
                </div>
              ) : null}
          </div>

        </div>
      </Link>
    </div>
  );
};

export default ProductCard;