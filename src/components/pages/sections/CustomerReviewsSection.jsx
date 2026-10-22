import React, { useState, useEffect, useRef } from "react";
import { Link, useNavigate } from "react-router-dom";
import { FaStar, FaChevronRight, FaChevronLeft, FaTimes, FaCamera, FaLock, FaUserCircle } from "react-icons/fa";
import { API_ENDPOINTS, BASE_URL } from "../../../config/apiEndpoints";
import { useAuth } from "../../../context/AuthContext";
import "./CustomerReviewsSection.css";

// Fallback high-quality reviews matching the design
const fallbackReviews = [
  {
    id: 1,
    customer_name: "Ankur Priyadarshi",
    initials: "AP",
    time_ago: "1 year ago",
    rating: 5,
    comment: "Prompt delivery and great print quality! Exceeded our expectations.",
    occasion: "Birthday",
    city: "Delhi",
    images: []
  },
  {
    id: 2,
    customer_name: "Harneet kaur",
    initials: "HK",
    time_ago: "1 year ago",
    rating: 5,
    comment: "Nice and clear picture printing with vibrant colors.",
    occasion: "Rakshabandhan",
    city: "Delhi",
    images: []
  },
  {
    id: 3,
    customer_name: "Zoom Mukherji",
    initials: "ZM",
    time_ago: "1 year ago",
    rating: 5,
    comment: "All the best to the team! The finish on the material is premium.",
    occasion: "Rakshabandhan",
    city: "Kolkata",
    images: []
  },
  {
    id: 4,
    customer_name: "KAILASA's Sudeep ...",
    initials: "KSB",
    time_ago: "1 year ago",
    rating: 5,
    comment: "Unique design idea and fast execution. Delivered right on time.",
    occasion: "Birthday",
    city: "Mumbai",
    images: []
  },
  {
    id: 5,
    customer_name: "Anita Patel",
    initials: "AP",
    time_ago: "6 months ago",
    rating: 5,
    comment: "Super fast dispatch and immaculate packaging. Truly same-day experience!",
    occasion: "Corporate Event",
    city: "Bengaluru",
    images: []
  },
  {
    id: 6,
    customer_name: "Rohit Malhotra",
    initials: "RM",
    time_ago: "8 months ago",
    rating: 5,
    comment: "The customized print was crisp and accurate to the artwork provided.",
    occasion: "Anniversary",
    city: "Pune",
    images: []
  }
];

const CustomerReviewsSection = ({
  categorySlug = "",
  categoryId = null,
  title = "Customer Reviews",
  showAllUrl = "#"
}) => {
  const { user, token } = useAuth();
  const navigate = useNavigate();

  const [reviews, setReviews] = useState(fallbackReviews);
  const [loading, setLoading] = useState(false);
  const [showModal, setShowModal] = useState(false);
  const [showLoginPrompt, setShowLoginPrompt] = useState(false);
  const [selectedPhoto, setSelectedPhoto] = useState(null);

  const [formData, setFormData] = useState({
    name: "",
    email: "",
    rating: 5,
    occasion: "Birthday",
    city: "Delhi",
    comment: ""
  });
  const [uploadedImages, setUploadedImages] = useState([]); // array of { file, preview, base64 }
  const [submitStatus, setSubmitStatus] = useState({ loading: false, success: false, message: "" });
  const trackRef = useRef(null);
  const fileInputRef = useRef(null);

  // Sync user info when logged in
  useEffect(() => {
    if (user) {
      const fullName = user.firstName ? `${user.firstName} ${user.lastName || ""}`.trim() : (user.name || "");
      setFormData((prev) => ({
        ...prev,
        name: fullName || prev.name,
        email: user.email || prev.email,
        city: user.city || prev.city || "Delhi"
      }));
    }
  }, [user]);

  // Fetch approved reviews from API
  useEffect(() => {
    let isMounted = true;
    const fetchReviews = async () => {
      setLoading(true);
      try {
        const url = API_ENDPOINTS.GET_REVIEWS({
          category_slug: categorySlug,
          category_id: categoryId,
          limit: 15
        });
        const res = await fetch(url);
        if (res.ok) {
          const data = await res.json();
          if (isMounted && data.status === "success" && Array.isArray(data.data) && data.data.length > 0) {
            setReviews(data.data);
          }
        }
      } catch (err) {
        console.error("Error loading reviews:", err);
      } finally {
        if (isMounted) setLoading(false);
      }
    };

    fetchReviews();
    return () => {
      isMounted = false;
    };
  }, [categorySlug, categoryId]);

  // Scroll controls
  const handleScroll = (direction) => {
    if (trackRef.current) {
      const scrollAmount = trackRef.current.clientWidth * 0.75;
      trackRef.current.scrollBy({
        left: direction === "next" ? scrollAmount : -scrollAmount,
        behavior: "smooth"
      });
    }
  };

  // Open review modal with auth check
  const handleOpenWriteReview = () => {
    if (!user) {
      setShowLoginPrompt(true);
    } else {
      setShowModal(true);
    }
  };

  // Handle image file selection with preview & base64 conversion
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

  // Submit new review (goes to pending state for admin approval)
  const handleSubmitReview = async (e) => {
    e.preventDefault();
    if (!user) {
      setShowLoginPrompt(true);
      return;
    }
    if (!formData.name || !formData.comment) return;

    setSubmitStatus({ loading: true, success: false, message: "" });
    try {
      const payload = {
        product_id: categoryId || 62,
        user_id: user.id || null,
        customer_name: formData.name,
        customer_email: formData.email,
        rating: Number(formData.rating),
        occasion: formData.occasion,
        city: formData.city,
        comment: formData.comment,
        images: uploadedImages.map((img) => img.base64)
      };

      const res = await fetch(API_ENDPOINTS.REVIEWS, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          ...(token ? { Authorization: `Bearer ${token}` } : {})
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (data.status === "success" || data.success) {
        setSubmitStatus({
          loading: false,
          success: true,
          message: data.message || "Thank you! Your review has been submitted for admin approval."
        });
        setFormData({ name: "", email: "", rating: 5, occasion: "Birthday", city: "Delhi", comment: "" });
        setUploadedImages([]);
        setTimeout(() => {
          setShowModal(false);
          setSubmitStatus({ loading: false, success: false, message: "" });
        }, 3000);
      } else {
        if (data.require_login) {
          setShowModal(false);
          setShowLoginPrompt(true);
        } else {
          setSubmitStatus({
            loading: false,
            success: false,
            message: data.message || "Failed to submit review."
          });
        }
      }
    } catch (err) {
      setSubmitStatus({
        loading: false,
        success: false,
        message: "An error occurred. Please try again."
      });
    }
  };

  const targetAllReviewsUrl = showAllUrl !== "#" ? showAllUrl : (categorySlug ? `/category/${categorySlug}/reviews` : "/reviews");

  return (
    <section className="cust-reviews-container">
      <div className="cust-reviews-inner">
        {/* Header */}
        <div className="cust-reviews-header">
          <h2 className="cust-reviews-title">{title}</h2>
          <div className="d-flex align-items-center gap-2">
            <button
              onClick={handleOpenWriteReview}
              className="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-1"
              style={{ fontSize: "0.85rem", borderRadius: "6px" }}
            >
              <FaCamera size={12} /> Write a Review
            </button>
            <Link
              to={targetAllReviewsUrl}
              className="cust-reviews-show-all-btn"
            >
              Show All Reviews <FaChevronRight size={11} />
            </Link>
          </div>
        </div>

        {/* Carousel / Card Track */}
        <div className="cust-reviews-carousel-wrapper">
          <div className="cust-reviews-track" ref={trackRef}>
            {reviews.map((rev, idx) => (
              <div key={rev.id || idx} className="cust-review-card">
                {/* User Info & Rating */}
                <div>
                  <div className="cust-review-user-row">
                    <div className="cust-review-avatar">
                      {rev.initials || "AP"}
                    </div>
                    <div className="cust-review-user-info">
                      <div className="cust-review-name-time">
                        <span className="cust-review-name">{rev.customer_name}</span>{" "}
                        <span className="cust-review-time">• {rev.time_ago || "Recently"}</span>
                      </div>
                      <div className="cust-review-stars">
                        {[...Array(5)].map((_, i) => (
                          <FaStar
                            key={i}
                            color={i < (rev.rating || 5) ? "#008a00" : "#d1d5db"}
                            size={14}
                          />
                        ))}
                      </div>
                    </div>
                  </div>

                  {/* Comment Body */}
                  <div className="cust-review-body">
                    <p className="cust-review-comment">
                      {rev.comment || rev.title || "Great product and excellent service!"}
                    </p>
                  </div>

                  {/* Customer Uploaded Photos Row */}
                  {rev.images && rev.images.length > 0 && (
                    <div className="cust-review-images-row">
                      {rev.images.map((imgUrl, i) => (
                        <img
                          key={i}
                          src={imgUrl}
                          alt="Customer product photo"
                          className="cust-review-img-thumb"
                          onClick={() => setSelectedPhoto(imgUrl)}
                        />
                      ))}
                    </div>
                  )}
                </div>

                {/* Bottom Tags / Pills */}
                <div className="cust-review-pills">
                  {rev.occasion && (
                    <span className="cust-review-pill">
                      Occasion: {rev.occasion}
                    </span>
                  )}
                  {rev.city && (
                    <span className="cust-review-pill">
                      City: {rev.city}
                    </span>
                  )}
                </div>
              </div>
            ))}
          </div>

          {/* Right Floating Arrow */}
          <button
            className="cust-reviews-nav-btn next"
            onClick={() => handleScroll("next")}
            aria-label="Next Reviews"
          >
            <FaChevronRight size={14} />
          </button>
        </div>
      </div>

      {/* Lightbox / Zoom Photo Modal */}
      {selectedPhoto && (
        <div
          className="modal show d-block"
          style={{ backgroundColor: "rgba(0,0,0,0.8)", zIndex: 1060 }}
          onClick={() => setSelectedPhoto(null)}
        >
          <div className="modal-dialog modal-dialog-centered modal-lg">
            <div className="modal-content bg-transparent border-0 text-center position-relative">
              <button
                className="btn btn-light position-absolute top-0 end-0 m-2 rounded-circle"
                onClick={() => setSelectedPhoto(null)}
              >
                <FaTimes size={16} />
              </button>
              <img
                src={selectedPhoto}
                alt="Enlarged review photo"
                className="img-fluid rounded-3 shadow-lg"
                style={{ maxHeight: "80vh", objectFit: "contain" }}
              />
            </div>
          </div>
        </div>
      )}

      {/* Login Required Prompt Modal */}
      {showLoginPrompt && (
        <div className="modal show d-block" style={{ backgroundColor: "rgba(0,0,0,0.5)", zIndex: 1050 }}>
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content rounded-4 border-0 shadow text-center p-4">
              <div className="mb-3">
                <div
                  className="mx-auto rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary"
                  style={{ width: "64px", height: "64px" }}
                >
                  <FaLock size={28} />
                </div>
              </div>
              <h4 className="fw-bold mb-2">Login Required</h4>
              <p className="text-muted small mb-4">
                You must be logged in to submit a review and share your product photos with the community.
              </p>
              <div className="d-flex justify-content-center gap-2">
                <button
                  type="button"
                  className="btn btn-light px-3"
                  onClick={() => setShowLoginPrompt(false)}
                >
                  Cancel
                </button>
                <button
                  type="button"
                  className="btn btn-primary px-4"
                  onClick={() => navigate(`/login?redirect=${encodeURIComponent(window.location.pathname)}`)}
                >
                  Log In / Sign Up
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Write a Review Modal */}
      {showModal && user && (
        <div className="modal show d-block" style={{ backgroundColor: "rgba(0,0,0,0.5)", zIndex: 1050 }}>
          <div className="modal-dialog modal-dialog-centered modal-lg">
            <div className="modal-content rounded-4 border-0 shadow">
              <div className="modal-header border-bottom-0 pb-0">
                <h5 className="modal-title fw-bold d-flex align-items-center gap-2">
                  <FaCamera className="text-primary" /> Share Your Product Review &amp; Photos
                </h5>
                <button
                  type="button"
                  className="btn-close"
                  onClick={() => setShowModal(false)}
                ></button>
              </div>
              <div className="modal-body pt-2">
                <p className="text-muted small mb-3">
                  Posting as <strong>{user.firstName ? `${user.firstName} ${user.lastName || ""}`.trim() : (user.name || user.email)}</strong>. All submitted reviews are verified and moderated by our team before going live.
                </p>

                {submitStatus.message && (
                  <div className={`alert ${submitStatus.success ? "alert-success" : "alert-danger"} py-2 small`}>
                    {submitStatus.message}
                  </div>
                )}

                {!submitStatus.success && (
                  <form onSubmit={handleSubmitReview}>
                    <div className="row g-2 mb-2">
                      <div className="col-md-6">
                        <label className="form-label small fw-bold mb-1">Your Name *</label>
                        <input
                          type="text"
                          className="form-control form-control-sm"
                          required
                          value={formData.name}
                          onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                          placeholder="e.g. Priya Sharma"
                        />
                      </div>
                      <div className="col-md-6">
                        <label className="form-label small fw-bold mb-1">Email (Optional)</label>
                        <input
                          type="email"
                          className="form-control form-control-sm"
                          value={formData.email}
                          onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                          placeholder="e.g. priya@example.com"
                        />
                      </div>
                    </div>

                    <div className="row g-2 mb-2">
                      <div className="col-6">
                        <label className="form-label small fw-bold mb-1">Rating *</label>
                        <select
                          className="form-select form-select-sm"
                          value={formData.rating}
                          onChange={(e) => setFormData({ ...formData, rating: e.target.value })}
                        >
                          <option value="5">⭐⭐⭐⭐⭐ (5 Stars)</option>
                          <option value="4">⭐⭐⭐⭐ (4 Stars)</option>
                          <option value="3">⭐⭐⭐ (3 Stars)</option>
                          <option value="2">⭐⭐ (2 Stars)</option>
                          <option value="1">⭐ (1 Star)</option>
                        </select>
                      </div>
                      <div className="col-6">
                        <label className="form-label small fw-bold mb-1">City</label>
                        <input
                          type="text"
                          className="form-control form-control-sm"
                          value={formData.city}
                          onChange={(e) => setFormData({ ...formData, city: e.target.value })}
                          placeholder="e.g. Delhi, Mumbai"
                        />
                      </div>
                    </div>

                    <div className="mb-2">
                      <label className="form-label small fw-bold mb-1">Occasion</label>
                      <input
                        type="text"
                        className="form-control form-control-sm"
                        value={formData.occasion}
                        onChange={(e) => setFormData({ ...formData, occasion: e.target.value })}
                        placeholder="e.g. Birthday, Corporate Event"
                      />
                    </div>

                    <div className="mb-3">
                      <label className="form-label small fw-bold mb-1">Review / Feedback *</label>
                      <textarea
                        className="form-control form-control-sm"
                        rows="3"
                        required
                        value={formData.comment}
                        onChange={(e) => setFormData({ ...formData, comment: e.target.value })}
                        placeholder="Tell us about the print quality, delivery time, finish, etc..."
                      ></textarea>
                    </div>

                    {/* Multiple Images Upload Box */}
                    <div className="mb-3">
                      <label className="form-label small fw-bold mb-1">
                        <FaCamera className="me-1 text-primary" /> Upload Product Photos (Multiple files allowed)
                      </label>
                      <div
                        className="upload-dropzone"
                        onClick={() => fileInputRef.current && fileInputRef.current.click()}
                      >
                        <FaCamera size={24} className="text-muted mb-1" />
                        <div className="fw-semibold small text-dark">Click here to browse or add photos</div>
                        <span className="text-muted" style={{ fontSize: "0.75rem" }}>
                          JPG, PNG, WEBP allowed (Max 5MB each)
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
                        <div className="upload-preview-grid">
                          {uploadedImages.map((img, idx) => (
                            <div key={idx} className="upload-preview-item">
                              <img src={img.preview} alt={`preview-${idx}`} />
                              <button
                                type="button"
                                className="upload-preview-remove"
                                onClick={() => handleRemoveImage(idx)}
                                title="Remove photo"
                              >
                                &times;
                              </button>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>

                    <div className="d-flex justify-content-end gap-2">
                      <button
                        type="button"
                        className="btn btn-sm btn-light"
                        onClick={() => setShowModal(false)}
                      >
                        Cancel
                      </button>
                      <button
                        type="submit"
                        disabled={submitStatus.loading}
                        className="btn btn-sm btn-primary px-3"
                      >
                        {submitStatus.loading ? "Submitting..." : "Submit Review"}
                      </button>
                    </div>
                  </form>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </section>
  );
};

export default CustomerReviewsSection;
