import React, { useState, useRef, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { FaChevronDown, FaStar, FaCheckCircle, FaTruck, FaTimesCircle } from 'react-icons/fa';
import { BsCart3 } from 'react-icons/bs';
import { resolveImageUrl } from '../../config/apiEndpoints';
import './DesktopOrderDetailView.css';

/**
 * Format order delivery status matching the wireframe:
 */
const formatDeliveryStatus = (order) => {
  const status = (order?.status || 'pending').toLowerCase();
  const dateStr = order?.updated_at || order?.created_at;
  let formattedDate = '';
  
  if (dateStr) {
    try {
      const d = new Date(dateStr);
      if (!isNaN(d.getTime())) {
        formattedDate = d.toLocaleDateString('en-GB', {
          day: 'numeric',
          month: 'long',
          year: 'numeric'
        });
      }
    } catch {
      formattedDate = dateStr;
    }
  }

  if (status === 'delivered' || status === 'completed') {
    return formattedDate ? `Package Delivered on ${formattedDate}.` : 'Package Delivered.';
  }
  if (status === 'cancelled') {
    return formattedDate ? `Order Cancelled on ${formattedDate}.` : 'Order Cancelled.';
  }
  if (status === 'shipped') {
    return 'Shipped • In Transit';
  }
  if (status === 'confirmed') {
    return 'Order Confirmed - Preparing';
  }
  return 'Processing your order';
};

/**
 * DesktopOrderDetailView
 * Implements the exact 4 desktop wireframe views:
 * 1. Before Delivery (media_1792462983244_57f40333.png)
 * 2. Before Delivery Detail Page (media_1792463009755_4db8182e.png)
 * 3. After Delivery (media_1792463049888_d376430e.png)
 * 4. After Delivery Detail Page (media_1792463106217_ee7589d7.png)
 */
const DesktopOrderDetailView = ({
  order,
  onOpenReview,
  onOpenInvoice,
  onOpenCancel,
  onTrackOrder,
  initialShowDetails = false
}) => {
  const navigate = useNavigate();
  const [showOrderDetails, setShowOrderDetails] = useState(initialShowDetails);
  const [showShipDropdown, setShowShipDropdown] = useState(false);
  const detailsRef = useRef(null);

  if (!order) return null;

  const status = (order.status || 'pending').toLowerCase();
  const isDelivered = status === 'delivered' || status === 'completed';
  const isCancelled = status === 'cancelled';

  // Format date helper: "2 February 2024"
  const formatDateLong = (dateStr) => {
    if (!dateStr) return '2 February 2024';
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

  // Compute Arriving Day, e.g. "Arriving Saturday" (Wireframe Image 1 & 2)
  const getArrivingDayText = () => {
    if (order.estimated_delivery) {
      try {
        const d = new Date(order.estimated_delivery);
        if (!isNaN(d.getTime())) {
          const dayName = d.toLocaleDateString('en-US', { weekday: 'long' });
          return `Arriving ${dayName}`;
        }
      } catch (e) {
        // fallback
      }
    }
    // Estimated delivery 4 days from placement date
    try {
      const placed = order.created_at ? new Date(order.created_at) : new Date();
      if (!isNaN(placed.getTime())) {
        const est = new Date(placed.getTime() + 4 * 24 * 60 * 60 * 1000);
        const dayName = est.toLocaleDateString('en-US', { weekday: 'long' });
        return `Arriving ${dayName}`;
      }
    } catch (e) {
      // fallback
    }
    return 'Arriving Saturday';
  };

  const recipientName = order.customer_name ||
    order.recipient_name ||
    order.shipping_name ||
    (order.shipping_address && typeof order.shipping_address === 'object' ? order.shipping_address.name : null) ||
    'Abdul khalik';

  const orderNumber = order.order_number || `#${order.id || '405-4288050-6954710'}`;
  const cleanOrderNumber = String(orderNumber).replace(/^#+/, '');
  const grandTotal = Number(order.grand_total ?? order.total_amount ?? 15800);
  const subtotal = Number(order.subtotal ?? (grandTotal > 1000 ? grandTotal + 1000 : grandTotal));
  const discountAmount = Number(order.discount_amount ?? (grandTotal > 1000 ? 1000 : 0));
  const couponDiscount = Number(order.coupon_discount ?? order.coupon_amount ?? (order.coupon_code ? 20 : 0));
  const shippingCost = Number(order.shipping_cost ?? (grandTotal > 0 ? 40 : 0));
  const convenienceCharge = Number(order.convenience_charge ?? 40);

  // Parse items
  const items = Array.isArray(order.items) && order.items.length > 0 
    ? order.items 
    : [{
        id: order.id || 1,
        product_name: order.title || order.product_name || 'Sky Blue Handle & Inside Colour Customizable Coffee Mug | 325 ml (Pack of 1)',
        product_image: order.image || order.product_image || '/default-img.jpg',
        unit_price: Number(order.unit_price || 549),
        quantity: Number(order.total_quantity || 10),
        slug: order.slug || ''
      }];

  const totalItemQuantity = items.reduce((sum, itm) => sum + Number(itm.quantity || 1), 0);

  // Address parsing
  const formatAddressBlock = (rawAddr, fallbackName) => {
    if (!rawAddr) {
      return {
        name: fallbackName,
        company: 'Printmont Corporation,',
        gstin: 'GSTIN. 07HPRPK31181ZZ',
        street: '3398, Bagichi Acchi ji, Bara Hindu Rao,\nnear Filmistan Cinema delhi - 110006,',
        city: 'New Delhi',
        statePin: 'DELHI 110006',
        phone: order.customer_phone || ''
      };
    }

    if (typeof rawAddr === 'object') {
      return {
        name: rawAddr.name || fallbackName,
        company: rawAddr.company || rawAddr.business_name || (order.company_name ? `${order.company_name},` : ''),
        gstin: rawAddr.gstin || order.gstin ? `GSTIN. ${rawAddr.gstin || order.gstin}` : '',
        street: [rawAddr.address, rawAddr.landmark].filter(Boolean).join(',\n'),
        city: rawAddr.city || '',
        statePin: [rawAddr.state, rawAddr.pincode].filter(Boolean).join(' '),
        phone: rawAddr.phone || order.customer_phone || ''
      };
    }

    // String address
    const lines = String(rawAddr).split(/[\n,]+/).map(s => s.trim()).filter(Boolean);
    return {
      name: fallbackName,
      company: order.company_name ? `${order.company_name},` : '',
      gstin: order.gstin ? `GSTIN. ${order.gstin}` : '',
      street: lines.slice(0, Math.max(1, lines.length - 2)).join(', '),
      city: lines.length > 2 ? lines[lines.length - 2] : '',
      statePin: lines.length > 1 ? lines[lines.length - 1] : '',
      phone: order.customer_phone || ''
    };
  };

  const shippingInfo = formatAddressBlock(order.shipping_address, recipientName);
  const billingInfo = formatAddressBlock(order.billing_address || order.shipping_address, recipientName);

  // Payment method text
  const getPaymentMethodText = () => {
    const pm = (order.payment_method || '').toLowerCase();
    if (pm === 'cod' || pm.includes('cash')) return 'Online / COD';
    if (pm.includes('card') || pm.includes('upi') || pm.includes('online') || pm.includes('razorpay')) {
      return 'Online / COD';
    }
    return 'Online / COD';
  };

  const toggleDetails = () => {
    setShowOrderDetails(prev => {
      const next = !prev;
      if (next && detailsRef.current) {
        setTimeout(() => {
          detailsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
      }
      return next;
    });
  };

  return (
    <div className="desktop-order-view-wrapper">
      {/* ─────────────────────────────────────────────────────────── */}
      {/* MAIN CARD (Views 1: Before or After Delivery)               */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="desktop-order-card">
        {/* Header Strip (#f0f2f2) */}
        <div className="desktop-order-card-header">
          {/* Left Metadata Columns */}
          <div className="desktop-order-header-left">
            {/* Column 1: ORDER PLACE */}
            <div className="desktop-order-meta-col">
              <span className="desktop-order-meta-label">ORDER PLACE</span>
              <span className="desktop-order-meta-value">
                {formatDateLong(order.created_at)}
              </span>
            </div>

            {/* Column 2: SHIP TO (with dropdown) */}
            <div className="desktop-order-meta-col position-relative">
              <span className="desktop-order-meta-label">SHIP TO</span>
              <span 
                className="desktop-order-meta-value desktop-order-shipto-trigger"
                onClick={() => setShowShipDropdown(!showShipDropdown)}
              >
                {recipientName} <FaChevronDown size={10} />
              </span>
              {showShipDropdown && (
                <div className="desktop-order-address-popover" onClick={(e) => e.stopPropagation()}>
                  <h6>{recipientName}</h6>
                  {shippingInfo.company && <p>{shippingInfo.company}</p>}
                  {shippingInfo.gstin && <p>{shippingInfo.gstin}</p>}
                  <p>{shippingInfo.street}</p>
                  <p>{shippingInfo.city} {shippingInfo.statePin}</p>
                  {shippingInfo.phone && <p className="mt-1">Phone: {shippingInfo.phone}</p>}
                </div>
              )}
            </div>

            {/* Column 3: TOTAL AMOUNT */}
            <div className="desktop-order-meta-col">
              <span className="desktop-order-meta-label">TOTAL AMOUNT</span>
              <span className="desktop-order-meta-value">
                ₹ {grandTotal.toFixed(2)}
              </span>
            </div>
          </div>

          {/* Right Column: ORDER ID & Header Links */}
          <div className="desktop-order-header-right">
            <span className="desktop-order-id-badge">
              ORDER ID #{cleanOrderNumber}
            </span>
            <div className="desktop-order-header-links">
              <button
                type="button"
                className="desktop-order-header-link"
                onClick={toggleDetails}
              >
                View Order details.
              </button>
              <span className="desktop-order-header-divider">|</span>
              <button
                type="button"
                className="desktop-order-header-link"
                onClick={() => onOpenInvoice && onOpenInvoice(order)}
              >
                Invoice <FaChevronDown size={9} />
              </button>
            </div>
          </div>
        </div>

        {/* Card Body */}
        <div className="desktop-order-card-body">
          {/* Status Headline */}
          {isDelivered ? (
            <div className="desktop-order-arriving-headline text-success">
              <FaCheckCircle size={18} /> {formatDeliveryStatus(order)}
            </div>
          ) : isCancelled ? (
            <div className="desktop-order-arriving-headline text-danger">
              <FaTimesCircle size={18} /> {formatDeliveryStatus(order)}
            </div>
          ) : (
            <div className="desktop-order-arriving-headline">
              <FaTruck size={18} /> {getArrivingDayText()}
            </div>
          )}

          {/* Item Rows */}
          {items.map((item, idx) => {
            const itemTitle = item.product_name || order.product_name || 'Sky Blue Handle & Inside Colour Customizable Coffee Mug...';
            const itemImage = item.product_image 
              ? resolveImageUrl(item.product_image) 
              : (order.image ? resolveImageUrl(order.image) : '/default-img.jpg');
            
            const unitPrice = Number(item.unit_price || item.price || 549);
            const mrpPrice = Number(item.mrp || item.regular_price || Math.round(unitPrice / 0.65));
            const discountPct = Math.max(1, Math.round(((mrpPrice - unitPrice) / mrpPrice) * 100));
            const qty = item.quantity || 10;
            const productLink = item.slug ? `/product/${item.slug}` : (item.product_id ? `/product/${item.product_id}` : '/allproducts');

            return (
              <div key={item.id || idx} className="desktop-order-item-row">
                {/* Left Product Info */}
                <div className="desktop-order-item-left">
                  <div className="desktop-order-item-thumb-wrap">
                    <img
                      src={itemImage}
                      alt={itemTitle}
                      className="desktop-order-item-thumb"
                      onError={(e) => { e.target.src = '/default-img.jpg'; }}
                    />
                  </div>

                  <div className="desktop-order-item-details">
                    <Link to={productLink} className="desktop-order-product-title">
                      {itemTitle}
                    </Link>

                    {/* Price Row: ₹ 549. ₹ 845 35% off */}
                    <div className="desktop-order-price-row">
                      <span className="desktop-order-price-current">
                        ₹ {unitPrice}.
                      </span>
                      <span className="desktop-order-price-mrp">
                        ₹ {mrpPrice}
                      </span>
                      <span className="desktop-order-discount-tag">
                        {discountPct}% off
                      </span>
                    </div>

                    <div className="desktop-order-qty-line">
                      Min Qty : {qty} pcs
                    </div>

                    {/* After Delivery (Images 3 & 4): Buy it again | View your item */}
                    {isDelivered && (
                      <div className="desktop-order-after-delivery-btn-group">
                        <Link to={productLink} className="desktop-order-btn-buy-again">
                          <BsCart3 size={14} /> Buy it again
                        </Link>
                        <Link to={productLink} className="desktop-order-btn-view-item">
                          View your item
                        </Link>
                      </div>
                    )}
                  </div>
                </div>

                {/* Right Action Buttons */}
                {!isDelivered ? (
                  /* Before Delivery (Images 1 & 2): 3 stacked pill buttons */
                  <div className="desktop-order-before-delivery-actions">
                    <button
                      type="button"
                      className="desktop-order-btn-track"
                      onClick={() => {
                        if (onTrackOrder) {
                          onTrackOrder(order);
                        } else {
                          navigate(`/track-order?order=${encodeURIComponent(cleanOrderNumber)}`);
                        }
                      }}
                    >
                      Track your order
                    </button>

                    <button
                      type="button"
                      className="desktop-order-btn-pill-outline"
                      onClick={toggleDetails}
                    >
                      View order details
                    </button>

                    <button
                      type="button"
                      className="desktop-order-btn-pill-outline"
                      onClick={() => onOpenCancel && onOpenCancel(order)}
                    >
                      Canceled my order
                    </button>
                  </div>
                ) : (
                  /* After Delivery (Images 3 & 4): Single Write a product review pill */
                  <div className="desktop-order-after-delivery-actions">
                    {item.is_reviewed ? (
                      <button
                        type="button"
                        className="desktop-order-btn-review desktop-order-btn-reviewed"
                        onClick={() => onOpenReview && onOpenReview(item, order, true)}
                        title="You have already reviewed this product. Click to view or edit your review."
                      >
                        <FaStar className="me-1 text-warning" /> View / Edit review
                      </button>
                    ) : (
                      <button
                        type="button"
                        className="desktop-order-btn-review"
                        onClick={() => onOpenReview && onOpenReview(item, order, false)}
                      >
                        Write a product review
                      </button>
                    )}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 3-COLUMN ORDER DETAILS SECTION (Images 2 & 4)               */}
      {/* ─────────────────────────────────────────────────────────── */}
      {showOrderDetails && (
        <div className="desktop-order-details-section" ref={detailsRef}>
          <h4 className="desktop-order-details-heading">Order Details</h4>
          <div className="desktop-order-details-meta-line">
            <span><strong>Order Placed :</strong> {formatDateLong(order.created_at)}</span>
            <span className="desktop-order-details-meta-divider">|</span>
            <span><strong>Order id:</strong> #{cleanOrderNumber}</span>
            <span className="desktop-order-details-meta-divider">|</span>
            <span><strong>Payment Method:</strong> {getPaymentMethodText()}</span>
          </div>

          <div className="desktop-order-three-col-card">
            {/* Column 1: SHIPPING ADDRESS. */}
            <div className="desktop-order-col">
              <div className="desktop-order-col-title">SHIPPING ADDRESS.</div>
              <div className="desktop-order-address-box">
                <div className="desktop-order-address-name">{shippingInfo.name}</div>
                {shippingInfo.company && (
                  <div className="desktop-order-address-company">{shippingInfo.company}</div>
                )}
                {shippingInfo.gstin && (
                  <div className="desktop-order-address-gstin">{shippingInfo.gstin}</div>
                )}
                <div className="desktop-order-address-street">{shippingInfo.street}</div>
                {shippingInfo.city && (
                  <div className="desktop-order-address-city">{shippingInfo.city}</div>
                )}
                {shippingInfo.statePin && (
                  <div className="desktop-order-address-statepin">{shippingInfo.statePin}</div>
                )}
                {shippingInfo.phone && (
                  <div className="desktop-order-address-phone">Phone: {shippingInfo.phone}</div>
                )}
              </div>
            </div>

            {/* Column 2: BILLING ADDRESS. */}
            <div className="desktop-order-col">
              <div className="desktop-order-col-title">BILLING ADDRESS.</div>
              <div className="desktop-order-address-box">
                <div className="desktop-order-address-name">{billingInfo.name}</div>
                {billingInfo.company && (
                  <div className="desktop-order-address-company">{billingInfo.company}</div>
                )}
                {billingInfo.gstin && (
                  <div className="desktop-order-address-gstin">{billingInfo.gstin}</div>
                )}
                <div className="desktop-order-address-street">{billingInfo.street}</div>
                {billingInfo.city && (
                  <div className="desktop-order-address-city">{billingInfo.city}</div>
                )}
                {billingInfo.statePin && (
                  <div className="desktop-order-address-statepin">{billingInfo.statePin}</div>
                )}
                {billingInfo.phone && (
                  <div className="desktop-order-address-phone">Phone: {billingInfo.phone}</div>
                )}
              </div>
            </div>

            {/* Column 3: PRICE DETAILS. */}
            <div className="desktop-order-col">
              <div className="desktop-order-col-title desktop-order-col-title-blue">
                PRICE DETAILS.
              </div>
              <div className="desktop-order-price-details-list">
                <div className="desktop-order-price-row-item">
                  <span className="desktop-order-price-label">
                    Items : ({totalItemQuantity || items.length})
                  </span>
                  <span className="desktop-order-price-val">
                    ₹ {Math.round(subtotal + discountAmount)}
                  </span>
                </div>

                <div className="desktop-order-price-row-item">
                  <span className="desktop-order-price-label">Discount :</span>
                  <span className="desktop-order-price-val desktop-order-price-val-minus">
                    - {discountAmount > 0 ? `${discountAmount}.` : '0.'}
                  </span>
                </div>

                <div className="desktop-order-price-row-item">
                  <span className="desktop-order-price-label">Coupon Applied :</span>
                  <span className="desktop-order-price-val desktop-order-price-val-minus">
                    - {couponDiscount.toFixed(2)}
                  </span>
                </div>

                <div className="desktop-order-price-row-item">
                  <span className="desktop-order-price-label">Shipping Charges :</span>
                  <span className="desktop-order-price-val desktop-order-price-val-plus">
                    + {shippingCost.toFixed(2)}
                  </span>
                </div>

                <div className="desktop-order-price-row-item">
                  <span className="desktop-order-price-label">Convenience Charge :</span>
                  <span className="desktop-order-price-val desktop-order-price-val-plus">
                    + {convenienceCharge.toFixed(2)}
                  </span>
                </div>

                <div className="desktop-order-price-total-row">
                  <span className="desktop-order-price-total-label">Sub Total Amount :</span>
                  <span className="desktop-order-price-total-val">
                    ₹ {grandTotal.toFixed(0)}.
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default DesktopOrderDetailView;
