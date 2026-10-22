import React, { useMemo } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import {
  FaFacebookF,
  FaInstagram,
  FaLinkedinIn,
  FaYoutube,
  FaChevronDown
} from "react-icons/fa";
import { FaXTwitter } from "react-icons/fa6";
import Categories from "../category-list/Categories";
import "./BlogHeader.css";

// Primary Category Navigation Bar Items (Navy Blue Strip)
const PRIMARY_CATEGORIES = [
  { name: "Gift Sets", url: "/category/gift-sets" },
  { name: "Event Merchandise", url: "/category/event-merchandise" },
  { name: "Cloths & Bags", url: "/category/cloths-bags" },
  { name: "Trophies", url: "/category/trophies" },
  { name: "Stationery", url: "/category/stationery" },
  { name: "Office Supplies", url: "/category/office-supplies" },
  { name: "Pens", url: "/category/pens" },
  { name: "Keychain", url: "/category/keychain" },
  { name: "Drinkware", url: "/category/drinkware" },
  { name: "Personalised Gifts", url: "/category/personalised-gifts" },
  { name: "Tech Products", url: "/category/tech-products" },
  { name: "Diwali Gifts", url: "/category/diwali-gifts" },
  { name: "Occasions", url: "/category/occasions" }
];

// Blog Topic Bar Items (Sub-Navigation according to PDF Mockup & FNP Mobile Category Chips)
const BLOG_TOPICS = [
  { label: "All", slug: "all", param: "all" },
  { label: "Occasion", slug: "occasions-gifts", param: "occasions" },
  { label: "Relationship", slug: "relationship-gifts", param: "relationship" },
  { label: "Corporate Gift Ideas", slug: "corporate-gifts", param: "corporate" },
  { label: "Promotional Gift Ideas", slug: "promotional-gifts", param: "promotional" },
  { label: "Business Gift Ideas", slug: "business-gifts", param: "business" },
  { label: "Personalised Gift Ideas", slug: "personalized-gifts", param: "personalized" },
  { label: "Doctor Gift Ideas", slug: "doctor-gifts", param: "doctor" }
];

const BlogHeader = ({ onSelectCategory, activeCategory: propActiveCategory }) => {
  const location = useLocation();
  const navigate = useNavigate();
  const searchParams = useMemo(() => new URLSearchParams(location.search), [location.search]);
  const activeCategory = propActiveCategory || searchParams.get("category") || "all";

  // Helper to handle topic click
  const handleTopicClick = (e, topic) => {
    e.preventDefault();
    if (onSelectCategory) {
      onSelectCategory(topic.slug);
    } else {
      if (topic.slug === "all") {
        navigate("/blog");
      } else {
        navigate(`/blog?category=${topic.slug}`);
      }
    }
  };

  return (
    <header className="pm-blog-header-wrapper" role="banner">
      {/* 1. TOP BAR */}
      <div className="pm-blog-top-bar">
        <div className="pm-blog-top-container">
          <div className="pm-blog-top-welcome">
            Welcome to Printmont
          </div>
          <div className="pm-blog-top-social">
            <span className="pm-blog-social-label">Follow us on</span>
            <div className="pm-blog-social-icons">
              <a
                href="https://facebook.com/printmont"
                target="_blank"
                rel="noopener noreferrer"
                className="pm-blog-social-btn"
                aria-label="Facebook"
              >
                <FaFacebookF />
              </a>
              <a
                href="https://twitter.com/printmont"
                target="_blank"
                rel="noopener noreferrer"
                className="pm-blog-social-btn"
                aria-label="Twitter / X"
              >
                <FaXTwitter />
              </a>
              <a
                href="https://instagram.com/printmont"
                target="_blank"
                rel="noopener noreferrer"
                className="pm-blog-social-btn"
                aria-label="Instagram"
              >
                <FaInstagram />
              </a>
              <a
                href="https://linkedin.com/company/printmont"
                target="_blank"
                rel="noopener noreferrer"
                className="pm-blog-social-btn"
                aria-label="LinkedIn"
              >
                <FaLinkedinIn />
              </a>
              <a
                href="https://youtube.com/printmont"
                target="_blank"
                rel="noopener noreferrer"
                className="pm-blog-social-btn"
                aria-label="YouTube"
              >
                <FaYoutube />
              </a>
            </div>
          </div>
        </div>
      </div>

      {/* 2. INNER PAGE CATEGORY NAVBAR BELOW WELCOME TO PRINTMONT (Desktop View) */}
      <div className="d-none d-lg-block w-100 shadow-sm" style={{ backgroundColor: "#0b53a1" }}>
        <Categories 
          showImages={false} 
          space="0" 
          bg="#0b53a1" 
          color="#ffffff" 
          isSticky={false} 
        />
      </div>

      {/* 3. CENTER BRAND LOGO AREA */}
      <div className="pm-blog-brand-section">
        <div className="pm-blog-brand-container">
          <Link to="/blog" className="pm-blog-logo-link" title="Printmont Blog Homepage">
            <img
              src="/blog-logo.png"
              alt="PRINTMONT | BLOG - Corporate Gifts & Promotional Items."
              className="pm-blog-logo-img"
              onError={(e) => {
                e.currentTarget.src = "/PrintLogo.png";
              }}
            />
          </Link>
        </div>
      </div>

      {/* 4. BLOG TOPIC / SUB-NAVIGATION BAR (FNP Style Scrollable Pill Chips) */}
      <nav className="pm-blog-topic-strip" aria-label="Blog Topics">
        <div className="pm-blog-topic-container">
          <ul className="pm-blog-topic-list">
            {BLOG_TOPICS.map((topic, idx) => {
              const isActive =
                activeCategory === topic.slug ||
                (topic.slug === "all" && (!activeCategory || activeCategory === "all"));
              return (
                <li key={idx} className="pm-blog-topic-item">
                  <Link
                    to={topic.slug === "all" ? "/blog" : `/blog?category=${topic.slug}`}
                    className={`pm-blog-topic-btn ${isActive ? "is-active" : ""}`}
                    onClick={(e) => handleTopicClick(e, topic)}
                  >
                    <span>{topic.label}</span>
                    <FaChevronDown className="pm-blog-topic-arrow" />
                  </Link>
                </li>
              );
            })}
          </ul>
        </div>
      </nav>
    </header>
  );
};

export default BlogHeader;
