import React, { useState, useEffect, useRef } from 'react';
import { Container, Row, Col, Card, Modal, Form, Spinner, Alert } from 'react-bootstrap';
import { useParams, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import { useCheckout } from '../../context/CheckoutContext';
import PageNotFound from '../pageNotFound/PageNotFound';
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
  FaQuestionCircle,
  FaTrash
} from 'react-icons/fa';

import SliderReact from 'react-slick';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Scrollbar, Pagination } from 'swiper/modules';
import FrequentlyBoughtTogether from './FrequentlyBoughtTogether';
import ProductReview from './ProductReview';
import ProductQASection from './ProductQASection';
import { ProductCarousel } from '../home';
import { discount, girloutfit } from '../../../data/data';
import { Singleproductdata } from '../../../data/reviewData';
import TabCarousel from '../pages/carousel/TabCarousel';

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
  const navigate = useNavigate();
  const checkoutContext = useCheckout();
  const addToCart = checkoutContext?.addToCart;
  const removeItem = checkoutContext?.removeItem;
  const cartItems = checkoutContext?.cartItems || [];

  const { productSlug } = useParams();
  const idMatch = productSlug ? productSlug.match(/-p(\d+)$/) : null;
  const productId = idMatch ? idMatch[1] : (productSlug && /^\d+$/.test(productSlug) ? productSlug : null);

  const [productData, setProductData] = useState(Singleproductdata);
  const currentProductId = productData?.id || productId || 101;
  const isInCart = cartItems.some(item => String(item.id) === String(currentProductId));
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const [activeImage, setActiveImage] = useState(Singleproductdata.images[0]);
  const [isWishlisted, setIsWishlisted] = useState(false);
  const [useCarousel, setUseCarousel] = useState(window.innerWidth < 992);
  const [startIndex, setStartIndex] = useState(0);
  const maxThumbnails = 4;

  // Cart Success Modal State
  const [showCartSuccessModal, setShowCartSuccessModal] = useState(false);
  const [addedCartDetails, setAddedCartDetails] = useState(null);

  // Amazon/Flipkart Image Hover Zoom Lens States
  const [isZooming, setIsZooming] = useState(false);
  const [lensPos, setLensPos] = useState({ left: 0, top: 0 });
  const [zoomBgPos, setZoomBgPos] = useState({ x: 0, y: 0 });
  const mainImageContainerRef = useRef(null);

  const handleMouseEnterZoom = () => {
    if (window.innerWidth >= 992) setIsZooming(true);
  };

  const handleMouseLeaveZoom = () => {
    setIsZooming(false);
  };

  const handleMouseMoveZoom = (e) => {
    if (!mainImageContainerRef.current || window.innerWidth < 992) return;
    const rect = mainImageContainerRef.current.getBoundingClientRect();
    const lensWidth = 140;
    const lensHeight = 140;
    const zoomRatio = 3;
    const prevWidth = 520;
    const prevHeight = 520;

    let lensX = e.clientX - rect.left - lensWidth / 2;
    let lensY = e.clientY - rect.top - lensHeight / 2;

    // Boundaries
    if (lensX < 0) lensX = 0;
    if (lensY < 0) lensY = 0;
    if (lensX > rect.width - lensWidth) lensX = rect.width - lensWidth;
    if (lensY > rect.height - lensHeight) lensY = rect.height - lensHeight;

    setLensPos({ left: lensX, top: lensY });

    // Exact pinpoint center of lens
    const centerX = lensX + lensWidth / 2;
    const centerY = lensY + lensHeight / 2;

    // Position background so (centerX, centerY) aligns exactly with center of 520x520 preview window
    const bgX = (prevWidth / 2) - (centerX * zoomRatio);
    const bgY = (prevHeight / 2) - (centerY * zoomRatio);
    const bgWidth = rect.width * zoomRatio;
    const bgHeight = rect.height * zoomRatio;

    setZoomBgPos({ x: bgX, y: bgY, width: bgWidth, height: bgHeight });
  };
  
  useEffect(() => {
    if (!productId) {
      setLoading(false);
      return;
    }

    const fetchProduct = async () => {
      try {
        setLoading(true);
        const res = await fetch(API_ENDPOINTS.PRODUCT_BY_ID(productId));
        if (!res.ok) throw new Error("Failed to load product");
        const data = await res.json();
        
        let p = data;
        if (data && data.success && data.data) p = data.data;
        else if (data && data.success && data.product) p = data.product;
        else if (Array.isArray(data) && data.length > 0) p = data[0];

        let images = [];
        if (Array.isArray(p.images) && p.images.length > 0) {
          images = p.images
            .map(img => {
              if (typeof img === 'string') return img;
              return img.image_url || img.image || img.image_path || img.img_url || '';
            })
            .filter(Boolean);
        } else if (p.img) {
          images = [p.img];
        } else if (p.image_url) {
          images = [p.image_url];
        } else if (p.image) {
          images = [p.image];
        }

        if (images.length === 0) {
          images = ['https://placehold.co/400x550/cccccc/000?text=No+Image'];
        }

        const price = parseFloat(p.price) || 1200;
        const discountPrice = parseFloat(p.discount_price) || price;
        const discountPercent = price > 0 && discountPrice < price 
            ? Math.round(((price - discountPrice) / price) * 100) 
            : 0;

        const formatted = {
          ...Singleproductdata,
          id: p.id,
          title: p.name || p.title || 'Unknown Product',
          images: images,
          originalPrice: price,
          perPiecePrice: discountPrice > 0 ? discountPrice : price,
          discount: discountPercent > 0 ? `${discountPercent}% off` : 'Best Price',
          description: p.description || Singleproductdata.description,
          long_description: p.long_description || p.description || Singleproductdata.description,
          instructions: p.instructions,
          delivery_info: p.delivery_info,
          category_name: p.category_name || p.main_category_name || '',
          brand: p.brand || '',
          sku: p.sku || '',
          stock_quantity: p.stock_quantity ?? 50,
          cod_status: p.cod_status || 'yes',
          cancel_status: p.cancel_status || 'yes',
          cancel_type: p.cancel_type || 'hour',
          cancel_value: p.cancel_value || 24,
          shipping_method_status: p.shipping_method_status || false,
          local_shipping_price: p.local_shipping_price || 0,
          regional_shipping_price: p.regional_shipping_price || 0,
          national_shipping_price: p.national_shipping_price || 0,
          regional_shipping_msg: p.regional_shipping_msg || '',
          national_shipping_msg: p.national_shipping_msg || '',
          sizes: Array.isArray(p.sizes) ? p.sizes : [],
          colors: Array.isArray(p.colors) ? p.colors : [],
          materials: Array.isArray(p.materials) ? p.materials : [],
          customization_data: Array.isArray(p.customization_data) ? p.customization_data : [],
          customization_label_status: p.customization_label_status || false,
          addon_product_ids: Array.isArray(p.addon_product_ids) ? p.addon_product_ids : [],
          meta_title: p.meta_title || '',
          meta_description: p.meta_description || '',
          meta_keywords: p.meta_keywords || '',
        };

        setProductData(formatted);
        setActiveImage(images[0]);

        // Dynamic SEO Update
        document.title = formatted.meta_title || `${formatted.title} - Printmont`;
        
        let metaDesc = document.querySelector('meta[name="description"]');
        if (!metaDesc) {
          metaDesc = document.createElement('meta');
          metaDesc.setAttribute('name', 'description');
          document.head.appendChild(metaDesc);
        }
        metaDesc.setAttribute('content', formatted.meta_description || `Buy ${formatted.title} at Printmont.`);

        let metaKeywords = document.querySelector('meta[name="keywords"]');
        if (!metaKeywords) {
          metaKeywords = document.createElement('meta');
          metaKeywords.setAttribute('name', 'keywords');
          document.head.appendChild(metaKeywords);
        }
        metaKeywords.setAttribute('content', formatted.meta_keywords || '');

      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };
    fetchProduct();
  }, [productId]);

  const visibleImages = productData.images.slice(startIndex, startIndex + maxThumbnails);

  const handlePrevThumb = () => {
    if (startIndex > 0) {
      setStartIndex(startIndex - 1);
    }
  };

  const handleNextThumb = () => {
    if (startIndex + maxThumbnails < productData.images.length) {
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

  // Category identification helpers
  const categoryName = (productData.category_name || '').toLowerCase();
  const isClothing = /cloth|shirt|pant|jeans|apparel|fashion|wear|t-shirt|polo/i.test(categoryName) || (productData.sizes && productData.sizes.length > 0);
  const isElectronics = /electronic|mobile|laptop|earbuds|headphone|gadget|phone/i.test(categoryName);

  // Single Quantity state for electronics / standard products
  const [quantity, setQuantity] = useState(1);

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

  // Customize Product Modal States
  const [customizeModalOpen, setCustomizeModalOpen] = useState(false);
  const [customActiveTab, setCustomActiveTab] = useState('upload'); // 'upload' | 'text'
  const [uploadedDesigns, setUploadedDesigns] = useState(Array(11).fill(null));
  const [customTexts, setCustomTexts] = useState(['', '', '', '']);
  const [activeSlotIndex, setActiveSlotIndex] = useState(0);

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

  const handleSlotFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onloadend = () => {
        setUploadedDesigns(prev => {
          const updated = [...prev];
          updated[activeSlotIndex] = reader.result;
          return updated;
        });
      };
      reader.readAsDataURL(file);
    }
  };

  const triggerSlotUpload = (index) => {
    setActiveSlotIndex(index);
    // Use setTimeout to ensure state is set before triggering click
    setTimeout(() => {
      const fileInput = document.getElementById('custom-slot-file-input');
      if (fileInput) {
        fileInput.value = '';
        fileInput.click();
      }
    }, 50);
  };

  const removeSlotDesign = (index) => {
    setUploadedDesigns(prev => {
      const updated = [...prev];
      updated[index] = null;
      return updated;
    });
  };

  const handleTextChange = (index, value) => {
    setCustomTexts(prev => {
      const updated = [...prev];
      updated[index] = value;
      return updated;
    });
  };

  const handleCustomizeSave = () => {
    const firstUploaded = uploadedDesigns.find(d => d !== null);
    if (firstUploaded) {
      setUploadedDesign(firstUploaded);
      setUploadedDesignName('custom_design.png');
    } else {
      const textEntered = customTexts.some(t => t.trim() !== '');
      if (textEntered) {
        setUploadedDesign('/printmontsecured.png'); // fallback thumbnail to indicate customization text is saved
        setUploadedDesignName('Custom Text Added');
      } else {
        setUploadedDesign(null);
        setUploadedDesignName('');
      }
    }
    setCustomizeModalOpen(false);
  };

  const handleUploadClick = () => {
    setCustomizeModalOpen(true);
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
  const perPiecePrice = productData.perPiecePrice || 499;
  const originalPerPiecePrice = productData.originalPrice || 1200;
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

  if (loading) {
    return (
      <Container className="d-flex justify-content-center align-items-center" style={{ minHeight: '60vh' }}>
        <Spinner animation="border" variant="primary" />
      </Container>
    );
  }

  // If there is no valid product ID in the URL, show the global 404 page
  if (!productId) {
    return <PageNotFound />;
  }

  if (error) {
    return (
      <Container className="py-5">
        <Alert variant="danger">{error}</Alert>
      </Container>
    );
  }

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
                        {productData.images.map((img, idx) => (
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
                          disabled={startIndex + maxThumbnails >= productData.images.length}
                        >
                          <FaChevronDown size="12" />
                        </button>
                      </div>

                      {/* Main Big Image */}
                      <div 
                        ref={mainImageContainerRef}
                        className="main-image-display-container position-relative flex-grow-1 border rounded bg-light p-1"
                        onMouseEnter={handleMouseEnterZoom}
                        onMouseLeave={handleMouseLeaveZoom}
                        onMouseMove={handleMouseMoveZoom}
                        style={{ cursor: isZooming ? 'crosshair' : 'pointer' }}
                      >
                        <div className="bestseller-badge">Best Seller</div>
                        
                        <div className="floating-actions-wrapper">
                          <div className="floating-action-btn" onClick={() => setIsWishlisted(!isWishlisted)}>
                            <FaHeart className={isWishlisted ? "text-danger" : "text-secondary"} />
                          </div>
                          <div className="floating-action-btn mt-2">
                            <FaShare className="text-secondary" />
                          </div>
                        </div>

                        {/* Zoom Lens Overlay */}
                        {isZooming && (
                          <div 
                            className="zoom-lens-overlay" 
                            style={{ 
                              left: `${lensPos.left}px`, 
                              top: `${lensPos.top}px`, 
                              width: '140px', 
                              height: '140px' 
                            }} 
                          />
                        )}

                        {/* Zoom Result Window */}
                        {isZooming && (
                          <div 
                            className="zoom-result-window" 
                            style={{ 
                              backgroundImage: `url("${activeImage}")`,
                              backgroundPosition: `${zoomBgPos.x}px ${zoomBgPos.y}px`,
                              backgroundSize: zoomBgPos.width ? `${zoomBgPos.width}px ${zoomBgPos.height}px` : '300% 300%'
                            }} 
                          />
                        )}

                        <div className="main-image-display d-flex justify-content-center align-items-center w-100 h-100">
                          <img
                            src={activeImage}
                            alt="Main Product"
                            className="img-fluid main-img-zoomable"
                            style={{ width: "100%", height: "100%", objectFit: "contain" }}
                          />
                        </div>
                      </div>
                    </div>
                  )}

                  {/* Add to Cart & Buy Now Buttons (Directly below main gallery) */}
                  <div className="action-buttons-wrapper px-2">
                    <div className="d-flex w-100 gap-2 align-items-stretch">
                      {isInCart ? (
                        <button 
                          className="w-50 remove-from-cart-btn rounded d-flex align-items-center justify-content-center gap-2 fw-bold text-white bg-danger border-0 shadow-sm"
                          style={{
                            height: '46px',
                            fontSize: '12.5px',
                            letterSpacing: '0.2px',
                            flex: '1 1 50%',
                            whiteSpace: 'nowrap'
                          }}
                          onClick={() => {
                            if (removeItem) {
                              removeItem(currentProductId);
                              toast.error('Item removed from cart!', {
                                duration: 2000,
                                position: 'top-center'
                              });
                            }
                          }}
                        >
                          <FaTrash size={13} /> REMOVE FROM CART
                        </button>
                      ) : (
                        <button 
                          className="w-50 add-to-cart-btn rounded d-flex align-items-center justify-content-center gap-2 fw-bold shadow-sm"
                          style={{
                            height: '46px',
                            fontSize: '12.5px',
                            letterSpacing: '0.2px',
                            flex: '1 1 50%',
                            whiteSpace: 'nowrap'
                          }}
                          onClick={() => {
                            const qty = isClothing ? (totalQty > 0 ? totalQty : 1) : quantity;
                            if (addToCart) {
                              addToCart(productData, qty, {
                                tshirtType,
                                style,
                                material,
                                fabricColour,
                                uploadedDesign
                              });
                              setAddedCartDetails({
                                title: productData.title,
                                price: perPiecePrice,
                                qty: qty,
                                image: activeImage
                              });
                              toast.success('Item added to cart successfully!', {
                                duration: 2500,
                                position: 'top-center'
                              });
                              setShowCartSuccessModal(true);
                            } else {
                              navigate('/cart');
                            }
                          }}
                        >
                          <FaShoppingCart size={13} /> ADD TO CART
                        </button>
                      )}
                      <button 
                        className="w-50 buy-now-btn rounded d-flex align-items-center justify-content-center gap-2 fw-bold shadow-sm"
                        style={{
                          height: '46px',
                          fontSize: '12.5px',
                          letterSpacing: '0.2px',
                          flex: '1 1 50%',
                          whiteSpace: 'nowrap'
                        }}
                        onClick={() => {
                          const qty = isClothing ? (totalQty > 0 ? totalQty : 1) : quantity;
                          if (addToCart) {
                            addToCart(productData, qty, {
                              tshirtType,
                              style,
                              material,
                              fabricColour,
                              uploadedDesign
                            });
                          }
                          navigate('/cart');
                        }}
                      >
                        <FaBolt size={13} /> BUY NOW
                      </button>
                    </div>
                  </div>

                </div>
              </Col>

              {/* RIGHT COLUMN: Product Detail Info Panel */}
              <Col lg={6} className="py-3 px-3 product-details-info-section">
                
                {/* Countdown Timer */}
                <CountdownTimer targetDate={productData.offerEnds} />

                {/* Product Title */}
                <h1 className="product-title-text text-dark fw-bold mb-2">{productData.title}</h1>
                
                {/* Reviews Badges Row */}
                <div className="d-flex align-items-center gap-3 mb-3">
                  <div className="rating-pill d-flex align-items-center gap-1 bg-success text-white px-2 py-1 rounded fw-semibold text-sm">
                    {productData.rating.toFixed(1)} <FaStar size="12" />
                  </div>
                  <span className="reviews-summary-text text-secondary text-sm">
                    {productData.reviewCount} Reviews
                  </span>
                  <div className="pm-secured-badge d-flex align-items-center ms-1">
                    <img src="/printmontsecured.png" alt="PM Secured" style={{ height: "38px", objectFit: "contain" }} />
                  </div>
                </div>

                {/* Pricing Block */}
                <div className="price-display-wrapper mb-2 border-bottom pb-3">
                  <div className="d-flex align-items-baseline gap-2">
                    <span className="price-current fw-bold text-dark fs-2">₹{perPiecePrice}</span>
                    <span className="price-original text-decoration-line-through text-muted fs-6">₹{originalPerPiecePrice}</span>
                    <span className="price-discount text-success fw-bold text-sm">({productData.discount})</span>
                  </div>
                  <div className="min-order-text text-danger fw-semibold text-sm mt-1">
                    Minimum order {productData.minimumOrder}.
                  </div>
                </div>

                {/* Coupon Cards grid */}
                <div className="coupon-cards-grid d-flex gap-3 mb-4 flex-wrap">
                  {productData.offers.map((offer, index) => {
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

                {/* Category-Specific Specifications & Customization Block */}
                {isElectronics ? (
                  /* Technical Specifications Table for Electronics */
                  <div className="tech-specs-section mb-4 border-bottom pb-3">
                    <h5 className="section-subtitle-text fw-bold text-dark mb-3">Technical Specifications</h5>
                    <table className="tech-specs-table">
                      <tbody>
                        <tr>
                          <td className="spec-label">Brand</td>
                          <td className="spec-value text-capitalize">{productData.brand || 'Premium Brand'}</td>
                        </tr>
                        <tr>
                          <td className="spec-label">Model / SKU</td>
                          <td className="spec-value">{productData.sku || 'N/A'}</td>
                        </tr>
                        <tr>
                          <td className="spec-label">Category</td>
                          <td className="spec-value">{productData.category_name || 'Electronics'}</td>
                        </tr>
                        <tr>
                          <td className="spec-label">Stock Availability</td>
                          <td className="spec-value text-success fw-bold">
                            {productData.stock_quantity > 0 ? `In Stock (${productData.stock_quantity} units available)` : 'Out of Stock'}
                          </td>
                        </tr>
                        <tr>
                          <td className="spec-label">Cancellation Policy</td>
                          <td className="spec-value">
                            {productData.cancel_status === 'yes' 
                              ? `Free cancellation within ${productData.cancel_value} ${productData.cancel_type === 'hour' ? 'hours' : 'days'} of order placement.` 
                              : 'Non-cancellable order.'}
                          </td>
                        </tr>
                        <tr>
                          <td className="spec-label">Cash on Delivery</td>
                          <td className="spec-value text-capitalize">
                            {productData.cod_status === 'yes' ? 'Available' : 'Not Available'}
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    {/* Quantity Picker for Electronics */}
                    <div className="quantity-manual-input d-flex align-items-center gap-3 mt-4">
                      <span className="fw-bold text-dark">Quantity:</span>
                      <div className="d-flex align-items-center border rounded">
                        <button 
                          className="btn btn-sm btn-light border-0 fw-bold px-3 py-1"
                          onClick={() => setQuantity(Math.max(1, quantity - 1))}
                        >-</button>
                        <span className="fw-bold px-3">{quantity}</span>
                        <button 
                          className="btn btn-sm btn-light border-0 fw-bold px-3 py-1"
                          onClick={() => setQuantity(quantity + 1)}
                        >+</button>
                      </div>
                    </div>
                  </div>
                ) : (
                  /* Customization Options & Sizing for Clothing / Customizable items */
                  <>
                    <div className="filter-dropdowns-section mb-4 border-bottom pb-3">
                      <h5 className="section-subtitle-text fw-bold text-dark mb-3">Customization Options</h5>
                      
                      {/* Mobile View */}
                      <div className="d-block d-lg-none">
                        {productData.materials.length > 0 && (
                          <div className="customization-row-mobile d-flex align-items-center justify-content-between mb-3">
                            <span className="customization-label-mobile fw-bold text-secondary" style={{ fontSize: '0.82rem', width: '35%' }}>Material</span>
                            <select className="form-select custom-dropdown" style={{ width: '65%' }} value={material} onChange={e => setMaterial(e.target.value)}>
                              {productData.materials.map((m, i) => <option key={i}>{m}</option>)}
                            </select>
                          </div>
                        )}

                        {productData.colors.length > 0 && (
                          <div className="customization-row-mobile d-flex align-items-center justify-content-between mb-3">
                            <span className="customization-label-mobile fw-bold text-secondary" style={{ fontSize: '0.82rem', width: '35%' }}>Fabric Colour</span>
                            <select className="form-select custom-dropdown" style={{ width: '65%' }} value={fabricColour} onChange={e => setFabricColour(e.target.value)}>
                              {productData.colors.map((c, i) => <option key={i}>{c}</option>)}
                            </select>
                          </div>
                        )}

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
                          {productData.materials.length > 0 && (
                            <Col xs={12} sm={6}>
                              <label className="form-label-text fw-bold text-secondary mb-1">Material / Fabric</label>
                              <select className="form-select custom-dropdown" value={material} onChange={e => setMaterial(e.target.value)}>
                                {productData.materials.map((m, i) => <option key={i}>{m}</option>)}
                              </select>
                            </Col>
                          )}

                          {productData.colors.length > 0 && (
                            <Col xs={12} sm={6}>
                              <label className="form-label-text fw-bold text-secondary mb-1">Fabric Colour</label>
                              <select className="form-select custom-dropdown" value={fabricColour} onChange={e => setFabricColour(e.target.value)}>
                                {productData.colors.map((c, i) => <option key={i}>{c}</option>)}
                              </select>
                            </Col>
                          )}

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
                              <option>Unisex Regular Fit</option>
                              <option>Slim Fit</option>
                              <option>Oversized Fit</option>
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
                        {(productData.sizes.length > 0 ? productData.sizes : ['S', 'M', 'L', 'XL', 'XXL']).map((size) => (
                          <div key={size} className="size-split-item flex-fill border rounded p-2">
                            <div className="size-label fw-bold mb-2 text-dark">{size}</div>
                            <input
                              type="text"
                              className="form-control text-center size-input-box py-1 px-1"
                              value={sizeSplit[size] === 0 || sizeSplit[size] === undefined || sizeSplit[size] === '' ? '' : sizeSplit[size]}
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
                            value={totalQty > 0 ? totalQty : quantity}
                            readOnly
                          />
                        </div>
                        <small className="text-secondary text-xs">
                          Instant ordering up to 3000 units. Bulk orders get discounted pricing.
                        </small>
                      </div>
                    </div>
                  </>
                )}

                {/* Total Price Calculation Summary */}
                <div className="total-price-summary-box bg-light border p-3 rounded mb-4">
                  <Row className="align-items-center g-2">
                    <Col xs={4} className="fw-bold text-secondary">Per piece</Col>
                    <Col xs={8} className="fw-bold text-dark text-end">₹ {perPiecePrice.toFixed(2)}</Col>

                    <Col xs={4} className="fw-bold text-secondary">Quantity</Col>
                    <Col xs={8} className="text-end fw-bold text-dark">
                      {isClothing ? (totalQty > 0 ? totalQty : 1) : quantity} units
                    </Col>

                    {couponApplied && (
                      <>
                        <Col xs={4} className="fw-bold text-success">Coupon Applied</Col>
                        <Col xs={8} className="fw-bold text-success text-end">- ₹ 50.00</Col>
                      </>
                    )}

                    <Col xs={12} className="border-top my-2"></Col>
                    
                    <Col xs={4} className="fw-bold text-dark fs-5">Total</Col>
                    <Col xs={8} className="fw-bold text-success text-end fs-4">
                      ₹ {(perPiecePrice * (isClothing ? (totalQty > 0 ? totalQty : 1) : quantity) - (couponApplied ? 50 : 0)).toFixed(2)}
                    </Col>
                  </Row>
                </div>

                {/* Upload & Create Design buttons for Customizable products */}
                {isClothing && (
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
                )}

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
                    <FaMapMarkerAlt className="text-muted" /> Check Delivery Date & Shipping Charges
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
                  {productData.shipping_method_status && (
                    <div className="mt-2 text-xs text-secondary d-flex flex-wrap gap-3 border-top pt-2">
                      <span>Local Shipping: <strong>₹{productData.local_shipping_price}</strong></span>
                      <span>Regional: <strong>₹{productData.regional_shipping_price}</strong></span>
                      <span>National: <strong>₹{productData.national_shipping_price}</strong></span>
                    </div>
                  )}
                </div>

                {/* Real Dynamic Trust Badges */}
                <div className="trust-badges-row d-none d-lg-flex justify-content-around align-items-center bg-light border py-3 px-2 rounded mb-4 text-center">
                  <div className="trust-badge-item">
                    <FaSyncAlt size="20" className="text-primary mb-1" />
                    <div className="fw-bold text-xs text-dark">
                      {productData.cancel_status === 'yes' ? `${productData.cancel_value} ${productData.cancel_type === 'hour' ? 'Hour' : 'Day'} Cancellation` : 'Non-Refundable'}
                    </div>
                  </div>
                  <div className="trust-badge-item">
                    <FaCheckCircle size="20" className={productData.cod_status === 'yes' ? "text-success mb-1" : "text-muted mb-1"} />
                    <div className="fw-bold text-xs text-dark">
                      {productData.cod_status === 'yes' ? 'Cash on Delivery' : 'Prepaid Only'}
                    </div>
                  </div>
                  <div className="trust-badge-item d-flex flex-column align-items-center">
                    <img src="/Asured.png" alt="PM Assured" style={{ height: "20px", objectFit: "contain", marginBottom: "4px" }} />
                    <div className="fw-bold text-xs text-dark">PM Assured</div>
                  </div>
                </div>

                {/* Mobile Trust Badges List */}
                <div className="d-block d-lg-none mb-4 bg-white border-top border-bottom py-1">
                  <div className="d-flex align-items-center justify-content-between py-2 border-bottom px-2">
                    <div className="d-flex align-items-center gap-2 text-dark">
                      <FaSyncAlt size="16" className="text-secondary" style={{ transform: 'scaleX(-1)' }} />
                      <span className="fw-semibold" style={{ fontSize: '0.85rem' }}>
                        <span className="text-success fw-bold">
                          {productData.shipping_method_status ? `National Delivery ₹${productData.national_shipping_price}` : 'FREE Delivery'}
                        </span>
                      </span>
                    </div>
                    <FaChevronRight size={12} className="text-secondary" />
                  </div>
                  <div className="d-flex align-items-center justify-content-between py-2 border-bottom px-2">
                    <div className="d-flex align-items-center gap-2 text-dark">
                      <FaSyncAlt size="16" className="text-secondary" />
                      <span className="fw-semibold" style={{ fontSize: '0.85rem' }}>
                        {productData.cancel_status === 'yes' ? `${productData.cancel_value} ${productData.cancel_type === 'hour' ? 'Hour' : 'Day'} Cancellation Policy` : 'Non-refundable'}
                      </span>
                    </div>
                    <FaChevronRight size={12} className="text-secondary" />
                  </div>
                  <div className="d-flex align-items-center justify-content-between py-2 px-2">
                    <div className="d-flex align-items-center gap-2 text-dark">
                      <FaCheckCircle size="16" className={productData.cod_status === 'yes' ? "text-success" : "text-muted"} />
                      <span className="fw-semibold" style={{ fontSize: '0.85rem' }}>
                        {productData.cod_status === 'yes' ? 'Cash on Delivery Available' : 'Cash on Delivery Not Available'}
                      </span>
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

        {/* SECTION 4: About the Product (Dynamic Tabs) */}
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
                <div dangerouslySetInnerHTML={{ __html: productData.long_description || productData.description }} />
              )}
              {activeTab === 'instructions' && (
                <div dangerouslySetInnerHTML={{ __html: productData.instructions || '<p>Follow standard product care guidelines.</p>' }} />
              )}
              {activeTab === 'delivery' && (
                <div dangerouslySetInnerHTML={{ __html: productData.delivery_info || '<p>Standard delivery within 3-5 business days across India.</p>' }} />
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

        {/* SECTION 5: Add-on Products / Frequently Bought Together (Flipkart Style) */}
        <Row className="p-0 mx-0 my-4 w-100">
          <Col xs={12} className="p-0">
            <FrequentlyBoughtTogether addonIds={productData.addon_product_ids} />
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
          <ProductCarousel products={discount} title="Similar Products" />
        </Row>

        <Row className='p-0 mx-0 my-3 w-100'>
          <ProductCarousel products={discount} title="Discount on Similar Products" />
        </Row>

        <Row className='p-0 mx-0 my-3 w-100'>
          <ProductCarousel products={girloutfit} title="You Might Be Interested" />
        </Row>

        <Row className='p-0 mx-0 my-3 w-100'>
          <ProductCarousel products={girloutfit} title="Your Recently Viewed" />
        </Row>

      </Container>

      {/* === Customize Product Modal === */}
      <Modal show={customizeModalOpen} onHide={() => setCustomizeModalOpen(false)} fullscreen="sm-down" centered scrollable size="lg">
        {/* Deep Blue Header */}
        <div className="d-flex align-items-center justify-content-between px-3 py-3 text-white" style={{ backgroundColor: '#00539f' }}>
          <div className="d-flex align-items-center gap-3">
            <button 
              type="button" 
              className="btn p-0 border-0 bg-transparent text-white d-flex align-items-center"
              onClick={() => setCustomizeModalOpen(false)}
            >
              {/* Back Arrow */}
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
              </svg>
            </button>
            <h5 className="mb-0 fw-bold">Customize Product</h5>
          </div>
          <button type="button" className="btn-close btn-close-white" onClick={() => setCustomizeModalOpen(false)} aria-label="Close"></button>
        </div>

        {/* Modal Body */}
        <Modal.Body className="p-0 bg-light d-flex flex-column" style={{ minHeight: '500px' }}>
          {/* Custom Tabs Navigation */}
          <div className="d-flex bg-white border-bottom shadow-sm">
            <button
              type="button"
              className="flex-fill py-3 border-0 bg-transparent fw-bold text-center position-relative"
              style={{
                color: customActiveTab === 'upload' ? '#00539f' : '#888',
                fontSize: '0.95rem',
                borderBottom: customActiveTab === 'upload' ? '3px solid #00539f' : 'none'
              }}
              onClick={() => setCustomActiveTab('upload')}
            >
              Upload Designs
            </button>
            <button
              type="button"
              className="flex-fill py-3 border-0 bg-transparent fw-bold text-center position-relative"
              style={{
                color: customActiveTab === 'text' ? '#00539f' : '#888',
                fontSize: '0.95rem',
                borderBottom: customActiveTab === 'text' ? '3px solid #00539f' : 'none'
              }}
              onClick={() => setCustomActiveTab('text')}
            >
              Enter Text
            </button>
          </div>

          <div className="p-3 bg-white flex-grow-1">
            {customActiveTab === 'upload' ? (
              /* Upload Design Content */
              <div>
                {/* 11 Slots Grid */}
                <div className="row g-3 mb-4">
                  {uploadedDesigns.map((design, idx) => (
                    <div key={idx} className="col-4">
                      <div 
                        className="custom-design-slot-box position-relative border rounded d-flex align-items-center justify-content-center cursor-pointer"
                        style={{ 
                          aspectRatio: '1', 
                          backgroundColor: '#f8f9fa', 
                          border: '1.5px dashed #ccc', 
                          borderRadius: '8px',
                          overflow: 'hidden'
                        }}
                        onClick={() => !design && triggerSlotUpload(idx)}
                      >
                        {design ? (
                          <>
                            <img src={design} alt={`Uploaded ${idx}`} className="w-100 h-100" style={{ objectFit: 'cover' }} />
                            <button
                              type="button"
                              className="position-absolute top-0 right-0 btn btn-sm btn-danger d-flex align-items-center justify-content-center rounded-circle p-0"
                              style={{ width: '20px', height: '20px', top: '4px', right: '4px', fontSize: '10px' }}
                              onClick={(e) => {
                                e.stopPropagation();
                                removeSlotDesign(idx);
                              }}
                            >
                              &times;
                            </button>
                          </>
                        ) : (
                          <>
                            {/* Placeholder Image Icon */}
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#888" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                              <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                              <circle cx="8.5" cy="8.5" r="1.5" />
                              <polyline points="21 15 16 10 5 21" />
                            </svg>
                            {/* Plus Icon at bottom right corner */}
                            <span 
                              className="position-absolute d-flex align-items-center justify-content-center text-muted fw-bold bg-white rounded-circle shadow-sm"
                              style={{ 
                                right: '6px', 
                                bottom: '6px', 
                                width: '18px', 
                                height: '18px', 
                                fontSize: '12px',
                                border: '1px solid #ddd' 
                              }}
                            >
                              +
                            </span>
                          </>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            ) : (
              /* Enter Text Content */
              <div className="d-flex flex-column gap-3 mb-4">
                {customTexts.map((txt, idx) => (
                  <div key={idx} className="border rounded px-2 py-1 bg-white" style={{ borderColor: '#ddd' }}>
                    <Form.Control
                      type="text"
                      placeholder="Enter your text name here."
                      value={txt}
                      className="border-0 shadow-none px-2 py-2"
                      style={{ fontSize: '0.9rem', color: '#495057' }}
                      onChange={(e) => handleTextChange(idx, e.target.value)}
                    />
                  </div>
                ))}
              </div>
            )}

            {/* Instructions Section */}
            <div className="instructions-panel border-top pt-3 mt-3">
              <h6 className="fw-bold text-dark mb-3" style={{ fontSize: '1rem' }}>Instructions</h6>
              <ul className="text-secondary list-unstyled d-flex flex-column gap-2" style={{ fontSize: '0.85rem', paddingLeft: '0' }}>
                <li>* File size should be 100KB - 10MB only</li>
                <li>* Upload only JPG, JPEG, PNG.</li>
                <li>* Please upload a good quality image.</li>
                <li>* Please ensure you have rights to use the image.</li>
              </ul>
            </div>
          </div>
        </Modal.Body>

        {/* Modal Footer with Continue Button */}
        <Modal.Footer className="bg-white p-3 border-top w-100 justify-content-center">
          <button
            type="button"
            className="btn btn-primary w-100 py-2.5 fw-bold text-white border-0"
            style={{ backgroundColor: '#00a6f3', borderRadius: '8px', fontSize: '1rem' }}
            onClick={handleCustomizeSave}
          >
            Continue
          </button>
        </Modal.Footer>
      </Modal>

      {/* Hidden file input for slots */}
      <input
        type="file"
        id="custom-slot-file-input"
        accept="image/*"
        style={{ display: 'none' }}
        onChange={handleSlotFileChange}
      />

      {/* Add to Cart Success Popup Modal */}
      <Modal 
        show={showCartSuccessModal} 
        onHide={() => setShowCartSuccessModal(false)} 
        centered
        size="md"
        className="cart-success-modal"
      >
        <Modal.Header closeButton className="border-0 pb-0">
          <Modal.Title className="w-100 text-center text-success fw-bold fs-5 d-flex align-items-center justify-content-center gap-2">
            <FaCheckCircle className="fs-4 text-success" /> Item Added to Cart!
          </Modal.Title>
        </Modal.Header>
        <Modal.Body className="pt-2 pb-4 px-4 text-center">
          {addedCartDetails && (
            <div className="d-flex align-items-center gap-3 p-3 bg-light rounded border mb-4 text-start">
              <img 
                src={addedCartDetails.image} 
                alt={addedCartDetails.title} 
                style={{ width: '75px', height: '75px', objectFit: 'contain' }}
                className="rounded border bg-white p-1"
              />
              <div className="flex-grow-1 overflow-hidden">
                <h6 className="fw-bold mb-1 text-dark text-truncate" style={{ maxWidth: '280px' }}>{addedCartDetails.title}</h6>
                <div className="text-muted small">Quantity: <span className="fw-semibold text-dark">{addedCartDetails.qty}</span></div>
                <div className="fw-bold text-success fs-6 mt-1">₹{(addedCartDetails.price * addedCartDetails.qty).toLocaleString('en-IN')}</div>
              </div>
            </div>
          )}
          <div className="d-flex gap-2">
            <button 
              className="btn btn-outline-secondary w-50 py-2.5 fw-semibold rounded"
              onClick={() => setShowCartSuccessModal(false)}
            >
              Continue Shopping
            </button>
            <button 
              className="btn btn-primary w-50 py-2.5 fw-bold rounded d-flex align-items-center justify-content-center gap-2"
              onClick={() => {
                setShowCartSuccessModal(false);
                navigate('/cart');
              }}
            >
              <FaShoppingCart /> View Cart & Checkout
            </button>
          </div>
        </Modal.Body>
      </Modal>
    </div>
  );
};

export default ProductDetails;
