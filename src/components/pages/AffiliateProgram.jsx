import React, { useEffect, useState, useMemo } from "react";
import { Container } from "react-bootstrap";
import { Link } from "react-router-dom";
import usePageSections from "./usePageSections";
import { updatePageSEO } from "../../utils/seo";
import PageHero from "../common/PageHero";
import {
  FaHandshake,
  FaPercentage,
  FaCalendarCheck,
  FaMoneyBillWave,
  FaCheckCircle,
  FaEnvelope,
  FaUserPlus,
  FaShareAlt,
  FaCoins,
  FaGift,
  FaTshirt,
  FaHeadset,
  FaAward,
  FaChevronDown,
  FaChevronUp,
  FaMugHot,
  FaIdCard,
  FaTags,
  FaArrowRight
} from "react-icons/fa";
import "./marketing-page.css";

/* Fallback default data ensuring high resiliency if API temporarily hiccups */
const FALLBACK_HERO = {
  title: "Printmont Affiliate & Partner Program",
  content: "Partner with India's premier custom printing and corporate gifting platform. Earn high commissions on every referral with 30-day tracking and guaranteed monthly payouts.",
  extra: "Join for free"
};

const FALLBACK_STEPS = [
  {
    id: "fb-s1",
    title: "Sign Up for Free",
    content: "Register in minutes and get your unique referral link once approved. No membership or signup fees required."
  },
  {
    id: "fb-s2",
    title: "Promote & Share",
    content: "Share your affiliate link on your website, blog, social media channels, or WhatsApp using our high-converting banners and coupon codes."
  },
  {
    id: "fb-s3",
    title: "Earn & Get Paid",
    content: "Track conversions in real-time with 30-day cookies and receive prompt monthly commission payouts directly to your bank account or UPI."
  }
];

const FALLBACK_HIGHLIGHTS = [
  {
    id: "fb-h1",
    title: "Up to 15% Commission",
    content: "Earn high competitive commissions on every completed purchase generated through your referral links."
  },
  {
    id: "fb-h2",
    title: "30-Day Cookie Window",
    content: "Long 30-day tracking cookie ensures you earn credit even if a customer buys weeks after clicking your link."
  },
  {
    id: "fb-h3",
    title: "Monthly Timely Payouts",
    content: "Hassle-free monthly commission disbursements directly to your verified Indian bank account or UPI."
  },
  {
    id: "fb-h4",
    title: "Free Banners & Coupons",
    content: "Access exclusive high-converting promotional banners, text links, and custom discount coupon codes."
  }
];

const FALLBACK_RATES = [
  { id: "fb-r1", title: "Custom Apparel & T-Shirts", extra: "12% - 15%", content: "Screen print, embroidery, and DTF printed corporate & fashion apparel" },
  { id: "fb-r2", title: "Corporate Gift Sets & Diaries", extra: "10% - 12%", content: "Executive combos, engraved pens, diaries, and welcome kits" },
  { id: "fb-r3", title: "Drinkware, Mugs & Bottles", extra: "10%", content: "Ceramic mugs, vacuum insulated flasks, and metal sippers" },
  { id: "fb-r4", title: "Marketing Print & Cards", extra: "8% - 10%", content: "Visiting cards, brochures, flyers, standees, and banners" },
  { id: "fb-r5", title: "Personalized Accessories", extra: "8%", content: "Keychains, badges, mousepads, tote bags, and tech gear" }
];

const FALLBACK_FAQS = [
  {
    id: "fb-f1",
    title: "Is there any cost to join the Printmont Affiliate Program?",
    content: "No. Joining is 100% free with no membership fees, minimum sales targets, or hidden charges at any stage."
  },
  {
    id: "fb-f2",
    title: "How are my sales and referral conversions tracked?",
    content: "Every affiliate receives a personalized tracking link. When customers click your link, our 30-day cookie window guarantees you earn credit for any order placed within 30 days."
  },
  {
    id: "fb-f3",
    title: "When and how do I get paid my commissions?",
    content: "Commissions are calculated monthly and transferred directly to your verified Indian bank account (NEFT/IMPS) or UPI ID upon reaching the minimum payout threshold."
  },
  {
    id: "fb-f4",
    title: "Can I promote Printmont on social media platforms?",
    content: "Yes! You may promote across Instagram, YouTube, Telegram, WhatsApp, LinkedIn, email newsletters, and personal blogs using our approved banners and links."
  }
];

const FALLBACK_CONTACT = {
  title: "To become our partner, please get in touch with our Affiliate Team.",
  content: "Our dedicated affiliate managers will help you get set up with custom referral links, promotional banners, and tailored coupon codes.",
  extra: "support@printmont.com"
};

const STEP_ICONS = [FaUserPlus, FaShareAlt, FaCoins];
const HIGHLIGHT_ICONS = [FaPercentage, FaCalendarCheck, FaMoneyBillWave, FaGift, FaAward, FaHeadset];
const RATE_ICONS = [FaTshirt, FaGift, FaMugHot, FaIdCard, FaTags];

const AffiliateProgram = () => {
  const { many, one, loading, failed } = usePageSections("affiliate");

  const rawHero = one("hero");
  const rawHighlights = many("highlight");
  const rawSteps = many("step");
  const rawRates = many("rate");
  const rawFaqs = many("faq");
  const rawContact = one("contact");

  // Merge API data with fallbacks
  const hero = rawHero || FALLBACK_HERO;
  const steps = rawSteps && rawSteps.length > 0 ? rawSteps : FALLBACK_STEPS;
  const highlights = rawHighlights && rawHighlights.length > 0 ? rawHighlights : FALLBACK_HIGHLIGHTS;
  const rates = rawRates && rawRates.length > 0 ? rawRates : FALLBACK_RATES;
  const faqs = rawFaqs && rawFaqs.length > 0 ? rawFaqs : FALLBACK_FAQS;
  const contact = rawContact || FALLBACK_CONTACT;

  // Interactive FAQ Accordion state
  const [openFaqId, setOpenFaqId] = useState(null);

  useEffect(() => {
    if (faqs && faqs.length > 0 && openFaqId === null) {
      setOpenFaqId(faqs[0].id);
    }
  }, [faqs, openFaqId]);

  const toggleFaq = (id) => {
    setOpenFaqId((prev) => (prev === id ? null : id));
  };

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: hero?.title ? `${hero.title} | Printmont` : "Affiliate Program - Printmont",
      description: hero?.content || "Partner with Printmont and earn up to 15% commission on every custom print and corporate gift order you refer.",
      keywords: "printmont affiliate program, print on demand affiliate india, referral partner program, corporate gifting commission, custom merchandise affiliate",
      type: "website"
    });
  }, [hero]);

  if (loading) {
    return (
      <div className="mk-page">
        {/* Shimmer Hero Header */}
        <div className="pm-page-hero">
          <div className="pm-page-hero-container">
            <div className="shimmer-bg rounded mb-3" style={{ height: 18, width: 220 }} />
            <div className="shimmer-bg rounded mb-2" style={{ height: 38, width: "55%" }} />
            <div className="shimmer-bg rounded mb-3" style={{ height: 22, width: "75%" }} />
            <div className="d-flex gap-2">
              <div className="shimmer-bg rounded" style={{ height: 32, width: 140 }} />
              <div className="shimmer-bg rounded" style={{ height: 32, width: 140 }} />
            </div>
          </div>
        </div>
        {/* Shimmer Content Body */}
        <Container className="mk-article">
          <div className="row g-3 mb-4">
            {[1, 2, 3].map((n) => (
              <div key={n} className="col-12 col-md-4">
                <div className="shimmer-bg rounded" style={{ height: 160 }} />
              </div>
            ))}
          </div>
          <div className="shimmer-bg rounded mb-4" style={{ height: 220 }} />
          <div className="shimmer-bg rounded" style={{ height: 180 }} />
        </Container>
      </div>
    );
  }

  return (
    <div className="mk-page">
      {/* ====================================================================
          1. Standard Reusable Hero Section (Rule 7, 8, 9 Compliant)
          Breadcrumbs strictly on Desktop, 1400px wide, brand styled
          ==================================================================== */}
      <PageHero
        breadcrumbs={[
          { label: "Home", path: "/" },
          { label: "Affiliate Program" }
        ]}
        tag="PRINTMONT PARTNER NETWORK"
        tagIcon={<FaHandshake />}
        title={hero?.title || "Printmont Affiliate & Partner Program"}
        titleIcon={<FaHandshake />}
        description={
          hero?.content ||
          "Partner with India's premier custom printing and corporate gifting platform. Earn attractive commissions on every referral with 30-day tracking and guaranteed monthly payouts."
        }
        stats={[
          { label: "Commission", value: "Up to 15%", icon: <FaPercentage /> },
          { label: "Cookie Window", value: "30 Days", icon: <FaCalendarCheck /> },
          { label: "Payout Schedule", value: "Monthly", icon: <FaMoneyBillWave /> },
          { label: "Joining Fee", value: "₹0 Free", icon: <FaCheckCircle /> }
        ]}
        actions={
          <a href="#band-contact" className="pm-hero-action-link" title="Contact Partner Desk">
            <FaEnvelope /> Join Partner Network
          </a>
        }
      />

      {/* ====================================================================
          Main Content Body (Rule 7: 1400px max-width, Rule 8: 24px/14px padding)
          ==================================================================== */}
      <Container className="mk-article">

        {/* ------------------------------------------------------------------
            Section 1: How It Works / Steps to Partner
            ------------------------------------------------------------------ */}
        <section className="mk-section">
          <div className="mk-section-header">
            <span className="mk-section-tag">HOW IT WORKS</span>
            <h2 className="mk-section-title">
              {steps.length} Simple Steps to Partner With Us
            </h2>
            <p className="mk-section-desc">
              Start earning with Printmont in three easy steps — from quick signup to seamless monthly bank transfers.
            </p>
          </div>

          <div className="mk-steps-grid">
            {steps.map((st, idx) => {
              const StepIcon = STEP_ICONS[idx % STEP_ICONS.length] || FaCheckCircle;
              const stepNumber = String(idx + 1).padStart(2, "0");

              return (
                <div className="mk-step-card" key={st.id || idx}>
                  <div className="mk-step-top">
                    <span className="mk-step-badge">{stepNumber}</span>
                    <span className="mk-step-icon">
                      <StepIcon />
                    </span>
                  </div>
                  <h3 className="mk-step-title">{st.title}</h3>
                  <p className="mk-step-desc">{st.content}</p>
                </div>
              );
            })}
          </div>
        </section>

        {/* ------------------------------------------------------------------
            Section 2: Benefits & Program Highlights
            ------------------------------------------------------------------ */}
        {highlights.length > 0 && (
          <section className="mk-section">
            <div className="mk-section-header">
              <span className="mk-section-tag">PROGRAM ADVANTAGES</span>
              <h2 className="mk-section-title">Why Partner With Printmont?</h2>
              <p className="mk-section-desc">
                Enjoy industry-leading commissions, transparent tracking, marketing creatives, and dedicated account support.
              </p>
            </div>

            <div className="mk-highlights-grid">
              {highlights.map((hl, idx) => {
                const HighlightIcon = HIGHLIGHT_ICONS[idx % HIGHLIGHT_ICONS.length] || FaCheckCircle;

                return (
                  <div className="mk-highlight-card" key={hl.id || idx}>
                    <div className="mk-highlight-icon-box">
                      <HighlightIcon />
                    </div>
                    <h3 className="mk-highlight-title">{hl.title}</h3>
                    <p className="mk-highlight-desc">{hl.content}</p>
                  </div>
                );
              })}
            </div>
          </section>
        )}

        {/* ------------------------------------------------------------------
            Section 3: Commission Rates by Product Category
            ------------------------------------------------------------------ */}
        {rates.length > 0 && (
          <section className="mk-section">
            <div className="mk-section-header">
              <span className="mk-section-tag">EARNING POTENTIAL</span>
              <h2 className="mk-section-title">Commission Rates by Product Category</h2>
              <p className="mk-section-desc">
                Earn competitive commission percentages across our complete catalog of custom apparel, corporate merchandise, and customized gifts.
              </p>
            </div>

            <div className="mk-rates-grid">
              {rates.map((rt, idx) => {
                const RateIcon = RATE_ICONS[idx % RATE_ICONS.length] || FaTags;

                return (
                  <div className="mk-rate-card" key={rt.id || idx}>
                    <div className="mk-rate-info">
                      <h3 className="mk-rate-cat">
                        <RateIcon className="text-primary me-2" style={{ fontSize: "0.95rem" }} />
                        {rt.title}
                      </h3>
                      {rt.content && <p className="mk-rate-note">{rt.content}</p>}
                    </div>
                    {rt.extra && (
                      <span className="mk-rate-pill">
                        {rt.extra}
                      </span>
                    )}
                  </div>
                );
              })}
            </div>
          </section>
        )}

        {/* ------------------------------------------------------------------
            Section 4: Frequently Asked Questions
            ------------------------------------------------------------------ */}
        {faqs.length > 0 && (
          <section className="mk-section">
            <div className="mk-section-header">
              <span className="mk-section-tag">HAVE QUESTIONS?</span>
              <h2 className="mk-section-title">Frequently Asked Questions</h2>
              <p className="mk-section-desc">
                Common questions about tracking cookies, payout cycles, accepted traffic channels, and affiliate requirements.
              </p>
            </div>

            <div className="mk-faqs-wrap">
              {faqs.map((fq, idx) => {
                const isOpen = openFaqId === fq.id;

                return (
                  <div className={`mk-faq-item ${isOpen ? "is-open" : ""}`} key={fq.id || idx}>
                    <button
                      type="button"
                      className="mk-faq-button"
                      onClick={() => toggleFaq(fq.id)}
                      aria-expanded={isOpen}
                    >
                      <div className="mk-faq-q-wrap">
                        <span className="mk-faq-q-icon">
                          <FaHandshake />
                        </span>
                        <span className="mk-faq-q">{fq.title}</span>
                      </div>
                      <span className="mk-faq-toggle">
                        {isOpen ? <FaChevronUp /> : <FaChevronDown />}
                      </span>
                    </button>

                    {isOpen && (
                      <div className="mk-faq-body">
                        {fq.content}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          </section>
        )}

        {/* ------------------------------------------------------------------
            Section 5: Closing CTA & Contact Desk (Admin controlled via #band-contact)
            ------------------------------------------------------------------ */}
        <div className="mk-closing-cta" id="band-contact">
          <div className="mk-cta-inner">
            <div className="mk-cta-text-col">
              <span className="mk-cta-pill">
                <FaHandshake /> PARTNER ONBOARDING
              </span>
              <h2 className="mk-cta-title">
                {contact?.title || "Ready to Start Earning with Printmont?"}
              </h2>
              <p className="mk-cta-desc">
                {contact?.content ||
                  "Our affiliate managers will help you get set up with custom referral links, promotional banners, and tailored coupon codes for your audience."}
              </p>
              <div className="mk-cta-features">
                <span className="mk-cta-feat-item">
                  <FaCheckCircle /> Free Registration
                </span>
                <span className="mk-cta-feat-item">
                  <FaCheckCircle /> 30-Day Cookies
                </span>
                <span className="mk-cta-feat-item">
                  <FaCheckCircle /> Timely Bank / UPI Payouts
                </span>
                <span className="mk-cta-feat-item">
                  <FaCheckCircle /> Dedicated Affiliate Desk
                </span>
              </div>
            </div>

            <div className="mk-cta-actions">
              <a
                href={`mailto:${contact?.extra || "support@printmont.com"}?subject=Printmont%20Affiliate%20Program%20Application`}
                className="btn-mk-cta-white"
                title="Email Printmont Affiliate Desk"
              >
                <FaEnvelope /> Apply via Email
              </a>
              <Link
                to="/contact"
                className="btn-mk-cta-outline"
                title="Contact Customer Support"
              >
                <FaHeadset /> 24x7 Support Desk
              </Link>
            </div>
          </div>
        </div>

      </Container>
    </div>
  );
};

export default AffiliateProgram;
