import React from 'react';
import { Link } from 'react-router-dom';
import { FaStar, FaTruck, FaChevronRight, FaBan, FaSyncAlt } from 'react-icons/fa';
import { resolveImageUrl } from '../../config/apiEndpoints';

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

const FlipkartOrderCard = ({ order, onOpenDetails, onOpenReview, onCancelOrder, onReturnOrder }) => {
  const items = Array.isArray(order.items) && order.items.length > 0 ? order.items : [];
  const firstItem = items[0] || {};

  const title = firstItem.product_name || order.title || order.product_name || `Order ${order.order_number || `#${order.id}`}`;
  const image = firstItem.product_image 
    ? resolveImageUrl(firstItem.product_image) 
    : (order.image || order.product_image ? resolveImageUrl(order.image || order.product_image) : '/default-img.jpg');

  const price = Number(order.grand_total ?? order.total_amount ?? order.price ?? 0);
  const status = (order.status || 'pending').toLowerCase();
  const tone = STATUS_COLOR[status] || '#f59e0b';
  const extraCount = items.length - 1;

  const isDelivered = status === 'delivered';
  const isCancelled = status === 'cancelled';
  const isShipped = status === 'shipped';
  const isReturnRequested = status === 'return_requested';
  const isReplacementRequested = status === 'replacement_requested';
  const isCancellable = ['pending', 'confirmed', 'processing'].includes(status);

  // Return policy check (15 days)
  const orderTime = order.updated_at ? new Date(order.updated_at).getTime() : new Date(order.created_at).getTime();
  const isWithinReturnWindow = isDelivered && (Date.now() - orderTime <= 15 * 24 * 60 * 60 * 1000);

  const statusTitle = isDelivered
    ? `Delivered on ${formatDate(order.updated_at || order.created_at)}`
    : isCancelled
    ? `Cancelled on ${formatDate(order.updated_at || order.created_at)}`
    : isReturnRequested
    ? `Return Requested`
    : isReplacementRequested
    ? `Replacement Requested`
    : isShipped
    ? `Shipped • In Transit`
    : status === 'confirmed'
    ? `Order Confirmed`
    : `Processing`;

  const statusSubtext = isDelivered
    ? 'Your item has been delivered'
    : isCancelled
    ? 'Cancellation completed'
    : isReturnRequested
    ? 'Our team is reviewing your return request'
    : isReplacementRequested
    ? 'Replacement reprint is being arranged'
    : isShipped
    ? 'Item is on the way to delivery hub'
    : 'Your order is being prepared';

  return (
    <div 
      className="flipkart-order-card bg-white rounded-3 mb-3 border position-relative transition-all"
      onClick={() => onOpenDetails && onOpenDetails(order)}
      role="button"
      tabIndex={0}
      onKeyDown={(e) => { if (e.key === 'Enter') onOpenDetails && onOpenDetails(order); }}
    >
      <div className="p-3 p-md-4">
        <div className="row g-3 align-items-center">
          
          {/* 1. Product Image Column */}
          <div className="col-auto">
            <div className="position-relative order-card-thumb-wrap">
              <img
                src={image}
                alt={title}
                className="order-card-thumb rounded border bg-light object-fit-contain"
                onError={(e) => { e.target.src = '/default-img.jpg'; }}
              />
              {extraCount > 0 && (
                <span className="position-absolute bottom-0 end-0 bg-dark text-white rounded-pill px-1 extra-items-pill fw-bold">
                  +{extraCount}
                </span>
              )}
            </div>
          </div>

          {/* 2. Product & Order Info Column */}
          <div className="col min-width-0">
            <h6 className="order-product-title mb-1 fw-semibold text-dark text-truncate-2">
              {title}
            </h6>

            {/* Variant / Customization tag */}
            {firstItem.variant && (
              <div className="text-muted small mb-1">
                Variant: <span className="text-dark fw-medium">{firstItem.variant}</span>
              </div>
            )}

            <div className="order-meta-info d-flex flex-wrap align-items-center gap-2 text-muted small">
              <span>Order: <strong className="text-dark">{order.order_number || `#${order.id}`}</strong></span>
              <span>•</span>
              <span>Qty: <strong className="text-dark">{order.total_quantity || firstItem.quantity || 1}</strong></span>
              {order.created_at && (
                <>
                  <span className="d-none d-sm-inline">•</span>
                  <span className="d-none d-sm-inline">{formatDate(order.created_at)}</span>
                </>
              )}
            </div>

            {/* Mobile View: Price & Status inline */}
            <div className="d-md-none mt-2 pt-1 border-top d-flex justify-content-between align-items-center">
              <span className="fw-bold text-dark fs-6">₹{price.toLocaleString('en-IN')}</span>
              <div className="d-flex align-items-center gap-1">
                <span className="status-dot-sm" style={{ backgroundColor: tone }}></span>
                <span className="fw-semibold small" style={{ color: tone }}>{statusTitle}</span>
              </div>
            </div>

            {/* Mobile Action Buttons Bar */}
            <div className="d-md-none mt-2 pt-2 border-top d-flex flex-wrap gap-2" onClick={(e) => e.stopPropagation()}>
              {isCancellable && onCancelOrder && (
                <button
                  type="button"
                  className="btn btn-outline-danger btn-sm rounded-pill py-0 px-2 fw-semibold"
                  style={{ fontSize: '0.75rem' }}
                  onClick={() => onCancelOrder(order)}
                >
                  Cancel
                </button>
              )}

              {isWithinReturnWindow && onReturnOrder && (
                <button
                  type="button"
                  className="btn btn-outline-primary btn-sm rounded-pill py-0 px-2 fw-semibold"
                  style={{ fontSize: '0.75rem' }}
                  onClick={() => onReturnOrder(order)}
                >
                  Return / Replace
                </button>
              )}

              {isDelivered && onOpenReview && (
                <button
                  type="button"
                  className="btn btn-outline-warning text-dark btn-sm rounded-pill py-0 px-2 fw-semibold d-inline-flex align-items-center gap-1"
                  style={{ fontSize: '0.75rem' }}
                  onClick={() => onOpenReview(firstItem, order)}
                >
                  <FaStar className="text-warning" size={11} /> Rate Product
                </button>
              )}
            </div>
          </div>

          {/* 3. Desktop Price Column */}
          <div className="col-auto d-none d-md-block text-center px-3 border-start">
            <div className="fw-bold text-dark fs-5">
              ₹{price.toLocaleString('en-IN')}
            </div>
            <div className="text-muted small">
              {order.payment_method ? order.payment_method.toUpperCase() : 'PREPAID'}
            </div>
          </div>

          {/* 4. Desktop Status Column */}
          <div className="col-12 col-md-4 col-lg-4 text-start text-md-end border-start-md ps-md-3">
            <div className="d-none d-md-flex flex-column align-items-md-end">
              <div className="d-flex align-items-center gap-2">
                <span className="status-dot" style={{ backgroundColor: tone }}></span>
                <span className="fw-bold text-dark" style={{ fontSize: '0.95rem' }}>
                  {statusTitle}
                </span>
              </div>
              <span className="text-muted small mt-1">{statusSubtext}</span>
            </div>

            {/* Desktop Action buttons */}
            <div className="mt-2 d-flex flex-wrap align-items-center justify-content-md-end gap-2" onClick={(e) => e.stopPropagation()}>
              {isCancellable && onCancelOrder && (
                <button
                  type="button"
                  className="btn btn-outline-danger btn-sm rounded-pill px-2 py-1 d-inline-flex align-items-center gap-1"
                  style={{ fontSize: '0.8rem' }}
                  onClick={() => onCancelOrder(order)}
                >
                  <FaBan size={11} /> Cancel Order
                </button>
              )}

              {isWithinReturnWindow && onReturnOrder && (
                <button
                  type="button"
                  className="btn btn-outline-primary btn-sm rounded-pill px-2 py-1 d-inline-flex align-items-center gap-1"
                  style={{ fontSize: '0.8rem' }}
                  onClick={() => onReturnOrder(order)}
                >
                  <FaSyncAlt size={11} /> Return / Replace
                </button>
              )}

              {isDelivered && onOpenReview && (
                <button
                  type="button"
                  className="btn-rate-review d-inline-flex align-items-center gap-1"
                  onClick={() => onOpenReview(firstItem, order)}
                >
                  <FaStar className="text-warning" size={13} />
                  <span>Rate & Review</span>
                </button>
              )}

              {order.tracking_number && (
                <Link
                  to={`/track-order?order=${encodeURIComponent(order.order_number || order.tracking_number)}`}
                  className="btn-track-order d-inline-flex align-items-center gap-1"
                >
                  <FaTruck size={12} />
                  <span>Track</span>
                </Link>
              )}

              <button 
                type="button" 
                className="btn btn-link btn-sm text-decoration-none text-muted p-0 d-none d-lg-inline-flex align-items-center gap-1 ms-1"
                onClick={() => onOpenDetails && onOpenDetails(order)}
              >
                <span>Details</span> <FaChevronRight size={10} />
              </button>
            </div>
          </div>

          {/* Chevron indicator on Mobile */}
          <div className="col-auto d-md-none text-muted">
            <FaChevronRight size={14} />
          </div>

        </div>
      </div>
    </div>
  );
};

export default FlipkartOrderCard;
