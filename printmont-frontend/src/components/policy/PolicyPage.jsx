import React from "react";
import { Nav } from "react-bootstrap";
import { Routes, Route, Link, useLocation } from "react-router-dom";
import "./Policy.css";
import PageNotFound from "../pageNotFound/PageNotFound";

const PolicyPage = () => {
  const location = useLocation();

  const tabs = [
    { path: "/policy/terms", label: "Terms & Conditions" },
    { path: "/policy/privacy", label: "Privacy Policy" },
    { path: "/policy/shipping", label: "Shipping Policy" },
    { path: "/policy/refund", label: "Return & Refund Policy" },
  ];

  return (
    <div className="policy-page container-fluid mx-0 px-0 py-3 py-lg-5 bg-white">
      <h2 className="fw-bold text-center mb-4">Our Policies</h2>

      {/* Tabs */}
      <Nav variant="tabs" className="justify-content-start justify-content-md-center mb-4">
        {tabs.map((tab) => (
          <Nav.Item key={tab.path} className="">
            <Nav.Link
              as={Link}
              to={tab.path}
              className={`policy-tab ${location.pathname === tab.path ? "active-tab" : ""
                } text-decoration-none`}
            >
              {tab.label}
            </Nav.Link>
          </Nav.Item>
        ))}
      </Nav>

      {/* Policy Content */}
      <div className="policy-content p-4 rounded shadow-sm bg-white">
        <Routes>
          <Route path="terms" element={<Terms />} />
          <Route path="privacy" element={<Privacy />} />
          <Route path="shipping" element={<Shipping />} />
          <Route path="refund" element={<Refund />} />
          <Route path="*" element={<PageNotFound />} />
        </Routes>
      </div>
    </div>
  );
};

export default PolicyPage;

/* ---------- INDIVIDUAL POLICY COMPONENTS ---------- */

const Terms = () => (
  <section>
    <h4 className="fw-bold mb-3">Terms & Conditions</h4>
    <p>
      Welcome to our website. By accessing or using our services, you agree to
      comply with our terms and conditions. These terms outline user
      responsibilities, limitations, and acceptable usage.
    </p>
    <ul>
      <li>Users must provide accurate information.</li>
      <li>We reserve the right to modify or terminate services.</li>
      <li>Unauthorized access or misuse may result in termination.</li>
    </ul>
  </section>
);

const Privacy = () => (
  <section>
    <h4 className="fw-bold mb-3">Privacy Policy</h4>
    <p>
      Your privacy is important to us. We collect personal information only to
      enhance your experience and provide better services. All data is handled
      securely and will not be shared with third parties without consent.
    </p>
    <ul>
      <li>We use cookies for personalization and analytics.</li>
      <li>Users can request deletion of personal data.</li>
      <li>Data is stored securely using encryption standards.</li>
    </ul>
  </section>
);

const Shipping = () => (
  <section>
    <h4 className="fw-bold mb-3">Shipping Policy</h4>
    <p>
      We strive to deliver your orders quickly and safely. Our standard
      processing time is 2-3 business days. Delivery timelines vary based on
      location.
    </p>
    <ul>
      <li>Orders are shipped within 2-3 business days after confirmation.</li>
      <li>Tracking information will be shared via email.</li>
      <li>Delays due to weather or customs are beyond our control.</li>
    </ul>
  </section>
);

const Refund = () => (
  <section>
    <h4 className="fw-bold mb-3">Return & Refund Policy</h4>
    <p>
      We want you to be fully satisfied with your purchase. If you are not
      happy with your order, you may return eligible items within 7 days of
      delivery.
    </p>
    <ul>
      <li>Items must be unused and in original packaging.</li>
      <li>Refunds are processed within 5-7 business days after approval.</li>
      <li>Shipping costs are non-refundable unless due to our error.</li>
    </ul>
  </section>
);
