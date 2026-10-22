import React, { useState, useEffect, useMemo } from "react";
import { Container, Row, Col, Modal, Button, Form, Spinner } from "react-bootstrap";
import { Link } from "react-router-dom";
import usePageSections from "./usePageSections";
import { updatePageSEO } from "../../utils/seo";
import { API_ENDPOINTS, ASSET_URL } from "../../config/apiEndpoints";
import { toast } from "react-hot-toast";
import BulkProductCard from "./BulkProductCard";
import ResponsiveHeroCarousel from "../home/banners/ResponsiveHeroCarousel";
import useHomeBanners from "../home/hooks/useHomeBanners";
import "./bulk-order.css";
import {
  FaCheckCircle,
  FaTruck,
  FaGlobeAmericas,
  FaShieldAlt,
  FaHeadset,
  FaPhoneAlt,
  FaEnvelope,
  FaPlus,
  FaMinus,
  FaBoxOpen,
  FaFileContract,
  FaLayerGroup,
  FaUsers,
  FaMapMarkerAlt,
  FaTags
} from "react-icons/fa";

/** Benefit icon mapping */
const getBenefitIcon = (index) => {
  const icons = [
    <FaTags size={18} key="tags" />,
    <FaLayerGroup size={18} key="layers" />,
    <FaUsers size={18} key="users" />,
    <FaMapMarkerAlt size={18} key="marker" />,
    <FaFileContract size={18} key="contract" />,
    <FaShieldAlt size={18} key="shield" />
  ];
  return icons[index % icons.length];
};

const BulkOrderPage = () => {
  const { many, one, loading, failed } = usePageSections("bulk-orders");
  const { banners: topBanners } = useHomeBanners(`${API_ENDPOINTS.BANNERS}?section=bulk_orders_top_banner`);

  const hero = one("hero");
  const rawProducts = many("product");
  const benefits = many("benefit");
  const fulfillment = one("fulfillment");
  const sampleCta = one("sample_cta");
  const faqs = many("faq");
  const contact = one("contact");

  // Active category filter for product catalog
  const [activeCategory, setActiveCategory] = useState("All");

  // FAQ Accordion active states (open/close)
  const [openFaqIdx, setOpenFaqIdx] = useState(0);

  // Inquiry / Sample Request Modal
  const [showModal, setShowModal] = useState(false);
  const [modalType, setModalType] = useState("quote"); // "quote" | "sample"
  const [selectedProduct, setSelectedProduct] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    company: "",
    product: "",
    quantity: "100",
    message: ""
  });

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: hero?.title ? `${hero.title} | Printmont` : "Bulk Orders & Wholesale Printing - Printmont",
      description: hero?.content || "Printmont provides end-to-end custom printing, corporate merchandise, and bulk promotional product solutions across India.",
      keywords: "bulk printing, wholesale custom merchandise, corporate gifting bulk, t-shirts bulk printing, custom diary bulk",
      type: "website"
    });
  }, [hero]);

  // Extract distinct categories from products
  const categories = useMemo(() => {
    const set = new Set();
    rawProducts.forEach((p) => {
      if (p.extra) set.add(p.extra.trim());
    });
    return ["All", ...Array.from(set)];
  }, [rawProducts]);

  // Filter products by category
  const filteredProducts = useMemo(() => {
    if (activeCategory === "All") return rawProducts;
    return rawProducts.filter((p) => (p.extra || "").trim() === activeCategory);
  }, [rawProducts, activeCategory]);

  const handleOpenQuoteModal = (productName = "") => {
    setModalType("quote");
    setSelectedProduct(productName);
    setFormData((prev) => ({ ...prev, product: productName || prev.product }));
    setShowModal(true);
  };

  const handleOpenSampleModal = () => {
    setModalType("sample");
    setSelectedProduct("Sample Kit Request");
    setFormData((prev) => ({ ...prev, product: "Curated Corporate Sample Kit" }));
    setShowModal(true);
  };

  const handleSubmitInquiry = async (e) => {
    e.preventDefault();
    if (!formData.name || !formData.phone || !formData.email) {
      toast.error("Please fill in your name, email, and phone number.");
      return;
    }

    try {
      setIsSubmitting(true);
      const res = await fetch(API_ENDPOINTS.CONTACT, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: formData.name,
          email: formData.email,
          phone: formData.phone,
          subject: modalType === "sample" ? `Sample Kit Request - ${formData.company || "Direct"}` : `Bulk Order Quote - ${formData.product || "General"} (${formData.quantity} pcs)`,
          message: `Company: ${formData.company || "N/A"}\nProduct: ${formData.product}\nQuantity: ${formData.quantity}\nRequirements: ${formData.message}`
        })
      });

      const json = await res.json();
      if (json.success || res.ok) {
        toast.success(modalType === "sample" ? "Sample request submitted! Our corporate desk will contact you." : "Bulk inquiry submitted successfully! Quote will be sent shortly.");
        setShowModal(false);
        setFormData({
          name: "",
          email: "",
          phone: "",
          company: "",
          product: "",
          quantity: "100",
          message: ""
        });
      } else {
        toast.error(json.message || "Failed to submit inquiry. Please try again.");
      }
    } catch (err) {
      console.error("Inquiry submission error:", err);
      toast.error("Network error. Please try again or reach out at bulk@printmont.com.");
    } finally {
      setIsSubmitting(false);
    }
  };

  // Helper to format image path
  const resolveImg = (img) => {
    if (!img) return null;
    if (img.startsWith("http://") || img.startsWith("https://")) return img;
    if (img.startsWith("/")) return img;
    return `${ASSET_URL}${img}`;
  };

  // Badges array from fulfillment extra (delimited by |)
  const fulfillmentBadges = useMemo(() => {
    if (!fulfillment?.extra) return ["100+ Countries", "Express Delivery", "Zero-Defect Promise"];
    return fulfillment.extra.split("|").map((s) => s.trim()).filter(Boolean);
  }, [fulfillment]);

  // Contact email and phone from contact extra (Email | Phone)
  const { contactEmail, contactPhone } = useMemo(() => {
    if (!contact?.extra) return { contactEmail: "bulk@printmont.com", contactPhone: "+91-9818532463" };
    const parts = contact.extra.split("|").map((s) => s.trim());
    return {
      contactEmail: parts[0] || "bulk@printmont.com",
      contactPhone: parts[1] || "+91-9818532463"
    };
  }, [contact]);

  if (loading) {
    return (
      <div className="bk-page py-5 text-center">
        <Container className="bk-container">
          <Spinner animation="border" variant="primary" />
          <p className="text-muted mt-3">Loading Bulk Orders &amp; Wholesale Catalog...</p>
        </Container>
      </div>
    );
  }

  return (
    <div className="bk-page pb-4">
      {/* 1. Full-Width Top Banner Carousel / Image (100% Bleed) */}
      <div className="bk-fullwidth-banner-wrap">
        {topBanners.length > 0 ? (
          <ResponsiveHeroCarousel banners={topBanners} carouselId="bulkOrdersTopCarousel" />
        ) : hero?.image_path ? (
          <img 
            src={resolveImg(hero.image_path)} 
            alt={hero.title || "Bulk Orders Banner"} 
            className="bk-fullwidth-banner-img"
          />
        ) : (
          <div className="bk-top-banner-placeholder">
            <span className="text-danger fw-bold" style={{ fontSize: "15px" }}>
              Banner and Banner Carousel Image
            </span>
          </div>
        )}
      </div>

      <Container className="bk-container pt-3 pt-md-4">
        {/* Hero / Header Section Card */}
        <header className="bk-hero-section">
          <span className="bk-eyebrow">{hero?.extra || "CORPORATE & ENTERPRISE SUPPLY"}</span>
          <h1 className="bk-title">{hero?.title || "Bulk Orders & Wholesale Printing"}</h1>
          <p className="bk-subtitle">
            {hero?.content || "PrintMont provides end-to-end custom printing, corporate merchandise, and bulk promotional product solutions for companies, events, startups, and institutions across India."}
          </p>
        </header>

        {/* Category Filter Tabs Bar */}
        {categories.length > 1 && (
          <div className="bk-filter-bar">
            {categories.map((cat) => (
              <button
                key={cat}
                type="button"
                className={`bk-filter-pill ${activeCategory === cat ? "active" : ""}`}
                onClick={() => setActiveCategory(cat)}
              >
                {cat}
              </button>
            ))}
          </div>
        )}

        {/* Bulk Products Catalog Grid (2 columns on mobile, 4 on desktop) */}
        {filteredProducts.length > 0 && (
          <section className="bk-product-grid">
            <Row className="g-3 g-md-4">
              {filteredProducts.map((p) => (
                <Col xs={6} md={4} lg={3} key={p.id}>
                  <BulkProductCard
                    product={p}
                    resolveImg={resolveImg}
                    onQuoteClick={handleOpenQuoteModal}
                  />
                </Col>
              ))}
            </Row>
          </section>
        )}

        {/* Why Partner with Printmont for Bulk Supply */}
        {benefits.length > 0 && (
          <section className="bk-why-partner-section">
            <h2 className="bk-section-title">Why Partner with Printmont for Bulk Supply</h2>
            <Row className="g-3 g-md-4">
              {benefits.map((b, idx) => (
                <Col xs={12} md={6} lg={4} key={b.id}>
                  <div className="bk-benefit-card">
                    <div className="bk-benefit-header">
                      <span className="bk-benefit-number">{b.extra || idx + 1}</span>
                      <h3 className="bk-benefit-title">{b.title}</h3>
                    </div>
                    {b.content && <p className="bk-benefit-desc">{b.content}</p>}
                  </div>
                </Col>
              ))}
            </Row>
          </section>
        )}

        {/* Global Fulfillment Network (Delivering Love to 100+ Countries) */}
        {fulfillment && (
          <section className="bk-fulfillment-card">
            <Row className="g-0 align-items-center">
              <Col xs={12} lg={6}>
                <div className="bk-fulfillment-img-wrap">
                  <img
                    src={resolveImg(fulfillment.image_path) || "/about-delivering-love.png"}
                    alt={fulfillment.title || "Global Delivery Network"}
                    className="bk-fulfillment-img"
                    onError={(e) => { e.target.src = "/about-delivering-love.png"; }}
                  />
                </div>
              </Col>
              <Col xs={12} lg={6}>
                <div className="bk-fulfillment-content">
                  <span className="bk-fulfillment-eyebrow">
                    <FaGlobeAmericas /> {fulfillmentBadges[0] || "Global Fulfillment Network"}
                  </span>
                  <h2 className="bk-fulfillment-title">{fulfillment.title}</h2>
                  {fulfillment.content && (
                    <p className="bk-fulfillment-desc">{fulfillment.content}</p>
                  )}
                  <div className="bk-fulfillment-badges">
                    {fulfillmentBadges.slice(1).map((badge, idx) => (
                      <span key={idx} className="bk-fulfillment-badge">
                        <FaCheckCircle size={13} /> {badge}
                      </span>
                    ))}
                  </div>
                </div>
              </Col>
            </Row>
          </section>
        )}

        {/* Sample Request CTA ("Touch it. Test it. Trust it.") */}
        {sampleCta && (
          <section className="bk-sample-cta">
            <div>
              <h3 className="bk-sample-title">{sampleCta.title}</h3>
              <p className="bk-sample-sub">
                {sampleCta.content || "Order pre-production sample kit before confirming bulk order"}
              </p>
            </div>
            <button
              type="button"
              className="bk-sample-btn"
              onClick={handleOpenSampleModal}
            >
              {sampleCta.extra || "Order Samples Now"}
            </button>
          </section>
        )}

        {/* Frequently Asked Questions Accordion */}
        {faqs.length > 0 && (
          <section className="bk-faq-section">
            <h2 className="bk-section-title">Frequently Asked Questions</h2>
            <div className="mx-auto" style={{ maxWidth: "980px" }}>
              {faqs.map((faq, idx) => {
                const isOpen = openFaqIdx === idx;
                return (
                  <div className="bk-faq-item" key={faq.id}>
                    <div
                      className="bk-faq-question"
                      onClick={() => setOpenFaqIdx(isOpen ? null : idx)}
                    >
                      <span>{faq.title}</span>
                      <span className="bk-faq-icon">
                        {isOpen ? <FaMinus size={13} /> : <FaPlus size={13} />}
                      </span>
                    </div>
                    {isOpen && faq.content && (
                      <div className="bk-faq-answer">
                        {faq.content}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          </section>
        )}

        {/* Bottom Contact / Direct Quote Banner */}
        <section className="bk-bottom-contact-card">
          <h2 className="bk-bottom-contact-title">
            {contact?.title || "Ready to Discuss Your Bulk Requirement?"}
          </h2>
          <p className="bk-bottom-contact-desc">
            {contact?.content || "Our corporate bulk experts are standing by to assist with instant price quotes, digital mockups, and technical guidance."}
          </p>
          <div className="bk-bottom-contact-actions">
            <button
              type="button"
              className="bk-btn-quote-main"
              onClick={() => handleOpenQuoteModal("")}
            >
              Get Custom Quote
            </button>
            {contactPhone && (
              <a href={`tel:${contactPhone.replace(/\s+/g, '')}`} className="bk-btn-call-main">
                <FaPhoneAlt size={13} /> {contactPhone}
              </a>
            )}
            {contactEmail && (
              <a href={`mailto:${contactEmail}`} className="bk-btn-call-main">
                <FaEnvelope size={13} /> {contactEmail}
              </a>
            )}
          </div>
        </section>

      </Container>

      {/* Interactive Bulk Inquiry / Sample Request Modal */}
      <Modal show={showModal} onHide={() => setShowModal(false)} centered size="lg">
        <Modal.Header closeButton className="border-0 pb-0">
          <Modal.Title className="fw-bold fs-5 text-dark">
            {modalType === "sample" ? "Request Corporate Sample Kit" : "Request Instant Bulk Quote"}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body className="pt-2 pb-4">
          <p className="text-secondary small mb-3">
            {modalType === "sample"
              ? "Share your delivery address and requirements. Our team will prepare and dispatch a customized sample kit."
              : "Tell us about your order quantity, preferred customization, and target timeline. We will send a formal quote within 4 hours."}
          </p>

          <Form onSubmit={handleSubmitInquiry}>
            <Row className="g-3">
              <Col xs={12} md={6}>
                <Form.Group>
                  <Form.Label className="small fw-semibold text-dark">Full Name *</Form.Label>
                  <Form.Control
                    type="text"
                    required
                    placeholder="Enter your name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col xs={12} md={6}>
                <Form.Group>
                  <Form.Label className="small fw-semibold text-dark">Email Address *</Form.Label>
                  <Form.Control
                    type="email"
                    required
                    placeholder="name@company.com"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col xs={12} md={6}>
                <Form.Group>
                  <Form.Label className="small fw-semibold text-dark">Phone / WhatsApp Number *</Form.Label>
                  <Form.Control
                    type="tel"
                    required
                    placeholder="+91 98765 43210"
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col xs={12} md={6}>
                <Form.Group>
                  <Form.Label className="small fw-semibold text-dark">Company / Organization</Form.Label>
                  <Form.Control
                    type="text"
                    placeholder="Company or Brand name"
                    value={formData.company}
                    onChange={(e) => setFormData({ ...formData, company: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col xs={12} md={8}>
                <Form.Group>
                  <Form.Label className="small fw-semibold text-dark">Selected Product / Item</Form.Label>
                  <Form.Control
                    type="text"
                    placeholder="e.g. Bulk Polo T-shirts, Hardbound Diaries"
                    value={formData.product}
                    onChange={(e) => setFormData({ ...formData, product: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col xs={12} md={4}>
                <Form.Group>
                  <Form.Label className="small fw-semibold text-dark">Estimated Quantity</Form.Label>
                  <Form.Control
                    type="text"
                    placeholder="e.g. 50, 100, 500 pcs"
                    value={formData.quantity}
                    onChange={(e) => setFormData({ ...formData, quantity: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col xs={12}>
                <Form.Group>
                  <Form.Label className="small fw-semibold text-dark">Order Details / Special Requirements</Form.Label>
                  <Form.Control
                    as="textarea"
                    rows={3}
                    placeholder="Specify customization (e.g. chest logo screen printing, Pantone colors, target delivery date)..."
                    value={formData.message}
                    onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                  />
                </Form.Group>
              </Col>
              <Col xs={12} className="text-end mt-4">
                <Button variant="secondary" className="me-2" onClick={() => setShowModal(false)}>
                  Cancel
                </Button>
                <Button variant="primary" type="submit" disabled={isSubmitting}>
                  {isSubmitting ? (
                    <>
                      <Spinner animation="border" size="sm" className="me-2" />
                      Submitting...
                    </>
                  ) : modalType === "sample" ? (
                    "Request Sample Kit"
                  ) : (
                    "Submit Bulk Inquiry"
                  )}
                </Button>
              </Col>
            </Row>
          </Form>
        </Modal.Body>
      </Modal>
    </div>
  );
};

export default BulkOrderPage;
