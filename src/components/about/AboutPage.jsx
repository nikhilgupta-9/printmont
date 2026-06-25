import React, { useState, useEffect } from "react";
import { Container, Row, Col } from "react-bootstrap";
import Slider from "react-slick";
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

  // Timeline milestones matching FNP's timeline format but with Printmont details
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

  // Accolades list customized for Printmont
  const accolades = [
    "2025 Pitch\nTop 50 Brands India",
    "Future of Workplace &\nLeadership Award",
    "Top 100 Franchise\nOpportunities",
    "National Excellence\nin Digital Printing",
    "Best Gifting e-Retailer\nof the Year",
    "Corporate Merchandise\nLeader of the Year"
  ];

  // Slick slider settings for team carousel
  const teamSliderSettings = {
    dots: true,
    infinite: true,
    speed: 500,
    slidesToShow: 4,
    slidesToScroll: 1,
    arrows: true,
    responsive: [
      {
        breakpoint: 1024,
        settings: {
          slidesToShow: 3,
          slidesToScroll: 1,
          infinite: true,
          dots: true
        }
      },
      {
        breakpoint: 768,
        settings: {
          slidesToShow: 2,
          slidesToScroll: 1
        }
      },
      {
        breakpoint: 480,
        settings: {
          slidesToShow: 1,
          slidesToScroll: 1
        }
      }
    ]
  };

  // Slick slider settings for timeline carousel (matches 2nd reference image with arrows)
  const timelineSliderSettings = {
    dots: false,
    infinite: false,
    speed: 500,
    slidesToShow: 5,
    slidesToScroll: 1,
    arrows: true,
    responsive: [
      {
        breakpoint: 1024,
        settings: {
          slidesToShow: 4,
          slidesToScroll: 1
        }
      },
      {
        breakpoint: 768,
        settings: {
          slidesToShow: 2,
          slidesToScroll: 1
        }
      },
      {
        breakpoint: 480,
        settings: {
          slidesToShow: 1,
          slidesToScroll: 1
        }
      }
    ]
  };

  return (
    <div className="about-fnp-page bg-white">
      {/* 🌸 Hero Banner with Overlay - Set to full height of screen */}
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
          <h2 className="fnp-theme-heading mb-4">How it all started</h2>
          <p className="fnp-started-desc text-muted mb-0">
            {heroSection?.section_content || 
              "A quest for the perfect print led to a gifting revolution, thanks to our founders. From a humble beginning in 2008 to becoming India’s leading one-stop destination for personalized prints and corporate celebrations, our journey has been about a lot more than merchandising—it's about crafting memories, spreading joy, and touching countless hearts."}
          </p>
        </Container>
      </div>

      {/* 📦 Ice-Blue Info Section: Delivering Love to 100+ Countries */}
      <div className="fnp-beige-section py-5">
        <Container>
          <div className="fnp-beige-card rounded-3 overflow-hidden">
            {/* Split layout: Image fills left completely, text padded on right */}
            <Row className="align-items-stretch g-0">
              <Col md={6}>
                <div className="fnp-beige-img-wrap h-100 position-relative">
                  <img 
                    src="/about-delivering-love.png" 
                    alt="Delivering Love Worldwide" 
                    className="w-100 h-100 object-fit-cover d-block"
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
              <Col md={6} className="d-flex align-items-center">
                <div className="p-4 p-md-5 ps-md-5">
                  <h3 className="fnp-theme-heading mb-3">Delivering Love to 100+ Countries</h3>
                  <p className="fnp-started-desc text-muted mb-0">
                    Our thoughtfully curated gifts travel the world to make celebrations special, even when one can’t be there in person.
                  </p>
                </div>
              </Col>
            </Row>
          </div>
        </Container>
      </div>

      {/* ⭐ The Perfect Surprise Section */}
      <div className="fnp-perfect-surprise py-5 bg-white">
        <Container className="text-center">
          <h2 className="fnp-theme-heading mb-5">The Perfect Surprise</h2>
          <Row className="gy-4">
            <Col md={4}>
              <div className="fnp-surprise-card">
                {/* Gift Box Icon */}
                <div className="fnp-surprise-icon-wrap mb-3 mx-auto">
                  <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                    <path d="M12 24h40v32H12V24z" stroke="rgb(11, 83, 161)" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M8 16h48v8H8v-8z" fill="rgba(11, 83, 161, 0.15)" stroke="rgb(11, 83, 161)" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M32 16v40M32 16c-3-6-10-6-10 0s7 6 10 0M32 16c3-6 10-6 10 0s-7 6-10 0" stroke="rgb(11, 83, 161)" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <circle cx="16" cy="40" r="2" fill="rgb(11, 83, 161)"/>
                    <circle cx="48" cy="34" r="2" fill="rgb(11, 83, 161)"/>
                    <circle cx="44" cy="48" r="2" fill="rgb(11, 83, 161)"/>
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
                    <rect x="12" y="14" width="40" height="40" rx="4" stroke="rgb(11, 83, 161)" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M12 26h40M22 10v8M42 10v8" stroke="rgb(11, 83, 161)" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <circle cx="22" cy="36" r="2" fill="rgb(11, 83, 161)"/>
                    <circle cx="32" cy="36" r="2" fill="rgb(11, 83, 161)"/>
                    <circle cx="42" cy="36" r="2" fill="rgb(11, 83, 161)"/>
                    <circle cx="22" cy="44" r="2" fill="rgb(11, 83, 161)"/>
                    <path d="M30 44l3 3 6-6" stroke="rgb(11, 83, 161)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
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
                    <path d="M22 34c-6-1-10-6-10-12s5-11 11-11 10 5 10 11c0 3-1 6-3 8" stroke="rgb(11, 83, 161)" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M42 34c6-1 10-6 10-12s-5-11-11-11-10 5-10 11c0 3 1 6 3 8" fill="rgba(11, 83, 161, 0.15)" stroke="rgb(11, 83, 161)" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M22 34c-1 3-3 8-1 12M42 34c1 3 3 8 1 12M32 46c0-4 1-9-1-13" stroke="rgb(11, 83, 161)" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
                <h5 className="fnp-surprise-title fw-bold">Personalised for them</h5>
              </div>
            </Col>
          </Row>
        </Container>
      </div>

      {/* 🏆 Accolades & Milestones Section (Laurel Wreaths Grid) */}
      <div className="fnp-accolades-section py-5 bg-white">
        <Container>
          <h2 className="fnp-theme-heading text-center mb-5">Accolades & Milestones</h2>
          <Row className="gy-4 justify-content-center">
            {accolades.map((acc, idx) => (
              <Col xs={12} sm={6} md={4} key={idx} className="d-flex align-items-center justify-content-center">
                <div className="fnp-laurel-wrapper d-flex align-items-center justify-content-center px-3">
                  {/* Left Laurel Branch with Thick Leaves */}
                  <div className="fnp-laurel-left me-3">
                    <svg width="45" height="90" viewBox="0 0 40 85" fill="none">
                      {/* Curved Stem */}
                      <path d="M32,78 C20,70 11,52 11,35 C11,20 16,5 17,4" stroke="#ffcb2f" strokeWidth="2.5" strokeLinecap="round" fill="none"/>
                      
                      {/* Pair 1 (Top) */}
                      <g transform="translate(17, 4) rotate(-10)">
                        <path d="M0,0 C-4,-1 -6,-7 0,-10 C6,-7 4,-1 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(17, 4) rotate(40)">
                        <path d="M0,0 C-4,-1 -6,-7 0,-10 C6,-7 4,-1 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 2 */}
                      <g transform="translate(14, 13) rotate(-25)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(16, 14) rotate(35)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 3 */}
                      <g transform="translate(12, 23) rotate(-35)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(14, 24) rotate(25)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 4 */}
                      <g transform="translate(11, 33) rotate(-45)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(13, 34) rotate(15)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 5 */}
                      <g transform="translate(11, 44) rotate(-55)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(13, 45) rotate(5)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 6 */}
                      <g transform="translate(13, 54) rotate(-65)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(15, 55) rotate(-5)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 7 */}
                      <g transform="translate(17, 64) rotate(-75)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(19, 65) rotate(-15)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 8 */}
                      <g transform="translate(23, 73) rotate(-85)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(25, 74) rotate(-25)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                    </svg>
                  </div>
                  
                  {/* Achievement Text */}
                  <div className="fnp-laurel-text text-center fw-semibold text-dark fs-6" style={{ whiteSpace: "pre-line", minWidth: "160px" }}>
                    {acc}
                  </div>

                  {/* Right Laurel Branch with Thick Leaves (Mirrored) */}
                  <div className="fnp-laurel-right ms-3">
                    <svg width="45" height="90" viewBox="0 0 40 85" fill="none" style={{ transform: "scaleX(-1)" }}>
                      {/* Curved Stem */}
                      <path d="M32,78 C20,70 11,52 11,35 C11,20 16,5 17,4" stroke="#ffcb2f" strokeWidth="2.5" strokeLinecap="round" fill="none"/>
                      
                      {/* Pair 1 (Top) */}
                      <g transform="translate(17, 4) rotate(-10)">
                        <path d="M0,0 C-4,-1 -6,-7 0,-10 C6,-7 4,-1 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(17, 4) rotate(40)">
                        <path d="M0,0 C-4,-1 -6,-7 0,-10 C6,-7 4,-1 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 2 */}
                      <g transform="translate(14, 13) rotate(-25)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(16, 14) rotate(35)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 3 */}
                      <g transform="translate(12, 23) rotate(-35)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(14, 24) rotate(25)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 4 */}
                      <g transform="translate(11, 33) rotate(-45)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(13, 34) rotate(15)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 5 */}
                      <g transform="translate(11, 44) rotate(-55)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(13, 45) rotate(5)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 6 */}
                      <g transform="translate(13, 54) rotate(-65)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(15, 55) rotate(-5)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 7 */}
                      <g transform="translate(17, 64) rotate(-75)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(19, 65) rotate(-15)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>

                      {/* Pair 8 */}
                      <g transform="translate(23, 73) rotate(-85)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                      <g transform="translate(25, 74) rotate(-25)">
                        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#ffcb2f"/>
                      </g>
                    </svg>
                  </div>
                </div>
              </Col>
            ))}
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
                <h3 className="fnp-theme-heading mb-3">Vision</h3>
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
                <h3 className="fnp-theme-heading mb-3">Mission</h3>
                <p className="fnp-started-desc text-muted mb-0">
                  {missionSection?.section_content || 
                    "Wow every customer every time, through premium products, services, value for money driven by innovation, technology and people-first approach."}
                </p>
              </div>
            </Col>
          </Row>
        </Container>
      </div>

      {/* 📅 Timeline: Rooted in Love, Growing With You (Dark Banner Timeline Slider) */}
      <div 
        className="fnp-dark-timeline-section py-5"
        style={{ backgroundImage: `url('/about-hero-banner.png')` }}
      >
        <div className="fnp-timeline-overlay py-5">
          <Container>
            <h3 className="text-center text-white mb-5 fw-bold fnp-timeline-heading">Rooted in Love, Growing With You</h3>
            <div className="fnp-timeline-wrapper position-relative px-4">
              <div className="fnp-timeline-horizontal-line d-none d-md-block"></div>
              <Slider {...timelineSliderSettings} className="fnp-timeline-slider">
                {milestones.map((m, idx) => (
                  <div key={idx} className="text-center position-relative z-index-2 px-2">
                    <div className="fnp-timeline-node-circle mb-3 mx-auto"></div>
                    <h4 className="fnp-timeline-year text-white fw-bold mb-2">{m.year}</h4>
                    <p className="fnp-timeline-node-text text-white-50 small mb-0 px-1">{m.text}</p>
                  </div>
                ))}
              </Slider>
            </div>
          </Container>
        </div>
      </div>

      {/* 👥 Meet the Team (Slider Carousel with Overlay Text) */}
      <div className="fnp-leadership-grid-section py-5 bg-white">
        <Container>
          <h3 className="text-center fnp-theme-heading mb-3">Meet the Team</h3>
          <p className="text-center text-muted mb-5 mx-auto" style={{ maxWidth: "600px" }}>
            Meet the team crafting unforgettable celebrations with passion and purpose!
          </p>
          <div className="fnp-team-slider-container px-2">
            <Slider {...teamSliderSettings}>
              {team.map((t, idx) => (
                <div key={idx} className="px-2">
                  <div className="fnp-leader-card position-relative overflow-hidden rounded-3 shadow-sm mb-3">
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
                </div>
              ))}
            </Slider>
          </div>
        </Container>
      </div>

      {/* 📊 Theme-Blue Stats Counter Bar */}
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
