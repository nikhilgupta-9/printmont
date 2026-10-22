import React, { useState, useEffect } from "react";
import { Row, Col, Accordion, Spinner } from "react-bootstrap";
import { Link } from "react-router-dom";
import {
  FaMobileAlt,
  FaGooglePlay,
  FaApple,
  FaBell,
  FaCheckCircle,
  FaGift,
  FaShieldAlt,
  FaPaintBrush,
  FaTruck,
  FaRedoAlt,
  FaArrowRight,
  FaCoins,
  FaBolt,
  FaHeart
} from "react-icons/fa";
import { toast } from "react-hot-toast";
import { updatePageSEO } from "../../utils/seo";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import "./AppAnnouncement.css";

const AppAnnouncement = () => {
  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: "Printmont Mobile App Coming Soon | Download for Android & iOS",
      description: "The official Printmont mobile app is arriving soon on Google Play Store and Apple App Store. Experience instant custom t-shirt printing, photo gift customization, exclusive app discounts, and live order tracking.",
      keywords: "printmont app, download printmont app, custom printing app india, personalized gifts app, t-shirt printing app, printmont mobile app",
      type: "website"
    });
  }, []);

  const [contactInput, setContactInput] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSubscribed, setIsSubscribed] = useState(false);

  const handleNotifySubmit = async (e) => {
    e.preventDefault();
    const val = contactInput.trim();
    const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
    const digits = val.replace(/\D/g, "");
    const isMobile = !isEmail && /^(\+?91)?[6-9]\d{9}$/.test(digits);

    if (!isEmail && !isMobile) {
      toast.error("Please enter a valid email address or 10-digit mobile number.");
      return;
    }

    try {
      setIsSubmitting(true);
      const payload = {
        full_name: "App Launch Subscriber",
        contact_number: isMobile ? digits.slice(-10) : "Not Provided",
        email_id: isEmail ? val : `${digits.slice(-10)}@printmont-app-lead.com`,
        company_name: "Printmont Mobile App Pre-Register",
        budget: "App Download Lead",
        product_requirement: `User pre-registered for Printmont Mobile App launch notifications. Contact: ${val}`,
        source_page: "app-download",
        device_type: window.innerWidth < 992 ? "mobile" : "desktop"
      };

      const res = await fetch(API_ENDPOINTS.LEADS_SUBMIT, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (!data?.success) {
        toast.error(data?.message || "Could not register you right now. Please try again.");
        return;
      }
      setIsSubscribed(true);
      toast.success("You're on the launch list! We'll notify you as soon as the app goes live.");
    } catch (err) {
      console.error("App pre-register error:", err);
      toast.error("Could not register you right now. Please check your connection and try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const appFeatures = [
    {
      icon: <FaPaintBrush />,
      title: "Pocket Design Studio",
      desc: "Upload photos directly from your phone gallery or Instagram, add custom text, preview instant 3D product renders, and create unique personalized gifts in seconds."
    },
    {
      icon: <FaGift />,
      title: "App-Exclusive Deals & 2x Coins",
      desc: "Enjoy extra app-only coupons, flash sale notifications, and 2x Printmont Coins on all orders placed via the mobile app."
    },
    {
      icon: <FaTruck />,
      title: "Live Order Tracking & Push Alerts",
      desc: "Stay informed at every single stage: printing, quality check, packaging, dispatch, and out-for-delivery with live delivery partner tracking."
    },
    {
      icon: <FaRedoAlt />,
      title: "1-Tap Quick Reordering",
      desc: "Running low on corporate merchandise or need duplicate custom gifts? Reorder past designs with a single tap from your personal order history."
    },
    {
      icon: <FaShieldAlt />,
      title: "Instant & Secure 1-Tap Checkout",
      desc: "Seamless UPI payments via Google Pay, PhonePe, Paytm, Credit/Debit cards, NetBanking, and instant Printmont Cash Wallet redemption."
    },
    {
      icon: <FaHeart />,
      title: "Curated Wishlists & Offline Drafts",
      desc: "Save design drafts on your phone even when offline. Share gift ideas with friends and family with 1-click shareable preview links."
    }
  ];

  const howItWorksSteps = [
    {
      num: "01",
      title: "Pick Your Canvas",
      desc: "Choose from 350+ personalized products: custom t-shirts, ceramic mugs, canvas frames, corporate kits, or neon signs."
    },
    {
      num: "02",
      title: "Customize in 30 Seconds",
      desc: "Upload favorite photos from your camera roll, customize names or quotes, and inspect real-time interactive 3D mockups."
    },
    {
      num: "03",
      title: "Doorstep Express Delivery",
      desc: "Place your order securely and receive high-definition, quality-checked customized merchandise delivered to your doorstep."
    }
  ];

  const faqs = [
    {
      q: "When will the Printmont mobile app be available for download?",
      a: "Our mobile app is currently in its final stages of testing and will launch soon on both the Google Play Store and Apple App Store. Pre-register today to be notified the minute it goes live!"
    },
    {
      q: "Will the app be available for both Android and iPhone (iOS)?",
      a: "Yes! The Printmont app is built natively for both Android smartphones (via Google Play Store) and Apple iOS devices (via Apple App Store), providing an ultra-smooth 60fps native experience on all screen sizes."
    },
    {
      q: "How do I claim my ₹200 Early Bird launch bonus?",
      a: "Simply enter your email address or WhatsApp mobile number in the pre-registration form above. When the app launches, use the same contact info to sign in and ₹200 Printmont Wallet Coins will be automatically credited to your account!"
    },
    {
      q: "Can I customize gifts directly using photos from my smartphone?",
      a: "Absolutely! The app features a high-performance native design editor where you can pick photos straight from your gallery, crop, adjust color filters, add stylized typography, and inspect realistic 3D mockups before ordering."
    },
    {
      q: "Can I access my existing Printmont.com account and orders on the app?",
      a: "Yes! Your existing Printmont account, saved delivery addresses, order history, wishlist, and wallet coins balance will sync automatically when you sign in on the mobile app."
    }
  ];

  return (
    <div className="app-download-page">
      <div className="app-container">
        
        {/* =================================================================
            1. HERO — "Coming soon" announcement with pre-register form
            ================================================================= */}
        <section className="app-hero-card">
          <Row className="align-items-center g-4 g-lg-5">

            {/* Left: heading, perks, notify form, store badges */}
            <Col lg={7} xs={12}>
              <div className="app-badge-pill">
                <FaMobileAlt /> Coming Soon &bull; Android &amp; iOS
              </div>

              <h1 className="app-hero-title">
                The <span className="highlight-brand">Printmont</span> App<br />
                is Coming Soon.
              </h1>

              <p className="app-hero-sub">
                Lightning-fast custom printing, 1-click photo gift creation, exclusive app-only discounts,
                and real-time live order tracking &mdash; all from your pocket.
              </p>

              <div className="app-hero-chips-grid">
                <div className="app-feature-chip">
                  <FaBolt />
                  <span>Instant Pocket Design Studio</span>
                </div>
                <div className="app-feature-chip">
                  <FaCoins />
                  <span>₹200 Welcome Bonus Coins</span>
                </div>
                <div className="app-feature-chip">
                  <FaTruck />
                  <span>Real-Time Order Push Alerts</span>
                </div>
                <div className="app-feature-chip">
                  <FaShieldAlt />
                  <span>1-Tap UPI &amp; Wallet Checkout</span>
                </div>
              </div>

              {/* Pre-register / notify form */}
              <div className="app-notify-box" id="notify-form-section">
                <div className="app-notify-title">
                  <FaBell />
                  <span>Get early access &amp; ₹200 launch coins</span>
                </div>
                <p className="app-notify-sub">
                  Be the first to know when the app launches on Play Store &amp; App Store. We will credit ₹200 coins to your wallet on launch day.
                </p>

                {isSubscribed ? (
                  <div className="app-notify-success">
                    <FaCheckCircle />
                    <span>You're on the launch list! We'll notify you as soon as the app is live.</span>
                  </div>
                ) : (
                  <form onSubmit={handleNotifySubmit} className="app-notify-form" noValidate>
                    <input
                      type="text"
                      required
                      value={contactInput}
                      onChange={(e) => setContactInput(e.target.value)}
                      placeholder="Enter email or mobile number"
                      className="app-notify-input"
                      aria-label="Email or mobile number"
                    />
                    <button type="submit" disabled={isSubmitting} className="app-notify-btn">
                      {isSubmitting ? (
                        <>
                          <Spinner animation="border" size="sm" /> Registering...
                        </>
                      ) : (
                        <>
                          Notify Me <FaArrowRight />
                        </>
                      )}
                    </button>
                  </form>
                )}
              </div>

              {/* Store badges (not live yet) */}
              <div className="app-store-badges-row">
                <div className="app-store-badge-card" title="Coming soon to Google Play Store" aria-disabled="true">
                  <FaGooglePlay className="store-icon" />
                  <div className="store-text-group">
                    <span className="store-sub">GET IT ON</span>
                    <span className="store-title">Google Play</span>
                  </div>
                  <span className="coming-soon-chip">Soon</span>
                </div>

                <div className="app-store-badge-card" title="Coming soon to Apple App Store" aria-disabled="true">
                  <FaApple className="store-icon" />
                  <div className="store-text-group">
                    <span className="store-sub">DOWNLOAD ON THE</span>
                    <span className="store-title">App Store</span>
                  </div>
                  <span className="coming-soon-chip">Soon</span>
                </div>
              </div>
            </Col>

            {/* Right: app preview */}
            <Col lg={5} xs={12}>
              <div className="app-hero-mockup-wrap">
                <img
                  src="/app-mockup-hero.png"
                  alt="Printmont Mobile App Interface Preview"
                  className="app-hero-mockup-img"
                  onError={(e) => {
                    e.currentTarget.src = "/coming-soon.png";
                  }}
                />
                <div className="app-hero-floating-badge">
                  <span className="app-pulse-dot"></span>
                  <span>Launching soon</span>
                </div>
              </div>
            </Col>

          </Row>
        </section>

        {/* =================================================================
            2. HIGHLIGHTS STRIP
            ================================================================= */}
        <section className="app-stats-bar">
          <Row className="g-3 align-items-center">
            <Col xs={6} md={3}>
              <div className="app-stat-item">
                <div className="app-stat-number">1-Click</div>
                <div className="app-stat-label">Photo Gift Customizer</div>
              </div>
            </Col>
            <Col xs={6} md={3}>
              <div className="app-stat-item">
                <div className="app-stat-number">₹200</div>
                <div className="app-stat-label">Early Bird Welcome Coins</div>
              </div>
            </Col>
            <Col xs={6} md={3}>
              <div className="app-stat-item">
                <div className="app-stat-number">350+</div>
                <div className="app-stat-label">Customizable Products</div>
              </div>
            </Col>
            <Col xs={6} md={3}>
              <div className="app-stat-item">
                <div className="app-stat-number">Android &amp; iOS</div>
                <div className="app-stat-label">Coming to Both Stores</div>
              </div>
            </Col>
          </Row>
        </section>

        {/* =================================================================
            3. WHY YOU'LL LOVE THE APP (6 FEATURE CARDS)
            ================================================================= */}
        <section className="app-section">
          <div className="app-sec-header">
            <span className="app-sec-pill">Built for Speed &amp; Creativity</span>
            <h2 className="app-sec-title">Why You'll Love the Printmont App</h2>
            <p className="app-sec-sub">
              Engineered from the ground up to make personalizing gifts, custom apparel, and corporate merchandise effortless, intuitive, and enjoyable.
            </p>
          </div>

          <Row className="g-3 g-md-4">
            {appFeatures.map((feat, idx) => (
              <Col lg={4} md={6} xs={12} key={idx}>
                <div className="app-benefit-card">
                  <div className="app-benefit-icon-box">{feat.icon}</div>
                  <h3 className="app-benefit-title">{feat.title}</h3>
                  <p className="app-benefit-text">{feat.desc}</p>
                </div>
              </Col>
            ))}
          </Row>
        </section>

        {/* =================================================================
            4. HOW IT WORKS (3 SIMPLE STEPS)
            ================================================================= */}
        <section className="app-section">
          <div className="app-sec-header">
            <span className="app-sec-pill">Simplicity at Your Fingertips</span>
            <h2 className="app-sec-title">How It Works in 3 Quick Steps</h2>
            <p className="app-sec-sub">
              From taking a photo on your smartphone to receiving your custom-printed masterpiece at your doorstep.
            </p>
          </div>

          <Row className="g-3 g-md-4">
            {howItWorksSteps.map((step, idx) => (
              <Col md={4} xs={12} key={idx}>
                <div className="app-step-box">
                  <div className="app-step-badge">{step.num}</div>
                  <h3 className="app-step-title">{step.title}</h3>
                  <p className="app-step-text">{step.desc}</p>
                </div>
              </Col>
            ))}
          </Row>
        </section>

        {/* =================================================================
            5. EARLY BIRD REWARD BANNER
            ================================================================= */}
        <section className="app-perk-banner">
          <div className="app-perk-content">
            <div className="d-flex align-items-center gap-3">
              <div className="app-perk-icon-wrap">
                <FaGift />
              </div>
              <div>
                <h3 className="app-perk-title">Pre-Register Today &amp; Get ₹200 Free Wallet Coins!</h3>
                <p className="app-perk-sub">
                  Be among our first 10,000 mobile app users. Sign up above and receive ₹200 coins credited instantly on launch day.
                </p>
              </div>
            </div>

            <button 
              type="button" 
              className="app-perk-btn"
              onClick={() => {
                const el = document.getElementById("notify-form-section");
                if (el) el.scrollIntoView({ behavior: "smooth" });
              }}
            >
              Pre-Register for ₹200 Coins <FaArrowRight />
            </button>
          </div>
        </section>

        {/* =================================================================
            6. FREQUENTLY ASKED QUESTIONS (ACCORDION)
            ================================================================= */}
        <section className="app-section">
          <div className="app-sec-header">
            <span className="app-sec-pill">Common Questions</span>
            <h2 className="app-sec-title">Mobile App Launch FAQs</h2>
            <p className="app-sec-sub">
              Everything you need to know about the upcoming Printmont mobile application.
            </p>
          </div>

          <div className="app-accordion max-w-900 mx-auto" style={{ maxWidth: "860px" }}>
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

        {/* =================================================================
            7. CLOSING CTA BANNER
            ================================================================= */}
        <section className="app-closing-cta">
          <h2 className="app-closing-title">Can't Wait for the Mobile App?</h2>
          <p className="app-closing-sub">
            You can shop our complete catalog of 350+ personalized gifts, custom apparel, and corporate merchandise directly on our web store right now.
          </p>

          <Link to="/allproducts" className="app-closing-btn">
            Explore All Products on Web <FaArrowRight className="ms-1" />
          </Link>
        </section>

      </div>
    </div>
  );
};

export default AppAnnouncement;
