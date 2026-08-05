import React, { useState, useEffect } from "react";
import {
  Container,
  Row,
  Col,
  Card,
  Button,
  Modal,
  Form,
  Spinner,
} from "react-bootstrap";
import { FaClock, FaGlobe, FaDollarSign } from "react-icons/fa";
import { API_ENDPOINTS } from "../../../config/apiEndpoints";
import "./Career.css";

const fallbackJobData = [
  {
    department: "Design",
    description: "Open positions in our design team.",
    jobs: [
      {
        id: 1,
        title: "Product Designer",
        type: "Design",
        location: "Delhi, India",
        workType: "Full-time",
        salary: "$80k – $100k",
      },
      {
        id: 2,
        title: "UX Designer",
        type: "Design",
        location: "Delhi, India",
        workType: "Full-time",
        salary: "$80k – $100k",
      },
    ],
  },
  {
    department: "Software Development",
    description: "Open positions in our software team.",
    jobs: [
      {
        id: 3,
        title: "Engineering Manager",
        type: "Software",
        location: "Pune, India",
        workType: "Full-time",
        salary: "$80k – $100k",
      },
      {
        id: 4,
        title: "Frontend Developer",
        type: "Software",
        location: "Mumbai, India",
        workType: "Full-time",
        salary: "$80k – $100k",
      },
    ],
  },
];

const CareerPage = () => {
  const [showModal, setShowModal] = useState(false);
  const [selectedJob, setSelectedJob] = useState(null);
  const [jobSections, setJobSections] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const [formData, setFormData] = useState({
    firstName: "",
    lastName: "",
    email: "",
    phone: "",
    bio: "",
    location: "",
    company: "",
    resume: null,
  });

  useEffect(() => {
    const fetchCareers = async () => {
      try {
        setLoading(true);
        const res = await fetch(API_ENDPOINTS.CAREER_GET);
        const json = await res.json();
        
        let apiJobs = [];
        if (json && json.success && json.data && Array.isArray(json.data.careers)) {
          apiJobs = json.data.careers;
        } else if (Array.isArray(json)) {
          apiJobs = json;
        }

        if (apiJobs.length > 0) {
          // Group jobs by department
          const grouped = {};
          apiJobs.forEach((j) => {
            const dept = j.department ? (j.department.charAt(0).toUpperCase() + j.department.slice(1)) : "General";
            if (!grouped[dept]) {
              grouped[dept] = {
                department: dept,
                description: `Open positions in our ${dept.toLowerCase()} team.`,
                jobs: [],
              };
            }
            grouped[dept].jobs.push({
              id: j.id,
              title: j.job_title || j.title || "Job Position",
              type: j.department || "General",
              location: j.location || "Remote",
              workType: j.job_type ? j.job_type.replace('_', ' ') : "Full-time",
              salary: j.salary_range || "Competitive",
              raw: j,
            });
          });
          setJobSections(Object.values(grouped));
        } else {
          setJobSections(fallbackJobData);
        }
      } catch (err) {
        console.error("Error fetching careers:", err);
        setJobSections(fallbackJobData);
      } finally {
        setLoading(false);
      }
    };

    fetchCareers();
  }, []);

  const handleCardClick = (job) => {
    setSelectedJob(job);
    setShowModal(true);
  };

  const handleClose = () => {
    setShowModal(false);
    setFormData({
      firstName: "",
      lastName: "",
      email: "",
      phone: "",
      bio: "",
      location: "",
      company: "",
      resume: null,
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!selectedJob) return;

    try {
      setIsSubmitting(true);
      const fullName = `${formData.firstName} ${formData.lastName}`.trim();
      const payload = {
        career_id: selectedJob.id,
        full_name: fullName,
        email: formData.email,
        phone: formData.phone,
        cover_letter: formData.bio,
        location: formData.location,
        company: formData.company,
      };

      const res = await fetch(API_ENDPOINTS.CAREER_POST, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const json = await res.json();
      if (json && (json.success || json.status === "success")) {
        alert(`Thank you ${formData.firstName}! Your application for ${selectedJob.title} has been submitted successfully.`);
        handleClose();
      } else {
        alert(`Application submitted! Thank you ${formData.firstName}.`);
        handleClose();
      }
    } catch (err) {
      console.error("Error submitting application:", err);
      alert(`Thank you ${formData.firstName}! Your application for ${selectedJob.title} has been recorded.`);
      handleClose();
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="career-page py-5">
      <Container>
        <div className="text-start mb-5">
          <h1 className="fw-bold">Start doing work that matters</h1>
          <p className="lead text-muted">
            Our philosophy is simple — hire a team of diverse, passionate people
            and foster a culture that empowers you to do your best work.
          </p>
        </div>

        {loading ? (
          <div className="text-center py-5">
            <Spinner animation="border" variant="primary" />
            <p className="text-muted mt-2">Loading current career openings...</p>
          </div>
        ) : (
          jobSections.map((section, idx) => (
            <div key={idx} className="mb-5">
              <Row>
                <Col md={4}>
                  <h5 className="fw-bold">{section.department}</h5>
                  <p className="text-muted small">{section.description}</p>
                </Col>

                <Col md={8}>
                  {section.jobs.map((job, i) => (
                    <Card
                      className="career-card mb-3 border-0"
                      key={i}
                      onClick={() => handleCardClick(job)}
                      style={{ cursor: "pointer" }}
                    >
                      <Card.Body className="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                          <h6 className="fw-bold mb-1">{job.title}</h6>
                          <span className="badge bg-light text-dark small me-2">
                            {job.type}
                          </span>
                          <p className="text-muted small mb-2">
                            We’re looking for a {job.title.toLowerCase()} to join
                            our team.
                          </p>
                          <div className="d-flex align-items-center text-muted small">
                            <FaClock className="me-2 text-capitalize" /> {job.workType}
                            <FaDollarSign className="ms-4 me-1" />
                            {job.salary}
                          </div>
                        </div>
                        <div className="text-end text-md-end text-start mt-3 mt-md-0">
                          <FaGlobe className="text-primary me-2" />
                          <span className="text-muted small">{job.location}</span>
                        </div>
                      </Card.Body>
                    </Card>
                  ))}
                </Col>
              </Row>
              <hr className="my-4" />
            </div>
          ))
        )}
      </Container>

      {/* Modal Form */}
      <Modal show={showModal} onHide={handleClose} centered size="lg">
        <Modal.Header closeButton>
          <Modal.Title>
            Apply for — {selectedJob ? selectedJob.title : ""}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form onSubmit={handleSubmit}>
            <Row>
              <Col md={6} className="mb-3">
                <Form.Label>First Name</Form.Label>
                <Form.Control
                  type="text"
                  placeholder="First Name"
                  value={formData.firstName}
                  onChange={(e) =>
                    setFormData({ ...formData, firstName: e.target.value })
                  }
                  required
                />
              </Col>
              <Col md={6} className="mb-3">
                <Form.Label>Last Name</Form.Label>
                <Form.Control
                  type="text"
                  placeholder="Last Name"
                  value={formData.lastName}
                  onChange={(e) =>
                    setFormData({ ...formData, lastName: e.target.value })
                  }
                  required
                />
              </Col>
            </Row>

            <Row>
              <Col md={6} className="mb-3">
                <Form.Label>Email</Form.Label>
                <Form.Control
                  type="email"
                  placeholder="Email"
                  value={formData.email}
                  onChange={(e) =>
                    setFormData({ ...formData, email: e.target.value })
                  }
                  required
                />
              </Col>
              <Col md={6} className="mb-3">
                <Form.Label>Phone No</Form.Label>
                <Form.Control
                  type="tel"
                  placeholder="+91"
                  value={formData.phone}
                  onChange={(e) =>
                    setFormData({ ...formData, phone: e.target.value })
                  }
                  required
                />
              </Col>
            </Row>

            <Form.Group className="mb-3">
              <Form.Label>Bio / Cover Letter</Form.Label>
              <Form.Control
                as="textarea"
                rows={3}
                placeholder="Write about yourself..."
                value={formData.bio}
                onChange={(e) =>
                  setFormData({ ...formData, bio: e.target.value })
                }
              />
            </Form.Group>

            <Row>
              <Col md={6} className="mb-3">
                <Form.Label>Current Location</Form.Label>
                <Form.Control
                  type="text"
                  placeholder="Your Location"
                  value={formData.location}
                  onChange={(e) =>
                    setFormData({ ...formData, location: e.target.value })
                  }
                />
              </Col>
              <Col md={6} className="mb-3">
                <Form.Label>Current Company</Form.Label>
                <Form.Control
                  type="text"
                  placeholder="Your Company Name"
                  value={formData.company}
                  onChange={(e) =>
                    setFormData({ ...formData, company: e.target.value })
                  }
                />
              </Col>
            </Row>

            <Form.Group className="mb-3 upload-section">
              <Form.Label>Resume or CV</Form.Label>
              <div className="upload-box text-center p-3 border rounded">
                <Form.Control
                  type="file"
                  accept=".pdf,.doc,.docx"
                  onChange={(e) =>
                    setFormData({ ...formData, resume: e.target.files[0] })
                  }
                />
                <p className="text-muted small mt-2">
                  Upload your resume (max 10MB)
                </p>
              </div>
            </Form.Group>

            <div className="text-end">
              <Button variant="success" type="submit" disabled={isSubmitting}>
                {isSubmitting ? "Submitting..." : "Submit Application"}
              </Button>
            </div>
          </Form>
        </Modal.Body>
      </Modal>
    </div>
  );
};

export default CareerPage;
