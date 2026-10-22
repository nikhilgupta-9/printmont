import React, { useState, useEffect } from "react";
import { useSearchParams, useNavigate, useLocation, Link } from "react-router-dom";
import { 
  FaSearch, 
  FaTruck, 
  FaBoxOpen, 
  FaCheckCircle, 
  FaTimesCircle, 
  FaClock, 
  FaHeadset, 
  FaFileInvoice, 
  FaBarcode, 
  FaExternalLinkAlt, 
  FaMapMarkerAlt, 
  FaCheck, 
  FaBan, 
  FaSyncAlt, 
  FaStar,
  FaArrowLeft,
  FaInfoCircle
} from "react-icons/fa";
import { 
  IoLocationOutline, 
  IoShieldCheckmarkOutline, 
  IoDocumentTextOutline, 
  IoAlertCircleOutline,
  IoCopyOutline,
  IoCheckmarkDone
} from "react-icons/io5";
import { Spinner, Alert } from "react-bootstrap";
import toast from "react-hot-toast";
import { API_ENDPOINTS, resolveImageUrl } from "../../config/apiEndpoints";
import { useAuth } from "../../context/AuthContext";
import { updatePageSEO } from "../../utils/seo";
import AccountSidebar from "../user/AccountSidebar";
import TaxInvoiceModal from "../orders/TaxInvoiceModal";
import OrderHelpModal from "../orders/OrderHelpModal";
import CancelOrderModal from "../orders/CancelOrderModal";
import ReturnReplacementModal from "../orders/ReturnReplacementModal";
import OrderReviewModal from "../orders/OrderReviewModal";
import "../user/UserDashboard.css";
import "./TrackOrder.css";

/**
 * Flipkart-style 5-Stage Milestone Progression
 */
const TRACKING_STAGES = [
  { key: "placed", label: "Ordered", sub: "Order placed & verified", icon: FaFileInvoice },
  { key: "confirmed", label: "Approved", sub: "Print file verified", icon: FaBoxOpen },
  { key: "shipped", label: "Shipped", sub: "Dispatched via courier", icon: FaTruck },
  { key: "out_for_delivery", label: "Out for Delivery", sub: "Reached local hub", icon: FaMapMarkerAlt },
  { key: "delivered", label: "Delivered", sub: "Handed over to customer", icon: FaCheckCircle },
];

const STATUS_COLOR = {
  delivered: '#16a34a',
  shipped: '#00529b',
  processing: '#f59e0b',
  confirmed: '#00529b',
  pending: '#f59e0b',
  cancelled: '#dc2626',
  refunded: '#64748b',
  return_requested: '#d97706',
  returned: '#64748b',
  replacement_requested: '#2563eb'
};

const formatDateTime = (value) => {
  if (!value) return null;
  try {
    const parsed = new Date(String(value).replace(" ", "T"));
    if (Number.isNaN(parsed.getTime())) return String(value);
    return parsed.toLocaleString("en-IN", {
      day: "2-digit",
      month: "short",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return String(value);
  }
};

const formatDateOnly = (value) => {
  if (!value) return null;
  try {
    const parsed = new Date(String(value).replace(" ", "T"));
    if (Number.isNaN(parsed.getTime())) return String(value);
    return parsed.toLocaleDateString("en-IN", {
      weekday: "short",
      day: "2-digit",
      month: "short",
      year: "numeric"
    });
  } catch {
    return String(value);
  }
};

const TrackOrder = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const location = useLocation();
  const { user, token, getUsernamePath } = useAuth();
  const usernamePath = getUsernamePath ? getUsernamePath() : "user";

  // Check if we are already rendered inside the User.jsx wrapper
  const isInsideUserWrapper = location.pathname.startsWith(`/${usernamePath}/`) || location.pathname.startsWith('/user/');

  const urlRef = searchParams.get("order") || searchParams.get("tracking") || searchParams.get("id") || "";

  const [searchBy, setSearchBy] = useState("orderid"); // 'orderid' or 'tracking'
  const [formData, setFormData] = useState({
    trackingId: "",
    email: user?.email || "",
    orderId: urlRef || "",
    mobile: user?.mobile || user?.phone || "",
  });

  const [userRecentOrders, setUserRecentOrders] = useState([]);
  const [order, setOrder] = useState(null);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [copiedAWB, setCopiedAWB] = useState(false);

  // Modals
  const [showInvoiceModal, setShowInvoiceModal] = useState(false);
  const [showHelpModal, setShowHelpModal] = useState(false);
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [showReturnModal, setShowReturnModal] = useState(false);
  const [activeReviewItem, setActiveReviewItem] = useState(null);

  useEffect(() => {
    updatePageSEO({
      title: "Live Order Tracking | Printmont Dashboard",
      description: "Track your print orders, dispatch details, and live courier AWB delivery status in real-time."
    });
  }, []);

  // Fetch logged-in user's recent orders for quick tracking pills
  useEffect(() => {
    if (!token || !user?.id) return;
    const fetchRecent = async () => {
      try {
        const res = await fetch(API_ENDPOINTS.GET_CUSTOMER_ORDERS({ limit: 4 }), {
          headers: { Authorization: `Bearer ${token}` },
        });
        const data = await res.json();
        if (data.success && Array.isArray(data.data)) {
          setUserRecentOrders(data.data);
        }
      } catch (e) {
        console.debug("Failed to load user recent orders:", e);
      }
    };
    fetchRecent();
  }, [token, user?.id]);

  // Execute tracking lookup
  const trackSpecificOrder = async (reference, contactValue = "") => {
    if (!reference || !reference.trim()) return;

    setLoading(true);
    setError("");
    setOrder(null);

    const cleanRef = reference.trim();
    const cleanContact = contactValue.trim() || formData.mobile.trim() || formData.email.trim() || user?.email || user?.mobile || "";

    try {
      const res = await fetch(API_ENDPOINTS.TRACK_ORDER, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        body: JSON.stringify({
          order_number: cleanRef,
          contact: cleanContact,
        }),
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        setError(data.error || "We could not find that order. Please check the order details and try again.");
        return;
      }

      setOrder(data.data);
      setSearchParams({ order: cleanRef }, { replace: true });
    } catch (err) {
      console.error("Order tracking error:", err);
      setError("Unable to connect to tracking server. Please check your internet connection and try again.");
    } finally {
      setLoading(false);
    }
  };

  // Auto track if URL has query parameter
  useEffect(() => {
    if (urlRef) {
      trackSpecificOrder(urlRef);
    }
  }, [urlRef]);

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const switchTab = (tab) => {
    setSearchBy(tab);
    setError("");
  };

  const handleFormSubmit = (e) => {
    e.preventDefault();
    if (loading) return;

    const reference = searchBy === "tracking" ? formData.trackingId : formData.orderId;
    const contact = searchBy === "tracking" ? formData.email : formData.mobile;

    trackSpecificOrder(reference, contact);
  };

  const handleCopyAWB = (awbText) => {
    if (!awbText) return;
    navigator.clipboard.writeText(awbText);
    setCopiedAWB(true);
    toast.success("AWB Tracking Number copied to clipboard!");
    setTimeout(() => setCopiedAWB(false), 2500);
  };

  const status = (order?.status || "pending").toLowerCase();
  const isCancelled = status === "cancelled";
  const isDelivered = status === "delivered";
  const isShipped = status === "shipped" || isDelivered;
  const isConfirmed = status === "confirmed" || isShipped;
  const isReturnRequested = status === "return_requested";
  const isReplacementRequested = status === "replacement_requested";
  const isCancellable = ["pending", "confirmed", "processing"].includes(status);

  // Calculate current stage index in 5-milestone progression
  let currentStageIdx = 0;
  if (isDelivered) {
    currentStageIdx = 4;
  } else if (status === "out_for_delivery") {
    currentStageIdx = 3;
  } else if (status === "shipped") {
    currentStageIdx = 2;
  } else if (status === "confirmed" || status === "processing") {
    currentStageIdx = 1;
  } else {
    currentStageIdx = 0;
  }

  const items = Array.isArray(order?.items) && order.items.length > 0 ? order.items : [];

  // Content pane rendering
  const renderTrackOrderContent = () => (
    <div className="track-order-content-pane">
      {/* ─────────────────────────────────────────────────────────── */}
      {/* 1. Desktop Breadcrumbs (Rule 9: Desktop Only >= 768px)      */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="orders-breadcrumb-wrapper d-none d-md-flex">
        <button
          type="button"
          className="orders-back-link"
          onClick={() => navigate(`/${usernamePath}/orders`)}
          aria-label="Back to Orders"
        >
          <FaArrowLeft size={12} /> Back to My Orders
        </button>
        <nav aria-label="breadcrumb">
          <ol className="orders-breadcrumb">
            <li><Link to="/">Home</Link></li>
            <span>/</span>
            <li><Link to={`/${usernamePath}/profile`}>My Account</Link></li>
            <span>/</span>
            <li><Link to={`/${usernamePath}/orders`}>My Orders</Link></li>
            <span>/</span>
            <li className="active" aria-current="page">Track Order</li>
          </ol>
        </nav>
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 2. Mobile Topbar (Rule 4 & Rule 8: Screens < 992px)         */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="d-lg-none mobile-track-topbar">
        <button
          type="button"
          className="mobile-track-back-btn"
          onClick={() => navigate(-1)}
          aria-label="Go Back"
        >
          <FaArrowLeft size={15} />
        </button>
        <div className="mobile-track-title">
          <h5 className="mb-0 fw-bold">Live Order Tracking</h5>
          <span className="text-muted" style={{ fontSize: '0.74rem' }}>Real-time GPS Milestones &amp; Hub Updates</span>
        </div>
        {user && (
          <Link
            to={`/${usernamePath}/orders`}
            className="mobile-track-orders-link"
            title="My Orders"
          >
            <FaBoxOpen size={16} />
          </Link>
        )}
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 3. Desktop Dashboard Header Card (Rule 4: Hidden on Mobile) */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="desktop-track-header-card d-none d-lg-flex">
        <div className="desktop-track-header-left">
          <div className="desktop-track-header-icon-box">
            <FaTruck />
          </div>
          <div className="desktop-track-header-meta">
            <h4 className="desktop-track-header-title">Live Shipment &amp; Order Tracker</h4>
            <p className="desktop-track-header-sub">
              Trace manufacturing progress, hub dispatch, and real-time courier GPS milestones
            </p>
          </div>
        </div>
        <div className="desktop-track-badge">
          <IoShieldCheckmarkOutline size={16} className="text-primary me-1" />
          <span>Verified Logistics Network</span>
        </div>
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 4. Search & Quick Track Card                                */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="track-search-card p-3 p-md-4 mb-3 mb-md-4">
        <div className="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-2 border-bottom">
          <div>
            <h6 className="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
              <FaSearch className="text-primary" size={13} /> Search &amp; Track Consignment
            </h6>
            <small className="text-muted">Enter your Printmont Order ID or Courier AWB number below</small>
          </div>

          {/* Mode Switches */}
          <div className="d-flex gap-1 bg-light p-1 rounded-pill border">
            <button
              type="button"
              className={`track-tab-btn ${searchBy === "orderid" ? "active" : ""}`}
              onClick={() => switchTab("orderid")}
            >
              <FaFileInvoice className="me-1" size={12} /> Order ID
            </button>
            <button
              type="button"
              className={`track-tab-btn ${searchBy === "tracking" ? "active" : ""}`}
              onClick={() => switchTab("tracking")}
            >
              <FaBarcode className="me-1" size={12} /> AWB Tracking ID
            </button>
          </div>
        </div>

        {/* Quick Tracking Chips for Logged-In User */}
        {userRecentOrders.length > 0 && (
          <div className="mb-3 d-flex flex-wrap align-items-center gap-2">
            <span className="small text-muted fw-semibold me-1">Your Recent Orders:</span>
            {userRecentOrders.map((ro) => {
              const roNumber = ro.order_number || `#${ro.id}`;
              const isSelected = order?.order_number === ro.order_number || String(order?.id) === String(ro.id);
              return (
                <button
                  key={ro.id}
                  type="button"
                  className={`quick-order-chip ${isSelected ? "active" : ""}`}
                  onClick={() => {
                    setFormData({ ...formData, orderId: ro.order_number || ro.id });
                    trackSpecificOrder(ro.order_number || ro.id);
                  }}
                >
                  <span>{roNumber}</span>
                  <span className="ms-1 text-capitalize opacity-75">({ro.status})</span>
                </button>
              );
            })}
          </div>
        )}

        {/* Search Form */}
        <form onSubmit={handleFormSubmit}>
          <div className="row g-2 g-md-3 align-items-end">
            {searchBy === "orderid" ? (
              <>
                <div className="col-12 col-md-5">
                  <label className="form-label small fw-bold text-dark mb-1">
                    PrintMont Order ID <span className="text-danger">*</span>
                  </label>
                  <div className="input-group">
                    <span className="input-group-text bg-light text-muted border-end-0">
                      <FaFileInvoice size={14} />
                    </span>
                    <input
                      type="text"
                      name="orderId"
                      className="form-control border-start-0 text-uppercase fw-semibold"
                      placeholder="e.g. ORD-1011 or AK3H8CWWKHPS"
                      value={formData.orderId}
                      onChange={handleChange}
                      required
                    />
                  </div>
                </div>

                <div className="col-12 col-md-5">
                  <label className="form-label small fw-bold text-dark mb-1">
                    Registered Mobile / Email <span className="text-muted fw-normal">({user ? "Auto-filled" : "Required for guest"})</span>
                  </label>
                  <input
                    type="text"
                    name="mobile"
                    className="form-control"
                    placeholder="10-digit mobile or email address"
                    value={formData.mobile || formData.email}
                    onChange={(e) => {
                      const val = e.target.value;
                      if (val.includes("@")) {
                        setFormData({ ...formData, email: val });
                      } else {
                        setFormData({ ...formData, mobile: val });
                      }
                    }}
                    required={!token}
                  />
                </div>
              </>
            ) : (
              <>
                <div className="col-12 col-md-5">
                  <label className="form-label small fw-bold text-dark mb-1">
                    AWB / Tracking Number <span className="text-danger">*</span>
                  </label>
                  <div className="input-group">
                    <span className="input-group-text bg-light text-muted border-end-0">
                      <FaBarcode size={14} />
                    </span>
                    <input
                      type="text"
                      name="trackingId"
                      className="form-control border-start-0 fw-semibold"
                      placeholder="e.g. 4820917365 or DEL98124"
                      value={formData.trackingId}
                      onChange={handleChange}
                      required
                    />
                  </div>
                </div>

                <div className="col-12 col-md-5">
                  <label className="form-label small fw-bold text-dark mb-1">
                    Registered Email / Phone <span className="text-muted fw-normal">({user ? "Auto-filled" : "Required for guest"})</span>
                  </label>
                  <input
                    type="text"
                    name="email"
                    className="form-control"
                    placeholder="e.g. name@example.com or phone"
                    value={formData.email || formData.mobile}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    required={!token}
                  />
                </div>
              </>
            )}

            <div className="col-12 col-md-2">
              <button
                type="submit"
                className="btn btn-primary w-100 fw-bold d-inline-flex align-items-center justify-content-center gap-2 py-2 shadow-sm rounded-3 track-submit-btn"
                disabled={loading}
              >
                {loading ? (
                  <>
                    <Spinner animation="border" size="sm" />
                    <span>Tracing...</span>
                  </>
                ) : (
                  <>
                    <FaSearch size={13} />
                    <span>Trace Order</span>
                  </>
                )}
              </button>
            </div>
          </div>
        </form>
      </div>

      {/* Error Alert */}
      {error && (
        <Alert variant="danger" className="rounded-3 p-3 shadow-xs d-flex align-items-start gap-2 mb-3">
          <IoAlertCircleOutline size={22} className="flex-shrink-0 text-danger mt-1" />
          <div className="flex-grow-1">
            <strong className="d-block mb-1">Tracking Lookup Failed</strong>
            <span className="small">{error}</span>
            {user && (
              <div className="mt-2">
                <Link to={`/${usernamePath}/orders`} className="btn btn-sm btn-outline-danger rounded-pill px-3 py-1">
                  View All Orders
                </Link>
              </div>
            )}
          </div>
        </Alert>
      )}

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 5. Live Tracking Details Feed (When Order Is Loaded)       */}
      {/* ─────────────────────────────────────────────────────────── */}
      {order && (
        <div className="d-flex flex-column gap-3 mb-4">
          
          {/* Expected Delivery & Courier Banner */}
          <div className={`expected-delivery-banner p-3 p-md-4 shadow-sm ${isCancelled ? "cancelled" : isDelivered ? "delivered" : ""}`}>
            <div className="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <div className="d-flex align-items-center gap-2 mb-1">
                  <span className="badge bg-white text-dark fw-bold px-2 py-1 text-uppercase" style={{ fontSize: "0.76rem" }}>
                    ● {status.replace("_", " ")}
                  </span>
                  <span className="text-white-50 small">Order {order.order_number || `#${order.id}`}</span>
                </div>

                <h3 className="fw-bold mb-1 fs-4 fs-md-3 text-white">
                  {isDelivered ? (
                    <>🎉 Delivered on {formatDateOnly(order.updated_at || order.placed_at)}</>
                  ) : isCancelled ? (
                    <>Order Cancelled</>
                  ) : isReturnRequested ? (
                    <>Return Request Received</>
                  ) : isReplacementRequested ? (
                    <>Replacement Reprint in Transit</>
                  ) : status === "out_for_delivery" ? (
                    <>🚚 Out for Delivery Today</>
                  ) : (
                    <>⚡ Expected Delivery by {order.expected_delivery || "Within 4–5 Days"}</>
                  )}
                </h3>

                <p className="mb-0 text-white-75 small">
                  {isDelivered 
                    ? "Package was successfully handed over to customer."
                    : isCancelled
                    ? "This order was cancelled. Any refund has been processed."
                    : "Your personalized print consignment is progressing smoothly through our hub network."}
                </p>
              </div>

              {/* Courier Partner Info */}
              {(order.courier_name || order.tracking_number || order.courier_tracking_number) && (
                <div className="courier-info-pill">
                  <div className="small text-white-75 mb-1 d-flex align-items-center gap-1">
                    <FaTruck size={12} /> Courier Partner
                  </div>
                  <div className="fw-bold fs-6 mb-1 text-white">
                    {order.courier_name || "Express Logistics"}
                  </div>
                  
                  {(order.tracking_number || order.courier_tracking_number) && (
                    <div className="d-flex align-items-center gap-2 small">
                      <span className="text-white-75">AWB:</span>
                      <strong className="text-white">{order.tracking_number || order.courier_tracking_number}</strong>
                      <button
                        type="button"
                        className="copy-badge-btn"
                        title="Copy AWB Tracking Number"
                        onClick={() => handleCopyAWB(order.tracking_number || order.courier_tracking_number)}
                      >
                        {copiedAWB ? <IoCheckmarkDone size={16} className="text-success" /> : <IoCopyOutline size={15} />}
                      </button>
                    </div>
                  )}

                  {order.tracking_url && (
                    <a
                      href={order.tracking_url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="btn btn-sm btn-light rounded-pill px-3 py-1 fw-bold text-dark mt-2 d-inline-flex align-items-center gap-1"
                      style={{ fontSize: "0.75rem" }}
                    >
                      <span>Live Courier GPS</span> <FaExternalLinkAlt size={10} />
                    </a>
                  )}
                </div>
              )}
            </div>
          </div>

          {/* Flipkart 5-Stage Live Milestone Stepper */}
          <div className="card border-0 shadow-sm rounded-3 p-3 p-md-4 bg-white">
            <h6 className="fw-bold text-dark mb-3 d-flex align-items-center justify-content-between">
              <span>📍 Live Milestone Progression</span>
              <span className="small text-muted fw-normal">Updated live from manufacturing hub</span>
            </h6>

            {/* Desktop Stepper */}
            <div className="d-none d-md-block flipkart-track-stepper-desktop">
              <div className="stepper-progress-bar-bg">
                <div 
                  className="stepper-progress-bar-fill" 
                  style={{ width: `${(currentStageIdx / (TRACKING_STAGES.length - 1)) * 100}%` }}
                ></div>
              </div>

              <div className="d-flex justify-content-between position-relative">
                {TRACKING_STAGES.map((stage, idx) => {
                  const isCompleted = idx <= currentStageIdx && !isCancelled;
                  const isCurrent = idx === currentStageIdx && !isCancelled;
                  const IconComp = stage.icon;

                  return (
                    <div 
                      key={stage.key} 
                      className={`stepper-node-item ${isCompleted ? "completed" : ""} ${isCurrent ? "current" : ""}`}
                    >
                      <div className="stepper-node-circle">
                        {isCompleted ? <FaCheck size={13} /> : <IconComp size={14} />}
                      </div>
                      <div className="stepper-node-title">{stage.label}</div>
                      <div className="stepper-node-subtitle">{stage.sub}</div>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Mobile Vertical Stepper */}
            <div className="d-md-none flipkart-track-stepper-mobile pt-2">
              {TRACKING_STAGES.map((stage, idx) => {
                const isCompleted = idx <= currentStageIdx && !isCancelled;
                const isCurrent = idx === currentStageIdx && !isCancelled;
                const isLast = idx === TRACKING_STAGES.length - 1;
                const IconComp = stage.icon;

                return (
                  <div 
                    key={stage.key} 
                    className={`mobile-step-row ${isCompleted ? "completed" : ""} ${isCurrent ? "current" : ""}`}
                  >
                    {!isLast && <div className="mobile-step-connector"></div>}
                    <div className="mobile-node-icon">
                      {isCompleted ? <FaCheck size={12} /> : <IconComp size={12} />}
                    </div>
                    <div className="min-width-0 flex-grow-1">
                      <div className="d-flex align-items-center justify-content-between">
                        <h6 className="mb-0 fw-bold fs-6 text-dark">{stage.label}</h6>
                        {isCurrent && (
                          <span className="badge bg-primary-subtle text-primary rounded-pill px-2 py-0" style={{ fontSize: "0.7rem" }}>
                            Current Stage
                          </span>
                        )}
                      </div>
                      <small className="text-muted d-block">{stage.sub}</small>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Live Chronological Transit History */}
          {Array.isArray(order.history) && order.history.length > 0 && (
            <div className="card border-0 shadow-sm rounded-3 p-3 p-md-4 bg-white">
              <h6 className="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                <FaClock className="text-primary" /> Chronological Transit History
              </h6>

              <div className="transit-log-card p-3">
                <div className="d-flex flex-column gap-3">
                  {order.history.map((entry, idx) => (
                    <div key={entry.id || idx} className="transit-log-item">
                      <div className="d-flex flex-wrap align-items-baseline justify-content-between gap-1 mb-1">
                        <strong className="text-dark small text-capitalize">
                          {entry.notes || entry.comment || `Order ${entry.status}`}
                        </strong>
                        <span className="text-muted" style={{ fontSize: "0.75rem" }}>
                          {formatDateTime(entry.created_at)}
                        </span>
                      </div>
                      <span className="badge bg-light text-secondary border text-uppercase" style={{ fontSize: "0.68rem" }}>
                        Status: {entry.status}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* Items, Shipping Address & Quick Lifecycle Actions Grid */}
          <div className="row g-3">
            
            {/* Ordered Products Card */}
            <div className="col-12 col-lg-7">
              <div className="card border-0 shadow-sm rounded-3 p-3 p-md-4 bg-white h-100">
                <div className="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <h6 className="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <FaBoxOpen className="text-primary" /> Products in this Shipment ({items.length})
                  </h6>
                  <span className="fw-bold text-dark fs-6">
                    ₹{Number(order.grand_total || order.total_amount || 0).toLocaleString("en-IN")}
                  </span>
                </div>

                <div className="d-flex flex-column gap-3">
                  {items.map((item, index) => {
                    const itemImg = item.product_image
                      ? resolveImageUrl(item.product_image)
                      : (item.image ? resolveImageUrl(item.image) : "/default-img.jpg");
                    const itemPrice = Number(item.unit_price ?? item.price ?? 0);
                    const itemQty = Number(item.quantity || 1);
                    const itemTotal = Number(item.total_price ?? (itemPrice * itemQty));

                    return (
                      <div key={item.id || index} className="d-flex align-items-center gap-3 py-1">
                        <img
                          src={itemImg}
                          alt={item.product_name || "Product"}
                          className="track-item-thumb flex-shrink-0"
                          onError={(e) => { e.target.src = "/default-img.jpg"; }}
                        />
                        <div className="flex-grow-1 min-width-0">
                          <h6 className="text-dark fw-semibold mb-1 fs-6 text-truncate">
                            {item.product_name || item.title || "Custom Print Product"}
                          </h6>
                          <div className="d-flex flex-wrap gap-2 text-muted small">
                            {item.variant && <span>Variant: <strong className="text-dark">{item.variant}</strong></span>}
                            <span>Qty: <strong className="text-dark">{itemQty}</strong></span>
                          </div>
                          <div className="fw-bold text-dark small mt-1">
                            ₹{itemTotal.toLocaleString("en-IN")}
                          </div>
                        </div>

                        {isDelivered && (
                          <div className="flex-shrink-0">
                            <button
                              type="button"
                              className="btn btn-outline-warning text-dark btn-sm rounded-pill py-1 px-3 d-inline-flex align-items-center gap-1"
                              style={{ fontSize: "0.78rem" }}
                              onClick={() => setActiveReviewItem(item)}
                            >
                              <FaStar className="text-warning" size={12} /> Rate
                            </button>
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>

            {/* Delivery Address & Actions */}
            <div className="col-12 col-lg-5">
              <div className="card border-0 shadow-sm rounded-3 p-3 p-md-4 bg-white h-100 d-flex flex-column justify-content-between">
                <div>
                  <h6 className="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                    <IoLocationOutline className="text-primary" size={18} /> Destination Address
                  </h6>
                  <div className="small text-muted mb-3">
                    <div className="fw-bold text-dark fs-6 mb-1">{order.customer_name || user?.name || "Recipient"}</div>
                    <div style={{ whiteSpace: "pre-line", lineHeight: "1.4" }}>
                      {order.shipping_address || "Address provided during checkout."}
                    </div>
                    {order.customer_phone && (
                      <div className="mt-2 text-dark">
                        Phone: <strong>{order.customer_phone}</strong>
                      </div>
                    )}
                  </div>
                </div>

                {/* Action Buttons */}
                <div className="pt-3 border-top d-flex flex-column gap-2">
                  <button
                    type="button"
                    className="btn btn-outline-primary btn-sm rounded-pill w-100 d-inline-flex align-items-center justify-content-center gap-2 fw-semibold py-2"
                    onClick={() => setShowInvoiceModal(true)}
                  >
                    <IoDocumentTextOutline size={16} /> Download Tax Invoice (PDF)
                  </button>

                  <div className="d-flex gap-2">
                    {isCancellable && (
                      <button
                        type="button"
                        className="btn btn-outline-danger btn-sm rounded-pill w-100 fw-semibold d-inline-flex align-items-center justify-content-center gap-1"
                        onClick={() => setShowCancelModal(true)}
                      >
                        <FaBan size={12} /> Cancel Order
                      </button>
                    )}

                    {isDelivered && (
                      <button
                        type="button"
                        className="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold d-inline-flex align-items-center justify-content-center gap-1"
                        onClick={() => setShowReturnModal(true)}
                      >
                        <FaSyncAlt size={12} /> Return / Replace
                      </button>
                    )}

                    <button
                      type="button"
                      className="btn btn-light btn-sm rounded-pill w-100 border text-dark fw-semibold d-inline-flex align-items-center justify-content-center gap-1"
                      onClick={() => setShowHelpModal(true)}
                    >
                      <FaHeadset size={12} className="text-primary" /> Need Help?
                    </button>
                  </div>
                </div>

              </div>
            </div>

          </div>

        </div>
      )}

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 6. Initial / Empty State Guidance Card                      */}
      {/* ─────────────────────────────────────────────────────────── */}
      {!order && !loading && !error && (
        <div className="track-guide-card p-3 p-md-4 rounded-3 border bg-white shadow-sm mb-4">
          <div className="d-flex align-items-center gap-3 mb-3">
            <div className="track-guide-icon-box">
              <FaInfoCircle size={22} className="text-primary" />
            </div>
            <div>
              <h6 className="fw-bold text-dark mb-0 fs-6">Where can I find my Order ID or AWB Tracking?</h6>
              <small className="text-muted">Quick tips to easily trace your personalized print shipment</small>
            </div>
          </div>

          <div className="row g-3 pt-2">
            <div className="col-12 col-md-4">
              <div className="track-step-box p-3 rounded-3 bg-light h-100 border">
                <span className="badge bg-primary rounded-pill mb-2">1. Check SMS &amp; Email</span>
                <h6 className="fw-bold text-dark mb-1 fs-6">Order Confirmation</h6>
                <p className="text-muted small mb-0">
                  We dispatch an instant confirmation SMS &amp; email containing your unique <strong>ORD-XXXX</strong> order ID immediately upon checkout.
                </p>
              </div>
            </div>

            <div className="col-12 col-md-4">
              <div className="track-step-box p-3 rounded-3 bg-light h-100 border">
                <span className="badge bg-primary rounded-pill mb-2">2. My Orders Section</span>
                <h6 className="fw-bold text-dark mb-1 fs-6">Logged-in Dashboard</h6>
                <p className="text-muted small mb-0">
                  All your past and live printing jobs are organized in the <strong>My Orders</strong> tab with 1-click tracking shortcuts.
                </p>
              </div>
            </div>

            <div className="col-12 col-md-4">
              <div className="track-step-box p-3 rounded-3 bg-light h-100 border">
                <span className="badge bg-primary rounded-pill mb-2">3. Courier Dispatch SMS</span>
                <h6 className="fw-bold text-dark mb-1 fs-6">AWB Consignment No.</h6>
                <p className="text-muted small mb-0">
                  Once quality check passes and the courier picks up your package, an AWB tracking code is sent with real-time GPS connectivity.
                </p>
              </div>
            </div>
          </div>

          <div className="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4 pt-3 border-top">
            <div className="d-flex align-items-center gap-2 text-muted small">
              <IoShieldCheckmarkOutline size={18} className="text-success" />
              <span>100% Secure &amp; Real-time GPS synchronization</span>
            </div>
            <div className="d-flex gap-2">
              {user ? (
                <Link to={`/${usernamePath}/orders`} className="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 fw-semibold">
                  Open My Orders
                </Link>
              ) : (
                <Link to="/login?redirect=/track-order" className="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 fw-semibold">
                  Sign In to Your Account
                </Link>
              )}
              <Link to="/help-center" className="btn btn-light btn-sm rounded-pill border px-3 py-1 fw-semibold text-dark">
                Help Center
              </Link>
            </div>
          </div>
        </div>
      )}

      {/* Tax Invoice Modal */}
      {showInvoiceModal && order && (
        <TaxInvoiceModal
          order={order}
          onClose={() => setShowInvoiceModal(false)}
        />
      )}

      {/* Help Modal */}
      {showHelpModal && order && (
        <OrderHelpModal
          order={order}
          onClose={() => setShowHelpModal(false)}
        />
      )}

      {/* Cancel Order Modal */}
      {showCancelModal && order && (
        <CancelOrderModal
          order={order}
          onClose={() => setShowCancelModal(false)}
          onSuccess={() => trackSpecificOrder(order.order_number || order.id)}
        />
      )}

      {/* Return / Replacement Modal */}
      {showReturnModal && order && (
        <ReturnReplacementModal
          order={order}
          onClose={() => setShowReturnModal(false)}
          onSuccess={() => trackSpecificOrder(order.order_number || order.id)}
        />
      )}

      {/* Rate & Review Modal */}
      {activeReviewItem && order && (
        <OrderReviewModal
          item={activeReviewItem}
          order={order}
          onClose={() => setActiveReviewItem(null)}
          onSuccess={() => trackSpecificOrder(order.order_number || order.id)}
        />
      )}
    </div>
  );

  // If already inside the User.jsx wrapper (e.g. /:username/track-order or /user/track-order),
  // return content directly so User.jsx provides the outer canvas and sidebar.
  if (isInsideUserWrapper) {
    return renderTrackOrderContent();
  }

  // Standalone /track-order route: Render full user dashboard canvas matching User.jsx (Rule 7 & 8)
  return (
    <div className="track-order-outer-container">
      <div className="user-dashboard-canvas">
        <div className="row g-0 g-lg-4">
          {/* Left Sidebar - Desktop only (>= 992px) */}
          <div className="col-12 col-lg-3 d-none d-lg-block">
            <AccountSidebar activeKey="track-order" />
          </div>

          {/* Right Side Content Pane */}
          <div className="col-12 col-lg-9">
            {renderTrackOrderContent()}
          </div>
        </div>
      </div>
    </div>
  );
};

export default TrackOrder;
