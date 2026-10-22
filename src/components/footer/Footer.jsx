import React, { useState, useEffect } from "react";
import "bootstrap/dist/css/bootstrap.min.css";
import {
  FaYoutube,
  FaFacebookF,
  FaInstagram,
  FaLinkedinIn,
  FaPhoneAlt,
  FaWhatsapp,
  FaPinterestP
} from "react-icons/fa";
import { FaXTwitter } from "react-icons/fa6";
import MobileFooter from "./MobileFooter";
import IconX from "./IconX";
import { Link, useLocation } from "react-router-dom";
import { useAuth } from "../../context/AuthContext";
import { API_ENDPOINTS, resolveImageUrl } from "../../config/apiEndpoints";
import "./Footer.css";

const DEFAULT_SECTIONS = [
  {
    id: 1,
    title: "Our Company",
    links: [
      { id: 1, title: "Contact Us", url: "/contact" },
      { id: 2, title: "About Us", url: "/about" },
      { id: 3, title: "Careers", url: "/careers" },
      { id: 4, title: "Affiliate Program", url: "/affiliate-program" },
      { id: 5, title: "Blog", url: "/blog" },
      { id: 6, title: "Franchise", url: "/franchise" },
    ]
  },
  {
    id: 2,
    title: "Policy Info",
    links: [
      { id: 6, title: "Terms & Conditions", url: "/terms" },
      { id: 7, title: "Privacy Policy", url: "/privacy" },
      { id: 8, title: "Shipping Policy", url: "/shipping" },
      { id: 9, title: "Return & Refund Policy", url: "/returns" },
    ]
  },
  {
    id: 3,
    title: "Quick Links",
    links: [
      { id: 10, title: "Help Center", url: "/help-center" },
      { id: 11, title: "Security", url: "/security" },
      { id: 12, title: "Sitemap", url: "/sitemap" },
      { id: 13, title: "FAQ", url: "/faq" },
      { id: 14, title: "Bulk Orders", url: "/bulk-orders" },
      { id: 15, title: "Business Solutions", url: "/business-solutions" },
    ]
  },
  {
    id: 4,
    title: "Support",
    links: [
      { id: 16, title: "Account Settings", url: "/account-setting" },
      { id: 17, title: "My Orders", url: "/orders" },
      { id: 18, title: "My Wallet", url: "/wallet" },
      { id: 19, title: "Track Orders", url: "/track-order" },
    ]
  }
];

const DEFAULT_COMPANY_INFO = {
  company_name: "Xordox International Pvt. Ltd.",
  address: "3398, Bagichi Acchi ji Bara Hindu Rao,\nNear Filmistan Cinema, New Delhi 110006,\nNew Delhi, India",
  customer_care: "+91-9818532463",
  newsletter_text: "Get updates on new products and offers. Coupons.",
  copyright_text: `© 2019-${new Date().getFullYear()} printmont.com All Rights Reserved.`
};

const DEFAULT_SOCIAL_LINKS = [
  { platform: "facebook", url: "https://facebook.com/printmont", icon: "fab fa-facebook-f" },
  { platform: "twitter", url: "https://twitter.com/printmont", icon: "fab fa-twitter" },
  { platform: "instagram", url: "https://instagram.com/printmont", icon: "fab fa-instagram" },
  { platform: "linkedin", url: "https://linkedin.com/company/printmont", icon: "fab fa-linkedin-in" },
  { platform: "youtube", url: "https://youtube.com/printmont", icon: "fab fa-youtube" },
];

const DEFAULT_BOTTOM_LINKS = [
  { title: "Become a Seller", url: "/become-a-seller", icon: "fas fa-store" },
  { title: "Advertising", url: "/advertising", icon: "fas fa-bullhorn" },
  { title: "Printmont Coins", url: "/printmont-coin", icon: "fas fa-coins" },
  { title: "Help Center", url: "/help-center", icon: "fas fa-question-circle" },
];

const DEFAULT_STYLING = {
  footer_bg_color: '#0e1a35',
  footer_text_color: '#cbd5e1',
  footer_heading_color: '#ffffff',
  footer_link_color: '#94a3b8',
  footer_link_hover_color: '#f8991d',
  footer_bottom_bg_color: '#091224',
  footer_border_color: 'rgba(255, 255, 255, 0.1)',
  footer_preset: 'printmont_dark'
};

const getSocialIconComponent = (platform) => {
  const p = (platform || "").toLowerCase();
  if (p.includes("fb") || p.includes("facebook")) return <FaFacebookF size={14} />;
  if (p.includes("tw") || p.includes("twitter") || p.includes("x")) return <FaXTwitter size={14} />;
  if (p.includes("insta") || p.includes("instagram")) return <FaInstagram size={14} />;
  if (p.includes("link") || p.includes("linkedin")) return <FaLinkedinIn size={14} />;
  if (p.includes("you") || p.includes("youtube")) return <FaYoutube size={14} />;
  if (p.includes("what") || p.includes("wa")) return <FaWhatsapp size={14} />;
  if (p.includes("pin")) return <FaPinterestP size={14} />;
  return <FaFacebookF size={14} />;
};

const getBottomIconPath = (title, icon) => {
  if (icon && (icon.startsWith("/") || icon.startsWith("http")) && !icon.includes("fa-")) return icon;
  const t = (title || "").toLowerCase();
  const ic = (icon || "").toLowerCase();
  if (t.includes("seller") || t.includes("sell") || ic.includes("store") || ic.includes("shop")) return "/become-seller.png";
  if (t.includes("solution") || t.includes("advertis") || t.includes("business") || ic.includes("bullhorn") || ic.includes("ad")) return "/advertising.png";
  if (t.includes("coin") || t.includes("wallet") || ic.includes("coin") || ic.includes("money")) return "/printmont-coin.png";
  if (t.includes("help") || t.includes("center") || t.includes("support") || ic.includes("question")) return "/Help-center.png";
  return "/Help-center.png";
};

const Footer = () => {
  const location = useLocation();
  const { user, getUsernamePath } = useAuth();
  const isAuthPage = location.pathname.startsWith('/login') || 
                     location.pathname.startsWith('/signup') || 
                     location.pathname.startsWith('/register') || 
                     location.pathname.startsWith('/forgot-password');

  const resolveFooterNavLink = (rawUrl) => {
    if (!rawUrl) return "/";
    const u = rawUrl.trim().toLowerCase();
    if (u === "/wallet" || u === "/my-wallet" || u === "wallet") {
      return user ? `/${getUsernamePath()}/wallet` : `/login?redirect=${encodeURIComponent('/user/wallet')}`;
    }
    if (u === "/orders" || u === "/my-orders") {
      return user ? `/${getUsernamePath()}/orders` : `/login?redirect=${encodeURIComponent('/orders')}`;
    }
    if (u === "/account-setting" || u === "/profile" || u === "/my-profile") {
      return user ? `/${getUsernamePath()}/profile` : `/login?redirect=${encodeURIComponent('/user/profile')}`;
    }
    return rawUrl;
  };

  const [footerData, setFooterData] = useState({
    sections: DEFAULT_SECTIONS,
    company_info: DEFAULT_COMPANY_INFO,
    social_links: DEFAULT_SOCIAL_LINKS,
    bottom_links: DEFAULT_BOTTOM_LINKS,
    payment_methods: [],
    styling: DEFAULT_STYLING
  });

  const [newsletterEmail, setNewsletterEmail] = useState("");
  const [newsletterSubscribed, setNewsletterSubscribed] = useState(false);

  // Fetch footer data and auto re-sync when switching back from Admin panel (window focus)
  useEffect(() => {
    let isMounted = true;

    const loadFooterData = () => {
      fetch(API_ENDPOINTS.FOOTER, { cache: 'no-cache' })
        .then(res => {
          if (!res.ok) throw new Error("Footer API response not ok");
          return res.json();
        })
        .then(data => {
          if (isMounted && data && data.success) {
            setFooterData({
              sections: data.sections && data.sections.length > 0 ? data.sections : DEFAULT_SECTIONS,
              company_info: data.company_info || DEFAULT_COMPANY_INFO,
              social_links: data.social_links && data.social_links.length > 0 ? data.social_links : DEFAULT_SOCIAL_LINKS,
              bottom_links: data.bottom_links && data.bottom_links.length > 0 ? data.bottom_links : DEFAULT_BOTTOM_LINKS,
              payment_methods: Array.isArray(data.payment_methods) ? data.payment_methods : [],
              styling: data.styling || DEFAULT_STYLING
            });
          }
        })
        .catch(err => {
          console.warn("Footer data load warning (using defaults):", err);
        });
    };

    loadFooterData();

    // Auto-refresh when tab gains focus (e.g. admin saves footer colors or links)
    window.addEventListener('focus', loadFooterData);

    return () => {
      isMounted = false;
      window.removeEventListener('focus', loadFooterData);
    };
  }, []);

  const handleNewsletterSubmit = (e) => {
    e.preventDefault();
    if (newsletterEmail.trim()) {
      setNewsletterSubscribed(true);
      setNewsletterEmail("");
      setTimeout(() => setNewsletterSubscribed(false), 4000);
    }
  };

  const sectionsToRender = footerData.sections && footerData.sections.length > 0
    ? footerData.sections
    : DEFAULT_SECTIONS;

  const companyInfo = footerData.company_info || DEFAULT_COMPANY_INFO;
  const socialLinks = footerData.social_links && footerData.social_links.length > 0
    ? footerData.social_links
    : DEFAULT_SOCIAL_LINKS;

  const bottomLinks = footerData.bottom_links && footerData.bottom_links.length > 0
    ? footerData.bottom_links
    : DEFAULT_BOTTOM_LINKS;

  const paymentMethods = Array.isArray(footerData.payment_methods) && footerData.payment_methods.length > 0
    ? footerData.payment_methods
    : [];

  const styling = footerData.styling || DEFAULT_STYLING;

  const cleanPhone = (companyInfo.customer_care || "").replace(/[^0-9+]/g, "");

  return (
    <>
      {/* 1. Feature / Trust Bar */}
      <div className="d-none d-xl-block">
        <IconX />
      </div>

      {/* 2. Desktop Footer View (>= 992px) - Synchronized with footer-management.php */}
      <footer 
        className={`desktop-footer-view small-screen-foot text-light pt-5 ${isAuthPage ? 'd-none d-lg-block' : ''}`}
        style={{
          '--pm-foot-bg': styling.footer_bg_color || '#0e1a35',
          '--pm-foot-text': styling.footer_text_color || '#cbd5e1',
          '--pm-foot-heading': styling.footer_heading_color || '#ffffff',
          '--pm-foot-link': styling.footer_link_color || '#94a3b8',
          '--pm-foot-link-hover': styling.footer_link_hover_color || '#f8991d',
          '--pm-foot-bottom-bg': styling.footer_bottom_bg_color || '#091224',
          '--pm-foot-border': styling.footer_border_color || 'rgba(255, 255, 255, 0.1)',
          backgroundColor: 'var(--pm-foot-bg)',
          color: 'var(--pm-foot-text)'
        }}
      >
        <div className="container-fluid px-4 px-md-5">
          <div className="row g-4 pb-4">

            {/* Left Half: Dynamic Sections & Link Columns */}
            <div className="col-12 col-lg-6">
              <div className="row g-3 g-md-4">
                {sectionsToRender.map((section, sIdx) => {
                  const colClass = sectionsToRender.length <= 4 
                    ? "col-6 col-md-3" 
                    : "col-6 col-md-auto flex-grow-1";

                  return (
                    <div key={section.id || sIdx} className={colClass}>
                      <h6 
                        className="footer-sec-heading text-uppercase"
                        style={{ color: styling.footer_heading_color || '#ffffff' }}
                      >
                        {section.title}
                      </h6>
                      <ul className="list-unstyled d-flex flex-column gap-2 mb-0" style={{ fontSize: '13px' }}>
                        {(section.links || []).map((link, lIdx) => {
                          const resolvedUrl = resolveFooterNavLink(link.url);
                          const isExternal = resolvedUrl && (resolvedUrl.startsWith("http://") || resolvedUrl.startsWith("https://"));
                          return (
                            <li key={link.id || lIdx}>
                              {isExternal ? (
                                <a 
                                  href={resolvedUrl} 
                                  target="_blank" 
                                  rel="noopener noreferrer" 
                                  className="footer-nav-link text-decoration-none"
                                >
                                  {link.title}
                                </a>
                              ) : (
                                <Link 
                                  to={resolvedUrl || "/"} 
                                  className="footer-nav-link text-decoration-none"
                                >
                                  {link.title}
                                </Link>
                              )}
                            </li>
                          );
                        })}
                      </ul>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Right Half: Registered Address, Social Channels & Newsletter */}
            <div 
              className="col-12 col-lg-6 border-start ps-lg-4"
              style={{ borderColor: styling.footer_border_color || 'rgba(255,255,255,0.1)' }}
            >
              <div className="row g-4">
                
                {/* Registered Address & Customer Care */}
                <div className="col-12 col-md-6">
                  <h6 
                    className="footer-sec-heading"
                    style={{ color: styling.footer_heading_color || '#ffffff' }}
                  >
                    Registered Address:
                  </h6>
                  <address style={{ fontSize: '12.5px', lineHeight: '1.6', color: styling.footer_text_color || '#cbd5e1' }}>
                    {companyInfo.company_name && (
                      <strong className="d-block mb-1 text-white">
                        {companyInfo.company_name}
                      </strong>
                    )}
                    {companyInfo.address ? (
                      companyInfo.address.split("\n").map((line, i) => (
                        <React.Fragment key={i}>
                          {line}<br />
                        </React.Fragment>
                      ))
                    ) : (
                      <>
                        3398, Bagichi Acchi ji Bara Hindu Rao,<br />
                        Near Filmistan Cinema, New Delhi 110006,<br />
                        New Delhi, India<br />
                      </>
                    )}
                    {companyInfo.customer_care && (
                      <div className="mt-2">
                        Customer Care:{" "}
                        <a 
                          href={`tel:${cleanPhone}`} 
                          className="fw-bold text-decoration-none" 
                          style={{ color: styling.footer_heading_color || '#ffffff' }}
                        >
                          <FaPhoneAlt size={11} className="me-1" />
                          {companyInfo.customer_care}
                        </a>
                      </div>
                    )}
                  </address>

                  {/* Social Channels */}
                  <div className="mt-3">
                    <span className="small opacity-75 d-block mb-2">Follow Us:</span>
                    <div className="d-flex align-items-center gap-2 flex-wrap">
                      {socialLinks.map((item, idx) => {
                        const pName = (item.platform || "").toLowerCase();
                        return (
                          <a
                            key={item.id || idx}
                            href={item.url || "#"}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="footer-social-btn"
                            aria-label={item.platform}
                            title={item.platform}
                          >
                            {item.icon ? (
                              <i className={item.icon}></i>
                            ) : (
                              getSocialIconComponent(pName)
                            )}
                          </a>
                        );
                      })}
                    </div>
                  </div>
                </div>

                {/* Newsletter Box */}
                <div className="col-12 col-md-6">
                  <h6 
                    className="footer-sec-heading"
                    style={{ color: styling.footer_heading_color || '#ffffff' }}
                  >
                    Newsletter
                  </h6>
                  <p className="small opacity-75 mb-2" style={{ color: styling.footer_text_color || '#cbd5e1' }}>
                    {companyInfo.newsletter_text || "Get updates on new products and offers. Coupons."}
                  </p>
                  <form className="d-flex mb-2" onSubmit={handleNewsletterSubmit}>
                    <input
                      id="desktop-footer-email"
                      type="email"
                      required
                      value={newsletterEmail}
                      onChange={(e) => setNewsletterEmail(e.target.value)}
                      placeholder="Email Address"
                      className="form-control rounded-start-2 rounded-end-0 border-0 shadow-none text-dark"
                      style={{ fontSize: '13px' }}
                    />
                    <button 
                      type="submit" 
                      className="btn btn-secondary rounded-end-2 rounded-start-0 fw-semibold px-3"
                      style={{ fontSize: '13px' }}
                    >
                      Submit
                    </button>
                  </form>
                  {newsletterSubscribed && (
                    <small className="text-success fw-bold d-block mt-2">
                      ✓ Thank you for subscribing to Printmont!
                    </small>
                  )}
                </div>

              </div>
            </div>

          </div>
        </div>

        {/* 3. Bottom Strip with Background Color from Admin */}
        <div 
          className="footer-bottom-bar py-3"
          style={{ backgroundColor: styling.footer_bottom_bg_color || '#091224' }}
        >
          <div className="container-fluid px-4 px-md-5">
            <div className="footer-bottom">
              
              {/* Feature / Bottom Links with Original PNG Icons */}
              <div className="footer-bottom__links small">
                {bottomLinks.map((bottom, idx) => {
                  const isExt = bottom.url && (bottom.url.startsWith("http://") || bottom.url.startsWith("https://"));
                  const iconSrc = getBottomIconPath(bottom.title, bottom.icon);

                  return (
                    <span className="footer-bottom__link" key={bottom.id || idx}>
                      <img 
                        src={iconSrc} 
                        alt="" 
                        width="20" 
                        height="20" 
                      />
                      {isExt ? (
                        <a 
                          href={bottom.url} 
                          target="_blank" 
                          rel="noopener noreferrer" 
                          className="link-text-white"
                        >
                          {bottom.title}
                        </a>
                      ) : (
                        <Link 
                          to={bottom.url || "/"} 
                          className="link-text-white"
                        >
                          {bottom.title}
                        </Link>
                      )}
                    </span>
                  );
                })}
              </div>

              {/* Copyright Text */}
              <div 
                className="footer-bottom__copy small"
                style={{ color: styling.footer_text_color || '#cbd5e1' }}
              >
                {companyInfo.copyright_text || `© 2019-${new Date().getFullYear()} printmont.com All Rights Reserved.`}
              </div>

              {/* Original Payment Mode Graphic */}
              <div className="footer-bottom__pay">
                <img 
                  src="/payment-method.svg" 
                  alt="Payment Methods" 
                  className="img-fluid" 
                  onError={(e) => { e.currentTarget.src = "./payment-method.svg"; }} 
                />
              </div>

            </div>
          </div>
        </div>

      </footer>

      {/* 4. Mobile Footer View (< 992px) */}
      {!isAuthPage && (
        <div className="mobile-footer-wrapper big-screen-foot">
          <MobileFooter footerData={footerData} />
        </div>
      )}
    </>
  );
};

export default Footer;
