import React, { useState } from 'react';
import { Container, Row, Col, Card, Form, Button, Accordion } from 'react-bootstrap';
import { FaStore, FaTruck, FaMoneyBillWave, FaHeadset, FaChartLine, FaPercent, FaRupeeSign, FaCheckCircle } from 'react-icons/fa';
import { MdOutlineSecurity, MdOutlineTouchApp } from 'react-icons/md';
import { BsBoxSeam } from "react-icons/bs";

const WaveDivider = () => (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" style={{ display: "block", marginTop: "-5px" }}>
        <path fill="rgb(240, 245, 255)" fillOpacity="1" d="M0,160L48,165.3C96,171,192,181,288,160C384,139,480,85,576,96C672,107,768,181,864,197.3C960,213,1056,171,1152,149.3C1248,128,1344,128,1392,128L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
    </svg>
);

const SellerHeroSVG = () => (
    <svg width="100%" height="100%" viewBox="0 0 500 400" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="250" cy="200" r="150" fill="rgb(240, 245, 255)"/>
        <rect x="150" y="120" width="200" height="160" rx="10" fill="white" stroke="rgb(11, 83, 161)" strokeWidth="8"/>
        <path d="M150 150H350" stroke="rgb(11, 83, 161)" strokeWidth="8"/>
        <circle cx="180" cy="135" r="5" fill="rgb(11, 83, 161)"/>
        <circle cx="200" cy="135" r="5" fill="rgb(11, 83, 161)"/>
        <circle cx="220" cy="135" r="5" fill="rgb(11, 83, 161)"/>
        <rect x="180" y="180" width="60" height="60" rx="5" fill="rgb(11, 83, 161)" fillOpacity="0.2"/>
        <rect x="260" y="180" width="60" height="20" rx="4" fill="rgb(11, 83, 161)"/>
        <rect x="260" y="210" width="40" height="10" rx="4" fill="rgb(11, 83, 161)" fillOpacity="0.5"/>
        <rect x="260" y="230" width="50" height="10" rx="4" fill="rgb(11, 83, 161)" fillOpacity="0.5"/>
        <path d="M250 280V320M200 320H300" stroke="rgb(11, 83, 161)" strokeWidth="8" strokeLinecap="round"/>
    </svg>
);

const BecomeASeller = () => {
    // Profit Calculator State
    const [ordersPerDay, setOrdersPerDay] = useState(20);
    const [avgOrderValue, setAvgOrderValue] = useState(500);

    const monthlyRevenue = ordersPerDay * avgOrderValue * 30;
    const estimatedProfit = monthlyRevenue * 0.40; // Assuming 40% margin

    const [formData, setFormData] = useState({
        name: '', email: '', mobile: '', gst: '', category: 'Apparel'
    });

    const handleChange = (e) => setFormData({ ...formData, [e.target.name]: e.target.value });

    const handleSubmit = (e) => {
        e.preventDefault();
        alert("Registration Request Received! Our team will contact you within 24 hours.");
    };

    return (
        <>
        <style>{`
            @media (max-width: 767.98px) {
                .hero-title { font-size: 2.2rem !important; line-height: 1.2 !important; }
                .hero-subtitle { font-size: 1.05rem !important; }
                .section-padding { padding-top: 2.5rem !important; padding-bottom: 2.5rem !important; }
                .section-title { font-size: 1.8rem !important; }
                .mobile-hide { display: none !important; }
                .step-wrapper { width: 60px !important; height: 60px !important; }
                .step-icon { transform: scale(0.65) !important; }
                .calc-card { padding: 1.5rem !important; }
                .revenue-title { font-size: 2.2rem !important; }
                .card-icon { width: 50px !important; height: 50px !important; }
                .card-icon svg { width: 24px !important; height: 24px !important; }
            }
        `}</style>
        <div className="become-seller-page" style={{ backgroundColor: "#ffffff" }}>
            {/* HERO SECTION */}
            <div className="hero-section position-relative overflow-hidden bg-light py-5 section-padding">
                <Container className="pt-2 pt-md-5">
                    <Row className="align-items-center g-4">
                        <Col lg={6} className="text-center text-lg-start z-1">
                            <div className="d-inline-block px-3 py-1 rounded-pill bg-warning text-dark fw-bold small mb-3 shadow-sm">
                                🚀 Start Selling in 10 Minutes
                            </div>
                            <h1 className="display-4 fw-bold mb-3 text-dark hero-title">
                                Sell to <span className="text-theme">Millions</span><br/> on Printmont.
                            </h1>
                            <p className="lead text-muted mb-4 hero-subtitle">
                                Join India's fastest-growing custom apparel marketplace. Zero registration fees, 0% commission for the first 30 days, and pan-India logistics handled by us.
                            </p>
                            <div className="d-grid gap-3 d-sm-flex justify-content-center justify-content-lg-start">
                                <Button className="bg-theme border-0 rounded-pill px-4 shadow-sm fw-bold">Start Selling</Button>
                                <Button className="border bg-white text-theme rounded-pill px-4 fw-bold" style={{ borderColor: "rgb(11, 83, 161)" }}>Learn More</Button>
                            </div>
                            <div className="mt-4 d-flex flex-column flex-sm-row justify-content-center justify-content-lg-start align-items-center gap-2 gap-sm-4 text-muted small fw-semibold">
                                <span><MdOutlineSecurity className="text-success me-1" size={18}/> Secure Payments</span>
                                <span><FaTruck className="text-theme me-1" size={18}/> We Handle Shipping</span>
                            </div>
                        </Col>
                        <Col lg={6} className="text-center position-relative mt-5 mt-lg-0">
                            <SellerHeroSVG />
                        </Col>
                    </Row>
                </Container>
            </div>
            
            <div className="mobile-hide">
                <WaveDivider />
            </div>

            {/* WHY SELL WITH US (UNIQUE SECTION) */}
            <div className="py-5 section-padding light-bg-theme" style={{ marginTop: "-5px" }}>
                <Container>
                    <div className="text-center mb-4 mb-md-5">
                        <h2 className="fw-bold mb-3 section-title">Why Sell on Printmont?</h2>
                        <p className="text-muted lead">Everything you need to grow your business, all in one dashboard.</p>
                    </div>
                    <Row className="g-4">
                        {[
                            { icon: <FaPercent size={30}/>, title: "0% Commission", desc: "Keep 100% of your profits for the first 30 days. Lowest industry fees thereafter." },
                            { icon: <FaTruck size={30}/>, title: "Hassle-Free Shipping", desc: "You pack the product, we pick it up and deliver it to 10,000+ pin codes." },
                            { icon: <FaMoneyBillWave size={30}/>, title: "Lightning Fast Payments", desc: "Get your funds settled securely into your bank account within 3 business days." },
                            { icon: <FaChartLine size={30}/>, title: "Advanced Analytics", desc: "Track views, conversions, and sales trends with our powerful seller dashboard." }
                        ].map((item, idx) => (
                            <Col md={6} lg={3} key={idx} className="mb-2 mb-md-0">
                                <Card className="h-100 border-0 shadow-sm text-center p-3 p-md-4 hover-lift" style={{ borderRadius: "15px", transition: "transform 0.3s" }}>
                                    <div className="card-icon bg-theme text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style={{ width: "70px", height: "70px" }}>
                                        {item.icon}
                                    </div>
                                    <h5 className="fw-bold mb-2">{item.title}</h5>
                                    <p className="text-muted small mb-0">{item.desc}</p>
                                </Card>
                            </Col>
                        ))}
                    </Row>
                </Container>
            </div>

            {/* INTERACTIVE PROFIT CALCULATOR (UNIQUE SECTION) */}
            <div className="py-5 section-padding">
                <Container>
                    <Row className="align-items-center g-4 g-lg-5">
                        <Col lg={5} className="text-center text-lg-start">
                            <h2 className="fw-bold mb-3 section-title">Calculate Your Potential Earnings</h2>
                            <p className="text-muted mb-4">See how much you can earn by partnering with Printmont. Our massive customer base and low fees mean higher margins for you.</p>
                            <ul className="list-unstyled text-muted d-inline-block text-start m-0">
                            <li className="mb-2"><FaCheckCircle className="text-success me-2"/> Transparent pricing structure</li>
                            <li className="mb-2"><FaCheckCircle className="text-success me-2"/> No hidden listing fees</li>
                            <li className="mb-2"><FaCheckCircle className="text-success me-2"/> Volume-based shipping discounts</li>
                        </ul>
                    </Col>
                    <Col lg={7}>
                        <Card className="border-0 shadow-lg" style={{ borderRadius: "20px", background: "linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)" }}>
                            <Card.Body className="p-4 p-md-5 calc-card">
                                <Form.Group className="mb-4">
                                    <div className="d-flex justify-content-between mb-2">
                                        <Form.Label className="fw-bold text-dark mb-0 small">Expected Orders Per Day</Form.Label>
                                        <span className="fw-bold text-theme">{ordersPerDay}</span>
                                    </div>
                                    <Form.Range min="1" max="500" value={ordersPerDay} onChange={(e) => setOrdersPerDay(e.target.value)} />
                                </Form.Group>

                                <Form.Group className="mb-4">
                                    <div className="d-flex justify-content-between mb-2">
                                        <Form.Label className="fw-bold text-dark mb-0 small">Average Order Value (₹)</Form.Label>
                                        <span className="fw-bold text-theme">₹{avgOrderValue}</span>
                                    </div>
                                    <Form.Range min="100" max="5000" step="50" value={avgOrderValue} onChange={(e) => setAvgOrderValue(e.target.value)} />
                                </Form.Group>

                                <div className="p-3 p-md-4 rounded text-center mt-4 light-bg-theme" style={{ border: "2px dashed rgba(11, 83, 161, 0.4)" }}>
                                    <h6 className="text-uppercase text-muted fw-bold mb-1 mb-md-2" style={{ fontSize: "0.75rem", letterSpacing: "1px" }}>Estimated Monthly Revenue</h6>
                                    <h2 className="display-6 fw-bold text-theme mb-0 revenue-title" style={{ wordBreak: "break-word" }}>
                                        ₹{monthlyRevenue.toLocaleString('en-IN')}
                                    </h2>
                                    <p className="text-success fw-bold mt-2 mb-0" style={{ fontSize: "0.75rem" }}>~ ₹{estimatedProfit.toLocaleString('en-IN')} Estimated Profit Margin (40%)</p>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            </Container>
            </div>

            {/* HOW IT WORKS */}
            <div className="bg-light py-5 section-padding">
                <Container>
                    <div className="text-center mb-4 mb-md-5">
                        <h2 className="fw-bold section-title">How It Works</h2>
                        <p className="text-muted lead mb-0">4 simple steps to scale your brand</p>
                    </div>
                    <Row className="text-center g-4 position-relative">
                        {/* Hidden line connecting steps on large screens */}
                        <div className="d-none d-lg-block position-absolute w-75" style={{ height: "2px", borderTop: "2px dashed #dee2e6", top: "45px", left: "12%", zIndex: 0 }}></div>
                        
                        {[
                            { icon: <FaStore size={30}/>, title: "1. Register Account", desc: "Sign up with your GSTIN and bank details." },
                            { icon: <BsBoxSeam size={30}/>, title: "2. List Products", desc: "Upload your catalog via our easy dashboard." },
                            { icon: <MdOutlineTouchApp size={30}/>, title: "3. Receive Orders", desc: "Customers across India buy your products." },
                            { icon: <FaRupeeSign size={30}/>, title: "4. Get Paid", desc: "Payments deposited securely into your bank." }
                        ].map((step, idx) => (
                            <Col xs={6} lg={3} key={idx} className="position-relative z-1 mb-2 mb-lg-0">
                                <div className="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center mx-auto mb-2 mb-md-3 step-wrapper" style={{ width: "90px", height: "90px", border: "4px solid rgb(240, 245, 255)" }}>
                                    <div className="text-theme step-icon">{step.icon}</div>
                                </div>
                                <h6 className="fw-bold mb-1 mb-md-2" style={{ fontSize: "0.95rem" }}>{step.title}</h6>
                                <p className="text-muted px-1 px-md-3" style={{ fontSize: "0.75rem", lineHeight: "1.3" }}>{step.desc}</p>
                            </Col>
                        ))}
                    </Row>
                </Container>
            </div>

            {/* REGISTRATION FORM & FAQ SECTION */}
            <div className="py-5 section-padding">
                <Container>
                    <Row className="g-4 g-lg-5">
                        {/* FAQ */}
                        <Col lg={6}>
                            <h3 className="fw-bold mb-3 mb-md-4 text-center text-lg-start section-title">Frequently Asked Questions</h3>
                        <Accordion defaultActiveKey="0" className="shadow-sm">
                            <Accordion.Item eventKey="0" className="border-0 border-bottom">
                                <Accordion.Header className="fw-bold">Do I need a GSTIN to sell on Printmont?</Accordion.Header>
                                <Accordion.Body className="text-muted text-sm">
                                    Yes, as per Indian government regulations, a valid GSTIN is mandatory for selling goods online.
                                </Accordion.Body>
                            </Accordion.Item>
                            <Accordion.Item eventKey="1" className="border-0 border-bottom">
                                <Accordion.Header>Who handles the shipping?</Accordion.Header>
                                <Accordion.Body className="text-muted text-sm">
                                    We do! Printmont's logistics partners will pick up the packaged products from your location and deliver them straight to the customer.
                                </Accordion.Body>
                            </Accordion.Item>
                            <Accordion.Item eventKey="2" className="border-0 border-bottom">
                                <Accordion.Header>When do I receive my payments?</Accordion.Header>
                                <Accordion.Body className="text-muted text-sm">
                                    Payments for delivered orders are processed and transferred to your registered bank account every Monday and Thursday.
                                </Accordion.Body>
                            </Accordion.Item>
                            <Accordion.Item eventKey="3" className="border-0">
                                <Accordion.Header>What categories can I sell in?</Accordion.Header>
                                <Accordion.Body className="text-muted text-sm">
                                    You can sell Apparel, Accessories, Notebooks, Mugs, and Corporate Gifting items. If your category isn't listed, contact our support team.
                                </Accordion.Body>
                            </Accordion.Item>
                        </Accordion>
                    </Col>

                    {/* FORM */}
                    <Col lg={6}>
                        <Card className="border-0 shadow-sm p-3 p-md-4" style={{ borderRadius: "15px" }}>
                            <h4 className="fw-bold mb-2">Start Your Journey Today</h4>
                            <p className="text-muted small mb-4" style={{ fontSize: "0.85rem" }}>Fill out the brief form below and our onboarding team will set up your seller account instantly.</p>
                            
                            <Form onSubmit={handleSubmit}>
                                <Row>
                                    <Col md={6}>
                                        <Form.Group className="mb-3">
                                            <Form.Label className="small fw-semibold">Full Name</Form.Label>
                                            <Form.Control type="text" name="name" value={formData.name} onChange={handleChange} required className="bg-light border-0 py-2" />
                                        </Form.Group>
                                    </Col>
                                    <Col md={6}>
                                        <Form.Group className="mb-3">
                                            <Form.Label className="small fw-semibold">Mobile Number</Form.Label>
                                            <Form.Control type="tel" name="mobile" value={formData.mobile} onChange={handleChange} required className="bg-light border-0 py-2" />
                                        </Form.Group>
                                    </Col>
                                </Row>

                                <Form.Group className="mb-3">
                                    <Form.Label className="small fw-semibold">Email Address</Form.Label>
                                    <Form.Control type="email" name="email" value={formData.email} onChange={handleChange} required className="bg-light border-0 py-2" />
                                </Form.Group>

                                <Row>
                                    <Col md={6}>
                                        <Form.Group className="mb-3">
                                            <Form.Label className="small fw-semibold">GSTIN</Form.Label>
                                            <Form.Control type="text" name="gst" value={formData.gst} onChange={handleChange} required className="bg-light border-0 py-2" />
                                        </Form.Group>
                                    </Col>
                                    <Col md={6}>
                                        <Form.Group className="mb-4">
                                            <Form.Label className="small fw-semibold">Primary Category</Form.Label>
                                            <Form.Select name="category" value={formData.category} onChange={handleChange} className="bg-light border-0 py-2">
                                                <option>Apparel</option>
                                                <option>Accessories</option>
                                                <option>Stationery</option>
                                                <option>Corporate Gifts</option>
                                            </Form.Select>
                                        </Form.Group>
                                    </Col>
                                </Row>

                                <Button type="submit" className="bg-theme w-100 py-3 fw-bold rounded-pill shadow-sm border-0 text-white">
                                    Create Seller Account
                                </Button>
                            </Form>
                        </Card>
                    </Col>
                </Row>
            </Container>
            </div>
        </div>
        </>
    );
};

export default BecomeASeller;
