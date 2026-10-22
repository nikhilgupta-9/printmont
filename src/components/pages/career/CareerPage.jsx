import React, { useState, useEffect, useMemo } from "react";
import { useNavigate, Link } from "react-router-dom";
import {
  Container,
  Row,
  Col,
  Modal,
  Form,
  Spinner
} from "react-bootstrap";
import {
  FaBriefcase,
  FaMapMarkerAlt,
  FaRegClock,
  FaMoneyBillWave,
  FaSearch,
  FaArrowRight,
  FaPaperPlane,
  FaCheckCircle,
  FaRocket,
  FaLaptopCode,
  FaGraduationCap,
  FaHeartbeat,
  FaCoins,
  FaUsers
} from "react-icons/fa";
import { toast } from "react-hot-toast";
import { API_ENDPOINTS } from "../../../config/apiEndpoints";
import { updatePageSEO } from "../../../utils/seo";
import "./Career.css";

const DEPARTMENTS = [
  { key: "all", label: "All Roles" },
  { key: "technology", label: "💻 Technology & Engineering" },
  { key: "design", label: "🎨 Design & Creative" },
  { key: "marketing", label: "📈 Marketing & Growth" },
  { key: "sales", label: "🤝 Sales & Business Dev" },
  { key: "operations", label: "📦 Operations & Logistics" },
  { key: "hr", label: "👥 HR & Talent" },
  { key: "finance", label: "💰 Finance & Legal" }
];

const PERKS = [
  {
    icon: <FaRocket />,
    title: "Fast-Track Career Growth",
    desc: "Rapid ownership, transparent appraisals, and leadership pathways across our fast-scaling teams."
  },
  {
    icon: <FaLaptopCode />,
    title: "Modern Tech & Tools",
    desc: "Work with modern web stacks, high-performance machines, and top-tier SaaS subscriptions."
  },
  {
    icon: <FaHeartbeat />,
    title: "Comprehensive Health Cover",
    desc: "Complete medical and accidental coverage for you and your immediate family members."
  },
  {
    icon: <FaCoins />,
    title: "Printmont Coins & Discounts",
    desc: "Exclusive employee store credits and steep discounts on all custom prints and merchandise."
  },
  {
    icon: <FaGraduationCap />,
    title: "Learning & Conference Stipend",
    desc: "Dedicated annual budget for online courses, tech certifications, books, and summits."
  },
  {
    icon: <FaUsers />,
    title: "Inclusive & Hybrid Culture",
    desc: "Collaborative, open environment that celebrates diversity, creativity, and work-life balance."
  }
];

export default function CareerPage() {
  const navigate = useNavigate();
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeDept, setActiveDept] = useState("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [jobTypeFilter, setJobTypeFilter] = useState("all");

  // Quick Apply Modal State
  const [showModal, setShowModal] = useState(false);
  const [selectedJob, setSelectedJob] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [appliedSuccess, setAppliedSuccess] = useState(false);
  const [formData, setFormData] = useState({
    fullName: "",
    email: "",
    phone: "",
    experience: "",
    location: "",
    linkedinUrl: "",
    coverLetter: "",
    resume: null
  });

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: "Careers at Printmont - Join India's Leading Custom Merchandise Team",
      description: "Explore open job positions across Technology, Design, Marketing, Operations, and Sales at Printmont. Apply online today.",
      keywords: "printmont careers, custom printing jobs, tech openings printmont, designer vacancies, graphic design jobs",
      type: "website"
    });

    const fetchCareers = async () => {
      try {
        setLoading(true);
        const res = await fetch(API_ENDPOINTS.CAREER_GET || "/api/career-get-api.php");
        const json = await res.json();

        let apiJobs = [];
        if (json?.success && json.data && Array.isArray(json.data.careers)) {
          apiJobs = json.data.careers;
        } else if (Array.isArray(json)) {
          apiJobs = json;
        }

        setJobs(apiJobs);
      } catch (err) {
        console.error("Error fetching careers:", err);
        setJobs([]);
      } finally {
        setLoading(false);
      }
    };

    fetchCareers();
  }, []);

  // Filter jobs based on active tab, search query, and job type
  const filteredJobs = useMemo(() => {
    return jobs.filter((job) => {
      // Dept filter
      if (activeDept !== "all" && job.department?.toLowerCase() !== activeDept.toLowerCase()) {
        return false;
      }
      // Job Type filter
      if (jobTypeFilter !== "all" && job.job_type?.toLowerCase() !== jobTypeFilter.toLowerCase()) {
        return false;
      }
      // Search query
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase().trim();
        const titleMatch = (job.job_title || job.title || "").toLowerCase().includes(q);
        const deptMatch = (job.department || "").toLowerCase().includes(q);
        const locMatch = (job.location || "").toLowerCase().includes(q);
        const descMatch = (job.description || "").toLowerCase().includes(q);
        const reqMatch = (job.requirements || "").toLowerCase().includes(q);
        return titleMatch || deptMatch || locMatch || descMatch || reqMatch;
      }
      return true;
    });
  }, [jobs, activeDept, jobTypeFilter, searchQuery]);

  const handleOpenApplyModal = (job, e) => {
    if (e) e.stopPropagation();
    setSelectedJob(job);
    setAppliedSuccess(false);
    setShowModal(true);
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setSelectedJob(null);
    setAppliedSuccess(false);
    setFormData({
      fullName: "",
      email: "",
      phone: "",
      experience: "",
      location: "",
      linkedinUrl: "",
      coverLetter: "",
      resume: null
    });
  };

  const handleApplySubmit = async (e) => {
    e.preventDefault();
    if (!selectedJob) return;

    if (!formData.fullName.trim() || !formData.email.trim() || !formData.phone.trim()) {
      toast.error("Please fill in all required fields (Name, Email, Phone).");
      return;
    }
    if (!formData.resume) {
      toast.error("Please attach your resume (PDF/DOCX).");
      return;
    }

    try {
      setIsSubmitting(true);
      const payload = new FormData();
      payload.append("career_id", selectedJob.id);
      payload.append("full_name", formData.fullName.trim());
      payload.append("email", formData.email.trim());
      payload.append("phone", formData.phone.trim());
      payload.append("experience", formData.experience.trim());
      payload.append("location", formData.location.trim());
      payload.append("linkedin_url", formData.linkedinUrl.trim());
      payload.append("cover_letter", formData.coverLetter.trim());
      payload.append("resume", formData.resume);

      const res = await fetch(API_ENDPOINTS.CAREER_POST || "/api/career-post-api.php", {
        method: "POST",
        body: payload
      });
      const json = await res.json();

      if (json && (json.success || json.status === "success")) {
        setAppliedSuccess(true);
        toast.success("Application submitted successfully!");
      } else {
        toast.error(json.error || json.message || "Failed to submit application.");
      }
    } catch (err) {
      console.error("Error submitting application:", err);
      toast.error("Network error. Please try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="cr-page-wrap">
      {/* 1. Header Hero Banner */}
      <section className="cr-hero-header">
        <Container className="cr-container">
          <nav aria-label="breadcrumb" className="cr-breadcrumbs mb-1">
            <Link to="/">Home</Link>
            <span className="mx-2 text-muted">/</span>
            <span className="text-secondary fw-semibold">Careers at Printmont</span>
          </nav>

          <div className="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <h1 className="cr-page-title">
                <FaBriefcase className="me-2 cr-title-icon" /> Careers at Printmont
              </h1>
              <p className="cr-page-sub">
                Build next-generation custom merchandising, corporate gifting &amp; printing technology for millions of businesses across India.
              </p>
            </div>

            <div className="d-flex flex-wrap gap-2 cr-stats-badges">
              <span className="cr-badge-stat">
                <strong>{jobs.length}+</strong> Openings
              </span>
              <span className="cr-badge-stat">
                <strong>7+</strong> Departments
              </span>
              <span className="cr-badge-stat">
                <strong>100%</strong> Meritocracy
              </span>
            </div>
          </div>
        </Container>
      </section>

      {/* 2. Sticky Department Filter Pills Bar */}
      <section className="cr-nav-bar-section">
        <Container className="cr-container">
          <div className="cr-filter-pills-wrap">
            {DEPARTMENTS.map((dept) => (
              <button
                key={dept.key}
                type="button"
                className={`cr-filter-pill ${activeDept === dept.key ? "active" : ""}`}
                onClick={() => setActiveDept(dept.key)}
              >
                {dept.label}
              </button>
            ))}
          </div>
        </Container>
      </section>

      {/* 3. Main Content Container */}
      <Container className="cr-container py-3 py-lg-4">
        {/* Search & Secondary Filter Box */}
        <div className="cr-search-card mb-3 mb-lg-4">
          <Row className="g-2 align-items-center">
            <Col md={7} xs={12}>
              <div className="cr-search-input-box">
                <FaSearch className="cr-search-icon" />
                <input
                  type="text"
                  placeholder="Search openings by role, keywords (e.g. Developer, Designer, Sales)..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="cr-search-input"
                />
              </div>
            </Col>
            <Col md={5} xs={12} className="d-flex gap-2 justify-content-md-end">
              <select
                className="cr-filter-select flex-grow-1 flex-md-grow-0"
                value={jobTypeFilter}
                onChange={(e) => setJobTypeFilter(e.target.value)}
              >
                <option value="all">All Job Types</option>
                <option value="full_time">Full Time</option>
                <option value="part_time">Part Time</option>
                <option value="remote">Remote</option>
                <option value="internship">Internship</option>
                <option value="contract">Contract</option>
              </select>
            </Col>
          </Row>
        </div>

        {/* 4. Open Positions Grid */}
        <div className="cr-positions-section mb-4 mb-lg-5">
          <div className="cr-positions-header d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
            <div>
              <h2 className="fw-bold text-dark mb-1" style={{ fontSize: "1.2rem" }}>
                Current Open Positions ({filteredJobs.length})
              </h2>
              <p className="text-muted small mb-0" style={{ fontSize: "0.84rem" }}>
                Explore open roles and apply directly in under 2 minutes.
              </p>
            </div>
          </div>

          {loading ? (
            <div className="text-center py-5">
              <Spinner animation="border" variant="primary" size="sm" />
              <p className="text-muted mt-2 small">Loading current career openings...</p>
            </div>
          ) : filteredJobs.length === 0 ? (
            <div className="cr-empty-state">
              <FaBriefcase size={36} className="text-muted mb-2 opacity-50" />
              <h5 className="fw-bold text-dark mb-1" style={{ fontSize: "1rem" }}>
                No active roles found matching your criteria
              </h5>
              <p className="text-muted small mb-3">
                Try searching for a different keyword or view all departments.
              </p>
              <button
                type="button"
                className="btn btn-primary btn-sm rounded-pill px-3"
                onClick={() => {
                  setSearchQuery("");
                  setActiveDept("all");
                  setJobTypeFilter("all");
                }}
              >
                Reset Filters
              </button>
            </div>
          ) : (
            <Row className="g-2 g-md-3">
              {filteredJobs.map((job) => {
                const slugOrId = job.slug || (job.job_title || job.title || "").toLowerCase().trim().replace(/[^a-z0-9]+/g, "-") || job.id;
                const formattedType = (job.job_type || "Full Time").replace(/_/g, " ");

                return (
                  <Col lg={4} md={6} xs={12} key={job.id}>
                    <div className="cr-job-card">
                      <div className="cr-job-header">
                        <span className="cr-dept-badge">
                          {job.department || "General"}
                        </span>
                        <span className="cr-hiring-badge">
                          <FaCheckCircle size={10} /> Active Hiring
                        </span>
                      </div>

                      <Link to={`/careers/${slugOrId}`} className="cr-job-title">
                        {job.job_title || job.title}
                      </Link>

                      <div className="cr-job-meta-row">
                        <span className="cr-job-meta-item">
                          <FaMapMarkerAlt /> {job.location || "Delhi / Remote"}
                        </span>
                        <span className="cr-job-meta-item text-capitalize">
                          <FaRegClock /> {formattedType}
                        </span>
                        {job.salary_range && (
                          <span className="cr-job-meta-item text-success fw-semibold">
                            <FaMoneyBillWave /> {job.salary_range}
                          </span>
                        )}
                      </div>

                      {job.description && (
                        <p className="cr-job-desc">{job.description}</p>
                      )}

                      <div className="cr-job-footer">
                        <Link to={`/careers/${slugOrId}`} className="cr-btn-details">
                          View Details <FaArrowRight size={9} />
                        </Link>
                        <button
                          type="button"
                          className="cr-btn-apply"
                          onClick={(e) => handleOpenApplyModal(job, e)}
                        >
                          <FaPaperPlane size={10} /> Quick Apply
                        </button>
                      </div>
                    </div>
                  </Col>
                );
              })}
            </Row>
          )}
        </div>

        {/* 5. Why Work at Printmont / Culture & Perks */}
        <div className="mb-4">
          <div className="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
            <div>
              <h3 className="fw-bold text-dark mb-0" style={{ fontSize: "1.15rem" }}>
                Why Join the Printmont Team?
              </h3>
              <p className="text-muted small mb-0" style={{ fontSize: "0.8rem" }}>
                We believe in empowering great talent with autonomy, rewards, and world-class perks.
              </p>
            </div>
          </div>

          <Row className="g-2 g-md-3">
            {PERKS.map((perk, idx) => (
              <Col lg={4} md={6} xs={12} key={idx}>
                <div className="cr-perk-card">
                  <div className="cr-perk-icon-box">{perk.icon}</div>
                  <div>
                    <h4 className="cr-perk-title">{perk.title}</h4>
                    <p className="cr-perk-desc">{perk.desc}</p>
                  </div>
                </div>
              </Col>
            ))}
          </Row>
        </div>
      </Container>

      {/* 6. Quick Apply Modal Form */}
      <Modal show={showModal} onHide={handleCloseModal} centered size="lg">
        <Modal.Header closeButton className="py-2 px-3">
          <Modal.Title className="cr-modal-title">
            {appliedSuccess ? "Application Submitted" : `Apply for: ${selectedJob?.job_title || selectedJob?.title || "Role"}`}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body className="p-3">
          {appliedSuccess ? (
            <div className="text-center py-4">
              <FaCheckCircle size={44} className="text-success mb-2" />
              <h5 className="fw-bold text-dark mb-1">Application Submitted Successfully!</h5>
              <p className="text-muted small mb-3">
                Thank you for applying for <strong>{selectedJob?.job_title || selectedJob?.title}</strong>. Our talent acquisition team will review your profile and reach out shortly.
              </p>
              <button
                type="button"
                className="btn btn-primary btn-sm rounded-pill px-4"
                onClick={handleCloseModal}
              >
                Close &amp; Explore Other Roles
              </button>
            </div>
          ) : (
            <Form onSubmit={handleApplySubmit}>
              <Row className="g-2 mb-2">
                <Col md={6} xs={12}>
                  <Form.Label className="cr-form-label">Full Name *</Form.Label>
                  <Form.Control
                    type="text"
                    required
                    placeholder="e.g. Rahul Sharma"
                    className="cr-form-input"
                    value={formData.fullName}
                    onChange={(e) => setFormData({ ...formData, fullName: e.target.value })}
                  />
                </Col>
                <Col md={6} xs={12}>
                  <Form.Label className="cr-form-label">Email Address *</Form.Label>
                  <Form.Control
                    type="email"
                    required
                    placeholder="rahul@example.com"
                    className="cr-form-input"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  />
                </Col>
              </Row>

              <Row className="g-2 mb-2">
                <Col md={6} xs={12}>
                  <Form.Label className="cr-form-label">Phone Number *</Form.Label>
                  <Form.Control
                    type="tel"
                    required
                    placeholder="+91 98765 43210"
                    className="cr-form-input"
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  />
                </Col>
                <Col md={6} xs={12}>
                  <Form.Label className="cr-form-label">Total Experience (Years)</Form.Label>
                  <Form.Control
                    type="text"
                    placeholder="e.g. 3.5 Years"
                    className="cr-form-input"
                    value={formData.experience}
                    onChange={(e) => setFormData({ ...formData, experience: e.target.value })}
                  />
                </Col>
              </Row>

              <Row className="g-2 mb-2">
                <Col md={6} xs={12}>
                  <Form.Label className="cr-form-label">Current City / Location</Form.Label>
                  <Form.Control
                    type="text"
                    placeholder="e.g. New Delhi, Noida, Bengaluru"
                    className="cr-form-input"
                    value={formData.location}
                    onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                  />
                </Col>
                <Col md={6} xs={12}>
                  <Form.Label className="cr-form-label">LinkedIn / Portfolio URL</Form.Label>
                  <Form.Control
                    type="url"
                    placeholder="https://linkedin.com/in/..."
                    className="cr-form-input"
                    value={formData.linkedinUrl}
                    onChange={(e) => setFormData({ ...formData, linkedinUrl: e.target.value })}
                  />
                </Col>
              </Row>

              <Form.Group className="mb-2">
                <Form.Label className="cr-form-label">Resume / CV (PDF or DOCX) *</Form.Label>
                <Form.Control
                  type="file"
                  required
                  accept=".pdf,.doc,.docx"
                  className="cr-form-input"
                  onChange={(e) => setFormData({ ...formData, resume: e.target.files[0] })}
                />
                <small className="text-muted" style={{ fontSize: "0.72rem" }}>
                  Max file size: 5MB (PDF, DOC, DOCX formats supported)
                </small>
              </Form.Group>

              <Form.Group className="mb-3">
                <Form.Label className="cr-form-label">Cover Note / Why Printmont?</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={2}
                  placeholder="Briefly tell us why you are a great fit for this role..."
                  className="cr-form-input"
                  value={formData.coverLetter}
                  onChange={(e) => setFormData({ ...formData, coverLetter: e.target.value })}
                />
              </Form.Group>

              <div className="d-flex justify-content-end gap-2">
                <button
                  type="button"
                  className="btn btn-light btn-sm px-3"
                  onClick={handleCloseModal}
                  disabled={isSubmitting}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="btn btn-primary btn-sm px-4 fw-semibold d-inline-flex align-items-center gap-1"
                  disabled={isSubmitting}
                >
                  {isSubmitting ? (
                    <>
                      <Spinner size="sm" animation="border" className="me-1" /> Submitting...
                    </>
                  ) : (
                    <>
                      <FaPaperPlane size={11} /> Submit Application
                    </>
                  )}
                </button>
              </div>
            </Form>
          )}
        </Modal.Body>
      </Modal>
    </div>
  );
}
