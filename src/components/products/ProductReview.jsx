import React, { useEffect, useState, useRef } from "react";
import { Link, useNavigate, useParams, useLocation } from "react-router-dom";
import { Card, Form, Button, Spinner, Modal, Alert } from "react-bootstrap";
import {
  FaStar,
  FaCheckCircle,
  FaThumbsUp,
  FaThumbsDown,
  FaCamera,
  FaChevronDown,
  FaChevronUp,
  FaChevronLeft,
  FaChevronRight,
  FaPen,
  FaTimes,
  FaTimesCircle
} from "react-icons/fa";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import { useAuth } from "../../context/AuthContext";
import toast from "react-hot-toast";
import "./ProductReview.css";

const ProductReview = ({ productId, ratingStats, isDesktop = false, categorySlug = "" }) => {
  const { user, token } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const { productSlug } = useParams();

  // Only logged-in customers can rate; guests are sent to login and brought back here
  const goToLogin = () => {
    toast.error("Please login to rate this product");
    navigate(`/login?redirect=${encodeURIComponent(location.pathname + location.search)}`);
  };
  const openWriteReview = () => {
    if (!user || !token) {
      goToLogin();
      return;
    }
    setShowWriteModal(true);
  };

  const [reviews, setReviews] = useState([]);
  const [stats, setStats] = useState({ average: 4.8, total: 6 });
  const [loading, setLoading] = useState(true);
  const [isOpen, setIsOpen] = useState(true);

  // Gallery / Lightbox state
  const [activePhotoModal, setActivePhotoModal] = useState(false);
  const [selectedPhotoIndex, setSelectedPhotoIndex] = useState(0);

  // Write Review Modal
  const [showWriteModal, setShowWriteModal] = useState(false);
  const [form, setForm] = useState({
    customer_name: "",
    customer_email: "",
    rating: 5,
    title: "",
    comment: "",
    occasion: "Personal Use",
    city: "Delhi"
  });
  const [uploadedImages, setUploadedImages] = useState([]);
  const [submitting, setSubmitting] = useState(false);
  const [submitSuccessMsg, setSubmitSuccessMsg] = useState("");
  const [submitErrorMsg, setSubmitErrorMsg] = useState("");
  const fileInputRef = useRef(null);

  // User Reactions (Likes / Dislikes)
  const [userReactions, setUserReactions] = useState(() => {
    try {
      const saved = localStorage.getItem("printmont_review_reactions");
      return saved ? JSON.parse(saved) : {};
    } catch {
      return {};
    }
  });

  // Pre-fill user data if logged in
  useEffect(() => {
    if (user) {
      const fullName = user.firstName
        ? `${user.firstName} ${user.lastName || ""}`.trim()
        : user.name || "";
      setForm((prev) => ({
        ...prev,
        customer_name: fullName || prev.customer_name,
        customer_email: user.email || prev.customer_email
      }));
    }
  }, [user]);

  // Fetch Reviews
  useEffect(() => {
    if (!productId) {
      setLoading(false);
      return;
    }
    let cancelled = false;
    setLoading(true);

    const fetchUrl = API_ENDPOINTS.PRODUCT_REVIEWS(productId);
    fetch(fetchUrl)
      .then((res) => res.json())
      .then((res) => {
        if (cancelled) return;
        if (res && res.success) {
          const list = Array.isArray(res.data) ? res.data : (res.reviews || []);
          setReviews(list);
          if (res.stats) {
            setStats(res.stats);
          }
        }
      })
      .catch((err) => {
        if (!cancelled) console.warn("Failed to load reviews:", err);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [productId]);

  // Extract all customer photos from reviews
  const allCustomerPhotos = [];
  reviews.forEach((r) => {
    if (Array.isArray(r.images) && r.images.length > 0) {
      r.images.forEach((img) => {
        if (img && typeof img === "string" && !allCustomerPhotos.includes(img)) {
          allCustomerPhotos.push(img);
        }
      });
    }
  });

  // Only real customer photos are shown; the grid is hidden when there are none
  const galleryPhotos = allCustomerPhotos;
  const hideBrokenImage = (e) => {
    e.currentTarget.style.visibility = "hidden";
  };

  // Review cards row: arrow buttons on desktop
  const cardsRef = useRef(null);
  const [cardsScroll, setCardsScroll] = useState({ prev: false, next: false });
  const updateCardsScroll = () => {
    const el = cardsRef.current;
    if (!el) return;
    setCardsScroll({
      prev: el.scrollLeft > 4,
      next: el.scrollLeft + el.clientWidth < el.scrollWidth - 4
    });
  };
  const scrollCards = (dir) => {
    const el = cardsRef.current;
    if (el) el.scrollBy({ left: dir * el.clientWidth * 0.75, behavior: "smooth" });
  };
  useEffect(() => {
    updateCardsScroll();
    window.addEventListener("resize", updateCardsScroll);
    return () => window.removeEventListener("resize", updateCardsScroll);
  }, [reviews.length, loading]);

  // Features Loved Tags
  const featureTags = [
    "Fabric Quality",
    "Colour",
    "Style",
    "Comfort",
    "True to Specs",
    "Value for Money",
    "Safe Packaging"
  ];

  // Helper: Rating description
  const getRatingLabel = (avg) => {
    const num = parseFloat(avg) || 4.5;
    if (num >= 4.5) return "Very Good";
    if (num >= 4.0) return "Good";
    if (num >= 3.0) return "Average";
    return "Fair";
  };

  // Like handler
  const handleLike = async (reviewId) => {
    const currentReaction = userReactions[reviewId];
    const newReaction = currentReaction === "like" ? null : "like";

    setReviews((prev) =>
      prev.map((r) => {
        if (r.id === reviewId) {
          let likes = parseInt(r.likes ?? r.helpful_votes ?? 0, 10);
          let dislikes = parseInt(r.dislikes ?? r.unhelpful_votes ?? 0, 10);

          if (currentReaction === "like") {
            likes = Math.max(0, likes - 1);
          } else if (currentReaction === "dislike") {
            likes += 1;
            dislikes = Math.max(0, dislikes - 1);
          } else {
            likes += 1;
          }
          return { ...r, likes, dislikes, helpful_votes: likes, unhelpful_votes: dislikes };
        }
        return r;
      })
    );

    const updated = { ...userReactions, [reviewId]: newReaction };
    setUserReactions(updated);
    try {
      localStorage.setItem("printmont_review_reactions", JSON.stringify(updated));
    } catch (e) {}

    if (newReaction === "like") {
      try {
        await fetch(API_ENDPOINTS.SUBMIT_REVIEW, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "reaction", review_id: reviewId, type: "like" })
        });
      } catch (e) {}
    }
  };

  // Dislike handler
  const handleDislike = async (reviewId) => {
    const currentReaction = userReactions[reviewId];
    const newReaction = currentReaction === "dislike" ? null : "dislike";

    setReviews((prev) =>
      prev.map((r) => {
        if (r.id === reviewId) {
          let likes = parseInt(r.likes ?? r.helpful_votes ?? 0, 10);
          let dislikes = parseInt(r.dislikes ?? r.unhelpful_votes ?? 0, 10);

          if (currentReaction === "dislike") {
            dislikes = Math.max(0, dislikes - 1);
          } else if (currentReaction === "like") {
            dislikes += 1;
            likes = Math.max(0, likes - 1);
          } else {
            dislikes += 1;
          }
          return { ...r, likes, dislikes, helpful_votes: likes, unhelpful_votes: dislikes };
        }
        return r;
      })
    );

    const updated = { ...userReactions, [reviewId]: newReaction };
    setUserReactions(updated);
    try {
      localStorage.setItem("printmont_review_reactions", JSON.stringify(updated));
    } catch (e) {}

    if (newReaction === "dislike") {
      try {
        await fetch(API_ENDPOINTS.SUBMIT_REVIEW, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ action: "reaction", review_id: reviewId, type: "dislike" })
        });
      } catch (e) {}
    }
  };

  // Image Upload Handling
  const handleImageChange = (e) => {
    const files = Array.from(e.target.files || []);
    if (!files.length) return;

    files.forEach((file) => {
      const reader = new FileReader();
      reader.onload = (event) => {
        setUploadedImages((prev) => [
          ...prev,
          {
            file,
            preview: URL.createObjectURL(file),
            base64: event.target.result
          }
        ]);
      };
      reader.readAsDataURL(file);
    });

    if (fileInputRef.current) {
      fileInputRef.current.value = "";
    }
  };

  const handleRemoveImage = (index) => {
    setUploadedImages((prev) => prev.filter((_, i) => i !== index));
  };

  // Submit Review Handler
  const handleSubmitReview = async (e) => {
    e.preventDefault();
    if (!form.customer_name.trim() || !form.comment.trim()) {
      setSubmitErrorMsg("Please enter your name and review comment.");
      return;
    }

    setSubmitting(true);
    setSubmitSuccessMsg("");
    setSubmitErrorMsg("");

    try {
      const payload = {
        product_id: productId,
        user_id: user?.id || null,
        customer_name: form.customer_name.trim(),
        customer_email: form.customer_email.trim(),
        rating: form.rating,
        title: form.title.trim() || form.comment.slice(0, 30),
        comment: form.comment.trim(),
        occasion: form.occasion,
        city: form.city,
        images: uploadedImages.map((img) => img.base64)
      };

      const res = await fetch(API_ENDPOINTS.SUBMIT_REVIEW, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          ...(token ? { Authorization: `Bearer ${token}` } : {})
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (data && (data.success || data.status === "success")) {
        setSubmitSuccessMsg(
          data.message ||
            "Thank you! Your review has been submitted for approval."
        );
        toast.success("Review submitted successfully!");

        // Add local preview item
        const newLocalItem = {
          id: Date.now(),
          customer_name: form.customer_name.trim(),
          rating: form.rating,
          title: form.title.trim() || "Verified Buyer Review",
          comment: form.comment.trim(),
          time_ago: "Just now",
          is_verified: 1,
          likes: 0,
          dislikes: 0,
          images: uploadedImages.map((img) => img.preview || img.base64)
        };
        setReviews((prev) => [newLocalItem, ...prev]);

        setTimeout(() => {
          setShowWriteModal(false);
          setSubmitSuccessMsg("");
          setUploadedImages([]);
          setForm((prev) => ({ ...prev, title: "", comment: "" }));
        }, 2200);
      } else if (res.status === 401 || data?.require_login) {
        setShowWriteModal(false);
        goToLogin();
      } else {
        setSubmitErrorMsg(data?.message || data?.error || "Failed to submit review.");
      }
    } catch (err) {
      setSubmitErrorMsg("Network error. Please try again later.");
    } finally {
      setSubmitting(false);
    }
  };

  const avgRating = stats.average ? parseFloat(stats.average).toFixed(1) : "4.8";
  const totalRatingsCount = stats.total || reviews.length || "6,388";

  // All reviews live on the main-category reviews page; ?from keeps the product for the summary card
  const reviewsPageUrl = categorySlug
    ? `/category/${categorySlug}/reviews${productId ? `?from=${productId}` : ""}`
    : `/product/${productSlug || productId}/reviews`;
  const visiblePhotos = galleryPhotos.slice(0, 5);
  const extraPhotos = galleryPhotos.length - visiblePhotos.length;
  const openPhoto = (idx) => {
    setSelectedPhotoIndex(idx);
    setActivePhotoModal(true);
  };

  return (
    <div className={`prr product-ratings-reviews-wrapper ${isDesktop ? "prr--desktop" : ""}`}>
      {/* ===== Header ===== */}
      <div className="prr__head">
        <h2 className="prr__heading" onClick={() => setIsOpen(!isOpen)}>Ratings and reviews</h2>
        <div className="prr__head-actions">
          <button type="button" className="prr__write-btn" onClick={openWriteReview}>
            <FaPen size={10} /> Rate product
          </button>
          <button
            type="button"
            className="prr__toggle"
            onClick={() => setIsOpen(!isOpen)}
            aria-label={isOpen ? "Collapse ratings" : "Expand ratings"}
          >
            {isOpen ? <FaChevronUp size={12} /> : <FaChevronDown size={12} />}
          </button>
        </div>
      </div>

      {isOpen && (
        <div className="prr__body">
          {loading ? (
            <div className="text-center py-4 text-muted small">
              <Spinner animation="border" size="sm" className="me-2 text-success" />
              Loading ratings & customer photos...
            </div>
          ) : (
            <>
              {/* ===== 1. Summary ===== */}
              <div className="prr__score">
                <span className="prr__score-num">
                  {avgRating}
                  <FaStar size={isDesktop ? 15 : 13} />
                </span>
                <span className="prr__label">{getRatingLabel(avgRating)}</span>
              </div>
              <div className="prr__based">
                <span>
                  based on{" "}
                  {typeof totalRatingsCount === "number"
                    ? totalRatingsCount.toLocaleString("en-IN")
                    : totalRatingsCount}{" "}
                  ratings by
                </span>
                <span className="d-inline-flex align-items-center gap-1">
                  <FaCheckCircle size={10} /> Verified Buyers
                </span>
              </div>

              {/* ===== 2. Customer photos: 1 large + 2x2 ===== */}
              {visiblePhotos.length > 0 && (
                <div className={`prr__photos ${visiblePhotos.length === 1 ? "prr__photos--single" : ""}`}>
                  {visiblePhotos.map((imgUrl, i) => {
                    const showMore = i === visiblePhotos.length - 1 && extraPhotos > 0;
                    return (
                      <button
                        type="button"
                        key={`${imgUrl}-${i}`}
                        className={`prr__photo ${i === 0 ? "prr__photo--main" : ""}`}
                        onClick={() => openPhoto(i)}
                      >
                        <img src={imgUrl} alt={`Customer photo ${i + 1}`} loading="lazy" onError={hideBrokenImage} />
                        {showMore && <span className="prr__photo-more">+{extraPhotos}</span>}
                      </button>
                    );
                  })}
                </div>
              )}

              {/* ===== 3. Feature chips ===== */}
              <div className="prr__chips">
                {featureTags.map((tag) => (
                  <span key={tag} className="prr__chip">{tag}</span>
                ))}
              </div>

              {/* ===== 4. Review cards ===== */}
              {reviews.length > 0 ? (
                <div className="prr__cards-wrap">
                  {isDesktop && cardsScroll.prev && (
                    <button type="button" className="prr__arrow prr__arrow--prev" onClick={() => scrollCards(-1)} aria-label="Previous reviews">
                      <FaChevronLeft size={13} />
                    </button>
                  )}
                  <div className="prr__cards" ref={cardsRef} onScroll={updateCardsScroll}>
                    {reviews.map((item) => {
                      const reaction = userReactions[item.id];
                      const reviewRating = Math.round(parseFloat(item.rating) || 5);
                      const likesCount = parseInt(item.likes ?? item.helpful_votes ?? 0, 10);
                      const dislikesCount = parseInt(item.dislikes ?? item.unhelpful_votes ?? 0, 10);
                      const comment = item.comment || "";
                      const isLong = comment.length > (isDesktop ? 70 : 60);

                      return (
                        <div key={item.id} className="prr__card">
                          <div>
                            <div className="prr__card-top">
                              <div className="prr__card-title">
                                <span className="prr__card-rating">
                                  {reviewRating}
                                  <FaStar size={9} />
                                </span>
                                <span title={item.title || ""}>{item.title || "Verified review"}</span>
                              </div>
                              {item.time_ago && <span className="prr__card-time">{item.time_ago}</span>}
                            </div>
                            {comment && (
                              <p className="prr__card-text">
                                {isLong ? (
                                  <>
                                    {comment.slice(0, isDesktop ? 70 : 60).trim()}
                                    <Link to={reviewsPageUrl} className="prr__card-more">...more</Link>
                                  </>
                                ) : (
                                  comment
                                )}
                              </p>
                            )}
                          </div>

                          <div className="prr__card-foot">
                            <div className="prr__author">
                              <div className="prr__author-name">{item.customer_name || "Printmont Buyer"}</div>
                              {Number(item.is_verified) !== 0 && (
                                <div className="prr__verified">
                                  <FaCheckCircle size={9} /> Verified Buyer
                                </div>
                              )}
                            </div>
                            <div className="prr__reactions">
                              <button
                                type="button"
                                className={`prr__react prr__react--like ${reaction === "like" ? "is-active" : ""}`}
                                onClick={() => handleLike(item.id)}
                                aria-label="Helpful"
                              >
                                <FaThumbsUp size={11} /> {likesCount}
                              </button>
                              <button
                                type="button"
                                className={`prr__react prr__react--dislike ${reaction === "dislike" ? "is-active" : ""}`}
                                onClick={() => handleDislike(item.id)}
                                aria-label="Not helpful"
                              >
                                <FaThumbsDown size={11} /> {dislikesCount}
                              </button>
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                  {isDesktop && cardsScroll.next && (
                    <button type="button" className="prr__arrow prr__arrow--next" onClick={() => scrollCards(1)} aria-label="More reviews">
                      <FaChevronRight size={13} />
                    </button>
                  )}
                </div>
              ) : (
                <p className="prr__empty">No reviews yet. Be the first to review this product.</p>
              )}

              {/* ===== 5. Show all ===== */}
              {reviews.length > 0 && (
                <Link to={reviewsPageUrl} className="prr__show-all">
                  Show all reviews <FaChevronRight size={10} />
                </Link>
              )}
            </>
          )}
        </div>
      )}

      {/* ===== Lightbox / Photo Viewer Modal ===== */}
      <Modal
        show={activePhotoModal}
        onHide={() => setActivePhotoModal(false)}
        centered
        size="lg"
        contentClassName="bg-dark text-white border-0"
      >
        <Modal.Header closeButton closeVariant="white" className="border-0 pb-0">
          <Modal.Title className="fs-6 text-white-50">
            Customer Photos ({selectedPhotoIndex + 1} of {galleryPhotos.length})
          </Modal.Title>
        </Modal.Header>
        <Modal.Body className="p-2 text-center position-relative">
          <div
            style={{
              maxHeight: "75vh",
              display: "flex",
              alignItems: "center",
              justifyContent: "center"
            }}
          >
            <img
              src={galleryPhotos[selectedPhotoIndex]}
              alt="Customer Full View"
              style={{
                maxWidth: "100%",
                maxHeight: "70vh",
                objectFit: "contain",
                borderRadius: "8px"
              }}
              onError={hideBrokenImage}
            />
          </div>

          {/* Gallery Thumbnails row */}
          <div
            className="d-flex gap-2 justify-content-center mt-3 pb-1"
            style={{ overflowX: "auto" }}
          >
            {galleryPhotos.map((p, idx) => (
              <img
                key={idx}
                src={p}
                alt="thumb"
                style={{
                  width: "48px",
                  height: "48px",
                  objectFit: "cover",
                  borderRadius: "6px",
                  cursor: "pointer",
                  border:
                    idx === selectedPhotoIndex
                      ? "2px solid #3b82f6"
                      : "1px solid rgba(255,255,255,0.2)",
                  opacity: idx === selectedPhotoIndex ? 1 : 0.6
                }}
                onClick={() => setSelectedPhotoIndex(idx)}
                onError={hideBrokenImage}
              />
            ))}
          </div>
        </Modal.Body>
      </Modal>

      {/* ===== Write a Review Modal ===== */}
      <Modal show={showWriteModal} onHide={() => setShowWriteModal(false)} centered size="md">
        <Modal.Header closeButton>
          <Modal.Title className="fw-bold text-dark fs-5">Write a Review</Modal.Title>
        </Modal.Header>
        <Modal.Body className="p-3">
          {submitSuccessMsg && <Alert variant="success" className="py-2 small">{submitSuccessMsg}</Alert>}
          {submitErrorMsg && <Alert variant="danger" className="py-2 small">{submitErrorMsg}</Alert>}

          <Form onSubmit={handleSubmitReview}>
            {/* Rating Stars Selection */}
            <Form.Group className="mb-3 text-center">
              <Form.Label className="fw-semibold text-dark mb-1">Rate this product</Form.Label>
              <div className="d-flex justify-content-center gap-2">
                {[1, 2, 3, 4, 5].map((s) => (
                  <FaStar
                    key={s}
                    size={28}
                    style={{
                      cursor: "pointer",
                      color: s <= form.rating ? "#eab308" : "#cbd5e1",
                      transition: "transform 0.15s"
                    }}
                    onClick={() => setForm((f) => ({ ...f, rating: s }))}
                  />
                ))}
              </div>
            </Form.Group>

            <div className="row g-2 mb-2">
              <div className="col-6">
                <Form.Group>
                  <Form.Label className="small fw-semibold text-secondary">Your Name *</Form.Label>
                  <Form.Control
                    size="sm"
                    placeholder="e.g. Rahul Sharma"
                    required
                    value={form.customer_name}
                    onChange={(e) => setForm((f) => ({ ...f, customer_name: e.target.value }))}
                  />
                </Form.Group>
              </div>
              <div className="col-6">
                <Form.Group>
                  <Form.Label className="small fw-semibold text-secondary">City (Optional)</Form.Label>
                  <Form.Control
                    size="sm"
                    placeholder="e.g. Mumbai"
                    value={form.city}
                    onChange={(e) => setForm((f) => ({ ...f, city: e.target.value }))}
                  />
                </Form.Group>
              </div>
            </div>

            <Form.Group className="mb-2">
              <Form.Label className="small fw-semibold text-secondary">Review Headline</Form.Label>
              <Form.Control
                size="sm"
                placeholder="e.g. Mind-blowing purchase!"
                value={form.title}
                onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold text-secondary">Detailed Feedback *</Form.Label>
              <Form.Control
                as="textarea"
                rows={3}
                size="sm"
                required
                placeholder="Describe product quality, print clarity, delivery, and overall experience..."
                value={form.comment}
                onChange={(e) => setForm((f) => ({ ...f, comment: e.target.value }))}
              />
            </Form.Group>

            {/* Photo Attachments */}
            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold text-secondary d-flex align-items-center gap-1">
                <FaCamera className="text-primary" /> Attach Product Photos
              </Form.Label>
              <div
                style={{
                  border: "2px dashed #cbd5e1",
                  borderRadius: "8px",
                  padding: "12px",
                  textAlign: "center",
                  background: "#f8fafc",
                  cursor: "pointer"
                }}
                onClick={() => fileInputRef.current && fileInputRef.current.click()}
              >
                <FaCamera size={18} className="text-muted mb-1" />
                <div className="small fw-semibold text-dark">Click to upload product photos</div>
                <span className="text-muted" style={{ fontSize: "0.72rem" }}>
                  JPG, PNG, WEBP (Multiple allowed)
                </span>
                <input
                  type="file"
                  ref={fileInputRef}
                  multiple
                  accept="image/*"
                  className="d-none"
                  onChange={handleImageChange}
                />
              </div>

              {uploadedImages.length > 0 && (
                <div className="d-flex flex-wrap gap-2 mt-2">
                  {uploadedImages.map((img, idx) => (
                    <div
                      key={idx}
                      style={{
                        position: "relative",
                        width: "52px",
                        height: "52px",
                        borderRadius: "6px",
                        overflow: "hidden",
                        border: "1px solid #cbd5e1"
                      }}
                    >
                      <img
                        src={img.preview}
                        alt={`thumb-${idx}`}
                        style={{ width: "100%", height: "100%", objectFit: "cover" }}
                      />
                      <button
                        type="button"
                        style={{
                          position: "absolute",
                          top: "2px",
                          right: "2px",
                          background: "rgba(0,0,0,0.7)",
                          color: "#fff",
                          border: "none",
                          borderRadius: "50%",
                          width: "16px",
                          height: "16px",
                          fontSize: "10px",
                          display: "flex",
                          alignItems: "center",
                          justifyContent: "center",
                          cursor: "pointer"
                        }}
                        onClick={() => handleRemoveImage(idx)}
                      >
                        &times;
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </Form.Group>

            <div className="d-flex justify-content-end gap-2">
              <Button variant="light" size="sm" onClick={() => setShowWriteModal(false)}>
                Cancel
              </Button>
              <Button
                variant="primary"
                size="sm"
                type="submit"
                disabled={submitting || !form.customer_name.trim() || !form.comment.trim()}
              >
                {submitting ? (
                  <>
                    <Spinner animation="border" size="sm" className="me-1" /> Submitting...
                  </>
                ) : (
                  "Submit Review"
                )}
              </Button>
            </div>
          </Form>
        </Modal.Body>
      </Modal>
    </div>
  );
};

export default ProductReview;
