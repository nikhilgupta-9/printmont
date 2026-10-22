import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { 
  IoArrowBack, 
  IoDownloadOutline, 
  IoLocationOutline, 
  IoDocumentTextOutline, 
  IoHeadsetOutline, 
  IoCheckmarkCircle,
  IoAlertCircleOutline
} from "react-icons/io5";
import { FaTruck, FaStar, FaBoxOpen, FaCheckCircle, FaChevronRight, FaPrint, FaBan, FaSyncAlt, FaUndo } from "react-icons/fa";
import { Spinner, Alert } from 'react-bootstrap';
import { useAuth } from '../../context/AuthContext';
import { API_ENDPOINTS, resolveImageUrl } from '../../config/apiEndpoints';
import TaxInvoiceModal from './TaxInvoiceModal';
import OrderHelpModal from './OrderHelpModal';
import OrderReviewModal from './OrderReviewModal';
import CancelOrderModal from './CancelOrderModal';
import ReturnReplacementModal from './ReturnReplacementModal';
import AfterDeliveryOrderCard from './AfterDeliveryOrderCard';
import DesktopOrderDetailView from './DesktopOrderDetailView';
import './OrdersWireframe.css';

const STATUS_COLOR = {
  delivered: '#16a34a',
  shipped: '#0b53a1',
  processing: '#f59e0b',
  confirmed: '#0b53a1',
  pending: '#f59e0b',
  cancelled: '#dc2626',
  refunded: '#64748b',
  return_requested: '#d97706',
  returned: '#64748b',
  replacement_requested: '#2563eb'
};

const formatDate = (dateStr) => {
  if (!dateStr) return 'Recent';
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-IN', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    });
  } catch {
    return dateStr;
  }
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '';
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    return d.toLocaleString('en-IN', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch {
    return '';
  }
};

const OrderDetailsPage = () => {
  const { id: routeId, orderId } = useParams();
  const orderIdentifier = routeId || orderId;

  const { user, token, getUsernamePath } = useAuth();
  const navigate = useNavigate();
  const usernamePath = getUsernamePath ? getUsernamePath() : 'user';

  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Modals state
  const [showInvoiceModal, setShowInvoiceModal] = useState(false);
  const [showHelpModal, setShowHelpModal] = useState(false);
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [showReturnModal, setShowReturnModal] = useState(false);
  const [activeReviewItem, setActiveReviewItem] = useState(null);
  const [showDetailsSection, setShowDetailsSection] = useState(true);

  const handleReviewAction = (item, ord, isReviewed) => {
    const prodId = item?.product_id || item?.id;
    if (isReviewed) {
      navigate(`/${usernamePath}/reviews?product_id=${prodId}&tab=reviewed&open=edit`);
    } else {
      navigate(`/${usernamePath}/reviews?product_id=${prodId}&order_id=${ord?.id || order?.id}&open_review=1`);
    }
  };

  const fetchOrderDetails = async () => {
    if (!orderIdentifier) return;

    try {
      setLoading(true);
      setError(null);

      const endpoint = API_ENDPOINTS.GET_ORDER(orderIdentifier);
      const res = await fetch(endpoint, {
        headers: {
          ...(token ? { 'Authorization': `Bearer ${token}` } : {})
        }
      });
      const data = await res.json();

      if (data.success && data.data) {
        setOrder(data.data);
      } else {
        // Fallback: search in customer orders list if single fetch is restricted
        const listRes = await fetch(API_ENDPOINTS.GET_CUSTOMER_ORDERS({ search: orderIdentifier }), {
          headers: { ...(token ? { 'Authorization': `Bearer ${token}` } : {}) }
        });
        const listData = await listRes.json();
        const found = (listData.data || listData.orders || []).find(
          o => String(o.id) === String(orderIdentifier) || o.order_number === orderIdentifier || o.tracking_number === orderIdentifier
        );
        if (found) {
          setOrder(found);
        } else {
          setError(data.error || 'Unable to locate this order.');
        }
      }
    } catch (err) {
      console.error('Error fetching order detail:', err);
      setError('An error occurred while loading the order details.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchOrderDetails();
  }, [orderIdentifier, token]);

  if (loading) {
    return (
      <div className="orders-page-wrapper min-vh-100 py-5">
        <div className="container-lg text-center p-5 bg-white rounded-3 shadow-xs">
          <Spinner animation="border" variant="primary" />
          <h6 className="mt-3 text-muted">Loading order details...</h6>
        </div>
      </div>
    );
  }

  if (error || !order) {
    return (
      <div className="orders-page-wrapper min-vh-100 py-5">
        <div className="container-lg">
          <Alert variant="danger" className="rounded-3 shadow-xs p-4">
            <h5 className="fw-bold mb-2">Order Not Found</h5>
            <p className="mb-3">{error || 'The requested order could not be retrieved.'}</p>
            <div className="d-flex gap-2">
              <button className="btn btn-outline-danger btn-sm rounded-pill px-3" onClick={() => navigate(-1)}>
                ← Go Back
              </button>
              <Link to={`/${usernamePath}/orders`} className="btn btn-danger btn-sm rounded-pill px-3">
                View My Orders
              </Link>
            </div>
          </Alert>
        </div>
      </div>
    );
  }

  const items = Array.isArray(order.items) && order.items.length > 0 
    ? order.items 
    : [{
        product_name: order.title || order.product_name || `Order ${order.order_number || `#${order.id}`}`,
        product_image: order.image || order.product_image || '/default-img.jpg',
        quantity: order.total_quantity || 1,
        unit_price: order.grand_total || order.total_amount || 0,
        total_price: order.grand_total || order.total_amount || 0,
      }];

  const status = (order.status || 'pending').toLowerCase();
  const tone = STATUS_COLOR[status] || '#f59e0b';
  const orderNumber = order.order_number || `#${order.id}`;
  const cleanOrderNumber = String(orderNumber).replace(/^#+/, '');
  const price = Number(order.grand_total ?? order.total_amount ?? 0);
  const subtotal = Number(order.subtotal ?? order.total_amount ?? price);
  const shippingCost = Number(order.shipping_cost ?? 0);
  const discountAmount = Number(order.discount_amount ?? 0);
  const taxAmount = Number(order.tax_amount ?? Math.round((price - (price / 1.18)) * 100) / 100);

  const isCancelled = status === 'cancelled';
  const isDelivered = status === 'delivered';
  const isShipped = status === 'shipped' || isDelivered;
  const isConfirmed = status === 'confirmed' || isShipped;
  const isReturnRequested = status === 'return_requested';
  const isReplacementRequested = status === 'replacement_requested';
  const isCancellable = ['pending', 'confirmed', 'processing'].includes(status);

  // Return policy window (15 days)
  const orderTime = order.updated_at ? new Date(order.updated_at).getTime() : new Date(order.created_at).getTime();
  const isWithinReturnWindow = isDelivered && (Date.now() - orderTime <= 15 * 24 * 60 * 60 * 1000);

  // Realtime 5-Stage Stepper
  const timelineSteps = [
    { 
      key: 'placed',
      title: 'Order Placed', 
      desc: 'We have received your order', 
      done: true, 
      current: status === 'pending', 
      date: formatDateTime(order.created_at) 
    },
    { 
      key: 'confirmed',
      title: 'Order Confirmed', 
      desc: 'Print file verified & approved', 
      done: isConfirmed && !isCancelled, 
      current: status === 'confirmed', 
      date: isConfirmed ? formatDateTime(order.created_at) : '' 
    },
    { 
      key: 'packed',
      title: 'Packed & Quality Checked', 
      desc: 'Printed, inspected & packed', 
      done: isShipped && !isCancelled, 
      current: status === 'processing', 
      date: isShipped ? formatDateTime(order.updated_at || order.created_at) : '' 
    },
    { 
      key: 'shipped',
      title: 'Shipped', 
      desc: order.courier_name ? `Dispatched via ${order.courier_name}` : 'Dispatched from manufacturing hub', 
      done: isShipped && !isCancelled, 
      current: status === 'shipped', 
      date: isShipped ? formatDateTime(order.updated_at || order.created_at) : '',
      tracking: order.tracking_number
    },
    { 
      key: 'delivered',
      title: 'Delivered', 
      desc: 'Handed over to customer', 
      done: isDelivered && !isCancelled, 
      current: isDelivered, 
      date: isDelivered ? formatDateTime(order.updated_at || order.created_at) : '' 
    },
  ];

  return (
    <div className="orders-page-wrapper min-vh-100 py-3 py-md-4">
      {/* ─────────────────────────────────────────────────────────── */}
      {/* 1. DESKTOP VIEW (Rule 4: >= 992px) - Wireframes 1, 2, 3, 4  */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="d-none d-lg-block desktop-orders-detail-container">
        {/* Navigation Breadcrumb / Back button (Rule 9) */}
        <div className="orders-breadcrumb-wrapper">
          <button
            type="button"
            className="orders-back-link"
            onClick={() => navigate(`/${usernamePath}/orders`)}
            aria-label="Back to My orders"
          >
            <IoArrowBack size={14} /> Back to My Orders
          </button>

          <nav aria-label="breadcrumb">
            <ol className="orders-breadcrumb">
              <li><Link to="/">Home</Link></li>
              <span>/</span>
              <li><Link to={`/${usernamePath}/profile`}>My Account</Link></li>
              <span>/</span>
              <li><Link to={`/${usernamePath}/orders`}>My Orders</Link></li>
              <span>/</span>
              <li className="active" aria-current="page">#{cleanOrderNumber}</li>
            </ol>
          </nav>
        </div>

        {/* Desktop Order Detail View (Images 1, 2, 3, 4) */}
        <DesktopOrderDetailView
          order={order}
          onOpenReview={handleReviewAction}
          onOpenInvoice={() => setShowInvoiceModal(true)}
          onOpenCancel={() => setShowCancelModal(true)}
          onTrackOrder={() => navigate(`/track-order?order=${encodeURIComponent(cleanOrderNumber)}`)}
        />
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 2. MOBILE VIEW (Rule 4: < 992px)                            */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="d-lg-none mobile-orders-detail-container">
        <div className="container-xl px-2 px-md-3">
        
        {/* Breadcrumbs */}
        <nav aria-label="breadcrumb" className="d-none d-md-block mb-3">
          <ol className="breadcrumb small mb-0">
            <li className="breadcrumb-item"><Link to="/" className="text-decoration-none text-muted">Home</Link></li>
            <li className="breadcrumb-item"><Link to={`/${usernamePath}/profile`} className="text-decoration-none text-muted">My Account</Link></li>
            <li className="breadcrumb-item"><Link to={`/${usernamePath}/orders`} className="text-decoration-none text-muted">My Orders</Link></li>
            <li className="breadcrumb-item active text-primary fw-semibold" aria-current="page">{orderNumber}</li>
          </ol>
        </nav>

        {/* Top Header Card */}
        <div className="bg-white rounded-3 border p-3 p-md-4 mb-3 shadow-xs">
          <div className="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div className="d-flex align-items-center gap-3">
              <button 
                type="button" 
                className="btn btn-light rounded-circle p-2 d-flex align-items-center justify-content-center"
                style={{ width: 38, height: 38 }}
                onClick={() => navigate(`/${usernamePath}/orders`)}
                aria-label="Back to orders"
              >
                <IoArrowBack size={18} />
              </button>
              <div>
                <div className="d-flex align-items-center gap-2">
                  <h5 className="mb-0 fw-bold text-dark fs-5">Order {orderNumber}</h5>
                  <span className="badge rounded-pill text-uppercase px-2 py-1" style={{ backgroundColor: `${tone}15`, color: tone, border: `1px solid ${tone}40` }}>
                    ● {status.replace('_', ' ')}
                  </span>
                </div>
                <small className="text-muted">Placed on {formatDateTime(order.created_at)}</small>
              </div>
            </div>

            {/* Header Action Buttons */}
            <div className="d-flex flex-wrap align-items-center gap-2">
              {isCancellable && (
                <button
                  type="button"
                  className="btn btn-outline-danger btn-sm rounded-pill d-flex align-items-center gap-1 px-3 py-2 fw-semibold"
                  onClick={() => setShowCancelModal(true)}
                >
                  <FaBan size={13} /> Cancel Order
                </button>
              )}

              {isWithinReturnWindow && (
                <button
                  type="button"
                  className="btn btn-outline-primary btn-sm rounded-pill d-flex align-items-center gap-1 px-3 py-2 fw-semibold"
                  onClick={() => setShowReturnModal(true)}
                >
                  <FaSyncAlt size={13} /> Return / Replace
                </button>
              )}

              <button 
                type="button" 
                className="btn btn-outline-primary btn-sm rounded-pill d-flex align-items-center gap-1 px-3 py-2 fw-semibold"
                onClick={() => setShowInvoiceModal(true)}
              >
                <IoDownloadOutline size={16} /> Tax Invoice
              </button>

              <button 
                type="button" 
                className="btn btn-outline-secondary btn-sm rounded-pill d-flex align-items-center gap-1 px-3 py-2"
                onClick={() => setShowHelpModal(true)}
              >
                <IoHeadsetOutline size={16} /> Need Help?
              </button>
            </div>
          </div>
        </div>

        {/* Wireframe View 1: After Delivery Product Card */}
        {isDelivered && (
          <div className="mb-4">
            <AfterDeliveryOrderCard
              order={order}
              onOpenReview={handleReviewAction}
              onOpenInvoice={() => setShowInvoiceModal(true)}
              onViewDetails={() => setShowDetailsSection((prev) => !prev)}
            />
          </div>
        )}

        {/* Main Grid */}
        <div className="row g-3 g-lg-4">
          
          {/* Left Column (Timeline & Ordered Items) */}
          <div className="col-12 col-lg-8">
            
            {/* Realtime Stepper Timeline Card */}
            <div className="card border rounded-3 bg-white p-3 p-md-4 mb-3 shadow-xs">
              <div className="d-flex align-items-center justify-content-between mb-3">
                <h6 className="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                  <FaTruck className="text-primary" /> Delivery Status & Tracking
                </h6>
                {order.tracking_number && (
                  <Link 
                    to={`/track-order?order=${encodeURIComponent(order.order_number || order.tracking_number)}`}
                    className="small text-decoration-none text-primary fw-semibold"
                  >
                    Live GPS Tracker →
                  </Link>
                )}
              </div>

              {isCancelled ? (
                <div className="alert alert-danger rounded-3 d-flex align-items-start gap-2 mb-0">
                  <IoAlertCircleOutline size={24} className="flex-shrink-0 mt-1" />
                  <div>
                    <strong className="d-block">This order was cancelled</strong>
                    <span className="small text-muted">
                      As per cancellation policy, any payment processed has been refunded to your original payment mode or Printmont coins wallet.
                    </span>
                  </div>
                </div>
              ) : isReturnRequested || isReplacementRequested ? (
                <div className="alert alert-info rounded-3 d-flex align-items-start gap-2 mb-0">
                  <FaSyncAlt size={22} className="flex-shrink-0 text-primary mt-1" />
                  <div>
                    <strong className="d-block text-dark">
                      {isReplacementRequested ? 'Replacement / Exchange Request in Progress' : 'Return & Refund Request Submitted'}
                    </strong>
                    <span className="small text-muted">
                      Our customer experience team has received your request and will verify the quality report within 24–48 hours. Pickup and replacement dispatch details will be sent via SMS/Email.
                    </span>
                  </div>
                </div>
              ) : (
                <div className="order-timeline-stepper pt-2">
                  {timelineSteps.map((step, idx) => (
                    <div 
                      key={step.key || idx} 
                      className={`timeline-step ${step.done ? 'step-done' : ''} ${step.current ? 'step-current' : ''}`}
                    >
                      <div className="timeline-node">
                        {step.done ? <FaCheckCircle size={14} /> : <span className="node-dot"></span>}
                      </div>
                      <div className="timeline-content flex-grow-1">
                        <div className="d-flex flex-wrap align-items-center justify-content-between gap-1">
                          <p className="timeline-title mb-0 fw-semibold">{step.title}</p>
                          {step.date && <span className="timeline-date text-muted small">{step.date}</span>}
                        </div>
                        <span className="timeline-desc text-muted">{step.desc}</span>
                        
                        {step.tracking && (
                          <div className="mt-1 small">
                            <span className="text-muted">Tracking AWB: </span>
                            <strong className="text-dark">{step.tracking}</strong>
                            {order.courier_name && <span className="badge bg-light text-dark border ms-2">{order.courier_name}</span>}
                          </div>
                        )}
                      </div>
                      {idx < timelineSteps.length - 1 && <div className="timeline-line"></div>}
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Ordered Items List (Only for active/pending orders since delivered orders use AfterDeliveryOrderCard) */}
            {!isDelivered && (
              <div className="card border rounded-3 bg-white p-3 p-md-4 mb-3 shadow-xs">
                <h6 className="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                  <FaBoxOpen className="text-primary" /> Items Ordered ({items.length})
                </h6>

                <div className="order-items-feed">
                  {items.map((item, index) => {
                    const itemImg = item.product_image 
                      ? resolveImageUrl(item.product_image) 
                      : (item.image ? resolveImageUrl(item.image) : '/default-img.jpg');
                    const itemPrice = Number(item.unit_price ?? item.price ?? 0);
                    const itemQty = Number(item.quantity || 1);
                    const itemTotal = Number(item.total_price ?? (itemPrice * itemQty));

                    return (
                      <div 
                        key={item.id || index} 
                        className={`order-item-block py-3 d-flex flex-column flex-sm-row gap-3 ${index < items.length - 1 ? 'border-bottom' : ''}`}
                      >
                        <img 
                          src={itemImg} 
                          alt={item.product_name || 'Product'} 
                          className="rounded border object-fit-contain bg-light flex-shrink-0"
                          style={{ width: 85, height: 85 }}
                          onError={(e) => { e.target.src = '/default-img.jpg'; }}
                        />

                        <div className="flex-grow-1">
                          <h6 className="fw-semibold text-dark mb-1 fs-6">{item.product_name}</h6>
                          <div className="d-flex flex-wrap gap-3 text-muted small mb-2">
                            {item.variant && <span>Variant: <strong className="text-dark">{item.variant}</strong></span>}
                            <span>Quantity: <strong className="text-dark">{itemQty}</strong></span>
                            {item.sku && <span>SKU: {item.sku}</span>}
                          </div>
                          <div className="d-flex align-items-baseline gap-2">
                            <span className="fw-bold text-dark fs-5">₹{itemTotal.toLocaleString('en-IN')}</span>
                            {itemQty > 1 && (
                              <span className="text-muted small">(₹{itemPrice.toLocaleString('en-IN')} each)</span>
                            )}
                          </div>
                        </div>

                        {/* Action (Rate & Review) */}
                        {isDelivered && (
                          <div className="flex-shrink-0 align-self-sm-center mt-2 mt-sm-0">
                            <button
                              type="button"
                              className="btn btn-outline-primary btn-sm rounded-pill d-flex align-items-center gap-1 px-3 py-1"
                              onClick={() => setActiveReviewItem(item)}
                            >
                              <FaStar className="text-warning" size={13} /> Rate & Review
                            </button>
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
              </div>
            )}

          </div>

          {/* Right Column (Shipping Address, Payment & Price Breakdown) */}
          <div className="col-12 col-lg-4">
            
            {/* Delivery Address Card */}
            <div className="card border rounded-3 bg-white p-3 p-md-4 mb-3 shadow-xs">
              <h6 className="fw-bold mb-2 text-dark d-flex align-items-center gap-2">
                <IoLocationOutline className="text-primary" size={18} /> Delivery Address
              </h6>
              <div className="small text-muted mt-2">
                <div className="fw-bold text-dark fs-6 mb-1">{order.customer_name || user?.name || 'Customer'}</div>
                <div style={{ whiteSpace: 'pre-line', lineHeight: '1.5' }}>
                  {order.shipping_address || 'Address provided at checkout'}
                </div>
                {order.customer_phone && (
                  <div className="mt-2 text-dark">
                    Phone Number: <strong>{order.customer_phone}</strong>
                  </div>
                )}
                {order.customer_email && (
                  <div className="text-muted">
                    Email: {order.customer_email}
                  </div>
                )}
              </div>
            </div>

            {/* Price Details & Tax Breakdown Card */}
            <div className="card border rounded-3 bg-white p-3 p-md-4 mb-3 shadow-xs">
              <h6 className="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                <IoDocumentTextOutline className="text-primary" size={18} /> Price Breakdown
              </h6>

              <div className="price-breakdown-list small">
                <div className="d-flex justify-content-between mb-2">
                  <span className="text-muted">List Price ({items.length} items)</span>
                  <span className="fw-semibold">₹{(subtotal + discountAmount).toLocaleString('en-IN')}</span>
                </div>

                {discountAmount > 0 && (
                  <div className="d-flex justify-content-between mb-2 text-success">
                    <span>Discount / Promo</span>
                    <span className="fw-semibold">-₹{discountAmount.toLocaleString('en-IN')}</span>
                  </div>
                )}

                <div className="d-flex justify-content-between mb-2">
                  <span className="text-muted">Delivery Charges</span>
                  <span className={shippingCost === 0 ? "text-success fw-bold" : "fw-semibold"}>
                    {shippingCost === 0 ? "FREE" : `₹${shippingCost.toLocaleString('en-IN')}`}
                  </span>
                </div>

                <div className="d-flex justify-content-between mb-2">
                  <span className="text-muted">Taxes & GST (18% inclusive)</span>
                  <span className="fw-semibold">₹{taxAmount.toLocaleString('en-IN')}</span>
                </div>

                <hr className="my-2" />

                <div className="d-flex justify-content-between fs-5 fw-bold text-dark pt-1">
                  <span>Total Amount</span>
                  <span className="text-primary">₹{price.toLocaleString('en-IN')}</span>
                </div>

                <div className="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                  <span className="text-muted">Payment Method</span>
                  <span className="badge bg-secondary-subtle text-secondary border text-uppercase px-2 py-1">
                    {order.payment_method || 'Online Payment'}
                  </span>
                </div>

                <div className="mt-2 d-flex justify-content-between align-items-center">
                  <span className="text-muted">Payment Status</span>
                  <span className={`badge ${order.payment_status === 'paid' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'} border text-uppercase px-2 py-1`}>
                    {order.payment_status || 'Paid'}
                  </span>
                </div>
              </div>

              <div className="mt-3 pt-3 border-top text-center">
                <button 
                  type="button" 
                  className="btn btn-light w-100 btn-sm rounded-pill border d-flex align-items-center justify-content-center gap-1 text-dark"
                  onClick={() => setShowInvoiceModal(true)}
                >
                  <IoDownloadOutline size={15} /> Download Invoice (PDF)
                </button>
              </div>
            </div>

            {/* Need Help Card */}
            <div className="card border rounded-3 bg-white p-3 shadow-xs">
              <div className="d-flex align-items-center gap-2 mb-2">
                <IoHeadsetOutline className="text-primary" size={20} />
                <h6 className="mb-0 fw-bold text-dark">Need Help with Order?</h6>
              </div>
              <p className="text-muted small mb-3">
                Have questions regarding your printing delivery, invoice, cancellation, or returns? Our team is available 24x7.
              </p>
              <button 
                type="button" 
                className="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold"
                onClick={() => setShowHelpModal(true)}
              >
                Contact Customer Support
              </button>
            </div>

          </div>

        </div>

      </div>
      </div>

      {/* Tax Invoice Modal */}
      {showInvoiceModal && (
        <TaxInvoiceModal
          order={order}
          onClose={() => setShowInvoiceModal(false)}
        />
      )}

      {/* Order Help Modal */}
      {showHelpModal && (
        <OrderHelpModal
          order={order}
          onClose={() => setShowHelpModal(false)}
        />
      )}

      {/* Cancel Order Modal */}
      {showCancelModal && (
        <CancelOrderModal
          order={order}
          onClose={() => setShowCancelModal(false)}
          onSuccess={fetchOrderDetails}
        />
      )}

      {/* Return / Replacement Modal */}
      {showReturnModal && (
        <ReturnReplacementModal
          order={order}
          onClose={() => setShowReturnModal(false)}
          onSuccess={fetchOrderDetails}
        />
      )}

      {/* Rate & Review Modal */}
      {activeReviewItem && (
        <OrderReviewModal
          item={activeReviewItem}
          order={order}
          onClose={() => setActiveReviewItem(null)}
          onSuccess={fetchOrderDetails}
        />
      )}

    </div>
  );
};

export default OrderDetailsPage;
