import React, { useState, useEffect } from "react";
import { Container, Row, Col, Button, Form, Spinner } from "react-bootstrap";
import { Link } from "react-router-dom";
import {
  FaHeadset,
  FaEnvelope,
  FaStore,
  FaPaperPlane,
  FaCheckCircle,
  FaClock,
  FaPhoneAlt
} from "react-icons/fa";
import { toast } from "react-hot-toast";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import { updatePageSEO } from "../../utils/seo";
import "./contact.css";

const ContactUs = () => {
  const [contactData, setContactData] = useState(null);
  const [loading, setLoading] = useState(false);

  // Desktop active tab: "order" | "general"
  const [desktopActiveTab, setDesktopActiveTab] = useState("order");

  // Mobile active tab: "order" | "general" (form stays closed until a tab is tapped; tap the active tab again to collapse)
  const [mobileActiveTab, setMobileActiveTab] = useState("order");
  const [showMobileForm, setShowMobileForm] = useState(false);

  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    subject: "Order Related Issue",
    orderId: "",
    message: "",
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: "Contact Us - Printmont Support & Customer Service",
      description: "Get in touch with Printmont for inquiries, bulk corporate orders, customer support, and tracking your customized printing orders.",
      keywords: "contact printmont, customer support, printmont phone, corporate gift orders, helpdesk",
    });
    let cancelled = false;

    const fetchContactData = async () => {
      try {
        setLoading(true);
        const response = await fetch(`${API_ENDPOINTS.CONTACT}?_t=${Date.now()}`, {
          cache: "no-cache",
        });
        if (!response.ok) throw new Error("Network response was not ok");
        const json = await response.json();
        if (!cancelled && json?.success && json?.data) {
          setContactData(json.data);
        }
      } catch (error) {
        console.error("Error fetching contact API data:", error);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    fetchContactData();

    const handleFocus = () => fetchContactData();
    window.addEventListener("focus", handleFocus);

    return () => {
      cancelled = true;
      window.removeEventListener("focus", handleFocus);
    };
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleDesktopTab = (tab) => {
    setDesktopActiveTab(tab);
    setIsSubmitted(false);
    setFormData((prev) => ({
      ...prev,
      subject: tab === "order" ? "Order Related Issue" : "General Query",
    }));
  };

  const handleMobileTab = (tab) => {
    if (mobileActiveTab === tab && showMobileForm) {
      setShowMobileForm(false);
    } else {
      setMobileActiveTab(tab);
      setShowMobileForm(true);
      setIsSubmitted(false);
      setFormData((prev) => ({
        ...prev,
        subject: tab === "order" ? "Order Related Issue" : "General Query",
      }));
    }
  };

  const handleSubmit = async (e, currentTab) => {
    e.preventDefault();
    if (isSubmitting) return;

    if (!formData.name.trim() || !formData.email.trim() || !formData.message.trim()) {
      toast.error("Please fill in all required fields (Name, Email, Message).");
      return;
    }

    setIsSubmitting(true);

    try {
      const subject =
        currentTab === "order"
          ? `Order Issue${formData.orderId.trim() ? ` — Order #${formData.orderId.trim()}` : ""}`
          : formData.subject || "General Query";

      const res = await fetch(API_ENDPOINTS.CONTACT, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: formData.name.trim(),
          email: formData.email.trim(),
          phone: formData.phone.trim(),
          subject,
          message: formData.message.trim(),
        }),
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        toast.error(data.message || "Could not send your message. Please try again.");
        return;
      }

      setIsSubmitted(true);
      toast.success(
        currentTab === "order"
          ? "Your Order Related Issue has been submitted! Our team will contact you shortly."
          : "Your General Query has been submitted successfully!"
      );
    } catch (err) {
      console.error("Contact form submission failed:", err);
      toast.error("Could not reach the server. Please try again in a moment.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const resetForm = (tab = "order") => {
    setFormData({
      name: "",
      email: "",
      phone: "",
      subject: tab === "order" ? "Order Related Issue" : "General Query",
      orderId: "",
      message: "",
    });
    setIsSubmitted(false);
  };

  // Live admin managed fields from backend
  const helpNumber = contactData?.help_number ?? "9818532463";
  const rawDigits = helpNumber ? helpNumber.replace(/\D/g, "") : "9818532463";
  const formattedPhone = helpNumber
    ? helpNumber.startsWith("+")
      ? helpNumber
      : `+91-${helpNumber}`
    : "+91-9818532463";
  const serviceTime = contactData?.service_time ?? "Mon - Sat: 10:30 AM - 6:30 PM";
  const salesEmail = contactData?.sales_email ?? "support@printmont.com";
  const corporateEmail = contactData?.corporate_email ?? "info@printmont.com";
  const addressOne =
    contactData?.address_one ||
    "3398, Bagichi Acchi ji, Bara Hindu Rao, Near Filmistan Cinema, Delhi India - 110006";
  const addressTwo =
    contactData?.address_two ||
    "7855 Nai Basti Bara Hindu Rao, Near Filmistan Cinema, Delhi India - 110006";

  return (
    <div className="contact-main-wrapper">
      <Container className="py-4 py-md-5">
        {/* ========================================================
            DESKTOP VIEW (Matching media_1791188015887.jpg)
            ======================================================== */}
        <div className="d-none d-lg-block">
          <Row className="gx-5">
            {/* LEFT COLUMN: Contact Details */}
            <Col lg={6} className="pe-xl-5">
              <h2 className="contact-heading mb-3">Contact Details</h2>

              {/* Action Buttons: Track Order & FAQs */}
              <div className="d-flex gap-3 mb-3">
                <Link to="/track-order" className="btn contact-btn-blue">
                  TRACK ORDER
                </Link>
                <Link to="/faq" className="btn contact-btn-blue">
                  FAQS
                </Link>
              </div>

              {/* Helpline */}
              <div className="contact-item-row mb-4">
                <div className="contact-icon-wrap">
                  <FaHeadset className="contact-main-icon" />
                </div>
                <div className="contact-text-wrap">
                  <div className="contact-text-primary">
                    Helpline no: <a href={`tel:${rawDigits}`} className="contact-phone-link">{formattedPhone}</a>
                  </div>
                  <div className="contact-text-muted">({serviceTime})</div>
                </div>
              </div>

              {/* Sales Enquiries */}
              <div className="contact-item-row mb-4">
                <div className="contact-icon-wrap">
                  <FaEnvelope className="contact-main-icon" />
                </div>
                <div className="contact-text-wrap">
                  <div className="contact-orange-title">Sales enquiries and customer support</div>
                  <div className="contact-text-primary">
                    <a href={`mailto:${salesEmail}`} className="contact-email-link">{salesEmail}</a>
                  </div>
                </div>
              </div>

              {/* Corporate Bulk Orders */}
              <div className="contact-item-row mb-4">
                <div className="contact-icon-wrap">
                  <FaEnvelope className="contact-main-icon" />
                </div>
                <div className="contact-text-wrap">
                  <div className="contact-orange-title">For Corporate Bulk Orders</div>
                  <div className="contact-text-primary">
                    <a href={`mailto:${corporateEmail}`} className="contact-email-link">{corporateEmail}</a>
                  </div>
                </div>
              </div>

              {/* Regd Factory Address */}
              <div className="contact-address-section mb-4 pt-2">
                <h5 className="contact-subheading mb-3">Regd Factory Address.</h5>
                <div className="d-flex align-items-start gap-3">
                  <FaStore className="contact-main-icon flex-shrink-0 mt-1" />
                  <div>
                    <div className="fw-bold text-dark mb-1">Printmont Corporation.</div>
                    <div className="text-secondary leading-relaxed" style={{ fontSize: "14px", maxWidth: "420px" }}>
                      {addressOne}
                    </div>
                  </div>
                </div>
              </div>

              {/* Corporate Offices */}
              {addressTwo && (
                <div className="contact-address-section mb-4 pt-2">
                  <h5 className="contact-subheading mb-3">Corporate Offices.</h5>
                  <div className="d-flex align-items-start gap-3">
                    <FaStore className="contact-main-icon flex-shrink-0 mt-1" />
                    <div>
                      <div className="fw-bold text-dark mb-1">Printmont Corporation.</div>
                      <div className="text-secondary leading-relaxed" style={{ fontSize: "14px", maxWidth: "420px" }}>
                        {addressTwo}
                      </div>
                    </div>
                  </div>
                </div>
              )}
            </Col>

            {/* RIGHT COLUMN: Have Questions? */}
            <Col lg={6} className="ps-xl-5">
              <h2 className="contact-heading mb-3">Have Questions?</h2>

              {/* Action Buttons: Order Related Issue? (Orange) & General Query (Blue) */}
              <div className="d-flex gap-3 mb-4">
                <button
                  type="button"
                  className={`btn ${desktopActiveTab === "order" ? "contact-btn-orange active-tab" : "contact-btn-orange-outline"}`}
                  onClick={() => handleDesktopTab("order")}
                >
                  ORDER RELATED ISSUE?
                </button>
                <button
                  type="button"
                  className={`btn ${desktopActiveTab === "general" ? "contact-btn-blue active-tab" : "contact-btn-blue-outline"}`}
                  onClick={() => handleDesktopTab("general")}
                >
                  GENERAL QUERY
                </button>
              </div>

              {/* Form Card */}
              <div className="contact-form-panel p-4 rounded-3 border bg-white shadow-sm">
                <h5 className="fw-bold text-dark mb-3">
                  {desktopActiveTab === "order" ? "📦 Submit Order Issue" : "💬 Send General Query"}
                </h5>

                {isSubmitted ? (
                  <div className="text-center py-4">
                    <FaCheckCircle className="text-success mb-3" size={48} />
                    <h5 className="fw-bold text-dark">Message Sent Successfully!</h5>
                    <p className="text-muted small mb-3">
                      Thank you for contacting Printmont. Our team will review your query and respond shortly.
                    </p>
                    <Button
                      variant="primary"
                      className="contact-btn-orange py-2 px-4 border-0"
                      onClick={() => resetForm(desktopActiveTab)}
                    >
                      Send Another Message
                    </Button>
                  </div>
                ) : (
                  <Form onSubmit={(e) => handleSubmit(e, desktopActiveTab)}>
                    <Row className="g-3">
                      <Col md={6}>
                        <Form.Group>
                          <Form.Label className="contact-label">
                            Full Name <span className="text-danger">*</span>
                          </Form.Label>
                          <Form.Control
                            size="sm"
                            name="name"
                            value={formData.name}
                            onChange={handleChange}
                            placeholder="Enter your name"
                            className="contact-input"
                            required
                          />
                        </Form.Group>
                      </Col>

                      <Col md={6}>
                        <Form.Group>
                          <Form.Label className="contact-label">
                            Email Address <span className="text-danger">*</span>
                          </Form.Label>
                          <Form.Control
                            size="sm"
                            type="email"
                            name="email"
                            value={formData.email}
                            onChange={handleChange}
                            placeholder="name@example.com"
                            className="contact-input"
                            required
                          />
                        </Form.Group>
                      </Col>

                      <Col md={6}>
                        <Form.Group>
                          <Form.Label className="contact-label">Phone Number</Form.Label>
                          <Form.Control
                            size="sm"
                            type="tel"
                            name="phone"
                            value={formData.phone}
                            onChange={handleChange}
                            placeholder="+91 98765 43210"
                            className="contact-input"
                          />
                        </Form.Group>
                      </Col>

                      {desktopActiveTab === "order" ? (
                        <Col md={6}>
                          <Form.Group>
                            <Form.Label className="contact-label">Order ID (if known)</Form.Label>
                            <Form.Control
                              size="sm"
                              type="text"
                              name="orderId"
                              value={formData.orderId}
                              onChange={handleChange}
                              placeholder="e.g. PM-94820"
                              className="contact-input"
                            />
                          </Form.Group>
                        </Col>
                      ) : (
                        <Col md={6}>
                          <Form.Group>
                            <Form.Label className="contact-label">Subject</Form.Label>
                            <Form.Control
                              size="sm"
                              type="text"
                              name="subject"
                              value={formData.subject}
                              onChange={handleChange}
                              placeholder="e.g. Bulk order inquiry"
                              className="contact-input"
                            />
                          </Form.Group>
                        </Col>
                      )}

                      <Col xs={12}>
                        <Form.Group>
                          <Form.Label className="contact-label">
                            Message <span className="text-danger">*</span>
                          </Form.Label>
                          <Form.Control
                            size="sm"
                            as="textarea"
                            rows={4}
                            name="message"
                            value={formData.message}
                            onChange={handleChange}
                            placeholder="Type your message or issue details here..."
                            className="contact-input"
                            required
                          />
                        </Form.Group>
                      </Col>

                      <Col xs={12} className="mt-3">
                        <Button
                          type="submit"
                          disabled={isSubmitting}
                          className="contact-btn-orange w-100 py-2.5 border-0 d-flex align-items-center justify-content-center gap-2"
                        >
                          {isSubmitting ? (
                            <>
                              <Spinner size="sm" animation="border" />
                              <span>Submitting...</span>
                            </>
                          ) : (
                            <>
                              <FaPaperPlane size={14} />
                              <span>Submit Message</span>
                            </>
                          )}
                        </Button>
                      </Col>
                    </Row>
                  </Form>
                )}
              </div>
            </Col>
          </Row>
        </div>


        {/* ========================================================
            MOBILE VIEW (Matching media_1791188003388.jpg)
            ======================================================== */}
        <div className="d-block d-lg-none">
          {/* Top: Have Questions? */}
          <div className="text-center mb-4">
            <h4 className="contact-heading text-center mb-3">Have Questions?</h4>
            <div className="d-flex justify-content-center gap-2">
              <button
                type="button"
                className={`btn contact-btn-orange flex-grow-1 ${mobileActiveTab === "order" && showMobileForm ? "active-tab" : ""}`}
                onClick={() => handleMobileTab("order")}
              >
                ORDER RELATED ISSUE?
              </button>
              <button
                type="button"
                className={`btn contact-btn-blue flex-grow-1 ${mobileActiveTab === "general" && showMobileForm ? "active-tab" : ""}`}
                onClick={() => handleMobileTab("general")}
              >
                GENERAL QUERY
              </button>
            </div>
          </div>

          {/* Form Box for Mobile (Toggles open when buttons are tapped) */}
          {showMobileForm && (
            <div className="contact-form-panel p-3 mb-4 rounded-3 border bg-white shadow-sm">
              <div className="d-flex justify-content-between align-items-center mb-3">
                <h6 className="fw-bold text-dark mb-0">
                  {mobileActiveTab === "order" ? "📦 Order Related Issue" : "💬 General Query"}
                </h6>
                <button
                  type="button"
                  className="btn btn-sm btn-outline-secondary py-0 px-2"
                  style={{ fontSize: "12px" }}
                  onClick={() => setShowMobileForm(false)}
                >
                  ✕ Close
                </button>
              </div>

              {isSubmitted ? (
                <div className="text-center py-4 bg-white rounded p-3">
                  <FaCheckCircle className="text-success mb-2" size={38} />
                  <h6 className="fw-bold text-dark">Thank You!</h6>
                  <p className="text-muted small mb-3">
                    Your message has been received. Our support team will contact you shortly.
                  </p>
                  <Button
                    size="sm"
                    className="contact-btn-orange border-0"
                    onClick={() => resetForm(mobileActiveTab || "order")}
                  >
                    Send Another Message
                  </Button>
                </div>
              ) : (
                <Form onSubmit={(e) => handleSubmit(e, mobileActiveTab || "order")}>
                  <Form.Group className="mb-2">
                    <Form.Label className="contact-label">Full Name <span className="text-danger">*</span></Form.Label>
                    <Form.Control
                      size="sm"
                      name="name"
                      value={formData.name}
                      onChange={handleChange}
                      placeholder="e.g. John Doe"
                      className="contact-input"
                      required
                    />
                  </Form.Group>

                  <Form.Group className="mb-2">
                    <Form.Label className="contact-label">Email Address <span className="text-danger">*</span></Form.Label>
                    <Form.Control
                      size="sm"
                      type="email"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      placeholder="e.g. john@example.com"
                      className="contact-input"
                      required
                    />
                  </Form.Group>

                  <Form.Group className="mb-2">
                    <Form.Label className="contact-label">Phone Number</Form.Label>
                    <Form.Control
                      size="sm"
                      type="tel"
                      name="phone"
                      value={formData.phone}
                      onChange={handleChange}
                      placeholder="e.g. +91 9876543210"
                      className="contact-input"
                    />
                  </Form.Group>

                  {mobileActiveTab === "order" && (
                    <Form.Group className="mb-2">
                      <Form.Label className="contact-label">Order ID (if known)</Form.Label>
                      <Form.Control
                        size="sm"
                        name="orderId"
                        value={formData.orderId}
                        onChange={handleChange}
                        placeholder="e.g. PM-94820"
                        className="contact-input"
                      />
                    </Form.Group>
                  )}

                  <Form.Group className="mb-3">
                    <Form.Label className="contact-label">Message <span className="text-danger">*</span></Form.Label>
                    <Form.Control
                      size="sm"
                      as="textarea"
                      rows={3}
                      name="message"
                      value={formData.message}
                      onChange={handleChange}
                      placeholder="Type your message or issue details here..."
                      className="contact-input"
                      required
                    />
                  </Form.Group>

                  <Button
                    type="submit"
                    disabled={isSubmitting}
                    className="contact-btn-orange w-100 py-2 border-0 fw-bold d-flex align-items-center justify-content-center gap-2"
                  >
                    {isSubmitting ? (
                      <>
                        <Spinner size="sm" animation="border" />
                        <span>Submitting...</span>
                      </>
                    ) : (
                      <>
                        <FaPaperPlane size={13} />
                        <span>Submit Message</span>
                      </>
                    )}
                  </Button>
                </Form>
              )}
            </div>
          )}

          {/* Contact Details Card with light blue container */}
          <div className="contact-details-card p-3 p-sm-4 rounded-3 mb-4">
            <h4 className="contact-heading mb-3">Contact Details</h4>

            {/* Action Buttons: Track Order & FAQs */}
            <div className="d-flex gap-2 mb-4">
              <Link to="/track-order" className="btn contact-btn-blue text-center flex-grow-1">
                TRACK ORDER
              </Link>
              <Link to="/faq" className="btn contact-btn-blue text-center flex-grow-1">
                FAQS
              </Link>
            </div>

            {/* Helpline */}
            <div className="contact-item-row mb-4">
              <div className="contact-icon-wrap">
                <FaHeadset className="contact-main-icon" />
              </div>
              <div className="contact-text-wrap">
                <div className="contact-text-primary">
                  Helpline no:- <a href={`tel:${rawDigits}`} className="contact-phone-link">{formattedPhone}</a>
                </div>
                <div className="contact-text-muted">({serviceTime})</div>
              </div>
            </div>

            {/* Sales Enquiries */}
            <div className="contact-item-row mb-4">
              <div className="contact-icon-wrap">
                <FaEnvelope className="contact-main-icon" />
              </div>
              <div className="contact-text-wrap">
                <div className="contact-orange-title">Sales enquiries and customer support</div>
                <div className="contact-text-primary">
                  <a href={`mailto:${salesEmail}`} className="contact-email-link">{salesEmail}</a>
                </div>
              </div>
            </div>

            {/* Corporate Bulk Orders */}
            <div className="contact-item-row mb-4">
              <div className="contact-icon-wrap">
                <FaEnvelope className="contact-main-icon" />
              </div>
              <div className="contact-text-wrap">
                <div className="contact-orange-title">For Corporate Bulk Orders</div>
                <div className="contact-text-primary">
                  <a href={`mailto:${corporateEmail}`} className="contact-email-link">{corporateEmail}</a>
                </div>
              </div>
            </div>
          </div>

          {/* Regd Factory Address */}
          <div className="contact-address-section mb-4 px-2">
            <h6 className="contact-subheading mb-2">Regd Factory Address.</h6>
            <div className="d-flex align-items-start gap-3">
              <FaStore className="contact-main-icon flex-shrink-0 mt-1" />
              <div>
                <div className="fw-bold text-dark">Printmont Corporation.</div>
                <div className="text-secondary small leading-relaxed">
                  {addressOne}
                </div>
              </div>
            </div>
          </div>

          {/* Corporate Offices */}
          {addressTwo && (
            <div className="contact-address-section mb-4 px-2">
              <h6 className="contact-subheading mb-2">Corporate Offices.</h6>
              <div className="d-flex align-items-start gap-3">
                <FaStore className="contact-main-icon flex-shrink-0 mt-1" />
                <div>
                  <div className="fw-bold text-dark">Printmont Corporation.</div>
                  <div className="text-secondary small leading-relaxed">
                    {addressTwo}
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      </Container>
    </div>
  );
};

export default ContactUs;
