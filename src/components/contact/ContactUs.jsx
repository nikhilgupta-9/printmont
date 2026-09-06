import React, { useState, useEffect } from "react";
import { Container, Row, Col, Button, Form, Spinner } from "react-bootstrap";
import { Link } from "react-router-dom";
import {
  FaHeadset, FaUserCircle, FaMapMarkerAlt, FaRegClock,
  FaCheckCircle, FaPaperPlane,
} from "react-icons/fa";
import { toast } from "react-hot-toast";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import "./contact.css";

const ContactUs = () => {
  const [contactData, setContactData] = useState(null);
  const [activeTab, setActiveTab] = useState("general"); // "general" | "order"

  const [formData, setFormData] = useState({
    name: "", email: "", phone: "",
    subject: "General Inquiry", orderId: "", message: "",
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);

  useEffect(() => {
    let cancelled = false;

    const fetchContactData = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.CONTACT);
        if (!response.ok) throw new Error("Network response was not ok");
        const json = await response.json();
        if (!cancelled && json?.success) setContactData(json.data);
      } catch (error) {
        console.error("Error fetching contact API data:", error);
      }
    };

    fetchContactData();
    return () => { cancelled = true; };
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (isSubmitting) return;

    if (!formData.name.trim() || !formData.email.trim() || !formData.message.trim()) {
      toast.error("Please fill in all required fields.");
      return;
    }

    setIsSubmitting(true);

    try {
      // The order tab collects an order id, which has no column of its own —
      // fold it into the subject so support still sees which order it concerns.
      const subject = activeTab === "order"
        ? `Order Issue${formData.orderId.trim() ? ` — ${formData.orderId.trim()}` : ""}`
        : (formData.subject || "General Inquiry");

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
        activeTab === "general"
          ? "Your General Query has been submitted successfully!"
          : "Your Order Issue details have been submitted!"
      );
    } catch (err) {
      console.error("Contact form submission failed:", err);
      toast.error("Could not reach the server. Please try again in a moment.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const resetForm = () => {
    setFormData({
      name: "", email: "", phone: "",
      subject: "General Inquiry", orderId: "", message: "",
    });
    setIsSubmitted(false);
  };

  // Admin-managed under Contact Details.
  const helpline = contactData?.help_number || "";
  const serviceTime = contactData?.service_time || "";
  const salesEmail = contactData?.sales_email || "";
  const corporateEmail = contactData?.corporate_email || "";
  const addressOne = contactData?.address_one || "";
  const addressTwo = contactData?.address_two || "";

  const telHref = `tel:${String(helpline).replace(/[^\d+]/g, "")}`;
  const mapHref = (addr) => `https://maps.google.com/?q=${encodeURIComponent(addr)}`;

  const offices = [
    { label: "Corporate Office", address: addressOne },
    { label: "Branch Office", address: addressTwo },
  ].filter((o) => o.address);

  return (
    <div className="cu-page">
      {/* Tinted band the card sits over. */}
      <div className="cu-band" aria-hidden="true" />

      <Container className="cu-shell">
        <div className="cu-card">
          <Row className="g-0">

            {/* LEFT — CONTACT DETAILS */}
            <Col xs={12} lg={6} className="cu-left">
              <h2 className="cu-h2">Contact Details</h2>

              <div className="cu-actions">
                <Link to="/track-order" className="cu-btn">Track Order</Link>
                <Link to="/faq" className="cu-btn">FAQs</Link>
              </div>

              {/* Chat */}
              {helpline && (
                <div className="cu-row">
                  <FaHeadset className="cu-row__icon" />
                  <div className="cu-row__body">
                    <a href={telHref} className="cu-row__lead">Chat with us</a>
                    <span className="cu-row__note">for order related queries</span>
                  </div>
                </div>
              )}

              {/* Support enquiries */}
              {salesEmail && (
                <div className="cu-row">
                  <FaUserCircle className="cu-row__icon" />
                  <div className="cu-row__body">
                    <span className="cu-row__label">For Support Enquiries</span>
                    <a href={`mailto:${salesEmail}`} className="cu-row__mail">{salesEmail}</a>
                  </div>
                </div>
              )}

              {/* Corporate bulk */}
              {corporateEmail && (
                <div className="cu-row">
                  <FaUserCircle className="cu-row__icon" />
                  <div className="cu-row__body">
                    <span className="cu-row__label">For Corporate Bulk Orders</span>
                    <a href={`mailto:${corporateEmail}`} className="cu-row__mail">{corporateEmail}</a>
                  </div>
                </div>
              )}

              {/* Offices */}
              {offices.length > 0 && (
                <>
                  <h3 className="cu-h3">Our Offices</h3>
                  {offices.map((o) => (
                    <div className="cu-row cu-row--office" key={o.label}>
                      <FaMapMarkerAlt className="cu-row__pin" />
                      <div className="cu-row__body">
                        <span className="cu-office__name">{o.label}</span>
                        <address className="cu-office__addr">{o.address}</address>
                        <a
                          href={mapHref(o.address)}
                          target="_blank" rel="noopener noreferrer"
                          className="cu-office__map"
                        >
                          View on map
                        </a>
                      </div>
                    </div>
                  ))}
                </>
              )}

              {serviceTime && (
                <div className="cu-row cu-row--hours">
                  <FaRegClock className="cu-row__icon" />
                  <div className="cu-row__body">
                    <span className="cu-office__name">Support Hours</span>
                    <span className="cu-row__note">{serviceTime}</span>
                  </div>
                </div>
              )}
            </Col>

            {/* RIGHT — HAVE QUESTIONS */}
            <Col xs={12} lg={6} className="cu-right">
              <h2 className="cu-h2">Have Questions?</h2>

              <div className="cu-tabs">
                <button
                  type="button"
                  className={`cu-tab-btn cu-tab-btn--order ${activeTab === "order" ? "is-active" : ""}`}
                  onClick={() => setActiveTab("order")}
                >
                  Order Related Issue?
                </button>
                <button
                  type="button"
                  className={`cu-tab-btn cu-tab-btn--general ${activeTab === "general" ? "is-active" : ""}`}
                  onClick={() => setActiveTab("general")}
                >
                  General Query
                </button>
              </div>

              {isSubmitted ? (
                <div className="cu-sent">
                  <FaCheckCircle className="cu-sent__icon" />
                  <h3 className="cu-h3 mb-2">Message received</h3>
                  <p className="cu-sent__note mb-4">
                    Thanks for getting in touch. We have emailed you a confirmation
                    and our team will reply shortly.
                  </p>
                  <Button className="cu-submit" onClick={resetForm}>Send another message</Button>
                </div>
              ) : (
                <Form onSubmit={handleSubmit} noValidate className="cu-form">
                  <Form.Label className="cu-label">Full Name *</Form.Label>
                  <Form.Control
                    name="name" value={formData.name} onChange={handleChange}
                    placeholder="Your good name" className="cu-field" required
                  />

                  <Form.Label className="cu-label">Email Address *</Form.Label>
                  <Form.Control
                    type="email" name="email" value={formData.email} onChange={handleChange}
                    placeholder="name@example.com" className="cu-field" required
                  />

                  <Row className="g-3">
                    <Col xs={12} md={6}>
                      <Form.Label className="cu-label">Phone</Form.Label>
                      <Form.Control
                        name="phone" value={formData.phone} onChange={handleChange}
                        placeholder="10-digit mobile" className="cu-field"
                      />
                    </Col>
                    <Col xs={12} md={6}>
                      {activeTab === "order" ? (
                        <>
                          <Form.Label className="cu-label">Order ID</Form.Label>
                          <Form.Control
                            name="orderId" value={formData.orderId} onChange={handleChange}
                            placeholder="e.g. 5FHEGN8N3TB4" className="cu-field text-uppercase"
                          />
                        </>
                      ) : (
                        <>
                          <Form.Label className="cu-label">Subject</Form.Label>
                          <Form.Select
                            name="subject" value={formData.subject} onChange={handleChange}
                            className="cu-field"
                          >
                            <option>General Inquiry</option>
                            <option>Bulk / Corporate Order</option>
                            <option>Product Customisation</option>
                            <option>Partnership</option>
                            <option>Feedback</option>
                          </Form.Select>
                        </>
                      )}
                    </Col>
                  </Row>

                  <Form.Label className="cu-label">Message *</Form.Label>
                  <Form.Control
                    as="textarea" rows={4} name="message"
                    value={formData.message} onChange={handleChange}
                    placeholder={activeTab === "order"
                      ? "Tell us what went wrong with your order..."
                      : "How can we help?"}
                    className="cu-field" required
                  />

                  <Button
                    type="submit" disabled={isSubmitting}
                    className="cu-submit d-inline-flex align-items-center gap-2"
                  >
                    {isSubmitting
                      ? <><Spinner size="sm" animation="border" /> Sending…</>
                      : <><FaPaperPlane /> Send Message</>}
                  </Button>
                </Form>
              )}
            </Col>
          </Row>
        </div>
      </Container>
    </div>
  );
};

export default ContactUs;
