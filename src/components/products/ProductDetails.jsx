import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card } from 'react-bootstrap';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';
import 'swiper/css';
import 'swiper/css/scrollbar';
import 'swiper/css/pagination';

import {
  FaShoppingCart,
  FaBolt,
  FaStar,
  FaTag,
  FaMapMarkerAlt,
  FaHeart,
  FaChevronUp,
  FaChevronDown,
  FaChevronRight,
  FaShare,
  FaUpload,
  FaEdit,
  FaCheckCircle,
  FaShieldAlt,
  FaCoins,
  FaInfoCircle,
  FaPercentage,
  FaSyncAlt,
  FaSearch,
  FaQuestionCircle
} from 'react-icons/fa';

import SliderReact from 'react-slick';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Scrollbar, Pagination } from 'swiper/modules';
import ProductReview from './ProductReview';
import ProductQASection from './ProductQASection';
import FrequentlyBoughtTogether from './FrequentlyBoughtTogether';
import SecondCarousel from "./../pages/carousel/SecondCarousel";
import { discount, girloutfit } from '../../../data/data';
import { Singleproductdata } from '../../../data/reviewData';
import TabCarousel from '../pages/carousel/TabCarousel';
import SingleProduct from '../pages/carousel/Singleproduct';

// Custom arrows for vertical thumbnails
const PrevArrow = ({ className, onClick }) => (
  <div className="custom-vertical-arrow top-arrow" onClick={onClick}>
    <FaChevronUp size="12" />
  </div>
);
const NextArrow = ({ className, onClick }) => (
  <div className="custom-vertical-arrow bottom-arrow" onClick={onClick}>
    <FaChevronDown size="12" />
  </div>
);

// Countdown Timer Component
const CountdownTimer = ({ targetDate }) => {
  const calculateTimeLeft = () => {
    const difference = +new Date(targetDate) - +new Date();
    let timeLeft = { hours: 12, minutes: 15, seconds: 44 };
    if (difference > 0) {
      timeLeft = {
        days: Math.floor(difference / (1000 * 60 * 60 * 24)),
        hours: Math.floor((difference / (1000 * 60 * 60)) % 24),
        minutes: Math.floor((difference / 1000 / 60) % 60),
        seconds: Math.floor((difference / 1000) % 60),
      };
    }
    return timeLeft;
  };

  const [timeLeft, setTimeLeft] = useState(calculateTimeLeft());

  useEffect(() => {
    const timer = setInterval(() => {
      setTimeLeft(calculateTimeLeft());
    }, 1000);
    return () => clearInterval(timer);
  }, [targetDate]);

  return (
    <div className="sales-timer-badge d-flex align-items-center gap-2 mb-2">
      <span className="sales-timer-text">sales End time</span>
      <div className="timer-box bg-danger text-white rounded px-2 py-0.5 fw-bold">
        {String(timeLeft.hours).padStart(2, "0")}h : {String(timeLeft.minutes).padStart(2, "0")}m : {String(timeLeft.seconds).padStart(2, "0")}s
      </div>
    </div>
  );
};

const ScallopedPercentIcon = ({ color }) => (
  <svg viewBox="0 0 64 64" width="40" height="40" style={{ color: color, flexShrink: 0 }} fill="currentColor">
    <path d="M32,2C30.2,2,28.5,3.2,28,5l-1.2,4.3c-.3,1-1.1,1.8-2.1,2.1L20.4,10c-1.8-.5-3.6.4-4.2,2.1l-1.5,4.2c-.3,1-1.2,1.7-2.2,1.7l-4.5-.2c-1.8,0-3.3,1.4-3.5,3.2l-.3,4.5c-.1,1-1,1.8-2,2L2,29.1c-1.5.9-2.2,2.7-1.7,4.4l1.5,4.2c.3,1,.3,2.1,0,3.1L.3,45c-.5,1.7.2,3.5,1.7,4.4l3.9,2.3c1,.6,1.5,1.6,1.6,2.7l.2,4.5c.1,1.8,1.6,3.2,3.4,3.2l4.5-.3c1.1,0,2.1.6,2.6,1.6l2.1,4c.9,1.6,2.8,2.2,4.5,1.5l4.2-1.7c1-.4,2.2-.4,3.2,0l4.2,1.7c1.7.7,3.6.1,4.5-1.5l2.1-4c.5-1,1.5-1.6,2.6-1.6l4.5.3c1.8,0,3.3-1.4,3.4-3.2l.2-4.5c.1-1.1.7-2.1,1.6-2.7l3.9-2.3c1.5-.9,2.2-2.7,1.7-4.4l-1.5-4.2c-.3-1-.3-2.1,0-3.1l1.5-4.2c.5-1.7-.2-3.5-1.7-4.4l-3.9-2.3c-1-.6-1.5-1.6-1.6-2.7l-.2-4.5c-.1-1.8-1.6-3.2-3.4-3.2l-4.5.3c-1.1,0-2.1-.6-2.6-1.6l-2.1-4c-.9-1.6-2.8-2.2-4.5-1.5L42.2,5c-1,.4-2.2.4-3.2,0L34.8,3.3C34,2.5,33,2,32,2ZM24.5,18.5c2.5,0,4.5,2,4.5,4.5s-2,4.5-4.5,4.5-4.5-2-4.5-4.5S22,18.5,24.5,18.5ZM21.2,42.7l21.5-21.5,2.8,2.8L24,45.5ZM39.5,35.5c2.5,0,4.5,2,4.5,4.5s-2,4.5-4.5,4.5-4.5-2-4.5-4.5S37,35.5,39.5,35.5Z"/>
  </svg>
);

const ProductDetails = () => {
  const [activeImage, setActiveImage] = useState(Singleproductdata.images[0]);
  const [isWishlisted, setIsWishlisted] = useState(false);
  const [useCarousel, setUseCarousel] = useState(window.innerWidth < 992);
  const [startIndex, setStartIndex] = useState(0);
  const maxThumbnails = 4;
  const visibleImages = Singleproductdata.images.slice(startIndex, startIndex + maxThumbnails);

  const handlePrevThumb = () => {
    if (startIndex > 0) {
      setStartIndex(startIndex - 1);
    }
  };

  const handleNextThumb = () => {
    if (startIndex + maxThumbnails < Singleproductdata.images.length) {
      setStartIndex(startIndex + 1);
    }
  };
  // Attributes State
  const [tshirtType, setTshirtType] = useState('Round Neck');
  const [style, setStyle] = useState('Unisex Round Neck');
  const [material, setMaterial] = useState('100% Cotton 180gsm');
  const [printType, setPrintType] = useState('Full Colour Print');
  const [fabricColour, setFabricColour] = useState('White');
  const [frontPrintSize, setFrontPrintSize] = useState('Pocket');
  const [printLocations, setPrintLocations] = useState(['Front']);

  // Sizing split up
  const [sizeSplit, setSizeSplit] = useState({
    S: 2,
    M: 2,
    L: 3,
    XL: 2,
    XXL: 1
  });

  // Pincode check
  const [pincode, setPincode] = useState('');
  const [pincodeMessage, setPincodeMessage] = useState('');

  // Discount Coupons
  const [couponApplied, setCouponApplied] = useState(false);

  const [uploadedDesign, setUploadedDesign] = useState(null);
  const [uploadedDesignName, setUploadedDesignName] = useState('');

  const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setUploadedDesignName(file.name);
      const reader = new FileReader();
      reader.onloadend = () => {
        setUploadedDesign(reader.result);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleUploadClick = () => {
    document.getElementById('design-file-upload').click();
  };

  // About Product active tab
  const [activeTab, setActiveTab] = useState('description');
  const [showAllOffers, setShowAllOffers] = useState(true);

  useEffect(() => {
    const handleResize = () => {
      setUseCarousel(window.innerWidth < 992);
    };
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  const totalQty = Object.values(sizeSplit).reduce((a, b) => a + b, 0);
  const perPiecePrice = 499;
  const originalPerPiecePrice = 1200;
  const totalPrice = totalQty * perPiecePrice - (couponApplied ? 50 : 0);

  const handleSizeChange = (size, val) => {
    const value = Math.max(0, parseInt(val) || 0);
    setSizeSplit(prev => ({
      ...prev,
      [size]: value
    }));
  };

  const handleAddLocation = (loc) => {
    if (loc && !printLocations.includes(loc)) {
      setPrintLocations([...printLocations, loc]);
    }
  };

  const handleRemoveLocation = (loc) => {
    setPrintLocations(printLocations.filter(item => item !== loc));
  };

  const handleCheckPincode = () => {
    if (!pincode) {
      setPincodeMessage('Please enter a valid pincode.');
      return;
    }
    if (pincode.length === 6) {
      setPincodeMessage('Standard delivery within 3-5 days. Cash on delivery available.');
    } else {
      setPincodeMessage('Delivery status unavailable for this pincode.');
    }
  };

  const thumbnailSettings = {
    dots: false,
    infinite: false,
    slidesToShow: 4,
    slidesToScroll: 1,
    vertical: true,
    verticalSwiping: true,
    arrows: true,
    prevArrow: <PrevArrow />,
    nextArrow: <NextArrow />,
  };

  return (
    <div className='product-details-page-wrapper px-2 px-xl-5 py-4'>
      <Container fluid className="app-main-container px-0 px-xl-4">
        <Row className="justify-content-center m-0 p-0">
          <Col xs={12} className="product-page-container px-0 pt-1 bg-white rounded shadow-sm">
            <Row className="p-0 m-0">
              
              {/* LEFT COLUMN: Image Gallery */}
              <Col xs={12} lg={6} className="p-0 d-flex flex-column border-end">
                <div className="left-sticky-gallery-panel" style={{ padding: useCarousel ? '0' : '15px' }}>
                  
                  {useCarousel ? (
                    // Mobile Carousel
                    <div className="product-image-section-mobile position-relative">
                      <div className="bestseller-badge">Best Seller</div>
                      <Swiper
                        pagination={{ clickable: true }}
                        modules={[Scrollbar, Pagination]}
                        className="mySwiper"
                        slidesPerView={1}
                      >
                        {Singleproductdata.images.map((img, idx) => (
                          <SwiperSlide key={idx}>
                            <div className="product-image-main-mobile">
                              <img
                                src={img}
                                alt={`Product ${idx}`}
                                className="product-image-mobile-item"
                              />
                            </div>
                          </SwiperSlide>
                        ))}
                      </Swiper>
                      
                      <div className="floating-actions-wrapper">
                        <div className="floating-action-btn" onClick={() => setIsWishlisted(!isWishlisted)}>
                          <FaHeart className={isWishlisted ? "text-danger" : "text-secondary"} />
                        </div>
                        <div className="floating-action-btn mt-2">
                          <FaShare className="text-secondary" />
                        </div>
                      </div>
                    </div>
                  ) : (
                    // Desktop Gallery
                    <div className="d-flex gap-3 justify-content-center align-items-stretch flex-grow-1 mb-3" style={{ minHeight: 0 }}>
                      
                      {/* Vertical Thumbnails */}
                      <div className="vertical-thumbnails-container" style={{ width: '80px', flexShrink: 0 }}>
                        {/* Top Arrow Button */}
                        <button
                          className="custom-vertical-arrow top-arrow"
                          onClick={handlePrevThumb}
                          disabled={startIndex === 0}
                        >
                          <FaChevronUp size="12" />
                        </button>

                        {/* Thumbnails List */}
                        <div className="thumbnails-scroll-list">
                          {visibleImages.map((img, index) => (
                            <div key={index} className="vertical-thumbnail-item">
                              <img
                                src={img}
                                alt={`Thumbnail ${startIndex + index + 1}`}
                                className={`thumbnail-img img-fluid ${activeImage === img ? "active-thumbnail" : ""}`}
                                onClick={() => setActiveImage(img)}
                              />
                            </div>
                          ))}
                        </div>

                        {/* Bottom Arrow Button */}
                        <button
                          className="custom-vertical-arrow bottom-arrow"
                          onClick={handleNextThumb}
                          disabled={startIndex + maxThumbnails >= Singleproductdata.images.length}
                        >
                          <FaChevronDown size="12" />
                        </button>
                      </div>

                      {/* Main Big Image */}
                      <div className="main-image-display-container position-relative flex-grow-1 border rounded bg-light p-3">
                        <div className="bestseller-badge">Best Seller</div>
                        
                        <div className="floating-actions-wrapper">
                          <div className="floating-action-btn" onClick={() => setIsWishlisted(!isWishlisted)}>
                            <FaHeart className={isWishlisted ? "text-danger" : "text-secondary"} />
                          </div>
                          <div className="floating-action-btn mt-2">
                            <FaShare className="text-secondary" />
                          </div>
                        </div>

                        <div className="main-image-display d-flex justify-content-center align-items-center" style={{ height: '100%' }}>
                          <img
                            src={activeImage}
                            alt="Main Product"
                            className="img-fluid main-img-zoomable"
                            style={{ maxHeight: "100%", maxWidth: "100%", objectFit: "contain" }}
                          />
                        </div>
                      </div>
                    </div>
                  )}

                  {/* Add to Cart & Buy Now Buttons (Directly below main gallery) */}
                  <div className="action-buttons-wrapper px-2">
                    <div className="d-flex w-100 gap-3">
                      <button className="flex-grow-1 add-to-cart-btn py-3 rounded d-flex align-items-center justify-content-center gap-2 fw-bold">
                        <FaShoppingCart /> ADD TO CART
                      </button>
                      <button className="flex-grow-1 buy-now-btn py-3 rounded d-flex align-items-center justify-content-center gap-2 fw-bold">
                        <FaBolt /> BUY NOW
                      </button>
                    </div>
                  </div>

                </div>
              </Col>

              {/* RIGHT COLUMN: Product Detail Info Panel */}
              <Col lg={6} className="py-3 px-3 product-details-info-section">
                
                {/* Countdown Timer */}
                <CountdownTimer targetDate={Singleproductdata.offerEnds} />

                {/* Product Title */}
                <h1 className="product-title-text text-dark fw-bold mb-2">{Singleproductdata.title}</h1>
                
                {/* Reviews Badges Row */}
                <div className="d-flex align-items-center gap-3 mb-3">
                  <div className="rating-pill d-flex align-items-center gap-1 bg-success text-white px-2 py-1 rounded fw-semibold text-sm">
                    {Singleproductdata.rating.toFixed(1)} <FaStar size="12" />
                  </div>
                  <span className="reviews-summary-text text-secondary text-sm">
                    {Singleproductdata.reviewCount} Reviews
                  </span>
                  <div className="pm-secured-badge d-flex align-items-center">
                    <img src="/printmontsecured.png" alt="PM Secured" style={{ height: "22px", objectFit: "contain" }} />
                  </div>
                </div>

                {/* Pricing Block */}
                <div className="price-display-wrapper mb-2 border-bottom pb-3">
                  <div className="d-flex align-items-baseline gap-2">
                    <span className="price-current fw-bold text-dark fs-2">₹{perPiecePrice}</span>
                    <span className="price-original text-decoration-line-through text-muted fs-6">₹{originalPerPiecePrice}</span>
                    <span className="price-discount text-success fw-bold text-sm">({Singleproductdata.discount} off)</span>
                  </div>
                  <div className="min-order-text text-danger fw-semibold text-sm mt-1">
                    Minimum order {Singleproductdata.minimumOrder}.
                  </div>
                </div>

                {/* Coupon Cards grid */}
                <div className="coupon-cards-grid d-flex gap-3 mb-4 flex-wrap">
                  {Singleproductdata.offers.map((offer, index) => {
                    const badgeColor = index === 0 ? "#0288d1" : "#00a65a";
                    return (
                      <div key={index} className="coupon-offer-card border rounded p-3 d-flex align-items-center gap-3">
                        {/* Scalloped badge */}
                        <div className="flex-shrink-0">
                          <ScallopedPercentIcon color={badgeColor} />
                        </div>
                        {/* Text details */}
                        <div className="flex-grow-1">
                          <div className="d-flex align-items-baseline mb-1">
                            <span className="fw-bold me-1 text-dark" style={{ fontSize: '0.82rem' }}>Get Flat</span>
                            <span className="fw-bold text-success" style={{ fontSize: '0.82rem' }}>5% OFF</span>
                          </div>
                          <div className="text-secondary mb-2" style={{ fontSize: '0.72rem', lineHeight: '1.2' }}>
                            Add items worth ₹1999+ to unlock this offer
                          </div>
                          <div className="d-flex justify-content-between align-items-center text-secondary" style={{ fontSize: '0.7rem' }}>
                            <span>Apply coupon at checkout</span>
                            <span className="fw-bold text-dark">Code: {offer.code}</span>
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>

                {/* Filter Dropdowns Grid */}
                <div className="filter-dropdowns-section mb-4 border-bottom pb-3">
                  <h5 className="section-subtitle-text fw-bold text-dark mb-3">Customization Options</h5>
                  
                  {/* Mobile View: Aligned side-by-side */}
                  <div className="d-block d-lg-none">
                    <div className="customization-row-mobile d-flex align-items-center justify-content-between mb-3">
                      <span className="customization-label-mobile fw-bold text-secondary" style={{ fontSize: '0.82rem', width: '35%' }}>Tshirt Type</span>
                      <select className="form-select custom-dropdown" style={{ width: '65%' }} value={tshirtType} onChange={e => setTshirtType(e.target.value)}>
                        <option>Round Neck</option>
                        <option>Polo Collar</option>
                        <option>V Neck</option>
                      </select>
                    </div>

                    <div className="customization-row-mobile d-flex align-items-center justify-content-between mb-3">
                      <span className="customization-label-mobile fw-bold text-secondary" style={{ fontSize: '0.82rem', width: '35%' }}>Style</span>
                      <select className="form-select custom-dropdown" style={{ width: '65%' }} value={style} onChange={e => setStyle(e.target.value)}>
                        <option>Unisex Round Neck</option>
                        <option>Regular Fit</option>
                        <option>Slim Fit</option>
                      </select>
                    </div>

                    <div className="customization-row-mobile d-flex align-items-center justify-content-between mb-3">
                      <span className="customization-label-mobile fw-bold text-secondary" style={{ fontSize: '0.82rem', width: '35%' }}>Material / Fabric</span>
                      <select className="form-select custom-dropdown" style={{ width: '65%' }} value={material} onChange={e => setMaterial(e.target.value)}>
                        <option>100% Cotton 180gsm</option>
                        <option>Cotton Blend 200gsm</option>
                        <option>Polyester 160gsm</option>
                      </select>
                    </div>

                    <div className="customization-row-mobile d-flex align-items-center justify-content-between mb-3">
                      <span className="customization-label-mobile fw-bold text-secondary" style={{ fontSize: '0.82rem', width: '35%' }}>Print Type</span>
                      <select className="form-select custom-dropdown" style={{ width: '65%' }} value={printType} onChange={e => setPrintType(e.target.value)}>
                        <option>Full Colour Print</option>
                        <option>Screen Print</option>
                        <option>Embroidery</option>
                      </select>
                    </div>

                    <div className="customization-row-mobile d-flex align-items-center justify-content-between mb-3">
                      <span className="customization-label-mobile fw-bold text-secondary" style={{ fontSize: '0.82rem', width: '35%' }}>Fabric Colour</span>
                      <select className="form-select custom-dropdown" style={{ width: '65%' }} value={fabricColour} onChange={e => setFabricColour(e.target.value)}>
                        <option>White</option>
                        <option>Rust Brown</option>
                        <option>Black</option>
                        <option>Navy Blue</option>
                      </select>
                    </div>

                    <div className="customization-row-mobile d-flex align-items-center justify-content-between mb-3">
                      <span className="customization-label-mobile fw-bold text-secondary" style={{ fontSize: '0.82rem', width: '35%' }}>Front Print Size</span>
                      <select className="form-select custom-dropdown" style={{ width: '65%' }} value={frontPrintSize} onChange={e => setFrontPrintSize(e.target.value)}>
                        <option>Pocket</option>
                        <option>A4 Size</option>
                        <option>A3 Size</option>
                      </select>
                    </div>

                    <div className="customization-row-mobile d-flex align-items-start justify-content-between mb-3">
                      <span className="customization-label-mobile fw-bold text-secondary pt-1" style={{ fontSize: '0.82rem', width: '35%' }}>Print Locations</span>
                      <div style={{ width: '65%' }}>
                        <div className="d-flex flex-wrap gap-2 mb-2">
                          {printLocations.map((loc, i) => (
                            <div key={i} className="location-purple-pill d-flex align-items-center gap-2 bg-purple text-white px-3 py-1 rounded-pill text-sm fw-semibold">
                              <span>{loc}</span>
                              <span className="remove-pill-cross" onClick={() => handleRemoveLocation(loc)} style={{ cursor: 'pointer' }}>×</span>
                            </div>
                          ))}
                        </div>
                        <select className="form-select custom-dropdown w-100" onChange={e => { handleAddLocation(e.target.value); e.target.value = ''; }}>
                          <option value="">Add Print Location...</option>
                          <option value="Front">Front</option>
                          <option value="Back">Back</option>
                          <option value="Left Sleeve">Left Sleeve</option>
                          <option value="Right Sleeve">Right Sleeve</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  {/* Desktop View */}
                  <div className="d-none d-lg-block">
                    <Row className="g-3">
                      <Col xs={12} sm={6}>
                        <label className="form-label-text fw-bold text-secondary mb-1">Tshirt Type</label>
                        <select className="form-select custom-dropdown" value={tshirtType} onChange={e => setTshirtType(e.target.value)}>
                          <option>Round Neck</option>
                          <option>Polo Collar</option>
                          <option>V Neck</option>
                        </select>
                      </Col>
                      <Col xs={12} sm={6}>
                        <label className="form-label-text fw-bold text-secondary mb-1">Style</label>
                        <select className="form-select custom-dropdown" value={style} onChange={e => setStyle(e.target.value)}>
                          <option>Unisex Round Neck</option>
                          <option>Regular Fit</option>
                          <option>Slim Fit</option>
                        </select>
                      </Col>
                      <Col xs={12} sm={6}>
                        <label className="form-label-text fw-bold text-secondary mb-1">Material / Fabric</label>
                        <select className="form-select custom-dropdown" value={material} onChange={e => setMaterial(e.target.value)}>
                          <option>100% Cotton 180gsm</option>
                          <option>Cotton Blend 200gsm</option>
                          <option>Polyester 160gsm</option>
                        </select>
                      </Col>
                      <Col xs={12} sm={6}>
                        <label className="form-label-text fw-bold text-secondary mb-1">Print Type</label>
                        <select className="form-select custom-dropdown" value={printType} onChange={e => setPrintType(e.target.value)}>
                          <option>Full Colour Print</option>
                          <option>Screen Print</option>
                          <option>Embroidery</option>
                        </select>
                      </Col>
                      <Col xs={12} sm={6}>
                        <label className="form-label-text fw-bold text-secondary mb-1">Fabric Colour</label>
                        <select className="form-select custom-dropdown" value={fabricColour} onChange={e => setFabricColour(e.target.value)}>
                          <option>White</option>
                          <option>Rust Brown</option>
                          <option>Black</option>
                          <option>Navy Blue</option>
                        </select>
                      </Col>
                      <Col xs={12} sm={6}>
                        <label className="form-label-text fw-bold text-secondary mb-1">Front Print Size</label>
                        <select className="form-select custom-dropdown" value={frontPrintSize} onChange={e => setFrontPrintSize(e.target.value)}>
                          <option>Pocket</option>
                          <option>A4 Size</option>
                          <option>A3 Size</option>
                        </select>
                      </Col>
                      <Col xs={12}>
                        <label className="form-label-text fw-bold text-secondary mb-1">Print Locations</label>
                        <div className="d-flex flex-wrap gap-2 mb-2">
                          {printLocations.map((loc, i) => (
                            <div key={i} className="location-purple-pill d-flex align-items-center gap-2 bg-purple text-white px-3 py-1 rounded-pill text-sm fw-semibold">
                              <span>{loc}</span>
                              <span className="remove-pill-cross" onClick={() => handleRemoveLocation(loc)} style={{ cursor: 'pointer' }}>×</span>
                            </div>
                          ))}
                        </div>
                        <select className="form-select custom-dropdown" onChange={e => { handleAddLocation(e.target.value); e.target.value = ''; }}>
                          <option value="">Add Print Location...</option>
                          <option value="Front">Front</option>
                          <option value="Back">Back</option>
                          <option value="Left Sleeve">Left Sleeve</option>
                          <option value="Right Sleeve">Right Sleeve</option>
                        </select>
                      </Col>
                    </Row>
                  </div>
                </div>

                {/* Sizing & Quantity split up */}
                <div className="sizing-splitup-section mb-4 border-bottom pb-3">
                  <h5 className="section-subtitle-text fw-bold text-dark mb-2">Size split up:</h5>
                  <div className="d-flex gap-2 justify-content-between mb-3 text-center">
                    {Object.keys(sizeSplit).map((size) => (
                      <div key={size} className="size-split-item flex-fill border rounded p-2">
                        <div className="size-label fw-bold mb-2 text-dark">{size}</div>
                        <input
                          type="text"
                          className="form-control text-center size-input-box py-1 px-1"
                          value={sizeSplit[size] === 0 || sizeSplit[size] === '' ? '' : sizeSplit[size]}
                          placeholder="-"
                          onChange={e => {
                            const val = e.target.value;
                            if (val === '' || /^[0-9]*$/.test(val)) {
                              handleSizeChange(size, val);
                            }
                          }}
                        />
                      </div>
                    ))}
                  </div>

                  <div className="quantity-manual-input d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3 mb-2">
                    <div className="d-flex align-items-center gap-3">
                      <span className="fw-bold text-dark">Quantity:</span>
                      <input
                        type="number"
                        className="form-control fw-bold text-center border-primary"
                        style={{ width: '100px' }}
                        value={totalQty}
                        readOnly
                      />
                    </div>
                    <small className="text-secondary text-xs">
                      Choose a quantity between 1 - 3000 for instant ordering. For higher quantities, you will be allowed to request quotations from Sales Team.
                    </small>
                  </div>
                </div>

                {/* Total Price Calculation Summary */}
                <div className="total-price-summary-box bg-light border p-3 rounded mb-4">
                  {/* Mobile Layout */}
                  <div className="d-block d-lg-none">
                    <div className="d-flex justify-content-between mb-1 fw-bold text-secondary text-xs">
                      <span>Per piece</span>
                      <span className="text-dark">₹ {perPiecePrice}.</span>
                    </div>
                    <div className="d-flex justify-content-between mb-1 fw-bold text-secondary text-xs">
                      <span>Quantity</span>
                      <span className="text-dark">{totalQty}.</span>
                    </div>
                    <div className="text-danger fw-semibold mb-2" style={{ fontSize: '0.7rem', lineHeight: '1.2' }}>
                      (plus qyt price per pcs drop design send you)
                    </div>
                    {couponApplied && (
                      <div className="d-flex justify-content-between mb-1 fw-bold text-success text-xs">
                        <span>Coupon Applied</span>
                        <span>- ₹ 50.00</span>
                      </div>
                    )}
                    <hr className="my-2" />
                    <div className="d-flex justify-content-between align-items-center fw-bold text-dark fs-6">
                      <span>Total</span>
                      <span className="text-success fs-5">₹ {totalPrice.toFixed(2)}</span>
                    </div>
                  </div>

                  {/* Desktop Layout */}
                  <div className="d-none d-lg-block">
                    <Row className="align-items-center g-2">
                      <Col xs={4} className="fw-bold text-secondary">Per piece</Col>
                      <Col xs={8} className="fw-bold text-dark text-end">₹ {perPiecePrice}.</Col>

                      <Col xs={4} className="fw-bold text-secondary">Quantity</Col>
                      <Col xs={8} className="text-end">
                        <span className="fw-bold text-dark">{totalQty}.</span>{' '}
                        <span className="text-danger fw-semibold text-xs ms-1">(plus qyt price per pcs drop design send you)</span>
                      </Col>

                      {couponApplied && (
                        <>
                          <Col xs={4} className="fw-bold text-success">Coupon Applied</Col>
                          <Col xs={8} className="fw-bold text-success text-end">- ₹ 50.00</Col>
                        </>
                      )}

                      <Col xs={12} className="border-top my-2"></Col>
                      
                      <Col xs={4} className="fw-bold text-dark fs-5">Total</Col>
                      <Col xs={8} className="fw-bold text-success text-end fs-4">₹ {totalPrice.toFixed(2)}</Col>
                    </Row>
                  </div>
                </div>

                {/* Upload & Create Design buttons */}
                <div className="design-action-buttons d-flex flex-column flex-sm-row gap-3 mb-3">
                  <input
                    type="file"
                    id="design-file-upload"
                    accept="image/*"
                    style={{ display: 'none' }}
                    onChange={handleFileChange}
                  />
                  <button
                    className="btn upload-design-btn flex-fill py-3 d-flex align-items-center justify-content-center gap-2 fw-bold text-white"
                    onClick={handleUploadClick}
                  >
                    <FaUpload /> Upload your design
                  </button>
                  <button className="btn create-design-btn flex-fill py-3 d-flex align-items-center justify-content-center gap-2 fw-bold bg-white text-primary border-primary">
                    <FaEdit /> Create your design
                  </button>
                </div>

                {/* Selected design preview thumbnail */}
                {uploadedDesign && (
                  <div className="uploaded-design-preview-box d-flex align-items-center gap-3 p-2 mb-4 border rounded bg-light" style={{ maxWidth: '300px' }}>
                    <div className="position-relative" style={{ width: '50px', height: '50px', borderRadius: '4px', overflow: 'hidden', border: '1px solid #dee2e6', flexShrink: 0 }}>
                      <img src={uploadedDesign} alt="Uploaded Design" className="w-100 h-100" style={{ objectFit: 'cover' }} />
                    </div>
                    <div className="flex-grow-1 min-w-0">
                      <div className="text-sm fw-semibold text-truncate text-dark" style={{ fontSize: '0.85rem' }}>{uploadedDesignName}</div>
                      <div className="text-xs text-muted" style={{ fontSize: '0.75rem' }}>Design Uploaded</div>
                    </div>
                    <button
                      type="button"
                      className="btn btn-sm btn-outline-danger border-0 p-1"
                      onClick={() => { setUploadedDesign(null); setUploadedDesignName(''); }}
                      title="Remove design"
                    >
                      <span style={{ fontSize: '1.2rem', lineHeight: '1' }}>&times;</span>
                    </button>
                  </div>
                )}

                {/* Check Delivery Date */}
                <div className="check-delivery-box mb-4 border p-3 rounded">
                  <h5 className="fw-bold text-dark mb-2 text-sm d-flex align-items-center gap-2">
                    <FaMapMarkerAlt className="text-muted" /> Check Delivery Date
                  </h5>
                  <div className="input-group">
                    <input
                      type="text"
                      className="form-control"
                      placeholder="Enter City Pincode*"
                      value={pincode}
                      onChange={e => setPincode(e.target.value)}
                    />
                    <button className="btn btn-dark check-pincode-btn px-4" onClick={handleCheckPincode}>Check</button>
                  </div>
                  {pincodeMessage && (
                    <div className="mt-2 text-sm text-primary fw-semibold">{pincodeMessage}</div>
                  )}
                </div>

                {/* Trust Badges */}
                {/* Desktop Trust Badges */}
                <div className="trust-badges-row d-none d-lg-flex justify-content-around align-items-center bg-light border py-3 px-2 rounded mb-4 text-center">
                  <div className="trust-badge-item">
                    <FaSyncAlt size="20" className="text-primary mb-1" />
                    <div className="fw-bold text-xs text-dark">10-Day Return</div>
                  </div>
                  <div className="trust-badge-item">
                    <FaCheckCircle size="20" className="text-success mb-1" />
                    <div className="fw-bold text-xs text-dark">Cash on Delivery</div>
                  </div>
                  <div className="trust-badge-item d-flex flex-column align-items-center">
                    <img src="/Asured.png" alt="PM Assured" style={{ height: "20px", objectFit: "contain", marginBottom: "4px" }} />
                    <div className="fw-bold text-xs text-dark">PM Assured</div>
                  </div>
                </div>

                {/* Mobile Trust Badges List (matches Figma Layout) */}
                <div className="d-block d-lg-none mb-4 bg-white border-top border-bottom py-1">
                  <div className="d-flex align-items-center justify-content-between py-2 border-bottom px-2">
                    <div className="d-flex align-items-center gap-2 text-dark">
                      <FaSyncAlt size="16" className="text-secondary" style={{ transform: 'scaleX(-1)' }} />
                      <span className="fw-semibold" style={{ fontSize: '0.85rem' }}>
                        <span className="text-success fw-bold">FREE Delivery</span> <span className="text-decoration-line-through text-muted" style={{fontSize: '0.8rem'}}>₹40</span> <span className="text-muted">• Delivery by 27 Jul, Saturday</span>
                      </span>
                    </div>
                    <FaChevronRight size={12} className="text-secondary" />
                  </div>
                  <div className="d-flex align-items-center justify-content-between py-2 border-bottom px-2">
                    <div className="d-flex align-items-center gap-2 text-dark">
                      <FaSyncAlt size="16" className="text-secondary" />
                      <span className="fw-semibold" style={{ fontSize: '0.85rem' }}>10 Days Return Policy</span>
                    </div>
                    <FaChevronRight size={12} className="text-secondary" />
                  </div>
                  <div className="d-flex align-items-center justify-content-between py-2 px-2">
                    <div className="d-flex align-items-center gap-2 text-dark">
                      <FaCheckCircle size="16" className="text-success" />
                      <span className="fw-semibold" style={{ fontSize: '0.85rem' }}>Cash on Delivery Available</span>
                    </div>
                    <FaChevronRight size={12} className="text-secondary" />
                  </div>
                </div>

                {/* Coupon discount section */}
                <div className="coupon-discount-apply border p-3 rounded d-flex justify-content-between align-items-center mb-4 bg-light-green">
                  <div>
                    <span className="fw-bold text-dark text-sm">Discount Coupons</span>
                    <span className="text-success fw-bold text-sm ms-2">Save ₹50</span>
                  </div>
                  <button
                    className={`btn text-sm fw-bold px-3 py-1 border rounded ${couponApplied ? 'btn-success text-white' : 'bg-white text-success border-success'}`}
                    onClick={() => setCouponApplied(!couponApplied)}
                  >
                    {couponApplied ? 'Applied ✓' : 'Apply'}
                  </button>
                </div>

                {/* Printmont Coin Banner */}
                <div className="printmont-coin-banner my-3 border-top border-bottom py-2">
                  <img
                    src="/printmont%20cart%20coins.png"
                    alt="Printmont Coin"
                    className="img-fluid w-100"
                    style={{ maxHeight: "65px", objectFit: "contain" }}
                  />
                </div>

                {/* All Offers & Coupons Header */}
                <div 
                  className="all-offers-header-row py-2 d-flex align-items-center justify-content-between border-top border-bottom cursor-pointer mb-3"
                  onClick={() => setShowAllOffers(!showAllOffers)}
                  style={{ userSelect: 'none' }}
                >
                  <div className="d-flex align-items-center gap-2">
                    <div className="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style={{ width: '20px', height: '20px', fontSize: '11px' }}>
                      %
                    </div>
                    <span className="fw-semibold text-dark" style={{ fontSize: '0.9rem' }}>All Offers & Coupons</span>
                  </div>
                  <FaChevronRight 
                    style={{ 
                      transition: 'transform 0.2s ease', 
                      transform: showAllOffers ? 'rotate(90deg)' : 'rotate(0deg)',
                      color: '#6c757d',
                      fontSize: '0.85rem'
                    }} 
                  />
                </div>

                {/* Offers List (Collapsible Accordion Content) */}
                {showAllOffers && (
                  <div className="all-offers-list-container d-flex flex-column gap-3 mb-4">
                    {/* Paytm Offer */}
                    <div className="offer-wallet-card d-flex align-items-center gap-3 p-3 rounded" style={{ backgroundColor: '#f2f7fe', border: '1px solid #d0e1fd' }}>
                      <div className="offer-wallet-logo d-flex align-items-center justify-content-center bg-white rounded border" style={{ width: '90px', height: '45px', flexShrink: 0 }}>
                        <span style={{ color: '#002c8c', fontWeight: '900', fontSize: '1rem', fontStyle: 'italic', letterSpacing: '-0.5px' }}>pay<span style={{ color: '#00baf2' }}>tm</span></span>
                      </div>
                      <div className="flex-grow-1 text-dark" style={{ fontSize: '0.75rem', fontWeight: '500', lineHeight: '1.3' }}>
                        Get Cashback up to Rs.300 on a minimum transaction of Rs.799
                      </div>
                      <div className="info-icon-circle" title="Terms & Conditions">i</div>
                    </div>

                    {/* Amazon Pay Offer */}
                    <div className="offer-wallet-card d-flex align-items-center gap-3 p-3 rounded" style={{ backgroundColor: '#faf2e8', border: '1px solid #f5e7d3' }}>
                      <div className="offer-wallet-logo d-flex align-items-center justify-content-center bg-white rounded border" style={{ width: '90px', height: '45px', flexShrink: 0 }}>
                        <span style={{ color: '#111', fontWeight: '800', fontSize: '0.8rem', letterSpacing: '-0.3px' }}>amazon<span style={{ color: '#ff9900', fontWeight: '500' }}>pay</span></span>
                      </div>
                      <div className="flex-grow-1 text-dark" style={{ fontSize: '0.75rem', fontWeight: '500', lineHeight: '1.3' }}>
                        Get up to Rs.200 cashback on 2 Amazon Pay Balance orders (minimum order value Rs.150).
                      </div>
                      <div className="info-icon-circle" title="Terms & Conditions">i</div>
                    </div>

                    {/* Airtel Bank Offer */}
                    <div className="offer-wallet-card d-flex align-items-center gap-3 p-3 rounded" style={{ backgroundColor: '#fef5f5', border: '1px solid #fbd7d7' }}>
                      <div className="offer-wallet-logo d-flex flex-column align-items-center justify-content-center bg-white rounded border" style={{ width: '90px', height: '45px', flexShrink: 0, padding: '2px' }}>
                        <span style={{ color: '#e11900', fontWeight: '900', fontSize: '0.85rem', letterSpacing: '-0.3px', lineHeight: '1' }}>airtel</span>
                        <span style={{ color: '#888', fontWeight: '400', fontSize: '0.55rem', letterSpacing: '-0.1px', lineHeight: '1', display: 'block', textTransform: 'uppercase' }}>payments bank</span>
                      </div>
                      <div className="flex-grow-1 text-dark" style={{ fontSize: '0.75rem', fontWeight: '500', lineHeight: '1.3' }}>
                        Flat 10% off up to Rs.200 on a minimum transaction of Rs.999
                      </div>
                      <div className="info-icon-circle" title="Terms & Conditions">i</div>
                    </div>

                    {/* MobiKwik Offer */}
                    <div className="offer-wallet-card d-flex align-items-center gap-3 p-3 rounded" style={{ backgroundColor: '#e9eef8', border: '1px solid #d4dbed' }}>
                      <div className="offer-wallet-logo d-flex align-items-center justify-content-center bg-white rounded border" style={{ width: '90px', height: '45px', flexShrink: 0 }}>
                        <span style={{ color: '#00539f', fontWeight: '800', fontSize: '0.85rem', fontStyle: 'italic', letterSpacing: '-0.2px' }}>MobiKwik</span>
                      </div>
                      <div className="flex-grow-1 text-dark" style={{ fontSize: '0.75rem', fontWeight: '500', lineHeight: '1.3' }}>
                        Get up to Rs.300 cashback on transactions using MobiKwik UPI (@ikwik)/Wallet.
                      </div>
                      <div className="info-icon-circle" title="Terms & Conditions">i</div>
                    </div>
                  </div>
                )}

              </Col>

            </Row>
          </Col>
        </Row>

        {/* SECTION 4: About the Product (Tabs) */}
        <Row className="p-0 mx-0 my-4 w-100 bg-white rounded border p-3 shadow-sm about-product-section-row">
          <Col xs={12} className="p-0">
            <h4 className="fw-bold text-dark mb-3 px-1" style={{ fontSize: '1.2rem' }}>About the product</h4>
            
            {/* Tabs Navigation */}
            <div className="about-product-tabs-row d-flex border-bottom pb-2 mb-3 gap-3">
              <button
                className={`tab-btn btn fw-bold border-0 px-4 py-2 ${activeTab === 'description' ? 'active-product-tab text-primary border-bottom-2' : 'text-secondary'}`}
                onClick={() => setActiveTab('description')}
              >
                Description
              </button>
              <button
                className={`tab-btn btn fw-bold border-0 px-4 py-2 ${activeTab === 'instructions' ? 'active-product-tab text-primary border-bottom-2' : 'text-secondary'}`}
                onClick={() => setActiveTab('instructions')}
              >
                Instructions
              </button>
              <button
                className={`tab-btn btn fw-bold border-0 px-4 py-2 ${activeTab === 'delivery' ? 'active-product-tab text-primary border-bottom-2' : 'text-secondary'}`}
                onClick={() => setActiveTab('delivery')}
              >
                Delivery Info
              </button>
            </div>

            {/* Tabs Content */}
            <div className="about-product-tab-content py-2 px-1">
              {activeTab === 'description' && (
                <div>
                  <p className="text-dark leading-relaxed text-sm mb-3">
                    Celebrate love and cherished moments with this personalised photo frame, tailored with special images, the couple's names, and a significant date. Perfect for weddings, anniversaries, or any milestone, it's a heartfelt way to honour their journey together. This frame beautifully preserves their memories, adding sentimental value to their home. A meaningful gift, it's ideal for couples who wish to relive their love story each day.
                  </p>
                  <h6 className="fw-bold text-dark mb-2 text-sm">Product Details:</h6>
                  <ul className="text-muted text-sm ps-3" style={{ listStyleType: 'disc' }}>
                    <li>Personalised photo frame: 1</li>
                    <li>Material: MDF and wooden</li>
                    <li>Size: 17.78 × 18.29 cms</li>
                    <li>Frame stand: 17.78 × 7.62 cms</li>
                    <li>For personalisation please provide us with 7 images & the couple's name & date (DD MM YYYY)</li>
                    <li>Net quantity: 1 Unit</li>
                    <li>Country of origin: India</li>
                  </ul>
                </div>
              )}
              {activeTab === 'instructions' && (
                <div className="text-sm text-dark">
                  <h6 className="fw-bold text-dark mb-2">Wash & Care Instructions:</h6>
                  <ul className="text-muted ps-3" style={{ listStyleType: 'disc' }}>
                    <li>Machine wash cold inside out with like colors.</li>
                    <li>Use only non-chlorine bleach when needed.</li>
                    <li>Tumble dry low. Do not iron directly on print.</li>
                    <li>Do not dry clean.</li>
                  </ul>
                </div>
              )}
              {activeTab === 'delivery' && (
                <div className="text-sm text-dark">
                  <h6 className="fw-bold text-dark mb-2">Delivery Information:</h6>
                  <p className="text-muted mb-2">All customized products take 2-3 business days in production before shipping.</p>
                  <ul className="text-muted ps-3" style={{ listStyleType: 'disc' }}>
                    <li>Metros: Delivery in 3-5 working days.</li>
                    <li>Other Cities: Delivery in 5-7 working days.</li>
                    <li>Tracking details will be shared via Email and SMS as soon as the package is dispatched.</li>
                  </ul>
                </div>
              )}
            </div>

            {/* Alternating Brand Banners */}
            <div className="alternating-brand-banners-container mt-4 pt-4 border-top">
              
              {/* Banner 1: Image Left, Text Right */}
              <div className="brand-banner-alternating-row d-flex flex-column flex-md-row align-items-center gap-4 mb-4 bg-light rounded overflow-hidden">
                <div className="banner-image-box flex-fill w-100" style={{ maxHeight: '300px', overflow: 'hidden' }}>
                  <img
                    src="/men_shirt/men-shirt-8.jpeg"
                    alt="Lifestyle model"
                    className="w-100 h-100 object-fit-cover"
                    style={{ objectFit: 'cover', minHeight: '260px' }}
                  />
                </div>
                <div className="banner-text-box flex-fill p-4">
                  <h4 className="fw-bold text-dark mb-3">The ultimate everyday essential</h4>
                  <p className="text-muted leading-relaxed text-sm">
                    The Pace collection of tees offer unmatched comfort and is perfect for lounging at home or running errands. Designed with high-quality breathable fabric that keeps you cool and stylish all day long.
                  </p>
                </div>
              </div>

              {/* Banner 2: Text Left, Image Right */}
              <div className="brand-banner-alternating-row d-flex flex-column flex-md-row-reverse align-items-center gap-4 bg-light rounded overflow-hidden">
                <div className="banner-image-box flex-fill w-100" style={{ maxHeight: '300px', overflow: 'hidden' }}>
                  <img
                    src="/girl-product-img/subsubcat-104.jpeg"
                    alt="Lifestyle model"
                    className="w-100 h-100 object-fit-cover"
                    style={{ objectFit: 'cover', minHeight: '260px' }}
                  />
                </div>
                <div className="banner-text-box flex-fill p-4">
                  <h4 className="fw-bold text-dark mb-3">Crafted for style and comfort</h4>
                  <p className="text-muted leading-relaxed text-sm">
                    Express your unique style with premium quality knitted polos. Tailor-made with precise printing and high density embroidery patterns to withstand multiple wash cycles and maintain vibrance.
                  </p>
                </div>
              </div>

            </div>

          </Col>
        </Row>

        {/* SECTION 6: Ratings & Reviews + Q&A Section */}
        <Row className="p-0 mx-0 my-4 w-100 g-4">
          {/* Ratings & Reviews Column */}
          <Col xs={12} md={7} className="d-flex flex-column p-0 px-md-3">
            {/* Desktop wrapper */}
            <div className="d-none d-md-block bg-white border rounded p-3 shadow-sm h-100">
              <h4 className="fw-bold text-dark mb-3">Ratings & Reviews</h4>
              <ProductReview />
            </div>
            {/* Mobile wrapper (direct) */}
            <div className="d-block d-md-none">
              <ProductReview />
            </div>
          </Col>

          {/* Q&A Column */}
          <Col xs={12} md={5} className="d-flex flex-column p-0 px-md-3">
            {/* Desktop wrapper */}
            <div className="d-none d-md-block bg-white border rounded p-3 shadow-sm h-100">
              <ProductQASection />
            </div>
            {/* Mobile wrapper (direct) */}
            <div className="d-block d-md-none">
              <ProductQASection />
            </div>
          </Col>
        </Row>

        {/* SECTION 7: Small Promo Banner */}
        <Row className="p-0 mx-0 my-4 w-100">
          <Col xs={12}>
            <div className="special-promotional-slider border rounded p-3 bg-light-red text-center">
              <h5 className="mb-0 text-danger fw-bold">Small size Banner and slider</h5>
              <div className="sliding-promo-text text-sm text-dark mt-1 fw-semibold">
                ★ Promo: Flat 20% off on your first order. Use Code: FIRST20 ★ Free Shipping on orders above ₹999 ★
              </div>
            </div>
          </Col>
        </Row>

        {/* SECTIONS 8-11: Product Carousels */}
        <Row className='p-0 mx-0 my-3 w-100'>
          <SecondCarousel products={discount} title="Similar Products" />
        </Row>

        <Row className='p-0 mx-0 my-3 w-100'>
          <SecondCarousel products={discount} title="Discount on Similar Products" />
        </Row>

        <Row className='p-0 mx-0 my-3 w-100'>
          <SingleProduct products={girloutfit} title="You Might Be Interested" />
        </Row>

        <Row className='p-0 mx-0 my-3 w-100'>
          <SingleProduct products={girloutfit} title="Your Recently Viewed" />
        </Row>

      </Container>
    </div>
  );
};

export default ProductDetails;
