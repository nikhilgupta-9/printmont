import React, { useState } from 'react';
import { Container, Row, Col, Card, Button, Form, Accordion, Badge } from 'react-bootstrap';
import { FaDollarSign, FaLink, FaUserCheck, FaChartLine, FaGift, FaArrowRight, FaCheckCircle, FaQuestionCircle } from 'react-icons/fa';

const AffiliateProgram = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    website: '',
    promotionalChannel: 'Blog / Website',
    message: ''
  });

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    alert("Thank you for your affiliate application! Our team will review your application and contact you within 24 hours.");
  };

  const highlights = [
    {
      icon: <FaDollarSign size={28} className="text-primary" />,
      title: "Up to 15% Commission",
      desc: "Earn high competitive commissions on every completed purchase generated through your referral links."
    },
    {
      icon: <FaChartLine size={28} className="text-primary" />,
      title: "30-Day Cookie Window",
      desc: "Long 30-day tracking cookie ensures you earn credit even if a customer buys weeks after clicking your link."
    },
    {
      icon: <FaUserCheck size={28} className="text-primary" />,
      title: "Monthly Timely Payouts",
      desc: "Hassle-free monthly commission disbursements directly to your verified Indian bank account or UPI."
    },
    {
      icon: <FaGift size={28} className="text-primary" />,
      title: "Free Banners & Coupons",
      desc: "Access exclusive high-converting promotional banners, text links, and custom discount coupon codes."
    }
  ];

  const steps = [
    {
      num: "01",
      title: "Sign Up for Free",
      desc: "Fill out the simple affiliate application form below. Instant review and approval within 24 hours."
    },
    {
      num: "02",
      title: "Promote & Share",
      desc: "Place PrintMont trackable links, banner ads, or custom coupon codes on your blog, social channels, or website."
    },
    {
      num: "03",
      title: "Earn & Get Paid",
      desc: "Track real-time clicks and sales conversions on your dashboard, receiving automated monthly payouts."
    }
  ];

  const commissionRates = [
    { category: "Custom Apparel & T-Shirts", rate: "12% - 15%" },
    { category: "Corporate Gift Sets & Executive Diaries", rate: "10% - 12%" },
    { category: "Drinkware, Mugs & Steel Bottles", rate: "10%" },
    { category: "Marketing Print & Business Cards", rate: "8% - 10%" },
    { category: "Other Personalized Accessories", rate: "8%" }
  ];

  const faqs = [
    {
      q: "Is there any cost to join the PrintMont Affiliate Program?",
      a: "No! Joining the PrintMont Affiliate Partner Program is 100% free with no hidden maintenance fees or mandatory minimum sales requirements."
    },
    {
      q: "How are my sales and referral conversions tracked?",
      a: "When you join, you receive a unique affiliate tracking ID and dashboard. Any user who clicks your link is assigned a 30-day tracking cookie."
    },
    {
      q: "When and how do I get paid my commissions?",
      a: "Commissions are calculated on the 1st of every month and disbursed by the 10th via direct NEFT/RTGS bank transfer or UPI for earnings above ₹1,000."
    },
    {
      q: "Can I promote PrintMont on social media platforms like Instagram & YouTube?",
      a: "Yes! You can share your affiliate link or dedicated coupon code in your Instagram bio, YouTube descriptions, Telegram channels, and blogs."
    }
  ];

  return (
    <div className="affiliate-program-page bg-light py-3 py-md-5" style={{ overflowX: "hidden" }}>
      <Container>
        {/* HERO BANNER SECTION */}
        <Card className="border-0 rounded-4 shadow-sm overflow-hidden mb-4 mb-md-5 text-white" style={{ background: "linear-gradient(135deg, #0b53a1 0%, #002b66 100%)" }}>
          <Card.Body className="p-4 p-md-5">
            <Row className="align-items-center g-4">
              <Col lg={7} md={12}>
                <Badge bg="light" className="text-primary fw-bold px-3 py-2 mb-3 rounded-pill text-uppercase fs-7">
                  Official Partner Program
                </Badge>
                <h1 className="fw-bold mb-3 display-6 display-md-5 text-white" style={{ lineHeight: "1.2" }}>
                  Partner with PrintMont & Earn Money Online
                </h1>
                <p className="lead text-white-50 mb-4 fs-6 fs-md-5" style={{ lineHeight: "1.7", maxWidth: "650px" }}>
                  Monetize your website, social channels, or business network by promoting India’s top-rated custom apparel, personalized gifting, and corporate merchandise brand.
                </p>
                <div className="d-flex flex-wrap gap-3">
                  <a href="#affiliate-form" className="btn btn-light text-primary fw-bold px-4 py-2 rounded-pill shadow-sm">
                    Apply Now <FaArrowRight className="ms-1" />
                  </a>
                  <a href="#commission-rates" className="btn btn-outline-light fw-bold px-4 py-2 rounded-pill">
                    View Commission Structure
                  </a>
                </div>
              </Col>
              <Col lg={5} className="d-none d-lg-block text-center">
                <div className="p-4 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-25 text-white">
                  <h3 className="fw-bold text-white mb-2">Up to 15%</h3>
                  <p className="text-white-50 small mb-3">High Conversion Commission Rate</p>
                  <hr className="border-white border-opacity-25 my-3" />
                  <div className="d-flex justify-content-around text-center">
                    <div>
                      <h5 className="fw-bold text-white m-0">30 Days</h5>
                      <span className="small text-white-50">Cookie Window</span>
                    </div>
                    <div>
                      <h5 className="fw-bold text-white m-0">Monthly</h5>
                      <span className="small text-white-50">Bank Payouts</span>
                    </div>
                  </div>
                </div>
              </Col>
            </Row>
          </Card.Body>
        </Card>

        {/* PROGRAM HIGHLIGHTS (4-COLUMN GRID) */}
        <div className="mb-4 mb-md-5">
          <div className="text-center mb-4">
            <h2 className="fw-bold text-dark fs-3 fs-md-2">Why Join PrintMont Affiliate Program</h2>
            <p className="text-muted small fs-6">Maximising your affiliate earnings with industry-leading support and conversion tools.</p>
          </div>
          <Row className="g-3 g-md-4">
            {highlights.map((item, idx) => (
              <Col key={idx} xs={12} sm={6} lg={3}>
                <Card className="h-100 border-0 shadow-sm p-3 p-md-4 rounded-3 bg-white transition-all hover-shadow">
                  <div className="mb-3 p-2 rounded-circle bg-primary-subtle d-inline-flex justify-content-center align-items-center" style={{ width: "52px", height: "52px" }}>
                    {item.icon}
                  </div>
                  <h5 className="fw-bold text-dark mb-2 fs-6">{item.title}</h5>
                  <p className="text-secondary small m-0" style={{ lineHeight: "1.6" }}>
                    {item.desc}
                  </p>
                </Card>
              </Col>
            ))}
          </Row>
        </div>

        {/* 3 SIMPLE STEPS TO START EARNING */}
        <Card className="border-0 shadow-sm p-4 p-md-5 mb-4 mb-md-5 rounded-4 bg-white">
          <div className="text-center mb-4 mb-md-5">
            <h2 className="fw-bold text-dark fs-3 fs-md-2">3 Simple Steps to Start Earning</h2>
            <p className="text-muted small fs-6">Quick setup to start generating passive income with PrintMont.</p>
          </div>
          <Row className="g-4">
            {steps.map((step, idx) => (
              <Col key={idx} xs={12} md={4}>
                <div className="p-4 rounded-3 border bg-light h-100 position-relative">
                  <span className="position-absolute top-0 end-0 m-3 display-6 fw-bold text-primary opacity-25">
                    {step.num}
                  </span>
                  <h5 className="fw-bold text-dark mb-2 fs-5">{step.title}</h5>
                  <p className="text-secondary small m-0" style={{ lineHeight: "1.6" }}>
                    {step.desc}
                  </p>
                </div>
              </Col>
            ))}
          </Row>
        </Card>

        {/* COMMISSION RATES SECTION */}
        <div id="commission-rates" className="mb-4 mb-md-5">
          <Row className="g-4 align-items-center">
            <Col lg={5} md={12}>
              <Card className="border-0 shadow-sm p-4 rounded-4 bg-white h-100">
                <h3 className="fw-bold text-dark mb-3 fs-4">Lucrative Commission Rates</h3>
                <p className="text-secondary small mb-4" style={{ lineHeight: "1.7" }}>
                  Earn generous commissions on high-converting product categories. Whether your audience is looking for individual custom printed t-shirts or bulk corporate gifting orders, you get paid on every order.
                </p>
                <div className="p-3 bg-primary-subtle rounded-3 text-primary border border-primary-subtle">
                  <div className="d-flex align-items-center gap-2 mb-1">
                    <FaCheckCircle /> <strong className="small">Special Corporate Bonus:</strong>
                  </div>
                  <p className="small mb-0 text-dark">
                    Earn an extra <strong>2% milestone bonus</strong> when your monthly referral sales volume exceeds ₹1,00,000.
                  </p>
                </div>
              </Card>
            </Col>

            <Col lg={7} md={12}>
              <Card className="border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <Card.Header className="bg-primary text-white fw-bold py-3 px-4 fs-6">
                  Category Commission Breakdown
                </Card.Header>
                <div className="table-responsive">
                  <table className="table table-hover align-middle mb-0">
                    <thead className="table-light">
                      <tr>
                        <th className="py-3 px-4">Product Category</th>
                        <th className="py-3 px-4 text-end">Commission Rate</th>
                      </tr>
                    </thead>
                    <tbody>
                      {commissionRates.map((c, idx) => (
                        <tr key={idx}>
                          <td className="py-3 px-4 fw-semibold text-dark">{c.category}</td>
                          <td className="py-3 px-4 text-end fw-bold text-primary">{c.rate}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </Card>
            </Col>
          </Row>
        </div>

        {/* AFFILIATE APPLICATION FORM SECTION */}
        <div id="affiliate-form" className="mb-4 mb-md-5">
          <Row className="justify-content-center">
            <Col lg={9} md={11} xs={12}>
              <Card className="border-0 shadow-sm p-3 p-md-5 rounded-4 bg-white">
                <div className="text-center mb-4">
                  <h3 className="fw-bold text-dark fs-3">Apply for PrintMont Affiliate Program</h3>
                  <p className="text-muted small">Fill in your information below and our affiliate management team will get back to you shortly.</p>
                </div>

                <Form onSubmit={handleSubmit}>
                  <Row className="g-3 mb-3">
                    <Col md={6} xs={12}>
                      <Form.Label className="small fw-semibold">Full Name *</Form.Label>
                      <Form.Control type="text" name="name" value={formData.name} onChange={handleChange} required className="py-2" placeholder="e.g. Rahul Sharma" />
                    </Col>
                    <Col md={6} xs={12}>
                      <Form.Label className="small fw-semibold">Email Address *</Form.Label>
                      <Form.Control type="email" name="email" value={formData.email} onChange={handleChange} required className="py-2" placeholder="name@example.com" />
                    </Col>
                  </Row>

                  <Row className="g-3 mb-3">
                    <Col md={6} xs={12}>
                      <Form.Label className="small fw-semibold">Phone / WhatsApp Number *</Form.Label>
                      <Form.Control type="tel" name="phone" value={formData.phone} onChange={handleChange} required className="py-2" placeholder="+91 9876543210" />
                    </Col>
                    <Col md={6} xs={12}>
                      <Form.Label className="small fw-semibold">Website / Social Media Profile URL</Form.Label>
                      <Form.Control type="url" name="website" value={formData.website} onChange={handleChange} className="py-2" placeholder="https://instagram.com/yourhandle" />
                    </Col>
                  </Row>

                  <Row className="g-3 mb-3">
                    <Col md={12}>
                      <Form.Label className="small fw-semibold">Primary Promotion Channel *</Form.Label>
                      <Form.Select name="promotionalChannel" value={formData.promotionalChannel} onChange={handleChange} className="py-2">
                        <option>Blog / Content Website</option>
                        <option>Instagram / Facebook Creator</option>
                        <option>YouTube Channel</option>
                        <option>Corporate Gifting Agent / Consultant</option>
                        <option>Deals & Coupons Portal</option>
                        <option>Other</option>
                      </Form.Select>
                    </Col>
                  </Row>

                  <Form.Group className="mb-4">
                    <Form.Label className="small fw-semibold">Briefly Tell Us How You Plan to Promote PrintMont (Optional)</Form.Label>
                    <Form.Control as="textarea" rows={3} name="message" value={formData.message} onChange={handleChange} placeholder="Share your estimated audience reach or website monthly visitors..." />
                  </Form.Group>

                  <div className="text-center">
                    <Button type="submit" variant="primary" size="lg" className="rounded-pill px-5 py-2 fw-bold shadow-sm">
                      Submit Partner Application
                    </Button>
                  </div>
                </Form>
              </Card>
            </Col>
          </Row>
        </div>

        {/* FREQUENTLY ASKED QUESTIONS */}
        <Card className="border-0 shadow-sm p-4 p-md-5 mb-4 mb-md-5 rounded-4 bg-white">
          <div className="d-flex align-items-center gap-2 mb-4">
            <FaQuestionCircle className="text-primary" size={24} />
            <h3 className="fw-bold text-dark m-0 fs-4">Affiliate Program FAQs</h3>
          </div>
          <Accordion defaultActiveKey="0" className="border-0">
            {faqs.map((faq, idx) => (
              <Accordion.Item eventKey={String(idx)} key={idx} className="border-0 mb-3 rounded-3 overflow-hidden shadow-xs">
                <Accordion.Header className="fw-semibold text-dark">{faq.q}</Accordion.Header>
                <Accordion.Body className="text-secondary small lh-lg bg-light">
                  {faq.a}
                </Accordion.Body>
              </Accordion.Item>
            ))}
          </Accordion>
        </Card>

        {/* COMPANY & CONTACT FOOTER CARD */}
        <Card className="border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white">
          <Row className="g-4 align-items-center">
            <Col md={8}>
              <h5 className="fw-bold text-dark mb-2">PrintMont Corporation Pvt. Ltd.</h5>
              <p className="text-muted small mb-2" style={{ lineHeight: "1.6" }}>
                <strong>Registered Office Address:</strong> 3398, Bagichi Acchi ji, Bara Hindu Rao, Near Filmistan Cinema, New Delhi, India - 110006
              </p>
              <p className="text-muted small m-0">
                <strong>Affiliate Desk Email:</strong> <a href="mailto:affiliate@printmont.com" className="text-primary text-decoration-none">affiliate@printmont.com</a> | <strong>Helpline:</strong> <a href="tel:+919818532463" className="text-primary text-decoration-none">+91-9818532463</a>
              </p>
            </Col>
            <Col md={4} className="text-md-end">
              <a href="mailto:affiliate@printmont.com" className="btn btn-outline-primary fw-bold px-4 py-2 rounded-pill">
                Email Affiliate Desk
              </a>
            </Col>
          </Row>
        </Card>
      </Container>
    </div>
  );
};

export default AffiliateProgram;
