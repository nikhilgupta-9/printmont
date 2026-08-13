import React, { useState } from "react";
import { Container, Row, Col, Form, Button, Card, Badge, ProgressBar, Spinner } from "react-bootstrap";
import {
  FaSearch,
  FaTruck,
  FaShoppingBag,
  FaBoxOpen,
  FaCheckCircle,
  FaTimesCircle,
  FaClock,
  FaHeadset,
  FaFileInvoice,
  FaBarcode,
  FaExternalLinkAlt
} from "react-icons/fa";
import { API_ENDPOINTS, resolveImageUrl } from "../../config/apiEndpoints";
import "./TrackOrder.css";

/**
 * The happy path a parcel walks through. Cancelled is deliberately absent —
 * it is not a stage but a termination, so it gets its own banner.
 */
const STAGES = [
  { key: "pending", label: "Ordered", Icon: FaShoppingBag },
  { key: "processing", label: "Processing", Icon: FaBoxOpen },
  { key: "shipped", label: "Shipped", Icon: FaTruck },
  { key: "delivered", label: "Delivered", Icon: FaCheckCircle },
];

const STATUS_TONE = {
  pending: "secondary",
  processing: "info",
  shipped: "primary",
  delivered: "success",
  cancelled: "danger",
};

const formatDateTime = (value) => {
  if (!value) return null;
  // MySQL hands back "YYYY-MM-DD HH:MM:SS", which Safari refuses to parse.
  const parsed = new Date(String(value).replace(" ", "T"));
  if (Number.isNaN(parsed.getTime())) return null;
  return parsed.toLocaleString("en-IN", {
    day: "2-digit", month: "short", year: "numeric",
    hour: "2-digit", minute: "2-digit",
  });
};

const TrackOrder = () => {
  const [searchBy, setSearchBy] = useState("orderid");
  const [formData, setFormData] = useState({
    trackingId: "",
    email: "",
    orderId: "",
    mobile: "",
  });
  const [order, setOrder] = useState(null);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const switchTab = (tab) => {
    setSearchBy(tab);
    setOrder(null);
    setError("");
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (loading) return;

    // Both tabs hit the same endpoint: the API accepts an order number or a
    // tracking number as the reference, and an email or mobile as the contact.
    const reference = searchBy === "tracking" ? formData.trackingId : formData.orderId;
    const contact = searchBy === "tracking" ? formData.email : formData.mobile;

    setLoading(true);
    setOrder(null);
    setError("");

    try {
      const res = await fetch(API_ENDPOINTS.TRACK_ORDER, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          order_number: reference.trim(),
          contact: contact.trim(),
        }),
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        setError(data.error || "We could not find that order.");
        return;
      }

      setOrder(data.data);
    } catch (err) {
      console.error("Order tracking failed:", err);
      setError("Could not reach the server. Please try again in a moment.");
    } finally {
      setLoading(false);
    }
  };

  const status = (order?.status || "pending").toLowerCase();
  const isCancelled = status === "cancelled";
  const stageIndex = Math.max(0, STAGES.findIndex((s) => s.key === status));

  /**
   * Earliest timestamp recorded for a given status. Orders placed before the
   * status trail existed have no history rows, so the first stage falls back
   * to the order's own creation time.
   */
  const stampFor = (stageKey) => {
    const rows = (order?.history || []).filter(
      (h) => (h.status || "").toLowerCase() === stageKey
    );
    if (rows.length) {
      const oldest = rows.reduce((a, b) => (a.created_at <= b.created_at ? a : b));
      return formatDateTime(oldest.created_at);
    }
    return stageKey === "pending" ? formatDateTime(order?.placed_at) : null;
  };

  // Newest first, for the update feed.
  const feed = [...(order?.history || [])].sort(
    (a, b) => String(b.created_at).localeCompare(String(a.created_at))
  );

  return (
    <div className="bg-light py-4 py-md-5" style={{ minHeight: "85vh", overflowX: "hidden" }}>
      <Container>
        <div className="text-center mb-4 mb-md-5">
          <Badge bg="primary-subtle" className="text-primary fw-bold px-3 py-2 mb-2 rounded-pill text-uppercase fs-7">
            Realtime Order Tracking
          </Badge>
          <h1 className="fw-bold text-dark display-6 display-md-5 mb-2">Track Shipment &amp; Order Status</h1>
          <p className="text-secondary mx-auto fs-6" style={{ maxWidth: "600px", lineHeight: "1.6" }}>
            Enter your order details below to trace your custom prints, parcels, and corporate delivery timelines.
          </p>
        </div>

        <Row className="justify-content-center">
          <Col lg={8} md={10}>
            <Card className="border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
              <div className="d-flex justify-content-center gap-3 mb-4 pb-3 border-bottom">
                <Button
                  variant={searchBy === "orderid" ? "primary" : "outline-secondary"}
                  className="rounded-pill px-4 py-2 fw-bold d-flex align-items-center gap-2"
                  onClick={() => switchTab("orderid")}
                >
                  <FaFileInvoice /> Order ID
                </Button>
                <Button
                  variant={searchBy === "tracking" ? "primary" : "outline-secondary"}
                  className="rounded-pill px-4 py-2 fw-bold d-flex align-items-center gap-2"
                  onClick={() => switchTab("tracking")}
                >
                  <FaBarcode /> Tracking ID
                </Button>
              </div>

              <Form onSubmit={handleSubmit}>
                <Row className="g-3 align-items-end">
                  {searchBy === "tracking" ? (
                    <>
                      <Col md={5} xs={12}>
                        <Form.Group>
                          <Form.Label className="small fw-semibold text-secondary">Tracking / AWB Number *</Form.Label>
                          <Form.Control
                            type="text" name="trackingId"
                            value={formData.trackingId} onChange={handleChange}
                            placeholder="E.g. AWB-89712634"
                            className="py-2 rounded-3 shadow-none border"
                            required
                          />
                        </Form.Group>
                      </Col>
                      <Col md={5} xs={12}>
                        <Form.Group>
                          <Form.Label className="small fw-semibold text-secondary">Registered Email Address *</Form.Label>
                          <Form.Control
                            type="email" name="email"
                            value={formData.email} onChange={handleChange}
                            placeholder="E.g. name@example.com"
                            className="py-2 rounded-3 shadow-none border"
                            required
                          />
                        </Form.Group>
                      </Col>
                    </>
                  ) : (
                    <>
                      <Col md={5} xs={12}>
                        <Form.Group>
                          <Form.Label className="small fw-semibold text-secondary">PrintMont Order ID *</Form.Label>
                          <Form.Control
                            type="text" name="orderId"
                            value={formData.orderId} onChange={handleChange}
                            placeholder="E.g. ORD-1011"
                            className="py-2 rounded-3 shadow-none border text-uppercase"
                            required
                          />
                        </Form.Group>
                      </Col>
                      <Col md={5} xs={12}>
                        <Form.Group>
                          <Form.Label className="small fw-semibold text-secondary">Mobile Number *</Form.Label>
                          <Form.Control
                            type="tel" name="mobile" pattern="[0-9]{10}"
                            value={formData.mobile} onChange={handleChange}
                            placeholder="10-digit mobile number"
                            className="py-2 rounded-3 shadow-none border"
                            required
                          />
                        </Form.Group>
                      </Col>
                    </>
                  )}

                  <Col md={2} xs={12}>
                    <Button
                      type="submit" variant="primary" disabled={loading}
                      className="w-100 py-2 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2 border-0"
                      style={{ backgroundColor: "#0b53a1" }}
                    >
                      {loading ? <Spinner size="sm" animation="border" /> : <><FaSearch /> Trace</>}
                    </Button>
                  </Col>
                </Row>
              </Form>
            </Card>

            {error && (
              <Card className="border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                <div className="text-danger fw-semibold">{error}</div>
              </Card>
            )}

            {order && (
              <div className="d-flex flex-column gap-4">

                {/* Summary */}
                <Card className="border-0 shadow-sm rounded-4 p-4 bg-white">
                  <div className="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <Badge bg={STATUS_TONE[status] || "secondary"} className="px-2 py-1 fw-bold rounded text-uppercase">
                      {status}
                    </Badge>
                    <span className="small text-muted border-start ps-2">
                      Order <strong>{order.order_number}</strong>
                    </span>
                    {order.placed_at && (
                      <span className="small text-muted border-start ps-2">
                        Placed {formatDateTime(order.placed_at)}
                      </span>
                    )}
                  </div>

                  <Row className="g-3">
                    <Col md={7}>
                      <div className="small text-secondary mb-1">Delivering to</div>
                      <div className="fw-semibold text-dark mb-1">{order.customer_name}</div>
                      <div className="small text-secondary" style={{ whiteSpace: "pre-line" }}>
                        {order.shipping_address}
                      </div>
                    </Col>
                    <Col md={5}>
                      <div className="small text-secondary mb-1">Order total</div>
                      <div className="fw-bold fs-5 text-dark mb-2">
                        ₹{Number(order.grand_total || 0).toLocaleString("en-IN")}
                      </div>
                      <div className="small text-secondary">
                        Payment: <strong className="text-dark">{order.payment_status || "pending"}</strong>
                      </div>
                    </Col>
                  </Row>

                  {(order.courier_name || order.tracking_number) && (
                    <div className="border-top mt-3 pt-3 small">
                      {order.courier_name && (
                        <div className="mb-1">Courier Partner: <strong>{order.courier_name}</strong></div>
                      )}
                      {order.tracking_number && (
                        <div className="mb-1">Tracking ID: <strong>{order.tracking_number}</strong></div>
                      )}
                      {order.tracking_url && (
                        <a
                          href={order.tracking_url} target="_blank" rel="noopener noreferrer"
                          className="fw-semibold text-decoration-none d-inline-flex align-items-center gap-1"
                          style={{ color: "#0b53a1" }}
                        >
                          Track on courier site <FaExternalLinkAlt size={11} />
                        </a>
                      )}
                    </div>
                  )}
                </Card>

                {/* Progress */}
                <Card className="border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                  <h6 className="fw-bold text-dark mb-4">Milestone Progress</h6>

                  {isCancelled ? (
                    <div className="d-flex align-items-center gap-3 p-3 rounded-3" style={{ backgroundColor: "#fdecec" }}>
                      <FaTimesCircle className="text-danger flex-shrink-0" size={26} />
                      <div>
                        <div className="fw-bold text-dark">This order was cancelled</div>
                        <div className="small text-secondary">
                          Contact support below if you believe this is a mistake.
                        </div>
                      </div>
                    </div>
                  ) : (
                    <>
                      <div className="position-relative mb-5 d-none d-md-block">
                        <ProgressBar
                          now={(stageIndex / (STAGES.length - 1)) * 100}
                          style={{ height: "4px", backgroundColor: "#e2e8f0" }}
                        />
                        <div className="d-flex justify-content-between position-absolute w-100" style={{ top: "-14px" }}>
                          {STAGES.map((stage, idx) => {
                            const done = idx <= stageIndex;
                            const stamp = stampFor(stage.key);
                            return (
                              <div key={stage.key} className="text-center" style={{ width: "90px" }}>
                                <div
                                  className="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2"
                                  style={{
                                    width: "32px", height: "32px",
                                    backgroundColor: done ? "#198754" : "#e2e8f0",
                                    color: done ? "#fff" : "#94a3b8",
                                  }}
                                >
                                  <stage.Icon />
                                </div>
                                <span className="small d-block fw-bold text-dark" style={{ fontSize: "0.78rem" }}>
                                  {stage.label}
                                </span>
                                <span className="text-muted d-block" style={{ fontSize: "0.68rem" }}>
                                  {stamp ? stamp.split(",")[0] : "—"}
                                </span>
                              </div>
                            );
                          })}
                        </div>
                      </div>

                      {/* Mobile: the same stages stacked. */}
                      <div className="d-md-none d-flex flex-column gap-3 mb-3">
                        {STAGES.map((stage, idx) => {
                          const done = idx <= stageIndex;
                          const stamp = stampFor(stage.key);
                          return (
                            <div key={stage.key} className="d-flex align-items-center gap-3">
                              <div
                                className="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                style={{
                                  width: "30px", height: "30px",
                                  backgroundColor: done ? "#198754" : "#e2e8f0",
                                  color: done ? "#fff" : "#94a3b8",
                                }}
                              >
                                <stage.Icon size={13} />
                              </div>
                              <div>
                                <div className="small fw-bold text-dark">{stage.label}</div>
                                <div className="text-muted" style={{ fontSize: "0.72rem" }}>{stamp || "Pending"}</div>
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    </>
                  )}

                  {feed.length > 0 && (
                    <div className="vertical-timeline d-flex flex-column gap-3 mt-md-4">
                      {feed.map((entry) => (
                        <div
                          key={entry.id}
                          className="d-flex gap-3 position-relative timeline-item pb-3 border-start ps-3"
                          style={{ borderColor: "#e2e8f0" }}
                        >
                          <div
                            className="timeline-badge rounded-circle bg-light border border-2 border-primary d-flex align-items-center justify-content-center flex-shrink-0"
                            style={{ width: "16px", height: "16px", marginLeft: "-22px" }}
                          >
                            <div className="rounded-circle bg-primary" style={{ width: "6px", height: "6px" }} />
                          </div>
                          <div>
                            <div className="small fw-bold text-dark mb-1">
                              {entry.notes || entry.comment || `Status updated to ${entry.status}`}
                            </div>
                            <span className="text-muted small" style={{ fontSize: "0.74rem" }}>
                              <FaClock size={11} className="me-1" />
                              {formatDateTime(entry.created_at) || "—"}
                            </span>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </Card>

                {/* Items */}
                {order.items?.length > 0 && (
                  <Card className="border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h6 className="fw-bold text-dark mb-3">Items in this order</h6>
                    <div className="d-flex flex-column gap-3">
                      {order.items.map((item) => (
                        <div key={item.id} className="d-flex align-items-center gap-3">
                          <div
                            style={{ width: "56px", height: "56px", overflow: "hidden", borderRadius: "10px", border: "1px solid #eee" }}
                            className="flex-shrink-0"
                          >
                            <img
                              src={resolveImageUrl(item.product_image)}
                              alt={item.product_name}
                              className="w-100 h-100"
                              style={{ objectFit: "contain" }}
                            />
                          </div>
                          <div className="flex-grow-1">
                            <div className="small fw-semibold text-dark">{item.product_name}</div>
                            <div className="text-muted" style={{ fontSize: "0.75rem" }}>
                              Qty {item.quantity}
                            </div>
                          </div>
                          <div className="fw-semibold text-dark small">
                            ₹{Number(item.total_price || 0).toLocaleString("en-IN")}
                          </div>
                        </div>
                      ))}
                    </div>
                  </Card>
                )}

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
                      1800-123-4567
                    </a>
                  </div>
                </Card>
              </div>
            )}
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default TrackOrder;
