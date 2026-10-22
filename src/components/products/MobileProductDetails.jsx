import React, { useState } from "react";
import { Link } from "react-router-dom";
import { Swiper, SwiperSlide } from "swiper/react";
import { Pagination } from "swiper/modules";
import "swiper/css";
import "swiper/css/pagination";

import {
  FaHeart,
  FaRegHeart,
  FaShare,
  FaStar,
  FaTruck,
  FaUndoAlt,
  FaMoneyBillWave,
  FaUpload,
  FaPencilAlt,
  FaChevronRight,
  FaCheckCircle,
  FaInfoCircle,
  FaMapMarkerAlt
} from "react-icons/fa";
import { BsCart4 } from "react-icons/bs";
import { RiDiscountPercentFill } from "react-icons/ri";
import { API_ENDPOINTS, resolveImageUrl, BASE_URL } from "../../config/apiEndpoints";
import ProductCustomizationForm from "./ProductCustomizationForm";
import SmallBannerCarousel from "../home/banners/SmallBannerCarousel";
import RecentlyViewedProducts from "./RecentlyViewedProducts";
import ProductReview from "./ProductReview";
import ProductQASection from "./ProductQASection";
import CategoryBannerTexts from "./CategoryBannerTexts";
import SaleCountdown from "./SaleCountdown";
import GiftVoucherOffer from "./GiftVoucherOffer";
import useProductLayout from "./useProductLayout";
import "./MobileProductDetails.css";

const optionLabel = (item) => {
  if (item == null) return '';
  if (typeof item === 'string') return item;
  const label = item.name ?? item.label ?? item.size ?? item.material ?? item.color ?? String(item);
  return item.price ? `${label} (+₹${item.price})` : label;
};

const optionValue = (item) => {
  if (item == null) return '';
  if (typeof item === 'string') return item;
  return item.name ?? item.label ?? item.size ?? item.material ?? item.color ?? String(item);
};

// Coupon cards under the price (scroll sideways)
const coupons = [
  { code: "PRNTM5", lead: "Get Flat", off: "5% OFF", desc: "Add items worth ₹1999+ to unlock this offer", color: "#0b8fe0" },
  { code: "PRNTM10", lead: "Get Flat", off: "10% OFF", desc: "Add items worth ₹3999+ to unlock this offer", color: "#1aa34a" },
  { code: "PRNTM15", lead: "Get Flat", off: "15% OFF", desc: "Add items worth ₹5999+ to unlock this offer", color: "#0b8fe0" },
];

// These sections sit together in one white block (no grey gap after them), as in the mobile design
const JOINED_SECTIONS = ["product_gallery", "title_pricing", "offers_coupons", "customization_options", "quantity_matrix", "ratings_reviews"];

export default function MobileProductDetails({
  productData,
  isWishlisted,
  setIsWishlisted,
  ratingStats,
  selectedSize,
  setSelectedSize,
  selectedColor,
  setSelectedColor,
  selectedMaterial,
  setMaterial,
  selectedLocations,
  setSelectedLocations,
  sizeSplit = {},
  handleSizeChange = () => {},
  quantity,
  setQuantity,
  dynamicUnitPrice,
  totalPrice,
  nextPriceTier = null,
  designData = { images: [], texts: [] },
  bankOffers = [],
  customizationValues = {},
  onSaleEnd,
  setCustomizationValues = () => {},
  pincode,
  setPincode,
  deliveryAvailable,
  deliveryMsg,
  checkDelivery,
  handleApplyCoupon,
  setShowBulkModal,
  setShowDesignModal,
  setShowUploadModal,
  isInCart,
  addToCart,
  buyNow,
  removeItem
}) {
  const [activeTab, setActiveTab] = useState("description");
  const [copiedCode, setCopiedCode] = useState("");
  const { sections: layoutSections } = useProductLayout("mobile", 3000);

  const handleShare = async () => {
    if (navigator.share) {
      try {
        await navigator.share({ title: productData.title, url: window.location.href });
      } catch { /* share sheet closed */ }
    } else {
      navigator.clipboard.writeText(window.location.href);
      alert("Product link copied to clipboard!");
    }
  };

  const applyCoupon = (code) => {
    handleApplyCoupon(code);
    setCopiedCode(code);
  };

  const scrollToReviews = () => {
    const el = document.getElementById("mpd-reviews");
    if (el) window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - 72, behavior: "smooth" });
  };

  const isCustomizable = Boolean(productData.customization_label_status);
  const isOutOfStock = productData.out_of_stock_status === 'out_of_stock' || productData.stock_quantity <= 0;
  const minQty = productData.min_quantity || 1;
  const maxQty = productData.stock_quantity > 0 ? productData.stock_quantity : 3000;
  const ratingAverage = Number(ratingStats?.average) || 0;
  const ratingCount = Number(ratingStats?.count) || 0;
  const hasSizes = Boolean(productData.size_status && productData.sizes && productData.sizes.length > 0);
  const listOf = (v) => (Array.isArray(v) ? v : []);

  const renderSection = (sec) => {
    switch (sec.section_type) {
      case "product_gallery":
        return (
          <div className="mpd-gallery">
            <div className="mpd-gallery__badges">
              {productData.our_bestseller && <span className="mpd-tag mpd-tag--best">Best Seller</span>}
              {isCustomizable && <span className="mpd-tag mpd-tag--custom">{productData.customization_label || 'Customizable'}</span>}
            </div>
            <div className="mpd-gallery__actions">
              <button
                type="button"
                className="mpd-fab"
                onClick={() => setIsWishlisted(!isWishlisted)}
                aria-label={isWishlisted ? "Remove from wishlist" : "Add to wishlist"}
              >
                {isWishlisted ? <FaHeart className="text-danger" size={16} /> : <FaRegHeart size={16} />}
              </button>
              <button type="button" className="mpd-fab" onClick={handleShare} aria-label="Share">
                <FaShare size={14} />
              </button>
            </div>
            <Swiper pagination={{ clickable: true }} modules={[Pagination]} slidesPerView={1} className="mpd-gallery__swiper">
              {(productData.images || []).map((img, i) => (
                <SwiperSlide key={i}>
                  <img src={resolveImageUrl(img)} alt={`${productData.title} ${i + 1}`} className="mpd-gallery__img" />
                </SwiperSlide>
              ))}
            </Swiper>
          </div>
        );

      case "title_pricing":
        return (
          <div className="mpd-pad mpd-info">
            {productData.sale && <SaleCountdown sale={productData.sale} onEnd={onSaleEnd} compact />}
            <h1 className="mpd-title">
              {productData.brand && <span className="mpd-title__brand">{productData.brand} </span>}
              {productData.title}
            </h1>
            <div className="mpd-rating-row">
              {ratingCount > 0 ? (
                <button type="button" className="mpd-rating" onClick={scrollToReviews}>
                  <span className="mpd-rating__badge">{ratingAverage.toFixed(1)} <FaStar size={10} /></span>
                  <span className="mpd-rating__count">{ratingCount} {ratingCount === 1 ? 'Review' : 'Reviews'}</span>
                </button>
              ) : (
                <span className="mpd-rating__count">No reviews yet</span>
              )}
              <img src="/Asured.png" alt="Printmont Assured" className="mpd-assured" onError={(e) => { e.target.style.display = 'none'; }} />
            </div>
            <div className="mpd-price">
              <span className="mpd-price__sp">₹{dynamicUnitPrice.toFixed(0)}</span>
              {productData.originalPrice > dynamicUnitPrice && (
                <>
                  <span className="mpd-price__mrp">₹{productData.originalPrice.toFixed(0)}</span>
                  {productData.discountPercent > 0 && <span className="mpd-price__off">({productData.discountPercent}% off)</span>}
                </>
              )}
            </div>
            {minQty > 1 && <div className="mpd-moq">Minimum order {minQty} pieces.</div>}
            <GiftVoucherOffer productId={productData.id} compact />
            {isOutOfStock ? (
              <div className="mpd-stock mpd-stock--out">Currently out of stock</div>
            ) : productData.out_of_stock_status === 'pre_order' ? (
              <div className="mpd-stock">Available for pre-order</div>
            ) : productData.show_left_quantity && productData.stock_quantity <= 10 ? (
              <div className="mpd-stock mpd-stock--out">Hurry, only {productData.stock_quantity} left!</div>
            ) : null}
          </div>
        );

      case "offers_coupons":
        return (
          <div className="mpd-coupons" aria-label="Coupons">
            {coupons.map((c) => (
              <button key={c.code} type="button" className="mpd-coupon" onClick={() => applyCoupon(c.code)}>
                <RiDiscountPercentFill className="mpd-coupon__icon" style={{ color: c.color }} />
                <span className="mpd-coupon__body">
                  <span className="mpd-coupon__title">{c.lead} <em>{c.off}</em></span>
                  <span className="mpd-coupon__desc">{c.desc}</span>
                  <span className="mpd-coupon__foot">
                    <span className={copiedCode === c.code ? "text-success fw-semibold" : ""}>
                      {copiedCode === c.code ? "✓ Code copied" : "Apply coupon at checkout"}
                    </span>
                    <span>Code: <b>{c.code}</b></span>
                  </span>
                </span>
              </button>
            ))}
          </div>
        );

      case "customization_options":
        return (
          <div className="mpd-pad mpd-options">
            {hasSizes && (
              <div className="mpd-row">
                <span className="mpd-row__label">Size</span>
                <select className="mpd-select" value={selectedSize} onChange={(e) => setSelectedSize(e.target.value)}>
                  {productData.sizes.map((sz, i) => (
                    <option key={i} value={optionValue(sz)}>{optionLabel(sz)}</option>
                  ))}
                </select>
              </div>
            )}
            {productData.colors && productData.colors.length > 0 && (
              <div className="mpd-row">
                <span className="mpd-row__label">Colour</span>
                <select className="mpd-select" value={selectedColor} onChange={(e) => setSelectedColor(e.target.value)}>
                  {productData.colors.map((c, i) => (
                    <option key={i} value={optionValue(c)}>{optionLabel(c)}</option>
                  ))}
                </select>
              </div>
            )}
            {productData.materials && productData.materials.length > 0 && (
              <div className="mpd-row">
                <span className="mpd-row__label">Material / Fabric</span>
                <select className="mpd-select" value={selectedMaterial} onChange={(e) => setMaterial(e.target.value)}>
                  {productData.materials.map((m, i) => (
                    <option key={i} value={optionValue(m)}>{optionLabel(m)}</option>
                  ))}
                </select>
              </div>
            )}
            {productData.type && (
              <div className="mpd-row">
                <span className="mpd-row__label">Type</span>
                <span className="mpd-row__value">{productData.type}</span>
              </div>
            )}
            {isCustomizable && listOf(productData.printing_location).length > 0 && (
              <div className="mpd-row">
                <span className="mpd-row__label">Print Location</span>
                <select className="mpd-select" value={selectedLocations[0] || productData.printing_location[0]} onChange={(e) => setSelectedLocations([e.target.value])}>
                  {productData.printing_location.map((loc, i) => (
                    <option key={i} value={loc}>{loc}</option>
                  ))}
                </select>
              </div>
            )}
            {listOf(productData.lamination).length > 0 && (
              <div className="mpd-row">
                <span className="mpd-row__label">Lamination</span>
                <span className="mpd-row__value">{productData.lamination.join(', ')}</span>
              </div>
            )}
            {listOf(productData.orientation).length > 0 && (
              <div className="mpd-row">
                <span className="mpd-row__label">Orientation</span>
                <span className="mpd-row__value text-capitalize">{productData.orientation.join(', ')}</span>
              </div>
            )}
            {productData.customization_tech && (
              <div className="mpd-row">
                <span className="mpd-row__label">Printing Technology</span>
                <span className="mpd-row__value">{productData.customization_tech}</span>
              </div>
            )}

            {isCustomizable && productData.customization_data && productData.customization_data.length > 0 && (
              <div className="mpd-options__custom">
                <ProductCustomizationForm fields={productData.customization_data} values={customizationValues} onChange={setCustomizationValues} />
              </div>
            )}

            <div className="mpd-row">
              <span className="mpd-row__label">Quantity</span>
              <div>
                <div className="mpd-qty">
                  <button type="button" onClick={() => setQuantity(Math.max(minQty, quantity - 1))} disabled={quantity <= minQty} aria-label="Decrease quantity">−</button>
                  <input
                    type="number"
                    inputMode="numeric"
                    min={minQty}
                    max={maxQty}
                    value={quantity}
                    onChange={(e) => setQuantity(Math.max(minQty, parseInt(e.target.value) || minQty))}
                    aria-label="Quantity"
                  />
                  <button type="button" onClick={() => setQuantity(quantity + 1)} aria-label="Increase quantity">+</button>
                </div>
                <p className="mpd-qty__hint">
                  Choose a quantity between {minQty} - {maxQty} for instant ordering. For higher quantities, you can request a quotation from our Sales Team.
                </p>
              </div>
            </div>

            {hasSizes && (
              <div className="mpd-split">
                <div className="mpd-split__title">Size split up:</div>
                <div className="mpd-split__grid" style={{ gridTemplateColumns: `repeat(${Math.min(productData.sizes.length, 5)}, minmax(0, 1fr))` }}>
                  {productData.sizes.map((sz) => {
                    const val = optionValue(sz);
                    return (
                      <label key={val} className="mpd-split__cell">
                        <span>{val}</span>
                        <input
                          type="number"
                          inputMode="numeric"
                          min="0"
                          placeholder="–"
                          value={sizeSplit[val] || ''}
                          onChange={(e) => handleSizeChange(val, e.target.value)}
                        />
                      </label>
                    );
                  })}
                </div>
              </div>
            )}
          </div>
        );

      case "quantity_matrix":
        return (
          <div className="mpd-pad mpd-summary">
            <div className="mpd-summary__row">
              <span>Per piece</span>
              <strong>₹{dynamicUnitPrice.toFixed(0)}</strong>
            </div>
            <div className="mpd-summary__row">
              <span>Quantity</span>
              <strong>{quantity}</strong>
            </div>
            {nextPriceTier && (
              <button type="button" className="mpd-summary__hint" onClick={() => setQuantity(nextPriceTier.qty)}>
                Buy {nextPriceTier.qty}+ pcs at ₹{nextPriceTier.price.toLocaleString('en-IN')}/pc — save ₹{(dynamicUnitPrice - nextPriceTier.price).toLocaleString('en-IN', { maximumFractionDigits: 0 })} per piece
              </button>
            )}
            <div className="mpd-summary__total">
              <span>Total</span>
              <strong>₹{totalPrice.toFixed(2)}</strong>
            </div>
          </div>
        );

      case "customization_buttons":
        if (!productData.show_bulk_form && !isCustomizable) return null;
        return (
          <div className="mpd-pad mpd-ctas">
            {productData.show_bulk_form && (
              <button type="button" className="mpd-bulk-link" onClick={() => setShowBulkModal(true)}>
                Interested in Bulk Discounts? Enquire Now <FaInfoCircle size={12} />
              </button>
            )}
            {isCustomizable && (
              <>
                <button type="button" className="mpd-design-btn mpd-design-btn--upload" onClick={() => setShowUploadModal(true)}>
                  <FaUpload size={15} /> Upload your design
                </button>
                <button type="button" className="mpd-design-btn mpd-design-btn--create" onClick={() => setShowDesignModal(true)}>
                  <FaPencilAlt size={14} /> Create your design
                </button>
                {(designData.images.length > 0 || designData.texts.length > 0) && (
                  <div className="mpd-design-summary">
                    <FaCheckCircle />
                    <span>
                      {[
                        designData.images.length ? `${designData.images.length} design${designData.images.length > 1 ? 's' : ''}` : null,
                        designData.texts.length ? `${designData.texts.length} text${designData.texts.length > 1 ? 's' : ''}` : null
                      ].filter(Boolean).join(' · ')} added
                    </span>
                    <button type="button" onClick={() => setShowUploadModal(true)}>Edit</button>
                  </div>
                )}
              </>
            )}
          </div>
        );

      case "delivery_pincode_check":
        return (
          <>
            <div className="mpd-pad">
              <h2 className="mpd-h2 mpd-h2--light">Check Delivery Date</h2>
              <div className="mpd-pincode">
                <FaMapMarkerAlt className="mpd-pincode__icon" size={18} />
                <input
                  type="text"
                  inputMode="numeric"
                  placeholder="Enter Your City Pincode*"
                  maxLength={6}
                  value={pincode}
                  onChange={(e) => setPincode(e.target.value.replace(/\D/g, ''))}
                  aria-label="Pincode"
                />
                <button type="button" onClick={checkDelivery}>Check</button>
              </div>
              {deliveryMsg && (
                <div className={`mpd-pincode__msg ${deliveryAvailable ? "text-success" : "text-danger"}`}>{deliveryMsg}</div>
              )}
            </div>
            <div className="mpd-gap" />
            <ul className="mpd-services">
              <li>
                <FaTruck className="mpd-services__icon" size={18} />
                <span className="mpd-services__text">
                  {productData.shipping_method_status && productData.national_shipping_price > 0 ? (
                    <>Delivery ₹{productData.national_shipping_price}</>
                  ) : (
                    <><span className="text-success fw-semibold">FREE Delivery</span> on this product</>
                  )}
                </span>
                <FaChevronRight size={12} className="mpd-services__chev" />
              </li>
              <li>
                <FaUndoAlt className="mpd-services__icon" size={16} />
                <span className="mpd-services__text">
                  {productData.cancel_status === 'yes' ? `${productData.cancel_value} ${productData.cancel_type} Return Policy` : 'Quality Assured'}
                </span>
                <FaChevronRight size={12} className="mpd-services__chev" />
              </li>
              <li>
                <FaMoneyBillWave className="mpd-services__icon" size={16} />
                <span className="mpd-services__text">
                  {productData.cod_status === 'yes' ? 'Cash on Delivery Available' : 'Prepaid Only'}
                </span>
                <FaChevronRight size={12} className="mpd-services__chev" />
              </li>
            </ul>
          </>
        );

      case "loyalty_coins_reward":
        return (
          <>
            <div className="mpd-pad mpd-row-between">
              <h2 className="mpd-h2 mb-0">
                Discount Coupons <span className="mpd-save">Save ₹50</span>
              </h2>
              <button type="button" className="mpd-outline-btn" onClick={() => applyCoupon('PRNTM5')}>
                {copiedCode === 'PRNTM5' ? 'Applied' : 'Apply'}
              </button>
            </div>
            <div className="mpd-gap" />
            <Link to="/printmont-coin" className="mpd-pad mpd-coin">
              <img
                src="/printmont-coin-offer.png"
                alt="Printmont Coin: For every ₹100 spent, you earn 2 Printmont Coins. Max 50 coins per order."
                loading="lazy"
              />
            </Link>
          </>
        );

      case "partner_offers":
        if (!bankOffers.length) return null;
        return (
          <div className="mpd-pad">
            <div className="mpd-offers-head">
              <span><RiDiscountPercentFill size={20} className="text-success" /> All Offers &amp; Coupons</span>
              <FaChevronRight size={14} />
            </div>
            <div className="mpd-offer-list">
              {bankOffers.map((b) => (
                <div key={b.name} className={`mpd-offer mpd-offer--${b.theme}`}>
                  <span className="mpd-offer__logo">
                    <img src={b.logo} alt={b.name} onError={(e) => { e.target.replaceWith(document.createTextNode(b.name.split(' ')[0])); }} />
                  </span>
                  <span className="mpd-offer__text">{b.desc}</span>
                  <FaInfoCircle className="mpd-offer__info" size={16} />
                </div>
              ))}
            </div>
          </div>
        );

      case "about_product_tabs":
        return (
          <div className="mpd-pad">
            <h2 className="mpd-h2">About the product</h2>
            <div className="mpd-tabs" role="tablist">
              {[
                { key: 'description', label: 'Description' },
                { key: 'instructions', label: 'Instructions' },
                { key: 'delivery', label: 'Delivery Info' }
              ].map((t) => (
                <button
                  key={t.key}
                  type="button"
                  role="tab"
                  aria-selected={activeTab === t.key}
                  className={`mpd-tab ${activeTab === t.key ? 'is-active' : ''}`}
                  onClick={() => setActiveTab(t.key)}
                >
                  {t.label}
                </button>
              ))}
            </div>
            <div className="mpd-tab-body">
              {activeTab === 'description' && (
                <>
                  {productData.long_description ? (
                    <div className="product-description-html" dangerouslySetInnerHTML={{ __html: productData.long_description }} />
                  ) : productData.description ? (
                    <div className="product-description-html" dangerouslySetInnerHTML={{ __html: productData.description }} />
                  ) : (
                    <p>Premium quality product crafted with precision.</p>
                  )}
                  <div className="mpd-details">
                    <div className="mpd-details__title">Product Details:</div>
                    <ul className="mpd-details__list">
                      {[
                        ['Brand', productData.brand],
                        ['SKU', productData.sku],
                        ['Category', productData.category_name],
                        ['Material', productData.material],
                        ['Colour', productData.color],
                        ['Type / Style', productData.type],
                        ['Key features', productData.features],
                        ['Occasion', productData.occasion],
                        ['Gift type', productData.gift_type],
                        ['Ideal for', productData.ideal_for],
                        ['Gender', productData.gender && productData.gender.charAt(0).toUpperCase() + productData.gender.slice(1)],
                        ['Shape', productData.shape],
                        ['Capacity / Size', productData.capacity],
                        ['Ink colour', productData.ink_color],
                        ['Printing technology', productData.customization_tech],
                        ['Print location', listOf(productData.printing_location).join(', ')],
                        ['Lamination', listOf(productData.lamination).join(', ')],
                        ['Orientation', listOf(productData.orientation).map((o) => o.charAt(0).toUpperCase() + o.slice(1)).join(', ')],
                        ['Country of origin', 'India']
                      ].filter(([, value]) => value).map(([label, value]) => (
                        <li key={label}>{label}: {value}</li>
                      ))}
                    </ul>
                  </div>
                </>
              )}
              {activeTab === 'instructions' && (
                productData.instructions ? (
                  <div dangerouslySetInnerHTML={{ __html: productData.instructions }} />
                ) : (
                  <p>Follow standard care, handling and maintenance instructions for this product.</p>
                )
              )}
              {activeTab === 'delivery' && (
                productData.delivery_info ? (
                  <div dangerouslySetInnerHTML={{ __html: productData.delivery_info }} />
                ) : (
                  <ul className="mpd-details__list">
                    <li>Standard courier delivery takes 3-5 business days across India.</li>
                    <li>Shipping: {productData.shipping_method_status && productData.national_shipping_price > 0 ? `₹${productData.national_shipping_price}` : 'FREE Delivery'}</li>
                    <li>Cash on Delivery: {productData.cod_status === 'yes' ? 'Available' : 'Prepaid Only'}</li>
                    <li>Returns / Replacement: {productData.cancel_status === 'yes' ? `${productData.cancel_value} ${productData.cancel_type} replacement policy` : 'Quality Assured'}</li>
                  </ul>
                )
              )}
            </div>
            {/* Category banners (admin: category-banner-texts.php): under every tab, only for this category */}
            <CategoryBannerTexts productId={productData.id} className="mpd-cbt" />
          </div>
        );

      case "marketing_banners":
      case "banner":
        if (!sec.background_image_url) return null;
        return (
          <Link to={sec.banner_target_url || "#"} className="mpd-pad d-block">
            <img src={resolveImageUrl(sec.background_image_url)} alt={sec.label || "Promo Banner"} className="mpd-banner-img" />
          </Link>
        );

      case "small_banner_carousel":
      case "small_banner_slider":
        return sec.section_key === "pdp_small_banner_slider" ? (
          // Below Q&A: admin view-banner.php?tab=banners&filter_page=product → "Product Page – Middle Banner"
          <SmallBannerCarousel sectionKey="product_page_middle" page="product" isMobile={true} height={120} />
        ) : (
          <SmallBannerCarousel page="product" banners={sec.banners} isMobile={true} />
        );

      case "product_carousel": {
        const sKey = sec.section_key || "";
        let carouselApi = API_ENDPOINTS.TOP_RATED;
        // Discount check first: "pdp_discount_similar" also contains "similar"
        if (sec.api_action === "discount" || sKey.includes("discount")) {
          carouselApi = `${BASE_URL}/products/products.php?action=discount_for_you&limit=${sec.product_limit || 8}`;
        } else if (sec.api_action === "related" || sKey.includes("similar")) {
          carouselApi = API_ENDPOINTS.RELATED_PRODUCTS(productData.id);
        } else if (sec.api_action === "top_selection" || sKey.includes("interested") || sKey.includes("you_might")) {
          carouselApi = API_ENDPOINTS.TOP_SELECTION;
        } else if (sec.api_action === "bestseller") {
          carouselApi = API_ENDPOINTS.BESTSELLER;
        } else if (sec.product_ids) {
          carouselApi = `${BASE_URL}/products/products.php?action=handpicked&ids=${sec.product_ids}`;
        }
        return (
          <RecentlyViewedProducts
            currentProductId={productData?.id}
            title={(sec.label || "Products").replace(/\s*\((Carousel|Carou|Category|Slider)\)/gi, "").trim()}
            apiUrl={carouselApi}
            limit={sec.product_limit || 8}
          />
        );
      }

      case "ratings_reviews":
        return (
          <div id="mpd-reviews">
            <ProductReview productId={productData.id} ratingStats={ratingStats} categorySlug={productData.breadcrumb?.[0]?.slug || ""} />
          </div>
        );

      case "questions_answers":
        return <ProductQASection productId={productData.id} />;

      case "recently_viewed":
        return (
          <RecentlyViewedProducts
            currentProductId={productData?.id}
            title={(sec.label || "Your Recently viewed").replace(/\s*\((Carousel|Carou|Category|Slider)\)/gi, "").trim()}
            isRecentlyViewed={true}
            limit={sec.product_limit || 10}
          />
        );

      case "custom_html":
        if (!sec.custom_html) return null;
        return <div className="mpd-pad" dangerouslySetInnerHTML={{ __html: sec.custom_html }} />;

      // Trust badges and the footer come from the site footer
      default:
        return null;
    }
  };

  // Default order if the layout API (admin: product layout, target=mobile) is still loading or offline
  const defaultSections = [
    { section_type: "product_gallery", id: "fb_gallery" },
    { section_type: "title_pricing", id: "fb_title" },
    { section_type: "offers_coupons", id: "fb_coupons" },
    { section_type: "customization_options", id: "fb_custom" },
    { section_type: "quantity_matrix", id: "fb_qty" },
    { section_type: "customization_buttons", id: "fb_ctas" },
    { section_type: "delivery_pincode_check", id: "fb_pincode" },
    { section_type: "loyalty_coins_reward", id: "fb_coins" },
    { section_type: "partner_offers", id: "fb_partners" },
    { section_type: "about_product_tabs", id: "fb_about" },
    { section_type: "product_carousel", section_key: "pdp_similar_products", label: "Similar Products", api_action: "related", id: "fb_sim" },
    { section_type: "ratings_reviews", id: "fb_rev" },
    { section_type: "questions_answers", id: "fb_qa" },
    { section_type: "small_banner_carousel", section_key: "pdp_small_banner_slider", id: "fb_small_banner" },
    { section_type: "product_carousel", section_key: "pdp_discount_similar", label: "Discount on Similar Products", api_action: "discount", id: "fb_disc" },
    { section_type: "product_carousel", section_key: "pdp_you_might_like", label: "You might be interested", api_action: "top_selection", id: "fb_you_might" },
    { section_type: "recently_viewed", label: "Your Recently viewed", id: "fb_rec" }
  ];

  const sectionsToRender = (layoutSections && layoutSections.length > 0) ? layoutSections : defaultSections;

  return (
    <div className="mpd">
      {/* Each section is a white band; an 8px grey gap separates the groups (mobile design PDF) */}
      {sectionsToRender.map((sec, idx) => {
        const content = renderSection(sec);
        if (!content) return null;
        const joined = JOINED_SECTIONS.includes(sec.section_type);
        return (
          <section
            key={sec.id || idx}
            className={`mpd-sec mpd-sec--${sec.section_type} ${joined ? 'mpd-sec--joined' : ''}`}
          >
            {content}
          </section>
        );
      })}

      {/* Add to cart / Buy now: always fixed at the bottom */}
      <div className="mpd-cta">
        {isInCart ? (
          <button type="button" className="mpd-cta__btn mpd-cta__btn--remove" onClick={() => removeItem(productData.id)}>
            REMOVE FROM CART
          </button>
        ) : (
          <button type="button" className="mpd-cta__btn mpd-cta__btn--cart" disabled={isOutOfStock} onClick={addToCart}>
            <BsCart4 size={16} /> ADD TO CART
          </button>
        )}
        <button type="button" className="mpd-cta__btn mpd-cta__btn--buy" disabled={isOutOfStock} onClick={buyNow}>
          BUY NOW
        </button>
      </div>
    </div>
  );
}
