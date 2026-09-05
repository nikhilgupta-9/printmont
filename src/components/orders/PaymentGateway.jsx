import React, { useState } from 'react';
import {
  BsChevronDown, BsChevronUp, BsPhone, BsCreditCard2Front,
  BsBank, BsCashCoin, BsGift, BsTagFill, BsListUl,
} from 'react-icons/bs';
import toast from 'react-hot-toast';
import PriceDetails from './PriceDetails';
import TrustBar from './TrustBar';
import { useCheckout } from '../../context/CheckoutContext';

/** Share of the total taken up front when paying cash on delivery. */
const COD_ADVANCE_RATE = 0.3;

const PAYMENT_OPTIONS = [
  {
    id: 'upi',
    title: 'UPI',
    subtitle: 'Pay by any UPI app',
    offers: 'Save upto ₹50 • 5 offers available',
    Icon: BsPhone,
  },
  {
    id: 'card',
    title: 'Credit / Debit / ATM Card',
    subtitle: 'Add and secure cards as per RBI guidelines',
    offers: 'Get upto 5% cashback* • 2 offers available',
    Icon: BsCreditCard2Front,
  },
  {
    id: 'emi',
    title: 'EMI',
    subtitle: 'Get Debit and Cardless EMIs on HDFC Bank',
    Icon: BsListUl,
  },
  {
    id: 'netbanking',
    title: 'Net Banking',
    subtitle: null,
    Icon: BsBank,
  },
  {
    id: 'cod',
    title: 'Cash on Delivery',
    subtitle: null,
    Icon: BsCashCoin,
  },
];

/**
 * Step 4 of checkout.
 *
 * Desktop follows the design's split: the methods list on the left, the chosen
 * method's form in a card on the right. Mobile keeps the accordion, with the
 * same form rendered inline under the open row.
 */
const PaymentGateway = ({ onPaymentSuccess }) => {
  const { paymentMethod, setPaymentMethod, cartTotals, submitOrder } = useCheckout();
  const [upiId, setUpiId] = useState('');
  const [showTotal, setShowTotal] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const payable = cartTotals.totalPayable.toLocaleString('en-IN');
  const codAdvance = Math.round(cartTotals.totalPayable * COD_ADVANCE_RATE).toLocaleString('en-IN');

  const handlePayment = async () => {
    if (submitting) return;
    setSubmitting(true);
    try {
      const result = await submitOrder();

      // Only leave the checkout once the order is genuinely placed; this used
      // to navigate to the orders page even when submission had failed.
      if (!result?.success) {
        toast.error(result?.error || 'Could not place your order. Please try again.');
        return;
      }

      const reference = result.orderNumber || result.orderId;
      toast.success(
        reference ? `Order #${reference} placed successfully` : 'Order placed successfully'
      );

      if (onPaymentSuccess) onPaymentSuccess();
    } finally {
      setSubmitting(false);
    }
  };

  const payLabel = (id) => {
    if (submitting) return 'Placing order…';
    return id === 'cod' ? 'Confirm Order' : `Pay ₹${payable}`;
  };

  // Rendered twice — in the right-hand card on desktop and inline under the
  // open row on mobile — so ids and radio group names carry the variant to
  // keep the two copies from colliding in the DOM.
  const renderDetail = (id, variant) => {
    if (id === 'upi') {
      return (
        <>
          <div className="d-flex align-items-center mb-3">
            <input
              type="radio" id={`new-upi-${variant}`} name={`upi-option-${variant}`} defaultChecked
              className="form-check-input mt-0 me-2 shadow-none"
              style={{ width: '15px', height: '15px' }}
            />
            <label htmlFor={`new-upi-${variant}`} className="me-auto text-dark" style={{ fontSize: '13px' }}>
              Add new UPI ID
            </label>
            <a href="#how-to-find" className="text-decoration-none fw-semibold text-end"
              style={{ color: '#0b53a1', fontSize: '12px', lineHeight: 1.2 }}>
              How to find?
            </a>
          </div>

          <label htmlFor={`upi-id-${variant}`} className="d-block text-secondary mb-1" style={{ fontSize: '11px' }}>UPI ID</label>
          <div className="d-flex gap-2 mb-3">
            <input
              id={`upi-id-${variant}`} type="text" placeholder="Enter your UPI ID"
              className="form-control checkout-input"
              value={upiId} onChange={(e) => setUpiId(e.target.value)}
            />
            <button type="button" className="pay-verify-btn">Verify</button>
          </div>

          <button
            type="button"
            className={`pay-pay-btn${upiId.trim() ? ' pay-pay-btn--ready' : ''}`}
            onClick={handlePayment}
            disabled={submitting}
          >
            {payLabel(id)}
          </button>
        </>
      );
    }

    if (id === 'cod') {
      return (
        <>
          <p className="text-dark mb-3" style={{ fontSize: '13px' }}>
            Pay ₹{codAdvance} as advance and balance amount as Cash on delivery
          </p>
          <button type="button" className="pay-pay-btn pay-pay-btn--ready"
            onClick={handlePayment} disabled={submitting}>
            {payLabel(id)}
          </button>
        </>
      );
    }

    return (
      <button type="button" className="pay-pay-btn pay-pay-btn--ready"
        onClick={handlePayment} disabled={submitting}>
        {payLabel(id)}
      </button>
    );
  };

  const openOption = PAYMENT_OPTIONS.find((o) => o.id === paymentMethod);

  return (
    <div className="payment-step bg-white">

      {/* Total, collapsible — mobile only; desktop has the sidebar. */}
      <button
        type="button"
        className="d-md-none w-100 d-flex align-items-center justify-content-between bg-white border-0 border-bottom px-3 py-3"
        onClick={() => setShowTotal((v) => !v)}
        aria-expanded={showTotal}
      >
        <span className="d-flex align-items-center gap-1" style={{ color: '#0b53a1', fontSize: '14px' }}>
          Total Amount {showTotal ? <BsChevronUp size={12} /> : <BsChevronDown size={12} />}
        </span>
        <span className="fw-bold text-dark" style={{ fontSize: '15px' }}>₹{payable}</span>
      </button>

      {showTotal && (
        <div className="d-md-none px-3 py-3 border-bottom bg-white">
          <PriceDetails
            itemCount={cartTotals.totalItems}
            totalPrice={cartTotals.originalTotalPrice}
            discount={cartTotals.totalDiscount}
            couponApplied={cartTotals.couponApplied}
            deliveryCharges={cartTotals.deliveryCharges}
            cashCoinsApplied={cartTotals.cashCoinsApplied}
            isMobile
          />
        </div>
      )}

      {/* Offer strip — mobile only, the design has no equivalent on desktop. */}
      <div className="d-md-none px-3 py-3">
        <div className="pay-offer-banner d-flex align-items-center gap-2">
          <div className="flex-grow-1">
            <div className="fw-semibold">10% instant discount</div>
            <div className="text-secondary" style={{ fontSize: '11px' }}>Claim now with payment offers</div>
          </div>
          <BsTagFill size={14} className="text-secondary" />
          <BsCreditCard2Front size={14} className="text-secondary" />
          <span className="text-secondary" style={{ fontSize: '11px' }}>+3</span>
        </div>
      </div>

      <div className="pay-split">

        {/* Methods */}
        <div className="pay-split__methods">
          {PAYMENT_OPTIONS.map(({ id, title, subtitle, offers, Icon }) => {
            const open = paymentMethod === id;
            return (
              <div key={id} className={`pay-method${open ? ' pay-method--open' : ''}`}>
                <button
                  type="button"
                  className="pay-method__head"
                  onClick={() => setPaymentMethod(open ? null : id)}
                  aria-expanded={open}
                >
                  <Icon size={17} className="text-secondary flex-shrink-0 mt-1" />
                  <span className="flex-grow-1">
                    <span className="pay-method__title d-block">{title}</span>
                    {subtitle && <span className="pay-method__sub d-block">{subtitle}</span>}
                    {offers && (
                      <span className="d-block text-success fw-semibold" style={{ fontSize: '11px' }}>{offers}</span>
                    )}
                  </span>
                  {open
                    ? <BsChevronUp size={12} className="text-secondary" />
                    : <BsChevronDown size={12} className="text-secondary" />}
                </button>

                {/* Mobile accordion body. */}
                {open && (
                  <div className="d-lg-none px-3 pb-3">
                    {renderDetail(id, 'mobile')}
                  </div>
                )}
              </div>
            );
          })}

          {/* Gift card */}
          <div className="pay-method d-flex align-items-center gap-3 px-3 py-3">
            <BsGift size={17} className="text-secondary flex-shrink-0" />
            <span className="pay-method__title flex-grow-1">Have a Printmont Gift Card?</span>
            <button type="button" className="btn btn-link p-0 text-decoration-none fw-semibold"
              style={{ color: '#0b53a1', fontSize: '13px' }}>
              Add
            </button>
          </div>
        </div>

        {/* Desktop detail pane */}
        <div className="pay-split__detail">
          {openOption && (
            <div className="pay-detail-card">
              {renderDetail(openOption.id, 'desktop')}
            </div>
          )}
        </div>

      </div>

      {/* Price details — mobile only. */}
      <div className="checkout-panel-band d-md-none mt-3">
        <div className="px-3 py-2 checkout-panel-title">PRICE DETAILS</div>
        <div className="px-3 pb-3">
          <div className="bg-white rounded-3 p-3 shadow-sm border">
            <PriceDetails
              itemCount={cartTotals.totalItems}
              totalPrice={cartTotals.originalTotalPrice}
              discount={cartTotals.totalDiscount}
              couponApplied={cartTotals.couponApplied}
              deliveryCharges={cartTotals.deliveryCharges}
              cashCoinsApplied={cartTotals.cashCoinsApplied}
              isMobile
            />
          </div>
          <div className="mt-3 bg-light rounded text-center">
            <TrustBar />
          </div>
        </div>
      </div>

      <div className="d-md-none checkout-bottom-bar">
        <button className="btn checkout-cta w-100" onClick={handlePayment} disabled={submitting}>
          {submitting ? 'Placing order…' : 'Proceed to Pay'}
        </button>
      </div>

    </div>
  );
};

export default PaymentGateway;
