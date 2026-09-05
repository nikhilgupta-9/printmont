import React, { useState, useEffect, useMemo } from "react";
import { Container, Row, Col, Nav, Card, Badge, Form, InputGroup, Spinner } from "react-bootstrap";
import { useLocation, Link } from "react-router-dom";
import "./Policy.css";
import {
  FaFileContract,
  FaUserShield,
  FaTruck,
  FaUndoAlt,
  FaCheckCircle,
  FaSearch,
  FaShieldAlt,
  FaEnvelope,
  FaGavel,
} from "react-icons/fa";
import { API_ENDPOINTS } from "../../config/apiEndpoints";

/**
 * Company policies, driven entirely by policies-api.php.
 *
 * Every policy — its tab, heading, body and bullet points — is authored in the
 * admin panel (policy-edit.php). Adding a policy there makes a new tab appear
 * here with no release. Previously all of this text was hardcoded in JSX, so
 * the admin editor had no effect on what customers actually read.
 */

/** Icons are presentation, so they stay in code, keyed by policy_key. */
const POLICY_ICONS = {
  terms: <FaFileContract size={18} />,
  "terms-of-use": <FaGavel size={18} />,
  privacy: <FaUserShield size={18} />,
  shipping: <FaTruck size={18} />,
  refund: <FaUndoAlt size={18} />,
  return: <FaUndoAlt size={18} />,
};

const iconFor = (key) => POLICY_ICONS[key] || <FaFileContract size={18} />;

/** Strip tags so the search box can match on rich-text bodies. */
const toPlainText = (html) =>
  String(html || "").replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();

const PolicyPage = () => {
  const location = useLocation();
  const [searchTerm, setSearchTerm] = useState("");
  const [policies, setPolicies] = useState([]);
  const [loading, setLoading] = useState(true);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    let cancelled = false;

    const load = async () => {
      try {
        const res = await fetch(API_ENDPOINTS.POLICIES);
        const json = await res.json();
        if (cancelled) return;

        if (json.success && Array.isArray(json.data)) {
          // Only active policies belong on the storefront.
          setPolicies(json.data.filter((p) => (p.status ?? "active") === "active"));
        } else {
          setFailed(true);
        }
      } catch (err) {
        console.error("Failed to load policies:", err);
        if (!cancelled) setFailed(true);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    load();
    return () => { cancelled = true; };
  }, []);

  /**
   * Map the URL onto a policy_key. The routes are legacy shapes
   * (/privacy-policy, /policy/privacy, /return-policy …) so match loosely
   * and fall back to the first policy rather than a hardcoded default.
   */
  const activeKey = useMemo(() => {
    if (!policies.length) return null;

    const path = location.pathname.toLowerCase();
    const slug = path.split("/").filter(Boolean).pop() || "";

    // Exact slug wins: /policy/terms-of-use must not be captured by "terms".
    const exact = policies.find((p) => p.policy_key.toLowerCase() === slug);
    if (exact) return exact.policy_key;

    // Legacy URL shapes: /terms-of-use, /privacy-policy, /shipping-policy …
    // Strip the decorative "-policy" suffix and try again.
    const bare = slug.replace(/-policy$/, "");
    const byBare = policies.find((p) => p.policy_key.toLowerCase() === bare);
    if (byBare) return byBare.policy_key;

    // /terms-and-conditions is the commercial contract, i.e. the "terms" policy.
    if (slug.startsWith("terms-and-")) {
      const terms = policies.find((p) => p.policy_key === "terms");
      if (terms) return terms.policy_key;
    }

    // "return-policy" should land on the refund policy.
    if (slug.includes("return")) {
      const refund = policies.find((p) => p.policy_key === "refund");
      if (refund) return refund.policy_key;
    }

    // Last resort: longest matching key, so a specific slug beats a prefix of it.
    const loose = policies
      .filter((p) => slug.includes(p.policy_key.toLowerCase()))
      .sort((a, b) => b.policy_key.length - a.policy_key.length)[0];
    if (loose) return loose.policy_key;

    return policies[0].policy_key;
  }, [location.pathname, policies]);

  const active = policies.find((p) => p.policy_key === activeKey) || null;

  const pathFor = (key) => `/policy/${key}`;

  return (
    <div className="policy-page bg-light py-3 py-md-5" style={{ minHeight: "85vh", overflowX: "hidden" }}>
      <Container>
        {/* HEADER */}
        <div className="text-center mb-4 mb-md-5">
          <Badge bg="primary-subtle" className="text-primary fw-bold px-3 py-2 mb-2 rounded-pill text-uppercase fs-7">
            PrintMont Legal &amp; Operations
          </Badge>
          <h1 className="fw-bold text-dark display-6 display-md-5 mb-2">Company Policies &amp; Terms</h1>
          <p className="text-secondary mx-auto fs-6" style={{ maxWidth: "650px", lineHeight: "1.6" }}>
            Transparent legal terms, website usage rules, privacy guidelines, delivery schedules,
            and refund policies governing PrintMont.
          </p>

          <div className="mx-auto mt-3" style={{ maxWidth: "500px" }}>
            <InputGroup className="shadow-sm rounded-pill overflow-hidden bg-white border">
              <InputGroup.Text className="bg-white border-0 ps-3 text-muted">
                <FaSearch />
              </InputGroup.Text>
              <Form.Control
                type="text"
                placeholder="Search policy terms (e.g. GST, copyright, shipping, returns)..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="border-0 py-2.5 shadow-none text-dark small"
              />
            </InputGroup>
          </div>
        </div>

        <Row className="g-4">
          {/* NAVIGATION */}
          <Col lg={3} md={12}>
            <Card className="border-0 shadow-sm rounded-4 p-3 bg-white sticky-top mb-4 mb-lg-0" style={{ top: "90px", zIndex: 10 }}>
              <h6 className="fw-bold text-dark px-2 mb-3 pb-2 border-bottom">Policy Navigation</h6>

              <Nav className="flex-column gap-1">
                {policies.map((policy) => (
                  <Nav.Item key={policy.policy_key}>
                    <Link
                      to={pathFor(policy.policy_key)}
                      className={`nav-link d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 fw-semibold transition-all text-decoration-none ${
                        activeKey === policy.policy_key ? "bg-primary text-white shadow-sm" : "text-secondary hover-bg-light"
                      }`}
                    >
                      <span className={activeKey === policy.policy_key ? "text-white" : "text-primary"}>
                        {iconFor(policy.policy_key)}
                      </span>
                      <span>{policy.heading}</span>
                    </Link>
                  </Nav.Item>
                ))}
              </Nav>

              <div className="mt-4 p-3 bg-primary-subtle rounded-3 text-primary border border-primary-subtle">
                <div className="d-flex align-items-center gap-2 mb-1">
                  <FaShieldAlt /> <strong className="small">Legal Desk Assistance</strong>
                </div>
                <p className="small text-dark mb-2" style={{ fontSize: "0.78rem" }}>
                  Have questions about our terms? Reach our compliance desk.
                </p>
                <a href="mailto:legal@printmont.com" className="small fw-bold text-primary text-decoration-none d-flex align-items-center gap-1">
                  <FaEnvelope size={12} /> legal@printmont.com
                </a>
              </div>
            </Card>
          </Col>

          {/* CONTENT */}
          <Col lg={9} md={12}>
            <Card className="border-0 shadow-sm rounded-4 p-3 p-md-4 p-lg-5 bg-white">
              {loading ? (
                <div className="text-center py-5">
                  <Spinner animation="border" variant="primary" />
                </div>
              ) : failed ? (
                <div className="text-center py-5">
                  <h5 className="fw-bold text-dark mb-2">Policies are unavailable right now</h5>
                  <p className="text-secondary small mb-0">
                    Please refresh, or <Link to="/contact">contact us</Link> if this keeps happening.
                  </p>
                </div>
              ) : !active ? (
                <div className="text-center py-5">
                  <h5 className="fw-bold text-dark mb-2">No policies published yet</h5>
                  <p className="text-secondary small mb-0">Please check back shortly.</p>
                </div>
              ) : (
                <PolicyBody policy={active} searchTerm={searchTerm} />
              )}
            </Card>
          </Col>
        </Row>
      </Container>
    </div>
  );
};

/** One policy: heading, rich-text body, and its bullet points. */
const PolicyBody = ({ policy, searchTerm }) => {
  const term = searchTerm.trim().toLowerCase();

  const points = Array.isArray(policy.points) ? policy.points : [];
  const matchingPoints = term
    ? points.filter((p) => String(p).toLowerCase().includes(term))
    : points;

  // When a search is active and nothing in this policy matches, say so rather
  // than rendering a body that looks like a non-result.
  const bodyMatches = !term || toPlainText(policy.description).toLowerCase().includes(term)
    || policy.heading.toLowerCase().includes(term);
  const nothingMatches = term && !bodyMatches && matchingPoints.length === 0;

  return (
    <section className="policy-section-animation" style={{ lineHeight: "1.8", color: "#334155" }}>
      <div className="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
        <div
          className="rounded-circle bg-primary-subtle p-3 text-primary d-flex align-items-center justify-content-center"
          style={{ width: "56px", height: "56px" }}
        >
          {iconFor(policy.policy_key)}
        </div>
        <div>
          <h3 className="fw-bold mb-0 text-dark">{policy.heading}</h3>
          {policy.updated_at && (
            <p className="text-muted small mb-0">
              Last updated:{" "}
              {new Date(String(policy.updated_at).replace(" ", "T")).toLocaleDateString("en-IN", {
                day: "2-digit", month: "long", year: "numeric",
              })}
            </p>
          )}
        </div>
      </div>

      {nothingMatches ? (
        <p className="text-secondary small mb-0">
          Nothing in this policy matches &ldquo;{searchTerm}&rdquo;. Try another policy from the list.
        </p>
      ) : (
        <>
          {policy.description && (
            <div
              className="policy-rich-text text-secondary mb-4"
              style={{ lineHeight: "1.7" }}
              /* Rich text authored in the admin CKEditor. */
              dangerouslySetInnerHTML={{ __html: policy.description }}
            />
          )}

          {matchingPoints.length > 0 && (
            <Row className="g-3 g-md-4 mb-4">
              {matchingPoints.map((point, idx) => (
                <Col xs={12} md={6} key={idx}>
                  <div className="p-3 rounded-3 bg-light border h-100 d-flex gap-2">
                    <FaCheckCircle className="text-primary flex-shrink-0 mt-1" size={14} />
                    <p className="small text-secondary m-0" style={{ lineHeight: "1.6" }}>{point}</p>
                  </div>
                </Col>
              ))}
            </Row>
          )}
        </>
      )}
    </section>
  );
};

export default PolicyPage;
