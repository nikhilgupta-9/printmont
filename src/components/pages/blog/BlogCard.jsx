import React from "react";
import { Link } from "react-router-dom";
import { FaArrowRight, FaRegClock, FaRegUser, FaTag } from "react-icons/fa6";

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
    slug,
    id
  } = cardData || {};

  const imageUrl = imageSrc || image || "/default-img.jpg";
  const displayTag = tag || tags || category || "Inspiration";
  const targetUrl = slug ? `/blog/${slug}` : `/blog/${id || 1}`;

  return (
    <div className="card h-100 blog-grid-card border bg-white overflow-hidden d-flex flex-column justify-content-between p-0">
      <div>
        {/* Featured Image with Category Tag Overlay */}
        <div className="position-relative overflow-hidden" style={{ aspectRatio: "16/10", backgroundColor: "#f1f5f9" }}>
          <Link to={targetUrl} className="d-block w-100 h-100">
            <img
              src={imageUrl}
              alt={title || "Blog Post"}
              className="w-100 h-100 object-fit-cover zoom-hover"
              loading="lazy"
              onError={(e) => {
                e.currentTarget.src = "/default-img.jpg";
              }}
            />
          </Link>
          <div className="position-absolute top-0 start-0 m-2.5">
            <span className="badge badge-theme-subtle px-2.5 py-1 rounded-pill">
              {displayTag}
            </span>
          </div>
        </div>

        {/* Card Body */}
        <div className="p-3.5 p-md-4">
          {/* Metadata */}
          <div className="d-flex align-items-center gap-2 text-muted small mb-2" style={{ fontSize: "0.78rem" }}>
            {date && (
              <span className="d-inline-flex align-items-center gap-1">
                <FaRegClock size={11} className="text-primary" /> {date}
              </span>
            )}
            {author && (
              <>
                <span>•</span>
                <span className="d-inline-flex align-items-center gap-1 text-truncate" style={{ maxWidth: "120px" }}>
                  <FaRegUser size={10} className="text-primary" /> {author}
                </span>
              </>
            )}
          </div>

          {/* Title */}
          <h5 className="fw-bold mb-2 line-clamp-2 fs-6" style={{ minHeight: "2.6rem", lineHeight: "1.35" }}>
            <Link to={targetUrl} className="hover-primary">
              {title}
            </Link>
          </h5>

          {/* Summary */}
          {summary && (
            <p className="text-secondary small mb-0 line-clamp-3" style={{ lineHeight: "1.5", fontSize: "0.84rem" }}>
              {summary}
            </p>
          )}
        </div>
      </div>

      {/* Footer / Read More CTA */}
      <div className="px-3.5 px-md-4 pb-3.5 pt-0 border-0 bg-transparent">
        <hr className="my-2 opacity-25" />
        <Link
          to={targetUrl}
          className="text-primary fw-bold small text-decoration-none d-inline-flex align-items-center gap-1.5 hover-primary"
        >
          Read Full Article <FaArrowRight size={11} />
        </Link>
      </div>
    </div>
  );
};

export default BlogCard;
