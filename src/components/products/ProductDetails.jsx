import React, { useState, useEffect, useRef } from 'react';
import { Container, Row, Col, Modal, Spinner, Alert } from 'react-bootstrap';
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
  FaMapMarkerAlt,
  FaHeart,
  FaChevronUp,
  FaChevronDown,
  FaChevronRight,
  FaShare,
  FaCheckCircle,
  FaSyncAlt,
  FaTrash
} from 'react-icons/fa';

import { Swiper, SwiperSlide } from 'swiper/react';
import { Scrollbar, Pagination } from 'swiper/modules';
import FrequentlyBoughtTogether from './FrequentlyBoughtTogether';
import ProductReview from './ProductReview';
import ProductCustomizationForm, { isCustomizationComplete } from './ProductCustomizationForm';
import { ProductCarousel } from '../home';

// Attribute options (materials/colors/sizes/lamination/orientation) come back
// from the backend in two different shapes depending on which admin form
// built them: plain strings (["100% Cotton"]) or richer variant objects
// ({name, price, hex}) for colors/sizes. Always resolve to a plain string
// label — rendering the object directly would crash React ("Objects are not
// valid as a React child").
const optionLabel = (item) => {
  if (item == null) return '';
  if (typeof item === 'string') return item;
  const label = item.name ?? item.label ?? String(item);
  return item.price ? `${label} (+₹${item.price})` : label;
};
const optionValue = (item) => (typeof item === 'string' ? item : (item?.name ?? String(item)));

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

const ProductDetails = () => {
  const navigate = useNavigate();
  const checkoutContext = useCheckout();
  const addToCart = checkoutContext?.addToCart;
  const removeItem = checkoutContext?.removeItem;
  const cartItems = checkoutContext?.cartItems || [];

  const { productSlug } = useParams();
  const idMatch = productSlug ? productSlug.match(/-p(\d+)$/) : null;
  const productId = idMatch ? idMatch[1] : (productSlug && /^\d+$/.test(productSlug) ? productSlug : null);

  const emptyProduct = {
    id: null, title: '', images: [], originalPrice: 0, perPiecePrice: 0, discountPercent: 0,
    description: '', long_description: '', instructions: '', delivery_info: '',
    category_name: '', brand: '', sku: '', stock_quantity: 0,
    cod_status: 'no', cancel_status: 'no', cancel_type: 'hour', cancel_value: 0,
    shipping_method_status: false, local_shipping_price: 0, regional_shipping_price: 0,
    national_shipping_price: 0, regional_shipping_msg: '', national_shipping_msg: '',
    min_quantity: 1, sizes: [], colors: [], materials: [], lamination: [], orientation: [],
    printing_location: [], color_images: {}, bulk_order_pricing: [], quantity_pricing: [],
    customization_data: [], customization_label_status: false, addon_product_ids: [],
    our_bestseller: false, top_rated: false, featured: false,
    meta_title: '', meta_description: '', meta_keywords: '',
  };

  const [productData, setProductData] = useState(emptyProduct);
  const currentProductId = productData?.id || productId;
  const isInCart = cartItems.some(item => String(item.id) === String(currentProductId));
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const [activeImage, setActiveImage] = useState('');
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

        const price = parseFloat(p.price) || 0;
        const discountPrice = parseFloat(p.discount_price) || price;
        const discountPercent = price > 0 && discountPrice > 0 && discountPrice < price
            ? Math.round(((price - discountPrice) / price) * 100)
            : 0;

        const formatted = {
          id: p.id,
          title: p.name || p.title || 'Unknown Product',
          images: images,
          originalPrice: price,
          perPiecePrice: discountPrice > 0 ? discountPrice : price,
          discountPercent,
          discount: discountPercent > 0 ? `${discountPercent}% off` : '',
          description: p.description || '',
          long_description: p.long_description || p.description || '',
          instructions: p.instructions || '',
          delivery_info: p.delivery_info || '',
          category_name: p.category_name || p.main_category_name || '',
          brand: p.brand || '',
          sku: p.sku || '',
          stock_quantity: p.stock_quantity ?? 0,
          cod_status: p.cod_status || 'no',
          cancel_status: p.cancel_status || 'no',
          cancel_type: p.cancel_type || 'hour',
          cancel_value: p.cancel_value || 0,
          shipping_method_status: p.shipping_method_status || false,
          local_shipping_price: p.local_shipping_price || 0,
          regional_shipping_price: p.regional_shipping_price || 0,
          national_shipping_price: p.national_shipping_price || 0,
          regional_shipping_msg: p.regional_shipping_msg || '',
          national_shipping_msg: p.national_shipping_msg || '',
          min_quantity: p.min_quantity || 1,
          sizes: Array.isArray(p.sizes) ? p.sizes : [],
          colors: Array.isArray(p.colors) ? p.colors : [],
          materials: Array.isArray(p.materials) ? p.materials : [],
          lamination: Array.isArray(p.lamination) ? p.lamination : [],
          orientation: Array.isArray(p.orientation) ? p.orientation : [],
          printing_location: Array.isArray(p.printing_location) ? p.printing_location : [],
          // Backend sends [] when unset (no admin UI populates this yet) rather
          // than {} — only treat it as a real colour->image map when it's a
          // genuine non-array object.
          color_images: (p.color_images && typeof p.color_images === 'object' && !Array.isArray(p.color_images)) ? p.color_images : {},
          bulk_order_pricing: Array.isArray(p.bulk_order_pricing) ? p.bulk_order_pricing : [],
          quantity_pricing: Array.isArray(p.quantity_pricing) ? p.quantity_pricing : [],
          customization_data: Array.isArray(p.customization_data) ? p.customization_data : [],
          customization_label_status: p.customization_label_status || false,
          addon_product_ids: Array.isArray(p.addon_product_ids) ? p.addon_product_ids : [],
          our_bestseller: !!p.our_bestseller,
          top_rated: !!p.top_rated,
          featured: !!p.featured,
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

  // Real average rating + count for the badge next to the title — the full
  // review list/write-a-review form lives lower on the page in <ProductReview>,
  // which fetches the same endpoint independently for its own section.
  const [ratingStats, setRatingStats] = useState({ average: 0, count: 0 });
  useEffect(() => {
    if (!productId) return;
    let cancelled = false;
    fetch(API_ENDPOINTS.PRODUCT_REVIEWS(productId))
      .then((res) => res.json())
      .then((res) => {
        if (!cancelled && res?.success && res.data?.stats) {
          setRatingStats(res.data.stats);
        }
      })
      .catch(() => {});
    return () => { cancelled = true; };
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
  // Real, product-driven attribute selections — only rendered when the
  // product actually has that field populated (see the Product Options
  // section below), so nothing here is a guessed/fake default.
  const [material, setMaterial] = useState('');
  const [fabricColour, setFabricColour] = useState('');
  const [selectedSize, setSelectedSize] = useState('');
  const [selectedLamination, setSelectedLamination] = useState('');
  const [selectedOrientation, setSelectedOrientation] = useState('');

  useEffect(() => {
    if (productData.materials.length > 0 && !material) setMaterial(optionValue(productData.materials[0]));
    if (productData.colors.length > 0 && !fabricColour) setFabricColour(optionValue(productData.colors[0]));
    if (productData.sizes.length > 0 && !selectedSize) setSelectedSize(optionValue(productData.sizes[0]));
    if (productData.lamination.length > 0 && !selectedLamination) setSelectedLamination(optionValue(productData.lamination[0]));
    if (productData.orientation.length > 0 && !selectedOrientation) setSelectedOrientation(optionValue(productData.orientation[0]));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [productData.id]);

  // Switch the main image when a colour with its own image is picked.
  useEffect(() => {
    if (fabricColour && productData.color_images[fabricColour]) {
      setActiveImage(productData.color_images[fabricColour]);
    }
  }, [fabricColour, productData.color_images]);

  const [quantity, setQuantity] = useState(productData.min_quantity || 1);
  useEffect(() => {
    setQuantity(productData.min_quantity || 1);
  }, [productData.min_quantity]);

  // Pincode check
  const [pincode, setPincode] = useState('');
  const [pincodeMessage, setPincodeMessage] = useState('');

  // Per-product customization form (only rendered when the admin enabled it
  // for this product) — see ProductCustomizationForm.jsx.
  const [customizationValues, setCustomizationValues] = useState({});
  useEffect(() => {
    setCustomizationValues({});
  }, [productData.id]);

  // About Product active tab
  const [activeTab, setActiveTab] = useState('description');

  useEffect(() => {
    const handleResize = () => {
      setUseCarousel(window.innerWidth < 992);
    };
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  const perPiecePrice = productData.perPiecePrice || 0;
  const originalPerPiecePrice = productData.originalPrice || 0;

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
                      {productData.our_bestseller && <div className="bestseller-badge">Best Seller</div>}
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
                        {productData.our_bestseller && <div className="bestseller-badge">Best Seller</div>}

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
                            if (!isCustomizationComplete(productData.customization_data, customizationValues)) {
                              toast.error('Please fill in the required customization fields.');
                              return;
                            }
                            if (addToCart) {
                              addToCart(productData, quantity, {
                                material,
                                fabricColour,
                                size: selectedSize,
                                lamination: selectedLamination,
                                orientation: selectedOrientation,
                                customization: customizationValues,
                              });
                              setAddedCartDetails({
                                title: productData.title,
                                price: perPiecePrice,
                                qty: quantity,
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
                          if (!isCustomizationComplete(productData.customization_data, customizationValues)) {
                            toast.error('Please fill in the required customization fields.');
                            return;
                          }
                          if (addToCart) {
                            addToCart(productData, quantity, {
                              material,
                              fabricColour,
                              size: selectedSize,
                              lamination: selectedLamination,
                              orientation: selectedOrientation,
                              customization: customizationValues,
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

                {/* Product Title */}
                <h1 className="product-title-text text-dark fw-bold mb-2">{productData.title}</h1>

                {/* Reviews Badges Row — real rating pulled from the reviews API, only shown once reviews exist */}
                {ratingStats.count > 0 && (
                  <div className="d-flex align-items-center gap-3 mb-3">
                    <div className="rating-pill d-flex align-items-center gap-1 bg-success text-white px-2 py-1 rounded fw-semibold text-sm">
                      {ratingStats.average.toFixed(1)} <FaStar size="12" />
                    </div>
                    <span className="reviews-summary-text text-secondary text-sm">
                      {ratingStats.count} Review{ratingStats.count !== 1 ? 's' : ''}
                    </span>
                  </div>
                )}

                {/* Pricing Block */}
                <div className="price-display-wrapper mb-4 border-bottom pb-3">
                  <div className="d-flex align-items-baseline gap-2">
                    <span className="price-current fw-bold text-dark fs-2">₹{perPiecePrice.toLocaleString('en-IN')}</span>
                    {productData.discountPercent > 0 && (
                      <>
                        <span className="price-original text-decoration-line-through text-muted fs-6">₹{originalPerPiecePrice.toLocaleString('en-IN')}</span>
                        <span className="price-discount text-success fw-bold text-sm">({productData.discount})</span>
                      </>
                    )}
                  </div>
                  {productData.min_quantity > 1 && (
                    <div className="min-order-text text-danger fw-semibold text-sm mt-1">
                      Minimum order {productData.min_quantity} units.
                    </div>
                  )}
                </div>

                {/* Specifications — always real, never category-guessed */}
                <div className="tech-specs-section mb-4 border-bottom pb-3">
                  <h5 className="section-subtitle-text fw-bold text-dark mb-3">Specifications</h5>
                  <table className="tech-specs-table">
                    <tbody>
                      <tr>
                        <td className="spec-label">Brand</td>
                        <td className="spec-value text-capitalize">{productData.brand || 'Printmont'}</td>
                      </tr>
                      <tr>
                        <td className="spec-label">SKU</td>
                        <td className="spec-value">{productData.sku || 'N/A'}</td>
                      </tr>
                      <tr>
                        <td className="spec-label">Category</td>
                        <td className="spec-value">{productData.category_name || 'N/A'}</td>
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
                </div>

                {/* Product Options — only the attributes this product actually has */}
                {(productData.materials.length > 0 || productData.colors.length > 0 || productData.sizes.length > 0 ||
                  productData.lamination.length > 0 || productData.orientation.length > 0) && (
                  <div className="filter-dropdowns-section mb-4 border-bottom pb-3">
                    <h5 className="section-subtitle-text fw-bold text-dark mb-3">Product Options</h5>
                    <Row className="g-3">
                      {productData.materials.length > 0 && (
                        <Col xs={12} sm={6}>
                          <label className="form-label-text fw-bold text-secondary mb-1">Material</label>
                          <select className="form-select custom-dropdown" value={material} onChange={e => setMaterial(e.target.value)}>
                            {productData.materials.map((m, i) => <option key={i} value={optionValue(m)}>{optionLabel(m)}</option>)}
                          </select>
                        </Col>
                      )}
                      {productData.colors.length > 0 && (
                        <Col xs={12} sm={6}>
                          <label className="form-label-text fw-bold text-secondary mb-1">Colour</label>
                          <select className="form-select custom-dropdown" value={fabricColour} onChange={e => setFabricColour(e.target.value)}>
                            {productData.colors.map((c, i) => <option key={i} value={optionValue(c)}>{optionLabel(c)}</option>)}
                          </select>
                        </Col>
                      )}
                      {productData.sizes.length > 0 && (
                        <Col xs={12} sm={6}>
                          <label className="form-label-text fw-bold text-secondary mb-1">Size</label>
                          <select className="form-select custom-dropdown" value={selectedSize} onChange={e => setSelectedSize(e.target.value)}>
                            {productData.sizes.map((s, i) => <option key={i} value={optionValue(s)}>{optionLabel(s)}</option>)}
                          </select>
                        </Col>
                      )}
                      {productData.lamination.length > 0 && (
                        <Col xs={12} sm={6}>
                          <label className="form-label-text fw-bold text-secondary mb-1">Lamination</label>
                          <select className="form-select custom-dropdown" value={selectedLamination} onChange={e => setSelectedLamination(e.target.value)}>
                            {productData.lamination.map((l, i) => <option key={i} value={optionValue(l)}>{optionLabel(l)}</option>)}
                          </select>
                        </Col>
                      )}
                      {productData.orientation.length > 0 && (
                        <Col xs={12} sm={6}>
                          <label className="form-label-text fw-bold text-secondary mb-1">Orientation</label>
                          <select className="form-select custom-dropdown" value={selectedOrientation} onChange={e => setSelectedOrientation(e.target.value)}>
                            {productData.orientation.map((o, i) => <option key={i} value={optionValue(o)}>{optionLabel(o)}</option>)}
                          </select>
                        </Col>
                      )}
                    </Row>
                  </div>
                )}

                {/* Bulk pricing tiers — hidden entirely when the product has none configured */}
                {(productData.bulk_order_pricing.length > 0 || productData.quantity_pricing.length > 0) && (
                  <div className="mb-4 border-bottom pb-3">
                    <h5 className="section-subtitle-text fw-bold text-dark mb-2">Bulk Pricing</h5>
                    <table className="tech-specs-table w-100">
                      <thead>
                        <tr>
                          <td className="spec-label">Quantity</td>
                          <td className="spec-label">Price / unit</td>
                        </tr>
                      </thead>
                      <tbody>
                        {(productData.bulk_order_pricing.length > 0 ? productData.bulk_order_pricing : productData.quantity_pricing).map((tier, i) => (
                          <tr key={i}>
                            <td className="spec-value">
                              {tier.min_qty ?? tier.qty}{tier.max_qty ? ` - ${tier.max_qty}` : '+'}
                            </td>
                            <td className="spec-value">₹{Number(tier.price).toLocaleString('en-IN')}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}

                {/* Quantity + Total */}
                <div className="quantity-manual-input d-flex align-items-center gap-3 mb-3">
                  <span className="fw-bold text-dark">Quantity:</span>
                  <div className="d-flex align-items-center border rounded">
                    <button
                      className="btn btn-sm btn-light border-0 fw-bold px-3 py-1"
                      onClick={() => setQuantity(Math.max(productData.min_quantity || 1, quantity - 1))}
                    >-</button>
                    <span className="fw-bold px-3">{quantity}</span>
                    <button
                      className="btn btn-sm btn-light border-0 fw-bold px-3 py-1"
                      onClick={() => setQuantity(quantity + 1)}
                    >+</button>
                  </div>
                </div>

                <div className="total-price-summary-box bg-light border p-3 rounded mb-4">
                  <Row className="align-items-center g-2">
                    <Col xs={4} className="fw-bold text-secondary">Per piece</Col>
                    <Col xs={8} className="fw-bold text-dark text-end">₹ {perPiecePrice.toFixed(2)}</Col>

                    <Col xs={4} className="fw-bold text-secondary">Quantity</Col>
                    <Col xs={8} className="text-end fw-bold text-dark">{quantity} units</Col>

                    <Col xs={12} className="border-top my-2"></Col>

                    <Col xs={4} className="fw-bold text-dark fs-5">Total</Col>
                    <Col xs={8} className="fw-bold text-success text-end fs-4">
                      ₹ {(perPiecePrice * quantity).toFixed(2)}
                    </Col>
                  </Row>
                </div>

                {/* Per-product customization, driven entirely by real admin config */}
                {productData.customization_label_status && (
                  <ProductCustomizationForm
                    fields={productData.customization_data}
                    values={customizationValues}
                    onChange={setCustomizationValues}
                  />
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

          </Col>
        </Row>

        {/* SECTION 5: Add-on Products / Frequently Bought Together (Flipkart Style) */}
        <Row className="p-0 mx-0 my-4 w-100">
          <Col xs={12} className="p-0">
            <FrequentlyBoughtTogether addonIds={productData.addon_product_ids} />
          </Col>
        </Row>

        {/* SECTION 6: Ratings & Reviews */}
        <Row className="p-0 mx-0 my-4 w-100">
          <Col xs={12} className="p-0">
            <div className="bg-white border rounded p-3 shadow-sm">
              <ProductReview productId={productData.id} />
            </div>
          </Col>
        </Row>

        {/* SECTION 7: Related Products — real category-matched products, not dummy data */}
        {productData.id && (
          <Row className='p-0 mx-0 my-3 w-100'>
            <ProductCarousel apiUrl={API_ENDPOINTS.RELATED_PRODUCTS(productData.id)} title="Related Products" />
          </Row>
        )}

      </Container>

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
