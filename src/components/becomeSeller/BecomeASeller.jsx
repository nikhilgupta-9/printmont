import React, { useState } from 'react';
import { Container, Row, Col, Card, Form, Button, Accordion, Badge } from 'react-bootstrap';
import { FaStore, FaTruck, FaMoneyBillWave, FaPercent, FaRupeeSign, FaCheckCircle, FaRocket, FaShieldAlt } from 'react-icons/fa';
import { MdOutlineSecurity, MdOutlineTouchApp } from 'react-icons/md';
import { BsBoxSeam } from "react-icons/bs";

const SellerHeroSVG = () => (
    <svg width="100%" height="100%" viewBox="0 0 500 400" fill="none" xmlns="http://www.w3.org/2000/svg" style={{ maxHeight: "360px" }}>
        <circle cx="250" cy="200" r="150" fill="#eaf2ff"/>
        <rect x="150" y="120" width="200" height="160" rx="12" fill="white" stroke="#0b53a1" strokeWidth="8"/>
        <path d="M150 150H350" stroke="#0b53a1" strokeWidth="8"/>
        <circle cx="180" cy="135" r="5" fill="#0b53a1"/>
        <circle cx="200" cy="135" r="5" fill="#0b53a1"/>
        <circle cx="220" cy="135" r="5" fill="#0b53a1"/>
        <rect x="180" y="180" width="60" height="60" rx="6" fill="#0b53a1" fillOpacity="0.15"/>
        <rect x="260" y="180" width="60" height="20" rx="4" fill="#0b53a1"/>
        <rect x="260" y="210" width="40" height="10" rx="4" fill="#0b53a1" fillOpacity="0.5"/>
        <rect x="260" y="230" width="50" height="10" rx="4" fill="#0b53a1" fillOpacity="0.5"/>
        <path d="M250 280V320M200 320H300" stroke="#0b53a1" strokeWidth="8" strokeLinecap="round"/>
    </svg>
);

const BecomeASeller = () => {
    // Profit Calculator State
    const [ordersPerDay, setOrdersPerDay] = useState(20);
    const [avgOrderValue, setAvgOrderValue] = useState(500);

    const monthlyRevenue = ordersPerDay * avgOrderValue * 30;
    const estimatedProfit = monthlyRevenue * 0.40; // 40% margin

    const [formData, setFormData] = useState({
        name: '', email: '', mobile: '', gst: '', category: 'Apparel'
    });

    const handleChange = (e) => setFormData({ ...formData, [e.target.name]: e.target.value });

    const handleSubmit = (e) => {
        e.preventDefault();
        alert("Registration Request Received! Our seller onboarding team will contact you within 24 hours.");
    };

    return (
        <div className="become-seller-page bg-white" style={{ overflowX: "hidden" }}>
            {/* HERO SECTION */}
            <div className="position-relative overflow-hidden bg-light py-4 py-md-5 border-bottom">
                <Container className="py-2 py-md-4">
                    <Row className="align-items-center g-4">
                        <Col lg={6} md={12} className="text-center text-lg-start">
                            <Badge bg="warning" className="text-dark fw-bold px-3 py-2 mb-3 rounded-pill shadow-xs fs-7">
                                <FaRocket className="me-1" /> Start Selling in 10 Minutes
                            </Badge>
                            <h1 className="fw-bold mb-3 text-dark" style={{ fontSize: "clamp(2rem, 5vw, 3.25rem)", lineHeight: "1.2" }}>
                                Sell to <span className="text-primary">Millions</span><br /> on Printmont.
                            </h1>
                            <p className="text-secondary mb-4 fs-6 fs-md-5" style={{ lineHeight: "1.7", maxWidth: "600px" }}>
                                Join India's fastest-growing custom apparel & gifting marketplace. Enjoy zero registration fees, 0% commission for your first 30 days, and pan-India logistics powered by us.
                            </p>

                            <div className="d-flex flex-column flex-sm-row justify-content-center justify-content-lg-start gap-3 mb-4">
                                <a href="#seller-form" className="btn btn-primary btn-lg rounded-pill px-4 py-2 fw-bold shadow-sm border-0 fs-6">
                                    Start Selling Today
                                </a>
                                <a href="#profit-calculator" className="btn btn-outline-primary btn-lg rounded-pill px-4 py-2 fw-bold fs-6">
                                    Calculate Earnings
                                </a>
                            </div>

                            <div className="d-flex flex-wrap justify-content-center justify-content-lg-start align-items-center gap-3 text-muted small fw-semibold">
                                <span className="d-flex align-items-center gap-1">
                                    <MdOutlineSecurity className="text-success" size={20} /> Secure Payments
                                </span>
                                <span className="d-flex align-items-center gap-1">
                                    <FaTruck className="text-primary" size={18} /> Automated Shipping
                                </span>
                                <span className="d-flex align-items-center gap-1">
                                    <FaShieldAlt className="text-primary" size={16} /> Verified Buyers
                                </span>
                            </div>
                        </Col>

                        <Col lg={6} md={12} className="text-center">
                            <SellerHeroSVG />
                        </Col>
                    </Row>
                </Container>
            </div>

            {/* WHY SELL ON PRINTMONT (4-CARD GRID) */}
            <div className="py-4 py-md-5 bg-light border-bottom">
                <Container>
                    <div className="text-center mb-4 mb-md-5">
                        <h2 className="fw-bold mb-2 text-dark fs-3 fs-md-2">Why Sell on Printmont?</h2>
                        <p className="text-muted small fs-6">Everything you need to grow your e-commerce brand, all in one intuitive seller portal.</p>
                    </div>

                    <Row className="g-3 g-md-4">
                        {[
                            { icon: <FaPercent size={26} className="text-primary" />, title: "0% Commission Rate", desc: "Keep 100% of your earnings for the first 30 days. Enjoy industry-lowest transaction fees afterwards." },
                            { icon: <FaTruck size={26} className="text-primary" />, title: "Hassle-Free Logistics", desc: "Just pack your order. Our courier network picks up and delivers directly across 10,000+ pin codes." },
                            { icon: <FaMoneyBillWave size={26} className="text-primary" />, title: "Rapid Payment Settlements", desc: "Get sales proceeds deposited directly into your bank account twice every week with zero delays." },
                            { icon: <FaStore size={26} className="text-primary" />, title: "Real-Time Analytics", desc: "Monitor catalog performance, customer visits, return metrics, and daily revenue on your live dashboard." }
                        ].map((item, idx) => (
                            <Col xs={12} sm={6} lg={3} key={idx}>
                                <Card className="h-100 border-0 shadow-sm text-center p-3 p-md-4 rounded-4 bg-white transition-all hover-shadow">
                                    <div className="bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style={{ width: "64px", height: "64px" }}>
                                        {item.icon}
                                    </div>
                                    <h5 className="fw-bold mb-2 text-dark fs-6">{item.title}</h5>
                                    <p className="text-secondary small mb-0" style={{ lineHeight: "1.6" }}>{item.desc}</p>
                                </Card>
                            </Col>
                        ))}
                    </Row>
                </Container>
            </div>

            {/* INTERACTIVE PROFIT CALCULATOR */}
            <div id="profit-calculator" className="py-4 py-md-5 bg-white border-bottom">
                <Container>
                    <Row className="align-items-center g-4 g-lg-5">
                        <Col lg={5} md={12} className="text-center text-lg-start">
                            <Badge bg="primary-subtle" className="text-primary fw-bold px-3 py-1 mb-2 rounded-pill text-uppercase fs-7">
                                Earnings Estimator
                            </Badge>
                            <h2 className="fw-bold mb-3 text-dark fs-3 fs-md-2">Calculate Your Potential Earnings</h2>
                            <p className="text-secondary mb-4 small fs-6" style={{ lineHeight: "1.7" }}>
                                See how much profit your brand can generate by selling on PrintMont. Low selling fees and high customer conversion rates mean higher margins in your pocket.
                            </p>
                            <ul className="list-unstyled text-secondary small d-inline-block text-start m-0">
                                <li className="mb-2 d-flex align-items-center"><FaCheckCircle className="text-success me-2 flex-shrink-0" /> Transparent pricing structure with zero hidden fees</li>
                                <li className="mb-2 d-flex align-items-center"><FaCheckCircle className="text-success me-2 flex-shrink-0" /> No mandatory listing or monthly store subscription fees</li>
                                <li className="mb-2 d-flex align-items-center"><FaCheckCircle className="text-success me-2 flex-shrink-0" /> Discounted bulk shipping rates negotiated for you</li>
                            </ul>
                        </Col>

                        <Col lg={7} md={12}>
                            <Card className="border-0 shadow-sm rounded-4 p-3 p-md-4 p-lg-5" style={{ background: "linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)", border: "1px solid #e9ecef" }}>
                                <Card.Body className="p-0">
                                    <Form.Group className="mb-4">
                                        <div className="d-flex justify-content-between align-items-center mb-2">
                                            <Form.Label className="fw-bold text-dark mb-0 small">Expected Orders Per Day</Form.Label>
                                            <span className="fw-bold text-primary fs-5">{ordersPerDay} Orders</span>
                                        </div>
                                        <Form.Range min="1" max="500" value={ordersPerDay} onChange={(e) => setOrdersPerDay(Number(e.target.value))} />
                                    </Form.Group>

                                    <Form.Group className="mb-4">
                                        <div className="d-flex justify-content-between align-items-center mb-2">
                                            <Form.Label className="fw-bold text-dark mb-0 small">Average Order Value (₹)</Form.Label>
                                            <span className="fw-bold text-primary fs-5">₹{avgOrderValue.toLocaleString('en-IN')}</span>
                                        </div>
                                        <Form.Range min="100" max="5000" step="50" value={avgOrderValue} onChange={(e) => setAvgOrderValue(Number(e.target.value))} />
                                    </Form.Group>

                                    <div className="p-3 p-md-4 rounded-3 text-center bg-primary-subtle border border-primary-subtle">
                                        <span className="text-uppercase text-muted fw-bold d-block mb-1" style={{ fontSize: "0.75rem", letterSpacing: "1px" }}>Estimated Monthly Gross Revenue</span>
                                        <h2 className="fw-bold text-primary mb-1" style={{ fontSize: "clamp(1.75rem, 4vw, 2.75rem)" }}>
                                            ₹{monthlyRevenue.toLocaleString('en-IN')}
                                        </h2>
                                        <p className="text-success fw-bold m-0 small">
                                            ~ ₹{estimatedProfit.toLocaleString('en-IN')} Estimated Net Profit Margin (approx. 40%)
                                        </p>
                                    </div>
                                </Card.Body>
                            </Card>
                        </Col>
                    </Row>
                </Container>
            </div>

            {/* HOW IT WORKS (4 STEPS) */}
            <div className="bg-light py-4 py-md-5 border-bottom">
                <Container>
                    <div className="text-center mb-4 mb-md-5">
                        <h2 className="fw-bold mb-2 text-dark fs-3 fs-md-2">How PrintMont Seller Hub Works</h2>
                        <p className="text-muted small fs-6">4 simple steps to onboard and scale your brand nationwide.</p>
                    </div>

                    <Row className="g-3 g-md-4 text-center">
                        {[
                            { icon: <FaStore size={24} />, title: "1. Register Account", desc: "Create your account with GSTIN and active bank details." },
                            { icon: <BsBoxSeam size={24} />, title: "2. List Catalog", desc: "Upload your product photos & prices to our seller portal." },
                            { icon: <MdOutlineTouchApp size={24} />, title: "3. Receive Orders", desc: "Millions of shoppers discover and order your products." },
                            { icon: <FaRupeeSign size={24} />, title: "4. Get Direct Payouts", desc: "Order earnings deposited straight into your bank account." }
                        ].map((step, idx) => (
                            <Col xs={12} sm={6} lg={3} key={idx}>
                                <div className="bg-white p-3 p-md-4 rounded-4 shadow-sm h-100 border text-center">
                                    <div className="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style={{ width: "56px", height: "56px" }}>
                                        {step.icon}
                                    </div>
                                    <h6 className="fw-bold mb-2 text-dark fs-6">{step.title}</h6>
                                    <p className="text-secondary small m-0" style={{ lineHeight: "1.5" }}>{step.desc}</p>
                                </div>
                            </Col>
                        ))}
                    </Row>
                </Container>
            </div>

            {/* REGISTRATION FORM & FAQ SECTION */}
            <div id="seller-form" className="py-4 py-md-5 bg-white">
                <Container>
                    <Row className="g-4 g-lg-5">
                        {/* FAQS */}
                        <Col lg={6} md={12}>
                            <h3 className="fw-bold mb-3 text-dark fs-4 text-center text-lg-start">Frequently Asked Questions</h3>
                            <Accordion defaultActiveKey="0" className="border-0 shadow-sm rounded-3">
                                <Accordion.Item eventKey="0" className="border-0 border-bottom">
                                    <Accordion.Header className="fw-semibold text-dark">Do I need a GSTIN to sell on PrintMont?</Accordion.Header>
                                    <Accordion.Body className="text-secondary small lh-lg bg-light">
                                        Yes, as per Indian e-commerce GST regulations, a valid GSTIN is mandatory for selling physical goods on online platforms.
                                    </Accordion.Body>
                                </Accordion.Item>
                                <Accordion.Item eventKey="1" className="border-0 border-bottom">
                                    <Accordion.Header className="fw-semibold text-dark">Who handles the pickup and customer shipping?</Accordion.Header>
                                    <Accordion.Body className="text-secondary small lh-lg bg-light">
                                        PrintMont does! Once an order comes in, pack the item and our shipping partner will pick it up from your warehouse/store and deliver it.
                                    </Accordion.Body>
                                </Accordion.Item>
                                <Accordion.Item eventKey="2" className="border-0 border-bottom">
                                    <Accordion.Header className="fw-semibold text-dark">When do I receive my seller payments?</Accordion.Header>
                                    <Accordion.Body className="text-secondary small lh-lg bg-light">
                                        Payments for delivered orders are calculated weekly and deposited directly into your bank account twice a week.
                                    </Accordion.Body>
                                </Accordion.Item>
                                <Accordion.Item eventKey="3" className="border-0">
                                    <Accordion.Header className="fw-semibold text-dark">What product categories can I list?</Accordion.Header>
                                    <Accordion.Body className="text-secondary small lh-lg bg-light">
                                        You can list Apparel (T-shirts, Hoodies, Polos), Drinkware, Stationery, Custom Gifts, Accessories, and Corporate Merchandise.
                                    </Accordion.Body>
                                </Accordion.Item>
                            </Accordion>
                        </Col>

                        {/* SELLER REGISTRATION FORM */}
                        <Col lg={6} md={12}>
                            <Card className="border-0 shadow-sm p-3 p-md-4 p-lg-5 rounded-4 bg-white border">
                                <h4 className="fw-bold text-dark mb-1 fs-4">Start Your Seller Account Today</h4>
                                <p className="text-muted small mb-4">Complete the form below to receive your seller onboarding access link.</p>
                                
                                <Form onSubmit={handleSubmit}>
                                    <Row className="g-3 mb-3">
                                        <Col sm={6} xs={12}>
                                            <Form.Label className="small fw-semibold">Full Name *</Form.Label>
                                            <Form.Control type="text" name="name" value={formData.name} onChange={handleChange} required className="py-2" placeholder="e.g. Amit Kumar" />
                                        </Col>
                                        <Col sm={6} xs={12}>
                                            <Form.Label className="small fw-semibold">Mobile Number *</Form.Label>
                                            <Form.Control type="tel" name="mobile" value={formData.mobile} onChange={handleChange} required className="py-2" placeholder="+91 9876543210" />
                                        </Col>
                                    </Row>

                                    <Form.Group className="mb-3">
                                        <Form.Label className="small fw-semibold">Email Address *</Form.Label>
                                        <Form.Control type="email" name="email" value={formData.email} onChange={handleChange} required className="py-2" placeholder="seller@yourbrand.com" />
                                    </Form.Group>

                                    <Row className="g-3 mb-4">
                                        <Col sm={6} xs={12}>
                                            <Form.Label className="small fw-semibold">GSTIN *</Form.Label>
                                            <Form.Control type="text" name="gst" value={formData.gst} onChange={handleChange} required className="py-2" placeholder="07AAAAA0000A1Z5" />
                                        </Col>
                                        <Col sm={6} xs={12}>
                                            <Form.Label className="small fw-semibold">Primary Selling Category</Form.Label>
                                            <Form.Select name="category" value={formData.category} onChange={handleChange} className="py-2">
                                                <option>Custom Apparel</option>
                                                <option>Drinkware & Mugs</option>
                                                <option>Stationery & Office</option>
                                                <option>Corporate Gifts</option>
                                                <option>Personalized Accessories</option>
                                            </Form.Select>
                                        </Col>
                                    </Row>

                                    <Button type="submit" variant="primary" size="lg" className="w-100 py-2.5 fw-bold rounded-pill shadow-sm border-0">
                                        Create Seller Account
                                    </Button>
                                </Form>
                            </Card>
                        </Col>
                    </Row>
                </Container>
            </div>
        </div>
    );
};

export default BecomeASeller;
