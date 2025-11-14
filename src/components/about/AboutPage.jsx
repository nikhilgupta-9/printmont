import React from "react";
import { Container, Row, Col } from "react-bootstrap";
import { FaCheckCircle } from "react-icons/fa";
import "./About.css";

const AboutPage = () => {
  return (
    <div className="about-page py-2 py-lg-4 px-0 mx-0">
      <Container fluid className="mx-0 px-0">
        {/* Title & Intro */}
        <Row className="align-items-center mb-4 mb-lg-5 mx-0 px-0">
          <Col md={7} className="px-3">
            <h2 className="fw-bold display-6 mb-3">
              Crafting Excellence Together
            </h2>
            <p className="text-muted">
              We believe in the power of collaboration to achieve
              outstanding results. With a team of skilled professionals and a
              commitment to quality, we work hand-in-hand with our clients to
              bring their ideas to life. Together, we create spaces and
              solutions that stand the test of time.
            </p>
          </Col>
          <Col md={5} className="d-flex justify-content-end">
            <div className="d-inline-block ">
             <img src="/Online-Shopping-1.png" className="mainimg rounded" alt=""  />
            </div>
          </Col>
        </Row>

        {/* Commitment Section */}
        <Row className="text-start mb-5 px-0 px-lg-3 mx-0">
          <Col md={12}>
            <p className="lead">
              At Renovex we are committed to revolutionizing the construction
              industry with innovative, sustainable, and cost-effective
              solutions. With a proven track record of delivering exceptional
              projects, we combine{" "}
              <span className="fw-semibold text-dark">
                state-of-the-art technology, skilled expertise,
              </span>{" "}
              and <span className="fw-semibold text-dark">customer-centric</span>{" "}
              approaches to bring visions to life.
            </p>
          </Col>
        </Row>
      </Container>
      <Container>
        

        {/* Stats Section */}
        <Row className="text-center mb-5 mx-0 px-0">
          <Col xs={6} md={3}>
            <h4 className="fw-bold">150+</h4>
            <p className="text-muted small">Complete Projects</p>
          </Col>
          <Col xs={6} md={3}>
            <h4 className="fw-bold">100+</h4>
            <p className="text-muted small">Team Members</p>
          </Col>
          <Col xs={6} md={3}>
            <h4 className="fw-bold">200+</h4>
            <p className="text-muted small">Client Reviews</p>
          </Col>
          <Col xs={6} md={3}>
            <h4 className="fw-bold">30</h4>
            <p className="text-muted small">Winning Awards</p>
          </Col>
        </Row>

        {/* Mission Section */}
        <Row className="align-items-center mb-5 mx-0 px-0">
          <Col md={6} className="d-flex justify-content-center">
            <img
              src="/our-mission.jpg"
              alt="Our Mission"
              className="img-fluid rounded shadow-sm our"
              
            />
          </Col>
          <Col md={6}>
            <h3 className="fw-bold mb-3">Our Mission</h3>
            <p className="text-muted small">
              To provide exceptional construction services that exceed client
              expectations through innovation, quality craftsmanship, and a
              commitment to sustainability. We aim to build lasting
              relationships and create spaces that enhance communities.
            </p>

            <ul className="list-unstyled small">
              <li className="mb-2">
                <FaCheckCircle className="text-success me-2" />
                Fostering Sustainable Growth and Green Development
              </li>
              <li className="mb-2">
                <FaCheckCircle className="text-success me-2" />
                Innovating for a Sustainable Future
              </li>
              <li className="mb-2">
                <FaCheckCircle className="text-success me-2" />
                Customer-Centric Approach
              </li>
              <li className="mb-2">
                <FaCheckCircle className="text-success me-2" />
                Building Stronger Communities
              </li>
            </ul>
          </Col>
        </Row>

        {/* Vision Section */}
        <Row className="align-items-center flex-column-reverse flex-md-row px-0 px-lg-3 mx-0">
          <Col md={7}>
            <h3 className="fw-bold mb-3">Our Vision</h3>
            <p className="text-muted small text-start">
              At Renovex, our vision is to redefine the future of construction
              through innovation, sustainability, and excellence. We aim to
              construct spaces that not only stand tall but inspire the
              community and respect the environment.
            </p>
            <ul className="list-unstyled small">
              <li className="mb-2">
                <FaCheckCircle className="text-success me-2" />
                Inspiring Modern Architecture
              </li>
              <li className="mb-2">
                <FaCheckCircle className="text-success me-2" />
                Pioneering Sustainable Construction
              </li>
            </ul>
          </Col>
          <Col md={5} className="d-flex justify-content-center">
            <img
              src="/our-vission.jpg"
              alt="Our Vision"
              className="img-fluid rounded-4 shadow-sm our"
            />
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default AboutPage;
