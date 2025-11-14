import React, { useState } from "react";
import { Container, Row, Col, Form, Button } from "react-bootstrap";
import { BsCheckCircleFill } from "react-icons/bs";
import "./TrackOrder.css";

const TrackOrder = () => {
  const [searchBy, setSearchBy] = useState("tracking");
  const [formData, setFormData] = useState({
    trackingId: "",
    email: "",
    orderId: "",
    mobile: "",
  });
  const [orderDetails, setOrderDetails] = useState(null);

  // Handle input changes
  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  // Dummy data for demo
  const dummyOrder = {
    productName:
      "METRONAUT BENT - Multicolor Cabin & Check-in Set 4 Wheels - 30 inch",
    color: "Multicolor",
    seller: "HSAtlastradeFashion",
    price: "₹3,912",
    status: [
      { text: "Order Confirmed, Jan 05", done: true },
      { text: "Shipped, Jan 08", done: true },
      { text: "Out for Delivery, Jan 11", done: true },
      { text: "Delivered, Jan 12", done: true },
    ],
    image:
      "https://rukminim2.flixcart.com/image/416/416/xif0q/luggage/5/x/b/30-20-24-28-4-wheels-polycarbonate-bent-trolley-bag-set-of-3-original-imagufg4hycjhuey.jpeg?q=70",
  };

  // Handle form submission
  const handleSubmit = (e) => {
    e.preventDefault();
    if (formData.trackingId === "TRK123" || formData.orderId === "ORD123") {
      setOrderDetails(dummyOrder);
    } else {
      setOrderDetails({
        error:
          "❌ No order found. Try with Tracking ID: TRK123 or Order ID: ORD123",
      });
    }
  };

  return (
    <div>
    <div className="track-order-section">
      <Container fluid className="py-5 px-4">
        {/* Title Button */}
        <div className="mb-3">
          <Button className="track-title-btn" disabled>
            Track your Order or Shipment
          </Button>
        </div>

        {/* Instruction */}
        <p className="instruction-text">
          Enter your Tracking ID or Order ID to track the status of your order.
          You can find them in your Email/SMS confirmation.
        </p>

        {/* Search Section */}
        <div className="search-section mt-4">
          <Row className="justify-content-start">
            <Col lg={8} md={10}>
              <div className="search-by d-flex align-items-md-center mb-4">
                <strong className="me-3">Search By :</strong>
                <div className="d-flex align-items-center gap-3">
                  <Form.Check
                    type="radio"
                    id="tracking"
                    label="Tracking ID"
                    name="searchBy"
                    checked={searchBy === "tracking"}
                    onChange={() => setSearchBy("tracking")}
                  />
                  <Form.Check
                    type="radio"
                    id="orderid"
                    label="Order ID"
                    name="searchBy"
                    checked={searchBy === "orderid"}
                    onChange={() => setSearchBy("orderid")}
                  />
                </div>
              </div>

              {/* Tracking Form */}
              {searchBy === "tracking" && (
                <Form onSubmit={handleSubmit}>
                  <Row className="g-3 align-items-end justify-content-center">
                    <Col md={5} xs={12}>
                      <Form.Label>Tracking ID:</Form.Label>
                      <Form.Control
                        type="text"
                        name="trackingId"
                        value={formData.trackingId}
                        onChange={handleChange}
                        placeholder="Enter Tracking #ID"
                        className="form-input text-uppercase"
                      />
                    </Col>
                    <Col md={5} xs={12}>
                      <Form.Label>Email ID:</Form.Label>
                      <Form.Control
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleChange}
                        placeholder="Enter Email ID"
                        className="form-input"
                      />
                    </Col>
                    <Col md={2} xs={12} className="text-center d-none d-md-block">
                      <Button type="submit" className="track-btn w-100">
                        TRACK ORDER
                      </Button>
                    </Col>
                  </Row>
                </Form>
              )}

              {/* Order ID Form */}
              {searchBy === "orderid" && (
                <Form onSubmit={handleSubmit}>
                  <Row className="g-3 align-items-end justify-content-center">
                    <Col md={5} xs={12}>
                      <Form.Label>Order ID:</Form.Label>
                      <Form.Control
                        type="text"
                        name="orderId"
                        value={formData.orderId}
                        onChange={handleChange}
                        placeholder="Enter Order ID"
                        className="form-input"
                      />
                    </Col>
                    <Col md={5} xs={12}>
                      <Form.Label>Mobile No:</Form.Label>
                      <Form.Control
                        type="text"
                        name="mobile"
                        value={formData.mobile}
                        onChange={handleChange}
                        placeholder="Enter Mobile No"
                        className="form-input"
                      />
                    </Col>
                    <Col md={2} xs={12} className="text-center d-none d-md-block">
                      <Button type="submit" className="track-btn w-100">
                        TRACK ORDER
                      </Button>
                    </Col>
                  </Row>
                </Form>
              )}
            </Col>
          </Row>
        </div>

        {/* Tracking Result Section */}
        {orderDetails && (
          <Row className="justify-content-start mt-5">
            <Col lg={8} md={10}>
              {orderDetails.error ? (
                <p className="text-danger fw-bold">{orderDetails.error}</p>
              ) : (
                <div className="order-card p-3 p-md-4">
                  <Row className="align-items-center flex-column-reverse flex-md-row">
                    <Col xs={12} md={9}>
                      <h5 className="fw-bold">{orderDetails.productName}</h5>
                      <p className="mb-1">{orderDetails.color}</p>
                      <p className="text-muted mb-1">
                        Seller: {orderDetails.seller}
                      </p>
                      <h6 className="fw-bold">{orderDetails.price}</h6>
                    </Col>
                    <Col
                      xs={12}
                      md={3}
                      className="text-center text-md-end mb-3 mb-md-0"
                    >
                      <div className="product-image-wrapper">
                        <img
                          src={orderDetails.image}
                          alt="product"
                          className="product-image"
                        />
                      </div>
                    </Col>


                  </Row>


                  <div className="tracking-status mt-4">
                    {orderDetails.status.map((step, index) => (
                      <div key={index} className="d-flex align-items-start mb-2">
                        <BsCheckCircleFill
                          className="text-success me-2 mt-1"
                          size={18}
                        />
                        <span>{step.text}</span>
                      </div>
                    ))}
                  </div>

                  <div className="mt-3 d-flex justify-content-between align-items-center">
                    <a href="#" className="see-updates text-primary">
                      See All Updates →
                    </a>
                    <div className="chat-link text-muted">💬 Chat with us</div>
                  </div>
                </div>
              )}
            </Col>
          </Row>
        )}
      </Container>
    </div>
        {/* Sticky Button for Mobile */}
      <div className="sticky-track-btn sticky-bottom d-md-none mt-5">
        <Button onClick={handleSubmit} className="track-btn w-100">
          Track your Order
        </Button>
      </div>
    </div>
  );
};

export default TrackOrder;
