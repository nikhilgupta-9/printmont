import React, { useEffect, useState } from 'react';
import { Link } from 'react-router';
import { Spinner } from 'react-bootstrap';
import { API_ENDPOINTS } from '../../config/apiEndpoints';

/**
 * Security page. The Q&A sections are managed in the admin panel
 * (security-management.php) and served by security-api.php, so they can be
 * edited without a release. The Privacy Policy and Contact links below stay
 * in code because they are navigation, not content.
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
        console.error('Failed to load security sections:', err);
        if (!cancelled) setFailed(true);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    load();
    return () => { cancelled = true; };
  }, []);

  return (
    <div className="container p-4 bg-white">

      <h4 className="mb-4">Safe and Secure Shopping</h4>

      {loading ? (
        <div className="text-center py-4">
          <Spinner animation="border" variant="primary" size="sm" />
        </div>
      ) : failed ? (
        <p className="text-muted small mb-4">
          We could not load this section right now. Please refresh, or{' '}
          <Link to="/contact" className="text-decoration-none">contact us</Link> if it persists.
        </p>
      ) : (
        <div className="security-faqs mb-4">
          {sections.map((section) => (
            <div className="mb-3" key={section.section_key || section.id}>
              <h6 className="mb-0">{section.heading}</h6>
              {/* Content is rich text authored in the admin CKEditor. */}
              <div
                className="ms-3 mb-0 small"
                dangerouslySetInnerHTML={{ __html: section.content || '' }}
              />
            </div>
          ))}
        </div>
      )}

      <hr className="my-3" />

      {/* Privacy Policy */}
      <div className="privacy-policy mb-3">
        <h6 className="mb-1">Privacy Policy</h6>
        <p className="ms-3 mb-0 small">
          Printmont.com respects your privacy and is committed to protecting it. For more details,
          please see our <Link to="/privacy-policy" className="text-decoration-none">Privacy Policy</Link>
        </p>
      </div>

      {/* Contact Us */}
      <div className="contact-us">
        <h6 className="mb-1">Contact Us</h6>
        <p className="ms-3 mb-0 small">
          Couldn&rsquo;t find the information you need? Please{' '}
          <Link to="/contact" className="text-decoration-none">Contact Us</Link>
        </p>
      </div>

    </div>
  );
};

export default SecurityInfo;
