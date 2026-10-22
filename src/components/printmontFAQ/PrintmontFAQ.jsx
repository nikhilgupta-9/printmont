import React, { useState, useEffect, useMemo } from "react";
import { Container, Spinner } from "react-bootstrap";
import {
  FaHeadset,
  FaEnvelope,
  FaStore,
  FaChevronDown,
  FaThumbsUp,
  FaThumbsDown
} from "react-icons/fa";
import { toast } from "react-hot-toast";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import { updatePageSEO } from "../../utils/seo";
import "./faq.css";

const isHtml = (value) => /<\/?[a-z][\s\S]*>/i.test(String(value || ""));

const FormattedAnswer = ({ text }) => {
  if (!text) return null;

  if (isHtml(text)) {
    return <div className="help-faq-html-body" dangerouslySetInnerHTML={{ __html: text }} />;
  }

  const lines = String(text)
    .split(/\r?\n/)
    .map((l) => l.trim())
    .filter(Boolean);

  const isBulletList = lines.some((l) => /^[-*•]\s*/.test(l) || lines.length > 1);

  if (isBulletList && lines.length > 1) {
    return (
      <ul className="help-faq-bullet-list">
        {lines.map((line, idx) => (
          <li key={idx}>{line.replace(/^[-*•]\s*/, "")}</li>
        ))}
      </ul>
    );
  }

  return <p className="help-faq-text-body mb-0">{text}</p>;
};

const PrintmontFAQ = () => {
  const [categories, setCategories] = useState([]);
  const [selectedCatId, setSelectedCatId] = useState(null);
  const [openIds, setOpenIds] = useState(new Set());
  const [feedback, setFeedback] = useState({});
  const [loading, setLoading] = useState(true);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: "Frequently Asked Questions (FAQ) | Printmont Support",
      description: "Find instant answers to questions regarding custom printing, delivery slots, order tracking, bulk corporate gifting, GST invoices, or contact Printmont helpline.",
      keywords: "printmont faq, frequently asked questions, custom printing help, order tracking faq, delivery queries, bulk orders help",
      type: "website"
    });

    let cancelled = false;

    const fetchFaqData = async () => {
      try {
        setLoading(true);
        // Primary source is the unified Help Center / FAQ API
        const endpoint = API_ENDPOINTS.HELP_CENTER || API_ENDPOINTS.FAQ || "/api/help-center-api.php";
        const res = await fetch(endpoint);
        const json = await res.json();

        if (cancelled) return;

        let cats = [];
        if (json && json.success && json.data && Array.isArray(json.data.categories)) {
          cats = json.data.categories.filter((c) => c.is_active !== false && c.is_active !== 0 && c.is_active !== "0");
        } else if (Array.isArray(json?.data)) {
          // If flat list returned from faq-api.php, normalize into categories
          const map = new Map();
          json.data.forEach((item) => {
            if (item.is_active === 0 || item.is_active === false || item.is_active === "0") return;
            const cName = item.category_name || "General";
            const cId = item.category_id || cName;
            if (!map.has(cId)) {
              map.set(cId, { id: cId, name: cName, faqs: [] });
            }
            map.get(cId).faqs.push(item);
          });
          cats = Array.from(map.values());
        } else if (Array.isArray(json)) {
          cats = json;
        }

        setCategories(cats);
        if (cats.length > 0) {
          // Select Delivery / Order info by default if present
          const deliveryCat = cats.find((c) =>
            (c.name || "").toLowerCase().includes("delivery") || (c.name || "").toLowerCase().includes("order")
          );
          const initialCat = deliveryCat || cats[0];
          setSelectedCatId(initialCat.id);
        }
      } catch (err) {
        console.error("Error fetching FAQ data:", err);
        if (!cancelled) setFailed(true);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    fetchFaqData();
    return () => {
      cancelled = true;
    };
  }, []);

  const currentCategory = useMemo(() => {
    if (!categories.length) return null;
    return categories.find((c) => String(c.id) === String(selectedCatId)) || categories[0];
  }, [categories, selectedCatId]);

  // Filtered FAQs based on active category
  const displayedFaqs = useMemo(() => {
    return currentCategory?.faqs || [];
  }, [currentCategory]);

  const toggleFaq = (id) => {
    setOpenIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  const handleFeedback = (faqId, type) => {
    if (feedback[faqId]) return;
    setFeedback((prev) => ({ ...prev, [faqId]: type }));
    if (type === "yes") {
      toast.success("Thank you for your feedback! 😊");
    } else {
      toast("We're here to help! Call our helpline or send us an email.", { icon: "ℹ️" });
    }
  };

  return (
    <div className="faq-page-wrap help-page-wrap">
      {/* =========================================================================
          DESKTOP VIEW (Exact Match to Help Center UI, min-width: 768px)
          ========================================================================= */}
      <div className="pm-desktop-help-wrap d-none d-md-block">
        <Container className="faq-container help-container">
          {/* 1. Desktop Top 3 Contact Columns */}
          <div className="pm-dt-contact-row">
            {/* Call us for Queries */}
            <div className="pm-dt-contact-col">
              <div className="pm-dt-icon-disc">
                <FaHeadset className="pm-dt-contact-icon" />
              </div>
              <h5 className="pm-dt-contact-title">Call us for Queries</h5>
              <p className="pm-dt-contact-text mb-0">
                Helpline no:- <a href="tel:+919818532463" className="pm-dt-contact-link">+91-9818532463</a>
                <span className="d-block">(Mon - Sat: 10:30 AM - 6:30 PM)</span>
              </p>
            </div>

            {/* E-Mail us */}
            <div className="pm-dt-contact-col">
              <div className="pm-dt-icon-disc">
                <FaEnvelope className="pm-dt-contact-icon" />
              </div>
              <h5 className="pm-dt-contact-title">E-Mail us</h5>
              <p className="pm-dt-contact-text mb-0">
                Sales enquiries and customer support:
                <a href="mailto:support@printmont.com" className="pm-dt-contact-link d-block">
                  support@printmont.com
                </a>
              </p>
            </div>

            {/* Postal Address */}
            <div className="pm-dt-contact-col">
              <div className="pm-dt-icon-disc">
                <FaStore className="pm-dt-contact-icon" />
              </div>
              <h5 className="pm-dt-contact-title">Postal Address</h5>
              <p className="pm-dt-contact-text mb-0">
                <strong className="text-dark d-block fw-normal">Printmont Corporation</strong>
                <span>3398, Bagichi Acchi ji, Bara Hindu Rao,Near Filmistan Cinema, Delhi India - 110006</span>
              </p>
            </div>
          </div>

          {/* 2. Desktop Headings Grid */}
          <div className="pm-dt-heading-grid">
            <h4 className="pm-dt-sec-heading">Frequently Asked Questions</h4>
            <h4 className="pm-dt-sec-heading">{currentCategory?.name || "Delivery information"}</h4>
          </div>

          {/* 3. Desktop Main Content Grid (Categories on Left, FAQs on Right) */}
          {loading ? (
            <div className="text-center py-5 bg-white border p-4">
              <Spinner animation="border" variant="primary" size="sm" />
              <p className="text-muted small mt-2 mb-0">Loading FAQs &amp; Help Topics...</p>
            </div>
          ) : failed || !categories.length ? (
            <div className="text-center py-5 bg-white border p-4">
              <h6 className="text-dark fw-bold mb-1">FAQs are currently unavailable</h6>
              <p className="text-muted small mb-0">Please try again later or reach out to our helpline.</p>
            </div>
          ) : (
            <div className="pm-dt-main-grid">
              {/* Left Column: Categories List */}
              <div className="pm-dt-category-box">
                {categories.map((cat) => {
                  const isActive = String(selectedCatId) === String(cat.id);
                  return (
                    <button
                      key={cat.id}
                      type="button"
                      className={`pm-dt-category-item ${isActive ? "is-active" : ""}`}
                      onClick={() => {
                        setSelectedCatId(cat.id);
                        setOpenIds(new Set());
                      }}
                    >
                      {cat.name}
                    </button>
                  );
                })}
              </div>

              {/* Right Column: FAQ Accordion List */}
              <div className="pm-dt-faq-box">
                {displayedFaqs && displayedFaqs.length > 0 ? (
                  displayedFaqs.map((faq, index) => {
                    const isOpen = openIds.has(faq.id);
                    const isLast = index === displayedFaqs.length - 1;
                    return (
                      <div
                        key={faq.id}
                        className={`pm-dt-faq-row ${isLast ? "is-last" : ""}`}
                      >
                        <button
                          type="button"
                          className="pm-dt-faq-btn"
                          onClick={() => toggleFaq(faq.id)}
                          aria-expanded={isOpen}
                        >
                          <span className="pm-dt-q-text">{faq.question}</span>
                          <span className="pm-dt-toggle-sign" aria-hidden="true">
                            {isOpen ? "−" : "+"}
                          </span>
                        </button>
                        {isOpen && (
                          <div className="pm-dt-answer-drawer">
                            <FormattedAnswer text={faq.answer} />

                            {/* Helpful feedback bar on desktop */}
                            <div className="help-feedback-bar">
                              <span className="help-feedback-label">Was this helpful?</span>
                              <div className="d-flex gap-2">
                                <button
                                  type="button"
                                  className={`help-fb-btn ${feedback[faq.id] === "yes" ? "active-yes" : ""}`}
                                  onClick={() => handleFeedback(faq.id, "yes")}
                                  disabled={!!feedback[faq.id]}
                                >
                                  <FaThumbsUp size={11} className="me-1" /> Yes
                                </button>
                                <button
                                  type="button"
                                  className={`help-fb-btn ${feedback[faq.id] === "no" ? "active-no" : ""}`}
                                  onClick={() => handleFeedback(faq.id, "no")}
                                  disabled={!!feedback[faq.id]}
                                >
                                  <FaThumbsDown size={11} className="me-1" /> No
                                </button>
                              </div>
                            </div>
                          </div>
                        )}
                      </div>
                    );
                  })
                ) : (
                  <div className="p-4 text-center text-muted small">
                    No questions available for this category.
                  </div>
                )}
              </div>
            </div>
          )}
        </Container>
      </div>

      {/* =========================================================================
          MOBILE VIEW (Exact Match to Help Center Mobile, max-width: 767.98px)
          ========================================================================= */}
      <div className="d-block d-md-none">
        {/* Mobile Contact Information */}
        <section className="help-contact-section">
          <Container className="faq-container help-container">
            <div className="help-contact-cards-wrap">
              {/* Call us for Queries */}
              <div className="help-contact-card">
                <div className="help-contact-icon-box">
                  <FaHeadset className="help-c-icon" />
                </div>
                <div className="help-contact-info">
                  <h6 className="help-c-title">Call us for Queries</h6>
                  <p className="help-c-text mb-0">
                    Helpline no:- <a href="tel:+919818532463" className="help-c-link fw-semibold">+91-9818532463</a>
                    <span className="help-c-timing d-block">(Mon - Sat: 10:30 AM - 7:00 PM)</span>
                  </p>
                </div>
              </div>

              {/* E-Mail us */}
              <div className="help-contact-card">
                <div className="help-contact-icon-box">
                  <FaEnvelope className="help-c-icon" />
                </div>
                <div className="help-contact-info">
                  <h6 className="help-c-title">E-Mail us</h6>
                  <p className="help-c-text mb-0">
                    Sales enquiries and customer support:
                    <a href="mailto:support@printmont.com" className="help-c-link fw-semibold d-block">
                      support@printmont.com
                    </a>
                  </p>
                </div>
              </div>

              {/* Regd Address */}
              <div className="help-contact-card last-card">
                <div className="help-contact-icon-box">
                  <FaStore className="help-c-icon" />
                </div>
                <div className="help-contact-info">
                  <h6 className="help-c-title">Regd Address</h6>
                  <p className="help-c-text mb-0">
                    <strong className="text-dark">Printmont Corporation</strong>
                    <span className="d-block">3398, Bagichi Acchi ji, Bara Hindu Rao, Near Filmistan Cinema, Delhi India - 110006</span>
                  </p>
                </div>
              </div>
            </div>
          </Container>
        </section>

        {/* Mobile FAQs Section */}
        <section className="help-faqs-section">
          <Container className="faq-container help-container">
            <h2 className="help-section-heading mb-2">Frequently Asked Questions</h2>

            {loading ? (
              <div className="text-center py-5 bg-white rounded-3 border p-4">
                <Spinner animation="border" variant="primary" size="sm" />
                <p className="text-muted small mt-2 mb-0">Loading FAQs &amp; Help Topics...</p>
              </div>
            ) : failed || !categories.length ? (
              <div className="text-center py-5 bg-white rounded-3 border p-4">
                <h6 className="text-dark fw-bold mb-1">FAQs are currently unavailable</h6>
                <p className="text-muted small mb-0">Please try again later or reach out to our helpline.</p>
              </div>
            ) : (
              <div className="help-faq-content-wrap">
                {/* Mobile Dropdown Category Select */}
                <div className="help-dropdown-box mb-3">
                  <select
                    className="help-category-select"
                    value={currentCategory?.id || ""}
                    onChange={(e) => {
                      setSelectedCatId(e.target.value);
                      setOpenIds(new Set());
                    }}
                  >
                    {categories.map((cat) => (
                      <option key={cat.id} value={cat.id}>
                        {cat.name} {cat.faqs && cat.faqs.length > 0 ? `(${cat.faqs.length})` : ""}
                      </option>
                    ))}
                  </select>
                  <FaChevronDown className="help-select-arrow" />
                </div>

                {/* Mobile Accordion Box */}
                <div className="help-accordion-box">
                  {displayedFaqs && displayedFaqs.length > 0 ? (
                    displayedFaqs.map((faq, index) => {
                      const isOpen = openIds.has(faq.id);
                      const isLast = index === displayedFaqs.length - 1;

                      return (
                        <div
                          key={faq.id}
                          className={`help-accordion-row ${isOpen ? "is-expanded" : ""} ${isLast ? "is-last" : ""}`}
                        >
                          <button
                            type="button"
                            className="help-accordion-btn"
                            onClick={() => toggleFaq(faq.id)}
                            aria-expanded={isOpen}
                          >
                            <span className="help-q-text">{faq.question}</span>
                            <span className="help-toggle-symbol" aria-hidden="true">
                              {isOpen ? "−" : "+"}
                            </span>
                          </button>

                          {isOpen && (
                            <div className="help-answer-drawer">
                              <FormattedAnswer text={faq.answer} />

                              {/* Helpful feedback bar */}
                              <div className="help-feedback-bar">
                                <span className="help-feedback-label">Was this helpful?</span>
                                <div className="d-flex gap-2">
                                  <button
                                    type="button"
                                    className={`help-fb-btn ${feedback[faq.id] === "yes" ? "active-yes" : ""}`}
                                    onClick={() => handleFeedback(faq.id, "yes")}
                                    disabled={!!feedback[faq.id]}
                                  >
                                    <FaThumbsUp size={11} className="me-1" /> Yes
                                  </button>
                                  <button
                                    type="button"
                                    className={`help-fb-btn ${feedback[faq.id] === "no" ? "active-no" : ""}`}
                                    onClick={() => handleFeedback(faq.id, "no")}
                                    disabled={!!feedback[faq.id]}
                                  >
                                    <FaThumbsDown size={11} className="me-1" /> No
                                  </button>
                                </div>
                              </div>
                            </div>
                          )}
                        </div>
                      );
                    })
                  ) : (
                    <div className="p-4 text-center text-muted small">
                      No questions found for this category.
                    </div>
                  )}
                </div>
              </div>
            )}
          </Container>
        </section>
      </div>
    </div>
  );
};

export default PrintmontFAQ;
