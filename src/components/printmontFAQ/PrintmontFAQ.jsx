import React, { useState, useEffect, useMemo } from "react";
import { Container, Row, Col } from "react-bootstrap";
import { Link } from "react-router-dom";
import { FaRegQuestionCircle } from "react-icons/fa";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import "./faq.css";

/** Category names in the table carry stray whitespace. */
const clean = (value) => String(value || "").trim();

const isHtml = (value) => /<\/?[a-z][\s\S]*>/i.test(String(value || ""));

const PrintmontFAQ = () => {
  const [faqs, setFaqs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [failed, setFailed] = useState(false);

  const [activeCategory, setActiveCategory] = useState(null);
  const [openId, setOpenId] = useState(null);

  useEffect(() => {
    window.scrollTo(0, 0);
    let cancelled = false;

    const load = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.FAQ);
        const json = await response.json();
        if (cancelled) return;

        const rows = Array.isArray(json?.data) ? json.data : [];
        if (!json?.success) setFailed(true);
        setFaqs(rows.filter((f) => Number(f.is_active) !== 0));
      } catch (error) {
        console.error("Error fetching FAQs:", error);
        if (!cancelled) setFailed(true);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    load();
    return () => { cancelled = true; };
  }, []);

  /**
   * Categories are derived from the questions themselves: four of the six
   * rows in faq_categories currently hold none, and an empty sidebar entry
   * that opens onto nothing is worse than no entry.
   */
  const categories = useMemo(() => {
    const map = new Map();
    faqs.forEach((f) => {
      const name = clean(f.category_name) || "Other";
      if (!map.has(name)) map.set(name, { name, items: [] });
      map.get(name).items.push(f);
    });
    return [...map.values()];
  }, [faqs]);

  // Select the first category once the data lands.
  useEffect(() => {
    if (!activeCategory && categories.length) setActiveCategory(categories[0].name);
  }, [categories, activeCategory]);

  const current = categories.find((c) => c.name === activeCategory) || categories[0] || null;

  const selectCategory = (name) => {
    setActiveCategory(name);
    setOpenId(null);
  };

  return (
    <div className="faq-page">
      <Container className="faq-wrap">
        {loading ? (
          <Row className="g-4">
            <Col lg={3}>
              {[0, 1, 2, 3].map((i) => (
                <div key={i} className="shimmer-bg mb-2" style={{ height: 50, borderRadius: 2 }} />
              ))}
            </Col>
            <Col lg={9}>
              <div className="shimmer-bg mb-3" style={{ height: 32, width: "40%", borderRadius: 2 }} />
              {[0, 1, 2, 3, 4].map((i) => (
                <div key={i} className="shimmer-bg mb-1" style={{ height: 52, borderRadius: 2 }} />
              ))}
            </Col>
          </Row>
        ) : failed || !current ? (
          <div className="faq-empty">
            <FaRegQuestionCircle className="faq-empty__icon" />
            <h2 className="faq-empty__title">
              {failed ? "FAQs are unavailable right now" : "No questions published yet"}
            </h2>
            <p className="faq-empty__note mb-0">
              {failed
                ? "Please refresh in a moment."
                : "Answers will appear here as soon as they are added."}
            </p>
          </div>
        ) : (
          <Row className="g-0 g-lg-4">
            {/* CATEGORY SIDEBAR */}
            <Col xs={12} lg={3}>
              <nav className="faq-cats" aria-label="FAQ categories">
                {categories.map((c) => (
                  <button
                    key={c.name}
                    type="button"
                    className={`faq-cat ${c.name === current.name ? "is-active" : ""}`}
                    aria-current={c.name === current.name ? "true" : undefined}
                    onClick={() => selectCategory(c.name)}
                  >
                    {c.name}
                  </button>
                ))}
              </nav>
            </Col>

            {/* QUESTIONS */}
            <Col xs={12} lg={9}>
              <h1 className="faq-title">{current.name}</h1>

              <div className="faq-list">
                {current.items.map((faq) => {
                  const open = openId === faq.id;
                  return (
                    <div key={faq.id} className={`faq-item ${open ? "is-open" : ""}`}>
                      <button
                        type="button"
                        className="faq-q"
                        aria-expanded={open}
                        onClick={() => setOpenId(open ? null : faq.id)}
                      >
                        <span className="faq-q__text">{faq.question}</span>
                        <span className="faq-q__toggle" aria-hidden="true">
                          {open ? "−" : "+"}
                        </span>
                      </button>

                      {open && (
                        <div className="faq-a">
                          {isHtml(faq.answer) ? (
                            <div dangerouslySetInnerHTML={{ __html: faq.answer }} />
                          ) : (
                            <p className="mb-0">{faq.answer}</p>
                          )}
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>

              <div className="faq-help">
                <p className="faq-help__text mb-3">
                  Still need help? Our support team is happy to assist.
                </p>
                <Link to="/contact" className="faq-help__cta">Contact us</Link>
                <Link to="/track-order" className="faq-help__cta faq-help__cta--ghost">
                  Track your order
                </Link>
              </div>
            </Col>
          </Row>
        )}
      </Container>
    </div>
  );
};

export default PrintmontFAQ;
