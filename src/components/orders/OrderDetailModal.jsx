import React, { useState } from 'react';
import { IoClose, IoLocationOutline, IoDocumentTextOutline, IoHelpCircleOutline, IoDownloadOutline, IoHeadsetOutline, IoOpenOutline } from "react-icons/io5";
import { FaTruck, FaStar, FaBoxOpen, FaCheckCircle, FaChevronRight, FaBan, FaSyncAlt } from "react-icons/fa";
import { Link } from 'react-router-dom';
import { resolveImageUrl } from '../../config/apiEndpoints';
import TaxInvoiceModal from './TaxInvoiceModal';
import OrderHelpModal from './OrderHelpModal';
import CancelOrderModal from './CancelOrderModal';
import ReturnReplacementModal from './ReturnReplacementModal';
import AfterDeliveryOrderCard from './AfterDeliveryOrderCard';

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

const formatTime = (dateStr) => {
  if (!dateStr) return '';
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    return d.toLocaleTimeString('en-IN', {
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch {
    return '';
  }
};

const OrderDetailModal = ({ order, onClose, onOpenReview, onOrderUpdated }) => {
  const [showInvoiceModal, setShowInvoiceModal] = useState(false);
  const [showHelpModal, setShowHelpModal] = useState(false);
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [showReturnModal, setShowReturnModal] = useState(false);
  const [currentOrder, setCurrentOrder] = useState(order);

  if (!currentOrder) return null;

  const items = Array.isArray(currentOrder.items) && currentOrder.items.length > 0
    ? currentOrder.items
    : [{
        product_name: currentOrder.title || currentOrder.product_name || `Order ${currentOrder.order_number || `#${currentOrder.id}`}`,
        product_image: currentOrder.image || currentOrder.product_image || '/default-img.jpg',
        quantity: currentOrder.total_quantity || 1,
        unit_price: currentOrder.grand_total || currentOrder.total_amount || currentOrder.price || 0,
        total_price: currentOrder.grand_total || currentOrder.total_amount || currentOrder.price || 0,
      }];

  const status = (currentOrder.status || 'pending').toLowerCase();
  const tone = STATUS_COLOR[status] || '#f59e0b';
  const orderNumber = currentOrder.order_number || `#${currentOrder.id}`;
  const price = Number(currentOrder.grand_total ?? currentOrder.total_amount ?? currentOrder.price ?? 0);
  const subtotal = Number(currentOrder.subtotal ?? currentOrder.total_amount ?? price);
  const shippingCost = Number(currentOrder.shipping_cost ?? 0);
  const discountAmount = Number(currentOrder.discount_amount ?? 0);
  const taxAmount = Number(currentOrder.tax_amount ?? 0);

  // Determine timeline steps
  const isCancelled = status === 'cancelled';
  const isDelivered = status === 'delivered';
  const isShipped = status === 'shipped' || isDelivered;
  const isConfirmed = status === 'confirmed' || isShipped;
  const isReturnRequested = status === 'return_requested';
  const isReplacementRequested = status === 'replacement_requested';
  const isCancellable = ['pending', 'confirmed', 'processing'].includes(status);

  // 15 days return window
  const orderTime = currentOrder.updated_at ? new Date(currentOrder.updated_at).getTime() : new Date(currentOrder.created_at).getTime();
  const isWithinReturnWindow = isDelivered && (Date.now() - orderTime <= 15 * 24 * 60 * 60 * 1000);

  const timelineSteps = [
    { title: 'Order Placed', desc: 'Order received', done: true, current: status === 'pending', date: formatDate(currentOrder.created_at) },
    { title: 'Order Confirmed', desc: 'Printmont verified', done: isConfirmed && !isCancelled, current: status === 'confirmed', date: formatDate(currentOrder.created_at) },
    { title: 'Shipped', desc: currentOrder.courier_name ? `Via ${currentOrder.courier_name}` : 'Dispatched from hub', done: isShipped && !isCancelled, current: status === 'shipped', date: isShipped ? formatDate(currentOrder.updated_at || currentOrder.created_at) : '' },
    { title: 'Delivered', desc: 'Package handed over', done: isDelivered && !isCancelled, current: isDelivered, date: isDelivered ? formatDate(currentOrder.updated_at || currentOrder.created_at) : '' },
  ];

  const handleOrderChange = (updated) => {
    setCurrentOrder(updated);
    if (onOrderUpdated) {
      onOrderUpdated(updated);
    }
  };

  return (
    <div className="order-modal-backdrop" onClick={onClose}>
      <div className="order-detail-sheet bg-white" onClick={(e) => e.stopPropagation()}>
        
        {/* Modal Header */}
        <div className="order-detail-header d-flex align-items-center justify-content-between p-3 border-bottom sticky-top bg-white">
          <div className="d-flex align-items-center gap-2">
            <span className="order-id-badge fw-bold">Order Details</span>
            <span className="text-muted small">| {orderNumber}</span>
          </div>
          <button 
            type="button" 
            className="btn-close-custom" 
            onClick={onClose}
            aria-label="Close"
          >
            <IoClose size={22} />
          </button>
        </div>

        {/* Modal Scrollable Body */}
        <div className="order-detail-body p-3 p-md-4">
          
          {/* Status Alert Banner */}
          <div 
            className="p-3 rounded-3 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-2"
            style={{ 
              backgroundColor: isCancelled ? '#fef2f2' : isDelivered ? '#f0fdf4' : '#eff6ff',
              border: `1px solid ${isCancelled ? '#fecaca' : isDelivered ? '#bbf7d0' : '#bfdbfe'}`
            }}
          >
            <div className="d-flex align-items-center gap-2">
              <span className="status-dot" style={{ backgroundColor: tone, width: 12, height: 12 }}></span>
              <div>
                <h6 className="mb-0 fw-bold text-capitalize" style={{ color: tone }}>
                  {isCancelled 
                    ? 'Order Cancelled' 
                    : isReturnRequested 
                    ? 'Return Requested' 
                    : isReplacementRequested 
                    ? 'Replacement Requested' 
                    : isDelivered 
                    ? `Delivered on ${formatDate(currentOrder.updated_at || currentOrder.created_at)}` 
                    : `Status: ${status}`}
                </h6>
                <small className="text-muted">
                  {isCancelled 
                    ? 'This order was cancelled. Any payment made will be refunded.'
                    : isReturnRequested || isReplacementRequested
                    ? 'Our team is reviewing your return/replacement request.'
                    : isDelivered 
                    ? 'Your package has been delivered.'
                    : 'Your package is on its way to the delivery address.'}
                </small>
              </div>
            </div>

            <div className="d-flex flex-wrap gap-2 align-items-center">
              {isCancellable && (
                <button
                  type="button"
                  className="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-1"
                  onClick={() => setShowCancelModal(true)}
                >
                  <FaBan size={12} /> Cancel Order
                </button>
              )}

              {isWithinReturnWindow && (
                <button
                  type="button"
                  className="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-1"
                  onClick={() => setShowReturnModal(true)}
                >
                  <FaSyncAlt size={12} /> Return / Replace
                </button>
              )}

              {currentOrder.tracking_number && (
                <Link 
                  to={`/track-order?order=${encodeURIComponent(currentOrder.order_number || currentOrder.tracking_number)}`}
                  className="btn btn-sm btn-outline-primary d-none d-sm-inline-flex align-items-center gap-1 text-nowrap rounded-pill px-3"
                >
                  <FaTruck size={12} /> Track Parcel
                </Link>
              )}
            </div>
          </div>

          {/* Flipkart Timeline Tracker */}
          <div className="card border shadow-xs rounded-3 p-3 p-md-4 mb-4">
            <h6 className="fw-bold mb-3 d-flex align-items-center gap-2 text-dark">
              <FaTruck className="text-primary" /> Delivery Progress
            </h6>
            
            <div className="order-timeline-stepper">
              {timelineSteps.map((step, idx) => (
                <div 
                  key={idx} 
                  className={`timeline-step ${step.done ? 'step-done' : ''} ${step.current ? 'step-current' : ''}`}
                >
                  <div className="timeline-node">
                    {step.done ? <FaCheckCircle size={14} /> : <span className="node-dot"></span>}
                  </div>
                  <div className="timeline-content">
                    <p className="timeline-title mb-0 fw-semibold">{step.title}</p>
                    <span className="timeline-desc text-muted">{step.desc}</span>
                    {step.date && <span className="timeline-date d-block text-muted small">{step.date}</span>}
                  </div>
                  {idx < timelineSteps.length - 1 && <div className="timeline-line"></div>}
                </div>
              ))}
            </div>

            {currentOrder.tracking_number && (
              <div className="mt-3 pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div className="small text-muted">
                  Tracking ID: <strong className="text-dark">{currentOrder.tracking_number}</strong>
                  {currentOrder.courier_name && <span> ({currentOrder.courier_name})</span>}
                </div>
                <Link 
                  to={`/track-order?order=${encodeURIComponent(currentOrder.order_number || currentOrder.tracking_number)}`}
                  className="btn btn-primary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1"
                >
                  <FaTruck size={13} /> Live Tracking
                </Link>
              </div>
            )}
          </div>

          {/* Wireframe View 1: After Delivery Product Card */}
          {isDelivered ? (
            <div className="mb-4">
              <AfterDeliveryOrderCard
                order={currentOrder}
                onOpenReview={onOpenReview}
                onOpenInvoice={() => setShowInvoiceModal(true)}
              />
            </div>
          ) : (
            /* Ordered Items List (for pending/active orders) */
            <div className="card border shadow-xs rounded-3 p-3 p-md-4 mb-4">
              <h6 className="fw-bold mb-3 d-flex align-items-center gap-2 text-dark">
                <FaBoxOpen className="text-primary" /> Items in this Order ({items.length})
              </h6>

              <div className="order-items-list">
                {items.map((item, index) => {
                  const itemImg = item.product_image 
                    ? resolveImageUrl(item.product_image) 
                    : (item.image ? resolveImageUrl(item.image) : '/default-img.jpg');
                  const itemPrice = Number(item.unit_price ?? item.price ?? 0);
                  const itemTotal = Number(item.total_price ?? (itemPrice * (item.quantity || 1)));

                  return (
                    <div 
                      key={item.id || index} 
                      className={`order-item-row py-3 d-flex flex-column flex-sm-row gap-3 ${index < items.length - 1 ? 'border-bottom' : ''}`}
                    >
                      <div className="item-img-wrap flex-shrink-0">
                        <img 
                          src={itemImg} 
                          alt={item.product_name || 'Product'} 
                          className="rounded border object-fit-contain bg-light"
                          style={{ width: 75, height: 75 }}
                          onError={(e) => { e.target.src = '/default-img.jpg'; }}
                        />
                      </div>
                      
                      <div className="flex-grow-1">
                        <h6 className="fw-semibold text-dark mb-1 fs-6">{item.product_name}</h6>
                        <div className="d-flex flex-wrap gap-2 text-muted small mb-2">
                          {item.variant && <span>Variant: <strong className="text-dark">{item.variant}</strong></span>}
                          <span>Qty: <strong className="text-dark">{item.quantity || 1}</strong></span>
                          {item.sku && <span>SKU: {item.sku}</span>}
                        </div>
                        <div className="fw-bold text-dark fs-6">
                          ₹{itemTotal.toLocaleString('en-IN')}
                          {item.quantity > 1 && (
                            <span className="text-muted fw-normal small ms-2">
                              (₹{itemPrice.toLocaleString('en-IN')} each)
                            </span>
                          )}
                        </div>
                      </div>

                      {isDelivered && onOpenReview && (
                        <div className="align-self-sm-center flex-shrink-0 mt-2 mt-sm-0">
                          <button
                            type="button"
                            className="btn btn-outline-primary btn-sm rounded-pill d-flex align-items-center gap-1 px-3 py-1"
                            onClick={() => onOpenReview(item, currentOrder)}
                          >
                            <FaStar className="text-warning" size={12} /> Rate & Review
                          </button>
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          <div className="row g-3">
            {/* Delivery Address Card */}
            <div className="col-12 col-md-6">
              <div className="card border shadow-xs rounded-3 p-3 h-100">
                <h6 className="fw-bold mb-2 d-flex align-items-center gap-2 text-dark">
                  <IoLocationOutline className="text-primary" size={18} /> Shipping Address
                </h6>
                <div className="small text-muted">
                  <div className="fw-bold text-dark mb-1">{currentOrder.customer_name || 'Customer'}</div>
                  <div style={{ whiteSpace: 'pre-line', lineHeight: '1.4' }}>
                    {currentOrder.shipping_address || 'Address provided during checkout'}
                  </div>
                  {currentOrder.customer_phone && (
                    <div className="mt-2 text-dark">
                      Phone: <strong>{currentOrder.customer_phone}</strong>
                    </div>
                  )}
                  {currentOrder.customer_email && (
                    <div className="text-muted">
                      Email: {currentOrder.customer_email}
                    </div>
                  )}
                </div>
              </div>
            </div>

            {/* Price Breakdown Card */}
            <div className="col-12 col-md-6">
              <div className="card border shadow-xs rounded-3 p-3 h-100 bg-light-subtle">
                <h6 className="fw-bold mb-3 d-flex align-items-center gap-2 text-dark">
                  <IoDocumentTextOutline className="text-primary" size={18} /> Price Breakdown
                </h6>
                
                <div className="price-breakdown-list small">
                  <div className="d-flex justify-content-between mb-2">
                    <span className="text-muted">List Price</span>
                    <span className="fw-semibold">₹{(subtotal + discountAmount).toLocaleString('en-IN')}</span>
                  </div>

                  {discountAmount > 0 && (
                    <div className="d-flex justify-content-between mb-2 text-success">
                      <span>Discount / Coupons</span>
                      <span className="fw-semibold">-₹{discountAmount.toLocaleString('en-IN')}</span>
                    </div>
                  )}

                  <div className="d-flex justify-content-between mb-2">
                    <span className="text-muted">Delivery Charges</span>
                    <span className={shippingCost === 0 ? "text-success fw-bold" : "fw-semibold"}>
                      {shippingCost === 0 ? "FREE" : `₹${shippingCost.toLocaleString('en-IN')}`}
                    </span>
                  </div>

                  {taxAmount > 0 && (
                    <div className="d-flex justify-content-between mb-2">
                      <span className="text-muted">Taxes & GST</span>
                      <span className="fw-semibold">₹{taxAmount.toLocaleString('en-IN')}</span>
                    </div>
                  )}

                  <hr className="my-2" />

                  <div className="d-flex justify-content-between fs-6 fw-bold text-dark pt-1">
                    <span>Total Amount Paid</span>
                    <span className="text-primary">₹{price.toLocaleString('en-IN')}</span>
                  </div>

                  <div className="mt-2 pt-2 border-top text-muted d-flex justify-content-between align-items-center" style={{ fontSize: '0.78rem' }}>
                    <span>Payment Method</span>
                    <span className="badge bg-secondary-subtle text-secondary border text-uppercase">
                      {currentOrder.payment_method || 'Online Payment'}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Quick Help & Invoicing Actions */}
          <div className="mt-4 pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
            <button 
              type="button"
              className="btn btn-link p-0 text-decoration-none text-muted small d-inline-flex align-items-center gap-1 hover-primary"
              onClick={() => setShowHelpModal(true)}
            >
              <IoHeadsetOutline size={16} className="text-primary" /> Need help with this order?
            </button>

            <div className="d-flex flex-wrap gap-2">
              <Link
                to={`/orders/${currentOrder.order_number || currentOrder.id}`}
                className="btn btn-outline-secondary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1"
                onClick={onClose}
              >
                <IoOpenOutline size={14} /> Full Page
              </Link>
              <button 
                type="button" 
                className="btn btn-outline-primary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1 fw-semibold"
                onClick={() => setShowInvoiceModal(true)}
              >
                <IoDownloadOutline size={15} /> Tax Invoice
              </button>
              <button 
                type="button" 
                className="btn btn-primary btn-sm rounded-pill px-4"
                onClick={onClose}
              >
                Done
              </button>
            </div>
          </div>

        </div>

      </div>

      {/* Tax Invoice Modal */}
      {showInvoiceModal && (
        <TaxInvoiceModal
          order={currentOrder}
          onClose={() => setShowInvoiceModal(false)}
        />
      )}

      {/* Order Help Modal */}
      {showHelpModal && (
        <OrderHelpModal
          order={currentOrder}
          onClose={() => setShowHelpModal(false)}
        />
      )}

      {/* Cancel Order Modal */}
      {showCancelModal && (
        <CancelOrderModal
          order={currentOrder}
          onClose={() => setShowCancelModal(false)}
          onSuccess={handleOrderChange}
        />
      )}

      {/* Return / Replacement Modal */}
      {showReturnModal && (
        <ReturnReplacementModal
          order={currentOrder}
          onClose={() => setShowReturnModal(false)}
          onSuccess={handleOrderChange}
        />
      )}
    </div>
  );
};

export default OrderDetailModal;
