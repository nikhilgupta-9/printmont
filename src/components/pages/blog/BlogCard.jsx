// src/components/pages/blog/BlogCard.jsx
import React from "react";
import { MdOutlineKeyboardArrowRight, MdPerson, MdCalendarToday, MdLocalOffer } from "react-icons/md";
import { Link } from "react-router-dom";

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
  const displayTag = tag || tags || "General";
  const targetUrl = slug ? `/blog/${slug}` : `/blog/${id || 1}`;

  return (
    // The card now sits inside a white section panel, so it carries only a
    // border — the same treatment product cards get on the homepage. The extra
    // shadow and padding read as a card-on-a-card once nested.
    <div className="card h-100 blog-card border-0 bg-transparent p-0">
      <div className="card-body rounded border bg-white d-flex flex-column justify-content-between p-3 h-100 transition-all hover-shadow">
        <div>
          {/* Category Header */}
          <div className="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
            <span className="badge bg-primary-subtle text-primary fw-bold px-2 py-1 fs-7 text-uppercase">
              {category || "Blog"}
            </span>
            <span className="text-primary d-flex align-items-center small fw-semibold">
              Read <MdOutlineKeyboardArrowRight size={20} />
            </span>
          </div>

          {/* Featured Image */}
          <div className="overflow-hidden rounded-3 mb-3 w-100 position-relative" style={{ aspectRatio: "16/9", backgroundColor: "#f8f9fa" }}>
            <img
              src={imageUrl}
              alt={title || "Blog Post"}
              className="w-100 h-100 rounded-3 zoom-hover"
              style={{ objectFit: "cover", display: "block" }}
              loading="lazy"
            />
          </div>

          {/* Title */}
          <h5 className="fw-bold text-dark mb-2 line-clamp-2 fs-6 fs-md-5" style={{ minHeight: "2.6rem" }}>
            {title}
          </h5>

          {/* Meta Infos */}
          <div className="d-flex flex-wrap align-items-center text-muted small mb-2 gap-2" style={{ fontSize: "0.78rem" }}>
            {author && (
              <span className="d-flex align-items-center gap-1">
                <MdPerson size={14} className="text-primary" /> {author}
              </span>
            )}
            {date && (
              <span className="d-flex align-items-center gap-1">
                <MdCalendarToday size={13} className="text-primary" /> {date}
              </span>
            )}
            {displayTag && (
              <span className="d-flex align-items-center gap-1 bg-light px-2 py-0.5 rounded text-secondary">
                <MdLocalOffer size={12} className="text-primary" /> {displayTag}
              </span>
            )}
          </div>

          {/* Summary */}
          {summary && (
            <p className="card-text text-secondary small mb-3 line-clamp-3" style={{ lineHeight: "1.5", fontSize: "0.85rem" }}>
              {summary}
            </p>
          )}
        </div>

        {/* Read More Button */}
        <div className="pt-2">
          <Link
            to={targetUrl}
            className="btn btn-outline-primary btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1"
          >
            Read Full Post <MdOutlineKeyboardArrowRight size={18} />
          </Link>
        </div>
      </div>
    </div>
  );
};

export default BlogCard;
