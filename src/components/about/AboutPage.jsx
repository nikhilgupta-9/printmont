import React, { useState, useEffect } from "react";
import { Container, Row, Col } from "react-bootstrap";
import {
  FaTruckFast,
  FaStore,
  FaGlobe,
  FaFaceSmile,
  FaShieldHalved,
  FaPlaneDeparture,
  FaBoxOpen,
  FaCalendarDays,
  FaPenNib
} from "react-icons/fa6";
import { API_ENDPOINTS, ASSET_URL, resolveImageUrl, assetUrl } from "../../config/apiEndpoints";
import BannerGrid from "../home/banners/BannerGrid";
import useHomeBanners from "../home/hooks/useHomeBanners";
import "./About.css";

/** Helper to check if string contains HTML tags */
const isHtml = (value) => /<\/?[a-z][\s\S]*>/i.test(String(value || ""));

const Text = ({ value, className = "" }) =>
  !value ? null : isHtml(value) ? (
    <div className={className} dangerouslySetInnerHTML={{ __html: value }} />
  ) : (
    <p className={className}>{value}</p>
  );

const imageFor = (section) => {
  if (!section?.image_path) return null;
  return section.image_path.startsWith("http")
    ? section.image_path
    : resolveImageUrl(section.image_path);
};

const teamImage = (member) => {
  if (!member?.image_path) return null;
  return member.image_path.startsWith("http")
    ? member.image_path
    : assetUrl(String(member.image_path).replace(/^\//, ""));
};

/** Golden Laurel SVG branch component for Accolades */
const Laurel = ({ flip = false }) => (
  <svg
    className={`laurel__branch${flip ? " laurel__branch--flip" : ""}`}
    viewBox="0 0 40 85"
    fill="none"
    aria-hidden="true"
  >
    <path
      d="M32,78 C20,70 11,52 11,35 C11,20 16,5 17,4"
      stroke="#d4af37"
      strokeWidth="2.5"
      strokeLinecap="round"
    />
    {[
      [17, 4, -10],
      [17, 4, 40],
      [14, 13, -25],
      [16, 14, 35],
      [12, 23, -35],
      [14, 24, 25],
      [12, 33, -35],
      [14, 34, 25],
      [14, 43, -35],
      [16, 44, 25],
      [18, 53, -35],
      [20, 54, 25],
    ].map(([x, y, r], i) => (
      <g key={i} transform={`translate(${x}, ${y}) rotate(${r})`}>
        <path d="M0,0 C-5,-2 -7,-9 0,-12 C7,-9 5,-2 0,0 Z" fill="#d4af37" />
      </g>
    ))}
  </svg>
);

const AboutPage = () => {
  const [sections, setSections] = useState([]);
  const [team, setTeam] = useState([]);
  const [loading, setLoading] = useState(true);

  // Fetch banner carousels from API
  const { banners: aboutHeroBanners } = useHomeBanners(API_ENDPOINTS.BANNERS, "about_hero");
  const { banners: aboutMidBanners1 } = useHomeBanners(API_ENDPOINTS.BANNERS, "about_mid_1");
  const { banners: aboutMidBanners2 } = useHomeBanners(API_ENDPOINTS.BANNERS, "about_mid_2");
  const { banners: accoladeBanners } = useHomeBanners(API_ENDPOINTS.BANNERS, "about_accolades");

  useEffect(() => {
    window.scrollTo(0, 0);
    let cancelled = false;

    const fetchAboutData = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.ABOUT);
        if (!response.ok) throw new Error("Network response was not ok");
        const json = await response.json();
        if (cancelled) return;

        if (json?.success) {
          setSections(
            Array.isArray(json.data)
              ? json.data.filter((s) => Number(s.is_active) !== 0)
              : []
          );
          setTeam(
            Array.isArray(json.team)
              ? json.team.filter((t) => Number(t.is_active) !== 0)
              : []
          );
        }
      } catch (error) {
        console.error("Error fetching About data:", error);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    fetchAboutData();
    return () => {
      cancelled = true;
    };
  }, []);

  const one = (type) => sections.find((s) => s.section_type === type) || null;
  const many = (type) => sections.filter((s) => s.section_type === type);

  const hero = one("hero");
  const story = one("story");
  const slogan = one("slogan");
  const highlight = one("highlight");
  const story2 = one("story_2");
  const vision = one("history");
  const mission = one("mission");
  const features = many("feature");
  const normalText = one("normal_text");
  const accolade = one("accolade");
  const stats = many("stat");

  // Default accolades matching the wireframe
  const defaultAccolades = [
    { year: "2025 Pitch", title: "Top 50 Brands", sub: "India" },
    { year: "", title: "Future of Workplace &", sub: "Leadership Award" },
    { year: "", title: "Top 100 Franchise", sub: "Opportunities" },
    { year: "", title: "National Excellence in", sub: "Digital Printing" },
    { year: "", title: "Best Gifting e-Retailer", sub: "of the Year" },
    { year: "", title: "Corporate Merchandise", sub: "Leader of the Year" },
  ];

  // Fallback team members if API returns empty
  const defaultTeam = [
    {
      id: 1,
      name: "Sarah Johnson",
      position: "CEO & Founder",
      image_path: "/team-vikaas.png",
    },
    {
      id: 2,
      name: "Michael Chen",
      position: "Creative Director",
      image_path: "/team-saurav.png",
    },
    {
      id: 3,
      name: "Emily Rodriguez",
      position: "Print Production Manager",
      image_path: "/team-meeta.png",
    },
    {
      id: 4,
      name: "David Thompson",
      position: "Sales Director",
      image_path: "/team-pawan.png",
    },
  ];

  const displayTeam = team.length > 0 ? team : defaultTeam;

  // Feature Card Block renderer (Worldwide delivery / Global network)
  const renderFeatureCard = (item) => (
    <div className="about-feature-card-wrapper h-100 rounded-4 overflow-hidden border">
      <div className="row g-0 align-items-center h-100">
        <div className="col-12 col-md-5">
          <div className="about-feature-card-img position-relative text-center p-3">
            <img
              src={imageFor(item) || "/banners/worldwide_delivery.jpg"}
              alt={item?.section_title || "Delivering Love"}
              className="img-fluid rounded-3"
              style={{ maxHeight: "260px", width: "100%", objectFit: "cover" }}
              onError={(e) => {
                e.currentTarget.src = "/banners/worldwide_delivery.jpg";
              }}
            />
            <div className="about-delivery-pill-badge">
              <FaGlobe className="me-1" />
              <span>DELIVERY WORLDWIDE</span>
            </div>
          </div>
        </div>
        <div className="col-12 col-md-7">
          <div className="p-3 p-md-4">
            <div className="mb-2">
              <span className="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1 rounded-pill">
                <FaPlaneDeparture className="me-1" /> Global Fulfillment Network
              </span>
            </div>
            <h4 className="fw-bold text-dark mb-2 fs-5">
              {item?.section_title || "Delivering Love to 100+ Countries"}
            </h4>
            <p className="text-secondary small mb-3 lh-base">
              {item?.section_content ||
                "From bulk corporate orders to a single personalised gift, we print, pack and deliver worldwide with a zero-defect promise."}
            </p>
            <div className="d-flex flex-wrap gap-1.5">
              <span className="about-chip-badge">
                <FaGlobe className="text-primary me-1" size={11} /> 100+ Countries
              </span>
              <span className="about-chip-badge">
                <FaTruckFast className="text-primary me-1" size={11} /> Express Delivery
              </span>
              <span className="about-chip-badge">
                <FaShieldHalved className="text-primary me-1" size={11} /> Zero-Defect Promise
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  // Banner slot renderer with carousel / static fallback
  const renderBannerSlot = (banners, fallbackImg, altText = "Banner") => {
    if (banners && banners.length > 0) {
      return <BannerGrid banners={banners} columns={1} mobileColumns={1} />;
    }
    return (
      <div className="about-banner-placeholder rounded-4 overflow-hidden shadow-sm h-100">
        <img
          src={fallbackImg}
          alt={altText}
          className="img-fluid w-100 h-100"
          style={{ minHeight: "220px", maxHeight: "320px", objectFit: "cover" }}
          onError={(e) => {
            e.currentTarget.src = "/banners/banner-30.jpg";
          }}
        />
      </div>
    );
  };

  // Extra dynamic rows added by admin
  const extraRows = sections.filter(
    (s) => s.section_type === "custom" || (!["hero", "story", "slogan", "highlight", "story_2", "values", "history", "mission", "feature", "normal_text", "accolade", "stat"].includes(s.section_type))
  );

  // Content rows between Hero and Value Pillars matching the wireframe
  const contentRows = [
    {
      id: story?.id || "def_1",
      section_type: "story",
      section_title: story?.section_title || "How it all started",
      section_content: story?.section_content || "What began as a single print outlet fuelled by passion has grown into a nationwide network delivering custom prints, corporate gifts and promotional merchandise across India and beyond.",
      extra: story?.extra || "left",
      image_path: story?.image_path || null,
      useFeatureCard: !story?.image_path,
    },
    {
      id: slogan?.id || "def_2",
      section_type: "slogan",
      section_title: slogan?.section_title || "Highlights Slogn Text.",
      section_content: slogan?.section_content || "What began as a single print outlet fuelled by passion has grown into a nationwide network delivering custom prints, corporate gifts and promotional merchandise across India and beyond.",
      extra: slogan?.extra || "right",
      image_path: slogan?.image_path || null,
      useCarousel1: true,
    },
    {
      id: highlight?.id || "def_3",
      section_type: "highlight",
      section_title: highlight?.section_title || "Delivering Love to 100+ Countries",
      section_content: highlight?.section_content || "From bulk corporate orders to a single personalised gift, we print, pack and deliver worldwide with a zero-defect promise.",
      extra: highlight?.extra || "right",
      image_path: highlight?.image_path || null,
      useFeatureCard: true,
    },
    {
      id: story2?.id || "def_4",
      section_type: "story_2",
      section_title: story2?.section_title || "How We Craft & Deliver",
      section_content: story2?.section_content || "Every item is carefully printed and packed by our team of print artisans using top-grade equipment, ensuring vivid colors and durable finishes.",
      extra: story2?.extra || "right",
      image_path: story2?.image_path || null,
      useCarousel2: !story2?.image_path,
    },
    ...extraRows,
  ];

  // Helper to render right-side media for a split row
  const renderRowMedia = (item, idx) => {
    if (item.section_type === "highlight" || item.useFeatureCard) {
      return renderFeatureCard(item);
    }
    if (item.image_path) {
      return renderBannerSlot([], imageFor(item), item.section_title || "Section Banner");
    }
    if (item.useCarousel1 || idx % 2 === 1) {
      return renderBannerSlot(aboutMidBanners1, "/banners/banner12.jpg", "Mid Banner Carousel");
    }
    return renderBannerSlot(aboutMidBanners2, "/banners/banner13.jpg", "Secondary Banner Carousel");
  };

  if (loading) {
    return (
      <div className="about-fnp-page py-5 bg-white">
        <Container>
          <div className="shimmer-box rounded-4 mb-4" style={{ height: "360px" }} />
          <Row className="g-4 mb-4">
            <Col md={6}>
              <div className="shimmer-box rounded-4" style={{ height: "240px" }} />
            </Col>
            <Col md={6}>
              <div className="shimmer-box rounded-4" style={{ height: "240px" }} />
            </Col>
          </Row>
        </Container>
      </div>
    );
  }

  return (
    <div className="about-wireframe-page bg-white">
      {/* ========================================================
          1. TOP BANNER AND BANNER CAROUSEL IMAGE
          ======================================================== */}
      <section className="about-hero-section">
        {aboutHeroBanners.length > 0 ? (
          <BannerGrid banners={aboutHeroBanners} columns={1} mobileColumns={1} />
        ) : hero ? (
          <div
            className="about-hero-banner-box position-relative overflow-hidden"
            style={{
              backgroundImage: `url('${imageFor(hero) || "/about-hero-banner.png"}')`,
            }}
          >
            <div className="about-hero-dark-overlay" />
            <Container className="position-relative z-2 text-center text-white py-5">
              <span className="badge bg-warning text-dark text-uppercase px-3 py-1.5 fw-bold mb-3">
                About Printmont
              </span>
              <h1 className="fw-bold display-5 mb-3">{hero.section_title}</h1>
              <Text
                value={hero.section_content}
                className="fs-6 text-white-50 mx-auto"
                style={{ maxWidth: "700px" }}
              />
            </Container>
          </div>
        ) : (
          <div className="about-banner-fullwidth">
            <img
              src="/about-hero-banner.png"
              alt="About Printmont Banner"
              className="w-100 img-fluid"
              style={{ maxHeight: "420px", objectFit: "cover" }}
              onError={(e) => {
                e.currentTarget.src = "/banners/banner11.jpg";
              }}
            />
          </div>
        )}
      </section>



      {/* ========================================================
          DESKTOP VIEW: SPLIT ROWS (Matching PDF Page 2 Wireframe)
          ======================================================== */}
      <div className="d-none d-lg-block">
        <Container className="py-5">
          {contentRows.map((item, idx) => {
            const isFlipped = item.extra === "left";
            return (
              <Row key={item.id || idx} className="align-items-center g-4 mb-5">
                <Col
                  lg={6}
                  className={`d-flex flex-column justify-content-center ${
                    isFlipped ? "order-lg-2 ps-lg-4" : "order-lg-1 pe-lg-4"
                  }`}
                >
                  <div className="about-text-column-block">
                    <h2 className="about-main-heading mb-3">
                      {item.section_title || "Printmont Story"}
                    </h2>
                    <div className="about-slogan-paragraph">
                      <Text value={item.section_content} />
                    </div>
                  </div>
                </Col>
                <Col lg={6} className={isFlipped ? "order-lg-1" : "order-lg-2"}>
                  {renderRowMedia(item, idx)}
                </Col>
              </Row>
            );
          })}
        </Container>
      </div>


      {/* ========================================================
          MOBILE VIEW: VERTICAL STACK (Matching PDF Page 1 Wireframe)
          ======================================================== */}
      <div className="d-block d-lg-none">
        <Container className="py-4 px-3 px-sm-4">
          {contentRows.map((item, idx) => (
            <React.Fragment key={item.id || idx}>
              {/* Text Block */}
              <div className="about-mobile-text-section mb-3">
                <h4 className="about-main-heading mb-2">
                  {item.section_title || "Printmont Story"}
                </h4>
                <div className="about-slogan-paragraph">
                  <Text value={item.section_content} />
                </div>
              </div>

              {/* Media Block (Feature Card / Banner / Carousel) */}
              <div className="mb-4">
                {renderRowMedia(item, idx)}
              </div>
            </React.Fragment>
          ))}
        </Container>
      </div>


      {/* ========================================================
          VALUE PILLARS: "The Perfect Surprise" (3 Column Icons)
          ======================================================== */}
      <section className="about-pillars-section py-4 py-md-5 bg-light-subtle border-top border-bottom">
        <Container className="px-3 px-sm-4">
          <h3 className="about-main-heading text-center mb-4 fs-3">The Perfect Surprise</h3>
          <Row className="g-3 g-md-4 justify-content-center text-center">
            {features.length > 0 ? (
              features.map((f, idx) => (
                <Col xs={12} md={4} key={f.id || idx}>
                  <div className="about-pillar-card p-3 p-md-4 h-100 bg-white rounded-3 border">
                    <div className="pillar-icon-box mx-auto mb-3">
                      {imageFor(f) ? (
                        <img
                          src={imageFor(f)}
                          alt={f.section_title}
                          style={{ maxWidth: "48px", maxHeight: "48px" }}
                        />
                      ) : idx === 0 ? (
                        <FaBoxOpen size={28} className="text-primary" />
                      ) : idx === 1 ? (
                        <FaCalendarDays size={28} className="text-primary" />
                      ) : (
                        <FaPenNib size={28} className="text-primary" />
                      )}
                    </div>
                    <h5 className="fw-bold text-dark mb-2 fs-6">{f.section_title}</h5>
                    <p className="text-muted small mb-0 lh-base">{f.section_content}</p>
                  </div>
                </Col>
              ))
            ) : (
              <>
                <Col xs={12} md={4}>
                  <div className="about-pillar-card p-3 p-md-4 h-100 bg-white rounded-3 border">
                    <div className="pillar-icon-box mx-auto mb-3">
                      <FaBoxOpen size={28} className="text-primary" />
                    </div>
                    <h5 className="fw-bold text-dark mb-2 fs-6">Thoughtful Gifts</h5>
                    <p className="text-muted small mb-0">Curated products chosen to suit every occasion and recipient.</p>
                  </div>
                </Col>
                <Col xs={12} md={4}>
                  <div className="about-pillar-card p-3 p-md-4 h-100 bg-white rounded-3 border">
                    <div className="pillar-icon-box mx-auto mb-3">
                      <FaCalendarDays size={28} className="text-primary" />
                    </div>
                    <h5 className="fw-bold text-dark mb-2 fs-6">Scheduled Delivery</h5>
                    <p className="text-muted small mb-0">Pick the date and we deliver on time, every time.</p>
                  </div>
                </Col>
                <Col xs={12} md={4}>
                  <div className="about-pillar-card p-3 p-md-4 h-100 bg-white rounded-3 border">
                    <div className="pillar-icon-box mx-auto mb-3">
                      <FaPenNib size={28} className="text-primary" />
                    </div>
                    <h5 className="fw-bold text-dark mb-2 fs-6">Personalised for them</h5>
                    <p className="text-muted small mb-0">Add names, logos and artwork to make each piece one of a kind.</p>
                  </div>
                </Col>
              </>
            )}
          </Row>
        </Container>
      </section>


      {/* ========================================================
          OUR VISION & OUR MISSION (2 Cards Grid with Images)
          ======================================================== */}
      <section className="about-vision-mission-section py-4 py-md-5">
        <Container className="px-3 px-sm-4">
          <Row className="g-4">
            {/* Our Vision */}
            <Col md={6}>
              <div className="about-vm-card h-100 rounded-3 overflow-hidden border bg-white shadow-sm">
                <div className="about-vm-image-wrap">
                  <img
                    src={imageFor(vision) || "/our-vission.jpg"}
                    alt="Our Vision"
                    className="w-100 img-fluid"
                    style={{ height: "200px", objectFit: "cover" }}
                    onError={(e) => {
                      e.currentTarget.src = "/about-vision-sunflowers.png";
                    }}
                  />
                </div>
                <div className="p-3 p-md-4">
                  <h4 className="fw-bold text-dark mb-2 fs-5">
                    {vision?.section_title || "Our Vision"}
                  </h4>
                  <p className="text-secondary small mb-0 lh-lg">
                    {vision?.section_content ||
                      "Founded in 2008 by printing industry veterans, Printmont started as a small local print shop with a big vision. What began with a single printing press and a commitment to quality has grown into a full-service printing company serving clients nationwide. Our journey has been marked by continuous innovation, expanding our services to include digital printing, large format printing, and custom packaging solutions."}
                  </p>
                </div>
              </div>
            </Col>

            {/* Our Mission */}
            <Col md={6}>
              <div className="about-vm-card h-100 rounded-3 overflow-hidden border bg-white shadow-sm">
                <div className="about-vm-image-wrap">
                  <img
                    src={imageFor(mission) || "/our-mission.jpg"}
                    alt="Our Mission"
                    className="w-100 img-fluid"
                    style={{ height: "200px", objectFit: "cover" }}
                    onError={(e) => {
                      e.currentTarget.src = "/about-mission-celebration.png";
                    }}
                  />
                </div>
                <div className="p-3 p-md-4">
                  <h4 className="fw-bold text-dark mb-2 fs-5">
                    {mission?.section_title || "Our Mission"}
                  </h4>
                  <p className="text-secondary small mb-0 lh-lg">
                    {mission?.section_content ||
                      "Our mission is to empower businesses and individuals with high-quality printing services that exceed expectations. We believe that great printing can transform ideas into powerful communications. Through cutting-edge technology, sustainable practices, and unparalleled customer service, we strive to be the preferred printing partner for clients across all industries."}
                  </p>
                </div>
              </div>
            </Col>
          </Row>
        </Container>
      </section>


      {/* ========================================================
          HIGHLIGHT TEXT (Normal Text Pages.) - RICH ARTICLE
          ======================================================== */}
      <section className="about-long-text-section py-4 py-md-5 bg-light border-top border-bottom">
        <Container className="px-3 px-sm-4">
          <div className="about-normal-text-box bg-white p-4 p-md-5 rounded-3 border">
            <h3 className="about-main-heading mb-4 fs-4">
              {normalText?.section_title || "Highlight Text (Normal Text Pages.)"}
            </h3>
            <div className="about-normal-text-body text-secondary lh-lg" style={{ fontSize: "14.5px" }}>
              {normalText?.section_content ? (
                <Text value={normalText.section_content} />
              ) : (
                <>
                  <p className="mb-3">
                    Founded in 2008 by printing industry veterans, Printmont started as a small local print shop with a big vision. What began with a single printing press and a commitment to quality has grown into a full-service printing company serving clients nationwide. Our journey has been marked by continuous innovation, expanding our services to include digital printing, large format printing, and custom packaging solutions.
                  </p>
                  <p className="mb-3">
                    Founded in 2008 by printing industry veterans, Printmont started as a small local print shop with a big vision. What began with a single printing press and a commitment to quality has grown into a full-service printing company serving clients nationwide. Our journey has been marked by continuous innovation, expanding our services to include digital printing, large format printing, and custom packaging solutions.
                  </p>
                  <p className="mb-0">
                    Founded in 2008 by printing industry veterans, Printmont started as a small local print shop with a big vision. What began with a single printing press and a commitment to quality has grown into a full-service printing company serving clients nationwide. Our journey has been marked by continuous innovation, expanding our services to include digital printing, large format printing, and custom packaging solutions.
                  </p>
                </>
              )}
            </div>
          </div>
        </Container>
      </section>


      {/* ========================================================
          ACCOLADES & MILESTONES (Golden Laurel Wreaths Grid)
          ======================================================== */}
      <section className="about-accolades-section py-4 py-md-5 bg-white">
        <Container className="px-3 px-sm-4">
          <div className="text-center mb-4 mb-md-5">
            <h3 className="about-main-heading mb-2 fs-3">
              {accolade?.section_title || "Accolades & Milestones"}
            </h3>
            <p className="text-muted small mx-auto" style={{ maxWidth: "600px" }}>
              {accolade?.section_content ||
                "Celebrating our industry recognition, brand excellence, and commitment to quality across India and globally."}
            </p>
          </div>

          {/* Accolades Laurel Badges Grid */}
          <Row className="g-3 g-md-4 justify-content-center">
            {defaultAccolades.map((acc, idx) => (
              <Col xs={6} md={4} key={idx}>
                <div className="about-laurel-card text-center p-3">
                  <div className="laurel-container position-relative mx-auto mb-2">
                    <Laurel />
                    <div className="laurel-award-content">
                      {acc.year && <span className="award-year d-block">{acc.year}</span>}
                      <span className="award-title d-block">{acc.title}</span>
                      {acc.sub && <span className="award-sub d-block">{acc.sub}</span>}
                    </div>
                    <Laurel flip />
                  </div>
                </div>
              </Col>
            ))}
          </Row>
        </Container>
      </section>


      {/* ========================================================
          THE MINDS BEHIND THE MAGIC (Team Members Grid)
          ======================================================== */}
      <section className="about-team-section py-4 py-md-5 bg-light border-top">
        <Container className="px-3 px-sm-4">
          <div className="text-center mb-4 mb-md-5">
            <h3 className="about-main-heading mb-2 fs-3">The Minds Behind the Magic</h3>
            <p className="text-muted small">
              Meet the team crafting unforgettable celebrations with passion and purpose!
            </p>
          </div>

          <Row className="g-3 g-md-4 justify-content-center">
            {displayTeam.map((member) => (
              <Col xs={6} sm={6} md={3} key={member.id}>
                <div className="about-team-card text-center p-3 bg-white rounded-3 border h-100">
                  <div className="about-team-avatar-box mx-auto mb-3">
                    {teamImage(member) ? (
                      <img
                        src={teamImage(member)}
                        alt={member.name}
                        className="about-team-img"
                        onError={(e) => {
                          e.currentTarget.style.display = "none";
                        }}
                      />
                    ) : (
                      <div className="team-avatar-fallback">
                        {String(member.name || "P")
                          .split(" ")
                          .map((n) => n[0])
                          .slice(0, 2)
                          .join("")}
                      </div>
                    )}
                  </div>
                  <h6 className="fw-bold text-dark mb-1 fs-6">{member.name}</h6>
                  <p className="text-muted small mb-0" style={{ fontSize: "12px" }}>
                    {member.position}
                  </p>
                </div>
              </Col>
            ))}
          </Row>
        </Container>
      </section>


      {/* ========================================================
          NUMBERS BACKING UP OUR PROPOSITION (Stats Bar at Bottom)
          ======================================================== */}
      <section className="about-stats-proposition-bar py-4 bg-white border-top">
        <Container className="px-3 px-sm-4">
          <div className="about-stats-inner-box p-3 p-md-4 rounded-3 border">
            <Row className="align-items-center text-center text-md-start g-3">
              <Col md={3}>
                <h6 className="fw-bold text-primary text-uppercase mb-0" style={{ fontSize: "13px", letterSpacing: "0.5px" }}>
                  Numbers Backing Up Our Proposition
                </h6>
              </Col>
              <Col md={9}>
                <Row className="g-3 text-center">
                  {stats.length > 0 ? (
                    stats.slice(0, 3).map((s, idx) => (
                      <Col xs={4} key={s.id || idx}>
                        <div className="stat-proposition-item">
                          <h4 className="fw-bold text-primary mb-1 fs-5">{s.section_title}</h4>
                          <p className="text-muted small mb-0" style={{ fontSize: "11.5px" }}>
                            {s.section_content}
                          </p>
                        </div>
                      </Col>
                    ))
                  ) : (
                    <>
                      <Col xs={4}>
                        <div className="stat-proposition-item">
                          <h4 className="fw-bold text-primary mb-1 fs-5">1 Million+</h4>
                          <p className="text-muted small mb-0" style={{ fontSize: "11.5px" }}>Custom Gifts Delivered</p>
                        </div>
                      </Col>
                      <Col xs={4}>
                        <div className="stat-proposition-item">
                          <h4 className="fw-bold text-primary mb-1 fs-5">20,000+</h4>
                          <p className="text-muted small mb-0" style={{ fontSize: "11.5px" }}>Happy Printing Partners</p>
                        </div>
                      </Col>
                      <Col xs={4}>
                        <div className="stat-proposition-item">
                          <h4 className="fw-bold text-primary mb-1 fs-5">7 Years</h4>
                          <p className="text-muted small mb-0" style={{ fontSize: "11.5px" }}>Of Excellence & Service</p>
                        </div>
                      </Col>
                    </>
                  )}
                </Row>
              </Col>
            </Row>
          </div>
        </Container>
      </section>

    </div>
  );
};

export default AboutPage;
