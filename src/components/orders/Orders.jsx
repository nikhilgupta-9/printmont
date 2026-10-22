import React, { useState, useEffect } from 'react';
import { 
  FaArrowLeft, 
  FaBoxOpen, 
  FaChevronRight, 
  FaCheckCircle, 
  FaClock, 
  FaTruck, 
  FaTimesCircle 
} from "react-icons/fa";
import { Spinner, Alert } from 'react-bootstrap';
import { useNavigate, useLocation, Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { API_ENDPOINTS, resolveImageUrl } from '../../config/apiEndpoints';
import OrderDetailModal from './OrderDetailModal';
import OrderReviewModal from './OrderReviewModal';
import CancelOrderModal from './CancelOrderModal';
import ReturnReplacementModal from './ReturnReplacementModal';
import OrdersBannerCarousel from './OrdersBannerCarousel';
import AccountSidebar from '../user/AccountSidebar';
import './OrdersWireframe.css';

/**
 * Format order delivery status matching the wireframe text:
 * - "Processing your order"
 * - "Delivered on 10 February 2026."
 */
const formatDeliveryStatus = (order) => {
  const status = (order.status || 'pending').toLowerCase();
  const dateStr = order.updated_at || order.created_at;
  let formattedDate = '';
  
  if (dateStr) {
    try {
      const d = new Date(dateStr);
      if (!isNaN(d.getTime())) {
        formattedDate = d.toLocaleDateString('en-GB', {
          day: 'numeric',
          month: 'long',
          year: 'numeric'
        }); // e.g. "10 February 2026"
      }
    } catch {
      formattedDate = dateStr;
    }
  }

  if (status === 'delivered') {
    return formattedDate ? `Delivered on ${formattedDate}.` : 'Delivered.';
  }
  if (status === 'cancelled') {
    return formattedDate ? `Cancelled on ${formattedDate}.` : 'Cancelled.';
  }
  if (status === 'shipped') {
    return 'Shipped • In Transit';
  }
  if (status === 'confirmed') {
    return 'Order Confirmed - Preparing';
  }
  return 'Processing your order';
};

const formatOrderDate = (dateStr) => {
  if (!dateStr) return 'Recent';
  try {
    const d = new Date(dateStr);
    if (!isNaN(d.getTime())) {
      return d.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
      });
    }
  } catch (e) {
    // fallback
  }
  return dateStr;
};

const Orders = () => {
  const { user, token, getUsernamePath } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const usernamePath = getUsernamePath ? getUsernamePath() : 'user';

  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [sessionExpired, setSessionExpired] = useState(false);
  const [statusFilter, setStatusFilter] = useState('all');

  // Modals for Order Detail, Cancel, Return & Review
  const [activeOrderDetail, setActiveOrderDetail] = useState(null);
  const [activeReviewItem, setActiveReviewItem] = useState(null);
  const [reviewContextOrder, setReviewContextOrder] = useState(null);
  const [activeCancelOrder, setActiveCancelOrder] = useState(null);
  const [activeReturnOrder, setActiveReturnOrder] = useState(null);

  // If logged in and browsing /orders directly, redirect to clean /:username/orders
  useEffect(() => {
    if (user && location.pathname === '/orders') {
      navigate(`/${usernamePath}/orders`, { replace: true });
    }
  }, [user, usernamePath, location.pathname, navigate]);

  const fetchOrders = async () => {
    if (!user?.id) {
      setLoading(false);
      return;
    }

    try {
      setLoading(true);
      setError(null);

      const params = {
        limit: 100,
      };

      const res = await fetch(API_ENDPOINTS.GET_CUSTOMER_ORDERS(params), {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await res.json();

      if (data.success || data.status === 'success') {
        const rows = data.data || data.orders || [];
        setOrders(rows);
      } else {
        if (res.status === 401) {
          setSessionExpired(true);
          setError("Your session has expired. Please sign in again to view your orders.");
        } else {
          setError(data.error || data.message || "Failed to load orders.");
        }
      }
    } catch (err) {
      console.error('Error fetching orders:', err);
      setError("An error occurred while fetching orders.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchOrders();
  }, [user, token]);

  const handleOpenReview = (item, order, isReviewed) => {
    const prodId = item?.product_id || item?.id;
    if (isReviewed) {
      navigate(`/${usernamePath}/reviews?product_id=${prodId}&tab=reviewed&open=edit`);
    } else {
      navigate(`/${usernamePath}/reviews?product_id=${prodId}&order_id=${order?.id}&open_review=1`);
    }
  };

  const handleReviewSuccess = () => {
    fetchOrders();
  };

  // Helper to extract flat item entries for display matching the wireframe
  // In Wireframe 1 & 2, each purchased product item is displayed with its own thumbnail & title
  const displayItems = [];
  orders.forEach((order) => {
    const items = Array.isArray(order.items) && order.items.length > 0 ? order.items : [];
    if (items.length > 0) {
      items.forEach((item, itemIdx) => {
        displayItems.push({
          key: `${order.id}-${item.id || itemIdx}`,
          order,
          item,
          title: item.product_name || order.title || order.product_name || `Order #${order.order_number || order.id}`,
          image: item.product_image 
            ? resolveImageUrl(item.product_image) 
            : (order.image || order.product_image ? resolveImageUrl(order.image || order.product_image) : '/default-img.jpg'),
          statusText: formatDeliveryStatus(order),
          statusClass: (order.status || 'pending').toLowerCase(),
          orderNumber: order.order_number || `#${order.id}`,
          orderDate: order.created_at,
          orderTotal: Number(order.grand_total ?? order.total_amount ?? 0),
          quantity: item.quantity || 1
        });
      });
    } else {
      displayItems.push({
        key: `ord-${order.id}`,
        order,
        item: null,
        title: order.title || order.product_name || `Order #${order.order_number || order.id}`,
        image: order.image || order.product_image ? resolveImageUrl(order.image || order.product_image) : '/default-img.jpg',
        statusText: formatDeliveryStatus(order),
        statusClass: (order.status || 'pending').toLowerCase(),
        orderNumber: order.order_number || `#${order.id}`,
        orderDate: order.created_at,
        orderTotal: Number(order.grand_total ?? order.total_amount ?? 0),
        quantity: order.total_quantity || 1
      });
    }
  });

  const allCount = displayItems.length;
  const deliveredCount = displayItems.filter(e => e.statusClass === 'delivered' || e.statusClass === 'completed').length;
  const inProgressCount = displayItems.filter(e => ['pending', 'processing', 'confirmed', 'shipped', 'transit'].includes(e.statusClass)).length;
  const cancelledCount = displayItems.filter(e => ['cancelled', 'refunded'].includes(e.statusClass)).length;

  const filteredDisplayItems = displayItems.filter(entry => {
    if (statusFilter === 'delivered') return entry.statusClass === 'delivered' || entry.statusClass === 'completed';
    if (statusFilter === 'inprogress') return ['pending', 'processing', 'confirmed', 'shipped', 'transit'].includes(entry.statusClass);
    if (statusFilter === 'cancelled') return ['cancelled', 'refunded'].includes(entry.statusClass);
    return true;
  });

  // Check if we are inside User.jsx wrapper
  const isInsideUserWrapper = location.pathname.startsWith(`/${usernamePath}`) || location.pathname.startsWith('/user');

  return (
    <>
      {/* ─────────────────────────────────────────────────────────── */}
      {/* 1. DESKTOP VIEW (Wireframe Image 1: >= 992px)               */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="d-none d-lg-block desktop-orders-container">
        {/* 1. Desktop Breadcrumbs (Rule 9) */}
        <div className="orders-breadcrumb-wrapper">
          <button
            type="button"
            className="orders-back-link"
            onClick={() => navigate(`/${usernamePath}/profile`)}
            aria-label="Back to Account"
          >
            <FaArrowLeft size={12} /> Back to My Account
          </button>
          <nav aria-label="breadcrumb">
            <ol className="orders-breadcrumb">
              <li><Link to="/">Home</Link></li>
              <span>/</span>
              <li><Link to={`/${usernamePath}/profile`}>My Account</Link></li>
              <span>/</span>
              <li className="active" aria-current="page">My Orders</li>
            </ol>
          </nav>
        </div>

        {/* 2. Desktop Orders Header Banner Card */}
        <div className="desktop-orders-header-card">
          <div className="desktop-orders-header-left">
            <div className="desktop-orders-header-icon-box">
              <FaBoxOpen />
            </div>
            <div className="desktop-orders-header-meta">
              <h4 className="desktop-orders-header-title">My Orders</h4>
              <p className="desktop-orders-header-sub">
                Manage, track, and reorder all your custom print purchases
              </p>
            </div>
          </div>
          <div className="desktop-orders-total-badge">
            <FaBoxOpen size={13} /> Total {orders.length} {orders.length === 1 ? 'Order' : 'Orders'}
          </div>
        </div>

        {/* 3. Status Filter Chips */}
        {orders.length > 0 && (
          <div className="desktop-orders-filter-chips">
            <button
              type="button"
              className={`desktop-orders-filter-chip ${statusFilter === 'all' ? 'active' : ''}`}
              onClick={() => setStatusFilter('all')}
            >
              <span>All Orders</span>
              <span className="chip-count">{allCount}</span>
            </button>
            <button
              type="button"
              className={`desktop-orders-filter-chip ${statusFilter === 'delivered' ? 'active' : ''}`}
              onClick={() => setStatusFilter('delivered')}
            >
              <FaCheckCircle size={12} className={statusFilter === 'delivered' ? 'text-white' : 'text-success'} />
              <span>Delivered</span>
              <span className="chip-count">{deliveredCount}</span>
            </button>
            <button
              type="button"
              className={`desktop-orders-filter-chip ${statusFilter === 'inprogress' ? 'active' : ''}`}
              onClick={() => setStatusFilter('inprogress')}
            >
              <FaClock size={12} className={statusFilter === 'inprogress' ? 'text-white' : 'text-warning'} />
              <span>In Progress</span>
              <span className="chip-count">{inProgressCount}</span>
            </button>
            <button
              type="button"
              className={`desktop-orders-filter-chip ${statusFilter === 'cancelled' ? 'active' : ''}`}
              onClick={() => setStatusFilter('cancelled')}
            >
              <FaTimesCircle size={12} className={statusFilter === 'cancelled' ? 'text-white' : 'text-danger'} />
              <span>Cancelled</span>
              <span className="chip-count">{cancelledCount}</span>
            </button>
          </div>
        )}

        {/* 4. Orders Card List */}
        <div className="desktop-orders-card">
          <div className="desktop-orders-list">
            {loading ? (
              <div className="text-center py-5">
                <Spinner animation="border" variant="primary" />
                <p className="mt-3 text-muted small mb-0">Loading your orders...</p>
              </div>
            ) : error ? (
              <Alert variant={sessionExpired ? "warning" : "danger"} className="m-3 rounded-3">
                <h6 className="fw-bold mb-1">{sessionExpired ? "Session Expired" : "Notice"}</h6>
                <div>{error}</div>
                {sessionExpired && (
                  <button
                    className="btn btn-sm btn-primary mt-3 rounded-pill px-4"
                    onClick={() => navigate("/login?redirect=/orders")}
                  >
                    Sign In to Continue
                  </button>
                )}
              </Alert>
            ) : !user?.id ? (
              <div className="text-center py-5">
                <FaBoxOpen size={48} className="text-primary mb-3" />
                <h5 className="fw-bold text-dark">Please Log In</h5>
                <p className="text-muted small mb-3">Sign in to track orders, manage purchases and invoices.</p>
                <button
                  className="btn btn-primary rounded-pill px-4 fw-bold"
                  onClick={() => navigate('/login?redirect=/orders')}
                >
                  Sign In
                </button>
              </div>
            ) : filteredDisplayItems.length > 0 ? (
              filteredDisplayItems.map((entry) => (
                <div
                  key={entry.key}
                  className="desktop-order-card-item"
                  onClick={() => {
                    const orderId = entry.order?.order_number || entry.order?.id;
                    if (orderId) {
                      navigate(`/${usernamePath}/orders/${orderId}`);
                    } else {
                      setActiveOrderDetail(entry.order);
                    }
                  }}
                  role="button"
                  tabIndex={0}
                  onKeyDown={(e) => { 
                    if (e.key === 'Enter') {
                      const orderId = entry.order?.order_number || entry.order?.id;
                      if (orderId) {
                        navigate(`/${usernamePath}/orders/${orderId}`);
                      } else {
                        setActiveOrderDetail(entry.order);
                      }
                    } 
                  }}
                >
                  <div className="desktop-order-card-left">
                    <div className="desktop-order-card-thumb-wrap">
                      <img
                        src={entry.image}
                        alt={entry.title}
                        className="desktop-order-card-thumb"
                        onError={(e) => { e.target.src = '/default-img.jpg'; }}
                      />
                    </div>
                    <div className="desktop-order-card-info">
                      <span className="desktop-order-card-product-title" title={entry.title}>
                        {entry.title}
                      </span>
                      <div className="desktop-order-card-meta-line">
                        <span>Order #{String(entry.orderNumber).replace(/^#+/, '')}</span>
                        <span>•</span>
                        <span>Placed on {formatOrderDate(entry.orderDate)}</span>
                        {entry.orderTotal > 0 && (
                          <>
                            <span>•</span>
                            <span className="desktop-order-card-price">₹{entry.orderTotal.toLocaleString('en-IN')}</span>
                          </>
                        )}
                      </div>
                      <span className={`desktop-order-status-badge ${entry.statusClass}`}>
                        {entry.statusClass === 'delivered' && <FaCheckCircle size={10} />}
                        {['pending', 'processing', 'confirmed'].includes(entry.statusClass) && <FaClock size={10} />}
                        {['shipped', 'transit'].includes(entry.statusClass) && <FaTruck size={10} />}
                        {['cancelled', 'refunded'].includes(entry.statusClass) && <FaTimesCircle size={10} />}
                        {entry.statusText}
                      </span>
                    </div>
                  </div>
                  <div className="desktop-order-card-right">
                    <button type="button" className="desktop-order-view-btn" tabIndex={-1}>
                      View Details <FaChevronRight size={11} />
                    </button>
                  </div>
                </div>
              ))
            ) : (
              <div className="text-center py-5">
                <div className="text-muted mb-3">
                  <FaBoxOpen size={42} />
                </div>
                <h6 className="fw-bold text-dark">
                  {statusFilter === 'all' ? 'No Orders Placed Yet' : `No ${statusFilter} orders found`}
                </h6>
                <p className="text-muted small mb-3">
                  {statusFilter === 'all'
                    ? "You haven't placed any orders yet. Explore our products to start!"
                    : `You don't have any orders under "${statusFilter}".`}
                </p>
                {statusFilter !== 'all' ? (
                  <button 
                    type="button" 
                    className="btn btn-outline-primary rounded-pill px-4 fw-bold"
                    onClick={() => setStatusFilter('all')}
                  >
                    View All Orders
                  </button>
                ) : (
                  <Link to="/allproducts" className="btn btn-primary rounded-pill px-4 fw-bold">
                    Start Shopping
                  </Link>
                )}
              </div>
            )}
          </div>
        </div>

        {/* If rendered outside User.jsx wrapper (e.g. standalone /orders), render full-width bottom banner here */}
        {!isInsideUserWrapper && (
          <div className="mt-4">
            <OrdersBannerCarousel isMobile={false} />
          </div>
        )}
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 2. MOBILE VIEW (Wireframe Image 2: < 992px)                 */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="d-lg-none mobile-orders-page">
        {/* Mobile Orders List with Dividers (Wireframe 2) */}
        <div className="mobile-orders-list">
          {loading ? (
            <div className="text-center py-5">
              <Spinner animation="border" variant="primary" />
              <p className="mt-3 text-muted small mb-0">Loading your orders...</p>
            </div>
          ) : error ? (
            <Alert variant={sessionExpired ? "warning" : "danger"} className="m-3 rounded-3">
              <h6 className="fw-bold mb-1">{sessionExpired ? "Session Expired" : "Notice"}</h6>
              <div>{error}</div>
              {sessionExpired && (
                <button
                  className="btn btn-sm btn-primary mt-3 rounded-pill px-4"
                  onClick={() => navigate("/login?redirect=/orders")}
                >
                  Sign In to Continue
                </button>
              )}
            </Alert>
          ) : !user?.id ? (
            <div className="text-center py-5 px-3">
              <FaBoxOpen size={44} className="text-primary mb-3" />
              <h5 className="fw-bold text-dark">Please Log In</h5>
              <p className="text-muted small mb-3">Sign in to track your orders and view past purchases.</p>
              <button
                className="btn btn-primary rounded-pill px-4 fw-bold"
                onClick={() => navigate('/login?redirect=/orders')}
              >
                Sign In
              </button>
            </div>
          ) : displayItems.length > 0 ? (
            displayItems.map((entry) => (
              <div
                key={entry.key}
                className="mobile-order-row-item"
                onClick={() => {
                  const orderId = entry.order?.order_number || entry.order?.id;
                  if (orderId) {
                    navigate(`/${usernamePath}/orders/${orderId}`);
                  } else {
                    setActiveOrderDetail(entry.order);
                  }
                }}
                role="button"
                tabIndex={0}
              >
                <div className="mobile-order-row-left">
                  <div className="mobile-order-row-thumb-wrap">
                    <img
                      src={entry.image}
                      alt={entry.title}
                      className="mobile-order-row-thumb"
                      onError={(e) => { e.target.src = '/default-img.jpg'; }}
                    />
                  </div>
                  <div className="mobile-order-row-info">
                    <span className="mobile-order-row-product-title">
                      {entry.title}
                    </span>
                    <span className={`mobile-order-row-status ${entry.statusClass}`}>
                      {entry.statusText}
                    </span>
                  </div>
                </div>
                <FaChevronRight className="mobile-order-row-chevron" />
              </div>
            ))
          ) : (
            <div className="text-center py-5 px-3">
              <FaBoxOpen size={40} className="text-muted mb-3" />
              <h6 className="fw-bold text-dark">No Orders Found</h6>
              <p className="text-muted small mb-3">
                You haven't placed any orders yet. Explore our products to start!
              </p>
              <Link to="/allproducts" className="btn btn-primary rounded-pill px-4 fw-bold">
                Start Shopping
              </Link>
            </div>
          )}
        </div>

        {/* Mobile Banner Carousel (Banner management support for orders page on mobile) */}
        <div className="mt-2 pb-4">
          <OrdersBannerCarousel isMobile={true} />
        </div>
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 3. MODALS (Detail, Cancel, Return, Review)                  */}
      {/* ─────────────────────────────────────────────────────────── */}
      {activeOrderDetail && (
        <OrderDetailModal
          order={activeOrderDetail}
          onClose={() => setActiveOrderDetail(null)}
          onOpenReview={(item, ord) => handleOpenReview(item, ord)}
          onOrderUpdated={() => fetchOrders()}
        />
      )}

      {activeCancelOrder && (
        <CancelOrderModal
          order={activeCancelOrder}
          onClose={() => setActiveCancelOrder(null)}
          onSuccess={() => fetchOrders()}
        />
      )}

      {activeReturnOrder && (
        <ReturnReplacementModal
          order={activeReturnOrder}
          onClose={() => setActiveReturnOrder(null)}
          onSuccess={() => fetchOrders()}
        />
      )}

      {activeReviewItem && (
        <OrderReviewModal
          item={activeReviewItem}
          order={reviewContextOrder}
          onClose={() => { setActiveReviewItem(null); setReviewContextOrder(null); }}
          onSuccess={handleReviewSuccess}
        />
      )}
    </>
  );
};

export default Orders;
