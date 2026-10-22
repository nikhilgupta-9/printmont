import React, { useState } from "react";
import { Container, Row, Col } from "react-bootstrap";
import Slider from "react-slick";
import { 
  FaPlus, 
  FaMinus, 
  FaStar,
  FaFileInvoiceDollar,
  FaUserTie,
  FaHeadset,
  FaTruck,
  FaCheckCircle,
  FaChevronRight,
  FaChevronLeft
} from "react-icons/fa";
import BusinessLeadForm from "./BusinessLeadForm";

// Custom Arrow Components for Sliders
function SampleNextArrow(props) {
  const { className, style, onClick } = props;
  return (
    <div
      className={`${className} bs-slick-arrow bs-slick-next`}
      style={{ ...style, display: "flex", alignItems: "center", justifyContent: "center" }}
      onClick={onClick}
    >
      <FaChevronRight size={14} color="#0f172a" />
    </div>
  );
}

function SamplePrevArrow(props) {
  const { className, style, onClick } = props;
  return (
    <div
      className={`${className} bs-slick-arrow bs-slick-prev`}
      style={{ ...style, display: "flex", alignItems: "center", justifyContent: "center" }}
      onClick={onClick}
    >
      <FaChevronLeft size={14} color="#0f172a" />
    </div>
  );
}

// Helper icon resolver for Why Printmont
const resolveValueIcon = (iconName) => {
  switch (iconName) {
    case "FaFileInvoiceDollar": return <FaFileInvoiceDollar size={24} className="text-primary" />;
    case "FaUserTie": return <FaUserTie size={24} className="text-primary" />;
    case "FaHeadset": return <FaHeadset size={24} className="text-primary" />;
    case "FaTruck": return <FaTruck size={24} className="text-primary" />;
    default: return <FaCheckCircle size={24} className="text-primary" />;
  }
};

export default function BusinessSolutionsDesktop({ sections = [], faqs = [], resolveImg }) {
  const [openFaqIdx, setOpenFaqIdx] = useState(0);

  // Slider settings for banner carousels
  const bannerSliderSettings = {
    dots: true,
    infinite: true,
    speed: 600,
    slidesToShow: 1,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 5000,
    nextArrow: <SampleNextArrow />,
    prevArrow: <SamplePrevArrow />
  };

  // Slider settings for reviews
  const reviewSliderSettings = {
    dots: true,
    infinite: true,
    speed: 500,
    slidesToShow: 3,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 4500,
    arrows: false
  };

  // Render individual dynamic section based on section_key
  const renderSection = (section) => {
    const config = section.config || {};

    switch (section.section_key) {
      /* 1. Hero Banner Slider */
      case "hero_banner": {
        const banners = config.banners || [];
        if (!banners.length) return null;
        return (
          <section key={section.id} className="bs-dt-hero-section mb-5">
            {banners.length === 1 ? (
              <div className="bs-dt-banner-wrap">
                <a href={banners[0].link_url || "#lead-form"}>
                  <img
                    src={resolveImg(banners[0].image_desktop || banners[0].image_mobile)}
                    alt={banners[0].title || "Business Solutions"}
                    className="bs-dt-banner-img"
                  />
                </a>
              </div>
            ) : (
              <div className="bs-dt-banner-wrap">
                <Slider {...bannerSliderSettings}>
                  {banners.map((b, idx) => (
                    <div key={idx}>
                      <a href={b.link_url || "#lead-form"}>
                        <img
                          src={resolveImg(b.image_desktop || b.image_mobile)}
                          alt={b.title || `Banner ${idx + 1}`}
                          className="bs-dt-banner-img"
                        />
                      </a>
                    </div>
                  ))}
                </Slider>
              </div>
            )}
          </section>
        );
      }

      /* 2. Lead Capture Form Card */
      case "lead_form": {
        return (
          <Container key={section.id} className="mb-5">
            <div className="mx-auto bs-dt-form-container">
              <BusinessLeadForm 
                deviceType="desktop" 
                title={section.title || "Business Solutions Tailored to Your Needs -"}
                subtitle={section.subtitle || "Fill the Form Today!"}
              />
            </div>
          </Container>
        );
      }

      /* 3. Printmont for Business (3-Column Industry Grid) */
      case "industry_solutions": {
        const items = config.items || [];
        if (!items.length) return null;
        return (
          <section key={section.id} className="mb-5">
            <Container>
              <div className="text-center mb-4">
                <h2 className="bs-dt-section-title">{section.title || "Printmont for Business"}</h2>
                {section.subtitle && <p className="bs-dt-section-sub">{section.subtitle}</p>}
              </div>

              <Row className="g-4">
                {items.map((item, idx) => (
                  <Col lg={4} md={6} key={idx}>
                    <div className="bs-dt-industry-card">
                      {/* Top Image Box */}
                      <div className="bs-dt-industry-img-box">
                        <img 
                          src={resolveImg(item.image)} 
                          alt={item.tag || item.headline} 
                          className="bs-dt-industry-img"
                          loading="lazy"
                          onError={(e) => { e.target.src = "/default-img.jpg"; }}
                        />
                      </div>

                      {/* Content Box */}
                      <div className="bs-dt-industry-content">
                        <h4 className="bs-dt-industry-tag">{item.tag}</h4>
                        <h5 className="bs-dt-industry-headline">{item.headline}</h5>
                        <p className="bs-dt-industry-desc">{item.desc}</p>
                      </div>
                    </div>
                  </Col>
                ))}
              </Row>
            </Container>
          </section>
        );
      }

      /* 4. Printmont for Corporate (2-Column Department Grid) */
      case "corporate_departments": {
        const items = config.items || [];
        if (!items.length) return null;
        return (
          <section key={section.id} className="mb-5 pt-3 pb-4">
            <Container>
              <div className="text-center mb-4">
                <h2 className="bs-dt-section-title">{section.title || "Printmont for Corporate"}</h2>
                {section.subtitle && <p className="bs-dt-section-sub">{section.subtitle}</p>}
              </div>

              <Row className="g-4">
                {items.map((item, idx) => (
                  <Col lg={6} key={idx}>
                    <div className="bs-dt-corp-box">
                      <h4 className="bs-dt-corp-tag">{item.tag}</h4>
                      <h5 className="bs-dt-corp-headline">{item.headline}</h5>
                      <p className="bs-dt-corp-desc">{item.desc}</p>
                    </div>
                  </Col>
                ))}
              </Row>
            </Container>
          </section>
        );
      }

      /* 5. Work We Delivered for India's Largest Brands (2-Column × 3-Row Grid) */
      case "portfolio_showcase": {
        const items = config.items || [];
        if (!items.length) return null;
        return (
          <section key={section.id} className="mb-5">
            <Container>
              <div className="text-center mb-4">
                <h2 className="bs-dt-section-title">{section.title || "Work We Delivered for India's Largest Brands."}</h2>
                {section.subtitle && <p className="bs-dt-section-sub">{section.subtitle}</p>}
              </div>

              <Row className="g-4">
                {items.map((p, idx) => (
                  <Col lg={6} md={6} key={idx}>
                    <div className="bs-dt-showcase-card">
                      <div className="bs-dt-showcase-img-box">
                        <img 
                          src={resolveImg(p.image)} 
                          alt={p.title} 
                          className="bs-dt-showcase-img"
                          loading="lazy"
                          onError={(e) => { e.target.src = "/default-img.jpg"; }}
                        />
                      </div>
                      <div className="bs-dt-showcase-caption">
                        <span className="fw-bold text-dark">{p.title}</span>
                        {p.client && <span className="text-muted ms-2">— {p.client}</span>}
                      </div>
                    </div>
                  </Col>
                ))}
              </Row>
            </Container>
          </section>
        );
      }

      /* 6. Why Printmont? (Light Blue Box with 2x2 Grid) */
      case "why_printmont": {
        const items = config.items || [];
        if (!items.length) return null;
        return (
          <section key={section.id} className="mb-5">
            <Container>
              <div className="bs-dt-why-container">
                <Row className="g-4 align-items-center">
                  {/* Left Column Title */}
                  <Col lg={4}>
                    <div className="bs-dt-why-title-wrap">
                      <h2 className="bs-dt-why-heading">{section.title || "Why Printmont?"}</h2>
                    </div>
                  </Col>

                  {/* Right Column 2x2 Grid */}
                  <Col lg={8}>
                    <Row className="g-4">
                      {items.map((val, idx) => (
                        <Col sm={6} key={idx}>
                          <div className="bs-dt-why-item d-flex gap-3">
                            <div className="bs-dt-why-icon flex-shrink-0 mt-1">
                              {resolveValueIcon(val.icon)}
                            </div>
                            <div>
                              <h4 className="bs-dt-why-item-title">{val.title}</h4>
                              <p className="bs-dt-why-item-desc">{val.desc}</p>
                            </div>
                          </div>
                        </Col>
                      ))}
                    </Row>
                  </Col>
                </Row>
              </div>
            </Container>
          </section>
        );
      }

      /* 7. Partner Banner Slider */
      case "partner_banner": {
        const banners = config.banners || [];
        if (!banners.length) return null;
        return (
          <section key={section.id} className="mb-5">
            <Container>
              <div className="text-center mb-3">
                <h2 className="bs-dt-section-title">{section.title || "Printmont Partner for Small & Large Businesses."}</h2>
              </div>
              <div className="bs-dt-partner-wrap">
                {banners.length === 1 ? (
                  <a href={banners[0].link_url || "#lead-form"}>
                    <img
                      src={resolveImg(banners[0].image_desktop || banners[0].image_mobile)}
                      alt={banners[0].title || "Partner Banner"}
                      className="bs-dt-partner-img"
                    />
                  </a>
                ) : (
                  <Slider {...bannerSliderSettings}>
                    {banners.map((b, idx) => (
                      <div key={idx}>
                        <a href={b.link_url || "#lead-form"}>
                          <img
                            src={resolveImg(b.image_desktop || b.image_mobile)}
                            alt={b.title || `Partner Banner ${idx + 1}`}
                            className="bs-dt-partner-img"
                          />
                        </a>
                      </div>
                    ))}
                  </Slider>
                )}
              </div>
            </Container>
          </section>
        );
      }

      /* 8. Stats Bar ("Numbers Backing Up Our Proposition") */
      case "stats_counters": {
        const stats = config.stats || [];
        if (!stats.length) return null;
        return (
          <section key={section.id} className="mb-5">
            <Container>
              <div className="bs-dt-stats-bar">
                <Row className="g-0 align-items-center">
                  <Col lg={3} className="bs-dt-stats-col bs-dt-stats-lead">
                    <h4 className="bs-dt-stats-lead-title">Numbers Backing Up Our Proposition</h4>
                  </Col>
                  {stats.map((st, idx) => (
                    <Col lg={3} key={idx} className="bs-dt-stats-col">
                      <div className="bs-dt-stats-box">
                        <div className="bs-dt-stats-val">{st.value}</div>
                        <div className="bs-dt-stats-lbl">{st.label}</div>
                      </div>
                    </Col>
                  ))}
                </Row>
              </div>
            </Container>
          </section>
        );
      }

      /* 9. Brands. Work We Delivered for India's Largest with Us (Reviews) */
      case "customer_reviews": {
        const reviews = config.reviews || [];
        if (!reviews.length) return null;
        return (
          <section key={section.id} className="mb-5">
            <Container>
              <div className="text-center mb-4">
                <h2 className="bs-dt-section-title">{section.title || "Brands. Work We Delivered for India's Largest with Us"}</h2>
              </div>
              <Row className="g-4">
                {reviews.map((rev, idx) => (
                  <Col lg={4} md={6} key={idx}>
                    <div className="bs-dt-review-card">
                      <div className="d-flex align-items-center gap-3 mb-2">
                        <div className="bs-dt-review-avatar">
                          {(rev.name || "U")[0]}
                        </div>
                        <div>
                          <h5 className="bs-dt-review-name mb-0">{rev.name}</h5>
                          <span className="bs-dt-review-role">{rev.company}</span>
                        </div>
                      </div>
                      <div className="bs-dt-review-stars mb-2">
                        {[...Array(rev.rating || 5)].map((_, i) => (
                          <FaStar key={i} size={13} color="#16a34a" />
                        ))}
                      </div>
                      <p className="bs-dt-review-text">"{rev.review}"</p>
                    </div>
                  </Col>
                ))}
              </Row>
            </Container>
          </section>
        );
      }

      /* 10. FAQ Accordions */
      case "faqs": {
        if (!faqs.length) return null;
        return (
          <section key={section.id} className="mb-5">
            <Container>
              <div className="text-center mb-4">
                <h2 className="bs-dt-section-title">{section.title || "Frequently Asked Questions"}</h2>
                {section.subtitle && <p className="bs-dt-section-sub">{section.subtitle}</p>}
              </div>
              <div className="mx-auto" style={{ maxWidth: "900px" }}>
                {faqs.map((faq, idx) => {
                  const isOpen = openFaqIdx === idx;
                  return (
                    <div className="bs-faq-item" key={faq.id || idx}>
                      <div
                        className="bs-faq-question"
                        onClick={() => setOpenFaqIdx(isOpen ? null : idx)}
                      >
                        <span>{faq.question}</span>
                        <span className="bs-faq-icon">
                          {isOpen ? <FaMinus size={13} /> : <FaPlus size={13} />}
                        </span>
                      </div>
                      {isOpen && faq.answer && (
                        <div className="bs-faq-answer">
                          {faq.answer}
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            </Container>
          </section>
        );
      }

      default:
        return null;
    }
  };

  return (
    <div className="bs-desktop-wrapper pb-5">
      <main className="bs-desktop-content">
        {sections.map((section) => renderSection(section))}
      </main>
    </div>
  );
}
