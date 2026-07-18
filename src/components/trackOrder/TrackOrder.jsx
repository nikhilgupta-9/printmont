import React, { useState } from "react";
import { Container, Row, Col, Form, Button, Card, Badge, ProgressBar } from "react-bootstrap";
import { 
  FaSearch, 
  FaEnvelope, 
  FaPhoneAlt, 
  FaTruck, 
  FaShoppingBag, 
  FaBoxOpen, 
  FaCheckCircle, 
  FaClock, 
  FaMapMarkerAlt, 
  FaHeadset,
  FaFileInvoice,
  FaBarcode
} from "react-icons/fa";
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
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  // Demo Order details with detailed milestones
  const dummyOrder = {
    orderNumber: "ORD-9827361",
    trackingNumber: "AWB-89712634",
    courierName: "BlueDart Express",
    estDelivery: "Jan 12, 2026",
    productName: "METRONAUT BENT - Multicolor Cabin & Check-in Set (Set of 3 Trolley Bags)",
    specs: "Multicolor · 4 Wheels · Polycarbonate · 30-inch",
    seller: "HSAtlastradeFashion (Official Partner)",
    price: "₹3,912",
    image: "https://rukminim2.flixcart.com/image/416/416/xif0q/luggage/5/x/b/30-20-24-28-4-wheels-polycarbonate-bent-trolley-bag-set-of-3-original-imagufg4hycjhuey.jpeg?q=70",
    currentStage: 3, // Out for Delivery
    milestones: [
      { label: "Ordered", status: "Order Confirmed", time: "Jan 05, 10:30 AM", done: true, icon: <FaShoppingBag /> },
      { label: "Dispatched", status: "Shipped from New Delhi Hub", time: "Jan 08, 04:15 PM", done: true, icon: <FaBoxOpen /> },
      { label: "In Transit", status: "Arrived at Regional Sorting Facility", time: "Jan 10, 09:00 AM", done: true, icon: <FaTruck /> },
      { label: "Out for Delivery", status: "Out for Delivery with courier agent", time: "Jan 11, 08:30 AM", done: true, icon: <FaMapMarkerAlt /> },
      { label: "Delivered", status: "Delivered at doorstep", time: "Jan 12, 02:45 PM", done: false, icon: <FaCheckCircle /> }
    ],
    updates: [
      { date: "Jan 11, 2026", time: "08:30 AM", detail: "Out for delivery with courier associate Sunil Kumar (Ph: +91 98765 43210)." },
      { date: "Jan 10, 2026", time: "09:00 AM", detail: "Arrived at regional delivery hub (Faridabad)." },
      { date: "Jan 09, 2026", time: "11:20 AM", detail: "In-transit to destination facility." },
      { date: "Jan 08, 2026", time: "04:15 PM", detail: "Parcel handed over to BlueDart courier team." },
      { date: "Jan 05, 2026", time: "10:30 AM", detail: "Order payment verified and sent to warehouse production." }
    ]
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setLoading(true);
    // Simulate lookup delay
    setTimeout(() => {
      setLoading(false);
      const isTrackingMatch = searchBy === "tracking" && formData.trackingId.trim().toUpperCase() === "TRK123";
      const isOrderMatch = searchBy === "orderid" && formData.orderId.trim().toUpperCase() === "ORD123";
      
      if (isTrackingMatch || isOrderMatch) {
        setOrderDetails(dummyOrder);
      } else {
        setOrderDetails({
          error: "❌ Order lookup failed. Please enter test Tracking ID: TRK123 or test Order ID: ORD123 to verify."
        });
      }
    }, 800);
  };

  return (
    <div className="bg-light py-4 py-md-5" style={{ minHeight: "85vh", overflowX: "hidden" }}>
      <Container>
        {/* HEADER BAR */}
        <div className="text-center mb-4 mb-md-5">
          <Badge bg="primary-subtle" className="text-primary fw-bold px-3 py-2 mb-2 rounded-pill text-uppercase fs-7">
            Realtime Order Tracking
          </Badge>
          <h1 className="fw-bold text-dark display-6 display-md-5 mb-2">Track Shipment & Order Status</h1>
          <p className="text-secondary mx-auto fs-6" style={{ maxWidth: "600px", lineHeight: "1.6" }}>
            Enter your booking details below to trace your custom prints, parcels, and corporate delivery timelines.
          </p>
        </div>

        {/* SEARCH SELECTOR CARD */}
        <Row className="justify-content-center">
          <Col lg={8} md={10}>
            <Card className="border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
              {/* Tabs selector */}
              <div className="d-flex justify-content-center gap-3 mb-4 pb-3 border-bottom">
                <Button
                  variant={searchBy === "tracking" ? "primary" : "outline-secondary"}
                  className="rounded-pill px-4 py-2 fw-bold d-flex align-items-center gap-2"
                  onClick={() => { setSearchBy("tracking"); setOrderDetails(null); }}
                >
                  <FaBarcode /> Tracking ID
                </Button>
                <Button
                  variant={searchBy === "orderid" ? "primary" : "outline-secondary"}
                  className="rounded-pill px-4 py-2 fw-bold d-flex align-items-center gap-2"
                  onClick={() => { setSearchBy("orderid"); setOrderDetails(null); }}
                >
                  <FaFileInvoice /> Order ID
                </Button>
              </div>

              {/* Form Input Blocks */}
              <Form onSubmit={handleSubmit}>
                {searchBy === "tracking" ? (
                  <Row className="g-3 align-items-end">
                    <Col md={5} xs={12}>
                      <Form.Group>
                        <Form.Label className="small fw-semibold text-secondary">Tracking / AWB Number *</Form.Label>
                        <Form.Control
                          type="text"
                          name="trackingId"
                          value={formData.trackingId}
                          onChange={handleChange}
                          placeholder="E.g. TRK123"
                          className="py-2.5 rounded-3 shadow-none border text-uppercase"
                          required
                        />
                      </Form.Group>
                    </Col>
                    <Col md={5} xs={12}>
                      <Form.Group>
                        <Form.Label className="small fw-semibold text-secondary">Registered Email Address *</Form.Label>
                        <Form.Control
                          type="email"
                          name="email"
                          value={formData.email}
                          onChange={handleChange}
                          placeholder="E.g. name@example.com"
                          className="py-2.5 rounded-3 shadow-none border"
                          required
                        />
                      </Form.Group>
                    </Col>
                    <Col md={2} xs={12}>
                      <Button type="submit" variant="primary" disabled={loading} className="w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2 border-0" style={{ backgroundColor: '#0b53a1' }}>
                        {loading ? <Spinner size="sm" animation="border" /> : <><FaSearch /> Trace</>}
                      </Button>
                    </Col>
                  </Row>
                ) : (
                  <Row className="g-3 align-items-end">
                    <Col md={5} xs={12}>
                      <Form.Group>
                        <Form.Label className="small fw-semibold text-secondary">PrintMont Order ID *</Form.Label>
                        <Form.Control
                          type="text"
                          name="orderId"
                          value={formData.orderId}
                          onChange={handleChange}
                          placeholder="E.g. ORD123"
                          className="py-2.5 rounded-3 shadow-none border text-uppercase"
                          required
                        />
                      </Form.Group>
                    </Col>
                    <Col md={5} xs={12}>
                      <Form.Group>
                        <Form.Label className="small fw-semibold text-secondary">Mobile Number *</Form.Label>
                        <Form.Control
                          type="tel"
                          name="mobile"
                          pattern="[0-9]{10}"
                          value={formData.mobile}
                          onChange={handleChange}
                          placeholder="10-digit mobile number"
                          className="py-2.5 rounded-3 shadow-none border"
                          required
                        />
                      </Form.Group>
                    </Col>
                    <Col md={2} xs={12}>
                      <Button type="submit" variant="primary" disabled={loading} className="w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2 border-0" style={{ backgroundColor: '#0b53a1' }}>
                        {loading ? <Spinner size="sm" animation="border" /> : <><FaSearch /> Trace</>}
                      </Button>
                    </Col>
                  </Row>
                )}
              </Form>
            </Card>

            {/* RESULTS VIEW */}
            {orderDetails && (
              <div className="mt-4">
                {orderDetails.error ? (
                  <Card className="border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                    <div className="text-danger fw-bold fs-6">{orderDetails.error}</div>
                  </Card>
                ) : (
                  <div className="d-flex flex-column gap-4">
                    {/* PACKAGE SUMMARY CARD */}
                    <Card className="border-0 shadow-sm rounded-4 p-4 bg-white">
                      <Row className="align-items-center g-3">
                        <Col md={8}>
                          <div className="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <Badge bg="success" className="px-2.5 py-1 fw-bold rounded">IN TRANSIT</Badge>
                            <span className="small text-muted border-start ps-2">Est. Delivery: <strong>{orderDetails.estDelivery}</strong></span>
                          </div>
                          <h5 className="fw-bold text-dark mb-1">{orderDetails.productName}</h5>
                          <p className="small text-secondary mb-2">{orderDetails.specs}</p>
                          <div className="small text-dark-emphasis">
                            Courier Partner: <strong>{orderDetails.courierName}</strong> | Tracking ID: <strong>{orderDetails.trackingNumber}</strong>
                          </div>
                        </Col>
                        <Col md={4} className="text-center text-md-end">
                          <div className="product-image-wrapper mx-auto ms-md-auto" style={{ width: "90px", height: "90px", overflow: "hidden", borderRadius: "12px", border: "1px solid #eee" }}>
                            <img src={orderDetails.image} alt="Package thumbnail" className="w-100 h-100 object-fit-contain" />
                          </div>
                        </Col>
                      </Row>
                    </Card>

                    {/* DYNAMIC PROGRESS STEPPER LINE */}
                    <Card className="border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                      <h6 className="fw-bold text-dark mb-4">Milestone Progress</h6>
                      
                      {/* Horizontal Line Stepper (Desktop) */}
                      <div className="position-relative mb-5 d-none d-md-block">
                        <ProgressBar now={75} style={{ height: "4px", backgroundColor: "#e2e8f0" }} />
                        <div className="d-flex justify-content-between position-absolute w-100" style={{ top: "-14px" }}>
                          {orderDetails.milestones.map((milestone, idx) => (
                            <div key={idx} className="text-center" style={{ width: "80px" }}>
                              <div 
                                className={`rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 shadow-xs transition-all`} 
                                style={{ 
                                  width: "32px", 
                                  height: "32px", 
                                  backgroundColor: milestone.done ? "#198754" : "#e2e8f0", 
                                  color: milestone.done ? "#fff" : "#94a3b8" 
                                }}
                              >
                                {milestone.icon}
                              </div>
                              <span className="small d-block fw-bold text-dark" style={{ fontSize: "0.78rem" }}>{milestone.label}</span>
                              <span className="text-muted d-block" style={{ fontSize: "0.68rem" }}>{milestone.time.split(",")[0]}</span>
                            </div>
                          ))}
                        </div>
                      </div>

                      {/* Vertical Milestones (Mobile Details) */}
                      <div className="vertical-timeline d-flex flex-column gap-3 mt-md-4">
                        {orderDetails.updates.map((update, idx) => (
                          <div key={idx} className="d-flex gap-3 position-relative timeline-item pb-3 border-start ps-3" style={{ borderColor: "#e2e8f0" }}>
                            <div className="timeline-badge rounded-circle bg-light border border-2 border-primary d-flex align-items-center justify-content-center flex-shrink-0" style={{ width: "16px", height: "16px", marginLeft: "-22px" }}>
                              <div className="rounded-circle bg-primary" style={{ width: "6px", height: "6px" }} />
                            </div>
                            <div>
                              <div className="d-flex align-items-center gap-2 mb-1">
                                <span className="small fw-bold text-dark">{update.detail}</span>
                              </div>
                              <span className="text-muted small" style={{ fontSize: "0.74rem" }}><FaClock size={11} className="me-1" /> {update.date} at {update.time}</span>
                            </div>
                          </div>
                        ))}
                      </div>
                    </Card>

                    {/* CONTACT ASSISTANCE HELP DESK */}
                    <Card className="border-0 shadow-sm rounded-4 p-4 bg-white text-center">
                      <h6 className="fw-bold text-dark mb-2">Need Help with Your Delivery?</h6>
                      <p className="text-secondary small mx-auto mb-3" style={{ maxWidth: "480px" }}>
                        Reach out to our customer support desk regarding parcel status, delivery redirection, or package damages.
                      </p>
                      <div className="d-flex flex-wrap justify-content-center gap-3">
                        <a href="mailto:support@printmont.com" className="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold d-flex align-items-center gap-2">
                          <FaHeadset /> support@printmont.com
                        </a>
                        <a href="tel:1800-123-4567" className="btn btn-light btn-sm rounded-pill px-4 fw-bold border text-secondary">
                          <FaPhoneAlt size={11} className="me-1" /> 1800-123-4567
                        </a>
                      </div>
                    </Card>
                  </div>
                )}
              </div>
            )}
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default TrackOrder;
