import React, { useState, useEffect, useRef } from "react";
import { Link, useParams, useSearchParams, useNavigate, useLocation } from "react-router-dom";
import {
  FaStar,
  FaChevronRight,
  FaChevronLeft,
  FaSearch,
  FaCheckCircle,
  FaFilter,
  FaRedo,
  FaShoppingBag,
  FaCamera,
  FaLock,
  FaTimes,
  FaThumbsUp,
  FaThumbsDown,
  FaImage,
  FaPen,
  FaArrowLeft
} from "react-icons/fa";
import { Button } from "react-bootstrap";
import { API_ENDPOINTS, BASE_URL, resolveImageUrl } from "../../../config/apiEndpoints";
import { useAuth } from "../../../context/AuthContext";
import "./AllReviewsPage.css";
import "../../products/ProductBreadcrumb.css";

const occasionOptions = [
  "All",
  "Birthday",
  "Anniversary",
  "Festival",
  "Diwali",
  "Housewarming",
  "Personal Gift",
  "Wedding",
  "Corporate Event"
];

const cityOptions = [
  "All",
  "Delhi",
  "Mumbai",
  "Bengaluru",
  "Pune",
  "Hyderabad",
  "Jaipur",
  "Ahmedabad",
  "Chennai",
  "Kolkata",
  "Chandigarh"
];

const AllReviewsPage = () => {
  const { user, token } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const goToLogin = () => {
    navigate(`/login?redirect=${encodeURIComponent(location.pathname + location.search)}`);
  };

  const { id: paramCategorySlug, productSlug: paramProductSlug, productId: paramProductId } = useParams();
  const [searchParams, setSearchParams] = useSearchParams();

  // Product-specific page (/product/:slug/reviews, /reviews/product/:id) or category page (/category/:slug/reviews).
  // On a category page, ?from=<productId> remembers the product the shopper came from (summary card + "Rate product").
  const queryProductId = searchParams.get("product_id") || "";
  const queryProductSlug = searchParams.get("product_slug") || "";
  const fromProductId = searchParams.get("from") || "";
  const categorySlug = paramCategorySlug || searchParams.get("category") || "";

  const productSlugParam = paramProductSlug || queryProductSlug || "";
  const slugIdMatch = productSlugParam.match(/-p(\d+)$/);
  const extractedProductId = paramProductId || queryProductId || (slugIdMatch ? slugIdMatch[1] : "") ||
    (/^\d+$/.test(productSlugParam) ? productSlugParam : "");
  const isProductPage = Boolean(extractedProductId);
  const contextProductId = extractedProductId || fromProductId;

  // State
  const [productData, setProductData] = useState(null);
  const [reviews, setReviews] = useState([]);
  const [categoryName, setCategoryName] = useState("All Products");
  const [stats, setStats] = useState({
    total: 0,
    average: 4.8,
    stars: { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 }
  });
  const [loading, setLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalCount, setTotalCount] = useState(0);

  // Filters
  const [selectedRating, setSelectedRating] = useState("");
  const [photosOnly, setPhotosOnly] = useState(false);
  const [verifiedOnly, setVerifiedOnly] = useState(false);
  const [selectedOccasion, setSelectedOccasion] = useState("All");
  const [selectedCity, setSelectedCity] = useState("All");
  const [searchQuery, setSearchQuery] = useState("");
  const [sortBy, setSortBy] = useState("helpful");

  // Modal & Lightbox
  const [showModal, setShowModal] = useState(false);
  const [showLoginPrompt, setShowLoginPrompt] = useState(false);
  const [selectedPhoto, setSelectedPhoto] = useState(null);

  // Form State
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    rating: 5,
    title: "",
    occasion: "Personal Gift",
    city: "Delhi",
    comment: ""
  });
  const [uploadedImages, setUploadedImages] = useState([]);
  const [submitStatus, setSubmitStatus] = useState({ loading: false, success: false, message: "" });
  const fileInputRef = useRef(null);

  // User Reactions State
  const [userReactions, setUserReactions] = useState(() => {
    try {
      const saved = localStorage.getItem("printmont_review_reactions");
      return saved ? JSON.parse(saved) : {};
    } catch {
      return {};
    }
  });

  // Sync user info
  useEffect(() => {
    if (user) {
      const fullName = user.firstName ? `${user.firstName} ${user.lastName || ""}`.trim() : user.name || "";
      setFormData((prev) => ({
        ...prev,
        name: fullName || prev.name,
        email: user.email || prev.email,
        city: user.city || prev.city || "Delhi"
      }));
    }
  }, [user]);

  // Product shown in the summary card
  useEffect(() => {
    if (!contextProductId) {
      setProductData(null);
      return;
    }
    let cancelled = false;
    fetch(API_ENDPOINTS.PRODUCT_DETAILS(contextProductId))
      .then((res) => res.json())
      .then((data) => {
        if (!cancelled && data && (data.success || data.product)) {
          setProductData(data.product || data.data || null);
        }
      })
      .catch((err) => console.warn("Could not load product details for reviews:", err));
    return () => { cancelled = true; };
  }, [contextProductId]);

  // Category shown in the summary card when there is no product context
  const [categoryData, setCategoryData] = useState(null);
  useEffect(() => {
    if (!categorySlug) {
      setCategoryData(null);
      return;
    }
    let cancelled = false;
    fetch(API_ENDPOINTS.CATEGORY_BY_SLUG(categorySlug))
      .then((res) => res.json())
      .then((data) => {
        if (!cancelled && data?.success && data.data && !Array.isArray(data.data)) setCategoryData(data.data);
      })
      .catch(() => {});
    return () => { cancelled = true; };
  }, [categorySlug]);

  // Fetch reviews data
  const fetchReviewsData = async (page = 1, searchOverride) => {
    setLoading(true);
    try {
      let url = "";
      if (isProductPage) {
        url = `${BASE_URL}/reviews-api.php?product_id=${extractedProductId}&page=${page}&limit=12`;
      } else if (categorySlug) {
        url = `${BASE_URL}/reviews-api.php?category_slug=${encodeURIComponent(categorySlug)}&page=${page}&limit=12`;
      } else {
        url = `${BASE_URL}/reviews-api.php?page=${page}&limit=12`;
      }

      if (selectedRating) url += `&rating=${selectedRating}`;
      if (selectedOccasion !== "All") url += `&occasion=${encodeURIComponent(selectedOccasion)}`;
      if (selectedCity !== "All") url += `&city=${encodeURIComponent(selectedCity)}`;
      const q = searchOverride ?? searchQuery;
      if (q) url += `&search=${encodeURIComponent(q)}`;
      if (sortBy) url += `&sort=${sortBy}`;

      const res = await fetch(url);
      if (res.ok) {
        const json = await res.json();
        if (json.status === "success" || json.success) {
          setReviews(json.data || json.reviews || []);
          setTotalCount(json.total_count || 0);
          setTotalPages(json.total_pages || 1);
          setCurrentPage(json.current_page || 1);

          if (json.category_name) {
            setCategoryName(json.category_name);
          } else if (categorySlug) {
            setCategoryName(categorySlug.replace(/-/g, " ").replace(/\b\w/g, (c) => c.toUpperCase()));
          }

          if (json.stats) {
            setStats(json.stats);
          }
        }
      }
    } catch (err) {
      console.error("Error loading reviews:", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReviewsData(currentPage);
  }, [extractedProductId, categorySlug, currentPage, selectedRating, selectedOccasion, selectedCity, sortBy]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setCurrentPage(1);
    fetchReviewsData(1);
  };

  const handleClearFilters = () => {
    setSelectedRating("");
    setPhotosOnly(false);
    setVerifiedOnly(false);
    setSelectedOccasion("All");
    setSelectedCity("All");
    setSearchQuery("");
    setSortBy("helpful");
    setCurrentPage(1);
  };

  // Like reaction handler
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

  // Dislike reaction handler
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

  // Open review modal
  // Only logged-in customers can rate; guests are sent to login and brought back here
  const handleOpenWriteReview = () => {
    if (!user || !token) {
      goToLogin();
      return;
    }
    setShowModal(true);
  };

  // Handle image files
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

  // Submit Review Form
  const handleSubmitReview = async (e) => {
    e.preventDefault();
    if (!formData.name || !formData.comment || !(contextProductId || productData?.id)) return;

    setSubmitStatus({ loading: true, success: false, message: "" });
    try {
      const payload = {
        product_id: contextProductId || productData?.id,
        user_id: user?.id || null,
        customer_name: formData.name.trim(),
        customer_email: formData.email.trim(),
        rating: Number(formData.rating),
        title: formData.title.trim() || formData.comment.slice(0, 30),
        occasion: formData.occasion,
        city: formData.city,
        comment: formData.comment.trim(),
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
      if (data.status === "success" || data.success) {
        setSubmitStatus({
          loading: false,
          success: true,
          message: data.message || "Thank you! Your review has been submitted for admin approval."
        });
        setFormData({ name: "", email: "", rating: 5, title: "", occasion: "Personal Gift", city: "Delhi", comment: "" });
        setUploadedImages([]);
        setTimeout(() => {
          setShowModal(false);
          setSubmitStatus({ loading: false, success: false, message: "" });
        }, 2500);
      } else if (res.status === 401 || data.require_login) {
        setSubmitStatus({ loading: false, success: false, message: "" });
        setShowModal(false);
        goToLogin();
      } else {
        setSubmitStatus({
          loading: false,
          success: false,
          message: data.message || "Failed to submit review."
        });
      }
    } catch (err) {
      setSubmitStatus({
        loading: false,
        success: false,
        message: "An error occurred. Please try again."
      });
    }
  };

  // Extract all customer photos for the gallery strip
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

  // Client-side filtering for photos only or verified buyers only
  const displayedReviews = reviews.filter((r) => {
    if (photosOnly && (!Array.isArray(r.images) || r.images.length === 0)) return false;
    if (verifiedOnly && !r.is_verified) return false;
    return true;
  });

  const totalRatings = Number(stats.total) || totalCount || reviews.length || 0;
  const avgRating = stats.average ? parseFloat(stats.average).toFixed(1) : "0.0";
  const starCount = (star) => Number(stats.stars?.[star] ?? stats.stars?.[String(star)] ?? 0);

  const productTitle = productData?.name || productData?.title || "";
  const productPrice = productData?.selling_price || productData?.price || "";
  const productMrp = productData?.mrp || productData?.original_price || "";
  const productImage = productData?.primary_image || productData?.thumbnail || productData?.image || "";
  const productLink = productData?.slug
    ? `/product/${productData.slug}-p${productData.id}`
    : contextProductId ? `/product/${contextProductId}` : "/";
  const productDiscount = productMrp && productPrice && Number(productMrp) > Number(productPrice)
    ? Math.round(((productMrp - productPrice) / productMrp) * 100)
    : 0;

  const pageCategoryName = categoryData?.name || categoryName;
  const pageHeading = isProductPage && productTitle ? productTitle : pageCategoryName;
  const canRate = Boolean(contextProductId || productData?.id);

  const sortTabs = [
    { key: "helpful", label: "Most Helpful" },
    { key: "newest", label: "Latest" },
    { key: "rating_high", label: "Positive" },
    { key: "rating_low", label: "Negative" }
  ];

  const ratingColor = (r) => (r >= 4 ? "#388e3c" : r === 3 ? "#ff9f00" : "#ff6161");
  const barColor = (star) => (star >= 4 ? "#388e3c" : star === 3 ? "#ff9f00" : "#ff6161");
  const formatPrice = (v) => Number(v).toLocaleString("en-IN");

  const visiblePhotos = allCustomerPhotos.slice(0, 8);
  const extraPhotos = allCustomerPhotos.length - visiblePhotos.length;

  const pageNumbers = [];
  const firstPage = Math.max(1, Math.min(currentPage - 4, totalPages - 9));
  for (let i = firstPage; i <= Math.min(totalPages, firstPage + 9); i++) pageNumbers.push(i);

  const summaryCard = (
    <div className="arp-side">
      {productData ? (
        <>
          <Link to={productLink} className="arp-side__img">
            {productImage && <img src={resolveImageUrl(productImage)} alt={productTitle} onError={(e) => { e.currentTarget.style.visibility = "hidden"; }} />}
          </Link>
          <Link to={productLink} className="arp-side__title" title={productTitle}>{productTitle}</Link>
          <div className="arp-side__rating">
            <span className="arp-badge" style={{ background: ratingColor(Math.round(avgRating)) }}>
              {avgRating} <FaStar size={9} />
            </span>
            <span className="arp-muted">({totalRatings.toLocaleString("en-IN")})</span>
          </div>
          {productPrice && (
            <div className="arp-side__price">
              <span className="arp-side__sp">₹{formatPrice(productPrice)}</span>
              {productDiscount > 0 && (
                <>
                  <span className="arp-side__mrp">₹{formatPrice(productMrp)}</span>
                  <span className="arp-side__off">{productDiscount}% off</span>
                </>
              )}
            </div>
          )}
          <Link to={productLink} className="arp-side__cta">
            <FaShoppingBag size={12} /> View Product
          </Link>
        </>
      ) : (
        <>
          <Link to={categorySlug ? `/category/${categorySlug}` : "/allproducts"} className="arp-side__img">
            {categoryData?.image && <img src={resolveImageUrl(categoryData.image)} alt={pageCategoryName} onError={(e) => { e.currentTarget.style.visibility = "hidden"; }} />}
          </Link>
          <div className="arp-side__title">{pageCategoryName}</div>
          <div className="arp-muted">Reviews from customers across all {pageCategoryName} products</div>
          <Link to={categorySlug ? `/category/${categorySlug}` : "/allproducts"} className="arp-side__cta">
            <FaShoppingBag size={12} /> Shop {pageCategoryName}
          </Link>
        </>
      )}
    </div>
  );

  return (
    <div className="all-reviews-page arp">
      <div className="arp-container">
        {/* ===== Breadcrumb ===== */}
        <nav aria-label="breadcrumb" className="pm-breadcrumb arp-breadcrumb">
          <Link to="/" className="pm-breadcrumb__link">Home</Link>
          <FaChevronRight className="pm-breadcrumb__sep" size={8} />
          {categorySlug && (
            <>
              <Link to={`/category/${categorySlug}`} className="pm-breadcrumb__link">{pageCategoryName}</Link>
              <FaChevronRight className="pm-breadcrumb__sep" size={8} />
            </>
          )}
          {isProductPage && productTitle && (
            <>
              <Link to={productLink} className="pm-breadcrumb__link arp-breadcrumb__product">{productTitle}</Link>
              <FaChevronRight className="pm-breadcrumb__sep" size={8} />
            </>
          )}
          <span className="pm-breadcrumb__current">Ratings &amp; Reviews</span>
        </nav>

        <div className="arp-layout">
          {/* ===== Left: product / category card (desktop sticky, mobile compact row) ===== */}
          <aside className="arp-layout__side">{summaryCard}</aside>

          {/* ===== Right: ratings + reviews ===== */}
          <main className="arp-layout__main">
            <section className="arp-card">
              <div className="arp-head">
                <h1 className="arp-head__title">
                  {pageHeading} <span>Ratings &amp; Reviews</span>
                </h1>
                {canRate && (
                  <button type="button" className="arp-rate-btn" onClick={handleOpenWriteReview}>
                    <FaPen size={11} /> Rate Product
                  </button>
                )}
              </div>

              {/* Summary: average + star distribution */}
              <div className="arp-summary">
                <div className="arp-summary__avg">
                  <div className="arp-summary__num">
                    {avgRating} <FaStar size={20} />
                  </div>
                  <div className="arp-muted">
                    {totalRatings.toLocaleString("en-IN")} Ratings &amp;<br />
                    {totalRatings.toLocaleString("en-IN")} Reviews
                  </div>
                </div>
                <div className="arp-summary__bars">
                  {[5, 4, 3, 2, 1].map((star) => {
                    const count = starCount(star);
                    const pct = totalRatings > 0 ? Math.round((count / totalRatings) * 100) : 0;
                    return (
                      <button
                        type="button"
                        key={star}
                        className={`arp-bar ${selectedRating === star ? "is-active" : ""}`}
                        onClick={() => { setSelectedRating(selectedRating === star ? "" : star); setCurrentPage(1); }}
                        title={`Show ${star} star reviews`}
                      >
                        <span className="arp-bar__label">{star} <FaStar size={9} /></span>
                        <span className="arp-bar__track">
                          <span className="arp-bar__fill" style={{ width: `${pct}%`, background: barColor(star) }} />
                        </span>
                        <span className="arp-bar__count">{count.toLocaleString("en-IN")}</span>
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* Customer photos strip */}
              {visiblePhotos.length > 0 && (
                <div className="arp-photos">
                  {visiblePhotos.map((photo, i) => (
                    <button type="button" key={`${photo}-${i}`} className="arp-photos__item" onClick={() => setSelectedPhoto(photo)}>
                      <img src={photo} alt={`Customer photo ${i + 1}`} loading="lazy" onError={(e) => { e.currentTarget.style.visibility = "hidden"; }} />
                      {i === visiblePhotos.length - 1 && extraPhotos > 0 && <span className="arp-photos__more">+{extraPhotos}</span>}
                    </button>
                  ))}
                </div>
              )}
            </section>

            {/* Sort tabs + quick filters (sticky on mobile) */}
            <section className="arp-card arp-toolbar">
              <div className="arp-tabs" role="tablist">
                {sortTabs.map((t) => (
                  <button
                    type="button"
                    key={t.key}
                    role="tab"
                    aria-selected={sortBy === t.key}
                    className={`arp-tab ${sortBy === t.key ? "is-active" : ""}`}
                    onClick={() => { setSortBy(t.key); setCurrentPage(1); }}
                  >
                    {t.label}
                  </button>
                ))}
              </div>
              <div className="arp-filters">
                {selectedRating && (
                  <button type="button" className="arp-chip is-active" onClick={() => { setSelectedRating(""); setCurrentPage(1); }}>
                    {selectedRating} <FaStar size={9} /> <FaTimes size={9} />
                  </button>
                )}
                <button type="button" className={`arp-chip ${photosOnly ? "is-active" : ""}`} onClick={() => setPhotosOnly(!photosOnly)}>
                  <FaCamera size={11} /> With Photos
                </button>
                <button type="button" className={`arp-chip ${verifiedOnly ? "is-active" : ""}`} onClick={() => setVerifiedOnly(!verifiedOnly)}>
                  <FaCheckCircle size={11} /> Verified
                </button>
                <form onSubmit={handleSearchSubmit} className="arp-search">
                  <FaSearch size={12} />
                  <input
                    type="search"
                    placeholder="Search reviews"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                  />
                  {searchQuery && (
                    <button
                      type="button"
                      aria-label="Clear search"
                      onClick={() => { setSearchQuery(""); setCurrentPage(1); fetchReviewsData(1, ""); }}
                    >
                      <FaTimes size={11} />
                    </button>
                  )}
                </form>
              </div>
            </section>

            {/* Review list */}
            <section className="arp-card arp-list">
              {loading ? (
                <div className="arp-state">
                  <div className="spinner-border spinner-border-sm text-primary" role="status" />
                  <span>Loading reviews...</span>
                </div>
              ) : displayedReviews.length === 0 ? (
                <div className="arp-state arp-state--empty">
                  <FaStar size={30} />
                  <div className="arp-state__title">No reviews found</div>
                  <div className="arp-muted">Try removing a filter or search keyword.</div>
                  <Button variant="outline-primary" size="sm" onClick={handleClearFilters}>Clear Filters</Button>
                </div>
              ) : (
                displayedReviews.map((r) => {
                  const reaction = userReactions[r.id];
                  const rRating = Math.round(parseFloat(r.rating) || 0);
                  const likes = parseInt(r.likes ?? r.helpful_votes ?? 0, 10);
                  const dislikes = parseInt(r.dislikes ?? r.unhelpful_votes ?? 0, 10);
                  const showProductTag = !isProductPage && r.product_name;

                  return (
                    <article key={r.id} className="arp-review">
                      <div className="arp-review__top">
                        <span className="arp-badge" style={{ background: ratingColor(rRating) }}>
                          {rRating} <FaStar size={9} />
                        </span>
                        <span className="arp-review__title">{r.title || "Verified review"}</span>
                      </div>

                      {r.comment && <p className="arp-review__text">{r.comment}</p>}

                      {Array.isArray(r.images) && r.images.length > 0 && (
                        <div className="arp-review__imgs">
                          {r.images.map((imgUrl, idx) => (
                            <button type="button" key={idx} onClick={() => setSelectedPhoto(imgUrl)}>
                              <img src={imgUrl} alt="Customer upload" loading="lazy" onError={(e) => { e.currentTarget.style.visibility = "hidden"; }} />
                            </button>
                          ))}
                        </div>
                      )}

                      {showProductTag && (
                        <Link
                          to={r.product_id ? `/product/${r.product_id}` : "#"}
                          className="arp-review__product"
                        >
                          {r.product_image && <img src={resolveImageUrl(r.product_image)} alt="" loading="lazy" />}
                          <span>{r.product_name}</span>
                        </Link>
                      )}

                      <div className="arp-review__foot">
                        <div className="arp-review__meta">
                          <span className="arp-review__name">{r.customer_name || "Printmont Buyer"}</span>
                          {Number(r.is_verified) !== 0 && (
                            <span className="arp-review__verified"><FaCheckCircle size={10} /> Certified Buyer</span>
                          )}
                          {r.city && <span>{r.city}</span>}
                          {r.time_ago && <span>{r.time_ago}</span>}
                        </div>
                        <div className="arp-review__reactions">
                          <button
                            type="button"
                            className={`arp-react ${reaction === "like" ? "is-like" : ""}`}
                            onClick={() => handleLike(r.id)}
                            aria-label="Helpful"
                          >
                            <FaThumbsUp size={12} /> {likes}
                          </button>
                          <button
                            type="button"
                            className={`arp-react ${reaction === "dislike" ? "is-dislike" : ""}`}
                            onClick={() => handleDislike(r.id)}
                            aria-label="Not helpful"
                          >
                            <FaThumbsDown size={12} /> {dislikes}
                          </button>
                        </div>
                      </div>
                    </article>
                  );
                })
              )}

              {/* Pagination */}
              {!loading && totalPages > 1 && (
                <div className="arp-pages">
                  <span className="arp-muted">Page {currentPage} of {totalPages}</span>
                  <div className="arp-pages__nums">
                    {currentPage > 1 && (
                      <button type="button" className="arp-pages__nav" onClick={() => setCurrentPage(currentPage - 1)}>
                        <FaChevronLeft size={10} /> Prev
                      </button>
                    )}
                    {pageNumbers.map((n) => (
                      <button
                        type="button"
                        key={n}
                        className={`arp-pages__num ${n === currentPage ? "is-active" : ""}`}
                        onClick={() => setCurrentPage(n)}
                      >
                        {n}
                      </button>
                    ))}
                    {currentPage < totalPages && (
                      <button type="button" className="arp-pages__nav" onClick={() => setCurrentPage(currentPage + 1)}>
                        Next <FaChevronRight size={10} />
                      </button>
                    )}
                  </div>
                </div>
              )}
            </section>
          </main>
        </div>
      </div>

      {/* Mobile: Rate Product fixed at the bottom, like Flipkart */}
      {canRate && (
        <div className="arp-mobile-bar">
          <button type="button" onClick={handleOpenWriteReview}>
            <FaPen size={12} /> Rate Product
          </button>
        </div>
      )}

      {/* ===== Photo Lightbox Modal ===== */}
      {selectedPhoto && (
        <div
          className="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
          style={{ backgroundColor: "rgba(0, 0, 0, 0.85)", zIndex: 9999 }}
          onClick={() => setSelectedPhoto(null)}
        >
          <div className="position-relative p-2" style={{ maxWidth: "90vw", maxHeight: "90vh" }} onClick={(e) => e.stopPropagation()}>
            <button
              type="button"
              className="btn btn-dark rounded-circle position-absolute top-0 end-0 m-3 shadow"
              style={{ width: "36px", height: "36px", padding: 0 }}
              onClick={() => setSelectedPhoto(null)}
            >
              <FaTimes size={16} />
            </button>
            <img
              src={selectedPhoto}
              alt="Customer photo enlarged"
              className="rounded-3 shadow-lg object-fit-contain"
              style={{ maxWidth: "100%", maxHeight: "80vh" }}
              onError={(e) => { e.currentTarget.style.visibility = "hidden"; }}
            />
          </div>
        </div>
      )}

      {/* ===== Write Review Modal ===== */}
      {showModal && (
        <div
          className="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center p-3"
          style={{ backgroundColor: "rgba(0, 0, 0, 0.6)", zIndex: 9999 }}
        >
          <div className="bg-white rounded-3 shadow-lg p-4" style={{ maxWidth: "520px", width: "100%", maxHeight: "90vh", overflowY: "auto" }}>
            <div className="d-flex align-items-center justify-content-between pb-2 border-bottom mb-3">
              <h5 className="fw-bold text-dark mb-0">Write a Review</h5>
              <button
                type="button"
                className="btn-close shadow-none"
                onClick={() => setShowModal(false)}
              />
            </div>

            {submitStatus.message && (
              <div className={`alert ${submitStatus.success ? "alert-success" : "alert-danger"} py-2 small mb-3`}>
                {submitStatus.message}
              </div>
            )}

            <form onSubmit={handleSubmitReview}>
              {/* Star Selection */}
              <div className="mb-3 text-center">
                <label className="fw-semibold text-dark small d-block mb-1">Rate this Product</label>
                <div className="d-flex justify-content-center gap-2">
                  {[1, 2, 3, 4, 5].map((s) => (
                    <FaStar
                      key={s}
                      size={28}
                      style={{
                        cursor: "pointer",
                        color: s <= formData.rating ? "#eab308" : "#cbd5e1"
                      }}
                      onClick={() => setFormData({ ...formData, rating: s })}
                    />
                  ))}
                </div>
              </div>

              <div className="row g-2 mb-2">
                <div className="col-6">
                  <label className="small fw-semibold text-secondary">Your Name *</label>
                  <input
                    type="text"
                    className="form-control form-control-sm"
                    required
                    placeholder="Enter your name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  />
                </div>
                <div className="col-6">
                  <label className="small fw-semibold text-secondary">City</label>
                  <input
                    type="text"
                    className="form-control form-control-sm"
                    placeholder="e.g. Mumbai"
                    value={formData.city}
                    onChange={(e) => setFormData({ ...formData, city: e.target.value })}
                  />
                </div>
              </div>

              <div className="mb-2">
                <label className="small fw-semibold text-secondary">Review Title</label>
                <input
                  type="text"
                  className="form-control form-control-sm"
                  placeholder="e.g. Loved the print quality!"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                />
              </div>

              <div className="mb-3">
                <label className="small fw-semibold text-secondary">Your Feedback *</label>
                <textarea
                  rows={3}
                  className="form-control form-control-sm"
                  required
                  placeholder="Share your experience with quality, finish, packaging and delivery..."
                  value={formData.comment}
                  onChange={(e) => setFormData({ ...formData, comment: e.target.value })}
                />
              </div>

              {/* Upload Images */}
              <div className="mb-3">
                <label className="small fw-semibold text-secondary d-flex align-items-center gap-1 mb-1">
                  <FaCamera className="text-primary" /> Attach Product Photos
                </label>
                <div
                  className="border rounded-2 p-3 text-center bg-light cursor-pointer"
                  onClick={() => fileInputRef.current && fileInputRef.current.click()}
                  style={{ borderStyle: "dashed" }}
                >
                  <FaCamera size={18} className="text-muted mb-1" />
                  <div className="small fw-semibold text-dark">Click to upload photos</div>
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
                      <div key={idx} className="position-relative" style={{ width: "50px", height: "50px" }}>
                        <img src={img.preview} alt="preview" className="w-100 h-100 rounded object-fit-cover border" />
                        <button
                          type="button"
                          className="btn btn-dark p-0 rounded-circle position-absolute top-0 end-0"
                          style={{ width: "16px", height: "16px", fontSize: "10px", lineHeight: "1" }}
                          onClick={() => handleRemoveImage(idx)}
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
                  className="btn btn-sm btn-primary px-3"
                  disabled={submitStatus.loading || !formData.name || !formData.comment}
                >
                  {submitStatus.loading ? "Submitting..." : "Submit Review"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default AllReviewsPage;
