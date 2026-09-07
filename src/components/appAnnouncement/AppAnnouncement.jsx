import React from "react";
import { Container, Row, Col } from "react-bootstrap";
import { FaMobileAlt } from "react-icons/fa";
import "./AppAnnouncement.css";

const AppAnnouncement = () => {
  return (
    <section className="announcement-section">
    {/* <section> */}
      <Container fluid className="my-5">
        <Row className="align-items-center justify-content-center text-center text-md-start">
          <Col xs={12} md={3} className="mb-3 mb-md-0">
            <div className="mx-auto mx-md-0 d-flex justify-content-end">
            {/* <div className="icon-wrapper mx-auto mx-md-0"> */}
              <img src="/coming-soon.png" width={300} alt="" />
            </div>
          </Col>

          <Col xs={12} md={9}>
            <h3 className="fw-bold mb-2 text-dark">
              Exciting News! 🚀
            </h3>
            <p className="mb-0 text-muted">
              Our <strong>Prinmont mobile app</strong> is on the way. We’re
              working hard to make your experience smoother and faster — stay
              connected for updates!
            </p>
          </Col>
        </Row>
      </Container>
    </section>
  );
};

export default AppAnnouncement;
