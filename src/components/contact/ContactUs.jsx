import React, { useState, useEffect } from "react";
import { Container, Row, Col, Button, Form, Spinner } from "react-bootstrap";
import { IoIosMail } from "react-icons/io";
import { FaCheckCircle, FaPaperPlane } from "react-icons/fa";
import { toast } from "react-hot-toast";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import "./contact.css"; // for small style tweaks

const ContactUs = () => {
  const [contactData, setContactData] = useState(null);
  const [activeTab, setActiveTab] = useState("general"); // "general" | "order"

  // Form State
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    subject: "General Inquiry",
    orderId: "",
    message: ""
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);

  useEffect(() => {
    const fetchContactData = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.CONTACT);
        if (!response.ok) throw new Error("Network response was not ok");
        const json = await response.json();
        if (json && json.success && json.data) {
          setContactData(json.data);
        }
      } catch (error) {
        console.error("Error fetching contact API data:", error);
      }
    };
    fetchContactData();
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!formData.name.trim() || !formData.email.trim() || !formData.message.trim()) {
      toast.error("Please fill in all required fields.");
      return;
    }

    setIsSubmitting(true);

    // Simulate query submission
    setTimeout(() => {
      setIsSubmitting(false);
      setIsSubmitted(true);
      toast.success(
        activeTab === "general"
          ? "Your General Query has been submitted successfully!"
          : "Your Order Issue details have been submitted!"
      );
    }, 1000);
  };

  const resetForm = () => {
    setFormData({
      name: "",
      email: "",
      phone: "",
      subject: "General Inquiry",
      orderId: "",
      message: ""
    });
    setIsSubmitted(false);
  };

  // Safe fallback values matching the original layout content
  const helpline = contactData?.help_number || "9818532463";
  const serviceTime = contactData?.service_time || "Mon - Sat: 10:30 AM - 6:30 PM";
  const salesEmail = contactData?.sales_email || "support@printmont.com";
  const corporateEmail = contactData?.corporate_email || "info@printmont.com";
  const addressOne = contactData?.address_one || "3398, Bagichi Acchi ji, Bara Hindu Rao, Near Filmistan Cinema, Delhi India - 110006";
  const addressTwo = contactData?.address_two || "7855 Nai Basti Bara Hindu Rao, Near Filmistan Cinema, Delhi India - 110006";

  return (
    <>
      <Container className="contact-container py-4">
        <Row className="g-4 flex-column-reverse flex-lg-row bg-white rounded shadow-sm overflow-hidden p-3 p-lg-4">
          {/* Left Section - Contact Info */}
          <Col xs={12} lg={5} className="border-end-lg">
            <div className="pe-lg-3">
              <h5 className="fw-bold mb-3 text-dark">Contact Details</h5>

              <div className="d-flex gap-2 mb-4">
                <Button className="bg-theme text-uppercase fw-semibold" size="sm" href="/track-order">
                  TRACK ORDER
                </Button>
                <Button className="bg-theme text-uppercase fw-semibold" size="sm" href="/faq">
                  FAQS
                </Button>
              </div>

              <div className="d-flex mb-3 align-items-center">
                <img src="/online-support.png" width={50} height={50} className="me-3 contact-icon" alt="Support" />
                <div>
                  <p className="mb-0 fw-semibold text-dark">
                    Helpline no: <strong>+91-{helpline}</strong>
                  </p>
                  <small className="text-muted">({serviceTime})</small>
                </div>
              </div>

              <div className="d-flex justify-content-start align-items-start mb-3">
                <IoIosMail className="contact-icon me-3 mt-1 text-primary fs-3" />
                <div>
                  <p className="fw-semibold text-org mb-1">
                    Sales enquiries and customer support
                  </p>
                  <p className="mb-0">
                    <a href={`mailto:${salesEmail}`} className="text-decoration-none text-primary fw-medium">
                      {salesEmail}
                    </a>
                  </p>
                </div>
              </div>

              <div className="d-flex justify-content-start align-items-start mb-4">
                <IoIosMail className="contact-icon me-3 mt-1 text-primary fs-3" />
                <div>
                  <p className="fw-semibold text-org mb-1">
                    For Corporate Bulk Orders
                  </p>
                  <p className="mb-0">
                    <a href={`mailto:${corporateEmail}`} className="text-decoration-none text-primary fw-medium">
                      {corporateEmail}
                    </a>
                  </p>
                </div>
              </div>

              <div className="mt-4 pt-3 border-top">
                <h6 className="fw-bold text-dark mb-2">Regd Factory Address</h6>
                <div className="d-flex align-items-start mb-3">
                  <img src="/store-black.png" width={36} height={36} className="me-3 contact-add-icon mt-1" alt="Factory" />
                  <div>
                    <strong className="d-block mb-1 text-dark">Printmont Corporation</strong>
                    <p className="text-muted small mb-0">{addressOne}</p>
                  </div>
                </div>

                <h6 className="fw-bold text-dark mb-2 mt-3">Corporate Offices</h6>
                <div className="d-flex align-items-start">
                  <img src="/store-black.png" width={36} height={36} className="me-3 contact-add-icon mt-1" alt="Office" />
                  <div>
                    <strong className="d-block mb-1 text-dark">Printmont Corporation</strong>
                    <p className="text-muted small mb-0">{addressTwo}</p>
                  </div>
                </div>
              </div>
            </div>
          </Col>

          {/* Right Section - Have Questions & Form */}
          <Col xs={12} lg={7}>
            <div className="ps-lg-3">
              <h5 className="fw-bold mb-3 text-dark text-center text-lg-start">Have Questions?</h5>

              {/* Query Type Buttons */}
              <div className="d-flex flex-column flex-sm-row justify-content-start gap-2 mb-4">
                <Button
                  className={`fw-semibold py-2 px-3 ${activeTab === 'general' ? 'bg-theme text-white border-0 shadow-sm' : 'btn-outline-secondary bg-light text-dark border'}`}
                  onClick={() => { setActiveTab('general'); setIsSubmitted(false); }}
                >
                  GENERAL QUERY
                </Button>
                <Button
                  className={`fw-semibold py-2 px-3 ${activeTab === 'order' ? 'bg-org text-white border-0 shadow-sm' : 'btn-outline-secondary bg-light text-dark border'}`}
                  onClick={() => { setActiveTab('order'); setIsSubmitted(false); }}
                >
                  ORDER RELATED ISSUE?
                </Button>
              </div>

              {/* Form Container */}
              <div className="bg-light p-3 p-md-4 rounded-3 border">
                {isSubmitted ? (
                  <div className="text-center py-4">
                    <FaCheckCircle className="text-success mb-3" size={50} />
                    <h5 className="fw-bold text-dark">Query Submitted Successfully!</h5>
                    <p className="text-muted small mb-4">
                      Thank you for contacting Printmont. Our support team will review your message and reply back within 24 hours.
                    </p>
                    <Button variant="primary" className="bg-theme border-0 fw-semibold px-4" onClick={resetForm}>
                      Submit Another Query
                    </Button>
                  </div>
                ) : (
                  <Form onSubmit={handleSubmit}>
                    <h6 className="fw-bold text-dark mb-3 border-bottom pb-2">
                      {activeTab === 'general' ? 'General Query Form' : 'Order Related Issue Form'}
                    </h6>

                    <Row className="g-3">
                      <Col xs={12} sm={6}>
                        <Form.Group controlId="queryName">
                          <Form.Label className="small fw-semibold text-dark">Full Name <span className="text-danger">*</span></Form.Label>
                          <Form.Control
                            type="text"
                            name="name"
                            placeholder="Enter your full name"
                            value={formData.name}
                            onChange={handleChange}
                            required
                            className="bg-white"
                          />
                        </Form.Group>
                      </Col>

                      <Col xs={12} sm={6}>
                        <Form.Group controlId="queryEmail">
                          <Form.Label className="small fw-semibold text-dark">Email Address <span className="text-danger">*</span></Form.Label>
                          <Form.Control
                            type="email"
                            name="email"
                            placeholder="e.g. name@example.com"
                            value={formData.email}
                            onChange={handleChange}
                            required
                            className="bg-white"
                          />
                        </Form.Group>
                      </Col>

                      <Col xs={12} sm={6}>
                        <Form.Group controlId="queryPhone">
                          <Form.Label className="small fw-semibold text-dark">Phone Number</Form.Label>
                          <Form.Control
                            type="tel"
                            name="phone"
                            placeholder="e.g. 9876543210"
                            value={formData.phone}
                            onChange={handleChange}
                            className="bg-white"
                          />
                        </Form.Group>
                      </Col>

                      {activeTab === 'general' ? (
                        <Col xs={12} sm={6}>
                          <Form.Group controlId="querySubject">
                            <Form.Label className="small fw-semibold text-dark">Topic / Subject</Form.Label>
                            <Form.Select
                              name="subject"
                              value={formData.subject}
                              onChange={handleChange}
                              className="bg-white"
                            >
                              <option value="General Inquiry">General Inquiry</option>
                              <option value="Product Customization">Product Customization</option>
                              <option value="Delivery & Shipping">Delivery & Shipping</option>
                              <option value="Bulk Corporate Order">Bulk / Corporate Order</option>
                              <option value="Payment / Refund">Payment / Refund</option>
                              <option value="Other">Other Query</option>
                            </Form.Select>
                          </Form.Group>
                        </Col>
                      ) : (
                        <Col xs={12} sm={6}>
                          <Form.Group controlId="queryOrderId">
                            <Form.Label className="small fw-semibold text-dark">Order ID / Reference</Form.Label>
                            <Form.Control
                              type="text"
                              name="orderId"
                              placeholder="e.g. ORD-109283"
                              value={formData.orderId}
                              onChange={handleChange}
                              className="bg-white"
                            />
                          </Form.Group>
                        </Col>
                      )}

                      <Col xs={12}>
                        <Form.Group controlId="queryMessage">
                          <Form.Label className="small fw-semibold text-dark">Query Details <span className="text-danger">*</span></Form.Label>
                          <Form.Control
                            as="textarea"
                            rows={4}
                            name="message"
                            placeholder={activeTab === 'general' ? "How can we help you? Describe your question here..." : "Please describe the issue with your order in detail..."}
                            value={formData.message}
                            onChange={handleChange}
                            required
                            className="bg-white"
                          />
                        </Form.Group>
                      </Col>

                      <Col xs={12} className="mt-3">
                        <Button
                          type="submit"
                          disabled={isSubmitting}
                          className={`w-100 fw-bold py-2.5 d-flex align-items-center justify-content-center gap-2 border-0 ${activeTab === 'general' ? 'bg-theme' : 'bg-org'}`}
                        >
                          {isSubmitting ? (
                            <>
                              <Spinner animation="border" size="sm" />
                              <span>Submitting Query...</span>
                            </>
                          ) : (
                            <>
                              <FaPaperPlane size={15} />
                              <span>{activeTab === 'general' ? 'Submit General Query' : 'Submit Order Issue'}</span>
                            </>
                          )}
                        </Button>
                      </Col>
                    </Row>
                  </Form>
                )}
              </div>
            </div>
          </Col>
        </Row>
      </Container>
    </>
  );
};

export default ContactUs;
