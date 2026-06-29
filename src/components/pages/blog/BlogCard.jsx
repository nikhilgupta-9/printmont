// src/components/BlogCard.jsx
import React from "react";
import { MdOutlineKeyboardArrowRight } from "react-icons/md";
import { Link } from "react-router-dom"; // ✅ use react-router-dom

const BlogCard = ({ cardData }) => {
  const {
    category,
    title,
    imageSrc,
    image,
    author,
    date,
    tag,
    tags,
    summary,
    readMoreLink = "#",
  } = cardData || {};

  // Handle variations
  const imageUrl = imageSrc || image || "https://via.placeholder.com/150";
  const displayTag = tag || tags || "General";

  return (
    <div className="card h-100 blog-card">
      <div className="card-body rounded border">
        {/* Category */}
        <div className="d-flex justify-content-center align-items-center mb-2">
          <h4 className="text-dark fw-bold mb-0 d-flex align-items-center">
            {category}
            <i className="bi bi-chevron-right text-black"><MdOutlineKeyboardArrowRight size={30} />
</i>
          </h4>
        </div>

        {/* Image + Details */}
        <div className="d-flex justify-content-center align-items-center flex-column">
          {/* Image */}
          <div
            className="d-flex flex-column mb-2 mb-sm-0 border-0"
            style={{ maxWidth: "250px", minWidth: "100px" }}
          >
            <img
              src={imageUrl}
              alt={title}
              className="img-fluid rounded border-0"
              style={{ objectFit: "contain", aspectRatio: "1/1" }}
            />
          </div>

          {/* Text */}
          <div className="ms-3 flex-grow-1 justify-content-center align-items-center mt-3">
            <h5 className="fw-semibold mt-0">{title}</h5>

            <p className="card-text small text-muted mb-1">
              <i className="bi bi-person me-1"></i> {author}{" "}
              <i className="bi bi-calendar ms-2 me-1"></i> {date}{" "}
              <i className="bi bi-tags ms-2 me-1"></i> {displayTag}
            </p>

            <p className="card-text text-black text-secondary mb-1">{summary}</p>

            <Link
              to={`/blog/${cardData?.id || 1}`}
              className="small fw-semibold text-primary text-decoration-none"
            >
              Read More
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
};

export default BlogCard;
