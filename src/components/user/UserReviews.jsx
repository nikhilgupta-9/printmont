import React, { useState, useEffect } from 'react';
import {
  FaStar,
  FaCoins,
  FaCheckCircle,
  FaClock,
  FaCamera,
  FaPen,
  FaTrashAlt,
  FaShoppingBag,
  FaArrowLeft,
  FaExclamationCircle,
  FaBoxOpen,
  FaShieldAlt,
  FaTimes,
  FaRegThumbsUp,
  FaThumbsUp,
  FaChevronRight,
  FaTag,
  FaCheck
} from 'react-icons/fa';
import { IoClose } from 'react-icons/io5';
import { Spinner, Modal } from 'react-bootstrap';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useAuth } from '../../context/AuthContext';
import { API_ENDPOINTS, resolveImageUrl } from '../../config/apiEndpoints';
import './UserReviews.css';

const RATING_LABELS = {
  1: 'Very Bad',
  2: 'Bad',
  3: 'Good',
  4: 'Very Good',
  5: 'Excellent'
};

const RATING_FEEDBACK = {
  1: { label: 'Poor', text: 'Terrible / Very Disappointed', color: '#b91c1c', bg: '#fef2f2', border: '#fecaca' },
  2: { label: 'Fair', text: 'Below Expectations / Needs Improvement', color: '#c2410c', bg: '#fff7ed', border: '#fed7aa' },
  3: { label: 'Good', text: 'Average / Met Expectations', color: '#b45309', bg: '#fffbeb', border: '#fde68a' },
  4: { label: 'Very Good', text: 'Satisfied / Good Quality', color: '#047857', bg: '#ecfdf5', border: '#a7f3d0' },
  5: { label: 'Excellent', text: 'Loved It / Exceptional Quality!', color: '#15803d', bg: '#f0fdf4', border: '#bbf7d0' }
};

const REVIEW_TAGS = [
  'Print Quality',
  'Paper / Fabric Finish',
  'Vibrant Colors',
  'Fast Delivery',
  'Sturdy Packaging',
  'Value for Money'
];

const OCCASIONS = [
  'General / Personal',
  'Corporate Event',
  'Birthday Gift',
  'Wedding / Anniversary',
  'Festival / Celebration',
  'Business Marketing',
  'Other'
];

const UserReviews = () => {
  const { user, token, getUsernamePath } = useAuth();
  const navigate = useNavigate();
  const usernamePath = getUsernamePath ? getUsernamePath() : 'user';

  const [searchParams] = useSearchParams();
  const targetProductId = searchParams.get('product_id');
  const targetOrderId = searchParams.get('order_id');
  const openReviewParam = searchParams.get('open_review') || searchParams.get('open');
  const tabParam = searchParams.get('tab');

  const [activeTab, setActiveTab] = useState(tabParam === 'reviewed' ? 'reviewed' : 'to_review');
  const [loading, setLoading] = useState(true);
  const [toReview, setToReview] = useState([]);
  const [reviewed, setReviewed] = useState([]);
  const [stats, setStats] = useState({
    to_review_count: 0,
    reviewed_count: 0,
    review_bonus_coins: 25,
    user_coin_balance: 0
  });

  // Modal / Form state
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [editingReviewId, setEditingReviewId] = useState(null);
  const [rating, setRating] = useState(5);
  const [hoverRating, setHoverRating] = useState(0);
  const [title, setTitle] = useState('');
  const [comment, setComment] = useState('');
  const [occasion, setOccasion] = useState(OCCASIONS[0]);
  const [photos, setPhotos] = useState([]);
  const [submitting, setSubmitting] = useState(false);

  // Photo preview lightbox modal
  const [previewPhoto, setPreviewPhoto] = useState(null);

  // Delete confirm modal
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  // Helpful likes state (client-side interactive feedback)
  const [likedReviews, setLikedReviews] = useState({});

  // Fetch reviews on mount
  const fetchReviews = async () => {
    try {
      setLoading(true);
      const authToken = token || localStorage.getItem('token') || sessionStorage.getItem('token');
      if (!authToken) {
        setLoading(false);
        return;
      }

      const res = await fetch(API_ENDPOINTS.USER_REVIEWS || '/api/user-reviews-api.php', {
        headers: {
          'Authorization': `Bearer ${authToken}`
        }
      });

      const data = await res.json();
      if (data && data.success && data.data) {
        setToReview(data.data.to_review || []);
        setReviewed(data.data.reviewed || []);
        if (data.data.stats) {
          setStats(data.data.stats);
        }
      }
    } catch (err) {
      console.error('Error fetching user reviews:', err);
      toast.error('Failed to load your reviews.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReviews();
  }, [token]);

  // Open modal to write a review for a purchased product
  const handleOpenWriteReview = (item, initialRating = 5) => {
    setSelectedProduct(item);
    setEditingReviewId(null);
    setRating(initialRating);
    setHoverRating(0);
    setTitle('');
    setComment('');
    setOccasion(OCCASIONS[0]);
    setPhotos([]);
    setIsModalOpen(true);
  };

  // Open modal to edit an existing review
  const handleOpenEditReview = (rev) => {
    setSelectedProduct({
      product_id: rev.product_id,
      product_name: rev.product_name,
      product_image: rev.product_image,
      order_number: rev.order_number
    });
    setEditingReviewId(rev.id);
    setRating(rev.rating || 5);
    setHoverRating(0);
    setTitle(rev.title || '');
    setComment(rev.comment || '');
    setOccasion(rev.occasion || OCCASIONS[0]);
    setPhotos(Array.isArray(rev.images) ? [...rev.images] : []);
    setIsModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
    setSelectedProduct(null);
    setEditingReviewId(null);
  };

  // Auto-handle query parameters (e.g. from Order details "Write a product review" button)
  useEffect(() => {
    if (loading) return;

    if (tabParam === 'reviewed') {
      setActiveTab('reviewed');
    }

    if (targetProductId) {
      const pidNum = parseInt(targetProductId, 10);
      const unreviewed = toReview.find(i => Number(i.product_id) === pidNum);
      if (unreviewed) {
        setActiveTab('to_review');
        if (openReviewParam !== '0') {
          handleOpenWriteReview(unreviewed, 5);
        }
        return;
      }

      const rev = reviewed.find(r => Number(r.product_id) === pidNum);
      if (rev) {
        setActiveTab('reviewed');
        if (openReviewParam === 'edit' || openReviewParam === '1') {
          handleOpenEditReview(rev);
        }
      }
    }
  }, [loading, targetProductId, openReviewParam, tabParam, toReview, reviewed]);

  // Photo upload handling (up to 3 photos, < 5MB)
  const handlePhotoUpload = (e) => {
    const files = Array.from(e.target.files || []);
    if (photos.length + files.length > 3) {
      toast.error('You can upload a maximum of 3 photos.');
      return;
    }

    files.forEach((file) => {
      if (file.size > 5 * 1024 * 1024) {
        toast.error(`${file.name} is larger than 5MB.`);
        return;
      }
      const reader = new FileReader();
      reader.onloadend = () => {
        setPhotos((prev) => [...prev, reader.result]);
      };
      reader.readAsDataURL(file);
    });
  };

  const handleRemovePhoto = (index) => {
    setPhotos((prev) => prev.filter((_, idx) => idx !== index));
  };

  // Quick tag helper to inspire review text
  const handleTagClick = (tag) => {
    setComment((prev) => {
      const trimmed = prev.trim();
      if (!trimmed) {
        return `Great ${tag.toLowerCase()}! `;
      }
      if (trimmed.toLowerCase().includes(tag.toLowerCase())) {
        return prev;
      }
      return `${trimmed}, ${tag.toLowerCase()}`;
    });
  };

  // Submit review handler
  const handleSubmitReview = async (e) => {
    e.preventDefault();
    if (!comment.trim()) {
      toast.error('Please write a brief description of your feedback.');
      return;
    }

    try {
      setSubmitting(true);
      const authToken = token || localStorage.getItem('token') || sessionStorage.getItem('token');
      const payload = {
        action: 'submit_review',
        product_id: selectedProduct?.product_id,
        rating: Number(rating),
        title: title.trim() || `${RATING_LABELS[rating] || 'Good'} Product`,
        comment: comment.trim(),
        occasion: occasion,
        order_number: selectedProduct?.order_number || '',
        images: photos
      };

      const res = await fetch(API_ENDPOINTS.USER_REVIEWS || '/api/user-reviews-api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(authToken ? { 'Authorization': `Bearer ${authToken}` } : {})
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (data && data.success) {
        toast.success(data.message || 'Review submitted successfully!');
        handleCloseModal();
        setActiveTab('reviewed');
        fetchReviews();
      } else {
        toast.error(data.message || 'Failed to submit review.');
      }
    } catch (err) {
      console.error('Error submitting review:', err);
      toast.error('An error occurred. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  // Delete review handler
  const handleConfirmDelete = async () => {
    if (!deleteTarget) return;

    try {
      setDeleting(true);
      const authToken = token || localStorage.getItem('token') || sessionStorage.getItem('token');
      const res = await fetch(API_ENDPOINTS.USER_REVIEWS || '/api/user-reviews-api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(authToken ? { 'Authorization': `Bearer ${authToken}` } : {})
        },
        body: JSON.stringify({
          action: 'delete_review',
          review_id: deleteTarget.id
        })
      });

      const data = await res.json();
      if (data && data.success) {
        toast.success('Your review was successfully removed.');
        setDeleteTarget(null);
        fetchReviews();
      } else {
        toast.error(data.message || 'Could not delete review.');
      }
    } catch (err) {
      toast.error('Network error while deleting review.');
    } finally {
      setDeleting(false);
    }
  };

  const handleToggleLike = (reviewId) => {
    setLikedReviews(prev => {
      const isLiked = !prev[reviewId];
      if (isLiked) {
        toast.success('Thanks for your feedback!');
      }
      return { ...prev, [reviewId]: isLiked };
    });
  };

  const activeRating = hoverRating || rating;

  // Handle back navigation safely
  const handleGoBack = () => {
    if (window.history.length > 2) {
      navigate(-1);
    } else {
      navigate(`/${usernamePath}/profile`);
    }
  };

  return (
    <div className="user-reviews-page px-0 px-md-0 pb-5">
      {/* ─────────────────────────────────────────────────────────── */}
      {/* 1. Mobile Flipkart-Style Top Navigation Bar (< 992px)       */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="d-lg-none mobile-reviews-topbar mb-3">
        <button
          type="button"
          className="mobile-back-btn"
          onClick={handleGoBack}
          aria-label="Go Back"
        >
          <FaArrowLeft size={15} />
        </button>
        <div className="mobile-topbar-title">
          <h5 className="mb-0 fw-bold">My Reviews &amp; Ratings</h5>
          <span className="mobile-topbar-sub">Rate purchases &amp; earn SuperCoins</span>
        </div>
        <Link
          to={`/${usernamePath}/coins`}
          className="mobile-coin-pill text-decoration-none"
          title="View SuperCoin Balance"
        >
          <FaCoins className="text-warning fs-6" />
          <span>{stats.user_coin_balance}</span>
        </Link>
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 2. Desktop Breadcrumb Navigation (Rule 9: Desktop Only)     */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="reviews-breadcrumb-wrapper d-none d-lg-flex">
        <button 
          type="button" 
          className="reviews-back-link" 
          onClick={() => navigate(`/${usernamePath}/profile`)}
          aria-label="Back to Account"
        >
          <FaArrowLeft size={12} /> Back to My Account
        </button>
        <nav aria-label="breadcrumb">
          <ol className="reviews-breadcrumb">
            <li><Link to="/">Home</Link></li>
            <span>/</span>
            <li><Link to={`/${usernamePath}/profile`}>My Account</Link></li>
            <span>/</span>
            <li className="active" aria-current="page">My Reviews &amp; Ratings</li>
          </ol>
        </nav>
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 3. Executive Desktop Header Card (Theme Aligned)            */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="d-none d-lg-block reviews-header-card p-4 mb-4">
        <div className="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
          <div>
            <div className="d-flex align-items-center gap-2 mb-1">
              <span className="text-warning fs-4">★</span>
              <h4 className="mb-0 fw-bold text-dark">My Reviews &amp; Ratings</h4>
              <span className="badge bg-light text-primary border px-2 py-1 rounded-pill small fw-bold" style={{ borderColor: '#d0e4ff' }}>
                Verified Purchases
              </span>
            </div>
            <p className="text-muted small mb-0">
              Share authentic feedback for products you bought on Printmont, help fellow shoppers, and earn instant SuperCoins!
            </p>
          </div>

          <div className="d-flex align-items-center gap-3 bg-light border px-3 py-2 rounded-4 shadow-xs">
            <div className="d-flex align-items-center gap-2">
              <span className="review-coin-icon-mini"><FaCoins /></span>
              <div className="text-start">
                <div className="fw-bold text-dark small lh-1">+{stats.review_bonus_coins} Coins</div>
                <small className="text-muted" style={{ fontSize: '0.7rem' }}>per review</small>
              </div>
            </div>
            <div className="vr my-1"></div>
            <Link to={`/${usernamePath}/coins`} className="text-decoration-none fw-bold text-primary small">
              Balance: {stats.user_coin_balance} <FaChevronRight size={10} />
            </Link>
          </div>
        </div>
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 4. SuperCoin Reward Alert Banner (Executive Navy Theme)     */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="review-coin-banner">
        <div className="d-flex align-items-center gap-2 gap-sm-3 min-width-0">
          <div className="review-coin-icon-circle flex-shrink-0">
            <FaCoins size={16} />
          </div>
          <div className="min-width-0">
            <div className="fw-bold fs-6 text-white text-truncate">Earn {stats.review_bonus_coins} SuperCoins per Review!</div>
            <div className="small review-banner-subtext">
              Write genuine feedback with photos to help other buyers and earn instant rewards.
            </div>
          </div>
        </div>
        <Link
          to={`/${usernamePath}/coins`}
          className="btn btn-sm btn-review-banner-action text-nowrap flex-shrink-0"
        >
          {stats.user_coin_balance} Coins <FaChevronRight size={10} className="ms-1" />
        </Link>
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 5. Flipkart Segmented Tabs (To Be Reviewed vs Reviewed)     */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="reviews-tabs-nav">
        <button
          type="button"
          className={`reviews-tab-btn ${activeTab === 'to_review' ? 'active' : ''}`}
          onClick={() => setActiveTab('to_review')}
        >
          <FaShoppingBag />
          <span>To Be Reviewed</span>
          <span className="tab-badge">{toReview.length}</span>
        </button>

        <button
          type="button"
          className={`reviews-tab-btn ${activeTab === 'reviewed' ? 'active' : ''}`}
          onClick={() => setActiveTab('reviewed')}
        >
          <FaCheckCircle />
          <span>Reviewed by Me</span>
          <span className="tab-badge">{reviewed.length}</span>
        </button>
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 6. Tab Content                                             */}
      {/* ─────────────────────────────────────────────────────────── */}
      {loading ? (
        <div className="text-center py-5 bg-white rounded-4 shadow-sm border p-4">
          <Spinner animation="border" variant="primary" />
          <p className="mt-3 text-muted small fw-medium">Loading your purchased items...</p>
        </div>
      ) : activeTab === 'to_review' ? (
        /* TAB 1: TO BE REVIEWED (Flipkart-Style Unreviewed Purchases) */
        <div>
          {toReview.length === 0 ? (
            <div className="bg-white rounded-4 border p-4 p-md-5 text-center shadow-sm">
              <div
                className="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                style={{ width: 68, height: 68 }}
              >
                <FaBoxOpen size={30} className="text-muted" />
              </div>
              <h5 className="fw-bold text-dark mb-2">No Pending Reviews</h5>
              <p className="text-muted small mx-auto mb-4" style={{ maxWidth: 440 }}>
                You have reviewed all your recent purchases, or haven't placed an order yet. When you buy any product, you will see it here to rate and review!
              </p>
              <Link to="/allproducts" className="btn btn-primary px-4 py-2 rounded-pill fw-bold">
                Explore Products &amp; Shop Now
              </Link>
            </div>
          ) : (
            <div className="d-flex flex-column gap-3">
              {toReview.map((item) => {
                const productUrl = item.product_slug ? `/product/${item.product_slug}` : '#';
                return (
                  <div key={item.product_id} className="review-item-card">
                    {/* Top Row: Thumbnail + Product Info */}
                    <div className="d-flex align-items-start gap-3 min-width-0">
                      <Link to={productUrl} className="text-decoration-none flex-shrink-0">
                        <img
                          src={resolveImageUrl(item.product_image)}
                          alt={item.product_name}
                          className="review-product-thumb"
                          onError={(e) => { e.target.src = '/default-img.jpg'; }}
                        />
                      </Link>
                      <div className="min-width-0 flex-grow-1">
                        <Link to={productUrl} className="text-decoration-none text-dark">
                          <h6 className="fw-bold product-title-clamp mb-1" title={item.product_name}>
                            {item.product_name}
                          </h6>
                        </Link>
                        <div className="d-flex flex-wrap align-items-center gap-2 text-muted small mb-2" style={{ fontSize: '0.78rem' }}>
                          <span>Order #{item.order_number}</span>
                          <span>•</span>
                          <span>{new Date(item.order_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}</span>
                          {item.order_status && (
                            <>
                              <span>•</span>
                              <span className={`badge ${item.order_status === 'delivered' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border'} rounded-pill text-capitalize`} style={{ fontSize: '0.7rem' }}>
                                {item.order_status}
                              </span>
                            </>
                          )}
                          {item.unit_price > 0 && (
                            <>
                              <span>•</span>
                              <span className="fw-bold text-dark">₹{Number(item.unit_price).toFixed(2)}</span>
                            </>
                          )}
                        </div>
                        <span className="badge bg-light text-primary border px-2 py-1 rounded-pill small fw-semibold" style={{ borderColor: '#d0e4ff' }}>
                          🪙 Earn +{stats.review_bonus_coins} SuperCoins
                        </span>
                      </div>
                    </div>

                    {/* Middle Row: Flipkart Quick Star Rating Prompt */}
                    <div className="review-quick-rate-box">
                      <div className="d-flex align-items-center justify-content-between w-100">
                        <span className="text-dark small fw-bold">Rate this product:</span>
                        <span className="text-muted small" style={{ fontSize: '0.75rem' }}>Tap a star to start</span>
                      </div>
                      <div className="star-rating-hover">
                        {[1, 2, 3, 4, 5].map((star) => (
                          <div 
                            key={star} 
                            className="star-item-wrapper"
                            onClick={() => handleOpenWriteReview(item, star)}
                            title={`Rate ${star} star (${RATING_LABELS[star]})`}
                          >
                            <FaStar
                              size={28}
                              className="hover-star"
                              style={{ color: '#cbd5e1' }}
                            />
                            <span className="star-sub-label">{star}★</span>
                          </div>
                        ))}
                      </div>
                    </div>

                    {/* Bottom Row: Write a Product Review Button (Website Pattern) */}
                    <button
                      type="button"
                      className="user-reviews-btn-write"
                      onClick={() => handleOpenWriteReview(item, 5)}
                    >
                      <FaPen size={12} /> Write a product review
                    </button>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      ) : (
        /* TAB 2: REVIEWED PRODUCTS (Flipkart-Style Verified Review Cards) */
        <div>
          {reviewed.length === 0 ? (
            <div className="bg-white rounded-4 border p-4 p-md-5 text-center shadow-sm">
              <div
                className="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                style={{ width: 68, height: 68 }}
              >
                <FaStar size={30} className="text-warning" />
              </div>
              <h5 className="fw-bold text-dark mb-2">No Reviews Submitted Yet</h5>
              <p className="text-muted small mx-auto mb-4" style={{ maxWidth: 440 }}>
                You haven't reviewed any products yet. Review your purchased products to help other shoppers and earn SuperCoins!
              </p>
              {toReview.length > 0 && (
                <button
                  type="button"
                  className="btn btn-primary px-4 py-2 rounded-pill fw-bold"
                  onClick={() => setActiveTab('to_review')}
                >
                  Review Purchased Products ({toReview.length})
                </button>
              )}
            </div>
          ) : (
            <div className="d-flex flex-column gap-3">
              {reviewed.map((rev) => {
                const productUrl = rev.product_slug ? `/product/${rev.product_slug}` : '#';
                const isHighRating = Number(rev.rating) >= 4;
                const isMidRating = Number(rev.rating) === 3;
                const ratingClass = isHighRating ? 'rating-high' : isMidRating ? 'rating-mid' : 'rating-low';
                const isLiked = Boolean(likedReviews[rev.id]);

                return (
                  <div key={rev.id} className="review-item-card flipkart-review-card">
                    {/* Header: Product Thumbnail + Title + Status Pill */}
                    <div className="d-flex align-items-start gap-3 min-width-0 mb-3 pb-2 border-bottom">
                      <Link to={productUrl} className="text-decoration-none flex-shrink-0">
                        <img
                          src={resolveImageUrl(rev.product_image)}
                          alt={rev.product_name}
                          className="review-product-thumb"
                          onError={(e) => { e.target.src = '/default-img.jpg'; }}
                        />
                      </Link>
                      <div className="min-width-0 flex-grow-1">
                        <div className="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-1">
                          <Link to={productUrl} className="text-decoration-none text-dark flex-grow-1">
                            <h6 className="fw-bold mb-0 product-title-clamp">
                              {rev.product_name}
                            </h6>
                          </Link>
                        </div>
                        {/* Status badge */}
                        <div className="d-flex align-items-center gap-2 mt-1">
                          {rev.status === 'approved' ? (
                            <span className="status-pill status-pill-approved">
                              <FaCheckCircle size={10} /> Verified &amp; Published
                            </span>
                          ) : rev.status === 'rejected' ? (
                            <span className="status-pill status-pill-rejected">
                              <FaExclamationCircle size={10} /> Needs Revision
                            </span>
                          ) : (
                            <span className="status-pill status-pill-pending">
                              <FaClock size={10} /> Under Moderation
                            </span>
                          )}
                          {rev.order_number && (
                            <span className="text-muted small" style={{ fontSize: '0.74rem' }}>
                              Order #{rev.order_number}
                            </span>
                          )}
                        </div>
                      </div>
                    </div>

                    {/* Printmont Brand Star Rating & Headline */}
                    <div className="d-flex align-items-center gap-2 my-2">
                      <span className="pm-rating-badge">
                        {rev.rating} <FaStar size={10} />
                      </span>
                      <span className="fw-bold text-dark fs-6 text-truncate">{rev.title}</span>
                    </div>

                    {/* Review comment body */}
                    <p className="flipkart-review-text mb-2">
                      {rev.comment}
                    </p>

                    {/* Customer Photos Strip */}
                    {Array.isArray(rev.images) && rev.images.length > 0 && (
                      <div className="d-flex flex-wrap gap-2 my-2 overflow-x-auto pb-1">
                        {rev.images.map((imgUrl, i) => (
                          <div key={i} className="position-relative">
                            <img
                              src={resolveImageUrl(imgUrl)}
                              alt={`Customer photo ${i + 1}`}
                              className="review-photo-pill"
                              onClick={() => setPreviewPhoto(resolveImageUrl(imgUrl))}
                              title="Click to preview full image"
                            />
                          </div>
                        ))}
                      </div>
                    )}

                    {/* Admin Response Box */}
                    {rev.admin_reply && (
                      <div className="p-3 bg-light rounded-3 border-start border-primary border-3 my-2 small">
                        <div className="fw-bold text-primary mb-1">Response from Printmont Team:</div>
                        <div className="text-muted">{rev.admin_reply}</div>
                      </div>
                    )}

                    {/* Flipkart Certified Buyer & Date Metadata */}
                    <div className="flipkart-review-meta mt-2 pt-2 border-top">
                      <div className="d-flex flex-wrap align-items-center gap-2 small text-muted">
                        <span className="pm-certified-badge">
                          <FaCheckCircle className="text-primary me-1" size={11} /> Certified Buyer
                        </span>
                        <span>•</span>
                        <span>{new Date(rev.created_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}</span>
                        {rev.occasion && (
                          <>
                            <span>•</span>
                            <span className="badge bg-light text-secondary border">{rev.occasion}</span>
                          </>
                        )}
                      </div>

                      {/* Helpful Reaction & Edit/Delete Actions */}
                      <div className="d-flex align-items-center gap-2 mt-2 mt-sm-0">
                        <button
                          type="button"
                          className={`btn-helpful ${isLiked ? 'is-liked' : ''}`}
                          onClick={() => handleToggleLike(rev.id)}
                          title="Was this review helpful?"
                        >
                          {isLiked ? <FaThumbsUp size={12} /> : <FaRegThumbsUp size={12} />}
                          <span>{isLiked ? 'Helpful (1)' : 'Helpful'}</span>
                        </button>

                        <div className="review-action-btn-group">
                          <button
                            type="button"
                            className="review-btn-edit"
                            onClick={() => handleOpenEditReview(rev)}
                            title="Edit your review and rating"
                          >
                            <FaPen size={11} />
                            <span>Edit</span>
                          </button>
                          <button
                            type="button"
                            className="review-btn-delete"
                            title="Delete this review"
                            onClick={() => setDeleteTarget(rev)}
                          >
                            <FaTrashAlt size={11} />
                            <span>Delete</span>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 7. Product-Grade Review Creation/Edit Modal & Bottom Sheet */}
      {/* ─────────────────────────────────────────────────────────── */}
      {isModalOpen && selectedProduct && (
        <div className="review-modal-backdrop" onClick={handleCloseModal}>
          <div className="review-modal-sheet" onClick={(e) => e.stopPropagation()}>
            {/* Top Brand Accent Line */}
            <div className="review-modal-top-accent"></div>

            {/* Mobile Sheet Drag Handle */}
            <div className="sheet-drag-handle"></div>

            {/* Modal Header */}
            <div className="review-modal-header">
              <div className="d-flex align-items-center gap-2">
                <div className="review-modal-header-icon">
                  {editingReviewId ? <FaPen size={13} /> : <FaStar size={15} />}
                </div>
                <div>
                  <h5 className="review-modal-title mb-0">
                    {editingReviewId ? 'Edit Your Review' : 'Rate & Review Product'}
                  </h5>
                  <p className="review-modal-subtitle mb-0">
                    Share your genuine feedback to help other buyers
                  </p>
                </div>
              </div>
              <button
                type="button"
                className="review-modal-close-btn"
                onClick={handleCloseModal}
                aria-label="Close review dialog"
              >
                <IoClose size={20} />
              </button>
            </div>

            {/* Modal Form */}
            <form onSubmit={handleSubmitReview} className="d-flex flex-column flex-grow-1 overflow-hidden">
              <div className="review-modal-body flex-grow-1">
                {/* Product Summary Card */}
                <div className="review-product-summary-card">
                  <img
                    src={resolveImageUrl(selectedProduct.product_image)}
                    alt={selectedProduct.product_name}
                    className="review-product-summary-img"
                    onError={(e) => { e.target.src = '/default-img.jpg'; }}
                  />
                  <div className="review-product-summary-info">
                    <h6 className="review-product-summary-title">
                      {selectedProduct.product_name}
                    </h6>
                    <div className="review-product-summary-meta">
                      {selectedProduct.order_number && (
                        <span className="review-order-pill">
                          <FaBoxOpen size={11} className="me-1" /> Order #{selectedProduct.order_number}
                        </span>
                      )}
                      <span className="review-verified-pill">
                        <FaCheckCircle size={10} className="me-1" /> Verified Purchase
                      </span>
                    </div>
                  </div>
                </div>

                {/* 1. Interactive Star Rating Card */}
                <div className="review-rating-interactive-box">
                  <div className="review-rating-prompt">
                    <span className="review-section-label">
                      Overall Rating <span className="text-danger">*</span>
                    </span>
                    <span className="review-rating-hint">Tap a star to rate</span>
                  </div>

                  <div className="review-stars-row">
                    {[1, 2, 3, 4, 5].map((star) => {
                      const isActive = star <= activeRating;
                      return (
                        <button
                          type="button"
                          key={star}
                          className={`review-star-btn ${isActive ? 'is-active' : ''}`}
                          onMouseEnter={() => setHoverRating(star)}
                          onMouseLeave={() => setHoverRating(0)}
                          onClick={() => setRating(star)}
                          aria-label={`Rate ${star} star out of 5`}
                        >
                          <FaStar size={34} />
                        </button>
                      );
                    })}
                  </div>

                  {/* Dynamic Rating Feedback Pill */}
                  <div
                    className="review-rating-feedback-pill"
                    style={{
                      color: (RATING_FEEDBACK[activeRating] || RATING_FEEDBACK[5]).color,
                      backgroundColor: (RATING_FEEDBACK[activeRating] || RATING_FEEDBACK[5]).bg,
                      borderColor: (RATING_FEEDBACK[activeRating] || RATING_FEEDBACK[5]).border
                    }}
                  >
                    <span className="fw-bold me-1">{activeRating}.0 ★</span>
                    <span>{(RATING_FEEDBACK[activeRating] || RATING_FEEDBACK[5]).text}</span>
                  </div>
                </div>

                {/* 2. Occasion / Purpose Chips */}
                <div className="review-form-group mb-3">
                  <div className="d-flex justify-content-between align-items-center mb-1">
                    <label className="review-form-label mb-0">What was this purchase for?</label>
                    <span className="review-optional-text">Optional</span>
                  </div>
                  <div className="review-occasions-grid">
                    {OCCASIONS.map((occ) => {
                      const isSelected = occasion === occ;
                      return (
                        <button
                          type="button"
                          key={occ}
                          className={`review-occasion-chip ${isSelected ? 'active' : ''}`}
                          onClick={() => setOccasion(occ)}
                        >
                          {isSelected && <FaCheck size={9} className="me-1" />}
                          <span>{occ}</span>
                        </button>
                      );
                    })}
                  </div>
                </div>

                {/* 3. Headline / Summary */}
                <div className="review-form-group mb-3">
                  <div className="d-flex justify-content-between align-items-center mb-1">
                    <label className="review-form-label mb-0">Headline / Summary</label>
                    <span className="review-char-count">{title.length}/100</span>
                  </div>
                  <input
                    type="text"
                    className="form-control review-input"
                    placeholder="e.g. Exceptional print finish & prompt delivery!"
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    maxLength={100}
                  />
                </div>

                {/* 4. Detailed Feedback Comment */}
                <div className="review-form-group mb-3">
                  <div className="d-flex justify-content-between align-items-center mb-1">
                    <label className="review-form-label mb-0">
                      Detailed Review <span className="text-danger">*</span>
                    </label>
                    <span className="review-char-count">{comment.length}/1000</span>
                  </div>

                  {/* Quick Inspiration Tag Chips */}
                  <div className="review-quick-tags-container mb-2">
                    <span className="review-quick-tags-label">Quick topics:</span>
                    <div className="d-flex flex-wrap gap-1">
                      {REVIEW_TAGS.map((tag) => (
                        <button
                          type="button"
                          key={tag}
                          className="review-quick-tag-pill"
                          onClick={() => handleTagClick(tag)}
                          title={`Add "${tag}" into review`}
                        >
                          + {tag}
                        </button>
                      ))}
                    </div>
                  </div>

                  <textarea
                    rows={4}
                    className="form-control review-textarea"
                    placeholder="Share details about the print clarity, colors, paper/material durability, and packaging..."
                    value={comment}
                    onChange={(e) => setComment(e.target.value)}
                    required
                    maxLength={1000}
                  />
                  <div className="review-helper-note">
                    Helpful reviews mention product quality, colors, texture, packaging, or customer experience.
                  </div>
                </div>

                {/* 5. Photo Upload */}
                <div className="review-form-group mb-3">
                  <div className="d-flex justify-content-between align-items-center mb-2">
                    <label className="review-form-label mb-0">
                      Add Photos of Product <span className="review-optional-text">Optional</span>
                    </label>
                    <span className="review-char-count">{photos.length}/3 photos</span>
                  </div>

                  <div className="review-photos-dropzone-row">
                    {photos.map((src, index) => (
                      <div key={index} className="review-photo-preview-card">
                        <img
                          src={resolveImageUrl(src)}
                          alt={`Customer uploaded photo ${index + 1}`}
                          className="review-photo-preview-img"
                          onClick={() => setPreviewPhoto(resolveImageUrl(src))}
                          title="Click to view full image"
                        />
                        <button
                          type="button"
                          className="review-photo-remove-btn"
                          onClick={() => handleRemovePhoto(index)}
                          title="Remove photo"
                          aria-label="Remove photo"
                        >
                          <IoClose size={13} />
                        </button>
                        <span className="review-photo-index-tag">Photo {index + 1}</span>
                      </div>
                    ))}

                    {photos.length < 3 && (
                      <label className="review-photo-add-box" title="Upload a photo (PNG, JPG up to 5MB)">
                        <div className="review-photo-add-icon">
                          <FaCamera size={18} />
                        </div>
                        <span className="review-photo-add-title">+ Add Photo</span>
                        <span className="review-photo-add-subtitle">Max 5MB each</span>
                        <input
                          type="file"
                          accept="image/*"
                          className="d-none"
                          multiple
                          onChange={handlePhotoUpload}
                        />
                      </label>
                    )}
                  </div>
                </div>

                {/* 6. Rewards Incentive Banner */}
                <div className="review-reward-incentive-box">
                  <div className="review-reward-icon-wrapper">
                    <FaCoins size={18} />
                  </div>
                  <div className="review-reward-text">
                    <div className="review-reward-title">
                      Earn <strong className="text-primary">+{stats.review_bonus_coins} SuperCoins</strong>
                    </div>
                    <div className="review-reward-subtitle">
                      SuperCoins will be credited to your wallet once approved by our verification team.
                    </div>
                  </div>
                </div>
              </div>

              {/* Sticky Submit Footer */}
              <div className="review-modal-footer">
                <button
                  type="button"
                  className="user-reviews-btn-cancel"
                  onClick={handleCloseModal}
                  disabled={submitting}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="user-reviews-btn-submit"
                  disabled={submitting || !comment.trim()}
                >
                  {submitting ? (
                    <>
                      <Spinner animation="border" size="sm" /> Submitting...
                    </>
                  ) : editingReviewId ? (
                    <>Update Review</>
                  ) : (
                    <>Submit Review</>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 8. Photo Lightbox Modal                                     */}
      {/* ─────────────────────────────────────────────────────────── */}
      {previewPhoto && (
        <div className="review-lightbox-backdrop" onClick={() => setPreviewPhoto(null)}>
          <div className="review-lightbox-dialog" onClick={(e) => e.stopPropagation()}>
            <img
              src={previewPhoto}
              alt="Review preview"
              className="review-lightbox-img"
            />
            <button
              type="button"
              className="review-lightbox-close-btn"
              onClick={() => setPreviewPhoto(null)}
              aria-label="Close photo preview"
            >
              <IoClose size={20} />
            </button>
          </div>
        </div>
      )}

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 9. Delete Confirmation Modal                                */}
      {/* ─────────────────────────────────────────────────────────── */}
      {deleteTarget && (
        <Modal show={true} onHide={() => setDeleteTarget(null)} centered contentClassName="rounded-4 border-0 shadow-lg overflow-hidden">
          <div className="p-4 text-center">
            <div className="delete-confirm-icon-box">
              <FaTrashAlt size={22} />
            </div>
            <h5 className="fw-bold text-dark mb-2">Delete Your Review?</h5>
            <p className="text-secondary small mb-3">
              Are you sure you want to delete your review for <strong className="text-dark">{deleteTarget.product_name}</strong>?
            </p>

            <div className="delete-confirm-warning-box text-start">
              <FaExclamationCircle size={16} className="flex-shrink-0 mt-1 text-danger" />
              <div>
                Your submitted star rating, detailed feedback, and uploaded photos will be permanently removed.
              </div>
            </div>

            <div className="d-flex align-items-center justify-content-center gap-2 mt-4 pt-2">
              <button
                type="button"
                className="btn btn-light rounded-pill px-4 py-2 fw-semibold text-secondary"
                onClick={() => setDeleteTarget(null)}
                disabled={deleting}
              >
                Cancel &amp; Keep
              </button>
              <button
                type="button"
                className="btn btn-danger rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center gap-2 shadow-xs"
                onClick={handleConfirmDelete}
                disabled={deleting}
                style={{ backgroundColor: '#dc2626', borderColor: '#dc2626' }}
              >
                {deleting ? (
                  <>
                    <Spinner animation="border" size="sm" /> Deleting...
                  </>
                ) : (
                  <>
                    <FaTrashAlt size={12} /> Yes, Delete Review
                  </>
                )}
              </button>
            </div>
          </div>
        </Modal>
      )}
    </div>
  );
};

export default UserReviews;
