import React, { useEffect, useState, useMemo } from "react";
import { Link } from "react-router-dom";
import { Container, Row, Col, Spinner } from "react-bootstrap";
import { 
  FaShieldAlt, 
  FaLock, 
  FaCreditCard, 
  FaUserShield, 
  FaMobileAlt, 
  FaBoxOpen, 
  FaHeadset, 
  FaSearch, 
  FaCheckCircle, 
  FaExclamationTriangle, 
  FaChevronDown,
  FaFileContract,
  FaPhoneAlt,
  FaQrcode,
  FaBuilding
} from "react-icons/fa";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import { updatePageSEO } from "../../utils/seo";
import PageHero from "../common/PageHero";
import "./security.css";

const TRUST_PILLARS = [
  {
    icon: <FaLock />,
    colorClass: "blue",
    title: "256-Bit SSL/TLS Encryption",
    tag: "Bank-Grade Protocol",
    desc: "All transactions, browsing sessions, and customer interactions are encrypted using 256-bit SSL certificates to prevent unauthorized data interception."
  },
  {
    icon: <FaCreditCard />,
    colorClass: "green",
    title: "PCI-DSS Level-1 Compliant",
    tag: "Global Standard",
    desc: "Card processing strictly complies with highest global PCI-DSS Level-1 security standards via India's top RBI-licensed payment gateways (Razorpay, PayU)."
  },
  {
    icon: <FaUserShield />,
    colorClass: "orange",
    title: "Zero Sensitive Card Storage",
    tag: "100% Confidential",
    desc: "Printmont never stores your 16-digit card number, CVV, or NetBanking passwords on any server. Sensitive card tokens remain exclusively with your bank."
  },
  {
    icon: <FaMobileAlt />,
    colorClass: "purple",
    title: "Mandatory 3D Secure / OTP",
    tag: "Two-Factor Auth",
    desc: "Every online checkout requires direct One-Time Password (OTP) verification sent directly by your issuing bank to your registered mobile number."
  },
  {
    icon: <FaBoxOpen />,
    colorClass: "blue",
    title: "Tamper-Evident Packaging",
    tag: "Sealed Logistics",
    desc: "All custom prints, corporate merchandise, and gifts are dispatched in sealed, tamper-evident security packaging with live end-to-end GPS delivery tracking."
  },
  {
    icon: <FaHeadset />,
    colorClass: "green",
    title: "24x7 Real-Time Fraud Shield",
    tag: "Continuous Audit",
    desc: "Automated transaction anomaly detection and dedicated resolution desk actively monitoring for accidental double-debits or suspicious activity."
  }
];

const PAYMENT_METHODS = [
  { name: "Credit / Debit Cards", desc: "Visa, MasterCard, RuPay, Maestro, American Express", icon: <FaCreditCard /> },
  { name: "UPI & Instant QR", desc: "Google Pay, PhonePe, Paytm, BHIM, Cred, Amazon Pay", icon: <FaQrcode /> },
  { name: "Net Banking (50+ Banks)", desc: "SBI, HDFC, ICICI, Axis, Kotak & All Major Banks", icon: <FaBuilding /> },
  { name: "Printmont Coins & Wallet", desc: "Instant 1-click reward checkout balance", icon: <FaCheckCircle /> },
  { name: "Corporate Invoicing", desc: "GST Compliant B2B invoices with 100% Input Tax Credit", icon: <FaFileContract /> }
];

export default function SecurityInfo() {
  const [sections, setSections] = useState([]);
  const [loading, setLoading] = useState(true);
  const [failed, setFailed] = useState(false);
  const [openItems, setOpenItems] = useState({});
  const [searchQuery, setSearchQuery] = useState("");

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: "Safe & Secure Shopping - 100% Payment Protection | Printmont",
      description: "Experience 100% safe, bank-grade encrypted shopping at Printmont. PCI-DSS compliant gateways, 3D Secure OTP verification, and zero sensitive card storage.",
      keywords: "safe and secure shopping, printmont payment safety, pci dss printing, encrypted custom gifts, secure checkout",
      type: "website"
    });

    const fetchSections = async () => {
      try {
        setLoading(true);
        const res = await fetch(API_ENDPOINTS.SECURITY || "/api/security-api.php");
        const json = await res.json();

        if (json.success && Array.isArray(json.data)) {
          setSections(json.data);
          // By default, open first 2 FAQs
          const initialOpen = {};
          json.data.slice(0, 2).forEach(item => {
            initialOpen[item.id] = true;
          });
          setOpenItems(initialOpen);
        } else {
          setFailed(true);
        }
      } catch (err) {
        console.error("Failed to load security sections:", err);
        setFailed(true);
      } finally {
        setLoading(false);
      }
    };

    fetchSections();
  }, []);

  const toggleAccordion = (id) => {
    setOpenItems(prev => ({
      ...prev,
      [id]: !prev[id]
    }));
  };

  // Filter FAQ items by search
  const filteredFaqs = useMemo(() => {
    if (!searchQuery.trim()) return sections;
    const q = searchQuery.toLowerCase().trim();
    return sections.filter(sec => 
      sec.heading?.toLowerCase().includes(q) || 
      sec.content?.toLowerCase().includes(q)
    );
  }, [sections, searchQuery]);

  const areAllExpanded = useMemo(() => {
    return filteredFaqs.length > 0 && filteredFaqs.every(f => openItems[f.id]);
  }, [filteredFaqs, openItems]);

  const handleToggleAll = () => {
    if (areAllExpanded) {
      setOpenItems({});
    } else {
      const nextOpen = {};
      filteredFaqs.forEach(f => {
        nextOpen[f.id] = true;
      });
      setOpenItems(nextOpen);
    }
  };

  return (
    <div className="sec-page-wrap">
      {/* 1. Standard Reusable Hero Section with Breadcrumb for Desktop & Mobile */}
      <PageHero
        breadcrumbs={[
          { label: "Home", path: "/" },
          { label: "Safe & Secure Shopping" }
        ]}
        tag="100% PAYMENT PROTECTION"
        tagIcon={<FaShieldAlt />}
        title="Safe & Secure Shopping at Printmont"
        titleIcon={<FaUserShield />}
        description="Your privacy, financial data, and payments are protected with bank-grade 256-bit SSL encryption, PCI-DSS compliance, and mandatory two-factor OTP verification."
        stats={[
          { label: "SSL Protocol", value: "256-Bit", icon: <FaLock /> },
          { label: "Compliance", value: "PCI-DSS L1", icon: <FaCreditCard /> },
          { label: "Verification", value: "3D Secure", icon: <FaCheckCircle /> },
          { label: "Card Storage", value: "0% Stored", icon: <FaShieldAlt /> }
        ]}
        actions={
          <Link to="/contact" className="pm-hero-action-link" title="Contact 24x7 Customer Care">
            <FaHeadset /> 24x7 Support Desk
          </Link>
        }
      />

      {/* Main Content Body */}
      <Container className="sec-container py-2">
        
        {/* 2. Flipkart-Style Important Security Advisory Banner */}
        <div className="sec-warning-banner mb-4">
          <div className="sec-warning-header">
            <div className="sec-warning-icon-pill">
              <FaExclamationTriangle />
            </div>
            <div>
              <h3 className="sec-warning-title">
                Important Security Advisory for All Printmont Customers
              </h3>
              <p className="sec-warning-sub">
                Protect yourself from phishing, spoofed caller IDs, and online impersonation.
              </p>
            </div>
          </div>
          
          <div className="sec-warning-rules-grid">
            <div className="sec-rule-item">
              <span className="sec-rule-bullet">1</span>
              <span><strong>Never Share Sensitive Credentials:</strong> Printmont representatives will <strong>NEVER</strong> ask for your ATM PIN, NetBanking password, Card CVV, or OTP via phone, email, SMS, or WhatsApp.</span>
            </div>
            <div className="sec-rule-item">
              <span className="sec-rule-bullet">2</span>
              <span><strong>No Remote Access Apps:</strong> Never install third-party screen sharing apps (e.g. AnyDesk, TeamViewer, QuickSupport) at the instruction of any caller claiming to assist with payments.</span>
            </div>
            <div className="sec-rule-item">
              <span className="sec-rule-bullet">3</span>
              <span><strong>Verify Official Website URL:</strong> Always ensure the address bar begins with <code>https://printmont.com</code> with a valid padlock icon before entering any login credentials or payment info.</span>
            </div>
            <div className="sec-rule-item">
              <span className="sec-rule-bullet">4</span>
              <span><strong>Immediate Fraud Reporting:</strong> If you suspect any fraudulent attempt or unauthorized deduction, inform your bank immediately and reach out to our 24x7 Support Desk.</span>
            </div>
          </div>
        </div>

        {/* 3. Six Core Security Pillars Grid (Flipkart Clean Theme) */}
        <div className="mb-4 mb-lg-5">
          <div className="sec-section-header mb-3">
            <div>
              <span className="sec-section-tag">COMPREHENSIVE DATA SAFEGUARDS</span>
              <h2 className="sec-section-title">
                How We Protect Your Payments &amp; Information
              </h2>
              <p className="sec-section-desc">
                Bank-grade safeguards and multi-layered encryption active at every single touchpoint.
              </p>
            </div>
          </div>

          <Row className="g-3 g-md-4">
            {TRUST_PILLARS.map((pillar, idx) => (
              <Col lg={4} md={6} xs={12} key={idx}>
                <div className="sec-pillar-card">
                  <div className="d-flex align-items-center justify-content-between mb-3">
                    <div className={`sec-pillar-icon-box ${pillar.colorClass}`}>
                      {pillar.icon}
                    </div>
                    <span className="sec-pillar-badge">{pillar.tag}</span>
                  </div>
                  <div className="sec-pillar-content">
                    <h3 className="sec-pillar-title">{pillar.title}</h3>
                    <p className="sec-pillar-desc">{pillar.desc}</p>
                  </div>
                </div>
              </Col>
            ))}
          </Row>
        </div>

        {/* 4. Certified Payment Gateways Bar */}
        <div className="sec-partners-card mb-4 mb-lg-5">
          <div className="row align-items-center g-3">
            <div className="col-lg-4 col-12">
              <span className="sec-partners-tag">RBI AUTHORIZED NETWORKS</span>
              <h3 className="sec-partners-title">
                100% Secure Payment Options
              </h3>
              <p className="sec-partners-desc">
                All checkouts are encrypted and routed directly through verified financial clearing networks.
              </p>
            </div>
            <div className="col-lg-8 col-12">
              <div className="sec-partners-list">
                {PAYMENT_METHODS.map((pm, i) => (
                  <div className="sec-partner-pill" key={i} title={pm.desc}>
                    <span className="sec-partner-icon">{pm.icon}</span>
                    <div className="sec-partner-text">
                      <span className="sec-partner-name">{pm.name}</span>
                      <small className="sec-partner-sub">{pm.desc}</small>
                    </div>
                    <FaCheckCircle className="sec-partner-check" />
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* 5. Dynamic FAQ / Q&A Accordion (Admin-Controlled) */}
        <div className="sec-faq-box mb-4 mb-lg-5">
          <div className="sec-faq-header-bar">
            <div>
              <span className="sec-faq-tag">HELP &amp; CLARIFICATIONS</span>
              <h3 className="sec-faq-title">
                <FaShieldAlt className="text-primary me-2" /> Frequently Asked Security Questions
              </h3>
              <p className="sec-faq-sub">
                Answers to common queries regarding online payments, refunds, OTPs, and transaction protection.
              </p>
            </div>

            <div className="sec-faq-controls">
              {/* Search Questions */}
              <div className="sec-faq-search-box">
                <FaSearch className="sec-faq-search-icon" />
                <input
                  type="text"
                  placeholder="Search questions (e.g. card, OTP, refund)..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="sec-faq-search-input"
                  aria-label="Search security questions"
                />
                {searchQuery && (
                  <button 
                    type="button" 
                    className="sec-faq-search-clear" 
                    onClick={() => setSearchQuery("")}
                    title="Clear search"
                  >
                    ✕
                  </button>
                )}
              </div>

              {/* Expand / Collapse All Toggle */}
              {filteredFaqs.length > 0 && (
                <button
                  type="button"
                  className="sec-toggle-all-btn"
                  onClick={handleToggleAll}
                >
                  {areAllExpanded ? "Collapse All" : "Expand All"}
                </button>
              )}
            </div>
          </div>

          <div className="sec-faq-list">
            {loading ? (
              <div className="text-center py-5">
                <Spinner animation="border" variant="primary" />
                <p className="text-muted mt-3 small">Loading security questions from database...</p>
              </div>
            ) : filteredFaqs.length === 0 ? (
              <div className="text-center py-5 text-muted">
                <p className="mb-2 fs-5">No security questions found matching "{searchQuery}"</p>
                <button 
                  type="button" 
                  className="btn btn-outline-primary btn-sm"
                  onClick={() => setSearchQuery("")}
                >
                  Reset Search
                </button>
              </div>
            ) : (
              filteredFaqs.map((faq, index) => {
                const isOpen = !!openItems[faq.id];
                const itemNum = String(index + 1).padStart(2, '0');

                return (
                  <div className={`sec-faq-item ${isOpen ? "open" : ""}`} key={faq.id || index}>
                    <button
                      type="button"
                      className={`sec-faq-question-btn ${isOpen ? "open" : ""}`}
                      onClick={() => toggleAccordion(faq.id)}
                      aria-expanded={isOpen}
                    >
                      <span className="sec-faq-q-text">
                        <span className="sec-faq-q-badge">{itemNum}</span>
                        <span className="sec-faq-q-title">{faq.heading}</span>
                      </span>
                      <FaChevronDown className="sec-faq-chevron" />
                    </button>

                    {isOpen && (
                      <div className="sec-faq-answer">
                        <div 
                          className="sec-faq-answer-content"
                          dangerouslySetInnerHTML={{ __html: faq.content || "" }} 
                        />
                      </div>
                    )}
                  </div>
                );
              })
            )}
          </div>
        </div>

        {/* 6. Flipkart Style 24x7 Customer Support Card */}
        <div className="sec-contact-card">
          <div className="row align-items-center g-4">
            <div className="col-lg-7 col-12">
              <span className="sec-contact-tag">ROUND-THE-CLOCK FRAUD PROTECTION</span>
              <h3 className="sec-contact-title">
                Need Immediate Help or Suspect Suspicious Activity?
              </h3>
              <p className="sec-contact-sub">
                Our dedicated trust and safety team is available 24x7 to assist with payment verification, charge dispute guidance, and rapid order security checks.
              </p>
            </div>
            <div className="col-lg-5 col-12 text-lg-end d-flex flex-wrap gap-2 justify-content-lg-end">
              <Link to="/contact" className="sec-btn-support">
                <FaHeadset /> Contact 24x7 Support Desk
              </Link>
              <Link to="/privacy-policy" className="sec-btn-outline">
                <FaFileContract /> Privacy Policy
              </Link>
            </div>
          </div>
        </div>

      </Container>
    </div>
  );
}
