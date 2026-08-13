import React, { useState } from 'react';
import {
  BsChevronDown, BsChevronUp, BsPhone, BsCreditCard2Front,
  BsBank, BsCashCoin, BsGift, BsTagFill,
} from 'react-icons/bs';
import PriceDetails from './PriceDetails';
import TrustBar from './TrustBar';
import { useCheckout } from '../../context/CheckoutContext';

const PAYMENT_OPTIONS = [
  {
    id: 'upi',
    title: 'UPI',
    subtitle: null,
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
    subtitle: 'Credit Card EMI',
    Icon: BsBank,
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
 * Step 3 of checkout. One accordion row per method; only the open one shows
 * its form. The order is only submitted from the pay action.
 */
const PaymentGateway = ({ onPaymentSuccess }) => {
  const { paymentMethod, setPaymentMethod, cartTotals, submitOrder } = useCheckout();
  const [upiId, setUpiId] = useState('');
  const [showTotal, setShowTotal] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const payable = cartTotals.totalPayable.toLocaleString('en-IN');

  const handlePayment = async () => {
    if (submitting) return;
    setSubmitting(true);
    try {
      await submitOrder();
      if (onPaymentSuccess) onPaymentSuccess();
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="payment-step bg-white">

      {/* Total, collapsible */}
      <button
        type="button"
        className="w-100 d-flex align-items-center justify-content-between bg-white border-0 border-bottom px-3 py-3"
        onClick={() => setShowTotal((v) => !v)}
        aria-expanded={showTotal}
      >
        <span className="d-flex align-items-center gap-1" style={{ color: '#0b53a1', fontSize: '14px' }}>
          Total Amount {showTotal ? <BsChevronUp size={12} /> : <BsChevronDown size={12} />}
        </span>
        <span className="fw-bold text-dark" style={{ fontSize: '15px' }}>₹{payable}</span>
      </button>

      {showTotal && (
        <div className="px-3 py-3 border-bottom bg-white">
          <PriceDetails
            itemCount={cartTotals.totalItems}
            totalPrice={cartTotals.originalTotalPrice}
            discount={cartTotals.totalDiscount}
            couponApplied={cartTotals.couponApplied}
            deliveryCharges={cartTotals.deliveryCharges}
            cashCoinsApplied={cartTotals.cashCoinsApplied}
            step={4}
            isMobile
          />
        </div>
      )}

      {/* Offer strip */}
      <div className="px-3 py-3">
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

      {/* Methods */}
      <div className="payment-options border-top">
        {PAYMENT_OPTIONS.map(({ id, title, subtitle, offers, Icon }) => {
          const open = paymentMethod === id;
          return (
            <div key={id} className="pay-method">
              <button
                type="button"
                className="pay-method__head"
                onClick={() => setPaymentMethod(open ? null : id)}
                aria-expanded={open}
              >
                <Icon size={17} className="text-secondary flex-shrink-0" />
                <span className="flex-grow-1">
                  <span className="pay-method__title d-block">{title}</span>
                  {subtitle && <span className="pay-method__sub d-block">{subtitle}</span>}
                  {offers && (
                    <span className="d-block text-success fw-semibold" style={{ fontSize: '11px' }}>{offers}</span>
                  )}
                </span>
                {open ? <BsChevronUp size={12} className="text-secondary" /> : <BsChevronDown size={12} className="text-secondary" />}
              </button>

              {open && id === 'upi' && (
                <div className="px-3 pb-3">
                  <div className="d-flex align-items-center mb-2">
                    <input
                      type="radio" id="new-upi" name="upi-option" defaultChecked
                      className="form-check-input mt-0 me-2 shadow-none"
                      style={{ width: '15px', height: '15px' }}
                    />
                    <label htmlFor="new-upi" className="me-auto text-dark" style={{ fontSize: '13px' }}>
                      Add new UPI ID
                    </label>
                    <a href="#how-to-find" className="text-decoration-none fw-semibold" style={{ color: '#0b53a1', fontSize: '12px' }}>
                      How to find?
                    </a>
                  </div>

                  <label htmlFor="upi-id" className="d-block text-secondary mb-1" style={{ fontSize: '11px' }}>UPI ID</label>
                  <div className="d-flex gap-2 mb-3">
                    <input
                      id="upi-id" type="text" placeholder="Enter your UPI ID"
                      className="form-control checkout-input"
                      value={upiId} onChange={(e) => setUpiId(e.target.value)}
                    />
                    <button type="button" className="btn text-white fw-semibold px-3"
                      style={{ backgroundColor: '#0b53a1', borderRadius: '3px', fontSize: '13px' }}>
                      Verify
                    </button>
                  </div>

                  <button
                    type="button"
                    className="btn w-100 text-white fw-semibold py-2"
                    style={{ backgroundColor: upiId.trim() ? '#0b53a1' : '#8b8b8b', borderRadius: '2px', fontSize: '14px' }}
                    onClick={handlePayment}
                    disabled={submitting}
                  >
                    {submitting ? 'Placing order…' : `Pay ₹${payable}`}
                  </button>
                </div>
              )}

              {open && id !== 'upi' && (
                <div className="px-3 pb-3">
                  <button
                    type="button"
                    className="btn w-100 text-white fw-semibold py-2"
                    style={{ backgroundColor: '#0b53a1', borderRadius: '2px', fontSize: '14px' }}
                    onClick={handlePayment}
                    disabled={submitting}
                  >
                    {submitting ? 'Placing order…' : id === 'cod' ? 'Confirm Order' : `Pay ₹${payable}`}
                  </button>
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

      {/* Price details */}
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
              step={4}
              isMobile
            />
          </div>
          <div className="mt-3 bg-light rounded text-center">
            <TrustBar />
          </div>
        </div>
      </div>

      <div className="d-none d-md-flex justify-content-end p-3 bg-white border-top">
        <button className="btn checkout-cta" onClick={handlePayment} disabled={submitting}>
          {submitting ? 'Placing order…' : 'Proceed to Pay'}
        </button>
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
