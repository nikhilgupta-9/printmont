import React, { useState, useEffect } from "react";
import { Accordion, Spinner } from "react-bootstrap";
import { toast } from "react-hot-toast";
import {
  FaBullhorn,
  FaCheckCircle,
  FaArrowRight,
  FaPhoneAlt,
  FaEnvelope,
  FaBullseye,
  FaWallet,
  FaChartLine,
  FaRocket,
  FaSearchDollar,
  FaAd,
  FaStar,
  FaQuoteLeft,
  FaUsers,
  FaMapMarkerAlt,
  FaBoxes,
  FaHeadset
} from "react-icons/fa";
import usePageSections from "../pages/usePageSections";
import useHomeBanners from "../home/hooks/useHomeBanners";
import ResponsiveHeroCarousel from "../home/banners/ResponsiveHeroCarousel";
import { API_ENDPOINTS, ASSET_URL } from "../../config/apiEndpoints";
import { updatePageSEO } from "../../utils/seo";
import "./advertising.css";

const BENEFIT_ICONS = [FaBullseye, FaWallet, FaChartLine, FaRocket];
const SOLUTION_ICONS = [FaSearchDollar, FaAd, FaStar];
const STAT_ICONS = [FaUsers, FaMapMarkerAlt, FaBoxes, FaHeadset];
const HERO_POINT_ICONS = [FaBullseye, FaWallet, FaHeadset, FaChartLine];

const splitParts = (value, count) => {
  const parts = String(value || "").split("|");
  return Array.from({ length: count }, (_, i) => (parts[i] || "").trim());
};

const resolveImg = (img) => {
  if (!img) return "";
  if (/^(https?:)?\/\//.test(img) || img.startsWith("/")) return img;
  return `${ASSET_URL}${img}`;
};

const Advertising = () => {
  const { many, one } = usePageSections("advertising");
  const { banners: topBanners } = useHomeBanners(`${API_ENDPOINTS.BANNERS}?section=advertising_top_banner`);
  const { banners: midBanners } = useHomeBanners(`${API_ENDPOINTS.BANNERS}?section=advertising_mid_banner`);

  const [heroMobile, setHeroMobile] = useState("");
  const [form, setForm] = useState({ name: "", business: "", mobile: "", email: "", interest: "", budget: "", message: "" });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: "Advertise with Printmont | Printmont Ads",
      description: "Grow your business with Printmont Ads — sponsored products, display banners and brand spotlights that reach shoppers ready to buy custom prints, gifts and merchandise.",
      keywords: "advertise on printmont, printmont ads, sponsored products, display advertising india, brand promotion",
      type: "website"
    });
  }, []);

  const headingOf = (key, defTitle, defHighlight, defIntro) => {
    const row = many("heading").find((r) => (r.extra || "").trim() === key);
    if (!row) return { title: defTitle, highlight: defHighlight, intro: defIntro };
    const [highlight, ...rest] = String(row.content || "").split("|");
    return { title: row.title, highlight: (highlight || "").trim(), intro: rest.join("|").trim() };
  };
  const renderHeading = (h) => (
    <div className="ad-section-head">
      <h2>
        {h.title} {h.highlight && <span>{h.highlight}</span>}
      </h2>
      {h.intro && <p>{h.intro}</p>}
    </div>
  );

  // ---- admin-managed content ----
  const hero = one("hero");
  const heroTitle = hero?.title || "Grow your business with Printmont Ads";
  const heroSub = hero?.content || "Put your brand in front of shoppers who are ready to buy custom prints, gifts and merchandise.";
  const [cta1, cta2] = splitParts(hero?.extra, 2);
  const heroCta = cta1 || "Start Advertising";
  const heroCta2 = cta2 || "Talk to our team";
  const heroFallbackImg = resolveImg(hero?.image_path);
  const heroPoints = many("hero_point").map((r) => r.title);
  const stats = many("stat").map((r) => ({ value: r.title, label: r.content }));
  const benefits = many("benefit").map((r) => ({ title: r.title, desc: r.content }));
  const solutions = many("solution").map((r) => {
    const [desc, ...rest] = String(r.content || "").split("|");
    return {
      title: r.title,
      tag: r.extra,
      desc: (desc || "").trim(),
      features: rest.join("|").split("\n").map((f) => f.trim()).filter(Boolean),
      img: resolveImg(r.image_path)
    };
  });
  const steps = many("step").map((r) => ({ title: r.title, desc: r.content }));
  const stories = many("story").map((r) => {
    const [business, result] = splitParts(r.extra, 2);
    return { name: r.title, quote: r.content, business, result, img: resolveImg(r.image_path) };
  });
  const faqs = many("faq").map((r) => ({ q: r.title, a: r.content }));
  const budgets = many("budget").map((r) => r.title);
  const submitLabel = many("label").find((r) => (r.extra || "").trim() === "form_submit")?.title || "Request a callback";
  const contact = one("contact");
  const [contactPhone, contactEmail] = splitParts(contact?.extra, 2);

  const scrollToForm = () => {
    document.getElementById("ad-enquiry")?.scrollIntoView({ behavior: "smooth" });
  };

  const handleHeroStart = (e) => {
    e.preventDefault();
    setForm((prev) => ({ ...prev, mobile: heroMobile.trim() }));
    scrollToForm();
  };

  const chooseSolution = (title) => {
    setForm((prev) => ({ ...prev, interest: title }));
    scrollToForm();
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!form.name.trim() || !form.mobile.trim() || !form.email.trim()) {
      toast.error("Please fill in your name, mobile number and email address.");
      return;
    }
    try {
      setIsSubmitting(true);
      const payload = {
        full_name: form.name.trim(),
        contact_number: form.mobile.trim(),
        email_id: form.email.trim(),
        company_name: form.business.trim() || "Not specified",
        budget: form.budget || "Not specified",
        product_requirement: `Advertising enquiry. Interested in: ${form.interest || "Not sure yet"}. Notes: ${form.message.trim() || "None"}`,
        source_page: "advertising",
        device_type: window.innerWidth < 992 ? "mobile" : "desktop"
      };
      const res = await fetch(API_ENDPOINTS.LEADS_SUBMIT, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!data?.success) {
        toast.error(data?.message || "Could not send your enquiry. Please try again.");
        return;
      }
      setSubmitted(true);
      toast.success("Thanks! Our advertising team will get in touch shortly.");
    } catch {
      toast.error("Could not send your enquiry. Please check your connection and try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="ad-page">
      {/* Top banner — Admin → Page Banners → Advertising → Top Banner */}
      {(topBanners.length > 0 || heroFallbackImg) && (
        <div className="ad-banner-wrap">
          {topBanners.length > 0 ? (
            <ResponsiveHeroCarousel banners={topBanners} carouselId="adTopCarousel" />
          ) : (
            <img src={heroFallbackImg} alt="Printmont Ads" className="ad-banner-fallback" />
          )}
        </div>
      )}

      {/* Hero */}
      <section className="ad-hero">
        <div className="ad-container ad-hero-grid">
          <div>
            <span className="ad-eyebrow"><FaBullhorn /> Printmont Ads</span>
            <h1 className="ad-hero-title">{heroTitle}</h1>
            <p className="ad-hero-sub">{heroSub}</p>
            <div className="ad-hero-actions">
              <button type="button" className="ad-btn" onClick={scrollToForm}>
                {heroCta} <FaArrowRight />
              </button>
              <button type="button" className="ad-btn-outline" onClick={scrollToForm}>
                {heroCta2}
              </button>
            </div>
            {heroPoints.length > 0 && (
              <div className="ad-hero-points">
                {heroPoints.map((text, idx) => {
                  const Icon = HERO_POINT_ICONS[idx % HERO_POINT_ICONS.length];
                  return (
                    <div className="ad-hero-point" key={idx}>
                      <Icon /> <span>{text}</span>
                    </div>
                  );
                })}
              </div>
            )}
          </div>

          <aside className="ad-quick-card">
            <h3>Start advertising today</h3>
            <p>Enter your mobile number and our team will help you set up your first campaign.</p>
            <form onSubmit={handleHeroStart}>
              <div className="ad-phone">
                <span>+91</span>
                <input
                  type="tel"
                  required
                  maxLength="10"
                  placeholder="10-digit mobile number"
                  value={heroMobile}
                  onChange={(e) => setHeroMobile(e.target.value.replace(/\D/g, ""))}
                />
              </div>
              <button type="submit" className="ad-btn ad-btn-full">
                {heroCta} <FaArrowRight />
              </button>
            </form>
            <ul className="ad-ticks">
              <li><FaCheckCircle /> Flexible budgets</li>
              <li><FaCheckCircle /> Dedicated campaign manager</li>
              <li><FaCheckCircle /> Regular performance reports</li>
            </ul>
          </aside>
        </div>
      </section>

      {/* Stats */}
      {stats.length > 0 && (
        <div className="ad-container">
          <div className="ad-stats">
            {stats.map((stat, idx) => {
              const Icon = STAT_ICONS[idx % STAT_ICONS.length];
              return (
                <div className="ad-stat" key={idx}>
                  <span className="ad-stat-icon"><Icon /></span>
                  <div>
                    <div className="ad-stat-value">{stat.value}</div>
                    <div className="ad-stat-label">{stat.label}</div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      <div className="ad-container">
        {/* Why advertise */}
        {benefits.length > 0 && (
          <section className="ad-section">
            {renderHeading(headingOf("why", "Why advertise on", "Printmont?", ""))}
            <div className="ad-grid ad-grid-4">
              {benefits.map((b, idx) => {
                const Icon = BENEFIT_ICONS[idx % BENEFIT_ICONS.length];
                return (
                  <div className="ad-card" key={idx}>
                    <span className="ad-icon"><Icon /></span>
                    <h3>{b.title}</h3>
                    <p>{b.desc}</p>
                  </div>
                );
              })}
            </div>
          </section>
        )}

        {/* Ad solutions */}
        {solutions.length > 0 && (
          <section className="ad-section">
            {renderHeading(headingOf("solutions", "Advertising solutions for", "every goal", ""))}
            <div className="ad-grid ad-grid-3">
              {solutions.map((sol, idx) => {
                const Icon = SOLUTION_ICONS[idx % SOLUTION_ICONS.length];
                return (
                  <div className="ad-card ad-solution" key={idx}>
                    {sol.tag && <span className="ad-tag">{sol.tag}</span>}
                    {sol.img ? (
                      <img className="ad-solution-img" src={sol.img} alt={sol.title} loading="lazy" />
                    ) : (
                      <span className="ad-icon"><Icon /></span>
                    )}
                    <h3>{sol.title}</h3>
                    <p>{sol.desc}</p>
                    {sol.features.length > 0 && (
                      <ul className="ad-features">
                        {sol.features.map((f, i) => (
                          <li key={i}><FaCheckCircle /> <span>{f}</span></li>
                        ))}
                      </ul>
                    )}
                    <button type="button" className="ad-btn-outline ad-btn-full" onClick={() => chooseSolution(sol.title)}>
                      Get started <FaArrowRight />
                    </button>
                  </div>
                );
              })}
            </div>
          </section>
        )}

        {/* How it works */}
        {steps.length > 0 && (
          <section className="ad-section">
            {renderHeading(headingOf("steps", "How advertising works", "on Printmont", ""))}
            <div className="ad-steps">
              {steps.map((step, idx) => (
                <div className="ad-step" key={idx}>
                  <span className="ad-step-num">{idx + 1}</span>
                  <h3>{step.title}</h3>
                  <p>{step.desc}</p>
                </div>
              ))}
            </div>
          </section>
        )}

        {/* Mid banner — Admin → Page Banners → Advertising → Mid Banner */}
        {midBanners.length > 0 && (
          <section className="ad-section ad-mid-banner">
            <ResponsiveHeroCarousel banners={midBanners} carouselId="adMidCarousel" />
          </section>
        )}

        {/* Stories (hidden until the admin adds some) */}
        {stories.length > 0 && (
          <section className="ad-section">
            {renderHeading(headingOf("stories", "Advertiser", "stories", ""))}
            <div className="ad-grid ad-grid-3">
              {stories.map((story, idx) => (
                <div className="ad-card ad-story" key={idx}>
                  <FaQuoteLeft className="ad-quote" />
                  <p className="ad-story-text">{story.quote}</p>
                  {story.result && <div className="ad-story-result">{story.result}</div>}
                  <div className="ad-story-author">
                    {story.img ? <img src={story.img} alt={story.name} /> : <span className="ad-avatar">{(story.name || "?").charAt(0)}</span>}
                    <div>
                      <strong>{story.name}</strong>
                      {story.business && <span>{story.business}</span>}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </section>
        )}

        {/* FAQ */}
        {faqs.length > 0 && (
          <section className="ad-section">
            {renderHeading(headingOf("faqs", "Frequently asked", "questions", ""))}
            <div className="ad-faq">
              <Accordion defaultActiveKey="0">
                {faqs.map((faq, idx) => (
                  <Accordion.Item eventKey={String(idx)} key={idx}>
                    <Accordion.Header>{faq.q}</Accordion.Header>
                    <Accordion.Body>{faq.a}</Accordion.Body>
                  </Accordion.Item>
                ))}
              </Accordion>
            </div>
          </section>
        )}

        {/* Enquiry */}
        <section className="ad-section" id="ad-enquiry">
          <div className="ad-enquiry">
            <div className="ad-enquiry-form">
              {renderHeading(headingOf("form", "Let's grow your business", "together", "Tell us about your business and we will get back to you with a plan."))}

              {submitted ? (
                <div className="ad-success">
                  <FaCheckCircle />
                  <h3>Enquiry received</h3>
                  <p>Thank you! Our advertising team will contact you shortly to plan your campaign.</p>
                </div>
              ) : (
                <form onSubmit={handleSubmit}>
                  <div className="ad-form-row">
                    <input className="ad-input" name="name" placeholder="Your name *" value={form.name} onChange={handleChange} required />
                    <input className="ad-input" name="business" placeholder="Business / brand name" value={form.business} onChange={handleChange} />
                  </div>
                  <div className="ad-form-row">
                    <input
                      className="ad-input"
                      name="mobile"
                      type="tel"
                      maxLength="10"
                      placeholder="Mobile number *"
                      value={form.mobile}
                      onChange={(e) => setForm((p) => ({ ...p, mobile: e.target.value.replace(/\D/g, "") }))}
                      required
                    />
                    <input className="ad-input" name="email" type="email" placeholder="Email address *" value={form.email} onChange={handleChange} required />
                  </div>
                  <div className="ad-form-row">
                    <select className="ad-input" name="interest" value={form.interest} onChange={handleChange}>
                      <option value="">I'm interested in…</option>
                      {solutions.map((s) => (
                        <option key={s.title} value={s.title}>{s.title}</option>
                      ))}
                      <option value="Not sure yet">Not sure yet</option>
                    </select>
                    <select className="ad-input" name="budget" value={form.budget} onChange={handleChange}>
                      <option value="">Monthly ad budget</option>
                      {budgets.map((b) => (
                        <option key={b} value={b}>{b}</option>
                      ))}
                    </select>
                  </div>
                  <textarea className="ad-input" name="message" rows="3" placeholder="Tell us about your products and goals (optional)" value={form.message} onChange={handleChange} />
                  <button type="submit" className="ad-btn ad-btn-full" disabled={isSubmitting}>
                    {isSubmitting ? <Spinner animation="border" size="sm" /> : submitLabel}
                  </button>
                </form>
              )}
            </div>

            {(contactPhone || contactEmail) && (
              <aside className="ad-contact">
                <h3>{contact?.title || "Prefer to talk?"}</h3>
                {contactPhone && (
                  <a href={`tel:${contactPhone.replace(/[^+\d]/g, "")}`}>
                    <FaPhoneAlt /> <span>{contactPhone}</span>
                  </a>
                )}
                {contactEmail && (
                  <a href={`mailto:${contactEmail}`}>
                    <FaEnvelope /> <span>{contactEmail}</span>
                  </a>
                )}
              </aside>
            )}
          </div>
        </section>
      </div>
    </div>
  );
};

export default Advertising;
