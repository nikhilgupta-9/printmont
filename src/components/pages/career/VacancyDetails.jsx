import React, { useState, useEffect } from "react";
import { Container, Row, Col, Form, Button, Badge, Spinner } from "react-bootstrap";
import { useParams, Link, useNavigate } from "react-router-dom";
import {
  FaMapMarkerAlt, FaBriefcase, FaBuilding, FaMoneyBillWave,
  FaRegClock, FaArrowLeft, FaPaperPlane, FaCheckCircle,
} from "react-icons/fa";
import { toast } from "react-hot-toast";
import { API_ENDPOINTS } from "../../../config/apiEndpoints";

const THEME = "rgb(11, 83, 161)";

const Panel = ({ className = "", children }) => (
  <div className={`bg-white rounded-3 shadow-sm border ${className}`}>{children}</div>
);

const prettify = (value) =>
  String(value || "")
    .replace(/[_-]+/g, " ")
    .replace(/\b\w/g, (c) => c.toUpperCase());

const formatDate = (value) => {
  if (!value) return "";
  const d = new Date(String(value).replace(" ", "T"));
  return Number.isNaN(d.getTime())
    ? ""
    : d.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" });
};

/**
 * Renders a field that may hold plain text, newline-separated lines, or a JSON
 * array — the careers table is inconsistent across rows, so all three shapes
 * are handled rather than assuming one.
 */
const DetailBlock = ({ title, value }) => {
  if (!value) return null;

  let lines = [];
  try {
    const parsed = JSON.parse(value);
    lines = Array.isArray(parsed) ? parsed : String(value).split(/\r?\n/);
  } catch {
    lines = String(value).split(/\r?\n/);
  }
  lines = lines.map((l) => String(l).trim()).filter(Boolean);
  if (!lines.length) return null;

  return (
    <div className="mb-4">
      <h5 className="fw-bold text-dark mb-2 fs-6">{title}</h5>
      {lines.length === 1 ? (
        <p className="text-secondary mb-0" style={{ lineHeight: 1.7 }}>{lines[0]}</p>
      ) : (
        <ul className="text-secondary mb-0 ps-3" style={{ lineHeight: 1.8 }}>
          {lines.map((line, i) => <li key={i}>{line.replace(/^[-*•]\s*/, "")}</li>)}
        </ul>
      )}
    </div>
  );
};

const VacancyDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  const [job, setJob] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const [form, setForm] = useState({
    full_name: "", email: "", phone: "",
    experience: "", linkedin_url: "", cover_letter: "",
  });
  const [resume, setResume] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [applied, setApplied] = useState(false);

  useEffect(() => {
    window.scrollTo(0, 0);
    let cancelled = false;

    const load = async () => {
      setLoading(true);
      setError("");
      try {
        const res = await fetch(API_ENDPOINTS.CAREER_DETAIL(id));
        const json = await res.json();
        if (cancelled) return;

        // The API nests the record under data.career.
        const record = json?.data?.career || json?.data || null;
        if (!json?.success || !record) {
          setError("This vacancy is no longer available.");
        } else {
          setJob(record);
        }
      } catch (err) {
        console.error("Failed to load vacancy:", err);
        if (!cancelled) setError("Could not reach the server. Please try again.");
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    load();
    return () => { cancelled = true; };
  }, [id]);

  const handleChange = (e) => setForm((p) => ({ ...p, [e.target.name]: e.target.value }));

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (submitting) return;

    if (!form.full_name.trim() || !form.email.trim() || !form.phone.trim()) {
      toast.error("Name, email and phone are required.");
      return;
    }
    if (!resume) {
      toast.error("Please attach your resume.");
      return;
    }

    setSubmitting(true);
    try {
      // Sent as multipart so the resume file uploads. The API accepts both
      // form-data and JSON, but only form-data can carry the file.
      const body = new FormData();
      body.append("career_id", id);
      Object.entries(form).forEach(([key, value]) => body.append(key, value.trim()));
      body.append("resume", resume);

      const res = await fetch(API_ENDPOINTS.CAREER_POST, { method: "POST", body });
      const json = await res.json();

      if (!res.ok || !json.success) {
        toast.error(json.message || json.error || "Could not submit your application.");
        return;
      }

      setApplied(true);
      toast.success("Application submitted. We will be in touch.");
    } catch (err) {
      console.error("Application failed:", err);
      toast.error("Could not reach the server. Please try again.");
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="bg-light py-5 text-center" style={{ minHeight: "70vh" }}>
        <Spinner animation="border" style={{ color: THEME }} />
      </div>
    );
  }

  if (error || !job) {
    return (
      <div className="bg-light py-5" style={{ minHeight: "70vh" }}>
        <Container>
          <Panel className="p-5 text-center">
            <h5 className="fw-bold text-dark mb-2">Vacancy not found</h5>
            <p className="text-secondary mb-4">{error || "This role may have been filled or withdrawn."}</p>
            <Button style={{ backgroundColor: THEME, borderColor: THEME }} onClick={() => navigate("/careers")}>
              View all openings
            </Button>
          </Panel>
        </Container>
      </div>
    );
  }

  const title = job.job_title || job.title || "Vacancy";
  const meta = [
    { icon: <FaBuilding />, label: prettify(job.department) },
    { icon: <FaMapMarkerAlt />, label: job.location },
    { icon: <FaBriefcase />, label: prettify(job.job_type) },
    { icon: <FaMoneyBillWave />, label: job.salary_range },
    { icon: <FaRegClock />, label: job.experience ? `${job.experience} experience` : "" },
  ].filter((m) => m.label);

  return (
    <div className="bg-light py-3 py-md-4" style={{ minHeight: "85vh" }}>
      <Container>
        <Link
          to="/careers"
          className="text-decoration-none d-inline-flex align-items-center gap-2 mb-3"
          style={{ color: THEME }}
        >
          <FaArrowLeft size={13} /> All openings
        </Link>

        <Row className="g-3">
          <Col xs={12} lg={8}>
            <Panel className="p-3 p-md-4 mb-3">
              <h1 className="fw-bold text-dark fs-3 mb-2">{title}</h1>
              <div className="d-flex flex-wrap gap-3 text-secondary small mb-3">
                {meta.map((m, i) => (
                  <span key={i} className="d-flex align-items-center gap-1">
                    <span style={{ color: THEME }}>{m.icon}</span> {m.label}
                  </span>
                ))}
              </div>
              {job.application_deadline && (
                <Badge bg="warning" text="dark" className="fw-semibold">
                  Apply before {formatDate(job.application_deadline)}
                </Badge>
              )}
            </Panel>

            <Panel className="p-3 p-md-4">
              <DetailBlock title="About the role" value={job.description} />
              <DetailBlock title="Responsibilities" value={job.responsibilities} />
              <DetailBlock title="Requirements" value={job.requirements} />
            </Panel>
          </Col>

          {/* APPLY FORM */}
          <Col xs={12} lg={4}>
            <div className="sticky-top" style={{ top: "135px", zIndex: 10 }}>
              <Panel className="p-3 p-md-4">
                {applied ? (
                  <div className="text-center py-3">
                    <FaCheckCircle size={38} className="text-success mb-3" />
                    <h5 className="fw-bold text-dark mb-2">Application received</h5>
                    <p className="text-secondary small mb-3">
                      Thanks for applying for <strong>{title}</strong>. Our team will review it and get back to you.
                    </p>
                    <Button variant="outline-secondary" size="sm" onClick={() => navigate("/careers")}>
                      Browse other roles
                    </Button>
                  </div>
                ) : (
                  <>
                    <h5 className="fw-bold text-dark mb-3 fs-6">Apply for this role</h5>
                    <Form onSubmit={handleSubmit}>
                      <Form.Group className="mb-3">
                        <Form.Label className="small fw-semibold text-secondary">Full Name *</Form.Label>
                        <Form.Control size="sm" name="full_name" value={form.full_name} onChange={handleChange} required />
                      </Form.Group>

                      <Form.Group className="mb-3">
                        <Form.Label className="small fw-semibold text-secondary">Email *</Form.Label>
                        <Form.Control size="sm" type="email" name="email" value={form.email} onChange={handleChange} required />
                      </Form.Group>

                      <Form.Group className="mb-3">
                        <Form.Label className="small fw-semibold text-secondary">Phone *</Form.Label>
                        <Form.Control size="sm" name="phone" value={form.phone} onChange={handleChange} required />
                      </Form.Group>

                      <Form.Group className="mb-3">
                        <Form.Label className="small fw-semibold text-secondary">Total Experience</Form.Label>
                        <Form.Control size="sm" name="experience" placeholder="e.g. 3 years" value={form.experience} onChange={handleChange} />
                      </Form.Group>

                      <Form.Group className="mb-3">
                        <Form.Label className="small fw-semibold text-secondary">Resume *</Form.Label>
                        <Form.Control
                          size="sm" type="file" accept=".pdf,.doc,.docx"
                          onChange={(e) => setResume(e.target.files?.[0] || null)}
                          required
                        />
                        <Form.Text className="text-muted" style={{ fontSize: "0.72rem" }}>
                          PDF or Word document.
                        </Form.Text>
                      </Form.Group>

                      <Form.Group className="mb-3">
                        <Form.Label className="small fw-semibold text-secondary">LinkedIn</Form.Label>
                        <Form.Control size="sm" name="linkedin_url" value={form.linkedin_url} onChange={handleChange} />
                      </Form.Group>

                      <Form.Group className="mb-3">
                        <Form.Label className="small fw-semibold text-secondary">Cover Letter</Form.Label>
                        <Form.Control size="sm" as="textarea" rows={3} name="cover_letter" value={form.cover_letter} onChange={handleChange} />
                      </Form.Group>

                      <Button
                        type="submit" disabled={submitting}
                        className="w-100 fw-bold border-0 d-flex align-items-center justify-content-center gap-2"
                        style={{ backgroundColor: THEME }}
                      >
                        {submitting
                          ? <Spinner size="sm" animation="border" />
                          : <><FaPaperPlane size={13} /> Submit Application</>}
                      </Button>
                    </Form>
                  </>
                )}
              </Panel>
            </div>
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default VacancyDetails;
