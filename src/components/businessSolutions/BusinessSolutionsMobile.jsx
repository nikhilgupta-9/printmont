import React, { useState } from "react";
import { Container, Row, Col } from "react-bootstrap";
import { useNavigate, Link } from "react-router-dom";
import Slider from "react-slick";
import { 
  FaArrowLeft, 
  FaUserCircle, 
  FaPlus, 
  FaMinus, 
  FaStar,
  FaFileInvoiceDollar,
  FaUserTie,
  FaHeadset,
  FaTruck,
  FaBuilding,
  FaCheckCircle,
  FaArrowRight
} from "react-icons/fa";
import BusinessLeadForm from "./BusinessLeadForm";

// Helper icon resolver for Why Printmont
const resolveValueIcon = (iconName) => {
  switch (iconName) {
    case "FaFileInvoiceDollar": return <FaFileInvoiceDollar />;
    case "FaUserTie": return <FaUserTie />;
    case "FaHeadset": return <FaHeadset />;
    case "FaTruck": return <FaTruck />;
    default: return <FaCheckCircle />;
  }
};

export default function BusinessSolutionsMobile({ sections = [], faqs = [], resolveImg }) {
  const navigate = useNavigate();
  const [openFaqIdx, setOpenFaqIdx] = useState(0);

  // Slider settings for banner carousels
  const bannerSliderSettings = {
    dots: true,
    infinite: true,
    speed: 500,
    slidesToShow: 1,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 4000,
    arrows: false
  };

  // Slider settings for reviews
  const reviewSliderSettings = {
    dots: true,
    infinite: true,
    speed: 500,
    slidesToShow: 1,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 5000,
    arrows: false
  };

  // Render individual dynamic section based on section_key
  const renderSection = (section) => {
    const config = section.config || {};

    switch (section.section_key) {
      case "hero_banner": {
        const banners = config.banners || [];
        if (!banners.length) return null;
        return (
          <section key={section.id} className="bs-hero-banner-section mb-3">
            {banners.length === 1 ? (
              <a href={banners[0].link_url || "#lead-form"}>
                <img
                  src={resolveImg(banners[0].image_mobile || banners[0].image_desktop)}
                  alt={banners[0].title || "Business Solutions Banner"}
                  className="bs-hero-banner-img"
                />
              </a>
            ) : (
              <Slider {...bannerSliderSettings}>
                {banners.map((b, idx) => (
                  <div key={idx}>
                    <a href={b.link_url || "#lead-form"}>
                      <img
                        src={resolveImg(b.image_mobile || b.image_desktop)}
                        alt={b.title || `Banner ${idx + 1}`}
                        className="bs-hero-banner-img"
                      />
                    </a>
                  </div>
                ))}
              </Slider>
            )}
          </section>
        );
      }

      case "lead_form": {
        return (
          <div key={section.id} className="px-3">
            <BusinessLeadForm 
              deviceType="mobile" 
              title={section.title || "Business Solutions Tailored to Your Needs"}
              subtitle={section.subtitle || "Fill the Form Today!"}
            />
          </div>
        );
      }

      case "industry_solutions": {
        const items = config.items || [];
        if (!items.length) return null;
        return (
          <section key={section.id} className="px-3 mb-4">
            <div className="bs-section-header">
              <h2 className="bs-section-title">{section.title || "Printmont for Business"}</h2>
              {section.subtitle && <p className="bs-section-subtitle">{section.subtitle}</p>}
            </div>
            <Row className="g-3">
              {items.map((item, idx) => (
                <Col xs={12} key={idx}>
                  <div className="bs-industry-card">
                    <div className="bs-industry-img-wrap">
                      <img 
                        src={resolveImg(item.image)} 
                        alt={item.tag || item.headline} 
                        className="bs-industry-img"
                        loading="lazy"
                        onError={(e) => { e.target.src = "/default-img.jpg"; }}
                      />
                      {item.tag && <span className="bs-industry-tag">{item.tag}</span>}
                    </div>
                    <div className="bs-industry-body">
                      <h3 className="bs-industry-headline">{item.headline}</h3>
                      <p className="bs-industry-desc">{item.desc}</p>
                      <a href="#lead-form" className="bs-industry-cta">
                        Enquire for {item.tag ? item.tag.replace(/^For\s+/i, '') : "Solutions"} <FaArrowRight size={10} />
                      </a>
                    </div>
                  </div>
                </Col>
              ))}
            </Row>
          </section>
        );
      }

      case "corporate_departments": {
        const items = config.items || [];
        if (!items.length) return null;
        return (
          <section key={section.id} className="px-3 mb-4">
            <div className="bs-section-header">
              <h2 className="bs-section-title">{section.title || "Printmont for Corporate"}</h2>
              {section.subtitle && <p className="bs-section-subtitle">{section.subtitle}</p>}
            </div>
            <div className="bs-corp-list">
              {items.map((item, idx) => (
                <div key={idx} className="bs-corp-card">
                  <span className="bs-corp-tag">{item.tag}</span>
                  <h3 className="bs-corp-headline">{item.headline}</h3>
                  <p className="bs-corp-desc">{item.desc}</p>
                </div>
              ))}
            </div>
          </section>
        );
      }

      case "portfolio_showcase": {
        const items = config.items || [];
        if (!items.length) return null;
        return (
          <section key={section.id} className="px-3 mb-4">
            <div className="bs-section-header">
              <h2 className="bs-section-title">{section.title || "Work We Delivered for India's Largest Brands."}</h2>
              {section.subtitle && <p className="bs-section-subtitle">{section.subtitle}</p>}
            </div>
            <Row className="g-2">
              {items.map((p, idx) => (
                <Col xs={6} key={idx}>
                  <div className="bs-portfolio-card">
                    <div className="bs-portfolio-img-wrap">
                      <img 
                        src={resolveImg(p.image)} 
                        alt={p.title} 
                        className="bs-portfolio-img"
                        loading="lazy"
                        onError={(e) => { e.target.src = "/default-img.jpg"; }}
                      />
                    </div>
                    <div className="bs-portfolio-info">
                      <p className="bs-portfolio-title">{p.title}</p>
                      {p.client && <p className="bs-portfolio-client">{p.client}</p>}
                    </div>
                  </div>
                </Col>
              ))}
            </Row>
          </section>
        );
      }

      case "why_printmont": {
        const items = config.items || [];
        if (!items.length) return null;
        return (
          <section key={section.id} className="px-3 mb-4">
            <div className="bs-section-header">
              <h2 className="bs-section-title">{section.title || "Why Printmont?"}</h2>
              {section.subtitle && <p className="bs-section-subtitle">{section.subtitle}</p>}
            </div>
            <Row className="g-3">
              {items.map((val, idx) => (
                <Col xs={12} key={idx}>
                  <div className="bs-value-card d-flex align-items-start gap-3">
                    <div className="bs-value-icon-wrap flex-shrink-0">
                      {resolveValueIcon(val.icon)}
                    </div>
                    <div>
                      <h3 className="bs-value-title mb-1">{val.title}</h3>
                      <p className="bs-value-desc">{val.desc}</p>
                    </div>
                  </div>
                </Col>
              ))}
            </Row>
          </section>
        );
      }

      case "partner_banner": {
        const banners = config.banners || [];
        if (!banners.length) return null;
        return (
          <section key={section.id} className="bs-hero-banner-section mb-4">
            {banners.length === 1 ? (
              <a href={banners[0].link_url || "#lead-form"}>
                <img
                  src={resolveImg(banners[0].image_mobile || banners[0].image_desktop)}
                  alt={banners[0].title || "Partner Banner"}
                  className="bs-hero-banner-img"
                />
              </a>
            ) : (
              <Slider {...bannerSliderSettings}>
                {banners.map((b, idx) => (
                  <div key={idx}>
                    <a href={b.link_url || "#lead-form"}>
                      <img
                        src={resolveImg(b.image_mobile || b.image_desktop)}
                        alt={b.title || `Partner Banner ${idx + 1}`}
                        className="bs-hero-banner-img"
                      />
                    </a>
                  </div>
                ))}
              </Slider>
            )}
          </section>
        );
      }

      case "stats_counters": {
        const stats = config.stats || [];
        if (!stats.length) return null;
        return (
          <div key={section.id} className="px-3 mb-4">
            <div className="bs-stats-card">
              <Row className="g-2">
                {stats.map((st, idx) => (
                  <Col xs={4} key={idx}>
                    <div className="bs-stat-item">
                      <div className="bs-stat-value">{st.value}</div>
                      <div className="bs-stat-label">{st.label}</div>
                    </div>
                  </Col>
                ))}
              </Row>
            </div>
          </div>
        );
      }

      case "customer_reviews": {
        const reviews = config.reviews || [];
        if (!reviews.length) return null;
        return (
          <section key={section.id} className="px-3 mb-4">
            <div className="bs-section-header">
              <h2 className="bs-section-title">{section.title || "Customer Reviews"}</h2>
              {section.subtitle && <p className="bs-section-subtitle">{section.subtitle}</p>}
            </div>
            {reviews.length === 1 ? (
              <div className="bs-review-card">
                <div className="bs-review-stars">
                  {[...Array(reviews[0].rating || 5)].map((_, i) => (
                    <FaStar key={i} />
                  ))}
                </div>
                <p className="bs-review-text">"{reviews[0].review}"</p>
                <div className="bs-review-author">
                  <div className="bs-review-avatar">
                    {(reviews[0].name || "U")[0]}
                  </div>
                  <div>
                    <p className="bs-review-name">{reviews[0].name}</p>
                    <p className="bs-review-company">{reviews[0].company}</p>
                  </div>
                </div>
              </div>
            ) : (
              <Slider {...reviewSliderSettings}>
                {reviews.map((rev, idx) => (
                  <div key={idx} className="px-1 pb-3">
                    <div className="bs-review-card">
                      <div className="bs-review-stars">
                        {[...Array(rev.rating || 5)].map((_, i) => (
                          <FaStar key={i} />
                        ))}
                      </div>
                      <p className="bs-review-text">"{rev.review}"</p>
                      <div className="bs-review-author">
                        <div className="bs-review-avatar">
                          {(rev.name || "U")[0]}
                        </div>
                        <div>
                          <p className="bs-review-name">{rev.name}</p>
                          <p className="bs-review-company">{rev.company}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </Slider>
            )}
          </section>
        );
      }

      case "faqs": {
        if (!faqs.length) return null;
        return (
          <section key={section.id} className="px-3 mb-4">
            <div className="bs-section-header">
              <h2 className="bs-section-title">{section.title || "Frequently Asked Questions"}</h2>
              {section.subtitle && <p className="bs-section-subtitle">{section.subtitle}</p>}
            </div>
            <div className="bs-faq-list">
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
                        {isOpen ? <FaMinus size={12} /> : <FaPlus size={12} />}
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
          </section>
        );
      }

      default:
        return null;
    }
  };

  return (
    <div className="bs-mobile-container pb-4">
      {/* Dynamic Ordered Sections */}
      <main className="bs-mobile-content pt-2">
        {sections.map((section) => renderSection(section))}
      </main>
    </div>
  );
}
