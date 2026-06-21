import React, { useState, useEffect } from "react";
import { Container, Row, Col } from "react-bootstrap";
import { API_ENDPOINTS, ASSET_URL } from "../../config/apiEndpoints";
import "./About.css";

const AboutPage = () => {
  const [sections, setSections] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
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
  const whySection = sections.find(s => s.section_title?.toLowerCase().includes("choose") || (s.section_type === "mission" && s.id !== missionSection?.id));

  const getImageUrl = (sec, defaultUrl) => {
    if (!sec || !sec.image_path) return defaultUrl;
    if (sec.image_path.startsWith("http")) return sec.image_path;
    return `${ASSET_URL}${sec.image_path}`;
  };

  // Static timeline milestones matching FNP's timeline format
  const milestones = [
    { year: "2008", text: "Beginning of the first organised print outlet, fueled by passion." },
    { year: "2012", text: "India’s first online custom merchandising store that redefined gifting." },
    { year: "2016", text: "Launch of custom corporate gift packs, adding joy to every occasion." },
    { year: "2020", text: "Printmont goes global - making every custom print celebration accessible." },
    { year: "2023", text: "400+ corporate clients and millions of custom packages delivered." },
    { year: "2024", text: "Launch of Exclusive Luxury Box Collection & Same-Day Custom Delivery." }
  ];

  // Leadership team data based on FNP's team profile format
  const team = [
    { name: "Vikaas Gutgutia", role: "Founder & MD", img: "/team-vikaas.jpg" },
    { name: "Meeta Gutgutia", role: "Director & Creative Head", img: "/team-meeta.jpg" },
    { name: "Pawan Gadia", role: "Global CEO & Director", img: "/team-pawan.jpg" },
    { name: "Saurav Singh", role: "Chief Technology Officer", img: "/team-saurav.jpg" }
  ];

  return (
    <div className="about-fnp-page bg-white">
      {/* 🌸 FNP Style Introduction Banner */}
      <div className="fnp-intro-banner py-5 px-3 border-bottom">
        <Container>
          <Row className="align-items-center">
            <Col lg={7} className="pe-lg-5">
              <h2 className="fnp-banner-title mb-4">
                A quest for the perfect print led to a gifting revolution.
              </h2>
              <p className="fnp-banner-desc text-muted mb-0">
                {heroSection?.section_content || 
                  "From a humble beginning in 2008 to becoming India’s leading one-stop destination for personalized prints and celebrations, our journey has been about a lot more than merchandise—it's about crafting memories, spreading joy, and touching countless hearts."}
              </p>
            </Col>
            <Col lg={5} className="mt-4 mt-lg-0 text-center">
              <div className="fnp-hero-img-container">
                <img 
                  src={getImageUrl(heroSection, "/Online-Shopping-1.png")} 
                  alt="Printmont Gifting Revolution" 
                  className="fnp-hero-image img-fluid rounded"
                />
              </div>
            </Col>
          </Row>
        </Container>
      </div>

      {/* 📊 FNP Style Counter / Stats Bar */}
      <div className="fnp-stats-bar py-4 bg-light border-bottom">
        <Container>
          <Row className="text-center gy-3">
            <Col xs={6} md={3}>
              <h3 className="fnp-stat-num mb-1">12 M+</h3>
              <p className="fnp-stat-label text-muted mb-0">deliveries worldwide</p>
            </Col>
            <Col xs={6} md={3}>
              <h3 className="fnp-stat-num mb-1">400+</h3>
              <p className="fnp-stat-label text-muted mb-0">corporate clients</p>
            </Col>
            <Col xs={6} md={3}>
              <h3 className="fnp-stat-num mb-1">100+</h3>
              <p className="fnp-stat-label text-muted mb-0">cities served</p>
            </Col>
            <Col xs={6} md={3}>
              <h3 className="fnp-stat-num mb-1">100%</h3>
              <p className="fnp-stat-label text-muted mb-0">smiles delivered</p>
            </Col>
          </Row>
        </Container>
      </div>

      {/* 🎯 Vision & Mission Grid */}
      <div className="fnp-vision-mission py-5 border-bottom">
        <Container>
          <Row className="gy-4">
            <Col md={6} className="border-end-md">
              <div className="px-lg-4 text-center text-md-start">
                <h4 className="fnp-section-heading mb-3">Vision</h4>
                <p className="fnp-section-content text-muted mb-0">
                  {visionSection?.section_content || 
                    "Be the most trusted gifting and printing brand to celebrate the joy of giving and brand identity worldwide."}
                </p>
              </div>
            </Col>
            <Col md={6}>
              <div className="px-lg-4 text-center text-md-start">
                <h4 className="fnp-section-heading mb-3">Mission</h4>
                <p className="fnp-section-content text-muted mb-0">
                  {missionSection?.section_content || 
                    "Wow every customer every time, through premium products, customized merchandise, value for money driven by innovation, next-gen print technology, and a people-first approach."}
                </p>
              </div>
            </Col>
          </Row>
        </Container>
      </div>

      {/* 📈 Timeline Milestones: Rooted in Love, Growing With You */}
      <div className="fnp-timeline-section py-5 bg-white border-bottom">
        <Container>
          <h3 className="text-center fnp-section-title mb-5">Rooted in Love, Growing With You</h3>
          <div className="fnp-timeline">
            <Row className="gy-4 position-relative">
              <div className="fnp-timeline-line"></div>
              {milestones.map((m, idx) => (
                <Col md={4} key={idx} className="position-relative text-center px-3 z-index-2">
                  <div className="fnp-timeline-node mb-3">{m.year}</div>
                  <p className="fnp-timeline-text text-muted small">{m.text}</p>
                </Col>
              ))}
            </Row>
          </div>
        </Container>
      </div>

      {/* 👥 Leadership / Team Section */}
      <div className="fnp-team-section py-5 bg-white">
        <Container>
          <h3 className="text-center fnp-section-title mb-3">Meet the Team</h3>
          <p className="text-center text-muted mb-5 mx-auto" style={{ maxWidth: "600px" }}>
            Meet the leaders crafting unforgettable celebrations and printing solutions with passion and purpose!
          </p>
          <Row className="justify-content-center gy-4">
            {team.map((t, idx) => (
              <Col xs={6} md={3} key={idx} className="text-center">
                <div className="fnp-member-avatar-wrap mb-3 mx-auto">
                  <div className="fnp-member-avatar bg-light rounded-circle d-flex align-items-center justify-content-center border">
                    {/* Fallback to user icon if leadership images aren't present */}
                    <span className="fs-1 text-secondary fw-bold">{t.name.split(" ")[0][0]}</span>
                  </div>
                </div>
                <h6 className="fnp-member-name fw-bold mb-1">{t.name}</h6>
                <p className="fnp-member-role text-muted small mb-0">{t.role}</p>
              </Col>
            ))}
          </Row>
        </Container>
      </div>
    </div>
  );
};

export default AboutPage;
