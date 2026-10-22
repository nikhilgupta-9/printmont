import React, { useState, useEffect, useMemo } from "react";
import { Container, Row, Col, Spinner } from "react-bootstrap";
import { useLocation, useNavigate, Link } from "react-router-dom";
import {
  FaFileContract,
  FaUserShield,
  FaTruck,
  FaUndoAlt,
  FaCheckCircle,
  FaShieldAlt,
  FaEnvelope,
  FaGavel,
  FaChevronRight,
  FaHeadset
} from "react-icons/fa";
import { IoChevronDown } from "react-icons/io5";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import { updatePageSEO } from "../../utils/seo";
import "./Policy.css";

/** Presentation icons for policy keys */
const POLICY_ICONS = {
  terms: <FaFileContract />,
  "terms-of-use": <FaGavel />,
  privacy: <FaUserShield />,
  shipping: <FaTruck />,
  refund: <FaUndoAlt />,
  return: <FaUndoAlt />,
};

const iconFor = (key) => POLICY_ICONS[key] || <FaFileContract />;

/** Strip HTML tags for clean plain text */
const toPlainText = (html) =>
  String(html || "").replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();

const PolicyPage = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const [policies, setPolicies] = useState([]);
  const [loading, setLoading] = useState(true);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    let cancelled = false;

    const loadPolicies = async () => {
      try {
        const res = await fetch(API_ENDPOINTS.POLICIES);
        const json = await res.json();
        if (cancelled) return;

        if (json.success && Array.isArray(json.data)) {
          setPolicies(json.data.filter((p) => (p.status ?? "active") === "active"));
        } else {
          setFailed(true);
        }
      } catch (err) {
        console.error("Failed to load policies:", err);
        if (!cancelled) setFailed(true);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    loadPolicies();
    return () => { cancelled = true; };
  }, []);

  /**
   * Map current pathname to an active policy_key
   */
  const activeKey = useMemo(() => {
    if (!policies.length) return null;

    const path = location.pathname.toLowerCase();
    const slug = path.split("/").filter(Boolean).pop() || "";

    // 1. Exact slug match
    const exact = policies.find((p) => p.policy_key.toLowerCase() === slug);
    if (exact) return exact.policy_key;

    // 2. Legacy URL patterns
    const bare = slug.replace(/-policy$/, "");
    const byBare = policies.find((p) => p.policy_key.toLowerCase() === bare);
    if (byBare) return byBare.policy_key;

    // 3. /terms-and-conditions -> "terms"
    if (slug.startsWith("terms-and-")) {
      const terms = policies.find((p) => p.policy_key === "terms");
      if (terms) return terms.policy_key;
    }

    // 4. "return" -> "refund"
    if (slug.includes("return")) {
      const refund = policies.find((p) => p.policy_key === "refund");
      if (refund) return refund.policy_key;
    }

    // 5. Longest key match
    const loose = policies
      .filter((p) => slug.includes(p.policy_key.toLowerCase()))
      .sort((a, b) => b.policy_key.length - a.policy_key.length)[0];
    if (loose) return loose.policy_key;

    return policies[0]?.policy_key || "terms";
  }, [location.pathname, policies]);

  const active = policies.find((p) => p.policy_key === activeKey) || policies[0] || null;

  useEffect(() => {
    window.scrollTo(0, 0);
    if (active) {
      updatePageSEO({
        title: `${active.heading} | Printmont Policies`,
        description: toPlainText(active.description).slice(0, 160) || `${active.heading} for Printmont`,
        keywords: `printmont ${active.policy_key}, printmont legal, ${active.heading.toLowerCase()}, company policies`,
        type: "website"
      });
    }
  }, [active]);

  const pathFor = (key) => `/policy/${key}`;

  const handlePolicyChange = (e) => {
    const selectedKey = e.target.value;
    if (selectedKey === "terms-of-use") {
      navigate("/terms-of-use");
    } else {
      navigate(pathFor(selectedKey));
    }
  };

  return (
    <div className="policy-page-wrapper">
      <Container className="policy-container">
        
        {/* Slim Breadcrumb Navigation (Desktop Only, Rule 9) */}
        <div className="policy-breadcrumbs-wrap d-none d-md-block">
          <nav aria-label="breadcrumb" className="policy-breadcrumb">
            <Link to="/" className="policy-bc-link">Home</Link>
            <span className="policy-bc-sep">/</span>
            <span className="policy-bc-muted">Company Policies</span>
            <span className="policy-bc-sep">/</span>
            <span className="policy-bc-current">{active?.heading || "Policies"}</span>
          </nav>
        </div>

        {/* ============================================================ */}
        {/* 📱 MOBILE VIEW: Exact Match with Reference Mock Image        */}
        {/* ============================================================ */}
        <div className="d-block d-lg-none">
          {/* Hero Header Section */}
          <div className="policy-hero-header-section text-center">
            {/* Top Pill Tag */}
            <div className="d-inline-flex align-items-center justify-content-center">
              <span className="policy-hero-tag-badge">
                PRINTMONT LEGAL &amp; OPERATIONS
              </span>
            </div>

            {/* Main Title */}
            <h1 className="policy-hero-main-title">
              Company Policies &amp; Terms
            </h1>

            {/* Subtitle */}
            <p className="policy-hero-main-desc">
              Transparent legal terms, website usage rules, privacy guidelines, delivery schedules, and refund policies governing PrintMont.
            </p>

            {/* Policy Selector Dropdown (Styled as prominent select button) */}
            <div className="policy-selector-box-wrap mx-auto">
              <div className="policy-select-custom-wrapper">
                <select
                  id="policy-switcher-mobile"
                  className="policy-select-control"
                  value={activeKey || "terms"}
                  onChange={handlePolicyChange}
                  aria-label="Select Company Policy"
                >
                  {policies.map((p) => (
                    <option key={p.policy_key} value={p.policy_key}>
                      {p.heading}
                    </option>
                  ))}
                </select>
                <div className="policy-select-arrow-icon">
                  <IoChevronDown size={18} />
                </div>
              </div>
            </div>
          </div>

          {/* Main Policy Content Card (Mobile) */}
          <div className="policy-content-main-card">
            {loading ? (
              <div className="text-center py-5">
                <Spinner animation="border" variant="primary" />
                <p className="text-muted mt-2 small">Loading policy document...</p>
              </div>
            ) : failed ? (
              <div className="text-center py-5">
                <h5 className="fw-bold text-dark mb-2">Policies are currently unavailable</h5>
                <p className="text-secondary small mb-3">Please refresh the page or contact support.</p>
              </div>
            ) : !active ? (
              <div className="text-center py-5">
                <h5 className="fw-bold text-dark mb-2">No policy published yet</h5>
              </div>
            ) : (
              <PolicyContentMobile policy={active} />
            )}
          </div>
        </div>

        {/* ============================================================ */}
        {/* 🖥️ DESKTOP VIEW: Previous Original Sidebar + Content Layout */}
        {/* ============================================================ */}
        <div className="d-none d-lg-block">
          {loading ? (
            <div className="text-center py-5">
              <Spinner animation="border" variant="primary" />
              <p className="text-muted mt-2 small">Loading policies...</p>
            </div>
          ) : failed ? (
            <div className="text-center py-5 bg-white rounded-3 p-5 border">
              <h5 className="fw-bold text-dark mb-2">Policies are currently unavailable</h5>
              <p className="text-secondary small mb-3">Please refresh the page or contact support if the issue persists.</p>
              <button className="btn btn-outline-primary btn-sm rounded-pill px-4" onClick={() => window.location.reload()}>
                Refresh Page
              </button>
            </div>
          ) : !active ? (
            <div className="text-center py-5 bg-white rounded-3 p-5 border">
              <h5 className="fw-bold text-dark mb-2">No policies published yet</h5>
              <p className="text-secondary small mb-0">Please check back shortly.</p>
            </div>
          ) : (
            <Row className="g-4">
              {/* Desktop View: Left Sidebar Navigation */}
              <Col lg={3}>
                <div className="policy-sidebar-card sticky-top" style={{ top: "90px", zIndex: 10 }}>
                  <div className="policy-sidebar-title">
                    Company Policies
                  </div>

                  <div className="d-flex flex-column gap-1">
                    {policies.map((policy) => (
                      <Link
                        key={policy.policy_key}
                        to={pathFor(policy.policy_key)}
                        className={`policy-nav-link ${
                          activeKey === policy.policy_key ? "active" : ""
                        }`}
                      >
                        <div className="policy-nav-link-left">
                          <span className="policy-nav-icon">{iconFor(policy.policy_key)}</span>
                          <span>{policy.heading}</span>
                        </div>
                        {activeKey === policy.policy_key && (
                          <FaChevronRight size={12} className="text-primary opacity-75" />
                        )}
                      </Link>
                    ))}
                  </div>

                  {/* Legal Desk Assistance Card */}
                  <div className="policy-sidebar-help">
                    <div className="policy-sidebar-help-title">
                      <FaShieldAlt /> Legal Desk Assistance
                    </div>
                    <p className="policy-sidebar-help-text">
                      Have questions about our terms or compliance?
                    </p>
                    <a href="mailto:support@printmont.com" className="policy-sidebar-help-mail">
                      <FaEnvelope size={11} /> support@printmont.com
                    </a>
                  </div>
                </div>
              </Col>

              {/* Main Content Area (Desktop) */}
              <Col lg={9}>
                <div className="policy-main-card">
                  <PolicyBodyDesktop policy={active} />
                </div>
              </Col>
            </Row>
          )}
        </div>

      </Container>
    </div>
  );
};

/** Mobile Formatted Policy Document Component (Matches Mockup) */
const PolicyContentMobile = ({ policy }) => {
  const points = Array.isArray(policy.points) 
    ? policy.points.filter((p) => String(p).trim() !== "") 
    : [];

  return (
    <article className="policy-document-article">
      {/* Policy Heading (Mock format: # <Title> - Create Lasting Impressions) */}
      <div className="policy-doc-title-row">
        <h2 className="policy-doc-title">
          # {policy.heading} &ndash; Create Lasting Impressions with Personalized Branding
        </h2>
        {policy.updated_at && (
          <div className="policy-doc-date text-muted">
            Last revised: {new Date(String(policy.updated_at).replace(" ", "T")).toLocaleDateString("en-IN", {
              day: "2-digit",
              month: "long",
              year: "numeric",
            })}
          </div>
        )}
      </div>

      {/* Main Rich Text Content */}
      {policy.description && (
        <div
          className="policy-doc-body-text"
          dangerouslySetInnerHTML={{ __html: policy.description }}
        />
      )}

      {/* Key Highlights / Action Points */}
      {points.length > 0 && (
        <div className="policy-highlights-block mt-4 pt-3 border-top">
          <h4 className="policy-highlights-heading">
            <FaShieldAlt className="text-primary me-2" /> Important Highlights &amp; Key Terms:
          </h4>
          <Row className="g-3 mt-1">
            {points.map((pt, idx) => (
              <Col xs={12} key={idx}>
                <div className="policy-highlight-card">
                  <FaCheckCircle className="policy-highlight-check-icon" />
                  <p className="policy-highlight-text mb-0">{pt}</p>
                </div>
              </Col>
            ))}
          </Row>
        </div>
      )}
    </article>
  );
};

/** Desktop Formatted Policy Document Component (Original Desktop Layout) */
const PolicyBodyDesktop = ({ policy }) => {
  const points = Array.isArray(policy.points) ? policy.points.filter((p) => String(p).trim() !== "") : [];

  return (
    <article className="policy-section-animation">
      {/* Header Block with Icon, Heading & Metadata */}
      <div className="policy-header-box">
        <div className="policy-header-icon-badge">
          {iconFor(policy.policy_key)}
        </div>
        <div>
          <h1 className="policy-heading">{policy.heading}</h1>
          <p className="policy-meta">
            {policy.updated_at && (
              <>
                Last updated:{" "}
                {new Date(String(policy.updated_at).replace(" ", "T")).toLocaleDateString("en-IN", {
                  day: "2-digit",
                  month: "long",
                  year: "numeric",
                })}
              </>
            )}
            <span className="policy-meta-badge">Official Document</span>
          </p>
        </div>
      </div>

      {/* Rich Text Body from Admin CKEditor */}
      {policy.description && (
        <div
          className="policy-rich-text"
          dangerouslySetInnerHTML={{ __html: policy.description }}
        />
      )}

      {/* Key Highlights / Summary Points */}
      {points.length > 0 && (
        <div className="policy-highlights-section">
          <h3 className="policy-highlights-title">Key Highlights &amp; Important Points:</h3>
          <Row className="g-3">
            {points.map((point, idx) => (
              <Col xs={12} md={6} key={idx}>
                <div className="policy-point-card">
                  <FaCheckCircle className="policy-point-icon" />
                  <p className="policy-point-text">{point}</p>
                </div>
              </Col>
            ))}
          </Row>
        </div>
      )}

      {/* Bottom Closing Support Strip */}
      <div className="policy-bottom-contact">
        <div className="d-flex align-items-center gap-2">
          <FaHeadset className="text-primary flex-shrink-0" size={18} />
          <p className="policy-bottom-contact-text">
            Need clarification regarding our <strong>{policy.heading}</strong>?
          </p>
        </div>
        <a href="mailto:support@printmont.com" className="policy-bottom-contact-btn">
          <FaEnvelope size={12} /> Contact Legal Support
        </a>
      </div>
    </article>
  );
};

export default PolicyPage;
