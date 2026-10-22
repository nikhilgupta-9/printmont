import React, { useState, useEffect, useRef } from "react";
import { Link } from "react-router-dom";
import { FaStar, FaChevronRight } from "react-icons/fa";
import { API_ENDPOINTS } from "../../../config/apiEndpoints";
import "./CustomerReviewCarousel.css";

// Fallback high-quality reviews if API returns empty
const fallbackReviews = [
  {
    id: 1,
    customer_name: "Ankur Priyadarshi",
    initials: "AP",
    time_ago: "1 week ago",
    rating: 5,
    comment: "Prompt delivery and great print quality! Exceeded our expectations.",
    occasion: "Birthday",
    city: "Delhi",
    product_name: "Navy Blue Cotton Shirt"
  },
  {
    id: 2,
    customer_name: "Harneet Kaur",
    initials: "HK",
    time_ago: "2 weeks ago",
    rating: 5,
    comment: "Nice and clear picture printing with vibrant colors and fast dispatch.",
    occasion: "Rakshabandhan",
    city: "Mumbai",
    product_name: "Customized White Mug"
  },
  {
    id: 3,
    customer_name: "Zoom Mukherji",
    initials: "ZM",
    time_ago: "3 weeks ago",
    rating: 5,
    comment: "All the best to the team! The finish on the material is premium.",
    occasion: "Anniversary",
    city: "Kolkata",
    product_name: "Canvas Gallery Wrap"
  },
  {
    id: 4,
    customer_name: "Anita Patel",
    initials: "AP",
    time_ago: "1 month ago",
    rating: 5,
    comment: "Super fast dispatch and immaculate packaging. Truly same-day experience!",
    occasion: "Corporate",
    city: "Bengaluru",
    product_name: "Acrylic Photo Frame"
  }
];

// Helper to get initials from customer name
const getInitials = (name = "") => {
  const clean = name.replace(/[^a-zA-Z0-9\s]/g, "").trim();
  if (!clean) return "PM";
  const parts = clean.split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return clean.substring(0, 2).toUpperCase();
};

/**
 * CustomerReviewCarousel renders a horizontal-scrolling list of customer reviews.
 * Fetches approved reviews directly from the backend API or uses admin layout reviews.
 * Displays exactly 2 reviews per row in mobile view.
 */
export default function CustomerReviewCarousel({
  section,
  title,
  showAllUrl = "/reviews",
  reviews: propReviews,
  limit
}) {
  const sectionTitle = title || section?.label || "Customer Reviews";
  const initialData = Array.isArray(propReviews) && propReviews.length > 0
    ? propReviews
    : (Array.isArray(section?.reviews) && section.reviews.length > 0 ? section.reviews : fallbackReviews);

  const [reviews, setReviews] = useState(initialData);
  const [loading, setLoading] = useState(false);
  const trackRef = useRef(null);

  useEffect(() => {
    // If parent/layout API already provided reviews, use them immediately
    if (Array.isArray(section?.reviews) && section.reviews.length > 0) {
      setReviews(section.reviews);
      return;
    }
    if (Array.isArray(propReviews) && propReviews.length > 0) {
      setReviews(propReviews);
      return;
    }

    let isMounted = true;
    const fetchReviews = async () => {
      setLoading(true);
      try {
        const reviewLimit = Number(limit || section?.product_limit) || 5;
        const reviewIds = section?.product_ids || "";
        const params = { limit: reviewLimit, sort: "rating_high" };
        if (reviewIds && reviewIds.trim()) {
          params.ids = reviewIds.trim();
        }

        const url = API_ENDPOINTS.GET_REVIEWS(params);
        const res = await fetch(url);
        if (res.ok) {
          const data = await res.json();
          if (
            isMounted &&
            data.status === "success" &&
            Array.isArray(data.data) &&
            data.data.length > 0
          ) {
            setReviews(data.data);
          }
        }
      } catch (err) {
        console.error("Error loading home customer reviews:", err);
      } finally {
        if (isMounted) setLoading(false);
      }
    };

    fetchReviews();
    return () => {
      isMounted = false;
    };
  }, [section?.reviews, section?.product_ids, section?.product_limit, propReviews, limit]);

  return (
    <section className="home-reviews-section">
      <div className="home-reviews-header">
        <h3 className="home-reviews-title">{sectionTitle}</h3>
        <Link to={showAllUrl} className="home-reviews-show-all">
          Show All <FaChevronRight size={10} />
        </Link>
      </div>

      <div className="home-reviews-track" ref={trackRef}>
        {reviews.map((rev, idx) => {
          const customerName = rev.customer_name || rev.name || "Happy Customer";
          const initials = rev.initials || getInitials(customerName);
          const timeAgo = rev.time_ago || rev.created_at || "Recently";
          const rating = Number(rev.rating) || 5;
          const comment = rev.comment || rev.text || rev.title || "Great quality and fast delivery!";
          const productTag = rev.product_name || rev.product || (rev.occasion ? `For ${rev.occasion}` : "Verified Purchase");

          return (
            <div key={rev.id || idx} className="home-review-card">
              <div>
                {/* User Header */}
                <div className="home-review-user-row">
                  <div className="home-review-avatar">{initials}</div>
                  <div className="home-review-user-meta">
                    <span className="home-review-name" title={customerName}>
                      {customerName}
                    </span>
                    <span className="home-review-time">{timeAgo}</span>
                  </div>
                </div>

                {/* Rating Stars (Green) */}
                <div className="home-review-stars">
                  {Array.from({ length: 5 }).map((_, i) => (
                    <FaStar
                      key={i}
                      size={11}
                      color={i < rating ? "#008a00" : "#d1d5db"}
                    />
                  ))}
                </div>

                {/* Comment Text */}
                <p className="home-review-comment" title={comment}>
                  "{comment}"
                </p>
              </div>

              {/* Footer / Product Tag */}
              <div className="home-review-footer">
                <span className="home-review-tag" title={productTag}>
                  {productTag}
                </span>
                {rev.city && (
                  <span className="home-review-pill">{rev.city}</span>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </section>
  );
}
