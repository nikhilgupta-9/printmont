import React, { useState } from 'react';
import { Container, Row, Col, Form, Button, Card, Carousel } from 'react-bootstrap';
import { FaPhoneAlt, FaCheckCircle, FaStar, FaShippingFast, FaHeadset, FaPrint } from 'react-icons/fa';
import { MdHighQuality } from "react-icons/md";
import { AiFillTags } from "react-icons/ai";
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

    const dummyImage = "/fashion_brand.png";

    const businessNeeds = [
        { title: "For MNCs & Corporates", image: "/corporate_apparel.png", desc: "Premium corporate apparel, embroidered polo shirts, and custom office wear to maintain consistent global branding." },
        { title: "For Startups & Tech", image: "/startup_hoodies.png", desc: "Trendy custom hoodies, graphic tees, and team merchandise that build company culture and make your brand stand out." },
        { title: "For Cafes & Hospitality", image: "/restaurant_aprons.png", desc: "Branded aprons, chef coats, and uniform t-shirts designed for comfort, durability, and a professional aesthetic." },
        { title: "For D2C Fashion Brands", image: "/fashion_brand.png", desc: "High-quality streetwear blanks, custom neck labels, and seamless print-on-demand services to scale your clothing line." },
        { title: "For Gyms & Fitness", image: "/activewear_gym.png", desc: "Moisture-wicking activewear, branded tank tops, and tracksuits to build a strong, motivating community around your fitness brand." },
        { title: "For Local Businesses", image: "/b2b_hero.png", desc: "Custom uniforms, promotional event tees, and high-quality staff apparel to dominate your local market with a professional look." }
    ];

    const uniquePoints = [
        { title: "Quality Prints", desc: "We use state-of-the-art machinery to ensure vibrant colors and crisp details." },
        { title: "Tech-led Delivery System", desc: "Track your orders in real-time with our advanced logistics network." },
        { title: "End-to-end printing solutions", desc: "From design consultation to final delivery, we handle everything." },
        { title: "Multiple printing options", desc: "Offset, digital, screen printing, and large format printing available." },
        { title: "Hassle-free printing experience", desc: "Easy online ordering with instant previews and dedicated support." },
        { title: "Design Services", desc: "Don't have a design? Our expert team will create one for you." }
    ];

    return (
        <div className="business-solutions-page" style={{ backgroundColor: "#f8f9fa" }}>
            {/* HERO CAROUSEL */}
            <Carousel indicators={true} controls={true} className="mb-5 shadow-sm">
                <Carousel.Item>
                    <div 
                        style={{ 
                            height: "450px", 
                            backgroundImage: `url('/b2b_hero.png')`, 
                            backgroundSize: 'cover', 
                            backgroundPosition: 'center',
                            position: 'relative' 
                        }}
                    >
                        {/* Dark Overlay for text readability */}
                        <div style={{ position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(0,0,0,0.5)' }}></div>
                        
                        <Container className="h-100 d-flex flex-column justify-content-center align-items-center position-relative text-center text-white px-3" style={{ zIndex: 1 }}>
                            <h1 className="fw-bold display-5 mb-3 text-white" style={{ textShadow: "2px 2px 4px rgba(0,0,0,0.5)" }}>Scale Your Apparel Brand</h1>
                            <p className="fs-5 mb-4" style={{ maxWidth: "700px", textShadow: "1px 1px 3px rgba(0,0,0,0.8)" }}>Premium quality blanks, custom embroidery, and on-demand printing solutions for businesses of all sizes.</p>
                            <Button variant="primary" size="lg" className="rounded-pill px-5 py-2 fw-bold shadow bg-theme border-0">Partner With Us</Button>
                        </Container>
                    </div>
                </Carousel.Item>
            </Carousel>

            <Container className="py-5">
                <Row className="justify-content-center">
                    {/* FORM SECTION */}
                    <Col lg={8} md={10} className="mb-5">
                        <Card className="shadow-sm border-0 p-4">
                            <h5 className="mb-4 text-center fw-bold">Talk to a Print Expert</h5>
                            <p className="text-center text-muted mb-4 small">Fill out the form below and our printing experts will get in touch with you shortly to discuss your custom requirements.</p>
                            
                            <Form onSubmit={handleSubmit}>
                                <Row className="mb-3">
                                    <Col md={6} className="mb-3 mb-md-0">
                                        <Form.Label className="small fw-semibold">Full Name *</Form.Label>
                                        <Form.Control type="text" name="fullName" value={formData.fullName} onChange={handleChange} required className="rounded-0" />
                                    </Col>
                                    <Col md={6}>
                                        <Form.Label className="small fw-semibold">Mobile Number *</Form.Label>
                                        <Form.Control type="tel" name="mobile" value={formData.mobile} onChange={handleChange} required className="rounded-0" />
                                    </Col>
                                </Row>

                                <Row className="mb-3">
                                    <Col md={6} className="mb-3 mb-md-0">
                                        <Form.Label className="small fw-semibold">Email ID *</Form.Label>
                                        <Form.Control type="email" name="email" value={formData.email} onChange={handleChange} required className="rounded-0" />
                                    </Col>
                                    <Col md={6}>
                                        <Form.Label className="small fw-semibold">Company Name</Form.Label>
                                        <Form.Control type="text" name="company" value={formData.company} onChange={handleChange} className="rounded-0" />
                                    </Col>
                                </Row>

                                <Row className="mb-3">
                                    <Col md={6} className="mb-3 mb-md-0">
                                        <Form.Label className="small fw-semibold">Pincode *</Form.Label>
                                        <Form.Control type="text" name="pincode" value={formData.pincode} onChange={handleChange} required className="rounded-0" />
                                    </Col>
                                    <Col md={6}>
                                        <Form.Label className="small fw-semibold">Request *</Form.Label>
                                        <Form.Select name="requestType" value={formData.requestType} onChange={handleChange} className="rounded-0">
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

                                <div className="d-flex flex-column flex-md-row gap-3 justify-content-center align-items-center mb-3">
                                    <Button variant="info" className="text-white rounded-pill px-5 py-2 fw-bold" style={{ backgroundColor: "#17a2b8", minWidth: "200px" }}>
                                        <FaPhoneAlt className="me-2" /> Call Now
                                    </Button>
                                    <Button type="submit" variant="success" className="rounded-pill px-5 py-2 fw-bold" style={{ minWidth: "200px" }}>
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

                {/* PRINTING FOR EVERY NEED */}
                <div className="text-center mb-5 bg-light py-2 border-top border-bottom">
                    <h5 className="text-uppercase fw-bold m-0" style={{ letterSpacing: "1px" }}>PRINTING FOR EVERY NEED</h5>
                </div>

                <div className="mb-5">
                    {businessNeeds.map((item, idx) => (
                        <Row key={idx} className={`align-items-center mb-5 ${idx % 2 !== 0 ? 'flex-row-reverse' : ''}`}>
                            <Col md={6} className="mb-3 mb-md-0 text-center">
                                <img src={item.image} alt={item.title} className="img-fluid rounded shadow" style={{ maxHeight: "350px", width: "100%", objectFit: "cover" }} />
                            </Col>
                            <Col md={6} className="px-md-5">
                                <h3 className="fw-bold mb-3" style={{ color: "#2c3e50" }}>{item.title}</h3>
                                <p className="text-muted" style={{ fontSize: "1.1rem", lineHeight: "1.7" }}>{item.desc}</p>
                            </Col>
                        </Row>
                    ))}
                </div>

                {/* OUR WORK NETWORK */}
                <div className="mb-5 text-center">
                    <h4 className="fw-bold mb-4">Our Print Network</h4>
                    <img src="/b2b_hero.png" alt="Network" className="img-fluid rounded mb-3 shadow" style={{ width: "100%", maxHeight: "300px", objectFit: "cover" }} />
                    <p className="text-muted">Serving over 10,000+ pin codes across India with state-of-the-art printing facilities.</p>
                </div>

                {/* HOW IT WORKS */}
                <div className="mb-5 text-center">
                    <h4 className="fw-bold mb-4">How Printmont Works</h4>
                    <img src="/corporate_apparel.png" alt="How it works" className="img-fluid rounded mb-3 shadow" style={{ width: "100%", maxHeight: "300px", objectFit: "cover" }} />
                    <p className="text-muted">Simple 4-step process: Select Product → Upload Design → Approve Proof → Get it Delivered.</p>
                </div>

                {/* WHY PRINTMONT IS UNIQUE */}
                <div className="mb-5 p-4 rounded" style={{ backgroundColor: "#e3f2fd" }}>
                    <h4 className="fw-bold mb-4 text-center text-primary">WHY PRINTMONT IS UNIQUE FOR YOU</h4>
                    <Row>
                        {uniquePoints.map((point, idx) => (
                            <Col md={6} lg={4} className="mb-4" key={idx}>
                                <div className="d-flex align-items-start bg-white p-3 rounded h-100 shadow-sm border border-light">
                                    <FaCheckCircle className="text-success mt-1 me-3 flex-shrink-0" size={24} />
                                    <div>
                                        <h6 className="fw-bold mb-2">{point.title}</h6>
                                        <p className="text-muted small mb-0">{point.desc}</p>
                                    </div>
                                </div>
                            </Col>
                        ))}
                    </Row>
                </div>

                {/* TESTIMONIALS */}
                <div className="mb-5 text-center">
                    <h4 className="fw-bold mb-4 text-muted bg-light py-2 rounded">What Our Customers Say About Us</h4>
                    <Swiper
                        spaceBetween={20}
                        slidesPerView={1}
                        breakpoints={{
                            768: { slidesPerView: 2 },
                            1024: { slidesPerView: 3 }
                        }}
                        className="py-3"
                    >
                        {[1, 2, 3, 4].map((i) => (
                            <SwiperSlide key={i}>
                                <div className="bg-white p-3 rounded shadow-sm border">
                                    <img src="/startup_hoodies.png" alt="Testimonial" className="img-fluid rounded mb-3" style={{ height: "150px", width: "100%", objectFit: "cover", objectPosition: "top" }} />
                                    <div className="d-flex justify-content-center text-warning mb-2">
                                        <FaStar/><FaStar/><FaStar/><FaStar/><FaStar/>
                                    </div>
                                    <p className="small text-muted font-italic">"Excellent print quality and incredibly fast delivery. Printmont has become our go-to partner for all corporate needs."</p>
                                    <h6 className="fw-bold m-0">- Happy Client {i}</h6>
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
