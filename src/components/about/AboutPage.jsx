import React, { useState, useEffect } from "react";
import { Container, Row, Col } from "react-bootstrap";
import { API_ENDPOINTS, ASSET_URL } from "../../config/apiEndpoints";
import "./About.css";

const AboutPage = () => {
  const [sections, setSections] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Scroll to top on mount
    window.scrollTo(0, 0);

    const fetchAboutData = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.ABOUT);
        if (!response.ok) throw new Error("Network response was not ok");
        const json = await response.json();
        if (json && json.success && Array.isArray(json.data)) {
          setSections(json.data);
        }
      } catch (error) {
        console.error("Error fetching about page API content:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchAboutData();
  }, []);

  const getSection = (type, titleFallback) => {
    if (!sections.length) return null;
    return (
      sections.find(s => s.section_type === type) ||
      sections.find(s => s.section_title?.toLowerCase().includes(titleFallback.toLowerCase()))
    );
  };

  const heroSection = getSection("hero", "Welcome to Printmont");
  const missionSection = getSection("mission", "Our Mission");
  const visionSection = getSection("history", "Our Vision");

  // Timeline milestones matching FNP's timeline format
  const milestones = [
    { year: "1994", text: "Beginning of the first organised print outlet, fueled by passion." },
    { year: "2002", text: "India’s first online custom merchandising store that redefined gifting." },
    { year: "2004", text: "Launch of custom corporate gift packs, adding sweetness to occasions." },
    { year: "2010", text: "Printmont goes global - making every custom print celebration accessible." },
    { year: "2023", text: "400+ outlets and millions of custom print packages delivered." },
    { year: "2024", text: "Launch of Exclusive Luxury Box Collection & Same-Day Custom Delivery." }
  ];

  // Leadership team data matching FNP's vertical layout with overlay names
  const team = [
    { name: "Vikaas Gutgutia", role: "Founder & MD", img: "/team-vikaas.png" },
    { name: "Meeta Gutgutia", role: "Director & Creative Head", img: "/team-meeta.png" },
    { name: "Pawan Gadia", role: "Global CEO & Director", img: "/team-pawan.png" },
    { name: "Saurav Singh", role: "Chief Technology Officer", img: "/team-saurav.png" }
  ];

  return (
    <div className="about-fnp-page bg-white">
      {/* 🌸 FNP Hero Banner with Overlay */}
      <div 
        className="fnp-hero-banner-new" 
        style={{ backgroundImage: `url('/about-hero-banner.png')` }}
      >
        <div className="fnp-hero-overlay d-flex align-items-center">
          <Container>
            <div className="fnp-hero-text-wrap text-start">
              <span className="fnp-hero-subtitle text-white-50 text-uppercase fw-semibold mb-2 d-block">ABOUT US</span>
              <h1 className="fnp-hero-main-title text-white">
                We are the creators of <br />
                <span className="highlight-text">Special Moments</span>
              </h1>
            </div>
          </Container>
        </div>
      </div>

      {/* 📜 How it all started Section */}
      <div className="fnp-how-it-started py-5 bg-white">
        <Container>
          <h2 className="fnp-olive-heading mb-4">How it all started</h2>
          <p className="fnp-started-desc text-muted mb-0">
            {heroSection?.section_content || 
              "A quest for the perfect print led to a gifting revolution, thanks to our founders. From a humble beginning in 2008 to becoming India’s leading one-stop destination for personalized prints and corporate celebrations, our journey has been about a lot more than merchandising—it's about crafting memories, spreading joy, and touching countless hearts."}
          </p>
        </Container>
      </div>

      {/* 📦 Beige Info Section: Delivering Love to 100+ Countries */}
      <div className="fnp-beige-section py-5">
        <Container>
          <div className="fnp-beige-card p-4 p-md-5 rounded-3">
            <Row className="align-items-center gy-4">
              <Col md={6}>
                <div className="fnp-beige-img-wrap position-relative">
                  <img 
                    src="/about-delivering-love.png" 
                    alt="Delivering Love Worldwide" 
                    className="img-fluid rounded-3 shadow-sm w-100"
                  />
                  {/* Delivery Worldwide Dashed Circular Badge */}
                  <div className="fnp-delivery-badge">
                    <div className="fnp-badge-inner">
                      <svg width="24" height="24" viewBox="0 0 24 24" fill="white" className="mb-1">
                        <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L14 19v-5.5L21 16z"/>
                      </svg>
                      <span className="fnp-badge-txt">DELIVERY WORLDWIDE</span>
                    </div>
                  </div>
                </div>
              </Col>
              <Col md={6} className="ps-md-5">
                <h3 className="fnp-olive-heading mb-3">Delivering Love to 100+ Countries</h3>
                <p className="fnp-started-desc text-muted mb-0">
                  Our thoughtfully curated gifts travel the world to make celebrations special, even when one can’t be there in person.
                </p>
              </Col>
            </Row>
          </div>
        </Container>
      </div>

      {/* ⭐ The Perfect Surprise Section */}
      <div className="fnp-perfect-surprise py-5 bg-white">
        <Container className="text-center">
          <h2 className="fnp-olive-heading mb-5">The Perfect Surprise</h2>
          <Row className="gy-4">
            <Col md={4}>
              <div className="fnp-surprise-card">
                {/* Gift Box Icon */}
                <div className="fnp-surprise-icon-wrap mb-3 mx-auto">
                  <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                    <path d="M12 24h40v32H12V24z" stroke="#7d7f3e" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M8 16h48v8H8v-8z" fill="#facc15" stroke="#7d7f3e" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M32 16v40M32 16c-3-6-10-6-10 0s7 6 10 0M32 16c3-6 10-6 10 0s-7 6-10 0" stroke="#7d7f3e" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <circle cx="16" cy="40" r="2" fill="#7d7f3e"/>
                    <circle cx="48" cy="34" r="2" fill="#facc15"/>
                    <circle cx="44" cy="48" r="2" fill="#7d7f3e"/>
                  </svg>
                </div>
                <h5 className="fnp-surprise-title fw-bold">Thoughtful Gifts</h5>
              </div>
            </Col>
            <Col md={4}>
              <div className="fnp-surprise-card">
                {/* Calendar Icon */}
                <div className="fnp-surprise-icon-wrap mb-3 mx-auto">
                  <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                    <rect x="12" y="14" width="40" height="40" rx="4" stroke="#7d7f3e" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M12 26h40M22 10v8M42 10v8" stroke="#7d7f3e" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <circle cx="22" cy="36" r="2" fill="#facc15"/>
                    <circle cx="32" cy="36" r="2" fill="#7d7f3e"/>
                    <circle cx="42" cy="36" r="2" fill="#7d7f3e"/>
                    <circle cx="22" cy="44" r="2" fill="#7d7f3e"/>
                    <path d="M30 44l3 3 6-6" stroke="#7d7f3e" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
                <h5 className="fnp-surprise-title fw-bold">Scheduled Delivery</h5>
              </div>
            </Col>
            <Col md={4}>
              <div className="fnp-surprise-card">
                {/* Balloons Icon */}
                <div className="fnp-surprise-icon-wrap mb-3 mx-auto">
                  <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                    <path d="M22 34c-6-1-10-6-10-12s5-11 11-11 10 5 10 11c0 3-1 6-3 8" stroke="#7d7f3e" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M42 34c6-1 10-6 10-12s-5-11-11-11-10 5-10 11c0 3 1 6 3 8" fill="#facc15" stroke="#7d7f3e" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M22 34c-1 3-3 8-1 12M42 34c1 3 3 8 1 12M32 46c0-4 1-9-1-13" stroke="#7d7f3e" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
                <h5 className="fnp-surprise-title fw-bold">Personalised for them</h5>
              </div>
            </Col>
          </Row>
        </Container>
      </div>

      {/* 👁️ Vision & Mission side-by-side with Landscape Images */}
      <div className="fnp-vision-mission-cards py-5 bg-white">
        <Container>
          <Row className="gy-5">
            <Col md={6}>
              <div className="fnp-vision-card pe-md-4">
                <img 
                  src="/about-vision-sunflowers.png" 
                  alt="Vision" 
                  className="img-fluid rounded-3 mb-4 w-100 object-fit-cover"
                  style={{ height: "240px" }}
                />
                <h3 className="fnp-olive-heading mb-3">Vision</h3>
                <p className="fnp-started-desc text-muted mb-0">
                  {visionSection?.section_content || 
                    "Be the most trusted gifting brand to celebrate the joy of giving."}
                </p>
              </div>
            </Col>
            <Col md={6}>
              <div className="fnp-mission-card ps-md-4">
                <img 
                  src="/about-mission-celebration.png" 
                  alt="Mission" 
                  className="img-fluid rounded-3 mb-4 w-100 object-fit-cover"
                  style={{ height: "240px" }}
                />
                <h3 className="fnp-olive-heading mb-3">Mission</h3>
                <p className="fnp-started-desc text-muted mb-0">
                  {missionSection?.section_content || 
                    "Wow every customer every time, through premium products, services, value for money driven by innovation, technology and people-first approach."}
                </p>
              </div>
            </Col>
          </Row>
        </Container>
      </div>

      {/* 📅 Timeline: Rooted in Love, Growing With You (Dark Banner Timeline) */}
      <div 
        className="fnp-dark-timeline-section py-5"
        style={{ backgroundImage: `url('/about-hero-banner.png')` }}
      >
        <div className="fnp-timeline-overlay py-5">
          <Container>
            <h3 className="text-center text-white mb-5 fw-bold fnp-timeline-heading">Rooted in Love, Growing With You</h3>
            <div className="fnp-timeline-wrapper position-relative">
              <div className="fnp-timeline-horizontal-line d-none d-md-block"></div>
              <Row className="gy-5 gy-md-0">
                {milestones.map((m, idx) => (
                  <Col md={2} key={idx} className="text-center position-relative z-index-2 px-2">
                    <div className="fnp-timeline-node-circle mb-3 mx-auto"></div>
                    <h4 className="fnp-timeline-year text-white fw-bold mb-2">{m.year}</h4>
                    <p className="fnp-timeline-node-text text-white-50 small mb-0 px-1">{m.text}</p>
                  </Col>
                ))}
              </Row>
            </div>
          </Container>
        </div>
      </div>

      {/* 👥 Meet the Team (Grid with Overlay Text) */}
      <div className="fnp-leadership-grid-section py-5 bg-white">
        <Container>
          <h3 className="text-center fnp-olive-heading mb-3">Meet the Team</h3>
          <p className="text-center text-muted mb-5 mx-auto" style={{ maxWidth: "600px" }}>
            Meet the team crafting unforgettable celebrations with passion and purpose!
          </p>
          <Row className="gy-4 justify-content-center">
            {team.map((t, idx) => (
              <Col xs={12} sm={6} md={3} key={idx}>
                <div className="fnp-leader-card position-relative overflow-hidden rounded-3 shadow-sm">
                  <img 
                    src={t.img} 
                    alt={t.name} 
                    className="w-100 d-block object-fit-cover" 
                    style={{ height: "320px" }}
                  />
                  {/* Dark Gradient Overlay with Text */}
                  <div className="fnp-leader-info-overlay p-3 d-flex flex-column justify-content-end">
                    <h5 className="text-white fw-bold mb-1">{t.name}</h5>
                    <p className="text-white-50 small mb-0">{t.role}</p>
                  </div>
                </div>
              </Col>
            ))}
          </Row>
        </Container>
      </div>

      {/* 📊 Green Stats Counter Bar */}
      <div className="fnp-green-stats-bar py-5">
        <Container>
          <Row className="text-center gy-4 gy-md-0">
            <Col xs={12} md={3} className="fnp-stats-col">
              <h3 className="fnp-stats-num-white text-white mb-2">12 M+</h3>
              <p className="fnp-stats-label-gold mb-0">deliveries worldwide</p>
            </Col>
            <Col xs={12} md={3} className="fnp-stats-col">
              <h3 className="fnp-stats-num-white text-white mb-2">400+</h3>
              <p className="fnp-stats-label-gold mb-0">stores across India</p>
            </Col>
            <Col xs={12} md={3} className="fnp-stats-col">
              <h3 className="fnp-stats-num-white text-white mb-2">100+</h3>
              <p className="fnp-stats-label-gold mb-0">countries served</p>
            </Col>
            <Col xs={12} md={3} className="fnp-stats-col">
              <h3 className="fnp-stats-num-white text-white mb-2">100%</h3>
              <p className="fnp-stats-label-gold mb-0">smiles delivered</p>
            </Col>
          </Row>
        </Container>
      </div>
    </div>
  );
};

export default AboutPage;
