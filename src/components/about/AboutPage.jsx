import React, { useState, useEffect } from "react";
import { Container, Row, Col } from "react-bootstrap";
import { 
  FaCheckCircle, FaAward, FaUsers, FaBoxOpen, FaHeadset, 
  FaPrint, FaLeaf, FaShippingFast, FaQuoteLeft 
} from "react-icons/fa";
import { API_ENDPOINTS, ASSET_URL } from "../../config/apiEndpoints";
import "./About.css";

const AboutPage = () => {
  const [sections, setSections] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchAboutData = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.ABOUT);
        if (!response.ok) throw new Error("Network response was not ok");
        const json = await response.json();
        if (json && json.success && Array.isArray(json.data)) {
          setSections(json.data);
        }
      } catch (error) {
        console.error("Error fetching about page API content:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchAboutData();
  }, []);

  // Helper to extract section content by type or title
  const getSection = (type, titleFallback) => {
    if (!sections.length) return null;
    return (
      sections.find(s => s.section_type === type) ||
      sections.find(s => s.section_title?.toLowerCase().includes(titleFallback.toLowerCase()))
    );
  };

  // Get specific sections
  const heroSection = getSection("hero", "Welcome to Printmont");
  const missionSection = getSection("mission", "Our Mission");
  const visionSection = getSection("history", "Our Vision");
  const valuesSection = getSection("values", "Our Values");
  const whySection = sections.find(s => s.section_title?.toLowerCase().includes("choose") || (s.section_type === "mission" && s.id !== missionSection?.id));

  // Resolved Image URLs
  const getImageUrl = (sec, defaultUrl) => {
    if (!sec || !sec.image_path) return defaultUrl;
    if (sec.image_path.startsWith("http")) return sec.image_path;
    return `${ASSET_URL}${sec.image_path}`;
  };

  return (
    <div className="about-page pb-3">
      {/* 🚀 Hero Header Section */}
      <div className="about-hero py-3 mb-3 text-center position-relative overflow-hidden">
        <div className="hero-shape hero-shape-1"></div>
        <div className="hero-shape hero-shape-2"></div>
        <div className="hero-shape hero-shape-3"></div>
        <Container className="position-relative z-index-2 py-2">
          <span className="badge bg-primary-soft text-primary px-3 py-1.5 rounded-pill mb-2 fw-bold tracking-wide text-uppercase">
            Beyond Ordinary Prints
          </span>
          <h1 className="fw-extrabold display-5 mb-2 text-dark main-title">
            {heroSection?.section_title || "We Print Your Imagination"}
          </h1>
          <p className="lead text-muted mx-auto fs-6 mb-0" style={{ maxWidth: "700px" }}>
            {heroSection?.section_content || 
              "Welcome to Printmont. We bridge the gap between creative digital dreams and physical, touchable masterpieces. Through next-gen tech and design passion, we make everyday gifting extraordinary."}
          </p>
        </Container>
      </div>

      <Container>
        {/* Intro Grid */}
        <Row className="align-items-center mb-4 gy-3">
          <Col lg={6} className="pe-lg-4">
            <span className="sub-tag text-uppercase fw-bold text-primary mb-1 d-block" style={{ fontSize: "0.8rem" }}>Our Passion</span>
            <h3 className="fw-bold text-dark mb-2 section-heading">
              Redefining Custom Merchandise
            </h3>
            <p className="text-secondary mb-2" style={{ fontSize: "0.95rem" }}>
              Printmont isn't just about ink on paper or fabric. It's about personal expression, professional representation, and the joy of giving.
            </p>
            <p className="text-muted mb-3 small">
              We leverage proprietary color-matching systems, premium sustainable garments, and organic substrates to ensure that every single item leaving our facility is a symbol of perfection. Whether it's a single custom mug or 10,000 corporate branding bundles, we treat every pixel with care.
            </p>
            <div className="d-flex gap-2 align-items-center quote-highlight p-2.5 rounded-3 bg-light border-start border-primary border-4">
              <FaQuoteLeft className="text-primary fs-5 flex-shrink-0" />
              <p className="mb-0 text-dark fw-medium italic-style small">
                "A product isn't complete until it carries a memory."
              </p>
            </div>
          </Col>
          <Col lg={6} className="text-center position-relative">
            <div className="deco-dot-grid"></div>
            <div className="about-image-wrapper p-2 bg-white shadow-sm rounded-4 border">
              <img 
                src={getImageUrl(heroSection, "/Online-Shopping-1.png")} 
                className="img-fluid rounded-3 zoom-hover w-100" 
                alt="Printmont Premium Gifting" 
                style={{ maxHeight: "260px", objectFit: "cover" }}
              />
            </div>
          </Col>
        </Row>

        {/* Stats Section */}
        <div className="stats-section-new py-3 my-4 border-top">
          <Row className="text-center gy-3">
            <Col xs={6} md={3}>
              <div className="stat-card-new p-2 bg-white rounded-3 border shadow-sm">
                <div className="stat-icon-wrap-new bg-primary-soft text-primary mb-2 mx-auto">
                  <FaBoxOpen size={20} />
                </div>
                <h4 className="fw-bold text-dark mb-0">15k+</h4>
                <p className="text-muted mb-0" style={{ fontSize: '0.75rem', fontWeight: '500' }}>Delivered Prints</p>
              </div>
            </Col>
            <Col xs={6} md={3}>
              <div className="stat-card-new p-2 bg-white rounded-3 border shadow-sm">
                <div className="stat-icon-wrap-new bg-primary-soft text-primary mb-2 mx-auto">
                  <FaUsers size={20} />
                </div>
                <h4 className="fw-bold text-dark mb-0">100+</h4>
                <p className="text-muted mb-0" style={{ fontSize: '0.75rem', fontWeight: '500' }}>Corporate Brands</p>
              </div>
            </Col>
            <Col xs={6} md={3}>
              <div className="stat-card-new p-2 bg-white rounded-3 border shadow-sm">
                <div className="stat-icon-wrap-new bg-primary-soft text-primary mb-2 mx-auto">
                  <FaAward size={20} />
                </div>
                <h4 className="fw-bold text-dark mb-0">50+</h4>
                <p className="text-muted mb-0" style={{ fontSize: '0.75rem', fontWeight: '500' }}>Bespoke Categories</p>
              </div>
            </Col>
            <Col xs={6} md={3}>
              <div className="stat-card-new p-2 bg-white rounded-3 border shadow-sm">
                <div className="stat-icon-wrap-new bg-primary-soft text-primary mb-2 mx-auto">
                  <FaHeadset size={20} />
                </div>
                <h4 className="fw-bold text-dark mb-0">24/7</h4>
                <p className="text-muted mb-0" style={{ fontSize: '0.75rem', fontWeight: '500' }}>Personal Advisors</p>
              </div>
            </Col>
          </Row>
        </div>

        {/* Mission Segment */}
        <Row className="align-items-center mb-3 gy-3 pt-2 border-top">
          <Col md={5} className="text-center">
            <div className="about-image-wrapper p-2 bg-white shadow-sm rounded-4 border">
              <img
                src={getImageUrl(missionSection, "/our-mission.jpg")}
                alt="Our Mission"
                className="img-fluid rounded-3 w-100"
                style={{ maxHeight: "220px", objectFit: "cover" }}
              />
            </div>
          </Col>
          <Col md={7} className="ps-lg-3">
            <h4 className="fw-bold text-dark mb-2">
              {missionSection?.section_title || "Our Mission"}
            </h4>
            <p className="text-muted small mb-2">
              {missionSection?.section_content || 
                "To democratize printing by making customized merchandising accessible, simple, and environment-friendly. We help organizations, small businesses, and individuals foster authentic connections through beautiful branded items."}
            </p>
            <ul className="list-unstyled custom-list mt-2 mb-0">
              <li className="mb-2 d-flex align-items-start">
                <FaCheckCircle className="text-success mt-1 me-2 flex-shrink-0" size={14} />
                <span className="small"><strong>No Minimum Order</strong>: Create one custom piece or a thousand, with equal love and speed.</span>
              </li>
              <li className="mb-0 d-flex align-items-start">
                <FaCheckCircle className="text-success mt-1 me-2 flex-shrink-0" size={14} />
                <span className="small"><strong>Premium Sourcing</strong>: Partnering only with ethical manufacturers for our raw fabrics and cups.</span>
              </li>
            </ul>
          </Col>
        </Row>

        {/* Vision Segment */}
        <Row className="align-items-center flex-column-reverse flex-md-row gy-3 mb-3 pt-2 border-top">
          <Col md={7} className="pe-lg-3">
            <h4 className="fw-bold text-dark mb-2">
              {visionSection?.section_title || "Our Vision"}
            </h4>
            <p className="text-muted small mb-2">
              {visionSection?.section_content || 
                "We envision a future where on-demand customized gifting produces zero waste. By deploying intelligent local print nodes and scaling zero-emission operations, we aim to deliver personalized items to any door step globally within 24 hours."}
            </p>
            <ul className="list-unstyled custom-list mt-2 mb-0">
              <li className="mb-2 d-flex align-items-start">
                <FaCheckCircle className="text-success mt-1 me-2 flex-shrink-0" size={14} />
                <span className="small"><strong>Micro-Manufacturing</strong>: Localizing production to minimize transport emission lines.</span>
              </li>
              <li className="mb-0 d-flex align-items-start">
                <FaCheckCircle className="text-success mt-1 me-2 flex-shrink-0" size={14} />
                <span className="small"><strong>Automated Customizer</strong>: Introducing intelligent layout placement assistants.</span>
              </li>
            </ul>
          </Col>
          <Col md={5} className="text-center">
            <div className="about-image-wrapper p-2 bg-white shadow-sm rounded-4 border">
              <img
                src={getImageUrl(visionSection, "/our-vission.jpg")}
                alt="Our Vision"
                className="img-fluid rounded-3 w-100"
                style={{ maxHeight: "250px", objectFit: "cover" }}
              />
            </div>
          </Col>
        </Row>

        {/* Unique Feature Core Columns (Pillars) */}
        <div className="why-choose-us mt-3 py-3 border-top">
          <h4 className="text-center fw-bold text-dark mb-3">
            {whySection?.section_title || "The Printmont Pillars"}
          </h4>
          {whySection ? (
            <p className="text-center text-muted small mb-3 mx-auto" style={{ maxWidth: "700px" }}>
              {whySection.section_content}
            </p>
          ) : null}
          <Row className="g-2">
            <Col md={4}>
              <div className="value-card p-3 rounded-4 border bg-white h-100 shadow-sm text-center">
                <div className="value-icon text-primary bg-primary-soft mb-2 mx-auto rounded-circle d-flex align-items-center justify-content-center">
                  <FaPrint size={20} />
                </div>
                <h6 className="fw-bold text-dark mb-2">Perfect Sublimation</h6>
                <p className="text-muted small mb-0" style={{ fontSize: "0.8rem" }}>
                  Our prints are chemical-fused. This means your cups and shirts won't crack, peel, or fade during washes.
                </p>
              </div>
            </Col>
            <Col md={4}>
              <div className="value-card p-3 rounded-4 border bg-white h-100 shadow-sm text-center">
                <div className="value-icon text-primary bg-primary-soft mb-2 mx-auto rounded-circle d-flex align-items-center justify-content-center">
                  <FaLeaf size={20} />
                </div>
                <h6 className="fw-bold text-dark mb-2">Eco-Friendly Stains</h6>
                <p className="text-muted small mb-0" style={{ fontSize: "0.8rem" }}>
                  Every print is done with non-hazardous inks that don't release toxins, keeping your family safe.
                </p>
              </div>
            </Col>
            <Col md={4}>
              <div className="value-card p-3 rounded-4 border bg-white h-100 shadow-sm text-center">
                <div className="value-icon text-primary bg-primary-soft mb-2 mx-auto rounded-circle d-flex align-items-center justify-content-center">
                  <FaShippingFast size={20} />
                </div>
                <h6 className="fw-bold text-dark mb-2">Smart Protective Pack</h6>
                <p className="text-muted small mb-0" style={{ fontSize: "0.8rem" }}>
                  Breakage-proof bubble armor is used for every product. If it breaks in transit, we replace it instantly.
                </p>
              </div>
            </Col>
          </Row>
        </div>
      </Container>
    </div>
  );
};

export default AboutPage;
