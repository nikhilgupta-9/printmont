import React, { useState } from "react";
import { Container, Row, Col, Form, Collapse, Nav } from "react-bootstrap";
import { FaBell } from "react-icons/fa";
import { IoIosArrowDown, IoIosArrowUp } from "react-icons/io";
import "./notify.css";

const NotificationPreferences = () => {
  const [activeTab, setActiveTab] = useState("sms");
  const [open, setOpen] = useState({
    reminders: false,
    recommendations: false,
    offers: false,
    community: false,
    feedback: false,
  });

  const toggleOpen = (key) => setOpen({ ...open, [key]: !open[key] });

  const renderChannelContent = (channel) => (
    <div className="main-content bg-white shadow-sm rounded p-4">
      <h5 className="fw-semibold mb-1 text-uppercase">{channel}</h5>
      <p className="text-muted small mb-4">
        Manage notifications you receive through {channel}.
      </p>

      <Section title="My Orders" subtitle="Latest updates on your orders" />

      <Section
        title="Reminders"
        subtitle="Price drops, Back-in-stock Products, etc."
        open={open.reminders}
        onToggle={() => toggleOpen("reminders")}
        subOptions={[
          "Reminders for items in Cart",
          "Reminders for Payments, Credit and Insurance",
          "Notify Me for subscriptions and answered questions",
          "Reminders to Restock",
          "Reminders for discounts just for you",
          "Reminders for Games",
        ]}
      />

      <Section
        title="Recommendations"
        subtitle="Products, offers and curated content based on your interest"
        open={open.recommendations}
        onToggle={() => toggleOpen("recommendations")}
        subOptions={[
          "Offers based on your interests",
          "Offers to complement your purchases",
          "Offers on products similar to past purchases",
          "Videos based on your interests",
        ]}
      />

      <Section
        title="New Offers"
        subtitle="Top deals and more"
        open={open.offers}
        onToggle={() => toggleOpen("offers")}
        subOptions={[
          "Welcome Offers",
          "Deals, Discounts and Sale",
          "Offer Zone",
          "Super Partner Services",
        ]}
      />

      <Section
        title="Community"
        subtitle="Profile updates, Newsletters, etc."
        open={open.community}
        onToggle={() => toggleOpen("community")}
        subOptions={[
          "Profile Updates",
          "Communication to help understand you better",
          "Newsletter",
          "Flipkart Ideas",
        ]}
      />

      <Section
        title="Feedback & Review"
        subtitle="Ratings and Reviews for your purchase"
        open={open.feedback}
        onToggle={() => toggleOpen("feedback")}
        subOptions={[
          "Feedback on products",
          "Answer questions by your fellow buyers",
        ]}
      />
    </div>
  );

  return (
    <Container fluid className="notification-page py-2">
      <Row className="justify-content-center">
        <Col lg={10}>
          <Row>
            {/* ---- LEFT SIDEBAR ---- */}
            <Col lg={3} className="mb-3 mb-lg-0">
              <div className="sidebar p-4 bg-white shadow-sm rounded">
                <div className="d-flex align-items-center mb-3">
                  <FaBell className="text-primary fs-4 me-1" />
                  <small className="mb-0 small">NOTIFICATION PREFERENCES</small>
                </div>
                <Nav
                  className="flex-column"
                  variant="pills"
                  activeKey={activeTab}
                  onSelect={(selectedKey) => setActiveTab(selectedKey)}
                >
                  {[
                    { key: "desktop", label: "Desktop Notifications" },
                    { key: "app", label: "In-App Notifications" },
                    { key: "sms", label: "SMS" },
                    { key: "email", label: "Email" },
                    { key: "whatsapp", label: "WhatsApp" },
                  ].map((tab) => (
                    <Nav.Item key={tab.key}>
                      <Nav.Link
                        eventKey={tab.key}
                        className="py-2 small sidebar-item text-muted"
                      >
                        {tab.label}
                      </Nav.Link>
                    </Nav.Item>
                  ))}
                </Nav>
              </div>
            </Col>

            {/* ---- MAIN CONTENT ---- */}
            <Col lg={9}>
              {activeTab === "desktop" && renderChannelContent("Desktop")}
              {activeTab === "app" && renderChannelContent("App")}
              {activeTab === "sms" && renderChannelContent("SMS")}
              {activeTab === "email" && renderChannelContent("Email")}
              {activeTab === "whatsapp" && renderChannelContent("WhatsApp")}
            </Col>
          </Row>
        </Col>
      </Row>
    </Container>
  );
};

// ✅ Reusable Section Component
const Section = ({ title, subtitle, subOptions = [], open, onToggle }) => {
  const [mainChecked, setMainChecked] = useState(false);
  const [subChecked, setSubChecked] = useState(subOptions.map(() => false));

  const handleMainToggle = () => {
    const newChecked = !mainChecked;
    setMainChecked(newChecked);
    setSubChecked(subOptions.map(() => newChecked));
  };

  const handleSubToggle = (index) => {
    const updated = [...subChecked];
    updated[index] = !updated[index];
    setSubChecked(updated);
    const anyChecked = updated.some((val) => val);
    setMainChecked(anyChecked);
  };

  return (
    <div className="notification-section mb-3">
      <div className="d-flex justify-content-between align-items-start">
        <div>
          <Form.Check
            type="checkbox"
            checked={mainChecked}
            onChange={handleMainToggle}
            label={
              <div className="section-label">
                <span className="fw-semibold d-block">{title}</span>
                <span className="text-muted small d-block">{subtitle}</span>
              </div>
            }
          />
        </div>

        {subOptions.length > 0 && (
          <span
            className="toggle-icon mt-1 cursor-pointer"
            onClick={onToggle}
            style={{ cursor: "pointer" }}
          >
            {open ? <IoIosArrowUp /> : <IoIosArrowDown />}
          </span>
        )}
      </div>

      {subOptions.length > 0 && (
        <Collapse in={open}>
          <div className="ms-4 mt-2">
            {subOptions.map((opt, idx) => (
              <Form.Check
                key={idx}
                type="checkbox"
                checked={subChecked[idx]}
                onChange={() => handleSubToggle(idx)}
                label={<span className="small text-muted">{opt}</span>}
                className="mb-1"
              />
            ))}
          </div>
        </Collapse>
      )}
      <hr />
    </div>
  );
};

export default NotificationPreferences;
