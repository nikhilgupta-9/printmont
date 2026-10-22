import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import {
  FaFacebookF,
  FaInstagram,
  FaLinkedinIn,
  FaYoutube
} from "react-icons/fa";
import { FaXTwitter } from "react-icons/fa6";
import { BsShieldFillCheck } from "react-icons/bs";
import { API_ENDPOINTS, resolveImageUrl } from "../../config/apiEndpoints";
import "./MobileFooter.css";

const getSocialIconComponent = (platform) => {
  const p = (platform || "").toLowerCase();
  if (p.includes("fb") || p.includes("facebook")) return <FaFacebookF size={16} />;
  if (p.includes("tw") || p.includes("twitter") || p.includes("x")) return <FaXTwitter size={15} />;
  if (p.includes("insta") || p.includes("instagram")) return <FaInstagram size={16} />;
  if (p.includes("link") || p.includes("linkedin")) return <FaLinkedinIn size={15} />;
  if (p.includes("you") || p.includes("youtube")) return <FaYoutube size={16} />;
  return <FaFacebookF size={16} />;
};

const DEFAULT_SOCIALS = [
  { platform: "facebook", url: "https://facebook.com/printmont" },
  { platform: "twitter", url: "https://twitter.com/printmont" },
  { platform: "instagram", url: "https://instagram.com/printmont" },
  { platform: "linkedin", url: "https://linkedin.com/company/printmont" },
  { platform: "youtube", url: "https://youtube.com/printmont" },
];

const MobileFooter = ({ footerData: propFooterData }) => {
  const [footerData, setFooterData] = useState(propFooterData || null);

  useEffect(() => {
    if (propFooterData) {
      setFooterData(propFooterData);
      return;
    }

    fetch(API_ENDPOINTS.FOOTER)
      .then(res => {
        if (!res.ok) throw new Error("Mobile footer API response not ok");
        return res.json();
      })
      .then(data => {
        if (data && data.success) {
          setFooterData(data);
        }
      })
      .catch(err => {
        console.warn("Mobile footer data load error:", err);
      });
  }, [propFooterData]);

  const socialLinks = (footerData && footerData.social_links && footerData.social_links.length > 0)
    ? footerData.social_links
    : DEFAULT_SOCIALS;

  const copyrightText = footerData?.company_info?.copyright_text || `© Copyright 2019 - ${new Date().getFullYear()} Printmont.com All Rights Reserved.`;
  const logoUrl = footerData?.logo_url ? resolveImageUrl(footerData.logo_url) : "/PrintLogo.png";

  return (
    <footer className="mob-footer">
      {/* 1. Top Feature / Trust Bar (3 Columns) */}
      <div className="mob-footer__trust-bar">
        <div className="mob-footer__trust-item">
          <div className="mob-footer__trust-icon-wrap">
            <img src="/free.png" alt="Free Shipping" className="mob-footer__trust-icon" />
          </div>
          <span className="mob-footer__trust-title">Free Shipping.</span>
          <span className="mob-footer__trust-sub">On orders above standard cart.</span>
        </div>

        <div className="mob-footer__trust-item">
          <div className="mob-footer__trust-icon-wrap">
            <img src="/secure.png" alt="Online Payment" className="mob-footer__trust-icon" />
          </div>
          <span className="mob-footer__trust-title">Online Payment</span>
          <span className="mob-footer__trust-sub">100% Safe and secure payments.</span>
        </div>

        <div className="mob-footer__trust-item">
          <div className="mob-footer__trust-icon-wrap">
            <img src="/original.png" alt="100% Original Guarantee" className="mob-footer__trust-icon" />
          </div>
          <span className="mob-footer__trust-title">100% Quality Guaranteed</span>
          <span className="mob-footer__trust-sub">Premium customized prints.</span>
        </div>
      </div>

      {/* 2. Action Pill Buttons */}
      <div className="mob-footer__btn-row">
        <Link to="/contact" className="mob-footer__pill-btn">
          CONTACT US
        </Link>
        <Link to="/quick-links" className="mob-footer__pill-btn">
          QUICK LINKS
        </Link>
        <Link to="/help-center" className="mob-footer__pill-btn">
          HELP CENTER
        </Link>
      </div>

      {/* 3. Brand Logo */}
      <div className="mob-footer__logo-wrap">
        <Link to="/" className="d-inline-block">
          <img 
            src={logoUrl} 
            alt="Printmont" 
            className="mob-footer__logo" 
            onError={(e) => { e.currentTarget.src = "/PrintLogo.png"; }} 
          />
        </Link>
      </div>

      {/* 4. Security Shield & Guarantee */}
      <div className="mob-footer__security">
        <BsShieldFillCheck className="mob-footer__shield-icon" />
        <h4 className="mob-footer__security-text">100% Safe and secure payments.</h4>
      </div>

      {/* 5. Follow Us */}
      <div className="mob-footer__follow">
        <div className="mob-footer__follow-title">Follow us</div>
        <div className="mob-footer__social-row">
          {socialLinks.map((item, idx) => {
            const pName = (item.platform || "").toLowerCase();
            return (
              <a
                key={item.id || idx}
                href={item.url || "#"}
                target="_blank"
                rel="noopener noreferrer"
                className={`mob-footer__social-btn ${pName}`}
                aria-label={item.platform}
              >
                {getSocialIconComponent(pName)}
              </a>
            );
          })}
        </div>
      </div>

      {/* 6. Copyright */}
      <p className="mob-footer__copyright">
        {copyrightText}
      </p>
    </footer>
  );
};

export default MobileFooter;
