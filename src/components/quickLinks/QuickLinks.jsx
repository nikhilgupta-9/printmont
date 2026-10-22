import React, { useState, useEffect, useMemo } from "react";
import { Link } from "react-router-dom";
import { IoIosArrowForward } from "react-icons/io";
import { 
  MdOutlineManageAccounts, 
  MdHelpOutline, 
  MdOutlineBusinessCenter, 
  MdSupportAgent,
  MdStorefront, 
  MdMonetizationOn, 
  MdInfoOutline, 
  MdOutlineArticle, 
  MdWorkOutline, 
  MdPeopleOutline, 
  MdOutlineSecurity, 
  MdOutlinePhone, 
  MdOutlineDescription, 
  MdOutlinePrivacyTip, 
  MdOutlineLocalShipping, 
  MdOutlineAssignmentReturn, 
  MdOutlineAssignment,
  MdExplore
} from "react-icons/md";
import { 
  FaBoxes, 
  FaSitemap, 
  FaLink, 
  FaSearch, 
  FaTimes, 
  FaHeadset, 
  FaTruck, 
  FaShieldAlt, 
  FaHandshake,
  FaBuilding,
  FaFileContract
} from "react-icons/fa";
import { LuTruck } from "react-icons/lu";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import { updatePageSEO } from "../../utils/seo";
import "./quick.css";

// 🧠 Pre-configured fallback list with exact labels & subtexts from reference design
const DEFAULT_SECTIONS = [
  {
    section_name: "",
    category_label: "Top Level",
    links: [
      { id: 1, title: "Track Your Orders", subtext: "Live status updates for your printing orders.", icon_name: "truck", url: "/track-order" },
      { id: 2, title: "Account Related Queries", subtext: "Answer to all account related queries.", icon_name: "account", url: "/my-account" },
      { id: 3, title: "All Help Topics", subtext: "Answer to frequently asked questions.", icon_name: "help", url: "/help-center" },
    ]
  },
  {
    section_name: "SUPPORT :",
    category_label: "Support",
    links: [
      { id: 4, title: "Business Solutions", subtext: "Answer to all your business related solutions.", icon_name: "business", url: "/business-solutions" },
      { id: 5, title: "Bulk Orders", subtext: "Best discount to all product on bulk orders.", icon_name: "bulk", url: "/bulk-orders" },
      { id: 6, title: "Help Center", subtext: "How can we help?.", icon_name: "support", url: "/help-center" },
    ]
  },
  {
    section_name: "Need More Help?",
    category_label: "Partnerships",
    links: [
      { id: 7, title: "Franchise", subtext: "Low investment high return I promise.", icon_name: "franchise", url: "/franchise" },
      { id: 8, title: "Become a seller", subtext: "Sell online to crores of customers at 0% Commission.", icon_name: "seller", url: "/become-a-seller" },
    ]
  },
  {
    section_name: "OUR COMPANY :",
    category_label: "Our Company",
    links: [
      { id: 9, title: "About Us", subtext: "Since Printmont inception in 2019..", icon_name: "about", url: "/about" },
      { id: 10, title: "Blog", subtext: "Celebrate Relations.", icon_name: "blog", url: "/blog" },
      { id: 11, title: "Careers", subtext: "Where Passion Meets Career.", icon_name: "career", url: "/careers" },
      { id: 12, title: "Affiliate Program", subtext: "3 Simple Steps to Partner with us!.", icon_name: "affiliate", url: "/affiliate-program" },
      { id: 13, title: "Security", subtext: "Safe and Secure Shopping.", icon_name: "security", url: "/security" },
      { id: 14, title: "Sitemap", subtext: "Trending Product Pages.", icon_name: "sitemap", url: "/sitemap" },
      { id: 15, title: "Contact Us", subtext: "Contact Details and General Queries.", icon_name: "contact", url: "/contact" },
    ]
  },
  {
    section_name: "POLICY INFO :",
    category_label: "Policy Info",
    links: [
      { id: 16, title: "Terms & Conditions", subtext: "The following Terms and Conditions.", icon_name: "terms", url: "/policy/terms" },
      { id: 17, title: "Privacy Policy", subtext: "The following Privacy Policy.", icon_name: "privacy", url: "/policy/privacy" },
      { id: 18, title: "Shipping Policy", subtext: "The following Shipping Policy.", icon_name: "shipping", url: "/policy/shipping" },
      { id: 19, title: "Return & Refund Policy", subtext: "The following Return and Refund Policy.", icon_name: "refund", url: "/policy/refund" },
      { id: 20, title: "Terms of use", subtext: "The following Terms of use.", icon_name: "terms_of_use", url: "/terms-of-use" },
    ]
  }
];

// Helper to render matched icon component
const renderIcon = (iconName) => {
  switch (iconName) {
    case "truck":
      return <LuTruck size={20} className="quick-icon" />;
    case "account":
      return <MdOutlineManageAccounts size={21} className="quick-icon" />;
    case "help":
      return <MdHelpOutline size={21} className="quick-icon" />;
    case "business":
      return <MdOutlineBusinessCenter size={20} className="quick-icon" />;
    case "bulk":
      return <FaBoxes size={18} className="quick-icon" />;
    case "support":
      return <MdSupportAgent size={22} className="quick-icon" />;
    case "franchise":
      return <MdStorefront size={21} className="quick-icon" />;
    case "seller":
      return <MdMonetizationOn size={21} className="quick-icon" />;
    case "about":
      return <MdInfoOutline size={21} className="quick-icon" />;
    case "blog":
      return <MdOutlineArticle size={21} className="quick-icon" />;
    case "career":
      return <MdWorkOutline size={21} className="quick-icon" />;
    case "affiliate":
      return <MdPeopleOutline size={21} className="quick-icon" />;
    case "security":
      return <MdOutlineSecurity size={21} className="quick-icon" />;
    case "sitemap":
      return <FaSitemap size={18} className="quick-icon" />;
    case "contact":
      return <MdOutlinePhone size={20} className="quick-icon" />;
    case "terms":
      return <MdOutlineDescription size={21} className="quick-icon" />;
    case "privacy":
      return <MdOutlinePrivacyTip size={21} className="quick-icon" />;
    case "shipping":
      return <MdOutlineLocalShipping size={21} className="quick-icon" />;
    case "refund":
      return <MdOutlineAssignmentReturn size={21} className="quick-icon" />;
    case "terms_of_use":
      return <MdOutlineAssignment size={21} className="quick-icon" />;
    default:
      return <FaLink size={18} className="quick-icon" />;
  }
};

const QuickLinks = () => {
  const [sections, setSections] = useState(DEFAULT_SECTIONS);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState("");
  const [activeTab, setActiveTab] = useState("all");

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: "Quick Links & Navigation Directory | Printmont",
      description: "Direct navigation directory for Printmont orders, 24x7 helpdesk, bulk corporate printing, franchise opportunities, career vacancies, and customer policies.",
      keywords: "printmont quick links, order tracking, bulk gifts help, printmont seller, printmont franchise, help desk, return policy, terms of use",
      type: "website"
    });

    let isMounted = true;
    fetch(API_ENDPOINTS.QUICK_LINKS)
      .then((res) => res.json())
      .then((data) => {
        if (isMounted && data?.success && Array.isArray(data.sections) && data.sections.length > 0) {
          setSections(data.sections);
        }
      })
      .catch((err) => {
        console.warn("Quick links API fallback triggered:", err);
      })
      .finally(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, []);

  // Total links count
  const totalLinksCount = useMemo(() => {
    return sections.reduce((sum, s) => sum + (s.links ? s.links.length : 0), 0);
  }, [sections]);

  // Filter sections by search and active tab
  const filteredSections = useMemo(() => {
    return sections.map((sec) => {
      // Tab filter
      if (activeTab !== "all") {
        const secNameLower = (sec.section_name || "").toLowerCase();
        if (activeTab === "support" && !secNameLower.includes("support") && sec.section_name !== "") return null;
        if (activeTab === "partnerships" && !secNameLower.includes("help") && !secNameLower.includes("need")) return null;
        if (activeTab === "company" && !secNameLower.includes("company")) return null;
        if (activeTab === "policy" && !secNameLower.includes("policy")) return null;
      }

      // Search filter
      if (!searchQuery.trim()) return sec;
      const query = searchQuery.toLowerCase();
      const matchedLinks = (sec.links || []).filter(
        (l) =>
          l.title.toLowerCase().includes(query) ||
          (l.subtext && l.subtext.toLowerCase().includes(query)) ||
          (sec.section_name && sec.section_name.toLowerCase().includes(query))
      );
      return { ...sec, links: matchedLinks };
    }).filter((sec) => sec && sec.links && sec.links.length > 0);
  }, [sections, searchQuery, activeTab]);

  return (
    <div className="quicklinks-page-wrapper">
      {/* ============================================================ */}
      {/* 📱 MOBILE VIEW: Exact Pixel-Perfect Match with Reference Image */}
      {/* ============================================================ */}
      <div className="quicklinks-mobile-view d-block d-lg-none">
        <div className="quicklinks-mobile-list">
          {filteredSections.map((section, sIndex) => (
            <div key={sIndex} className="quicklinks-mobile-section">
              {/* Section Header if title exists */}
              {section.section_name && (
                <div className="quicklinks-section-header">
                  {section.section_name}
                </div>
              )}

              {/* Section Links */}
              <div className="quicklinks-items-group">
                {section.links.map((link) => (
                  <Link
                    key={link.id || link.title}
                    to={link.url}
                    className="quicklinks-row-item"
                  >
                    <div className="quicklinks-row-left">
                      <div className="quicklinks-icon-badge">
                        {renderIcon(link.icon_name)}
                      </div>
                      <div className="quicklinks-text-block">
                        <div className="quicklinks-title">{link.title}</div>
                        {link.subtext && (
                          <div className="quicklinks-subtext">{link.subtext}</div>
                        )}
                      </div>
                    </div>
                    <div className="quicklinks-row-arrow">
                      <IoIosArrowForward size={18} />
                    </div>
                  </Link>
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* ============================================================ */}
      {/* 🖥️ DESKTOP VIEW: Clean Light/White Background + 1400px Standard Grid */}
      {/* ============================================================ */}
      <div className="quicklinks-desktop-view d-none d-lg-block">
        {/* 1. DESKTOP HERO & BREADCRUMB HEADER (CLEAN WHITE / LIGHT SLATE) */}
        <section className="quicklinks-desktop-hero-banner">
          <div className="container-1400">
            {/* Breadcrumbs */}
            <nav aria-label="breadcrumb" className="quicklinks-breadcrumbs mb-3">
              <Link to="/" className="text-secondary text-decoration-none hover-dark">Home</Link>
              <span className="mx-2 text-muted">/</span>
              <span className="text-dark fw-semibold">Quick Links &amp; Navigation Directory</span>
            </nav>

            <div className="row align-items-center g-4">
              {/* Hero Left Content */}
              <div className="col-12 col-xl-8">
                <div className="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-light text-dark mb-3 border border-secondary-subtle small">
                  <span className="pulse-dot"></span>
                  <span className="fw-semibold text-uppercase" style={{ letterSpacing: "0.6px", fontSize: "11.5px" }}>
                    Printmont Navigation Portal
                  </span>
                </div>

                <h1 className="quicklinks-hero-title text-dark fw-bold mb-2">
                  <MdExplore className="me-2 text-danger" /> Quick Links &amp; Directory
                </h1>
                <p className="quicklinks-hero-desc text-muted mb-4" style={{ maxWidth: "680px", fontSize: "15px", lineHeight: "1.6" }}>
                  Instant access to order tracking, customer help center, corporate business solutions, seller registration, and official company policies.
                </p>

                {/* Hero Badges */}
                <div className="d-flex flex-wrap gap-2 pt-1">
                  <span className="quicklinks-hero-pill">
                    <FaTruck className="text-primary me-1" /> Live Order Tracking
                  </span>
                  <span className="quicklinks-hero-pill">
                    <FaHeadset className="text-success me-1" /> 24x7 Customer Helpdesk
                  </span>
                  <span className="quicklinks-hero-pill">
                    <FaBoxes className="text-warning me-1" /> Corporate Bulk Discounts
                  </span>
                  <span className="quicklinks-hero-pill">
                    <FaShieldAlt className="text-info me-1" /> 100% Secure &amp; Verified
                  </span>
                </div>
              </div>

              {/* Hero Right Stats Card (Clean Light Slate) */}
              <div className="col-12 col-xl-4">
                <div className="quicklinks-hero-stats-card p-4 rounded-4 shadow-sm border bg-white">
                  <div className="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                    <div className="d-flex align-items-center gap-2">
                      <FaLink className="text-danger fs-5" />
                      <span className="text-dark fw-bold fs-6">Directory Overview</span>
                    </div>
                    <span className="badge bg-success-subtle text-success px-2 py-1 rounded-pill fw-semibold">Active &amp; Online</span>
                  </div>

                  <div className="row g-3 text-center">
                    <div className="col-4">
                      <div className="p-2 rounded-3 bg-light border">
                        <div className="text-dark fw-bold fs-4">{totalLinksCount}+</div>
                        <small className="text-muted" style={{ fontSize: "11px" }}>Total Portals</small>
                      </div>
                    </div>
                    <div className="col-4">
                      <div className="p-2 rounded-3 bg-light border">
                        <div className="text-dark fw-bold fs-4">{sections.length}</div>
                        <small className="text-muted" style={{ fontSize: "11px" }}>Categories</small>
                      </div>
                    </div>
                    <div className="col-4">
                      <div className="p-2 rounded-3 bg-light border">
                        <div className="text-success fw-bold fs-4">1-Click</div>
                        <small className="text-muted" style={{ fontSize: "11px" }}>Direct Access</small>
                      </div>
                    </div>
                  </div>

                  <div className="mt-3 pt-2">
                    <Link to="/track-order" className="btn btn-dark w-100 fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center gap-2">
                      <LuTruck size={18} /> Track Your Order Now
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* 2. SEARCH & FILTER CONTROLS BAR (CLEAN WHITE / BORDER) */}
        <section className="quicklinks-control-bar py-3 shadow-none bg-white border-bottom">
          <div className="container-1400">
            <div className="d-flex flex-wrap align-items-center justify-content-between gap-3">
              {/* Category Filter Pills */}
              <div className="d-flex flex-wrap align-items-center gap-2">
                <button
                  type="button"
                  className={`quick-pill-btn ${activeTab === "all" ? "active" : ""}`}
                  onClick={() => setActiveTab("all")}
                >
                  All Sections ({totalLinksCount})
                </button>
                <button
                  type="button"
                  className={`quick-pill-btn ${activeTab === "support" ? "active" : ""}`}
                  onClick={() => setActiveTab("support")}
                >
                  <FaHeadset className="me-1" /> Support &amp; Help
                </button>
                <button
                  type="button"
                  className={`quick-pill-btn ${activeTab === "partnerships" ? "active" : ""}`}
                  onClick={() => setActiveTab("partnerships")}
                >
                  <FaHandshake className="me-1" /> Partnerships
                </button>
                <button
                  type="button"
                  className={`quick-pill-btn ${activeTab === "company" ? "active" : ""}`}
                  onClick={() => setActiveTab("company")}
                >
                  <FaBuilding className="me-1" /> Our Company
                </button>
                <button
                  type="button"
                  className={`quick-pill-btn ${activeTab === "policy" ? "active" : ""}`}
                  onClick={() => setActiveTab("policy")}
                >
                  <FaFileContract className="me-1" /> Policies
                </button>
              </div>

              {/* Instant Search Bar */}
              <div className="quicklinks-search-container position-relative">
                <FaSearch className="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" />
                <input
                  type="text"
                  className="form-control ps-5 pe-5 py-2 rounded-pill shadow-none"
                  placeholder="Search portals, policies, services..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                />
                {searchQuery && (
                  <button
                    type="button"
                    className="btn btn-link position-absolute top-50 end-0 translate-middle-y me-2 p-0 text-muted"
                    onClick={() => setSearchQuery("")}
                  >
                    <FaTimes />
                  </button>
                )}
              </div>
            </div>
          </div>
        </section>

        {/* 3. MAIN DIRECTORY GRID */}
        <section className="quicklinks-directory-section py-4">
          <div className="container-1400">
            {filteredSections.length === 0 ? (
              <div className="text-center py-5 bg-white rounded-4 border shadow-sm my-4">
                <FaSearch size={40} className="text-muted mb-3 opacity-50" />
                <h5 className="fw-bold text-dark">No directory links found</h5>
                <p className="text-muted mb-3">Try searching for different keywords or reset your filters.</p>
                <button className="btn btn-outline-dark rounded-pill px-4" onClick={() => { setSearchQuery(""); setActiveTab("all"); }}>
                  Reset Filters
                </button>
              </div>
            ) : (
              <div className="row g-4">
                {filteredSections.map((section, sIndex) => (
                  <div key={sIndex} className="col-12 col-md-6 col-xl-4">
                    <div className="card h-100 border shadow-sm rounded-4 quicklinks-desk-card overflow-hidden">
                      {/* Section Card Header */}
                      <div className="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                        <h5 className="mb-0 fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                          <span className="category-indicator-bar"></span>
                          {section.section_name ? section.section_name.replace(":", "") : "General Access"}
                        </h5>
                        <span className="badge bg-light text-dark border rounded-pill px-2 py-1 fw-semibold" style={{ fontSize: "11px" }}>
                          {section.links.length} Portals
                        </span>
                      </div>

                      {/* Section Links */}
                      <div className="card-body p-0">
                        <div className="list-group list-group-flush">
                          {section.links.map((link) => (
                            <Link
                              key={link.id || link.title}
                              to={link.url}
                              className="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-4 py-3 border-0 border-bottom quick-desk-item"
                            >
                              <div className="d-flex align-items-center gap-3">
                                <div className="quicklinks-icon-badge">
                                  {renderIcon(link.icon_name)}
                                </div>
                                <div>
                                  <div className="fw-semibold text-dark mb-0 fs-6 quick-desk-title">
                                    {link.title}
                                  </div>
                                  {link.subtext && (
                                    <small className="text-muted d-block" style={{ fontSize: "12px", lineHeight: "1.35", marginTop: "2px" }}>
                                      {link.subtext}
                                    </small>
                                  )}
                                </div>
                              </div>
                              <div className="quick-desk-arrow-box">
                                <IoIosArrowForward size={16} className="text-muted" />
                              </div>
                            </Link>
                          ))}
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </section>
      </div>
    </div>
  );
};

export default QuickLinks;
