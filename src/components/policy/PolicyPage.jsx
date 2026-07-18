import React, { useState } from "react";
import { Container, Row, Col, Nav, Card, Badge, Form, InputGroup } from "react-bootstrap";
import { useLocation, Link, Routes, Route } from "react-router-dom";
import "./Policy.css";
import { 
  FaFileContract, 
  FaUserShield, 
  FaTruck, 
  FaUndoAlt, 
  FaCheckCircle, 
  FaClock, 
  FaBoxOpen, 
  FaLock, 
  FaCookie, 
  FaDatabase,
  FaSearch,
  FaShieldAlt,
  FaEnvelope,
  FaGavel,
  FaLaptopCode,
  FaCopyright,
  FaBan
} from "react-icons/fa";

const PolicyPage = () => {
  const location = useLocation();
  const [searchTerm, setSearchTerm] = useState("");

  // Determine active tab key based on current URL path
  const getActiveKey = () => {
    const p = location.pathname.toLowerCase();
    if (p.includes("terms-of-use") || p.includes("termsofuse")) return "terms-of-use";
    if (p.includes("privacy")) return "privacy";
    if (p.includes("shipping")) return "shipping";
    if (p.includes("refund") || p.includes("return")) return "refund";
    return "terms";
  };

  const activeKey = getActiveKey();

  const tabs = [
    { key: "terms", path: "/policy/terms", label: "Terms & Conditions", icon: <FaFileContract size={18} /> },
    { key: "terms-of-use", path: "/policy/terms-of-use", label: "Terms of Use", icon: <FaGavel size={18} /> },
    { key: "privacy", path: "/policy/privacy", label: "Privacy Policy", icon: <FaUserShield size={18} /> },
    { key: "shipping", path: "/policy/shipping", label: "Shipping Policy", icon: <FaTruck size={18} /> },
    { key: "refund", path: "/policy/refund", label: "Return & Refund Policy", icon: <FaUndoAlt size={18} /> },
  ];

  return (
    <div className="policy-page bg-light py-3 py-md-5" style={{ minHeight: "85vh", overflowX: "hidden" }}>
      <Container>
        {/* HEADER SECTION */}
        <div className="text-center mb-4 mb-md-5">
          <Badge bg="primary-subtle" className="text-primary fw-bold px-3 py-2 mb-2 rounded-pill text-uppercase fs-7">
            PrintMont Legal & Operations
          </Badge>
          <h1 className="fw-bold text-dark display-6 display-md-5 mb-2">Company Policies & Terms</h1>
          <p className="text-secondary mx-auto fs-6" style={{ maxWidth: "650px", lineHeight: "1.6" }}>
            Transparent legal terms, website usage rules, privacy guidelines, delivery schedules, and refund policies governing PrintMont.
          </p>

          {/* SEARCH FILTER BAR */}
          <div className="mx-auto mt-3" style={{ maxWidth: "500px" }}>
            <InputGroup className="shadow-sm rounded-pill overflow-hidden bg-white border">
              <InputGroup.Text className="bg-white border-0 ps-3 text-muted">
                <FaSearch />
              </InputGroup.Text>
              <Form.Control
                type="text"
                placeholder="Search policy terms (e.g. GST, copyright, shipping, returns)..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="border-0 py-2.5 shadow-none text-dark small"
              />
            </InputGroup>
          </div>
        </div>

        {/* POLICY LAYOUT GRID */}
        <Row className="g-4">
          {/* TABS SIDEBAR / NAVIGATION */}
          <Col lg={3} md={12}>
            <Card className="border-0 shadow-sm rounded-4 p-3 bg-white sticky-top mb-4 mb-lg-0" style={{ top: "90px", zIndex: 10 }}>
              <h6 className="fw-bold text-dark px-2 mb-3 pb-2 border-bottom">Policy Navigation</h6>
              <Nav className="flex-column gap-1">
                {tabs.map((tab) => (
                  <Nav.Item key={tab.key}>
                    <Link
                      to={tab.path}
                      className={`nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 fw-semibold transition-all text-decoration-none ${
                        activeKey === tab.key ? "bg-primary text-white shadow-sm" : "text-secondary hover-bg-light"
                      }`}
                    >
                      <span className={activeKey === tab.key ? "text-white" : "text-primary"}>
                        {tab.icon}
                      </span>
                      <span>{tab.label}</span>
                    </Link>
                  </Nav.Item>
                ))}
              </Nav>

              <div className="mt-4 p-3 bg-primary-subtle rounded-3 text-primary border border-primary-subtle">
                <div className="d-flex align-items-center gap-2 mb-1">
                  <FaShieldAlt /> <strong className="small">Legal Desk Assistance</strong>
                </div>
                <p className="small text-dark mb-2" style={{ fontSize: "0.78rem" }}>
                  Have questions about our terms? Reach our compliance desk.
                </p>
                <a href="mailto:legal@printmont.com" className="small fw-bold text-primary text-decoration-none d-flex align-items-center gap-1">
                  <FaEnvelope size={12} /> legal@printmont.com
                </a>
              </div>
            </Card>
          </Col>

          {/* MAIN CONTENT AREA */}
          <Col lg={9} md={12}>
            <Card className="border-0 shadow-sm rounded-4 p-3 p-md-4 p-lg-5 bg-white">
              <Routes>
                <Route path="/" element={<PolicyContent activeKey={activeKey} searchTerm={searchTerm} />} />
                <Route path="terms" element={<Terms searchTerm={searchTerm} />} />
                <Route path="terms-of-use" element={<TermsOfUseContent searchTerm={searchTerm} />} />
                <Route path="privacy" element={<Privacy searchTerm={searchTerm} />} />
                <Route path="shipping" element={<Shipping searchTerm={searchTerm} />} />
                <Route path="refund" element={<Refund searchTerm={searchTerm} />} />
                <Route path="*" element={<PolicyContent activeKey={activeKey} searchTerm={searchTerm} />} />
              </Routes>
            </Card>
          </Col>
        </Row>
      </Container>
    </div>
  );
};

/* CONTENT ROUTER FALLBACK */
const PolicyContent = ({ activeKey, searchTerm }) => {
  switch (activeKey) {
    case "terms-of-use":
      return <TermsOfUseContent searchTerm={searchTerm} />;
    case "privacy":
      return <Privacy searchTerm={searchTerm} />;
    case "shipping":
      return <Shipping searchTerm={searchTerm} />;
    case "refund":
      return <Refund searchTerm={searchTerm} />;
    default:
      return <Terms searchTerm={searchTerm} />;
  }
};

/* ---------- INDIVIDUAL POLICY COMPONENTS ---------- */

/* 1. TERMS & CONDITIONS (COMMERCIAL CONTRACT & TRANSACTIONS) */
const Terms = ({ searchTerm }) => (
  <section className="policy-section-animation" style={{ lineHeight: "1.8", color: "#334155" }}>
    <div className="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
      <div className="rounded-circle bg-primary-subtle p-3 text-primary d-flex align-items-center justify-content-center" style={{ width: "56px", height: "56px" }}>
        <FaFileContract size={24} />
      </div>
      <div>
        <h3 className="fw-bold mb-0 text-dark">Terms & Conditions of Commercial Sale</h3>
        <p className="text-muted small mb-0">Order Contract & Purchase Terms · Last updated: July 2026</p>
      </div>
    </div>

    <div className="alert alert-warning border-0 rounded-3 mb-4 shadow-xs d-flex align-items-start gap-3" style={{ background: "#fff9db" }}>
      <span className="fs-5 mt-0.5">⚠️</span>
      <div className="small text-dark">
        <strong>Commercial Contract Notice:</strong> These Terms and Conditions govern all commercial purchases, product quotes, order processing, and sales contracts on PrintMont.
      </div>
    </div>

    <Row className="g-3 g-md-4 mb-4">
      {[
        { title: "1. Electronic Record & Contract", desc: "This document is an electronic record under the Information Technology Act, 2000. Purchasing goods on PrintMont constitutes a legally binding sales agreement." },
        { title: "2. Product Pricing & Taxes", desc: "All prices are listed in Indian Rupees (INR) inclusive of GST where applicable. Valid GST invoices are issued for corporate tax credit compliance." },
        { title: "3. Corporate Ownership", desc: "The platform and merchandise supply infrastructure is owned by Printmont Corporation Pvt. Ltd., headquartered in New Delhi, India." },
        { title: "4. Order Verification", desc: "Orders are subject to artwork verification, stock availability, and address confirmation before dispatching to production." },
        { title: "5. Minor Purchases", desc: "Minors under 18 must transact under the supervision of a parent or legal guardian who accepts these binding commercial terms." },
        { title: "6. Jurisdiction & Dispute Resolution", desc: "Any legal claims or disputes arising from transactions on PrintMont shall be subject to the exclusive jurisdiction of courts in New Delhi, India." }
      ].filter(item => !searchTerm || item.title.toLowerCase().includes(searchTerm.toLowerCase()) || item.desc.toLowerCase().includes(searchTerm.toLowerCase()))
      .map((item, idx) => (
        <Col xs={12} md={6} key={idx}>
          <div className="p-3 rounded-3 bg-light border h-100">
            <h6 className="fw-bold text-dark mb-2 fs-6">{item.title}</h6>
            <p className="small text-secondary m-0" style={{ lineHeight: "1.6" }}>{item.desc}</p>
          </div>
        </Col>
      ))}
    </Row>

    <div className="p-3 rounded-3 bg-primary-subtle border border-primary-subtle text-primary">
      <p className="mb-0 fw-semibold small" style={{ lineHeight: "1.6" }}>
        BY TRANSACTING ON PRINTMONT, YOU ACCEPT AND AGREE TO BE BOUND BY THESE COMMERCIAL TERMS AND CONDITIONS.
      </p>
    </div>
  </section>
);

/* 2. TERMS OF USE (WEBSITE & PLATFORM ACCESS RULES) */
const TermsOfUseContent = ({ searchTerm }) => (
  <section className="policy-section-animation" style={{ lineHeight: "1.8", color: "#334155" }}>
    <div className="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
      <div className="rounded-circle bg-info-subtle p-3 text-info d-flex align-items-center justify-content-center" style={{ width: "56px", height: "56px" }}>
        <FaGavel size={24} />
      </div>
      <div>
        <h3 className="fw-bold mb-0 text-dark">Terms of Platform Use</h3>
        <p className="text-muted small mb-0">Website Access & User Guidelines · Last updated: July 2026</p>
      </div>
    </div>

    <p className="text-secondary mb-4 small fs-6" style={{ lineHeight: "1.7" }}>
      These Terms of Use specify the rules, guidelines, and acceptable conduct for accessing, browsing, interacting with design portals, and creating user accounts on PrintMont.
    </p>

    <Row className="g-3 g-md-4 mb-4">
      {[
        { icon: <FaLaptopCode size={22} className="text-info" />, title: "1. Platform Access & Account Security", desc: "Users are responsible for maintaining the confidentiality of their login credentials and all activities occurring under their account." },
        { icon: <FaCopyright size={22} className="text-info" />, title: "2. Intellectual Property Rights", desc: "All website code, UI designs, trademarks, brand logos, graphics, and product templates are owned exclusively by Printmont Corporation Pvt. Ltd." },
        { icon: <FaBan size={22} className="text-info" />, title: "3. Prohibited User Conduct", desc: "Users must not engage in automated scraping, uploading copyrighted graphics without permission, injecting malicious code, or hacking attempts." },
        { icon: <FaCheckCircle size={22} className="text-info" />, title: "4. Custom Design Upload Responsibilities", desc: "You represent that any graphic, trademark, or artwork uploaded for custom printing is owned by you or licensed with full authorization." }
      ].filter(item => !searchTerm || item.title.toLowerCase().includes(searchTerm.toLowerCase()) || item.desc.toLowerCase().includes(searchTerm.toLowerCase()))
      .map((item, idx) => (
        <Col xs={12} md={6} key={idx}>
          <div className="p-3 rounded-3 bg-light border h-100">
            <div className="d-flex align-items-center gap-2 mb-2">
              {item.icon}
              <h6 className="fw-bold text-dark m-0 fs-6">{item.title}</h6>
            </div>
            <p className="small text-secondary m-0" style={{ lineHeight: "1.6" }}>{item.desc}</p>
          </div>
        </Col>
      ))}
    </Row>

    <div className="p-3 rounded-3 bg-info-subtle border border-info-subtle text-info-emphasis small">
      <strong>Disclaimer:</strong> In case of any discrepancy between translations, the official English version of the Terms of Use shall take precedence.
    </div>
  </section>
);

/* 3. PRIVACY POLICY */
const Privacy = ({ searchTerm }) => (
  <section className="policy-section-animation" style={{ lineHeight: "1.8", color: "#334155" }}>
    <div className="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
      <div className="rounded-circle bg-success-subtle p-3 text-success d-flex align-items-center justify-content-center" style={{ width: "56px", height: "56px" }}>
        <FaUserShield size={24} />
      </div>
      <div>
        <h3 className="fw-bold mb-0 text-dark">Privacy & Data Security Policy</h3>
        <p className="text-muted small mb-0">Information Security · Last updated: July 2026</p>
      </div>
    </div>

    <p className="text-secondary mb-4 small fs-6" style={{ lineHeight: "1.7" }}>
      Your privacy is fundamental to our business. PrintMont collects personal information solely to fulfill custom orders, improve design previews, and deliver seamless logistics services.
    </p>

    <Row className="g-3 g-md-4 mb-4">
      <Col xs={12} md={4}>
        <div className="card h-100 border-0 p-3 rounded-3 text-center bg-light">
          <FaCookie className="text-success mb-2 mx-auto" size={32} />
          <h6 className="fw-bold text-dark mb-1">Cookie Preferences</h6>
          <p className="small text-muted mb-0" style={{ lineHeight: "1.5" }}>We use essential cookies to maintain shopping cart items, design mockups, and login sessions.</p>
        </div>
      </Col>

      <Col xs={12} md={4}>
        <div className="card h-100 border-0 p-3 rounded-3 text-center bg-light">
          <FaDatabase className="text-success mb-2 mx-auto" size={32} />
          <h6 className="fw-bold text-dark">Data Deletion Rights</h6>
          <p className="small text-muted mb-0" style={{ lineHeight: "1.5" }}>Users maintain absolute rights to request total deletion of their account records and uploaded graphics.</p>
        </div>
      </Col>

      <Col xs={12} md={4}>
        <div className="card h-100 border-0 p-3 rounded-3 text-center bg-light">
          <FaLock className="text-success mb-2 mx-auto" size={32} />
          <h6 className="fw-bold text-dark">256-Bit SSL Encryption</h6>
          <p className="small text-muted mb-0" style={{ lineHeight: "1.5" }}>All online payments and customer uploads are secured using bank-grade 256-bit SSL encryption protocols.</p>
        </div>
      </Col>
    </Row>

    <h6 className="fw-bold text-dark mb-3">Our Core Privacy Commitments:</h6>
    <ul className="list-unstyled d-flex flex-column gap-2 mb-0 small text-secondary">
      <li className="d-flex align-items-start gap-2">
        <FaCheckCircle className="text-success mt-1 flex-shrink-0" size={14} />
        <span>No unauthorized sharing of personal contact details or proprietary design files with third-party brokers.</span>
      </li>
      <li className="d-flex align-items-start gap-2">
        <FaCheckCircle className="text-success mt-1 flex-shrink-0" size={14} />
        <span>Strict zero-spam policy with instant 1-click email unsubscribe settings.</span>
      </li>
      <li className="d-flex align-items-start gap-2">
        <FaCheckCircle className="text-success mt-1 flex-shrink-0" size={14} />
        <span>Fully compliant with Indian IT Data Protection regulations and PCI-DSS payment security standards.</span>
      </li>
    </ul>
  </section>
);

/* 4. SHIPPING POLICY */
const Shipping = ({ searchTerm }) => (
  <section className="policy-section-animation" style={{ lineHeight: "1.8", color: "#334155" }}>
    <div className="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
      <div className="rounded-circle bg-info-subtle p-3 text-info d-flex align-items-center justify-content-center" style={{ width: "56px", height: "56px" }}>
        <FaTruck size={24} />
      </div>
      <div>
        <h3 className="fw-bold mb-0 text-dark">Shipping & Logistics Policy</h3>
        <p className="text-muted small mb-0">Dispatch Guidelines · Last updated: July 2026</p>
      </div>
    </div>

    <p className="text-secondary mb-4 small fs-6" style={{ lineHeight: "1.7" }}>
      We partner with leading national courier networks (BlueDart, Delhivery, Expressbees) to ensure your custom apparel and gift items arrive safely and on time.
    </p>

    <Row className="g-3 g-md-4 mb-4">
      <Col xs={12} md={4}>
        <div className="card h-100 border-0 p-3 rounded-3 text-center bg-light">
          <FaClock className="text-info mb-2 mx-auto" size={32} />
          <h6 className="fw-bold text-dark mb-1">Production Time</h6>
          <p className="small text-muted mb-0" style={{ lineHeight: "1.5" }}>Standard orders are printed and packed within 2-3 business days following artwork confirmation.</p>
        </div>
      </Col>

      <Col xs={12} md={4}>
        <div className="card h-100 border-0 p-3 rounded-3 text-center bg-light">
          <FaBoxOpen className="text-info mb-2 mx-auto" size={32} />
          <h6 className="fw-bold text-dark">Protective Packaging</h6>
          <p className="small text-muted mb-0" style={{ lineHeight: "1.5" }}>Bubble-wrapped, heavy-duty tamper-proof packaging to prevent transit damage to ceramics & prints.</p>
        </div>
      </Col>

      <Col xs={12} md={4}>
        <div className="card h-100 border-0 p-3 rounded-3 text-center bg-light">
          <FaTruck className="text-info mb-2 mx-auto" size={32} />
          <h6 className="fw-bold text-dark">Live Air Tracking</h6>
          <p className="small text-muted mb-0" style={{ lineHeight: "1.5" }}>Real-time AWB tracking links dispatched via SMS and WhatsApp as soon as parcel is handed over.</p>
        </div>
      </Col>
    </Row>

    <div className="p-3 rounded-3 bg-info-subtle border border-info-subtle text-info-emphasis small">
      <strong>Note on Bulk Orders:</strong> Customized corporate bulk shipments may require extended production lead times. Estimated delivery dates are communicated prior to payment.
    </div>
  </section>
);

/* 5. REFUND & RETURN POLICY */
const Refund = ({ searchTerm }) => (
  <section className="policy-section-animation" style={{ lineHeight: "1.8", color: "#334155" }}>
    <div className="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
      <div className="rounded-circle bg-danger-subtle p-3 text-danger d-flex align-items-center justify-content-center" style={{ width: "56px", height: "56px" }}>
        <FaUndoAlt size={24} />
      </div>
      <div>
        <h3 className="fw-bold mb-0 text-dark">Return & Replacement Policy</h3>
        <p className="text-muted small mb-0">Hassle-Free Guarantee · Last updated: July 2026</p>
      </div>
    </div>

    <p className="text-secondary mb-4 small fs-6" style={{ lineHeight: "1.7" }}>
      We stand behind the quality of our prints. If your order arrives damaged, defective, or misprinted, we provide immediate free replacements or complete refunds.
    </p>

    <Row className="g-3 g-md-4 mb-4">
      <Col xs={12} md={6}>
        <div className="p-3 rounded-3 bg-light border h-100">
          <h6 className="fw-bold text-dark mb-2 fs-6">Return Conditions:</h6>
          <ul className="list-unstyled d-flex flex-column gap-2 mb-0 small text-secondary">
            <li className="d-flex align-items-start gap-2"><FaCheckCircle className="text-danger mt-1 flex-shrink-0" size={14} /> Item must be unused and in original packaging with intact tags.</li>
            <li className="d-flex align-items-start gap-2"><FaCheckCircle className="text-danger mt-1 flex-shrink-0" size={14} /> Defect or damage reports must be raised within 7 days of delivery.</li>
            <li className="d-flex align-items-start gap-2"><FaCheckCircle className="text-danger mt-1 flex-shrink-0" size={14} /> Free reverse pickup scheduled at zero cost for verified defect claims.</li>
          </ul>
        </div>
      </Col>

      <Col xs={12} md={6}>
        <div className="p-3 rounded-3 bg-light border h-100">
          <h6 className="fw-bold text-dark mb-2 fs-6">Refund Timeline:</h6>
          <ul className="list-unstyled d-flex flex-column gap-2 mb-0 small text-secondary">
            <li className="d-flex align-items-start gap-2"><FaCheckCircle className="text-danger mt-1 flex-shrink-0" size={14} /> Returned items inspected within 24 hours of warehouse receipt.</li>
            <li className="d-flex align-items-start gap-2"><FaCheckCircle className="text-danger mt-1 flex-shrink-0" size={14} /> Refunds credited to original payment mode (UPI/Card) within 3-5 business days.</li>
            <li className="d-flex align-items-start gap-2"><FaCheckCircle className="text-danger mt-1 flex-shrink-0" size={14} /> COD orders refunded directly to your verified bank account via IMPS/NEFT.</li>
          </ul>
        </div>
      </Col>
    </Row>

    <div className="p-3 rounded-3 bg-danger-subtle border border-danger-subtle text-danger small">
      <strong>Customer Support Desk:</strong> To initiate a return or replacement, email a photo of the damaged item along with your Order ID to <strong>returns@printmont.com</strong> or call <strong>1800-123-4567</strong>.
    </div>
  </section>
);

export default PolicyPage;
