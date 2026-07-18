import React, { useState } from 'react';
import { Container, Row, Col, Form, Button, Card, Carousel } from 'react-bootstrap';
import { FaPhoneAlt, FaStar, FaShippingFast, FaHeadset, FaFileAlt, FaUserTie } from 'react-icons/fa';
import { Swiper, SwiperSlide } from "swiper/react";
import "swiper/css";

const BusinessSolutions = () => {
    const [formData, setFormData] = useState({
        fullName: '',
        mobile: '',
        email: '',
        company: '',
        pincode: '',
        requestType: 'Notebook',
        message: ''
    });

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        alert("Form submitted! We will contact you soon.");
    };

    return (
        <div className="business-solutions-page" style={{ backgroundColor: "#f8f9fa", overflowX: "hidden" }}>
            {/* HERO CAROUSEL */}
            <Carousel indicators={true} controls={true} className="mb-4 mb-md-5 shadow-sm">
                <Carousel.Item>
                    <div 
                        style={{ 
                            minHeight: "320px",
                            height: "clamp(320px, 45vh, 480px)", 
                            backgroundImage: `url('/b2b_hero.png')`, 
                            backgroundSize: 'cover', 
                            backgroundPosition: 'center',
                            position: 'relative' 
                        }}
                    >
                        {/* Dark Overlay */}
                        <div style={{ position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(0,0,0,0.55)' }}></div>
                        
                        <Container className="h-100 d-flex flex-column justify-content-center align-items-center position-relative text-center text-white px-3" style={{ zIndex: 1 }}>
                            <h1 className="fw-bold mb-2 mb-md-3 text-white" style={{ fontSize: "clamp(1.6rem, 4.5vw, 3rem)", textShadow: "2px 2px 4px rgba(0,0,0,0.6)" }}>
                                Scale Your Apparel Brand
                            </h1>
                            <p className="mb-3 mb-md-4" style={{ fontSize: "clamp(0.9rem, 2vw, 1.25rem)", maxWidth: "700px", textShadow: "1px 1px 3px rgba(0,0,0,0.8)" }}>
                                Premium quality blanks, custom embroidery, and on-demand printing solutions for businesses of all sizes.
                            </p>
                            <Button variant="primary" size="md" className="rounded-pill px-4 px-md-5 py-2 fw-bold shadow bg-theme border-0 fs-6">
                                Partner With Us
                            </Button>
                        </Container>
                    </div>
                </Carousel.Item>
            </Carousel>

            <Container className="py-2 py-md-4">
                {/* FORM SECTION */}
                <Row className="justify-content-center mb-4 mb-md-5">
                    <Col lg={9} md={11} xs={12}>
                        <Card className="shadow-sm border-0 p-3 p-md-4">
                            <h4 className="mb-3 text-center fw-bold fs-4 fs-md-3">Talk to a Print Expert</h4>
                            <p className="text-center text-muted mb-4 small">
                                Fill out the form below and our printing experts will get in touch with you shortly to discuss your custom requirements.
                            </p>
                            
                            <Form onSubmit={handleSubmit}>
                                <Row className="mb-2 mb-md-3">
                                    <Col md={6} xs={12} className="mb-3 mb-md-0">
                                        <Form.Label className="small fw-semibold">Full Name *</Form.Label>
                                        <Form.Control type="text" name="fullName" value={formData.fullName} onChange={handleChange} required className="rounded-0 py-2" />
                                    </Col>
                                    <Col md={6} xs={12}>
                                        <Form.Label className="small fw-semibold">Mobile Number *</Form.Label>
                                        <Form.Control type="tel" name="mobile" value={formData.mobile} onChange={handleChange} required className="rounded-0 py-2" />
                                    </Col>
                                </Row>

                                <Row className="mb-2 mb-md-3">
                                    <Col md={6} xs={12} className="mb-3 mb-md-0">
                                        <Form.Label className="small fw-semibold">Email ID *</Form.Label>
                                        <Form.Control type="email" name="email" value={formData.email} onChange={handleChange} required className="rounded-0 py-2" />
                                    </Col>
                                    <Col md={6} xs={12}>
                                        <Form.Label className="small fw-semibold">Company Name</Form.Label>
                                        <Form.Control type="text" name="company" value={formData.company} onChange={handleChange} className="rounded-0 py-2" />
                                    </Col>
                                </Row>

                                <Row className="mb-2 mb-md-3">
                                    <Col md={6} xs={12} className="mb-3 mb-md-0">
                                        <Form.Label className="small fw-semibold">Pincode *</Form.Label>
                                        <Form.Control type="text" name="pincode" value={formData.pincode} onChange={handleChange} required className="rounded-0 py-2" />
                                    </Col>
                                    <Col md={6} xs={12}>
                                        <Form.Label className="small fw-semibold">Request *</Form.Label>
                                        <Form.Select name="requestType" value={formData.requestType} onChange={handleChange} className="rounded-0 py-2">
                                            <option>Notebook</option>
                                            <option>Business Cards</option>
                                            <option>Apparel</option>
                                            <option>Packaging</option>
                                            <option>Corporate Gifting</option>
                                            <option>Other</option>
                                        </Form.Select>
                                    </Col>
                                </Row>

                                <Form.Group className="mb-4">
                                    <Form.Label className="small fw-semibold">Write a Message (Optional)</Form.Label>
                                    <Form.Control as="textarea" rows={3} name="message" value={formData.message} onChange={handleChange} className="rounded-0" />
                                </Form.Group>

                                <div className="d-flex flex-column flex-sm-row gap-3 justify-content-center align-items-center mb-3">
                                    <Button variant="info" className="text-white rounded-pill px-4 px-md-5 py-2 fw-bold w-100 w-sm-auto" style={{ backgroundColor: "#17a2b8", minWidth: "180px" }}>
                                        <FaPhoneAlt className="me-2" /> Call Now
                                    </Button>
                                    <Button type="submit" variant="success" className="rounded-pill px-4 px-md-5 py-2 fw-bold w-100 w-sm-auto" style={{ minWidth: "180px" }}>
                                        Submit
                                    </Button>
                                </div>
                                <div className="text-center text-muted small fw-semibold">
                                    Or connect with us directly at: <span className="text-dark">1800-123-4567</span>
                                </div>
                            </Form>
                        </Card>
                    </Col>
                </Row>

                {/* 1. PRINTMONT CORPORATE (IMAGE 1) */}
                <div className="mb-4 mb-md-5 py-3 py-md-4 bg-white rounded p-3 p-md-4 shadow-sm border">
                    <h2 className="text-center fw-bold mb-4 mb-md-5 text-dark fs-3 fs-md-2">Printmont Corporate</h2>
                    <Row className="g-4 px-1 px-md-3">
                        <Col lg={6} md={6} xs={12} className="mb-2 mb-md-3">
                            <div className="p-2 border-start border-3 border-primary ps-3 h-100">
                                <h5 className="fw-bold text-primary mb-2 fs-5">For Marketing</h5>
                                <h6 className="fw-bold text-dark mb-2 fs-6">Boost Your Event Experience</h6>
                                <p className="text-secondary small m-0" style={{ lineHeight: "1.6" }}>
                                    We provide high-quality customized prints to help you run better events. Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptatibus nam nemo maxime quasi ab aliquid eveniet magnam ipsum sint. Vero.
                                </p>
                            </div>
                        </Col>

                        <Col lg={6} md={6} xs={12} className="mb-2 mb-md-3">
                            <div className="p-2 border-start border-3 border-primary ps-3 h-100">
                                <h5 className="fw-bold text-primary mb-2 fs-5">For Sales</h5>
                                <h6 className="fw-bold text-dark mb-2 fs-6">Corporate Branding Solutions</h6>
                                <p className="text-secondary small m-0" style={{ lineHeight: "1.6" }}>
                                    Premium corporate merchandise & branding solutions. Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptatibus nam nemo maxime quasi ab aliquid eveniet magnam ipsum sint. Vero.
                                </p>
                            </div>
                        </Col>

                        <Col lg={6} md={6} xs={12} className="mb-2 mb-md-3">
                            <div className="p-2 border-start border-3 border-primary ps-3 h-100">
                                <h5 className="fw-bold text-primary mb-2 fs-5">For Customer Experience</h5>
                                <h6 className="fw-bold text-dark mb-2 fs-6">Make Your Brand Stand Out</h6>
                                <p className="text-secondary small m-0" style={{ lineHeight: "1.6" }}>
                                    Affordable and creative branding to grow your startup. Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptatibus nam nemo maxime quasi ab aliquid eveniet magnam ipsum sint. Vero.
                                </p>
                            </div>
                        </Col>

                        <Col lg={6} md={6} xs={12} className="mb-2 mb-md-3">
                            <div className="p-2 border-start border-3 border-primary ps-3 h-100">
                                <h5 className="fw-bold text-primary mb-2 fs-5">For HR</h5>
                                <h6 className="fw-bold text-dark mb-2 fs-6">Are you aiming for a seamless and engaging on boarding process?</h6>
                                <p className="text-secondary small m-0" style={{ lineHeight: "1.6" }}>
                                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptatibus nam nemo maxime quasi ab aliquid eveniet magnam ipsum sint. Vero.
                                </p>
                            </div>
                        </Col>
                    </Row>
                </div>

                {/* 2. WHY PRINTMONT? (IMAGE 2) */}
                <div className="mb-4 mb-md-5 py-3 py-md-4 px-3 px-md-4 bg-white rounded shadow-sm border">
                    <Row className="align-items-center g-4">
                        <Col lg={4} md={12} xs={12} className="text-center text-lg-start mb-2 mb-lg-0 pe-lg-4">
                            <h2 className="display-5 fw-bold text-primary lh-sm m-0 fs-2 fs-md-1">
                                Why<br className="d-none d-lg-inline" /> Printmont?
                            </h2>
                        </Col>

                        <Col lg={8} md={12} xs={12}>
                            <Row className="g-3 g-md-4">
                                <Col sm={6} xs={12} className="d-flex align-items-start">
                                    <FaFileAlt className="text-primary me-3 flex-shrink-0 mt-1" size={28} />
                                    <div>
                                        <h6 className="fw-bold mb-1 text-dark fs-6">GST Invoice</h6>
                                        <p className="text-secondary small m-0" style={{ lineHeight: "1.5" }}>
                                            We facilitate PO base orders and inventory. GST reconciliation is quick and easy with Printmont.
                                        </p>
                                    </div>
                                </Col>

                                <Col sm={6} xs={12} className="d-flex align-items-start">
                                    <FaShippingFast className="text-primary me-3 flex-shrink-0 mt-1" size={28} />
                                    <div>
                                        <h6 className="fw-bold mb-1 text-dark fs-6">Drop-Shipping Facilities</h6>
                                        <p className="text-secondary small m-0" style={{ lineHeight: "1.5" }}>
                                            We deliver to multiple locations handling inventory, storage and order fulfillment, saving you time and displays that showcase daily specials, promotions, or logistical handles.
                                        </p>
                                    </div>
                                </Col>

                                <Col sm={6} xs={12} className="d-flex align-items-start">
                                    <FaUserTie className="text-primary me-3 flex-shrink-0 mt-1" size={28} />
                                    <div>
                                        <h6 className="fw-bold mb-1 text-dark fs-6">Single POC</h6>
                                        <p className="text-secondary small m-0" style={{ lineHeight: "1.5" }}>
                                            We designate a Single Point of Contact (POC) responsible for all your print needs, ensuring swift order handling and issues without unnecessary handovers.
                                        </p>
                                    </div>
                                </Col>

                                <Col sm={6} xs={12} className="d-flex align-items-start">
                                    <FaHeadset className="text-primary me-3 flex-shrink-0 mt-1" size={28} />
                                    <div>
                                        <h6 className="fw-bold mb-1 text-dark fs-6">Customer Service</h6>
                                        <p className="text-secondary small m-0" style={{ lineHeight: "1.5" }}>
                                            We emphasize exceptional customer service, being responsive, helpful, and adaptable to unique business needs.
                                        </p>
                                    </div>
                                </Col>
                            </Row>
                        </Col>
                    </Row>
                </div>

                {/* 3. PROMOTIONAL BANNER */}
                <div className="mb-4 mb-md-5 text-center">
                    <img 
                        src="/b2b_hero.png" 
                        alt="Printmont Corporate Banner" 
                        className="img-fluid rounded shadow-sm w-100" 
                        style={{ maxHeight: "300px", objectFit: "cover", width: "100%" }} 
                    />
                </div>

                {/* 4. NUMBERS BACKING UP OUR PROPOSITION (IMAGE 3) */}
                <div 
                    className="mb-4 mb-md-5 p-3 p-md-4 p-lg-5 rounded" 
                    style={{ backgroundColor: "#eaf2ff" }}
                >
                    <div 
                        className="border border-3 rounded p-3 p-md-4"
                        style={{ borderColor: "#0066cc" }}
                    >
                        <Row className="align-items-center text-center text-md-start g-3 g-md-4">
                            <Col lg={3} md={6} xs={12} className="text-center text-md-start mb-2 mb-md-0">
                                <h6 className="fw-bold text-primary m-0 fs-5" style={{ color: "#0056b3" }}>
                                    Numbers Backing Up Our Proposition
                                </h6>
                            </Col>

                            <Col lg={3} md={6} sm={4} xs={12} className="text-center">
                                <h2 className="fw-bold text-primary mb-1" style={{ color: "#0056b3", fontSize: "clamp(1.5rem, 3.5vw, 2.25rem)" }}>
                                    1 Million+
                                </h2>
                                <p className="text-secondary small m-0 fw-semibold">Customers Served</p>
                            </Col>

                            <Col lg={3} md={6} sm={4} xs={12} className="text-center">
                                <h2 className="fw-bold text-primary mb-1" style={{ color: "#0056b3", fontSize: "clamp(1.5rem, 3.5vw, 2.25rem)" }}>
                                    20,000+
                                </h2>
                                <p className="text-secondary small m-0 fw-semibold">Printing & Gifting Products</p>
                            </Col>

                            <Col lg={3} md={6} sm={4} xs={12} className="text-center">
                                <h2 className="fw-bold text-primary mb-1" style={{ color: "#0056b3", fontSize: "clamp(1.5rem, 3.5vw, 2.25rem)" }}>
                                    7 Years
                                </h2>
                                <p className="text-secondary small m-0 fw-semibold">of Service Excellence</p>
                            </Col>
                        </Row>
                    </div>
                </div>

                {/* TESTIMONIALS */}
                <div className="mb-4 mb-md-5 text-center">
                    <h4 className="fw-bold mb-4 text-muted bg-light py-2 rounded fs-5 fs-md-4">What Our Customers Say About Us</h4>
                    <Swiper
                        spaceBetween={20}
                        slidesPerView={1}
                        breakpoints={{
                            576: { slidesPerView: 1 },
                            768: { slidesPerView: 2 },
                            1024: { slidesPerView: 3 }
                        }}
                        className="py-2 py-md-3"
                    >
                        {[1, 2, 3, 4].map((i) => (
                            <SwiperSlide key={i}>
                                <div className="bg-white p-3 rounded shadow-sm border h-100">
                                    <img src="/startup_hoodies.png" alt="Testimonial" className="img-fluid rounded mb-3" style={{ height: "140px", width: "100%", objectFit: "cover", objectPosition: "top" }} />
                                    <div className="d-flex justify-content-center text-warning mb-2">
                                        <FaStar/><FaStar/><FaStar/><FaStar/><FaStar/>
                                    </div>
                                    <p className="small text-muted font-italic">"Excellent print quality and incredibly fast delivery. Printmont has become our go-to partner for all corporate needs."</p>
                                    <h6 className="fw-bold m-0 small">- Happy Client {i}</h6>
                                </div>
                            </SwiperSlide>
                        ))}
                    </Swiper>
                </div>
            </Container>
        </div>
    );
};

export default BusinessSolutions;
