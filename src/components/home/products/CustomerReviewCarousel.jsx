import React from 'react';
import { FaStar } from 'react-icons/fa';

/**
 * CustomerReviewCarousel renders a horizontal-scrolling list of customer reviews.
 */
export default function CustomerReviewCarousel() {
  const reviews = [
    {
      id: 1,
      user: 'Amit Kumar',
      rating: 5,
      date: '12 July 2026',
      comment: 'Super fast delivery! The print quality on the t-shirt is excellent. Highly recommended site.',
      product: 'Navy Blue Sulphur Cotton Shirt'
    },
    {
      id: 2,
      user: 'Priya Sharma',
      rating: 5,
      date: '10 July 2026',
      comment: 'Really loved the customization. Easy editor and helpful customer support. Will buy again!',
      product: 'Customized White Mug'
    },
    {
      id: 3,
      user: 'Sandeep Singh',
      rating: 5,
      date: '08 July 2026',
      comment: 'Beautiful design. The premium packaging was impressive. Absolute value for money.',
      product: 'Premium Canvas Print'
    }
  ];

  return (
    <div className="container-fluid px-1 px-lg-0 home-layout-gap">
      <div className="d-flex justify-content-between align-items-center mb-1 mb-lg-3" style={{ paddingLeft: "8px", paddingRight: "8px" }}>
        <h5 className="mb-0 fw-bold" style={{ color: "#d9534f" }}>Customer Review Carousel</h5>
      </div>

      <div 
        className="d-flex flex-nowrap overflow-x-auto pb-2 px-2"
        style={{ 
          gap: "12px", 
          scrollbarWidth: "none", 
          msOverflowStyle: "none",
          WebkitOverflowScrolling: "touch"
        }}
      >
        {reviews.map((rev) => (
          <div 
            key={rev.id}
            className="card bg-white p-3 border"
            style={{ 
              flex: "0 0 250px", 
              borderRadius: "8px",
              boxShadow: "0 1px 3px rgba(0,0,0,0.05)",
              minHeight: "140px",
              border: "1px solid #e0e0e0"
            }}
          >
            <div className="d-flex justify-content-between align-items-center mb-1">
              <span className="fw-bold" style={{ fontSize: "0.82rem", color: "#333" }}>{rev.user}</span>
              <span className="text-muted" style={{ fontSize: "0.68rem" }}>{rev.date}</span>
            </div>
            
            <div className="d-flex gap-1 mb-2 text-warning">
              {Array.from({ length: 5 }).map((_, i) => (
                <FaStar key={i} size={12} color={i < rev.rating ? "#ffc107" : "#e4e5e9"} />
              ))}
            </div>

            <p className="m-0 text-muted" style={{ fontSize: "0.72rem", lineHeight: "1.4", height: "50px", overflow: "hidden", display: "-webkit-box", WebkitLineClamp: 3, WebkitBoxOrient: "vertical" }}>
              "{rev.comment}"
            </p>

            <div className="mt-2 pt-2 border-top">
              <span className="text-primary fw-semibold text-truncate d-block" style={{ fontSize: "0.68rem" }}>
                {rev.product}
              </span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
