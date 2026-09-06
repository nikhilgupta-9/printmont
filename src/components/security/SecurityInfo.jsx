import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Container } from "react-bootstrap";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import "../pages/marketing-page.css";

/**
 * Security page redesigned with the clean marketing layout.
 * Dynamic Q&A sections are authored in the admin panel and served by security-api.php.
 */
const SecurityInfo = () => {
  const [sections, setSections] = useState([]);
  const [loading, setLoading] = useState(true);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    let cancelled = false;

    const load = async () => {
      try {
        const res = await fetch(API_ENDPOINTS.SECURITY);
        const json = await res.json();
        if (cancelled) return;

        if (json.success && Array.isArray(json.data)) {
          setSections(json.data);
        } else {
          setFailed(true);
        }
      } catch (err) {
        console.error("Failed to load security sections:", err);
        if (!cancelled) setFailed(true);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    load();
    return () => {
      cancelled = true;
    };
  }, []);

  if (loading) {
    return (
      <div className="mk-page">
        <Container className="mk-article">
          <div className="shimmer-bg rounded mb-4" style={{ height: 34, width: "40%" }} />
          <div className="shimmer-bg rounded mb-3" style={{ height: 90 }} />
          <div className="shimmer-bg rounded" style={{ height: 220 }} />
        </Container>
      </div>
    );
  }

  if (failed) {
    return (
      <div className="mk-page">
        <Container className="mk-article">
          <h1 className="mk-page-title">
            <span className="text-theme">Safe &amp; Secure</span> Shopping
          </h1>
          <p className="mk-para">This page is unavailable right now. Please refresh in a moment.</p>
        </Container>
      </div>
    );
  }

  return (
    <div className="mk-page">
      <Container className="mk-article">
        {/* Ruled, two-tone page title */}
        <h1 className="mk-page-title">
          <span className="text-theme">Safe &amp; Secure</span> Shopping
        </h1>

        {/* Intro */}
        <h2 className="mk-sub">Your Privacy and Payment Security is Our Highest Priority</h2>
        <p className="mk-para">
          At Printmont, we employ industry-standard encryption protocols, secure payment gateways, and strict data confidentiality to make your shopping experience safe, transparent, and completely worry-free.
        </p>

        {/* Security Highlights */}
        <p className="mk-lead-in">Key security measures we uphold across all transactions:</p>
        <ul className="mk-list">
          <li>
            <span className="mk-list__term">256-bit SSL/TLS Encryption</span> - All communications between your browser and our servers are fully encrypted.
          </li>
          <li>
            <span className="mk-list__term">PCI-DSS Compliant Payment Gateways</span> - Credit/debit card details are processed through bank-grade secured payment networks.
          </li>
          <li>
            <span className="mk-list__term">Zero Sensitive Card Storage</span> - Printmont never stores full card numbers or CVV codes on our servers.
          </li>
          <li>
            <span className="mk-list__term">Multi-Factor Authentication &amp; 3D Secure</span> - OTP verification enabled for every online debit/credit card and netbanking transaction.
          </li>
        </ul>

        {/* Dynamic Q&A Sections from Admin API */}
        {sections.length > 0 && (
          <>
            <p className="mk-lead-in mt-4">Frequently asked questions regarding security and payments:</p>
            {sections.map((section) => (
              <div className="mk-qa" key={section.section_key || section.id}>
                <p className="mk-qa__q">{section.heading}</p>
                <div
                  className="mk-para mb-0"
                  dangerouslySetInnerHTML={{ __html: section.content || "" }}
                />
              </div>
            ))}
          </>
        )}

        {/* Privacy Policy & Contact Closing Blocks */}
        <div className="mt-4 pt-3 border-top">
          <p className="mk-para mk-para--close">
            Printmont.com respects your privacy and is committed to protecting it. For more details, please see our{" "}
            <Link to="/privacy-policy" className="mk-mail">
              Privacy Policy
            </Link>.
          </p>
          <p className="mk-para mk-para--close mt-2">
            Couldn&rsquo;t find the information you need? Please{" "}
            <Link to="/contact" className="mk-mail">
              Contact Us
            </Link>{" "}
            or reach out directly to our customer support team at{" "}
            <a href="mailto:support@printmont.com" className="mk-mail">
              support@printmont.com
            </a>.
          </p>
        </div>
      </Container>
    </div>
  );
};

export default SecurityInfo;
