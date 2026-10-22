import React, { useState } from "react";
import { Form, Button, Row, Col, Spinner } from "react-bootstrap";
import { toast } from "react-hot-toast";
import { FaWhatsapp, FaPhoneAlt, FaCheckCircle, FaPaperPlane } from "react-icons/fa";
import { API_ENDPOINTS } from "../../config/apiEndpoints";

const BUDGET_OPTIONS = [
  "Select Estimated Budget",
  "Under ₹25,000",
  "₹25,000 - ₹50,000",
  "₹50,000 - ₹1,00,000",
  "₹1,00,000 - ₹5,00,000",
  "₹5,00,000+"
];

const EMPLOYEE_OPTIONS = [
  "Select Employee Count",
  "0-15",
  "15-50",
  "50-200",
  "200-500",
  "500+"
];

export default function BusinessLeadForm({ 
  deviceType = "mobile", 
  title = "Business Solutions Tailored to Your Needs -",
  subtitle = "Fill the Form Today!",
  onSuccess = null 
}) {
  const [formData, setFormData] = useState({
    fullName: "",
    contactNumber: "",
    companyName: "",
    emailId: "",
    budget: "",
    employeeCount: "",
    productRequirement: ""
  });

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!formData.fullName.trim() || !formData.contactNumber.trim() || !formData.emailId.trim()) {
      toast.error("Please provide your name, contact number, and email address.");
      return;
    }

    // Basic phone validation (10 digits)
    const cleanPhone = formData.contactNumber.replace(/[^0-9]/g, "");
    if (cleanPhone.length < 10) {
      toast.error("Please enter a valid 10-digit mobile or WhatsApp number.");
      return;
    }

    try {
      setIsSubmitting(true);
      const endpoint = API_ENDPOINTS.LEADS_SUBMIT || "/api/leads-api.php";
      
      const payload = {
        full_name: formData.fullName.trim(),
        contact_number: formData.contactNumber.trim(),
        company_name: formData.companyName.trim(),
        email_id: formData.emailId.trim(),
        budget: formData.budget === BUDGET_OPTIONS[0] ? "" : formData.budget,
        employee_count: formData.employeeCount === EMPLOYEE_OPTIONS[0] ? "" : formData.employeeCount,
        product_requirement: formData.productRequirement.trim() + (formData.employeeCount ? ` (Employee count: ${formData.employeeCount})` : ""),
        device_type: deviceType
      };

      const res = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const json = await res.json();

      if (json.success || res.ok) {
        toast.success("Thank you! Your inquiry has been submitted. Our team will contact you shortly.");
        setSubmitted(true);
        setFormData({
          fullName: "",
          contactNumber: "",
          companyName: "",
          emailId: "",
          budget: "",
          employeeCount: "",
          productRequirement: ""
        });
        if (onSuccess) onSuccess(json);
      } else {
        toast.error(json.message || "Failed to submit inquiry. Please try again.");
      }
    } catch (err) {
      console.error("Lead submission error:", err);
      toast.error("Network error submitting lead. Please try again or contact us on WhatsApp.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className={`bs-lead-form-card ${deviceType === 'mobile' ? 'bs-lead-mobile' : 'bs-lead-desktop'}`} id="lead-form">
      <div className="bs-lead-header text-center mb-4">
        <h3 className="bs-lead-title">{title}</h3>
        <p className="bs-lead-subtitle">{subtitle}</p>
      </div>

      {submitted ? (
        <div className="bs-lead-success text-center py-4">
          <div className="bs-success-icon-wrap mb-3">
            <FaCheckCircle size={48} className="text-success" />
          </div>
          <h4 className="fw-bold text-dark mb-2">Requirement Received!</h4>
          <p className="text-muted small mb-3">
            Our Enterprise Solutions Manager will analyze your request and send a tailored quotation within 25 minutes.
          </p>
          <button 
            type="button" 
            className="btn btn-outline-primary btn-sm rounded-pill px-4"
            onClick={() => setSubmitted(false)}
          >
            Submit Another Request
          </button>
        </div>
      ) : (
        <Form onSubmit={handleSubmit} className="bs-lead-form">
          <Row className="g-3">
            {/* Row 1: Full Name & Business Email */}
            <Col xs={12} md={6}>
              <Form.Group>
                <Form.Label className="bs-form-label">Full Name <span className="text-danger">*</span></Form.Label>
                <Form.Control
                  type="text"
                  name="fullName"
                  required
                  placeholder="Your Good Name Here"
                  value={formData.fullName}
                  onChange={handleChange}
                  className="bs-form-input"
                />
              </Form.Group>
            </Col>

            <Col xs={12} md={6}>
              <Form.Group>
                <Form.Label className="bs-form-label">Business Email <span className="text-danger">*</span></Form.Label>
                <Form.Control
                  type="email"
                  name="emailId"
                  required
                  placeholder="corporate@company.com"
                  value={formData.emailId}
                  onChange={handleChange}
                  className="bs-form-input"
                />
              </Form.Group>
            </Col>

            {/* Row 2: Contact Number & Company Name */}
            <Col xs={12} md={6}>
              <Form.Group>
                <Form.Label className="bs-form-label">Contact Number <span className="text-danger">*</span></Form.Label>
                <Form.Control
                  type="tel"
                  name="contactNumber"
                  required
                  placeholder="Your Contact Number"
                  value={formData.contactNumber}
                  onChange={handleChange}
                  className="bs-form-input"
                />
              </Form.Group>
            </Col>

            <Col xs={12} md={6}>
              <Form.Group>
                <Form.Label className="bs-form-label">Company Name</Form.Label>
                <Form.Control
                  type="text"
                  name="companyName"
                  placeholder="Your Company Name"
                  value={formData.companyName}
                  onChange={handleChange}
                  className="bs-form-input"
                />
              </Form.Group>
            </Col>

            {/* Row 3: Budgets & Employee Count */}
            <Col xs={12} md={6}>
              <Form.Group>
                <Form.Label className="bs-form-label">Budgets</Form.Label>
                <Form.Select
                  name="budget"
                  value={formData.budget}
                  onChange={handleChange}
                  className="bs-form-input bs-form-select"
                >
                  {BUDGET_OPTIONS.map((opt, i) => (
                    <option key={i} value={i === 0 ? "" : opt} disabled={i === 0}>
                      {i === 0 ? "Your Budgets" : opt}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>

            <Col xs={12} md={6}>
              <Form.Group>
                <Form.Label className="bs-form-label">Employee Count</Form.Label>
                <Form.Select
                  name="employeeCount"
                  value={formData.employeeCount}
                  onChange={handleChange}
                  className="bs-form-input bs-form-select"
                >
                  {EMPLOYEE_OPTIONS.map((opt, i) => (
                    <option key={i} value={i === 0 ? "" : opt} disabled={i === 0}>
                      {opt}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>

            {/* Row 4: Product Requirement */}
            <Col xs={12}>
              <Form.Group>
                <Form.Label className="bs-form-label">Product Requirement</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={3}
                  name="productRequirement"
                  placeholder="Your Product Requirement here (e.g. 500 Custom Welcome Kits, T-shirts, Diaries, GST Billing)..."
                  value={formData.productRequirement}
                  onChange={handleChange}
                  className="bs-form-input"
                />
              </Form.Group>
            </Col>

            {/* Action Buttons */}
            <Col xs={12} className="text-center mt-3">
              <Button 
                type="submit" 
                className="bs-btn-get-quote px-5 py-2.5" 
                disabled={isSubmitting}
              >
                {isSubmitting ? (
                  <>
                    <Spinner animation="border" size="sm" className="me-2" />
                    Submitting Inquiry...
                  </>
                ) : (
                  "Get Quote"
                )}
              </Button>
            </Col>

            {/* WhatsApp Us Button */}
            <Col xs={12} className="text-center">
              <a 
                href="https://wa.me/919818532463?text=Hi%20Printmont%2C%20I%20am%20interested%20in%20Business%20Solutions%20and%20Corporate%20Printing." 
                target="_blank" 
                rel="noopener noreferrer" 
                className="bs-btn-whatsapp-pill"
              >
                <FaWhatsapp size={18} className="me-1" /> WhatsApp us
              </a>
            </Col>

            {/* Footer Support Info */}
            <Col xs={12} className="text-center mt-2">
              <p className="bs-form-note mb-1">
                *We typically reply within 25 minutes 10:30 AM to 7:00 PM Mon to Sat.
              </p>
              <p className="bs-form-call mb-0">
                Or talk to us at <a href="tel:+919818532463">+91-9818532463</a>
              </p>
            </Col>

          </Row>
        </Form>
      )}
    </div>
  );
}
