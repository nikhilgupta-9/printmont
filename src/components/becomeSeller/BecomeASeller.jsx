import React, { useState, useEffect } from "react";
import { Row, Col, Accordion, Modal, Spinner } from "react-bootstrap";
import { Link } from "react-router-dom";
import {
  FaStore,
  FaTruck,
  FaMoneyBillWave,
  FaPercentage,
  FaChartLine,
  FaHeadset,
  FaShieldAlt,
  FaBoxOpen,
  FaCheckCircle,
  FaArrowRight,
  FaCalculator,
  FaUsers,
  FaMapMarkerAlt,
  FaClock,
  FaFileInvoiceDollar,
  FaUniversity,
  FaMobileAlt,
  FaEnvelope,
  FaPhoneAlt,
  FaDownload,
  FaStar,
  FaQuoteLeft,
  FaTshirt,
  FaCoffee,
  FaGift,
  FaAddressCard,
  FaBoxes,
  FaTools,
  FaBarcode,
  FaShippingFast,
  FaHandHoldingUsd,
  FaCheckDouble,
  FaRocket,
  FaBullhorn,
  FaGraduationCap
} from "react-icons/fa";
import { toast } from "react-hot-toast";
import { API_ENDPOINTS, ASSET_URL, assetUrl } from "../../config/apiEndpoints";
import usePageSections from "../pages/usePageSections";
import useHomeBanners from "../home/hooks/useHomeBanners";
import ResponsiveHeroCarousel from "../home/banners/ResponsiveHeroCarousel";
import { updatePageSEO } from "../../utils/seo";
import "./become-seller.css";

// Fee Calculator Categories with base delivery & handling
const CALCULATOR_CATEGORIES = [
  { id: "apparel", name: "Custom T-Shirts & Apparel", defaultPrice: 599, shipping: 59 },
  { id: "mugs", name: "Ceramic Mugs & Drinkware", defaultPrice: 349, shipping: 49 },
  { id: "corporate", name: "Corporate Gift Sets", defaultPrice: 1299, shipping: 89 },
  { id: "stationery", name: "Visiting Cards & Stationery", defaultPrice: 449, shipping: 45 },
  { id: "packaging", name: "Custom Boxes & Packaging", defaultPrice: 899, shipping: 79 },
  { id: "acrylic", name: "Photo Frames & Acrylic Prints", defaultPrice: 699, shipping: 65 }
];


const DEFAULT_BENEFITS = [
  { title: "0% Marketplace Commission", desc: "Keep 100% of your product selling price. We do not deduct commission percentages like other platforms (15%-25%)." },
  { title: "Doorstep Pan-India Pickup", desc: "Automated courier pickup by Delhivery, BlueDart, and DTDC directly from your workshop or warehouse across 19,000+ pincodes." },
  { title: "7-Day Fast Bank Settlements", desc: "Receive your cleared earnings directly in your bank account via NEFT/RTGS every 7 days from order delivery date." },
  { title: "₹0 Setup & Listing Fees", desc: "No registration fees, no monthly software charges, and unlimited free product listings. Start selling with zero risk." },
  { title: "Dedicated Account Manager", desc: "Get an expert e-commerce account manager on WhatsApp & phone to assist with catalog uploads, pricing, and orders." },
  { title: "Printmont Seller Shield", desc: "100% protection against fraudulent returns and transit damages. Rest easy knowing your products and capital are secure." }
];

const DEFAULT_STEPS = [
  { title: "Register Online", desc: "Submit your basic contact, business name, and GSTIN or PAN details in 2 minutes." },
  { title: "List Your Products", desc: "Upload your customized merchandise catalog, images, and set your desired prices." },
  { title: "Receive & Pack", desc: "When an order arrives, print the 1-click shipping label and pack the parcel securely." },
  { title: "Doorstep Pickup & Pay", desc: "Courier rider picks up the parcel. Receive payment in your bank within 7 days." }
];

const DEFAULT_STATS = [
  { value: "50L+", label: "Active Buyers" },
  { value: "19,000+", label: "Pincodes Pickup" },
  { value: "8,500+", label: "Active Sellers" },
  { value: "0%", label: "Commission" },
  { value: "7 Days", label: "Direct Settlement" }
];

const DEFAULT_CATEGORIES = [
  { title: "Custom T-Shirts & Apparel", desc: "Oversized graphic tees, polo shirts, hoodies, and corporate uniforms. High demand across youth & startups.", tag: "35-50% Margin" },
  { title: "Ceramic Mugs & Drinkware", desc: "White gloss mugs, magic color-changing mugs, steel tumblers, and personalized sippers for gifts.", tag: "40-55% Margin" },
  { title: "Corporate Gift Sets", desc: "Executive diaries, engraved pens, power banks, and employee welcome kits for corporate clients.", tag: "45-60% Margin" },
  { title: "Visiting Cards & Stationery", desc: "Spot UV visiting cards, textured paper cards, letterheads, and customized bill books for businesses.", tag: "30-45% Margin" },
  { title: "Custom Boxes & Packaging", desc: "Rigid gift boxes, corrugated shipping mailers, branded courier tapes, and printed butter paper.", tag: "35-50% Margin" },
  { title: "Photo Frames & Acrylic Prints", desc: "Custom LED song plaques, engraved photo frames, 3D illusion lamps, and customized wall clocks.", tag: "40-60% Margin" }
];

const DEFAULT_DOCUMENTS = [
  { title: "Active GSTIN / PAN", desc: "GSTIN for taxable goods or PAN card for composition / non-taxable products." },
  { title: "Active Bank Account", desc: "Cancelled cheque or bank statement in your business name for 7-day NEFT settlements." },
  { title: "Mobile & Email", desc: "Active phone number for instant WhatsApp order alerts and email for invoices." },
  { title: "1+ Custom Product", desc: "At least one unique customized product ready to photograph and list online." }
];

const DEFAULT_FAQS = [
  { q: "Is it truly 0% commission on Printmont? Are there hidden fees?", a: "Yes, absolutely. Printmont operates on a strict 0% marketplace commission model for all product categories. There are zero listing fees, zero setup fees, and zero monthly subscriptions. Only standard doorstep logistics and nominal 2% payment gateway fees are deducted. You keep 100% of your earnings." },
  { q: "How and when do I receive payment for orders?", a: "Payments are credited directly to your registered bank account via NEFT/RTGS on a guaranteed 7-day payment cycle from the date of successful order delivery. You can track all cleared, pending, and scheduled payouts in real-time inside your Seller Dashboard." },
  { q: "Do I need a GST number to sell on Printmont?", a: "As per Indian e-commerce laws, an active GSTIN is required for selling taxable goods across states. However, if you are selling exempt categories or operating under specific composition thresholds, you can initiate registration using your PAN card while applying for GSTIN." },
  { q: "How does shipping and doorstep courier pickup work?", a: "You do NOT need to visit any courier office. When an order arrives, generate a shipping label with one click on your Seller Dashboard. Attach the label to your securely packed parcel. Our integrated courier partners will arrive at your workshop to scan and pick up the package." },
  { q: "Who handles customer returns (RTO) and transit damages?", a: "All shipments on Printmont are protected under the Printmont Seller Shield. If a customized product is damaged in transit or lost by the courier, Printmont investigates and reimburses the seller." }
];

const BENEFIT_ICONS = [FaPercentage, FaTruck, FaClock, FaMoneyBillWave, FaHeadset, FaShieldAlt];
const STEP_ICONS = [FaAddressCard, FaBoxes, FaBarcode, FaShippingFast, FaHandHoldingUsd];
const STAT_ICONS = [FaUsers, FaMapMarkerAlt, FaStore, FaPercentage, FaClock];
const CATEGORY_ICONS = [FaTshirt, FaCoffee, FaGift, FaAddressCard, FaBoxes, FaTools];
const DOCUMENT_ICONS = [FaFileInvoiceDollar, FaUniversity, FaMobileAlt, FaBoxOpen];
const TOOL_ICONS = [FaRocket, FaBullhorn, FaGift, FaGraduationCap, FaHeadset, FaStore];
const HERO_POINT_ICONS = [FaPercentage, FaTruck, FaClock, FaHeadset];

const resolveImg = (img) => {
  if (!img) return "";
  if (/^(https?:)?\/\//.test(img) || img.startsWith("/")) return img;
  return assetUrl(img);
};

const BecomeASeller = () => {
  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: "Sell Online on Printmont at 0% Commission | Seller Hub",
      description: "Become a marketplace seller on Printmont. Reach 50+ Lakh buyers with 0% commission, doorstep courier pickup across 19,000+ pincodes, guaranteed 7-day bank payouts, and dedicated seller support.",
      keywords: "sell on printmont, become a seller, print on demand seller marketplace, 0 commission marketplace india, sell custom printing online, printmont seller hub",
      type: "website"
    });
  }, []);

  // Admin-managed content (Admin → Website Pages → Become a Seller) and banners (Admin → Page Banners)
  const { many, one } = usePageSections("become-a-seller");
  const { banners: topBanners } = useHomeBanners(`${API_ENDPOINTS.BANNERS}?section=become_seller_top_banner`);
  const { banners: midBanners } = useHomeBanners(`${API_ENDPOINTS.BANNERS}?section=become_seller_mid_banner`);

  // Hero sign-up card (mobile number carries over into the full registration form)
  const [heroMobile, setHeroMobile] = useState("");

  // "We are happy to help you" query form
  const [helpData, setHelpData] = useState({ fullName: "", mobile: "", email: "", topic: "", message: "" });
  const [isHelpSubmitting, setIsHelpSubmitting] = useState(false);

  // Main Seller Onboarding Form State
  const [formData, setFormData] = useState({
    fullName: "",
    businessName: "",
    mobile: "",
    email: "",
    city: "",
    state: "",
    category: "Custom T-Shirts & Apparel",
    hasGstin: "Yes, I have an active GSTIN",
    monthlyVolume: "50 - 200 orders/month",
    message: ""
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitSuccess, setSubmitSuccess] = useState(false);

  // Modals state
  const [showCallbackModal, setShowCallbackModal] = useState(false);
  const [showGuideModal, setShowGuideModal] = useState(false);

  const [callbackData, setCallbackData] = useState({
    fullName: "",
    mobile: "",
    city: ""
  });
  const [isCallbackSubmitting, setIsCallbackSubmitting] = useState(false);

  const [guideData, setGuideData] = useState({
    fullName: "",
    email: "",
    mobile: ""
  });
  const [isGuideSubmitting, setIsGuideSubmitting] = useState(false);

  // Profit Calculator State
  const [selectedCategory, setSelectedCategory] = useState(CALCULATOR_CATEGORIES[0]);
  const [sellingPrice, setSellingPrice] = useState(599);

  // Fee calculation logic
  const commission = 0; // 0% Commission on Printmont!
  const shippingFee = selectedCategory.shipping;
  const pgFee = Math.round(sellingPrice * 0.02); // 2% Payment Gateway Fee
  const netEarnings = Math.max(0, sellingPrice - commission - shippingFee - pgFee);
  const profitMarginPercent = sellingPrice > 0 ? ((netEarnings / sellingPrice) * 100).toFixed(1) : 0;

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const scrollToForm = () => {
    const formElement = document.getElementById("seller-registration-form");
    if (formElement) {
      formElement.scrollIntoView({ behavior: "smooth" });
    }
  };

  const scrollToCalculator = () => {
    const calcElement = document.getElementById("profit-calculator");
    if (calcElement) {
      calcElement.scrollIntoView({ behavior: "smooth" });
    }
  };

  const handleCategorySelect = (categoryName) => {
    setFormData((prev) => ({ ...prev, category: categoryName }));
    scrollToForm();
  };

  // Submit main seller application to backend leads-api.php
  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!formData.fullName.trim() || !formData.mobile.trim() || !formData.email.trim()) {
      toast.error("Please fill in your name, mobile number, and email address.");
      return;
    }

    try {
      setIsSubmitting(true);
      const payload = {
        full_name: formData.fullName.trim(),
        contact_number: formData.mobile.trim(),
        email_id: formData.email.trim(),
        company_name: `${formData.businessName ? formData.businessName.trim() + " | " : ""}${formData.city ? formData.city.trim() + ", " : ""}${formData.state ? formData.state.trim() : "India"}`,
        budget: `GST: ${formData.hasGstin} | Vol: ${formData.monthlyVolume}`,
        product_requirement: `Marketplace Seller Application: Business: ${formData.businessName || "Not specified"}. Category: ${formData.category}. GST Status: ${formData.hasGstin}. Monthly Vol: ${formData.monthlyVolume}. Notes: ${formData.message || "Wants to sell on Printmont Online Marketplace"}`,
        source_page: "become-a-seller",
        device_type: window.innerWidth < 992 ? "mobile" : "desktop"
      };

      const endpoint = API_ENDPOINTS.LEADS_SUBMIT || "http://localhost/printmont/printmont-backend/api/leads-api.php";
      const res = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (data && data.success) {
        setSubmitSuccess(true);
        toast.success("Congratulations! Your seller registration has been received.");
      } else {
        toast.error(data.message || "Failed to submit application. Please try again.");
      }
    } catch (err) {
      console.error("Seller onboarding submit error:", err);
      toast.error("Could not send your application. Please check your connection and try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

  // Submit quick callback modal
  const handleCallbackSubmit = async (e) => {
    e.preventDefault();
    if (!callbackData.fullName.trim() || !callbackData.mobile.trim()) {
      toast.error("Please enter your name and mobile number.");
      return;
    }

    try {
      setIsCallbackSubmitting(true);
      const payload = {
        full_name: callbackData.fullName.trim(),
        contact_number: callbackData.mobile.trim(),
        email_id: "seller-callback@printmont.com",
        company_name: callbackData.city ? `City: ${callbackData.city}` : "Seller Prospect",
        budget: "Quick Seller Callback Request",
        product_requirement: "Prospect requested a callback from Printmont Seller Onboarding Director.",
        source_page: "seller-callback-modal",
        device_type: window.innerWidth < 992 ? "mobile" : "desktop"
      };

      const endpoint = API_ENDPOINTS.LEADS_SUBMIT || "http://localhost/printmont/printmont-backend/api/leads-api.php";
      const res = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!data?.success) {
        toast.error(data?.message || "Could not submit your request. Please try again.");
        return;
      }

      toast.success("Callback request submitted! Our onboarding advisor will call you shortly.");
      setShowCallbackModal(false);
      setCallbackData({ fullName: "", mobile: "", city: "" });
    } catch {
      toast.error("Could not submit your request. Please check your connection and try again.");
    } finally {
      setIsCallbackSubmitting(false);
    }
  };

  // Submit seller handbook download modal
  const handleGuideSubmit = async (e) => {
    e.preventDefault();
    if (!guideData.fullName.trim() || !guideData.email.trim() || !guideData.mobile.trim()) {
      toast.error("Please fill in your name, email, and mobile number.");
      return;
    }

    try {
      setIsGuideSubmitting(true);
      const payload = {
        full_name: guideData.fullName.trim(),
        contact_number: guideData.mobile.trim(),
        email_id: guideData.email.trim(),
        company_name: "Handbook Download Prospect",
        budget: "Seller Handbook Download",
        product_requirement: "Seller downloaded Printmont Complete Seller Growth & Payout Guidebook (PDF).",
        source_page: "seller-guide-modal",
        device_type: window.innerWidth < 992 ? "mobile" : "desktop"
      };

      const endpoint = API_ENDPOINTS.LEADS_SUBMIT || "http://localhost/printmont/printmont-backend/api/leads-api.php";
      const res = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!data?.success) {
        toast.error(data?.message || "Could not submit your request. Please try again.");
        return;
      }

      toast.success("Success! The Seller Handbook has been sent to your email.");
      setShowGuideModal(false);
      setGuideData({ fullName: "", email: "", mobile: "" });
    } catch {
      toast.error("Could not submit your request. Please check your connection and try again.");
    } finally {
      setIsGuideSubmitting(false);
    }
  };

  const handleHeroStart = (e) => {
    e.preventDefault();
    setFormData((prev) => ({ ...prev, mobile: heroMobile.trim() }));
    scrollToForm();
  };

  const handleHelpSubmit = async (e) => {
    e.preventDefault();
    if (!helpData.fullName.trim() || !helpData.mobile.trim() || !helpData.email.trim()) {
      toast.error("Please fill in your name, mobile number, and email address.");
      return;
    }
    try {
      setIsHelpSubmitting(true);
      const payload = {
        full_name: helpData.fullName.trim(),
        contact_number: helpData.mobile.trim(),
        email_id: helpData.email.trim(),
        company_name: "Seller Query",
        budget: helpData.topic || "General",
        product_requirement: `Seller query${helpData.topic ? ` (${helpData.topic})` : ""}: ${helpData.message || "No message"}`,
        source_page: "become-a-seller-query",
        device_type: window.innerWidth < 992 ? "mobile" : "desktop"
      };
      const endpoint = API_ENDPOINTS.LEADS_SUBMIT || "http://localhost/printmont/printmont-backend/api/leads-api.php";
      const res = await fetch(endpoint, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload) });
      const data = await res.json();
      if (!data?.success) {
        toast.error(data?.message || "Could not send your query. Please try again.");
        return;
      }
      toast.success("Thanks! Our seller support team will get back to you shortly.");
      setHelpData({ fullName: "", mobile: "", email: "", topic: "", message: "" });
    } catch {
      toast.error("Could not send your query. Please check your connection and try again.");
    } finally {
      setIsHelpSubmitting(false);
    }
  };

  // ---------- Admin-managed content with built-in fallbacks ----------
  const headingOf = (key, defTitle, defHighlight, defIntro) => {
    const row = many("heading").find((r) => (r.extra || "").trim() === key);
    if (!row) return { title: defTitle, highlight: defHighlight, intro: defIntro };
    const [highlight, ...rest] = String(row.content || "").split("|");
    return { title: row.title, highlight: (highlight || "").trim(), intro: rest.join("|").trim() };
  };
  const labelOf = (key, fallback) => many("label").find((r) => (r.extra || "").trim() === key)?.title || fallback;
  const asideOf = (key) => many("aside").find((r) => (r.extra || "").trim() === key);

  const cmsHero = one("hero");
  const heroTitle = cmsHero?.title || "Sell your custom merchandise across India with Printmont";
  const heroSub = cmsHero?.content || "Reach over 50 Lakh+ active buyers with ₹0 registration fee, automated courier pickups and guaranteed 7-day bank payouts.";
  const heroButton = cmsHero?.extra || "Start Selling";
  const heroFallbackImg = resolveImg(cmsHero?.image_path) || "/seller-hub-hero.jpg";
  const cmsHeroPoints = many("hero_point");
  const heroPoints = cmsHeroPoints.length ? cmsHeroPoints.map((r) => r.title) : ["0% Marketplace Commission", "19,000+ Pincodes Pickup", "Fast 7-Day Bank Payouts", "Dedicated Account Manager"];

  const cmsStats = many("stat");
  const statItems = cmsStats.length ? cmsStats.map((r) => ({ value: r.title, label: r.content })) : DEFAULT_STATS;

  const cmsBenefits = many("benefit");
  const benefits = cmsBenefits.length ? cmsBenefits.map((r) => ({ title: r.title, desc: r.content, img: resolveImg(r.image_path) })) : DEFAULT_BENEFITS;
  const whyAside = asideOf("why");

  const cmsJourney = many("journey");
  const journeySteps = cmsJourney.length ? cmsJourney.map((r) => ({ title: r.title, desc: r.content, img: resolveImg(r.image_path) })) : DEFAULT_STEPS;

  const cmsCategories = many("category");
  const categories = cmsCategories.length ? cmsCategories.map((r) => ({ title: r.title, desc: r.content, tag: r.extra })) : DEFAULT_CATEGORIES;

  const tools = many("tool").map((r) => ({ title: r.title, desc: r.content, link: (r.extra || "").trim(), img: resolveImg(r.image_path) }));
  const platformSlides = many("platform").map((r) => ({ title: r.title, desc: r.content, button: r.extra, img: resolveImg(r.image_path) }));
  const stories = many("story").map((r) => ({ name: r.title, quote: r.content, business: r.extra, img: resolveImg(r.image_path) }));

  const cmsDocs = many("document");
  const documents = cmsDocs.length ? cmsDocs.map((r) => ({ title: r.title, desc: r.content })) : DEFAULT_DOCUMENTS;

  const cmsFaqs = many("faq");
  const faqs = cmsFaqs.length ? cmsFaqs.map((r) => ({ q: r.title, a: r.content })) : DEFAULT_FAQS;

  const topics = many("topic").map((r) => r.title);
  const helpAside = asideOf("help");

  const renderHeading = (h, alignClass = "text-center") => (
    <div className={`section-head ${alignClass}`}>
      <h2 className="section-title">
        {h.title} {h.highlight && <span className="highlight-theme">{h.highlight}</span>}
      </h2>
      {h.intro && <p className="section-subtitle">{h.intro}</p>}
    </div>
  );

  return (
    <div className="seller-page-wrapper">

      {/* =================================================================
          TOP BANNER — Admin → Page Banners → Become a Seller → Top Banner (desktop + mobile images)
          ================================================================= */}
      <div className="sl-banner-wrap">
        {topBanners.length > 0 ? (
          <ResponsiveHeroCarousel banners={topBanners} carouselId="sellerTopCarousel" />
        ) : (
          <img src={heroFallbackImg} alt="Sell on Printmont" className="sl-banner-fallback" />
        )}
      </div>

      {/* =================================================================
          HERO + SIGN-UP CARD — Admin → Website Pages → Become a Seller → Hero
          ================================================================= */}
      <section className="sl-hero">
        <div className="seller-container">
          <div className="sl-hero-grid">
            <div className="sl-hero-text">
              <h1 className="sl-hero-title">{heroTitle}</h1>
              <p className="sl-hero-sub">{heroSub}</p>

              <div className="sl-hero-actions">
                <button type="button" className="btn-seller-cta btn-theme-cta" onClick={scrollToForm}>
                  {heroButton} <FaArrowRight className="ms-2" />
                </button>
                <button type="button" className="btn-seller-cta sl-btn-outline" onClick={scrollToCalculator}>
                  Calculate Earnings <FaCalculator className="ms-2" />
                </button>
                <button type="button" className="btn-seller-cta sl-btn-ghost" onClick={() => setShowCallbackModal(true)}>
                  Request Callback <FaPhoneAlt className="ms-2" />
                </button>
              </div>

              <div className="sl-hero-points">
                {heroPoints.map((text, idx) => {
                  const Icon = HERO_POINT_ICONS[idx % HERO_POINT_ICONS.length];
                  return (
                    <div className="sl-hero-point" key={idx}>
                      <Icon className="sl-hero-point-icon" />
                      <span>{text}</span>
                    </div>
                  );
                })}
              </div>
            </div>

            <aside className="sl-signup-card">
              <h3>Start selling in minutes</h3>
              <p>Enter your mobile number to begin your seller registration.</p>
              <form onSubmit={handleHeroStart}>
                <div className="sl-signup-input">
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
                <button type="submit" className="btn-seller-cta btn-theme-cta w-100">
                  {heroButton} <FaArrowRight className="ms-2" />
                </button>
              </form>
              <ul className="sl-signup-ticks">
                <li><FaCheckCircle /> ₹0 registration &amp; listing fee</li>
                <li><FaCheckCircle /> Doorstep courier pickup</li>
                <li><FaCheckCircle /> Payments in 7 days</li>
              </ul>
              <div className="sl-signup-login">
                Already a seller? <Link to="/login">Login</Link>
              </div>
            </aside>
          </div>
        </div>
      </section>

      {/* Numbers strip */}
      <div className="stats-bar-outer">
        <div className="seller-container">
          <div className="seller-stats-bar">
            {statItems.map((stat, idx) => {
              const Icon = STAT_ICONS[idx % STAT_ICONS.length];
              const isAccent = idx === statItems.length - 1;
              return (
                <div className="stat-item" key={idx}>
                  <div className="stat-icon-wrapper">
                    <Icon className={`stat-bar-icon ${isAccent ? "theme-accent-text" : ""}`} />
                  </div>
                  <div className="stat-info">
                    <div className={`stat-value ${isAccent ? "theme-accent-text" : ""}`}>{stat.value}</div>
                    <div className="stat-label">{stat.label}</div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </div>

      <div className="seller-container">

        {/* WHY SELL */}
        <section className="seller-content-section">
          {renderHeading(headingOf("why", "Why sell on", "Printmont?", "We empower local manufacturers, printers, and creators with enterprise e-commerce reach, automated logistics, and transparent payouts."))}
          <div className={`sl-why-grid ${whyAside?.image_path ? "has-aside" : ""}`}>
            <div className="sl-why-cards">
              {benefits.map((b, idx) => {
                const Icon = BENEFIT_ICONS[idx % BENEFIT_ICONS.length];
                return (
                  <div className="benefit-card" key={idx}>
                    <div className="benefit-icon-box">
                      {b.img ? <img src={b.img} alt="" className="sl-icon-img" /> : <Icon />}
                    </div>
                    <h3>{b.title}</h3>
                    <p>{b.desc}</p>
                  </div>
                );
              })}
            </div>
            {whyAside?.image_path && (
              <div className="sl-why-aside">
                <img src={resolveImg(whyAside.image_path)} alt={whyAside.title || "Seller"} loading="lazy" />
              </div>
            )}
          </div>
        </section>

        {/* JOURNEY */}
        <section className="seller-content-section">
          {renderHeading(headingOf("journey", "Your journey", "on Printmont", "Start receiving orders within 24 hours of account activation."))}
          <div className="sl-journey">
            {journeySteps.map((step, idx) => {
              const Icon = STEP_ICONS[idx % STEP_ICONS.length];
              return (
                <div className="sl-journey-step" key={idx}>
                  <div className="sl-journey-num">{idx + 1}</div>
                  <div className="sl-journey-art">
                    {step.img ? <img src={step.img} alt="" /> : <Icon />}
                  </div>
                  <h4>{step.title}</h4>
                  <p>{step.desc}</p>
                </div>
              );
            })}
          </div>
          <div className="text-center mt-4">
            <button type="button" className="btn-seller-cta btn-theme-cta" onClick={() => setShowGuideModal(true)}>
              {labelOf("journey_cta", "Download Launch Kit")} <FaDownload className="ms-2" />
            </button>
          </div>
        </section>

        {/* =================================================================
            SECTION 4: INTERACTIVE PROFIT & PAYOUT CALCULATOR
            ================================================================= */}
        <section className="seller-content-section" id="profit-calculator">
          <div className="section-head text-center">
            <span className="section-badge">Transparent Earnings</span>
            <h2 className="section-title">
              Interactive <span className="highlight-theme">Earnings Calculator</span>
            </h2>
            <p className="section-subtitle">
              See exactly how much money lands in your bank account with our 0% marketplace commission model.
            </p>
          </div>

          <div className="calculator-box">
            <Row className="g-4 align-items-center">
              <Col lg={6} md={12}>
                <div className="calc-left">
                  <label className="calc-section-label">Select Your Category:</label>
                  <div className="category-chips">
                    {CALCULATOR_CATEGORIES.map((cat) => (
                      <button
                        key={cat.id}
                        type="button"
                        className={`calc-chip ${selectedCategory.id === cat.id ? "active" : ""}`}
                        onClick={() => {
                          setSelectedCategory(cat);
                          setSellingPrice(cat.defaultPrice);
                        }}
                      >
                        {cat.name}
                      </button>
                    ))}
                  </div>

                  <div className="slider-wrapper mt-4">
                    <div className="d-flex justify-content-between align-items-center mb-2">
                      <label className="calc-section-label mb-0">Your Selling Price (MRP):</label>
                      <span className="price-tag">₹{sellingPrice}</span>
                    </div>
                    <input
                      type="range"
                      min="199"
                      max="4999"
                      step="20"
                      value={sellingPrice}
                      onChange={(e) => setSellingPrice(Number(e.target.value))}
                      className="form-range custom-slider"
                    />
                    <div className="d-flex justify-content-between text-muted small mt-1">
                      <span>₹199</span>
                      <span>₹2,500</span>
                      <span>₹4,999</span>
                    </div>
                  </div>

                  <div className="zero-comm-alert mt-4">
                    <FaCheckCircle className="text-success me-2" />
                    <span>Printmont charges <strong>₹0 Commission</strong>. You keep 100% of your earnings.</span>
                  </div>
                </div>
              </Col>

              <Col lg={6} md={12}>
                <div className="calc-receipt">
                  <div className="receipt-head">
                    <h4>Payout Breakdown per Order</h4>
                    <span className="receipt-cat">{selectedCategory.name}</span>
                  </div>

                  <div className="receipt-body">
                    <div className="receipt-item">
                      <span>Product Selling Price</span>
                      <strong>₹{sellingPrice}</strong>
                    </div>

                    <div className="receipt-item highlight-free">
                      <span>
                        Marketplace Commission (0%)
                        <small className="d-block text-muted">Zero commission on Printmont</small>
                      </span>
                      <strong className="text-success">₹0 (Zero)</strong>
                    </div>

                    <div className="receipt-item">
                      <span>
                        Doorstep Shipping &amp; Handling
                        <small className="d-block text-muted">Standard courier delivery</small>
                      </span>
                      <strong>₹{shippingFee}</strong>
                    </div>

                    <div className="receipt-item">
                      <span>
                        Payment Gateway Fee (2%)
                        <small className="d-block text-muted">UPI / Card / NetBanking processing</small>
                      </span>
                      <strong>₹{pgFee}</strong>
                    </div>

                    <div className="receipt-divider"></div>

                    <div className="receipt-total">
                      <div>
                        <div className="total-label">Net Direct Bank Payout</div>
                        <div className="total-sub">Credited directly via NEFT in 7 days</div>
                      </div>
                      <div className="total-value">₹{netEarnings}</div>
                    </div>

                    <div className="margin-indicator mt-3">
                      <div className="d-flex justify-content-between small mb-1">
                        <span>Net Profit Margin:</span>
                        <strong className="text-success">{profitMarginPercent}% of Selling Price</strong>
                      </div>
                      <div className="margin-progress">
                        <div 
                          className="margin-bar" 
                          style={{ width: `${Math.min(100, Math.max(10, profitMarginPercent))}%` }}
                        ></div>
                      </div>
                    </div>

                    <button
                      type="button"
                      className="btn-seller-cta btn-theme-cta w-100 mt-4"
                      onClick={() => handleCategorySelect(selectedCategory.name)}
                    >
                      Sell in this Category <FaArrowRight className="ms-2" />
                    </button>
                  </div>
                </div>
              </Col>
            </Row>
          </div>
        </section>

        {/* CATEGORIES */}
        <section className="seller-content-section">
          {renderHeading(headingOf("categories", "Top selling categories on", "Printmont", "Join thousands of creators, printers, and manufacturers selling customized merchandise with huge buyer demand."))}
          <Row className="g-4">
            {categories.map((cat, idx) => {
              const Icon = CATEGORY_ICONS[idx % CATEGORY_ICONS.length];
              return (
                <Col lg={4} md={6} key={idx}>
                  <div className="catalog-card">
                    <div className="catalog-icon"><Icon /></div>
                    {cat.tag && <span className="margin-badge">{cat.tag}</span>}
                    <h3>{cat.title}</h3>
                    <p>{cat.desc}</p>
                    <button type="button" className="btn-catalog-select" onClick={() => handleCategorySelect(cat.title)}>
                      Sell This Category <FaArrowRight className="ms-1" />
                    </button>
                  </div>
                </Col>
              );
            })}
          </Row>
        </section>

        {/* GROWTH TOOLS */}
        {tools.length > 0 && (
          <section className="seller-content-section">
            {renderHeading(headingOf("tools", "Access our tools to grow faster", "on Printmont", "Your business may need extra support from time to time, and we have you covered."))}
            <div className="sl-tools-wrap">
              <span className="sl-tools-watermark" aria-hidden="true">{labelOf("tools_watermark", "5x Growth")}</span>
              <div className="sl-tools-grid">
                {tools.map((tool, idx) => {
                  const Icon = TOOL_ICONS[idx % TOOL_ICONS.length];
                  const body = (
                    <>
                      <div className="sl-tool-icon">{tool.img ? <img src={tool.img} alt="" /> : <Icon />}</div>
                      <h4>{tool.title}</h4>
                      <p>{tool.desc}</p>
                      {tool.link && <span className="sl-tool-link">Learn more <FaArrowRight /></span>}
                    </>
                  );
                  if (!tool.link) return <div className="sl-tool-card" key={idx}>{body}</div>;
                  return tool.link.startsWith("/") ? (
                    <Link className="sl-tool-card" to={tool.link} key={idx}>{body}</Link>
                  ) : (
                    <a className="sl-tool-card" href={tool.link} key={idx} target="_blank" rel="noopener noreferrer">{body}</a>
                  );
                })}
              </div>
            </div>
          </section>
        )}

        {/* PLATFORM PREVIEW */}
        {platformSlides.length > 0 && (
          <section className="seller-content-section">
            {renderHeading(headingOf("platform", "Take a sneak peek into", "our platform", ""))}
            <div className="sl-platform-grid">
              {platformSlides.map((slide, idx) => (
                <div className="sl-platform-card" key={idx}>
                  {slide.img && (
                    <div className="sl-platform-shot">
                      <img src={slide.img} alt={slide.title} loading="lazy" />
                    </div>
                  )}
                  <h4>{slide.title}</h4>
                  <p>{slide.desc}</p>
                  {slide.button && (
                    <button type="button" className="sl-platform-btn" onClick={scrollToForm}>
                      {slide.button} <FaArrowRight className="ms-1" />
                    </button>
                  )}
                </div>
              ))}
            </div>
          </section>
        )}

        {/* SELLER STORIES */}
        {stories.length > 0 && (
          <section className="seller-content-section">
            {renderHeading(headingOf("stories", "Seller success", "stories", "Thousands of sellers trust Printmont for their online business."))}
            <div className="sl-stories-grid">
              {stories.map((story, idx) => (
                <div className="sl-story-card" key={idx}>
                  <FaQuoteLeft className="sl-story-quote" />
                  <p className="sl-story-text">{story.quote}</p>
                  <div className="sl-story-author">
                    {story.img ? (
                      <img src={story.img} alt={story.name} className="sl-story-photo" loading="lazy" />
                    ) : (
                      <div className="sl-story-avatar">{(story.name || "?").charAt(0)}</div>
                    )}
                    <div>
                      <div className="sl-story-name">{story.name}</div>
                      {story.business && <div className="sl-story-biz">{story.business}</div>}
                    </div>
                  </div>
                </div>
              ))}
            </div>
            <div className="text-center mt-4">
              <button type="button" className="btn-seller-cta sl-btn-outline" onClick={scrollToForm}>
                {labelOf("stories_cta", "See All Stories")} <FaArrowRight className="ms-2" />
              </button>
            </div>
          </section>
        )}

        {/* Mid-page banner — Admin → Page Banners → Become a Seller → Mid Banner */}
        {midBanners.length > 0 && (
          <section className="seller-content-section sl-mid-banner">
            <ResponsiveHeroCarousel banners={midBanners} carouselId="sellerMidCarousel" />
          </section>
        )}

        {/* DOCUMENTS */}
        <section className="seller-content-section">
          {renderHeading(headingOf("documents", "What you need to", "start selling", "Get your seller account activated with just 4 basic details."))}
          <Row className="g-4">
            {documents.map((doc, idx) => {
              const Icon = DOCUMENT_ICONS[idx % DOCUMENT_ICONS.length];
              return (
                <Col lg={3} sm={6} key={idx}>
                  <div className="doc-box">
                    <div className="doc-icon"><Icon /></div>
                    <h4>{doc.title}</h4>
                    <p>{doc.desc}</p>
                  </div>
                </Col>
              );
            })}
          </Row>
        </section>

        {/* =================================================================
            SECTION 8: SELLER REGISTRATION FORM
            (Directly connected to backend leads-api.php)
            ================================================================= */}
        <section className="seller-content-section" id="seller-registration-form">
          <div className="seller-form-container">
            <Row className="g-0">
              
              {/* Left Column: Side Info */}
              <Col lg={5} md={12} className="form-left-panel">
                <div className="form-left-inner">
                  <span className="panel-badge">Join 8,500+ Sellers</span>
                  <h3>Start Your 0% Commission Journey Today</h3>
                  <p>
                    Submit your details below. Our Seller Onboarding Team will review your application and activate your Seller Portal within 24 hours.
                  </p>

                  <div className="form-usp-list">
                    <div className="usp-item">
                      <FaCheckCircle className="theme-accent-text me-2" />
                      <span>₹0 Registration &amp; Listing Fee</span>
                    </div>
                    <div className="usp-item">
                      <FaCheckCircle className="theme-accent-text me-2" />
                      <span>Guaranteed 7-Day Bank Payouts</span>
                    </div>
                    <div className="usp-item">
                      <FaCheckCircle className="theme-accent-text me-2" />
                      <span>Dedicated Account Manager</span>
                    </div>
                    <div className="usp-item">
                      <FaCheckCircle className="theme-accent-text me-2" />
                      <span>Doorstep Logistics across 19,000+ Pincodes</span>
                    </div>
                  </div>

                  <div className="form-help-box mt-4">
                    <div className="help-icon"><FaHeadset /></div>
                    <div>
                      <div className="help-caption">Seller Support Helpline</div>
                      <div className="help-phone">+91 98765 43210</div>
                      <div className="help-timing">Mon - Sat (10:00 AM - 7:00 PM IST)</div>
                    </div>
                  </div>
                </div>
              </Col>

              {/* Right Column: Actual Form */}
              <Col lg={7} md={12} className="form-right-panel">
                <div className="form-right-inner">
                  
                  {submitSuccess ? (
                    <div className="form-success-box text-center">
                      <div className="success-icon"><FaCheckCircle /></div>
                      <h3>Application Submitted Successfully!</h3>
                      <p>
                        Thank you for registering to sell on Printmont. Your seller application has been recorded.
                      </p>
                      <div className="next-steps-card">
                        <strong>What happens next?</strong>
                        <ul className="text-start mt-2 mb-0">
                          <li>Our Seller Onboarding Manager will call you within 24 hours.</li>
                          <li>You will receive your login credentials for the Seller Portal.</li>
                          <li>Our catalog team will assist in listing your products.</li>
                        </ul>
                      </div>
                      <button
                        type="button"
                        className="btn-seller-cta btn-theme-cta mt-4"
                        onClick={() => {
                          setSubmitSuccess(false);
                          setFormData({
                            fullName: "",
                            businessName: "",
                            mobile: "",
                            email: "",
                            city: "",
                            state: "",
                            category: "Custom T-Shirts & Apparel",
                            hasGstin: "Yes, I have an active GSTIN",
                            monthlyVolume: "50 - 200 orders/month",
                            message: ""
                          });
                        }}
                      >
                        Submit Another Application
                      </button>
                    </div>
                  ) : (
                    <form onSubmit={handleSubmit} className="seller-lead-form">
                      <div className="form-header-area mb-4">
                        <h3 className="form-title">Seller Registration Form</h3>
                        <p className="form-subtitle text-muted">
                          Fill in your details below. Fields with <span className="text-danger">*</span> are required.
                        </p>
                      </div>

                      <Row className="g-3">
                        <Col sm={6}>
                          <div className="form-group">
                            <label className="form-label">
                              Your Full Name <span className="text-danger">*</span>
                            </label>
                            <input
                              type="text"
                              name="fullName"
                              value={formData.fullName}
                              onChange={handleInputChange}
                              placeholder="e.g. Ramesh Patel"
                              className="form-control"
                              required
                            />
                          </div>
                        </Col>

                        <Col sm={6}>
                          <div className="form-group">
                            <label className="form-label">
                              Business / Workshop Name
                            </label>
                            <input
                              type="text"
                              name="businessName"
                              value={formData.businessName}
                              onChange={handleInputChange}
                              placeholder="e.g. Patel Merchandise"
                              className="form-control"
                            />
                          </div>
                        </Col>

                        <Col sm={6}>
                          <div className="form-group">
                            <label className="form-label">
                              Mobile Number (WhatsApp) <span className="text-danger">*</span>
                            </label>
                            <input
                              type="tel"
                              name="mobile"
                              value={formData.mobile}
                              onChange={handleInputChange}
                              placeholder="10-digit mobile number"
                              maxLength={10}
                              className="form-control"
                              required
                            />
                          </div>
                        </Col>

                        <Col sm={6}>
                          <div className="form-group">
                            <label className="form-label">
                              Email Address <span className="text-danger">*</span>
                            </label>
                            <input
                              type="email"
                              name="email"
                              value={formData.email}
                              onChange={handleInputChange}
                              placeholder="e.g. ramesh@prints.com"
                              className="form-control"
                              required
                            />
                          </div>
                        </Col>

                        <Col sm={6}>
                          <div className="form-group">
                            <label className="form-label">City</label>
                            <input
                              type="text"
                              name="city"
                              value={formData.city}
                              onChange={handleInputChange}
                              placeholder="e.g. Ahmedabad / Surat"
                              className="form-control"
                            />
                          </div>
                        </Col>

                        <Col sm={6}>
                          <div className="form-group">
                            <label className="form-label">State</label>
                            <input
                              type="text"
                              name="state"
                              value={formData.state}
                              onChange={handleInputChange}
                              placeholder="e.g. Gujarat / Maharashtra"
                              className="form-control"
                            />
                          </div>
                        </Col>

                        <Col sm={6}>
                          <div className="form-group">
                            <label className="form-label">
                              Primary Product Category <span className="text-danger">*</span>
                            </label>
                            <select
                              name="category"
                              value={formData.category}
                              onChange={handleInputChange}
                              className="form-select"
                            >
                              <option value="Custom T-Shirts & Apparel">Custom T-Shirts & Apparel</option>
                              <option value="Ceramic Mugs & Drinkware">Ceramic Mugs & Drinkware</option>
                              <option value="Corporate Gift Sets">Corporate Gift Sets</option>
                              <option value="Visiting Cards & Stationery">Visiting Cards & Stationery</option>
                              <option value="Custom Boxes & Packaging">Custom Boxes & Packaging</option>
                              <option value="Photo Frames & Acrylic Prints">Photo Frames & Acrylic Prints</option>
                              <option value="Laser Engraved Wooden Gifts">Laser Engraved Wooden Gifts</option>
                              <option value="Other Customized Merchandise">Other Customized Merchandise</option>
                            </select>
                          </div>
                        </Col>

                        <Col sm={6}>
                          <div className="form-group">
                            <label className="form-label">
                              Do You Have GSTIN? <span className="text-danger">*</span>
                            </label>
                            <select
                              name="hasGstin"
                              value={formData.hasGstin}
                              onChange={handleInputChange}
                              className="form-select"
                            >
                              <option value="Yes, I have an active GSTIN">Yes, I have an active GSTIN</option>
                              <option value="Enrolled in GST Composition Scheme">Enrolled in GST Composition Scheme</option>
                              <option value="Applying for GST soon">Applying for GST soon</option>
                              <option value="Selling non-taxable / PAN only">Selling non-taxable / PAN only</option>
                            </select>
                          </div>
                        </Col>

                        <Col sm={12}>
                          <div className="form-group">
                            <label className="form-label">
                              Current / Expected Monthly Order Volume
                            </label>
                            <select
                              name="monthlyVolume"
                              value={formData.monthlyVolume}
                              onChange={handleInputChange}
                              className="form-select"
                            >
                              <option value="Starting Fresh (0 - 50 orders/month)">Starting Fresh (0 - 50 orders/month)</option>
                              <option value="50 - 200 orders/month">50 - 200 orders/month</option>
                              <option value="200 - 500 orders/month">200 - 500 orders/month</option>
                              <option value="500+ orders/month (High-Volume Producer)">500+ orders/month (High-Volume Producer)</option>
                            </select>
                          </div>
                        </Col>

                        <Col sm={12}>
                          <div className="form-group">
                            <label className="form-label">
                              About Your Products or Machinery
                            </label>
                            <textarea
                              name="message"
                              value={formData.message}
                              onChange={handleInputChange}
                              rows={3}
                              placeholder="e.g. We own 4 DTF printers and 2 mug heat presses, looking to scale online..."
                              className="form-control"
                            ></textarea>
                          </div>
                        </Col>

                        <Col sm={12} className="mt-3">
                          <button
                            type="submit"
                            className="btn-seller-cta btn-theme-cta w-100"
                            disabled={isSubmitting}
                          >
                            {isSubmitting ? (
                              <>
                                <Spinner animation="border" size="sm" className="me-2" />
                                Submitting Registration...
                              </>
                            ) : (
                              <>
                                Register as Seller (0% Commission) <FaArrowRight className="ms-2" />
                              </>
                            )}
                          </button>

                          <div className="form-secure-note text-center mt-3 text-muted small">
                            <FaShieldAlt className="text-success me-1" />
                            Your business information is strictly confidential and protected by Printmont Privacy Policy.
                          </div>
                        </Col>

                      </Row>
                    </form>
                  )}

                </div>
              </Col>
            </Row>
          </div>
        </section>

        {/* FAQ */}
        <section className="seller-content-section">
          {renderHeading(headingOf("faqs", "Frequently asked", "questions", "Everything you need to know about commissions, payments, shipping, and returns."))}
          <div className="faq-box">
            <Accordion defaultActiveKey="0">
              {faqs.map((faq, idx) => (
                <Accordion.Item eventKey={String(idx)} className="faq-item" key={idx}>
                  <Accordion.Header>{faq.q}</Accordion.Header>
                  <Accordion.Body>{faq.a}</Accordion.Body>
                </Accordion.Item>
              ))}
            </Accordion>
          </div>
        </section>

        {/* HELP / QUERY FORM */}
        <section className="seller-content-section">
          <div className="sl-help">
            <div className="sl-help-form">
              {renderHeading(headingOf("help", "We are happy to", "help you", "Still have questions left unanswered? Share your thoughts below and we will get back to you."), "text-start")}
              <form onSubmit={handleHelpSubmit}>
                <div className="sl-help-row">
                  <input className="form-control" placeholder="Your name *" value={helpData.fullName} onChange={(e) => setHelpData((p) => ({ ...p, fullName: e.target.value }))} required />
                  <input className="form-control" type="tel" maxLength="10" placeholder="Mobile number *" value={helpData.mobile} onChange={(e) => setHelpData((p) => ({ ...p, mobile: e.target.value.replace(/\D/g, "") }))} required />
                </div>
                <div className="sl-help-row">
                  <input className="form-control" type="email" placeholder="Email address *" value={helpData.email} onChange={(e) => setHelpData((p) => ({ ...p, email: e.target.value }))} required />
                  <select className="form-control" value={helpData.topic} onChange={(e) => setHelpData((p) => ({ ...p, topic: e.target.value }))}>
                    <option value="">Select a topic</option>
                    {topics.map((t) => <option key={t} value={t}>{t}</option>)}
                  </select>
                </div>
                <textarea className="form-control" rows="3" placeholder="Your question or message" value={helpData.message} onChange={(e) => setHelpData((p) => ({ ...p, message: e.target.value }))} />
                <button type="submit" className="btn-seller-cta btn-theme-cta mt-3" disabled={isHelpSubmitting}>
                  {isHelpSubmitting ? <Spinner animation="border" size="sm" /> : labelOf("form_submit", "Send Query")}
                </button>
              </form>
            </div>
            {helpAside?.image_path && (
              <div className="sl-help-aside">
                <img src={resolveImg(helpAside.image_path)} alt={helpAside.title || "Seller support"} loading="lazy" />
              </div>
            )}
          </div>
        </section>

      </div>

      {/* =================================================================
          QUICK CALLBACK MODAL
          ================================================================= */}
      <Modal
        show={showCallbackModal}
        onHide={() => setShowCallbackModal(false)}
        centered
        className="seller-modal"
      >
        <Modal.Header closeButton>
          <Modal.Title className="modal-title-custom">
            <FaPhoneAlt className="theme-accent-text me-2" /> Request Seller Callback
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p className="text-muted small mb-3">
            Our Senior Seller Onboarding Director will call you to explain catalog listing, 0% commission benefits, and delivery logistics.
          </p>
          <form onSubmit={handleCallbackSubmit}>
            <div className="mb-3">
              <label className="form-label small fw-bold">Your Name *</label>
              <input
                type="text"
                className="form-control"
                placeholder="Enter your name"
                value={callbackData.fullName}
                onChange={(e) => setCallbackData({ ...callbackData, fullName: e.target.value })}
                required
              />
            </div>
            <div className="mb-3">
              <label className="form-label small fw-bold">Mobile Number *</label>
              <input
                type="tel"
                className="form-control"
                placeholder="10-digit mobile number"
                maxLength={10}
                value={callbackData.mobile}
                onChange={(e) => setCallbackData({ ...callbackData, mobile: e.target.value })}
                required
              />
            </div>
            <div className="mb-3">
              <label className="form-label small fw-bold">City / State</label>
              <input
                type="text"
                className="form-control"
                placeholder="e.g. Jaipur, Rajasthan"
                value={callbackData.city}
                onChange={(e) => setCallbackData({ ...callbackData, city: e.target.value })}
              />
            </div>
            <button
              type="submit"
              className="btn-seller-cta btn-theme-cta w-100 mt-2"
              disabled={isCallbackSubmitting}
            >
              {isCallbackSubmitting ? (
                <>
                  <Spinner animation="border" size="sm" className="me-2" />
                  Scheduling Call...
                </>
              ) : (
                "Confirm Callback Request"
              )}
            </button>
          </form>
        </Modal.Body>
      </Modal>

      {/* =================================================================
          SELLER HANDBOOK DOWNLOAD MODAL
          ================================================================= */}
      <Modal
        show={showGuideModal}
        onHide={() => setShowGuideModal(false)}
        centered
        className="seller-modal"
      >
        <Modal.Header closeButton>
          <Modal.Title className="modal-title-custom">
            <FaDownload className="theme-accent-text me-2" /> Download Seller Handbook
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p className="text-muted small mb-3">
            Get instant access to the official guide on product photography, packaging rules, tax invoices, and growth strategies.
          </p>
          <form onSubmit={handleGuideSubmit}>
            <div className="mb-3">
              <label className="form-label small fw-bold">Your Name *</label>
              <input
                type="text"
                className="form-control"
                placeholder="Enter your full name"
                value={guideData.fullName}
                onChange={(e) => setGuideData({ ...guideData, fullName: e.target.value })}
                required
              />
            </div>
            <div className="mb-3">
              <label className="form-label small fw-bold">Email Address *</label>
              <input
                type="email"
                className="form-control"
                placeholder="e.g. rahul@business.com"
                value={guideData.email}
                onChange={(e) => setGuideData({ ...guideData, email: e.target.value })}
                required
              />
            </div>
            <div className="mb-3">
              <label className="form-label small fw-bold">Mobile Number *</label>
              <input
                type="tel"
                className="form-control"
                placeholder="10-digit mobile number"
                maxLength={10}
                value={guideData.mobile}
                onChange={(e) => setGuideData({ ...guideData, mobile: e.target.value })}
                required
              />
            </div>
            <button
              type="submit"
              className="btn-seller-cta btn-theme-cta w-100 mt-2"
              disabled={isGuideSubmitting}
            >
              {isGuideSubmitting ? (
                <>
                  <Spinner animation="border" size="sm" className="me-2" />
                  Sending Guide...
                </>
              ) : (
                "Download Free Handbook (PDF)"
              )}
            </button>
          </form>
        </Modal.Body>
      </Modal>

      {/* =================================================================
          MOBILE STICKY BOTTOM BAR (Mobile Only < 768px)
          ================================================================= */}
      <div className="seller-mobile-sticky-bar d-md-none">
        <button
          type="button"
          className="sticky-btn-ghost"
          onClick={() => setShowCallbackModal(true)}
        >
          <FaPhoneAlt className="me-1" /> Callback
        </button>
        <button
          type="button"
          className="sticky-btn-theme"
          onClick={scrollToForm}
        >
          <FaRocket className="me-1" /> Start Selling (0% Fee)
        </button>
      </div>

    </div>
  );
};

export default BecomeASeller;
