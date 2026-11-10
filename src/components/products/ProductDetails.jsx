import React, { useState, useEffect, useRef, useMemo } from 'react';
import { Container, Row, Col, Modal, Spinner, Alert, Form } from 'react-bootstrap';
import { useParams, useNavigate, Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import { API_ENDPOINTS, resolveImageUrl } from '../../config/apiEndpoints';
import ProductInfoBanners from './ProductInfoBanners';
import ProductAddons from './ProductAddons';
import DesignUploadModal from './DesignUploadModal';
import SaleCountdown from './SaleCountdown';
import GiftVoucherOffer from './GiftVoucherOffer';
import ProductBreadcrumb from './ProductBreadcrumb';
import './ProductDetailsDesktop.css';
import { useCheckout } from '../../context/CheckoutContext';
import { useWishlist } from '../../context/WishlistContext';
import useRequireLogin from '../../hooks/useRequireLogin';
import checkPincode from '../../utils/checkPincode';
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
  FaTrash,
  FaDraftingCompass,
  FaUpload,
  FaQuestionCircle,
  FaCoins,
  FaTag,
  FaShieldAlt,
  FaAward,
  FaLeaf,
  FaTshirt,
  FaTruck,
  FaMoneyBillWave,
  FaInfoCircle,
  FaCheck,
  FaHeadset,
  FaPencilAlt,
  FaThumbsUp,
  FaThumbsDown,
  FaRulerHorizontal,
  FaEnvelope
} from 'react-icons/fa';
import { RiDiscountPercentFill } from 'react-icons/ri';

import { Swiper, SwiperSlide } from 'swiper/react';
import { Scrollbar, Pagination } from 'swiper/modules';
import FrequentlyBoughtTogether from './FrequentlyBoughtTogether';
import ProductReview from './ProductReview';
import ProductQASection from './ProductQASection';
import ProductCustomizationForm, { isCustomizationComplete, customizationExtraPrice } from './ProductCustomizationForm';
import { ProductCarousel } from '../home';
import SmallBannerCarousel from '../home/banners/SmallBannerCarousel';
import MobileProductDetails from './MobileProductDetails';
import RecentlyViewedProducts from './RecentlyViewedProducts';

// Helper to get string label from string or object option
const optionLabel = (item) => {
  if (item == null) return '';
  if (typeof item === 'string') return item;
  const label = item.name ?? item.label ?? item.size ?? item.material ?? item.color ?? String(item);
  return item.price ? `${label} (+₹${item.price})` : label;
};

// Helper to get string value from string or object option
const optionValue = (item) => {
  if (item == null) return '';
  if (typeof item === 'string') return item;
  return item.name ?? item.label ?? item.size ?? item.material ?? item.color ?? String(item);
};

// Custom attribute values are a string for single-choice types and an array for 'multi'
const isCustomAttrEmpty = (val) => (Array.isArray(val) ? val.length === 0 : !val);

const ProductDetails = () => {
  const navigate = useNavigate();
  const checkoutContext = useCheckout();
  const addToCart = checkoutContext?.addToCart;
  const removeItem = checkoutContext?.removeItem;
  const cartItems = checkoutContext?.cartItems || [];
  const setDeliveryPincode = checkoutContext?.setDeliveryPincode;
  const setDeliveryInfo = checkoutContext?.setDeliveryInfo;

  const { productSlug } = useParams();
  const idMatch = productSlug ? productSlug.match(/-p(\d+)$/) : null;
  const lookupKey = idMatch ? idMatch[1] : productSlug;

  const emptyProduct = {
    id: null,
    title: '',
    name: '',
    images: [],
    originalPrice: 0,
    perPiecePrice: 0,
    discountPercent: 0,
    discount: '',
    description: '',
    long_description: '',
    instructions: '',
    delivery_info: '',
    category_id: null,
    category_name: '',
    main_category_name: '',
    brand: '',
    sku: '',
    stock_quantity: 100,
    out_of_stock_status: 'in_stock',
    show_left_quantity: false,
    min_quantity: 1,
    cod_status: 'yes',
    cancel_status: 'yes',
    cancel_type: 'days',
    cancel_value: 10,
    shipping_method_status: false,
    local_shipping_price: 0,
    regional_shipping_price: 0,
    national_shipping_price: 0,
    regional_shipping_msg: '',
    national_shipping_msg: '',
    show_bulk_form: true,
    show_cart_buttons: true,
    show_inquiry_form: false,
    size_chart_image: null,
    custom_attributes: [],
    show_help_button: false,
    help_button_text: 'Need Help?',
    help_button_link: '/contact',
    // Customization
    customization_label_status: false,
    customization_label: 'Customizable',
    customization_data: [],
    customization_tech: '',
    customization_location: '',
    // Variants
    size_status: false,
    sizes: [],
    colors: [],
    color_images: {},
    materials: [],
    lamination: [],
    orientation: [],
    printing_location: [],
    bulk_order_pricing: [],
    quantity_pricing: [],
    addon_product_ids: [],
    our_bestseller: false,
    top_rated: false,
    featured: false,
    top_deal: false,
    type: '',
    material: '',
    color: '',
    occasion: '',
    discount_type: '',
    gender: '',
    ideal_for: '',
    features: '',
    meta_title: '',
    meta_description: '',
    meta_keywords: ''
  };

  const [productData, setProductData] = useState(emptyProduct);

  // Wishlist / cart need a logged-in customer (guests are sent to login and brought back here)
  const requireLogin = useRequireLogin();
  const { isInWishlist, toggleWishlist, removeFromWishlist } = useWishlist();
  const isWishlisted = Boolean(productData?.id) && isInWishlist(productData.id);
  const setIsWishlisted = () => {
    if (productData?.id) toggleWishlist(productData);
  };
  const currentProductId = productData?.id || (idMatch ? idMatch[1] : null);
  const isInCart = cartItems.some(item => String(item.id) === String(currentProductId));
  const [loading, setLoading] = useState(true);
  // Bumped when a sale countdown reaches zero, so the product (and its normal price) is reloaded
  const [saleRefresh, setSaleRefresh] = useState(0);
  const [error, setError] = useState(null);

  const [activeImage, setActiveImage] = useState('');
  const [useCarousel, setUseCarousel] = useState(window.innerWidth < 992);
  const [startIndex, setStartIndex] = useState(0);
  const maxThumbnails = 5;

  // Modals state
  const [showCartSuccessModal, setShowCartSuccessModal] = useState(false);
  const [addedCartDetails, setAddedCartDetails] = useState(null);
  const [showBulkModal, setShowBulkModal] = useState(false);
  const [showUploadModal, setShowUploadModal] = useState(false);
  const [uploadedArtwork, setUploadedArtwork] = useState(null);
  // Designs from the "Upload your design / Create your design" popup: { images: [{url, name}], texts: [] }
  const [designData, setDesignData] = useState({ images: [], texts: [] });
  const [copiedCoupon, setCopiedCoupon] = useState('');

  // Accordion states
  const [showReviewsAccordion, setShowReviewsAccordion] = useState(true);
  const [showQaAccordion, setShowQaAccordion] = useState(false);

  // Bulk Inquiry Form State
  const [bulkInquiry, setBulkInquiry] = useState({
    name: '',
    email: '',
    phone: '',
    quantity: '50',
    message: ''
  });
  const [bulkSubmitting, setBulkSubmitting] = useState(false);

  // Zoom Lens state for Flipkart style preview
  const [isZooming, setIsZooming] = useState(false);
  const [lensPos, setLensPos] = useState({ left: 0, top: 0, width: 170, height: 170 });
  const [zoomBgPos, setZoomBgPos] = useState({ x: 0, y: 0, width: 0, height: 0 });
  const mainImageContainerRef = useRef(null);
  const mainImgRef = useRef(null);

  // No zoom while the pointer is on the wishlist / share buttons or the badges over the image
  const isOverImageControls = (e) => Boolean(e && e.target && e.target.closest && e.target.closest('.pdd-image__actions, .pdd-image__badges'));

  const handleMouseEnterZoom = (e) => {
    if (window.innerWidth >= 992 && !isOverImageControls(e)) setIsZooming(true);
  };

  const handleMouseLeaveZoom = () => {
    setIsZooming(false);
  };

  const handleMouseMoveZoom = (e) => {
    if (!mainImageContainerRef.current || !mainImgRef.current || window.innerWidth < 992) return;
    if (isOverImageControls(e)) {
      if (isZooming) setIsZooming(false);
      return;
    }
    if (!isZooming) setIsZooming(true);
    
    const containerRect = mainImageContainerRef.current.getBoundingClientRect();
    const imgEl = mainImgRef.current;
    const imgRect = imgEl.getBoundingClientRect();

    const naturalWidth = imgEl.naturalWidth || imgRect.width;
    const naturalHeight = imgEl.naturalHeight || imgRect.height;
    const naturalAspect = naturalWidth / (naturalHeight || 1);
    const boxAspect = imgRect.width / (imgRect.height || 1);

    let renderedWidth = imgRect.width;
    let renderedHeight = imgRect.height;
    let renderedLeft = imgRect.left;
    let renderedTop = imgRect.top;

    if (naturalAspect > boxAspect) {
      renderedWidth = imgRect.width;
      renderedHeight = imgRect.width / naturalAspect;
      renderedTop = imgRect.top + (imgRect.height - renderedHeight) / 2;
    } else {
      renderedHeight = imgRect.height;
      renderedWidth = imgRect.height * naturalAspect;
      renderedLeft = imgRect.left + (imgRect.width - renderedWidth) / 2;
    }

    const prevWidth = 520;
    const prevHeight = 520;
    const zoomFactor = 2.5;

    const lensWidth = Math.min(renderedWidth, prevWidth / zoomFactor);
    const lensHeight = Math.min(renderedHeight, prevHeight / zoomFactor);

    let mouseX = e.clientX - renderedLeft;
    let mouseY = e.clientY - renderedTop;

    mouseX = Math.max(0, Math.min(mouseX, renderedWidth));
    mouseY = Math.max(0, Math.min(mouseY, renderedHeight));

    let lensLeft = mouseX - lensWidth / 2;
    let lensTop = mouseY - lensHeight / 2;

    lensLeft = Math.max(0, Math.min(lensLeft, renderedWidth - lensWidth));
    lensTop = Math.max(0, Math.min(lensTop, renderedHeight - lensHeight));

    const lensContainerLeft = (renderedLeft - containerRect.left) + lensLeft;
    const lensContainerTop = (renderedTop - containerRect.top) + lensTop;

    setLensPos({
      left: lensContainerLeft,
      top: lensContainerTop,
      width: lensWidth,
      height: lensHeight
    });

    const bgX = -(lensLeft / (renderedWidth - lensWidth || 1)) * (renderedWidth * zoomFactor - prevWidth);
    const bgY = -(lensTop / (renderedHeight - lensHeight || 1)) * (renderedHeight * zoomFactor - prevHeight);

    setZoomBgPos({
      x: bgX,
      y: bgY,
      width: renderedWidth * zoomFactor,
      height: renderedHeight * zoomFactor
    });
  };

  // Dynamic Selected Options
  const [selectedSize, setSelectedSize] = useState('');
  const [selectedType, setSelectedType] = useState('');
  const [selectedStyle, setSelectedStyle] = useState('');
  const [selectedMaterial, setSelectedMaterial] = useState('');
  const [selectedPrintType, setSelectedPrintType] = useState('');
  const [selectedColor, setSelectedColor] = useState('');
  const [selectedLamination, setSelectedLamination] = useState('');
  const [selectedOrientation, setSelectedOrientation] = useState('');
  const [selectedLocations, setSelectedLocations] = useState([]);
  const [frontPrintSize, setFrontPrintSize] = useState('');
  const [backPrintSize, setBackPrintSize] = useState('');
  const [selectedCustomAttributes, setSelectedCustomAttributes] = useState({});
  const [showSizeChartModal, setShowSizeChartModal] = useState(false);

  // Customization fields values { [label]: { text?: string, fileUrl?: string, fileName?: string } }
  const [customizationValues, setCustomizationValues] = useState({});

  // Size split for bulk if clothing sizes exist
  const [sizeSplit, setSizeSplit] = useState({});
  const [quantity, setQuantity] = useState(1);
  const [showTiersDropdown, setShowTiersDropdown] = useState(false);
  const tiersDropdownRef = useRef(null);

  useEffect(() => {
    if (!showTiersDropdown) return;
    const handleOutsideClick = (e) => {
      if (tiersDropdownRef.current && !tiersDropdownRef.current.contains(e.target)) {
        setShowTiersDropdown(false);
      }
    };
    document.addEventListener('mousedown', handleOutsideClick);
    return () => document.removeEventListener('mousedown', handleOutsideClick);
  }, [showTiersDropdown]);

  // Fetch product data directly from backend API
  useEffect(() => {
    if (!lookupKey) {
      setLoading(false);
      return;
    }

    const fetchProduct = async (isInitial = true) => {
      try {
        if (isInitial) setLoading(true);
        const ep = API_ENDPOINTS.PRODUCT_DETAILS(lookupKey);
        const sep = ep.includes("?") ? "&" : "?";
        const res = await fetch(`${ep}${sep}_t=${Date.now()}`);
        const json = await res.json();
        const p = json?.data || json?.product;
        if (!json?.success || !p) throw new Error(json?.message || 'Product not found');

        // Extract and resolve product images
        let images = [];
        if (p.images && Array.isArray(p.images) && p.images.length > 0) {
          images = p.images.map(img => (typeof img === 'string' ? resolveImageUrl(img) : resolveImageUrl(img.image_url)));
        } else if (p.primary_image || p.thumbnail) {
          images = [resolveImageUrl(p.primary_image || p.thumbnail)];
        } else {
          images = ['https://via.placeholder.com/600x600?text=No+Product+Image'];
        }
        // Admin "Product Gallery" images come after the main images
        if (Array.isArray(p.gallery_images)) {
          p.gallery_images.forEach((g) => {
            const url = resolveImageUrl(g);
            if (url && !images.includes(url)) images.push(url);
          });
        }

        const regPrice = parseFloat(p.regular_price || p.price || 0);
        const offPrice = p.offer_price ? parseFloat(p.offer_price) : (p.discount_price ? parseFloat(p.discount_price) : 0);
        const perPiece = (offPrice > 0 && offPrice < regPrice) ? offPrice : (regPrice > 0 ? regPrice : offPrice);
        const discPercent = (regPrice > 0 && regPrice > perPiece) ? Math.round(((regPrice - perPiece) / regPrice) * 100) : 0;

        // Customization check
        const hasCustomization = Boolean(p.customization_label_status);
        const hasSizes = Boolean(p.size_status);
        const minQty = parseInt(p.min_quantity || 1);

        // Format backend product
        const formatted = {
          id: p.id,
          name: p.name || '',
          title: p.name || '',
          images,
          originalPrice: regPrice,
          perPiecePrice: perPiece,
          discountPercent: discPercent,
          discount: discPercent > 0 ? `${discPercent}% off` : '',
          description: p.description || '',
          long_description: p.long_description || p.description || '',
          instructions: p.instructions || '',
          delivery_info: p.delivery_info || '',
          category_id: p.category_id,
          category_name: p.category_name || p.main_category_name || '',
          breadcrumb: Array.isArray(p.breadcrumb) ? p.breadcrumb : [],
          main_category_name: p.main_category_name || '',
          brand: p.brand || '',
          sku: p.sku || (p.id ? `PM-${p.id}` : ''),
          stock_quantity: parseInt(p.stock_quantity ?? 100),
          out_of_stock_status: p.out_of_stock_status || (p.stock_quantity <= 0 ? 'out_of_stock' : 'in_stock'),
          show_left_quantity: Boolean(p.show_left_quantity),
          min_quantity: minQty,
          cod_status: p.cod_status || 'yes',
          cancel_status: p.cancel_status || 'yes',
          cancel_type: p.cancel_type || 'days',
          cancel_value: parseInt(p.cancel_value || 10),
          shipping_method_status: Boolean(p.shipping_method_status),
          local_shipping_price: parseFloat(p.local_shipping_price || 0),
          regional_shipping_price: parseFloat(p.regional_shipping_price || 0),
          national_shipping_price: parseFloat(p.national_shipping_price || 0),
          regional_shipping_msg: p.regional_shipping_msg || '',
          national_shipping_msg: p.national_shipping_msg || '',
          show_bulk_form: p.show_bulk_form !== undefined ? Boolean(p.show_bulk_form) : true,
          show_cart_buttons: p.show_cart_buttons !== undefined ? Boolean(p.show_cart_buttons) : true,
          show_inquiry_form: Boolean(p.show_inquiry_form),
          size_chart_image: p.size_chart_image || null,
          custom_attributes: Array.isArray(p.custom_attributes) ? p.custom_attributes : (Array.isArray(p.product_attributes_fields) ? p.product_attributes_fields : []),
          show_help_button: Boolean(p.show_help_button),
          help_button_text: p.help_button_text || 'Need Help?',
          help_button_link: p.help_button_link || '/contact',
          // Customization settings & Design Buttons
          customization_label_status: hasCustomization,
          customization_label: p.customization_label || 'Customizable',
          enable_upload_design: p.enable_upload_design !== undefined ? Boolean(p.enable_upload_design) : true,
          upload_design_max_images: p.upload_design_max_images !== undefined ? Math.max(1, parseInt(p.upload_design_max_images)) : 4,
          upload_design_max_texts: p.upload_design_max_texts !== undefined ? Math.max(0, parseInt(p.upload_design_max_texts)) : 2,
          enable_customizer: p.enable_customizer !== undefined ? Boolean(p.enable_customizer) : true,
          customizer_template_id: p.customizer_template_id || null,
          customization_data: Array.isArray(p.customization_data) ? p.customization_data : [],
          customization_tech: p.customization_tech || '',
          customization_location: p.customization_location || '',
          // Variants from backend or standard options
          size_status: hasSizes,
          sizes: Array.isArray(p.sizes) ? p.sizes : [],
          colors: Array.isArray(p.colors) ? p.colors : [],
          color_images: (p.color_images && typeof p.color_images === 'object' && !Array.isArray(p.color_images)) ? p.color_images : {},
          materials: Array.isArray(p.materials) ? p.materials : [],
          lamination: Array.isArray(p.lamination) ? p.lamination : [],
          orientation: Array.isArray(p.orientation) ? p.orientation : [],
          printing_location: Array.isArray(p.printing_location) ? p.printing_location : [],
          bulk_order_pricing: Array.isArray(p.bulk_order_pricing) ? p.bulk_order_pricing : [],
          quantity_pricing: Array.isArray(p.quantity_pricing) ? p.quantity_pricing : [],
          addon_product_ids: Array.isArray(p.addon_product_ids) ? p.addon_product_ids : [],
          our_bestseller: Boolean(p.our_bestseller),
          top_rated: Boolean(p.top_rated),
          featured: Boolean(p.featured),
          top_deal: Boolean(p.top_deal),
          is_assured: Boolean(p.is_assured),
          type: p.type || '',
          material: p.material || '',
          color: p.color || '',
          occasion: p.occasion || '',
          discount_type: p.discount_type || '',
          gender: p.gender || '',
          ideal_for: p.ideal_for || '',
          features: p.features || '',
          sale: p.sale && p.sale.ends_at ? p.sale : null,
          shape: p.shape || '',
          gift_type: p.gift_type || '',
          capacity: p.capacity || '',
          ink_color: p.ink_color || '',
          local_shipping_msg: p.local_shipping_msg || '',
          meta_title: p.meta_title || '',
          meta_description: p.meta_description || '',
          meta_keywords: p.meta_keywords || ''
        };

        setProductData(formatted);
        setActiveImage(images[0]);
        setQuantity(minQty);

        // Populate options based on actual product data
        if (hasSizes && formatted.sizes && formatted.sizes.length > 0) {
          setSelectedSize(optionValue(formatted.sizes[0]));
          const initSplit = {};
          formatted.sizes.forEach((sz, idx) => {
            initSplit[optionValue(sz)] = idx === 0 ? minQty : 0;
          });
          setSizeSplit(initSplit);
        } else {
          setSelectedSize('');
          setSizeSplit({});
        }

        if (formatted.colors && formatted.colors.length > 0) {
          const initColor = optionValue(formatted.colors[0]);
          setSelectedColor(initColor);
          const colorImg = (formatted.color_images && formatted.color_images[initColor]) || formatted.colors[0].image;
          if (colorImg) {
            setActiveImage(colorImg);
          }
        } else {
          setSelectedColor(formatted.color || '');
        }

        if (formatted.materials && formatted.materials.length > 0) {
          setSelectedMaterial(optionValue(formatted.materials[0]));
        } else {
          setSelectedMaterial(formatted.material || '');
        }

        if (hasCustomization && formatted.printing_location && formatted.printing_location.length > 0) {
          setSelectedLocations([formatted.printing_location[0]]);
        } else {
          setSelectedLocations([]);
        }

        if (formatted.type) {
          setSelectedType(formatted.type);
        }

        // Dynamic Document Title
        document.title = formatted.meta_title || `${formatted.title || 'Product'} - Printmont`;
      } catch (err) {
        if (isInitial) {
          setError(err.message);
        }
      } finally {
        if (isInitial) {
          setLoading(false);
        }
      }
    };

    fetchProduct(true);

    const handleFocus = () => {
      fetchProduct(false);
    };

    const handleVisibilityChange = () => {
      if (typeof document !== "undefined" && document.visibilityState === "visible") {
        fetchProduct(false);
      }
    };

    window.addEventListener("focus", handleFocus);
    document.addEventListener("visibilitychange", handleVisibilityChange);

    return () => {
      window.removeEventListener("focus", handleFocus);
      document.removeEventListener("visibilitychange", handleVisibilityChange);
    };
  }, [lookupKey, saleRefresh]);

  // Product Rating Stats
  const [ratingStats, setRatingStats] = useState({ average: 5.0, count: 10 });
  useEffect(() => {
    if (!productData?.id) return;
    let cancelled = false;
    fetch(API_ENDPOINTS.PRODUCT_REVIEWS(productData.id))
      .then((res) => res.json())
      .then((res) => {
        if (!cancelled && res?.success && res.data?.stats) {
          if (res.data.stats.count > 0) {
            setRatingStats(res.data.stats);
          }
        }
      })
      .catch(() => {});
    return () => { cancelled = true; };
  }, [productData?.id]);

  // Desktop: the sticky image fills the space from just below the navbar (or below the
  // breadcrumb, before scrolling) down to just above the sticky Add to cart / Buy now bar.
  // The top changes while scrolling, so the height is measured here and passed to CSS.
  useEffect(() => {
    if (loading || !productData?.id) return undefined;
    let frame = 0;
    const update = () => {
      frame = 0;
      const root = document.querySelector('.pdd');
      const gallery = root && root.querySelector('.pdd-gallery');
      if (!root || !gallery || window.innerWidth < 992) return;
      const css = getComputedStyle(root);
      const headerH = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--site-header-height')) || 100;
      const gap = 8;
      const ctaH = parseFloat(css.getPropertyValue('--pdd-cta-h')) || 69;
      const top = Math.max(gallery.getBoundingClientRect().top, headerH + gap);
      const height = Math.max(360, Math.round(window.innerHeight - ctaH - gap - top));
      root.style.setProperty('--pdd-gallery-h', `${height}px`);
    };
    const schedule = () => { if (!frame) frame = requestAnimationFrame(update); };
    update();
    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule);
    return () => {
      window.removeEventListener('scroll', schedule);
      window.removeEventListener('resize', schedule);
      if (frame) cancelAnimationFrame(frame);
    };
  }, [loading, productData?.id]);

  // Recently Viewed persistence
  useEffect(() => {
    if (!productData || !productData.id || !productData.title) return;
    try {
      const stored = JSON.parse(localStorage.getItem('pm_recently_viewed') || '[]');
      const currentItem = {
        id: productData.id,
        name: productData.title,
        title: productData.title,
        price: productData.originalPrice || productData.perPiecePrice,
        discount_price: productData.perPiecePrice,
        image: productData.images?.[0] || '',
        primary_image: productData.images?.[0] || '',
        slug: productSlug || '',
        category_name: productData.category_name,
        rating: 5.0
      };
      const filtered = Array.isArray(stored) ? stored.filter(item => String(item.id) !== String(productData.id)) : [];
      const updated = [currentItem, ...filtered].slice(0, 15);
      localStorage.setItem('pm_recently_viewed', JSON.stringify(updated));
    } catch (e) {
      console.error('Error saving recently viewed:', e);
    }
  }, [productData?.id, productData?.title, productSlug]);

  const recentlyViewed = useMemo(() => {
    try {
      const stored = JSON.parse(localStorage.getItem('pm_recently_viewed') || '[]');
      if (!Array.isArray(stored)) return [];
      return stored.filter(item => String(item.id) !== String(productData?.id));
    } catch {
      return [];
    }
  }, [productData?.id]);

  const visibleImages = (productData.images || []).slice(startIndex, startIndex + maxThumbnails);

  const handlePrevThumb = () => {
    if (startIndex > 0) setStartIndex(startIndex - 1);
  };

  const handleNextThumb = () => {
    if (startIndex + maxThumbnails < (productData.images || []).length) setStartIndex(startIndex + 1);
  };

  // Delivery check state
  const [pincode, setPincode] = useState('');
  const [pincodeMessage, setPincodeMessage] = useState('');
  const [deliveryAvailable, setDeliveryAvailable] = useState(true);

  // Real delivery check (locations-api.php); a deliverable pincode is also kept for the cart
  const handleCheckPincode = async () => {
    setPincodeMessage('Checking…');
    const result = await checkPincode(pincode);
    setDeliveryAvailable(Boolean(result.deliverable));
    if (!result.deliverable) {
      setPincodeMessage(result.message || 'Delivery is not available to this pincode.');
      return;
    }
    setPincodeMessage(
      `✓ Delivery to ${result.city_name} by ${result.delivery_by_label}`
      + (result.cod_available ? ' | Cash on Delivery Available' : '')
    );
    if (setDeliveryPincode) setDeliveryPincode(result.pincode);
    if (setDeliveryInfo) setDeliveryInfo(result);
  };

  // Active tab in specifications
  const [activeTab, setActiveTab] = useState('description');

  useEffect(() => {
    const handleResize = () => setUseCarousel(window.innerWidth < 992);
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  // Calculate dynamic unit price based on bulk pricing tier
  // Color selection with instant variant image swapping
  const handleColorChange = (colorVal) => {
    setSelectedColor(colorVal);
    let targetImg = null;
    if (productData.color_images && productData.color_images[colorVal]) {
      targetImg = productData.color_images[colorVal];
    } else if (Array.isArray(productData.colors)) {
      const matched = productData.colors.find((c) => optionValue(c) === colorVal);
      if (matched && matched.image) targetImg = matched.image;
    }
    if (targetImg) {
      setActiveImage(targetImg);
    }
  };

  // Price per piece for the current quantity and options:
  // bulk tier (min–max qty) → else quantity tier (from qty upwards) → else offer price,
  // plus the admin "price add-on" of the selected size and colour.
  const optionAddOn = (list, selected) => {
    const opt = (list || []).find((o) => optionValue(o) === selected);
    return opt && typeof opt === 'object' ? parseFloat(opt.price) || 0 : 0;
  };

  const calculateDynamicUnitPrice = () => {
    let unit = productData.perPiecePrice || productData.originalPrice || 0;

    const bulkTier = (productData.bulk_order_pricing || []).find((tier) => {
      const min = Number(tier.min_qty ?? tier.qty ?? 0);
      const max = Number(tier.max_qty) > 0 ? Number(tier.max_qty) : Infinity; // empty max = no upper limit
      return quantity >= min && quantity <= max && Number(tier.price) > 0;
    });

    if (bulkTier) {
      unit = parseFloat(bulkTier.price);
    } else {
      const qtyTier = (productData.quantity_pricing || [])
        .filter((tier) => Number(tier.price) > 0 && quantity >= Number(tier.qty ?? tier.min_qty ?? 0))
        .sort((a, b) => Number(b.qty ?? b.min_qty) - Number(a.qty ?? a.min_qty))[0];
      if (qtyTier) unit = parseFloat(qtyTier.price);
    }

    const customExtra = (productData.customization_data && productData.customization_data.length > 0)
      ? customizationExtraPrice(productData.customization_data, customizationValues)
      : 0;
    return unit + optionAddOn(productData.sizes, selectedSize) + optionAddOn(productData.colors, selectedColor) + customExtra + customAttributesAddOn;
  };

  // Calculate price add-ons from selected custom product attributes (Tab 2)
  const customAttributesAddOn = useMemo(() => {
    let total = 0;
    if (!productData.custom_attributes || !Array.isArray(productData.custom_attributes)) return 0;
    for (const attr of productData.custom_attributes) {
      const selectedVal = selectedCustomAttributes[attr.label];
      if (!selectedVal || !attr.options || !Array.isArray(attr.options)) continue;
      if (Array.isArray(selectedVal)) {
        selectedVal.forEach((val) => {
          const opt = attr.options.find((o) => (o.label || o) === val);
          if (opt && Number(opt.price) > 0) total += Number(opt.price);
        });
      } else {
        const opt = attr.options.find((o) => (o.label || o) === selectedVal);
        if (opt && Number(opt.price) > 0) total += Number(opt.price);
      }
    }
    return total;
  }, [productData.custom_attributes, selectedCustomAttributes]);

  const dynamicUnitPrice = calculateDynamicUnitPrice();

  // Next quantity tier that lowers the per-piece price (shown under Quantity in the summary)
  const nextPriceTier = (() => {
    const tiers = [
      ...(productData.bulk_order_pricing || []).map((t) => ({ qty: Number(t.min_qty ?? t.qty ?? 0), price: Number(t.price) })),
      ...(productData.quantity_pricing || []).map((t) => ({ qty: Number(t.qty ?? t.min_qty ?? 0), price: Number(t.price) })),
    ].filter((t) => t.qty > quantity && t.price > 0 && t.price < dynamicUnitPrice);
    tiers.sort((a, b) => a.qty - b.qty);
    return tiers[0] || null;
  })();
  const originalPerPiecePrice = productData.originalPrice || (dynamicUnitPrice * 1.25);
  const totalPrice = dynamicUnitPrice * quantity;

  // Handle size change in matrix
  const handleSizeChange = (szKey, val) => {
    const num = Math.max(0, parseInt(val) || 0);
    const updated = { ...sizeSplit, [szKey]: num };
    setSizeSplit(updated);
    const totalFromSizes = Object.values(updated).reduce((acc, n) => acc + n, 0);
    if (totalFromSizes > 0) {
      setQuantity(totalFromSizes);
    }
  };

  // Coupons available
  const coupons = [
    {
      code: "PRNTM5",
      title: "Get Flat 5% OFF",
      desc: "Add items worth ₹1999+ to unlock this offer",
      discount: 5,
    },
    {
      code: "PRNTM10",
      title: "Get Flat 10% OFF",
      desc: "Add items worth ₹3999+ to unlock this offer",
      discount: 10,
    },
    {
      code: "PRNTM15",
      title: "Get Flat 15% OFF",
      desc: "Add items worth ₹5999+ to unlock this offer",
      discount: 15,
    },
  ];

  // Bank & Wallet offers
  const bankOffers = [
    {
      theme: "paytm",
      logo: "https://upload.wikimedia.org/wikipedia/commons/2/24/Paytm_Logo_%28standalone%29.svg",
      name: "Paytm / UPI",
      desc: "Get Cashback up to ₹300 on a minimum transaction of ₹799",
    },
    {
      theme: "amazon",
      logo: "https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg",
      name: "Amazon Pay",
      desc: "Get up to ₹200 cashback on 2 Amazon Pay Balance orders (minimum order value ₹150).",
    },
    {
      theme: "airtel",
      logo: "/offers/airtel.png",
      name: "Airtel / Freecharge",
      desc: "Flat 10% off up to ₹200 on a minimum transaction of ₹999",
    },
    {
      theme: "mobikwik",
      logo: "/offers/mobikwik.png",
      name: "MobiKwik",
      desc: "Get up to ₹300 cashback on transactions using MobiKwik UPI (@ikwik)/Wallet.",
    },
  ];

  // Handle coupon copy
  const handleCopyCoupon = (code) => {
    navigator.clipboard.writeText(code);
    setCopiedCoupon(code);
    toast.success(`Coupon ${code} applied!`, { position: 'top-center' });
    setTimeout(() => setCopiedCoupon(''), 3000);
  };

  // Add to Cart Validation & Dispatch
  const handleAddToCart = () => {
    if (!requireLogin('Please login to add items to your cart')) return;
    if (productData.out_of_stock_status === 'out_of_stock' || productData.stock_quantity <= 0) {
      toast.error('This product is currently out of stock.');
      return;
    }

    if (productData.customization_data && productData.customization_data.length > 0 && !isCustomizationComplete(productData.customization_data, customizationValues)) {
      toast.error('Please fill in required customization fields.');
      return;
    }

    if (productData.custom_attributes && Array.isArray(productData.custom_attributes)) {
      for (const attr of productData.custom_attributes) {
        if (attr.required && isCustomAttrEmpty(selectedCustomAttributes[attr.label])) {
          toast.error(`Please select ${attr.label}`);
          return;
        }
      }
    }

    if (addToCart) {
      addToCart(productData, quantity, {
        price: dynamicUnitPrice,
        size: selectedSize,
        color: selectedColor,
        material: selectedMaterial,
        type: selectedType,
        style: selectedStyle,
        printType: selectedPrintType,
        printLocations: selectedLocations,
        frontPrintSize,
        backPrintSize,
        sizeSplit,
        customization: customizationValues,
        customAttributes: selectedCustomAttributes,
        uploadedArtwork: uploadedArtwork?.name || null,
        designUploads: designData.images,
        designTexts: designData.texts
      });

      // If product is in wishlist, remove it (Flipkart behavior)
      const currentId = productData.id || productData.productId;
      if (currentId && isInWishlist(currentId) && removeFromWishlist) {
        removeFromWishlist(currentId, { silent: true });
      }

      setAddedCartDetails({
        title: productData.title,
        price: dynamicUnitPrice,
        qty: quantity,
        image: activeImage
      });

      toast.success('Item added to cart successfully!', { duration: 2500, position: 'top-center' });
      setShowCartSuccessModal(true);
    } else {
      navigate('/cart');
    }
  };

  const handleBuyNow = () => {
    if (!requireLogin('Please login to buy this product')) return;
    if (productData.out_of_stock_status === 'out_of_stock' || productData.stock_quantity <= 0) {
      toast.error('This product is currently out of stock.');
      return;
    }

    if (productData.customization_data && productData.customization_data.length > 0 && !isCustomizationComplete(productData.customization_data, customizationValues)) {
      toast.error('Please fill in required customization fields.');
      return;
    }

    if (productData.custom_attributes && Array.isArray(productData.custom_attributes)) {
      for (const attr of productData.custom_attributes) {
        if (attr.required && isCustomAttrEmpty(selectedCustomAttributes[attr.label])) {
          toast.error(`Please select ${attr.label}`);
          return;
        }
      }
    }

    if (addToCart) {
      addToCart(productData, quantity, {
        price: dynamicUnitPrice,
        size: selectedSize,
        color: selectedColor,
        material: selectedMaterial,
        type: selectedType,
        style: selectedStyle,
        printType: selectedPrintType,
        printLocations: selectedLocations,
        frontPrintSize,
        backPrintSize,
        sizeSplit,
        customization: customizationValues,
        customAttributes: selectedCustomAttributes,
        uploadedArtwork: uploadedArtwork?.name || null,
        designUploads: designData.images,
        designTexts: designData.texts
      });

      const currentId = productData.id || productData.productId;
      if (currentId && isInWishlist(currentId) && removeFromWishlist) {
        removeFromWishlist(currentId, { silent: true });
      }
    }
    navigate('/cart');
  };

  // Bulk Inquiry Submit
  const handleBulkInquirySubmit = (e) => {
    e.preventDefault();
    setBulkSubmitting(true);
    setTimeout(() => {
      setBulkSubmitting(false);
      setShowBulkModal(false);
      toast.success('Inquiry submitted successfully! Our B2B expert will contact you shortly.', {
        duration: 4000,
        position: 'top-center'
      });
      setBulkInquiry({ name: '', email: '', phone: '', quantity: '50', message: '' });
    }, 1000);
  };

  if (loading) {
    return (
      <Container className="d-flex justify-content-center align-items-center" style={{ minHeight: '60vh' }}>
        <div className="text-center">
          <Spinner animation="border" variant="primary" />
          <div className="text-muted mt-2 text-xs">Loading product details...</div>
        </div>
      </Container>
    );
  }

  if (error) {
    return (
      <Container className="py-5">
        <Alert variant="danger">{error}</Alert>
      </Container>
    );
  }

  if (!lookupKey || (!productData?.id && !loading)) {
    return <PageNotFound />;
  }

  return (
    <div className='product-details-page-wrapper' style={{ backgroundColor: '#f1f3f6', minHeight: '100vh', paddingBottom: '0px' }}>
      
      {/* ══════════════════════════════════════════════════════════════════
          MOBILE VIEW: Synchronized Specification (< 992px)
      ══════════════════════════════════════════════════════════════════ */}
      <div className="d-block d-lg-none">
        <MobileProductDetails
          productData={productData}
          onSaleEnd={() => setSaleRefresh((k) => k + 1)}
          activeImage={activeImage}
          setActiveImage={setActiveImage}
          isWishlisted={isWishlisted}
          setIsWishlisted={setIsWishlisted}
          ratingStats={ratingStats}
          selectedSize={selectedSize}
          setSelectedSize={setSelectedSize}
          selectedColor={selectedColor}
          setSelectedColor={setSelectedColor}
          handleColorChange={handleColorChange}
          selectedMaterial={selectedMaterial}
          setMaterial={setSelectedMaterial}
          selectedType={selectedType}
          setSelectedType={setSelectedType}
          selectedLocations={selectedLocations}
          setSelectedLocations={setSelectedLocations}
          frontPrintSize={frontPrintSize}
          setFrontPrintSize={setFrontPrintSize}
          backPrintSize={backPrintSize}
          setBackPrintSize={setBackPrintSize}
          sizeSplit={sizeSplit}
          setSizeSplit={setSizeSplit}
          handleSizeChange={handleSizeChange}
          nextPriceTier={nextPriceTier}
          designData={designData}
          bankOffers={bankOffers}
          quantity={quantity}
          setQuantity={setQuantity}
          dynamicUnitPrice={dynamicUnitPrice}
          originalPerPiecePrice={originalPerPiecePrice}
          totalPrice={totalPrice}
          uploadedArtwork={uploadedArtwork}
          setUploadedArtwork={setUploadedArtwork}
          customizationValues={customizationValues}
          setCustomizationValues={setCustomizationValues}
          pincode={pincode}
          setPincode={setPincode}
          deliveryAvailable={deliveryAvailable}
          deliveryMsg={pincodeMessage}
          checkDelivery={handleCheckPincode}
          handleApplyCoupon={handleCopyCoupon}
          setShowBulkModal={setShowBulkModal}
          selectedCustomAttributes={selectedCustomAttributes}
          setSelectedCustomAttributes={setSelectedCustomAttributes}
          setShowSizeChartModal={setShowSizeChartModal}
          setShowUploadModal={setShowUploadModal}
          isInCart={isInCart}
          addToCart={handleAddToCart}
          buyNow={handleBuyNow}
          removeItem={removeItem}
          cartItems={cartItems}
          recentlyViewed={recentlyViewed}
        />
      </div>

      {/* ══════════════════════════════════════════════════════════════════
          DESKTOP VIEW: Wider Layout with Full PDF Elements (>= 992px)
      ══════════════════════════════════════════════════════════════════ */}
      <div className="d-none d-lg-block pdd">
        <div className="pdd-container">

          {/* ══════════════════════════════════════════════════════════════════
              MAIN CARD: left gallery (sticky) + right details (scrolls)
          ══════════════════════════════════════════════════════════════════ */}
          <section className="pdd-card pdd-main">
            <ProductBreadcrumb productData={productData} />

            <div className="pdd-grid">

              {/* ───────── LEFT: thumbnails, main image, Add to cart / Buy now ───────── */}
              <aside className="pdd-left">
                <div className="pdd-gallery">
                  {productData.images && productData.images.length > 1 && (
                    <div className="pdd-thumbs">
                      <button
                        type="button"
                        className="pdd-thumbs__arrow"
                        onClick={handlePrevThumb}
                        disabled={startIndex === 0}
                        aria-label="Previous images"
                      >
                        <FaChevronUp size={12} />
                      </button>

                      <div className="pdd-thumbs__list">
                        {visibleImages.map((img, index) => (
                          <div
                            key={index}
                            className={`pdd-thumb ${activeImage === img ? 'is-active' : ''}`}
                            onMouseEnter={() => setActiveImage(img)}
                            onClick={() => setActiveImage(img)}
                          >
                            <img
                              src={img}
                              alt={`Thumbnail ${startIndex + index + 1}`}
                              onError={(e) => { e.currentTarget.parentElement.style.display = 'none'; }}
                            />
                          </div>
                        ))}
                      </div>

                      <button
                        type="button"
                        className="pdd-thumbs__arrow"
                        onClick={handleNextThumb}
                        disabled={startIndex + maxThumbnails >= productData.images.length}
                        aria-label="Next images"
                      >
                        <FaChevronDown size={12} />
                      </button>
                    </div>
                  )}

                  {/* Main image with hover zoom */}
                  <div
                    ref={mainImageContainerRef}
                    className="pdd-image"
                    onMouseEnter={handleMouseEnterZoom}
                    onMouseLeave={handleMouseLeaveZoom}
                    onMouseMove={handleMouseMoveZoom}
                    style={{ cursor: isZooming ? 'crosshair' : 'pointer' }}
                  >
                    <div className="pdd-image__badges">
                      {productData.our_bestseller && <span className="pdd-tag pdd-tag--green">Best Seller</span>}
                      {productData.customization_label_status && (
                        <span className="pdd-tag pdd-tag--blue">
                          <FaDraftingCompass size={10} /> {productData.customization_label || 'Customizable'}
                        </span>
                      )}
                    </div>

                    <div className="pdd-image__actions">
                      <button
                        type="button"
                        className="pdd-icon-btn"
                        onClick={setIsWishlisted}
                        title={isWishlisted ? 'Remove from Wishlist' : 'Add to Wishlist'}
                      >
                        <FaHeart className={isWishlisted ? 'text-danger' : ''} size={14} />
                      </button>
                      <button
                        type="button"
                        className="pdd-icon-btn"
                        onClick={() => {
                          if (navigator.share) {
                            navigator.share({ title: productData.title, url: window.location.href });
                          } else {
                            navigator.clipboard.writeText(window.location.href);
                            toast.success('Link copied to clipboard!');
                          }
                        }}
                        title="Share"
                      >
                        <FaShare size={13} />
                      </button>
                    </div>

                    {isZooming && (
                      <div
                        className="zoom-lens-overlay"
                        style={{
                          position: 'absolute',
                          border: '1px solid #2874f0',
                          backgroundColor: 'rgba(40, 116, 240, 0.15)',
                          pointerEvents: 'none',
                          zIndex: 5,
                          left: `${lensPos.left}px`,
                          top: `${lensPos.top}px`,
                          width: `${lensPos.width || 170}px`,
                          height: `${lensPos.height || 170}px`
                        }}
                      />
                    )}

                    {isZooming && (
                      <div
                        className="zoom-result-window border shadow-lg rounded"
                        style={{
                          position: 'absolute',
                          left: 'calc(100% + 16px)',
                          top: 0,
                          width: '540px',
                          height: '540px',
                          zIndex: 999,
                          backgroundColor: '#fff',
                          backgroundImage: `url("${activeImage}")`,
                          backgroundRepeat: 'no-repeat',
                          backgroundPosition: `${zoomBgPos.x}px ${zoomBgPos.y}px`,
                          backgroundSize: zoomBgPos.width ? `${zoomBgPos.width}px ${zoomBgPos.height}px` : 'auto'
                        }}
                      />
                    )}

                    <img ref={mainImgRef} src={activeImage} alt={productData.title} />
                  </div>
                </div>


                {productData.show_help_button && (
                  <a
                    className="pdd-help"
                    href={productData.help_button_link || '/contact'}
                    {...(/^https?:/i.test(productData.help_button_link || '') ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
                  >
                    <FaInfoCircle size={13} /> {productData.help_button_text || 'Need Help?'}
                  </a>
                )}

              </aside>

              {/* ───────── RIGHT: details, offers, about, reviews, Q&A ───────── */}
              <div className="pdd-right">

                {/* 1. Title, rating, price */}
                <div className="pdd-block">
                  {productData.sale && (
                    <SaleCountdown sale={productData.sale} onEnd={() => setSaleRefresh((k) => k + 1)} />
                  )}
                  {productData.top_deal && <span className="pdd-deal">⚡ Special Deal: Flat Bulk Discounts on Orders!</span>}
                  <h1 className="pdd-title">
                    {productData.brand && <span className="pdd-title__brand">{productData.brand}</span>}
                    {productData.title}
                  </h1>
                  {productData.sku && <div className="pdd-sku">SKU: {productData.sku}</div>}

                  <div className="pdd-rating-row">
                    <div className="pdd-rating">
                      <span className="pdd-badge">{ratingStats.average.toFixed(1)} <FaStar size={10} /></span>
                      <span>{ratingStats.count} Reviews</span>
                      {productData.is_assured && (
                        <img src="/Asured.png" alt="Printmont Assured" className="pdd-assured-logo" />
                      )}
                    </div>
                    <span className="pdd-secure"><FaShieldAlt size={12} /> 100% Secure Transaction</span>
                  </div>

                  <div className="pdd-price">
                    <span className="pdd-price__sp">₹{dynamicUnitPrice.toFixed(0)}</span>
                    {productData.originalPrice > dynamicUnitPrice && (
                      <>
                        <span className="pdd-price__mrp">₹{productData.originalPrice.toFixed(0)}</span>
                        {productData.discountPercent > 0 && <span className="pdd-price__off">{productData.discountPercent}% off</span>}
                      </>
                    )}
                  </div>
                  {productData.min_quantity > 1 && (
                    <div className="pdd-moq">Minimum order {productData.min_quantity} pieces.</div>
                  )}
                  <GiftVoucherOffer productId={productData.id} />
                  {(productData.out_of_stock_status === 'out_of_stock' || productData.stock_quantity <= 0) ? (
                    <div className="pdd-stock pdd-stock--out">Currently out of stock</div>
                  ) : productData.out_of_stock_status === 'pre_order' ? (
                    <div className="pdd-stock pdd-stock--pre">Available for pre-order</div>
                  ) : productData.show_left_quantity && (
                    <div className={`pdd-stock ${productData.stock_quantity <= 10 ? 'pdd-stock--low' : ''}`}>
                      {productData.stock_quantity <= 10
                        ? `Hurry, only ${productData.stock_quantity} left!`
                        : `${productData.stock_quantity} in stock`}
                    </div>
                  )}
                </div>

                {/* All offers & coupons, right below the price */}
                {/* Coupon cards, right below the price */}
                <div className="pdd-block pdd-offers-block">
                  <div className="pdf-horizontal-coupons-wrap">
                    {coupons.map((c, idx) => (
                      <div key={idx} className="pdf-coupon-card">
                        <div>
                          <div className="d-flex align-items-center gap-2 text-success fw-bold text-xs mb-1">
                            <RiDiscountPercentFill size={16} />
                            <span>{c.title}</span>
                          </div>
                          <div className="text-muted" style={{ fontSize: '11px' }}>{c.desc}</div>
                        </div>
                        <div className="d-flex align-items-center justify-content-between mt-2 pt-2 border-top">
                          <span className="fw-bold text-dark text-2xs">Code: {c.code}</span>
                          <button
                            type="button"
                            className="btn btn-xs btn-outline-success fw-bold py-0 px-2"
                            onClick={() => handleCopyCoupon(c.code)}
                          >
                            {copiedCoupon === c.code ? 'Applied' : 'Apply'}
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>

                </div>

                {/* 2. Options, quantity, size split, customization */}
                <div className="pdd-block pdd-options">
                  <div className="pdf-attr-table">
                    {productData.size_status && productData.sizes && productData.sizes.length > 0 && (
                      <div className="pdf-attr-row">
                        <div className="d-flex align-items-center justify-content-between w-100 mb-1">
                          <span className="pdf-attr-label">Select Size</span>
                          {productData.size_chart_image && (
                            <button
                              type="button"
                              className="btn btn-link btn-sm p-0 text-decoration-none text-primary d-inline-flex align-items-center gap-1"
                              style={{ fontSize: '13px', fontWeight: 600 }}
                              onClick={() => setShowSizeChartModal(true)}
                            >
                              <FaRulerHorizontal size={13} /> Size Guide
                            </button>
                          )}
                        </div>
                        <div className="pdf-attr-control w-100">
                          <select className="pdf-select" value={selectedSize} onChange={(e) => setSelectedSize(e.target.value)}>
                            {productData.sizes.map((sz, idx) => (
                              <option key={idx} value={optionValue(sz)}>{optionLabel(sz)}</option>
                            ))}
                          </select>
                        </div>
                      </div>
                    )}

                    {productData.colors && productData.colors.length > 0 && (() => {
                      const selObj = productData.colors.find((c) => optionValue(c) === selectedColor) || productData.colors[0];
                      return (
                        <div className={`pdf-attr-row ${selObj.text ? 'flex-wrap' : ''}`}>
                          <span className="pdf-attr-label">Colour</span>
                          <div className="pdf-attr-control d-flex align-items-center gap-2">
                            <span
                              className="pdf-attr-swatch-dot"
                              style={{ backgroundColor: selObj.hex || '#000000' }}
                            />
                            <select
                              className="pdf-select pdf-select--with-swatch"
                              value={selectedColor || optionValue(productData.colors[0])}
                              onChange={(e) => handleColorChange(e.target.value)}
                            >
                              {productData.colors.map((c, idx) => (
                                <option key={idx} value={optionValue(c)}>{optionLabel(c)}</option>
                              ))}
                            </select>
                            {Number(selObj.price) > 0 && (
                              <span className="badge bg-success-subtle text-success small flex-shrink-0">+₹{selObj.price}</span>
                            )}
                          </div>
                          {selObj.text && (
                            <small className="text-muted w-100 mt-1"><i className="fa fa-info-circle me-1"></i>{selObj.text}</small>
                          )}
                        </div>
                      );
                    })()}

                    {/* Dynamic Custom Product Attributes (Tab 2) */}
                    {productData.custom_attributes && productData.custom_attributes.length > 0 && (
                      productData.custom_attributes.map((attr, aIdx) => {
                        const curVal = selectedCustomAttributes[attr.label] || '';
                        return (
                          <div key={aIdx} className="pdf-attr-row flex-column align-items-start gap-1">
                            <div className="d-flex align-items-center justify-content-between w-100">
                              <span className="pdf-attr-label">
                                {attr.label}
                                {attr.required ? <span className="text-danger ms-1">*</span> : null}:
                                {curVal ? <strong className="text-dark ms-1">{curVal}</strong> : null}
                              </span>
                              {(() => {
                                const optObj = (attr.options || []).find((o) => (o.label || o) === curVal);
                                return optObj && Number(optObj.price) > 0 ? (
                                  <span className="badge bg-success-subtle text-success small">+₹{optObj.price}</span>
                                ) : null;
                              })()}
                            </div>

                            {attr.type === 'select' && (
                              <div className="pdf-attr-control w-100">
                                <select
                                  className="pdf-select"
                                  value={curVal}
                                  onChange={(e) => setSelectedCustomAttributes({ ...selectedCustomAttributes, [attr.label]: e.target.value })}
                                >
                                  <option value="">— Select {attr.label} —</option>
                                  {(attr.options || []).map((opt, oIdx) => {
                                    const oLabel = opt.label || opt;
                                    const oPrice = Number(opt.price || 0);
                                    return (
                                      <option key={oIdx} value={oLabel}>
                                        {oLabel} {oPrice > 0 ? `(+₹${oPrice})` : ''}
                                      </option>
                                    );
                                  })}
                                </select>
                              </div>
                            )}

                            {attr.type === 'radio' && (
                              <div className="d-flex flex-wrap gap-2 w-100 mt-1">
                                {(attr.options || []).map((opt, oIdx) => {
                                  const oLabel = opt.label || opt;
                                  const oPrice = Number(opt.price || 0);
                                  const isChecked = curVal === oLabel;
                                  return (
                                    <button
                                      key={oIdx}
                                      type="button"
                                      className={`btn btn-sm ${isChecked ? 'btn-primary' : 'btn-outline-secondary'}`}
                                      onClick={() => setSelectedCustomAttributes({ ...selectedCustomAttributes, [attr.label]: oLabel })}
                                    >
                                      {oLabel} {oPrice > 0 ? `(+₹${oPrice})` : ''}
                                    </button>
                                  );
                                })}
                              </div>
                            )}

                            {attr.type === 'color' && (
                              <div className="pdf-attr-control w-100">
                                <select
                                  className="pdf-select"
                                  value={curVal}
                                  onChange={(e) => setSelectedCustomAttributes({ ...selectedCustomAttributes, [attr.label]: e.target.value })}
                                >
                                  <option value="">— Select {attr.label} —</option>
                                  {(attr.options || []).map((opt, oIdx) => {
                                    const oLabel = opt.label || opt;
                                    const oPrice = Number(opt.price || 0);
                                    return (
                                      <option key={oIdx} value={oLabel}>
                                        {oLabel} {oPrice > 0 ? `(+₹${oPrice})` : ''}
                                      </option>
                                    );
                                  })}
                                </select>
                              </div>
                            )}

                            {attr.type === 'multi' && (() => {
                              const chosen = Array.isArray(curVal) ? curVal : (curVal ? [curVal] : []);
                              const toggleAttrChip = (oLabel) => {
                                const next = chosen.includes(oLabel) ? chosen.filter((v) => v !== oLabel) : [...chosen, oLabel];
                                setSelectedCustomAttributes({ ...selectedCustomAttributes, [attr.label]: next });
                              };
                              return (
                                <div className="pdf-attr-control w-100">
                                  <div className="pdf-attr-tag-box">
                                    {chosen.length === 0 && <span className="pdf-attr-tag-box__placeholder">Select {attr.label}</span>}
                                    {chosen.map((v) => (
                                      <span key={v} className="pdf-attr-tag">
                                        {v}
                                        <button
                                          type="button"
                                          className="pdf-attr-tag__remove"
                                          onClick={() => toggleAttrChip(v)}
                                          aria-label={`Remove ${v}`}
                                        >
                                          ×
                                        </button>
                                      </span>
                                    ))}
                                  </div>
                                  <div className="d-flex flex-wrap gap-2 mt-2">
                                    {(attr.options || []).map((opt, oIdx) => {
                                      const oLabel = opt.label || opt;
                                      const oPrice = Number(opt.price || 0);
                                      const on = chosen.includes(oLabel);
                                      return (
                                        <button
                                          key={oIdx}
                                          type="button"
                                          className={`btn btn-sm ${on ? 'btn-primary' : 'btn-outline-secondary'}`}
                                          onClick={() => toggleAttrChip(oLabel)}
                                        >
                                          {oLabel} {oPrice > 0 ? `(+₹${oPrice})` : ''}
                                        </button>
                                      );
                                    })}
                                  </div>
                                </div>
                              );
                            })()}

                            {(attr.type === 'text' || attr.type === 'number') && (
                              <div className="pdf-attr-control w-100">
                                <input
                                  type={attr.type}
                                  className="form-control form-control-sm"
                                  placeholder={`Enter ${attr.label}`}
                                  value={curVal}
                                  onChange={(e) => setSelectedCustomAttributes({ ...selectedCustomAttributes, [attr.label]: e.target.value })}
                                />
                              </div>
                            )}

                            {attr.type === 'textarea' && (
                              <div className="pdf-attr-control w-100">
                                <textarea
                                  className="form-control form-control-sm"
                                  rows="2"
                                  placeholder={`Enter ${attr.label}`}
                                  value={curVal}
                                  onChange={(e) => setSelectedCustomAttributes({ ...selectedCustomAttributes, [attr.label]: e.target.value })}
                                />
                              </div>
                            )}

                            {attr.help && <small className="text-muted" style={{ fontSize: '11px' }}>{attr.help}</small>}
                          </div>
                        );
                      })
                    )}

                    {productData.materials && productData.materials.length > 0 && (
                      <div className="pdf-attr-row">
                        <span className="pdf-attr-label">Material / Fabric</span>
                        <div className="pdf-attr-control">
                          <select className="pdf-select" value={selectedMaterial} onChange={(e) => setSelectedMaterial(e.target.value)}>
                            {productData.materials.map((m, idx) => (
                              <option key={idx} value={optionValue(m)}>{optionLabel(m)}</option>
                            ))}
                          </select>
                        </div>
                      </div>
                    )}

                    {productData.type && (
                      <div className="pdf-attr-row">
                        <span className="pdf-attr-label">Type</span>
                        <div className="pdf-attr-control fw-semibold">{productData.type}</div>
                      </div>
                    )}

                    {productData.customization_label_status && productData.printing_location && productData.printing_location.length > 0 && (
                      <div className="pdf-attr-row">
                        <span className="pdf-attr-label">Print Location</span>
                        <div className="pdf-attr-control">
                          <select
                            className="pdf-select"
                            value={selectedLocations[0] || productData.printing_location[0]}
                            onChange={(e) => setSelectedLocations([e.target.value])}
                          >
                            {productData.printing_location.map((loc, idx) => (
                              <option key={idx} value={loc}>{loc}</option>
                            ))}
                          </select>
                        </div>
                      </div>
                    )}

                    {productData.lamination.length > 0 && (
                      <div className="pdf-attr-row">
                        <span className="pdf-attr-label">Lamination</span>
                        <div className="pdf-attr-control fw-semibold">{productData.lamination.join(', ')}</div>
                      </div>
                    )}
                    {productData.orientation.length > 0 && (
                      <div className="pdf-attr-row">
                        <span className="pdf-attr-label">Orientation</span>
                        <div className="pdf-attr-control fw-semibold text-capitalize">{productData.orientation.join(', ')}</div>
                      </div>
                    )}
                    {productData.customization_tech && (
                      <div className="pdf-attr-row">
                        <span className="pdf-attr-label">Printing Technology</span>
                        <div className="pdf-attr-control fw-semibold">{productData.customization_tech}</div>
                      </div>
                    )}

                  </div>

                  {productData.customization_data && productData.customization_data.length > 0 && (
                    <div className="pdd-options__custom">
                      <ProductCustomizationForm
                        fields={productData.customization_data}
                        values={customizationValues}
                        onChange={setCustomizationValues}
                      />
                    </div>
                  )}

                  {/* Quantity comes after the customization options (like printo.in) */}
                  <div className="pdf-attr-table">
                    <div className="pdf-attr-row">
                      <span className="pdf-attr-label">Quantity</span>
                      <div className="pdf-attr-control">
                        {(() => {
                          const hasTiers = productData.bulk_order_pricing.length > 0 || productData.quantity_pricing.length > 0;
                          return (
                            <div className="pdd-qty" ref={tiersDropdownRef}>
                              <input
                                type="number"
                                min={productData.min_quantity || 1}
                                max={productData.stock_quantity || 99999}
                                value={quantity}
                                onChange={(e) => {
                                  const val = Math.max(productData.min_quantity || 1, parseInt(e.target.value) || (productData.min_quantity || 1));
                                  setQuantity(val);
                                }}
                                onFocus={() => hasTiers && setShowTiersDropdown(true)}
                                onClick={() => hasTiers && setShowTiersDropdown(true)}
                              />
                              {hasTiers && (
                                <button
                                  type="button"
                                  className="pdd-qty__caret"
                                  onClick={() => setShowTiersDropdown((v) => !v)}
                                  aria-expanded={showTiersDropdown}
                                  aria-label="Show quantity price tiers"
                                >
                                  <FaChevronDown size={10} className={showTiersDropdown ? 'is-open' : ''} />
                                </button>
                              )}

                              {hasTiers && showTiersDropdown && (
                                <div className="pdd-tiers-dropdown__panel">
                                  <span className="pdd-tiers-dropdown__panel-label">Buy more, save more</span>
                                  <table className="pdd-tiers__table">
                                    <thead>
                                      <tr><th>Quantity</th><th>Price / piece</th></tr>
                                    </thead>
                                    <tbody>
                                      {productData.bulk_order_pricing.map((t, i) => {
                                        const min = Number(t.min_qty ?? t.qty ?? 0);
                                        const max = Number(t.max_qty) > 0 ? Number(t.max_qty) : null;
                                        const active = quantity >= min && (max === null || quantity <= max);
                                        return (
                                          <tr
                                            key={`b${i}`}
                                            className={active ? 'is-active' : ''}
                                            onClick={() => {
                                              setQuantity(max ?? min);
                                              setShowTiersDropdown(false);
                                            }}
                                          >
                                            <td>{max ? `${min} – ${max}` : `${min}+`} pcs</td>
                                            <td>₹{Number(t.price).toLocaleString('en-IN')}</td>
                                          </tr>
                                        );
                                      })}
                                      {productData.bulk_order_pricing.length === 0 && productData.quantity_pricing.map((t, i) => (
                                        <tr
                                          key={`q${i}`}
                                          onClick={() => {
                                            setQuantity(Number(t.qty ?? t.min_qty));
                                            setShowTiersDropdown(false);
                                          }}
                                        >
                                          <td>{Number(t.qty ?? t.min_qty)}+ pcs</td>
                                          <td>₹{Number(t.price).toLocaleString('en-IN')}</td>
                                        </tr>
                                      ))}
                                    </tbody>
                                  </table>
                                </div>
                              )}
                            </div>
                          );
                        })()}
                      </div>
                    </div>
                  </div>

                  {productData.size_status && productData.sizes && productData.sizes.length > 0 && (
                    <div className="mt-3">
                      <span className="pdd-sub-label">Size split up</span>
                      <div className="pdf-size-split-table">
                        <div className="row g-0 text-center">
                          {productData.sizes.map((sz) => {
                            const val = optionValue(sz);
                            return (
                              <div key={val} className="col">
                                <div className="pdf-size-col-head">{optionLabel(sz)}</div>
                                <div className="pdd-size-cell">
                                  <input
                                    type="number"
                                    min="0"
                                    className="pdf-size-input"
                                    value={sizeSplit[val] || ''}
                                    placeholder="0"
                                    onChange={(e) => handleSizeChange(val, e.target.value)}
                                  />
                                </div>
                              </div>
                            );
                          })}
                        </div>
                      </div>
                    </div>
                  )}
                </div>

                {/* 3. Price summary, bulk enquiry, design buttons (tier table moved into Quantity dropdown above) */}
                <div className="pdd-block">
                  <div className="pdd-summary">
                    <div className="pdd-summary__row">
                      <span>Per piece</span>
                      <strong>₹{dynamicUnitPrice.toFixed(0)}</strong>
                    </div>
                    <div className="pdd-summary__row">
                      <span>Quantity</span>
                      <strong>{quantity} {quantity === 1 ? 'pc' : 'pcs'}</strong>
                    </div>
                    {nextPriceTier && (
                      <button
                        type="button"
                        className="pdd-summary__hint"
                        onClick={() => setQuantity(nextPriceTier.qty)}
                        title={`Set quantity to ${nextPriceTier.qty}`}
                      >
                        Buy {nextPriceTier.qty}+ pcs at ₹{nextPriceTier.price.toLocaleString('en-IN')}/pc — save ₹{(dynamicUnitPrice - nextPriceTier.price).toLocaleString('en-IN', { maximumFractionDigits: 0 })} per piece
                      </button>
                    )}
                    <div className="pdd-summary__total">
                      <span>Total</span>
                      <strong>₹{totalPrice.toFixed(2)}</strong>
                    </div>
                  </div>

                  {(productData.enable_upload_design || productData.enable_customizer) && (
                    <>
                      <div className="pdd-design-btns">
                        {productData.enable_upload_design && (
                          <button type="button" className="pdd-design-btn pdd-design-btn--upload" onClick={() => setShowUploadModal(true)}>
                            <FaUpload size={13} /> Upload your design
                          </button>
                        )}
                        {productData.enable_customizer && (
                          <button type="button" className="pdd-design-btn pdd-design-btn--create" onClick={() => navigate(`/customize/${productData.slug || lookupKey}`)}>
                            <FaPencilAlt size={12} /> Create your design
                          </button>
                        )}
                      </div>
                      {(designData?.preview_url || (designData?.images && designData.images.length > 0) || (designData?.texts && designData.texts.length > 0)) && (
                        <div className="pdd-design-summary">
                          <FaCheckCircle />
                          <span>
                            {designData?.preview_url ? 'Custom Visual Design Ready' : [
                              designData?.images?.length ? `${designData.images.length} design${designData.images.length > 1 ? 's' : ''}` : null,
                              designData?.texts?.length ? `${designData.texts.length} text${designData.texts.length > 1 ? 's' : ''}` : null
                            ].filter(Boolean).join(' · ') + ' added'}
                          </span>
                          <button type="button" onClick={() => navigate(`/customize/${productData.slug || lookupKey}`)}>Edit in Studio</button>
                        </div>
                      )}
                    </>
                  )}

                  {productData.show_bulk_form && (
                    <button type="button" className="pdd-bulk-link" onClick={() => setShowBulkModal(true)}>
                      Interested in Bulk Discounts? Enquire Now <FaInfoCircle size={12} />
                    </button>
                  )}
                </div>

                {/* Addon products picked in admin (renders nothing when there are none) */}
                <ProductAddons addonIds={productData.addon_product_ids} />

                {/* 4. Delivery + service badges */}
                <div className="pdd-block">
                  <h2 className="pdd-block__title pdd-block__title--light">Check Delivery Date</h2>
                  <div className="pdd-pincode">
                    <FaMapMarkerAlt className="pdd-pincode__icon" size={16} />
                    <input
                      type="text"
                      placeholder="Enter your city pincode"
                      maxLength={6}
                      value={pincode}
                      onChange={(e) => setPincode(e.target.value.replace(/\D/g, ''))}
                    />
                    <button type="button" onClick={handleCheckPincode}>Check</button>
                  </div>
                  {pincodeMessage && (
                    <div className={`pdd-pincode-msg ${deliveryAvailable ? 'text-success' : 'text-danger'}`}>{pincodeMessage}</div>
                  )}

                  <div className="pdd-services">
                    <div className="pdd-service">
                      <span className="pdd-service__icon"><FaSyncAlt size={16} /></span>
                      {productData.cancel_status === 'yes' ? '10-Day Return' : 'Quality Assured'}
                    </div>
                    <div className="pdd-service">
                      <span className="pdd-service__icon"><FaMoneyBillWave size={16} /></span>
                      {productData.cod_status === 'yes' ? 'Cash on Delivery' : 'Prepaid Only'}
                    </div>
                    <div className="pdd-service">
                      <span className="pdd-service__icon pdd-service__icon--logo">
                        <img src="/Asured.png" alt="" />
                      </span>
                      Printmont Assured
                    </div>
                  </div>
                </div>

                {/* 5. Coupons, coins, offers */}
                <div className="pdd-block">
                  <div className="pdd-row-between">
                    <h2 className="pdd-block__title mb-0">
                      Discount Coupons <span className="pdd-save">Save ₹50</span>
                    </h2>
                    <button type="button" className="pdd-outline-btn" onClick={() => handleCopyCoupon('PRNTM5')}>Apply</button>
                  </div>

                  <Link to="/printmont-coin" className="pdd-coin">
                    <img
                      src="/printmont-coin-offer.png"
                      alt="Printmont Coin: For every ₹100 spent, you earn 2 Printmont Coins. Max 50 coins per order."
                      loading="lazy"
                    />
                  </Link>

                  <div className="pdd-offers-head">
                    <span className="d-inline-flex align-items-center gap-2">
                      <RiDiscountPercentFill size={18} className="text-success" /> All Offers &amp; Coupons
                    </span>
                    <FaChevronRight size={14} />
                  </div>

                  <div className="pdd-offer-list">
                    {bankOffers.map((b, idx) => (
                      <div key={idx} className={`pdd-offer pdd-offer--${b.theme}`}>
                        <span className="pdd-offer__logo">
                          <img src={b.logo} alt={b.name} onError={(e) => { e.target.replaceWith(document.createTextNode(b.name.split(' ')[0])); }} />
                        </span>
                        <div className="flex-grow-1">{b.desc}</div>
                        <FaInfoCircle className="pdd-offer__info flex-shrink-0" size={16} />
                      </div>
                    ))}
                  </div>

                </div>

                {/* 6. About the product */}
                <div className="pdd-block">
                  <h2 className="pdd-block__title">About the product</h2>
                  <div className="pdd-tabs" role="tablist">
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
                        className={`pdd-tab ${activeTab === t.key ? 'is-active' : ''}`}
                        onClick={() => setActiveTab(t.key)}
                      >
                        {t.label}
                      </button>
                    ))}
                  </div>

                  <div className="pdd-tab-body">
                    {activeTab === 'description' && (
                      <>
                        {productData.long_description ? (
                          <div className="product-description-html" dangerouslySetInnerHTML={{ __html: productData.long_description }} />
                        ) : productData.description ? (
                          <div className="product-description-html" dangerouslySetInnerHTML={{ __html: productData.description }} />
                        ) : (
                          <p>Premium quality product crafted with precision.</p>
                        )}

                        <div className="pdd-details">
                          <div className="pdd-details__title">Product Details:</div>
                          <ul className="pdd-details__list">
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
                              ['Print location', productData.printing_location.join(', ')],
                              ['Lamination', productData.lamination.join(', ')],
                              ['Orientation', productData.orientation.map((o) => o.charAt(0).toUpperCase() + o.slice(1)).join(', ')],
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
                        <>
                          <p>Standard courier delivery takes 3-5 business days across India.</p>
                          <ul className="ps-3">
                            {productData.shipping_method_status ? (
                              [
                                ['Local', productData.local_shipping_price, productData.local_shipping_msg],
                                ['Regional', productData.regional_shipping_price, productData.regional_shipping_msg],
                                ['National', productData.national_shipping_price, productData.national_shipping_msg]
                              ].map(([zone, price, msg]) => (
                                <li key={zone}>
                                  <strong>{zone} Shipping:</strong> {price > 0 ? `₹${price}` : 'FREE'}{msg ? ` — ${msg}` : ''}
                                </li>
                              ))
                            ) : (
                              <li><strong>Shipping Charges:</strong> FREE Delivery</li>
                            )}
                            <li><strong>Cash on Delivery:</strong> {productData.cod_status === 'yes' ? 'Available' : 'Prepaid Only'}</li>
                            <li><strong>Returns / Replacement:</strong> {productData.cancel_status === 'yes' ? `${productData.cancel_value} ${productData.cancel_type} replacement policy` : 'Quality Assured'}</li>
                          </ul>
                        </>
                      )
                    )}
                  </div>

                  {/* Admin: Add/Edit Product → section 1, point 13 "Product Information Image & Text Upload" */}
                  <ProductInfoBanners productId={productData?.id} className="pdd-pib" />
                </div>

                {/* 7. Ratings & reviews */}
                <div className="pdd-block">
                  <ProductReview productId={productData?.id} ratingStats={ratingStats} isDesktop categorySlug={productData?.breadcrumb?.[0]?.slug || ''} />
                </div>

                {/* 8. Questions & answers */}
                <div className="pdd-block">
                  <ProductQASection productId={productData?.id} isDesktop />
                </div>
              </div>
            </div>

            {/* Add to cart / Buy now: sticks to the bottom of the screen while the details scroll,
                lined up under the image column */}
            <div className="pdd-cta-bar">
              <div className="pdd-cta">
                {productData.show_cart_buttons === false ? (
                  <button
                    type="button"
                    className="pdd-cta__btn pdd-cta__btn--buy w-100"
                    style={{ background: '#0b53a1', borderColor: '#0b53a1', minHeight: '46px' }}
                    onClick={() => setShowBulkModal(true)}
                  >
                    <FaEnvelope size={16} /> Request Quote / Enquire Now
                  </button>
                ) : (
                  <>
                    {isInCart ? (
                      <button
                        type="button"
                        className="pdd-cta__btn pdd-cta__btn--remove"
                        onClick={() => {
                          if (removeItem) {
                            removeItem(currentProductId);
                            toast.error('Item removed from cart!', { duration: 2000, position: 'top-center' });
                          }
                        }}
                      >
                        <FaTrash size={14} /> Remove from cart
                      </button>
                    ) : (
                      <button
                        type="button"
                        className="pdd-cta__btn pdd-cta__btn--cart"
                        disabled={productData.out_of_stock_status === 'out_of_stock' || productData.stock_quantity <= 0}
                        onClick={handleAddToCart}
                      >
                        <FaShoppingCart size={16} /> Add to cart
                      </button>
                    )}
                    <button
                      type="button"
                      className="pdd-cta__btn pdd-cta__btn--buy"
                      disabled={productData.out_of_stock_status === 'out_of_stock' || productData.stock_quantity <= 0}
                      onClick={handleBuyNow}
                    >
                      <FaBolt size={16} /> Buy now
                    </button>
                  </>
                )}
                {productData.show_inquiry_form && productData.show_cart_buttons !== false && (
                  <button
                    type="button"
                    className="btn btn-outline-primary fw-semibold d-inline-flex align-items-center justify-content-center gap-1 py-2 px-3"
                    style={{ borderRadius: '8px', minHeight: '44px' }}
                    onClick={() => setShowBulkModal(true)}
                  >
                    <FaQuestionCircle size={14} /> Enquire Now
                  </button>
                )}
              </div>
            </div>
          </section>

          {/* ══════════════════════════════════════════════════════════════════
              FULL WIDTH: banner slider + product carousels
          ══════════════════════════════════════════════════════════════════ */}

          {/* Admin: view-banner.php?tab=banners&filter_page=product → "Product Page – Middle Banner" */}
          <div className="pdd-banner">
            <SmallBannerCarousel sectionKey="product_page_middle" page="product" isMobile={false} height={220} />
          </div>

          {/* Product carousels: each renders its own title + "View all"; empty carousels render nothing */}
          <div className="pdd-carousel">
            <ProductCarousel apiUrl={API_ENDPOINTS.TOP_RATED} title="Similar Products" viewAllUrl="/allproducts?sort=Popularity" />
          </div>
          <div className="pdd-carousel">
            <ProductCarousel apiUrl={API_ENDPOINTS.TOP_RATED} title="Discount on Similar Products" viewAllUrl="/allproducts?sort=Discount" />
          </div>
          <div className="pdd-carousel">
            <ProductCarousel apiUrl={API_ENDPOINTS.TOP_SELECTION} title="You might be interested" viewAllUrl="/allproducts" />
          </div>

          <div className="pdd-recent">
            <RecentlyViewedProducts currentProductId={productData?.id} title="Your Recently Viewed" />
          </div>

        </div>
      </div>

      {/* ══════════════════════════════════════════════════════════════════
          MODAL: Add to Cart Success Modal
      ══════════════════════════════════════════════════════════════════ */}
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
                style={{ width: '70px', height: '70px', objectFit: 'contain' }}
                className="rounded border bg-white p-1"
              />
              <div className="flex-grow-1 overflow-hidden">
                <h6 className="fw-bold mb-1 text-dark text-truncate" style={{ maxWidth: '280px' }}>{addedCartDetails.title}</h6>
                <div className="text-muted small">Total Quantity: <span className="fw-semibold text-dark">{addedCartDetails.qty} pcs</span></div>
                <div className="fw-bold text-success fs-6 mt-1">₹{(addedCartDetails.price * addedCartDetails.qty).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
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
              <FaShoppingCart /> View Cart &amp; Checkout
            </button>
          </div>
        </Modal.Body>
      </Modal>

      {/* ══════════════════════════════════════════════════════════════════
          MODAL: Bulk Inquiry Form Modal
      ══════════════════════════════════════════════════════════════════ */}
      <Modal 
        show={showBulkModal} 
        onHide={() => setShowBulkModal(false)} 
        centered
        size="lg"
      >
        <Modal.Header closeButton>
          <Modal.Title className="fw-bold text-dark fs-5 d-flex align-items-center gap-2">
            <FaQuestionCircle className="text-primary" /> Request Bulk Order Quotation
          </Modal.Title>
        </Modal.Header>
        <Modal.Body className="p-4">
          <p className="text-muted small mb-3">
            Looking for orders above 50+ pieces? Submit your requirement for corporate discounts, custom branding, and fast delivery quotes.
          </p>
          <Form onSubmit={handleBulkInquirySubmit}>
            <Row className="g-3">
              <Col md={6}>
                <Form.Group>
                  <Form.Label className="text-xs fw-bold">Full Name *</Form.Label>
                  <Form.Control 
                    type="text" 
                    required 
                    placeholder="Enter your full name" 
                    value={bulkInquiry.name}
                    onChange={e => setBulkInquiry({ ...bulkInquiry, name: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group>
                  <Form.Label className="text-xs fw-bold">Phone Number *</Form.Label>
                  <Form.Control 
                    type="tel" 
                    required 
                    placeholder="10-digit mobile number" 
                    value={bulkInquiry.phone}
                    onChange={e => setBulkInquiry({ ...bulkInquiry, phone: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group>
                  <Form.Label className="text-xs fw-bold">Work Email *</Form.Label>
                  <Form.Control 
                    type="email" 
                    required 
                    placeholder="name@company.com" 
                    value={bulkInquiry.email}
                    onChange={e => setBulkInquiry({ ...bulkInquiry, email: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group>
                  <Form.Label className="text-xs fw-bold">Estimated Quantity (Pieces) *</Form.Label>
                  <Form.Control 
                    type="number" 
                    min="10" 
                    required 
                    placeholder="e.g. 100" 
                    value={bulkInquiry.quantity}
                    onChange={e => setBulkInquiry({ ...bulkInquiry, quantity: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col xs={12}>
                <Form.Group>
                  <Form.Label className="text-xs fw-bold">Customization Requirements / Notes</Form.Label>
                  <Form.Control 
                    as="textarea" 
                    rows={3} 
                    placeholder="Specify printing locations, branding preferences, required delivery date, etc." 
                    value={bulkInquiry.message}
                    onChange={e => setBulkInquiry({ ...bulkInquiry, message: e.target.value })}
                  />
                </Form.Group>
              </Col>
            </Row>
            <div className="d-flex justify-content-end gap-2 mt-4">
              <button type="button" className="btn btn-outline-secondary px-4" onClick={() => setShowBulkModal(false)}>Cancel</button>
              <button type="submit" className="btn btn-primary px-4 fw-bold" disabled={bulkSubmitting}>
                {bulkSubmitting ? <Spinner size="sm" animation="border" /> : 'Submit Inquiry'}
              </button>
            </div>
          </Form>
        </Modal.Body>
      </Modal>

      {/* Upload your design popup (raw file slots) */}
      <DesignUploadModal
        show={showUploadModal}
        initialTab="upload"
        maxImages={productData.upload_design_max_images ?? 4}
        maxTexts={productData.upload_design_max_texts ?? 2}
        value={designData}
        onHide={() => setShowUploadModal(false)}
        onSave={(data) => {
          setDesignData(data);
          const count = (data.images?.length || 0) + (data.texts?.length || 0);
          setUploadedArtwork(count ? { name: `${data.images.length} design(s), ${data.texts.length} text(s)` } : null);
          if (count) toast.success('Your designs have been added');
        }}
      />

      {/* ── Size Guide Modal ── */}
      <Modal show={showSizeChartModal} onHide={() => setShowSizeChartModal(false)} centered size="lg">
        <Modal.Header closeButton>
          <Modal.Title className="fw-bold text-dark fs-5 d-flex align-items-center gap-2">
            <FaRulerHorizontal className="text-primary" /> Size Guide &amp; Measurements
          </Modal.Title>
        </Modal.Header>
        <Modal.Body className="text-center p-4">
          {productData.size_chart_image ? (
            <img
              src={productData.size_chart_image}
              alt="Size Guide"
              className="img-fluid rounded border shadow-sm"
              style={{ maxHeight: '70vh', objectFit: 'contain' }}
            />
          ) : (
            <p className="text-muted">No size chart available for this product.</p>
          )}
        </Modal.Body>
      </Modal>

    </div>
  );
};

export default ProductDetails;
