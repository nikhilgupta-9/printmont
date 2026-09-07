import React, { useState } from "react";
import {
  Container,
  Row,
  Col,
  Card,
  Button,
  ListGroup,
  Form,
  Image,
} from "react-bootstrap";
import {
  FaQuestionCircle,
  FaBoxOpen,
  FaShippingFast,
  FaUndoAlt,
  FaHeadset,
  FaSearch,
} from "react-icons/fa";
import "./support.css";

const SupportPage = () => {
  const [activeTopic, setActiveTopic] = useState("orders");
  const [search, setSearch] = useState("");

  const topics = [
    { key: "orders", label: "Order Related", icon: <FaBoxOpen /> },
    { key: "delivery", label: "Delivery Issues", icon: <FaShippingFast /> },
    { key: "returns", label: "Returns & Refunds", icon: <FaUndoAlt /> },
    { key: "account", label: "Account & Payment", icon: <FaHeadset /> },
    { key: "general", label: "General Queries", icon: <FaQuestionCircle /> },
  ];

  const helpData = {
    orders: {
      title: "Order Related Issues",
      products: [
        {
          id: 1,
          name: "Noise Pulse Smartwatch",
          status: "Delivered on 10 Nov 2025",
          img: "https://via.placeholder.com/80x80?text=Watch",
        },
        {
          id: 2,
          name: "boAt Rockerz 255",
          status: "Cancelled on 8 Nov 2025",
          img: "https://via.placeholder.com/80x80?text=Earphone",
        },
      ],
      faqs: [
        {
          q: "How can I cancel my order?",
          a: "Go to My Orders → Select order → Click Cancel.",
        },
        {
          q: "Why is my order delayed?",
          a: "Some deliveries may be delayed due to high demand.",
        },
      ],
    },
    delivery: {
      title: "Delivery Issues",
      products: [
        {
          id: 3,
          name: "Samsung Galaxy M15",
          status: "Out for delivery",
          img: "https://via.placeholder.com/80x80?text=Phone",
        },
      ],
      faqs: [
        {
          q: "Can I change my delivery address?",
          a: "Address can be changed only before shipment.",
        },
        {
          q: "What if my package is damaged?",
          a: "Raise a complaint under 'Damaged Item' section.",
        },
      ],
    },
    returns: {
      title: "Returns & Refunds",
      products: [
        {
          id: 4,
          name: "Puma Shoes",
          status: "Returned on 5 Nov 2025",
          img: "https://via.placeholder.com/80x80?text=Shoes",
        },
      ],
      faqs: [
        {
          q: "When will I get my refund?",
          a: "Refunds are processed within 5–7 business days.",
        },
        {
          q: "Can I exchange my item?",
          a: "Exchange is available for select sellers only.",
        },
      ],
    },
    account: {
      title: "Account & Payment",
      products: [],
      faqs: [
        {
          q: "How to reset my password?",
          a: "Click on Forgot Password on login page.",
        },
        {
          q: "Why is my payment failed?",
          a: "Ensure valid payment details or try another method.",
        },
      ],
    },
    general: {
      title: "General Queries",
      products: [],
      faqs: [
        {
          q: "How to contact support?",
          a: "Use the chat or call option available on this page.",
        },
        {
          q: "Do you offer 24/7 support?",
          a: "Yes, our chat support is available round-the-clock.",
        },
      ],
    },
  };

  const selected = helpData[activeTopic];
  const filteredTopics = topics.filter((t) =>
    t.label.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <Container fluid className="support-page py-3">
      <Row className="justify-content-center">
        <Col xs={12} lg={10}>
          {/* Scrollable horizontal tabs for small screens */}
          <div className="mobile-scroll-tabs d-md-none mb-3 p-0">
            {topics.map((topic) => (
              <div
                key={topic.key}
                className={`scroll-tab-item ${
                  activeTopic === topic.key ? "active" : ""
                }`}
                onClick={() => setActiveTopic(topic.key)}
              >
                <span className="fs-6 me-1">{topic.icon}</span>
                {topic.label}
              </div>
            ))}
          </div>

          <Row>
            {/* Sidebar for medium & large screens */}
            <Col md={4} lg={3} className="d-none d-md-block mb-3 mb-md-0">
              <Card className="shadow-sm sidebar-card">
                <Card.Body>
                  <h6 className="fw-bold mb-3 text-uppercase text-secondary">
                    Help Topics
                  </h6>
                  <div className="search-box position-relative mb-3">
                    <FaSearch className="search-icon" />
                    <Form.Control
                      type="text"
                      placeholder="Search topic..."
                      value={search}
                      onChange={(e) => setSearch(e.target.value)}
                    />
                  </div>
                  <ListGroup variant="flush">
                    {filteredTopics.map((topic) => (
                      <ListGroup.Item
                        key={topic.key}
                        action
                        onClick={() => setActiveTopic(topic.key)}
                        className={`d-flex align-items-center gap-2 sidebar-item ${
                          activeTopic === topic.key ? "active" : ""
                        }`}
                      >
                        <span className="fs-5">{topic.icon}</span>
                        <span>{topic.label}</span>
                      </ListGroup.Item>
                    ))}
                  </ListGroup>
                </Card.Body>
              </Card>
            </Col>

            {/* Main content */}
            <Col xs={12} md={8} lg={9}>
              <Card className="shadow-sm mb-4 content-card">
                <Card.Body>
                  <h5 className="fw-semibold mb-3 text-primary-color">
                    {selected.title}
                  </h5>

                  {selected.products.length > 0 ? (
                    <>
                      <h6 className="text-muted mb-2">Your Recent Orders</h6>
                      {selected.products.map((p) => (
                        <div
                          key={p.id}
                          className="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border rounded product-card p-3 mb-2"
                        >
                          <div className="d-flex align-items-center gap-3 mb-2 mb-sm-0">
                            <Image
                              src={p.img}
                              alt={p.name}
                              className="product-img rounded"
                              fluid
                            />
                            <div>
                              <p className="mb-0 fw-semibold">{p.name}</p>
                              <small className="text-muted">{p.status}</small>
                            </div>
                          </div>
                          <Button variant="theme" size="sm">
                            Raise Complaint
                          </Button>
                        </div>
                      ))}
                    </>
                  ) : (
                    <p className="text-muted">No recent orders for this topic.</p>
                  )}
                </Card.Body>
              </Card>

              {/* FAQ Section */}
              <Card className="shadow-sm faq-card">
                <Card.Body>
                  <h6 className="fw-bold mb-3 text-primary-color">
                    Common Questions
                  </h6>
                  {selected.faqs.map((faq, idx) => (
                    <div key={idx} className="faq-item mb-3">
                      <p className="fw-semibold mb-1">{faq.q}</p>
                      <p className="text-muted small mb-0">{faq.a}</p>
                      {idx < selected.faqs.length - 1 && <hr />}
                    </div>
                  ))}
                </Card.Body>
              </Card>
            </Col>
          </Row>
        </Col>
      </Row>
    </Container>
  );
};

export default SupportPage;
