import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { FaChevronDown, FaUndo, FaStar } from 'react-icons/fa';
import { BsCart3 } from 'react-icons/bs';
import { resolveImageUrl } from '../../config/apiEndpoints';
import './AfterDeliveryOrderCard.css';

/**
 * AfterDeliveryOrderCard - Renders the exact "Views 1: After Delivery Product"
 * wireframe layout (media_1792424979309_ebb9454e.png).
 */
const AfterDeliveryOrderCard = ({ 
  order, 
  onOpenReview, 
  onOpenInvoice,
  onViewDetails 
}) => {
  const navigate = useNavigate();
  const [showShipDropdown, setShowShipDropdown] = useState(false);
  const [showFullDetails, setShowFullDetails] = useState(false);

  if (!order) return null;

  // Format date helper: "2 February 2024"
  const formatDateLong = (dateStr) => {
    if (!dateStr) return 'Recent';
    try {
      const d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      return d.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      });
    } catch {
      return dateStr;
    }
  };

  // Recipient / customer name
  const recipientName = order.recipient_name ||
    order.shipping_name ||
    (order.shipping_address && typeof order.shipping_address === 'object' ? order.shipping_address.name : null) ||
    order.customer_name ||
    'Customer';

  const orderNumber = order.order_number || `#${order.id}`;
  const totalAmount = Number(order.grand_total ?? order.total_amount ?? order.price ?? 0);

  // Address text for dropdown
  const addressText = typeof order.shipping_address === 'object' && order.shipping_address
    ? `${order.shipping_address.address || ''}, ${order.shipping_address.city || ''}, ${order.shipping_address.state || ''} - ${order.shipping_address.pincode || ''}`
    : (order.shipping_address || 'Address provided at checkout');

  const phoneText = order.customer_phone || (order.shipping_address && typeof order.shipping_address === 'object' ? order.shipping_address.phone : '');

  // Extract items
  const items = Array.isArray(order.items) && order.items.length > 0 
    ? order.items 
    : [{
        id: order.id,
        product_name: order.title || order.product_name || `Corporate Customizable Coffee Mug`,
        product_image: order.image || order.product_image || '/default-img.jpg',
        unit_price: order.unit_price || totalAmount || 549,
        quantity: order.total_quantity || 10,
        slug: order.slug || ''
      }];

  const cleanOrderNumber = String(orderNumber).replace(/^#+/, '');

  return (
    <div className="after-delivery-card">
      {/* ─────────────────────────────────────────────────────────── */}
      {/* TOP HEADER STRIP                                            */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="after-delivery-header">
        {/* Left Metadata Columns */}
        <div className="after-delivery-header-left">
          {/* Column 1: ORDER PLACE */}
          <div className="after-delivery-meta-col">
            <span className="after-delivery-meta-label">ORDER PLACE</span>
            <span className="after-delivery-meta-value">
              {formatDateLong(order.created_at)}
            </span>
          </div>

          {/* Column 2: SHIP TO (with dropdown) */}
          <div className="after-delivery-meta-col position-relative">
            <span className="after-delivery-meta-label">SHIP TO</span>
            <span 
              className="after-delivery-meta-value after-delivery-shipto-trigger"
              onClick={() => setShowShipDropdown(!showShipDropdown)}
            >
              {recipientName} <FaChevronDown size={10} />
            </span>
            {showShipDropdown && (
              <div className="after-delivery-address-popover" onClick={(e) => e.stopPropagation()}>
                <h6>{recipientName}</h6>
                <p>{addressText}</p>
                {phoneText && <p className="mt-1">Phone: {phoneText}</p>}
              </div>
            )}
          </div>

          {/* Column 3: TOTAL AMOUNT */}
          <div className="after-delivery-meta-col">
            <span className="after-delivery-meta-label">TOTAL AMOUNT</span>
            <span className="after-delivery-meta-value">
              ₹ {totalAmount.toFixed(2)}
            </span>
          </div>
        </div>

        {/* Right Actions & Order ID */}
        <div className="after-delivery-header-right">
          <span className="after-delivery-order-id">
            ORDER ID #{cleanOrderNumber}
          </span>
          <div className="after-delivery-header-links">
            <button
              type="button"
              className="after-delivery-header-link"
              onClick={() => {
                if (onViewDetails) {
                  onViewDetails(order);
                } else {
                  setShowFullDetails(!showFullDetails);
                }
              }}
            >
              View Order details.
            </button>
            <span className="after-delivery-header-divider">|</span>
            <button
              type="button"
              className="after-delivery-header-link"
              onClick={() => onOpenInvoice && onOpenInvoice(order)}
            >
              Invoice <FaChevronDown size={9} />
            </button>
          </div>
        </div>
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* CARD BODY: ITEMS                                           */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="after-delivery-body">
        {items.map((item, idx) => {
          const itemTitle = item.product_name || order.product_name || 'Sky Blue Handle & Inside Colour Customizable Coffee Mug...';
          const itemImage = item.product_image 
            ? resolveImageUrl(item.product_image) 
            : (order.image ? resolveImageUrl(order.image) : '/default-img.jpg');
          
          const unitPrice = Number(item.unit_price || item.price || 549);
          // If mrp provided, use it, else compute ~35% off representation like wireframe
          const mrpPrice = Number(item.mrp || item.regular_price || Math.round(unitPrice / 0.65));
          const discountPct = Math.max(1, Math.round(((mrpPrice - unitPrice) / mrpPrice) * 100));
          const qty = item.quantity || 10;
          const productLink = item.slug ? `/product/${item.slug}` : (item.product_id ? `/product/${item.product_id}` : '/allproducts');

          return (
            <div key={item.id || idx} className="after-delivery-item-row">
              {/* Product Info Left */}
              <div className="after-delivery-item-left">
                {/* Square Product Image */}
                <div className="after-delivery-item-thumb-wrap">
                  <img
                    src={itemImage}
                    alt={itemTitle}
                    className="after-delivery-item-thumb"
                    onError={(e) => { e.target.src = '/default-img.jpg'; }}
                  />
                </div>

                {/* Details Column */}
                <div className="after-delivery-item-details">
                  <Link to={productLink} className="after-delivery-product-title">
                    {itemTitle}
                  </Link>

                  {/* Price Row: ₹ 549. ₹ 845 35% off */}
                  <div className="after-delivery-price-row">
                    <span className="after-delivery-price-current">
                      ₹ {unitPrice}.
                    </span>
                    <span className="after-delivery-price-mrp">
                      ₹ {mrpPrice}
                    </span>
                    <span className="after-delivery-discount-tag">
                      {discountPct}% off
                    </span>
                  </div>

                  {/* Minimum Qty */}
                  <div className="after-delivery-qty-line">
                    Min qty : {qty} pcs
                  </div>

                  {/* Action Buttons: Buy it again | View your item */}
                  <div className="after-delivery-btn-group">
                    <Link
                      to={productLink}
                      className="after-delivery-btn-buy-again"
                    >
                      <BsCart3 size={14} /> Buy it again
                    </Link>

                    <Link
                      to={productLink}
                      className="after-delivery-btn-view-item"
                    >
                      View your item
                    </Link>
                  </div>
                </div>
              </div>

              {/* Right Side: Write a product review */}
              <div className="after-delivery-item-right">
                <button
                  type="button"
                  className={`after-delivery-btn-review ${item.is_reviewed ? 'after-delivery-btn-reviewed' : ''}`}
                  onClick={() => onOpenReview && onOpenReview(item, order, item.is_reviewed)}
                  title={item.is_reviewed ? "You have already reviewed this product. Click to view or edit your review." : "Write a product review"}
                >
                  {item.is_reviewed ? (
                    <>
                      <FaStar className="me-1 text-warning" /> View / Edit review
                    </>
                  ) : (
                    'Write a product review'
                  )}
                </button>
              </div>
            </div>
          );
        })}
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* EXPANDABLE DETAILS ACCORDION                                */}
      {/* ─────────────────────────────────────────────────────────── */}
      {showFullDetails && (
        <div className="after-delivery-expanded-details">
          <div className="after-delivery-expanded-grid">
            <div className="after-delivery-expanded-section">
              <h6>Shipping Address</h6>
              <p><strong>{recipientName}</strong></p>
              <p>{addressText}</p>
              {phoneText && <p>Phone: {phoneText}</p>}
            </div>

            <div className="after-delivery-expanded-section">
              <h6>Payment Details</h6>
              <p>Payment Method: <strong>{order.payment_method ? order.payment_method.toUpperCase() : 'PREPAID'}</strong></p>
              <p>Status: <span className="text-success fw-bold">Completed</span></p>
            </div>

            <div className="after-delivery-expanded-section">
              <h6>Order Summary</h6>
              <p className="d-flex justify-content-between">
                <span>Subtotal:</span>
                <span>₹ {Number(order.subtotal || totalAmount).toFixed(2)}</span>
              </p>
              <p className="d-flex justify-content-between">
                <span>Shipping:</span>
                <span>{Number(order.shipping_cost || 0) === 0 ? 'FREE' : `₹ ${order.shipping_cost}`}</span>
              </p>
              <p className="d-flex justify-content-between fw-bold text-dark pt-1 border-top">
                <span>Total Amount:</span>
                <span>₹ {totalAmount.toFixed(2)}</span>
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default AfterDeliveryOrderCard;
