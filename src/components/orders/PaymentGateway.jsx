import React, { useState, useEffect } from 'react';
import {
  BsChevronDown, BsChevronUp, BsPhone, BsCreditCard2Front,
  BsBank, BsCashCoin, BsGift, BsTagFill, BsCalendar3
} from 'react-icons/bs';
import toast from 'react-hot-toast';
import PriceDetails from './PriceDetails';
import TrustBar from './TrustBar';
import { useCheckout } from '../../context/CheckoutContext';
import { useAuth } from '../../context/AuthContext';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import './PaymentGateway.css';

const PAYMENT_OPTIONS = [
  {
    id: 'upi',
    title: 'UPI',
    subtitle: 'Pay by any UPI app (Google Pay, PhonePe, Paytm, BHIM)',
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
    subtitle: 'Get Debit and Cardless EMIs on Major Banks',
    mobileOffer: 'Credit Card EMI',
    Icon: BsCalendar3,
  },
  {
    id: 'netbanking',
    title: 'Net Banking',
    subtitle: 'All Major Indian Banks Supported (50+ Banks)',
    Icon: BsBank,
  },
  {
    id: 'cod',
    title: 'Cash on Delivery',
    subtitle: 'Pay when your order is delivered',
    Icon: BsCashCoin,
  },
];

const METHOD_NOTES = {
  card: 'Pay securely with Credit / Debit Card (Visa, MasterCard, RuPay, Maestro) via Razorpay.',
  emi: 'Credit card and cardless EMIs available on HDFC, ICICI, SBI, Axis and other major banks.',
  netbanking: 'Pay directly from your bank account via Net Banking (SBI, HDFC, ICICI, Axis, Kotak & 50+ banks).',
};

const loadRazorpay = () => new Promise((resolve) => {
  if (window.Razorpay) return resolve(true);
  const script = document.createElement('script');
  script.src = 'https://checkout.razorpay.com/v1/checkout.js';
  script.async = true;
  script.onload = () => resolve(true);
  script.onerror = () => resolve(false);
  document.body.appendChild(script);
});

/**
 * Checkout step 3: payment.
 * Same order flow on desktop and mobile; only the layout differs (desktop: methods + detail pane,
 * mobile: the "Payment" design — stepper, total card, accordion methods, price details, sticky pay).
 *
 * COD    → create_order places the order.
 * Online → create_order returns a Razorpay order for the server total → Razorpay checkout →
 *          verify_payment checks the signature and confirms the order.
 */
const PaymentGateway = ({ onPaymentSuccess }) => {
  const {
    paymentMethod, setPaymentMethod, cartTotals, submitOrder, verifyPayment,
    buyerDetails, address, cartItems, setCheckoutStep
  } = useCheckout();
  const { user } = useAuth();

  const [paymentConfig, setPaymentConfig] = useState({
    razorpay_enabled: true,
    cod_enabled: false,
    upi_enabled: true,
    cards_enabled: true,
    netbanking_enabled: true,
    emi_enabled: true,
    cod_instructions: '',
    brand_name: 'Printmont',
    theme_color: '#0B53A1',
    logo_url: '',
  });
  const [configLoaded, setConfigLoaded] = useState(false);
  const [activeMethod, setActiveMethod] = useState(paymentMethod || 'upi');
  const [upiId, setUpiId] = useState('');
  const [isUpiVerified, setIsUpiVerified] = useState(false);
  const [showTotal, setShowTotal] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // Payment methods switched on in admin (payment settings)
  useEffect(() => {
    let isMounted = true;
    (async () => {
      try {
        const res = await fetch(API_ENDPOINTS.PAYMENT_SETTINGS);
        const data = res.ok ? await res.json() : null;
        if (data && data.success && isMounted) setPaymentConfig((prev) => ({ ...prev, ...data }));
      } catch (err) {
        console.warn('Could not load payment settings, using defaults:', err);
      } finally {
        if (isMounted) setConfigLoaded(true);
      }
    })();
    return () => { isMounted = false; };
  }, []);

  const payableAmount = cartTotals.totalPayable;
  const payable = Math.round(payableAmount).toLocaleString('en-IN');

  const filteredOptions = PAYMENT_OPTIONS.filter((opt) => {
    if (opt.id === 'upi') return paymentConfig.upi_enabled && paymentConfig.razorpay_enabled;
    if (opt.id === 'card') return paymentConfig.cards_enabled && paymentConfig.razorpay_enabled;
    if (opt.id === 'emi') return paymentConfig.emi_enabled && paymentConfig.razorpay_enabled;
    if (opt.id === 'netbanking') return paymentConfig.netbanking_enabled && paymentConfig.razorpay_enabled;
    if (opt.id === 'cod') return paymentConfig.cod_enabled;
    return true;
  });

  // Keep the selected method valid when admin switches one off
  useEffect(() => {
    if (filteredOptions.length > 0 && !filteredOptions.some((o) => o.id === activeMethod)) {
      setActiveMethod(filteredOptions[0].id);
      setPaymentMethod(filteredOptions[0].id);
    }
  }, [filteredOptions, activeMethod, setPaymentMethod]);

  const handleMethodSelect = (id) => {
    setActiveMethod(id);
    setPaymentMethod(id);
  };

  const handleVerifyUpi = () => {
    if (!/^[\w.-]{2,}@[a-zA-Z]{2,}$/.test(upiId.trim())) {
      toast.error('Please enter a valid UPI ID (e.g. name@okhdfcbank)');
      return;
    }
    setIsUpiVerified(true);
    toast.success('UPI ID looks good. You will confirm the payment in your UPI app.');
  };

  const finishOrder = (result) => {
    const reference = result.orderNumber || result.orderId;
    toast.success(reference ? `Order #${reference} placed successfully!` : 'Order placed successfully!');
    if (onPaymentSuccess) onPaymentSuccess(result);
  };

  const openRazorpay = async (order) => {
    const ready = await loadRazorpay();
    if (!ready || !window.Razorpay) {
      toast.error('Could not load the payment window. Please check your connection and try again.');
      setSubmitting(false);
      return;
    }
    const rzp = new window.Razorpay({
      key: order.razorpay.key_id,
      order_id: order.razorpay.order_id,
      amount: order.razorpay.amount,
      currency: order.razorpay.currency || 'INR',
      name: paymentConfig.brand_name || 'Printmont',
      description: `Order #${order.orderNumber}`,
      image: paymentConfig.logo_url || '/pm-white-icon.png',
      prefill: {
        name: buyerDetails?.name || address?.name || user?.name || '',
        email: buyerDetails?.email || address?.email || user?.email || '',
        contact: buyerDetails?.mobile || address?.mobile || address?.phone || '',
        ...(activeMethod === 'upi' && upiId ? { vpa: upiId.trim() } : {}),
      },
      method: { upi: activeMethod === 'upi', card: activeMethod === 'card', netbanking: activeMethod === 'netbanking', emi: activeMethod === 'emi', wallet: false, paylater: false },
      theme: { color: paymentConfig.theme_color || '#0B53A1' },
      handler: async (response) => {
        const verified = await verifyPayment(order.orderId, {
          razorpay_order_id: response.razorpay_order_id,
          razorpay_payment_id: response.razorpay_payment_id,
          razorpay_signature: response.razorpay_signature,
        });
        setSubmitting(false);
        if (verified.success) {
          finishOrder({ ...order, ...verified });
        } else {
          toast.error(verified.error || 'Payment could not be confirmed. Please contact support.');
        }
      },
      modal: {
        ondismiss: () => {
          toast('Payment was cancelled. Your order is saved as payment pending — you can try again.', { icon: 'ℹ️' });
          setSubmitting(false);
        },
      },
    });
    rzp.on('payment.failed', (response) => {
      toast.error(response.error?.description || 'Payment failed. Please try another method.');
      setSubmitting(false);
    });
    rzp.open();
  };

  const handlePayment = async () => {
    if (submitting) return;
    if (!filteredOptions.some((o) => o.id === activeMethod)) {
      toast.error('Please choose a payment method.');
      return;
    }
    if (cartTotals.errors?.length) {
      toast.error(cartTotals.errors[0]);
      return;
    }
    setSubmitting(true);
    const result = await submitOrder({ paymentMethod: activeMethod });
    if (!result?.success) {
      toast.error(result?.error || 'Could not place your order. Please try again.');
      setSubmitting(false);
      return;
    }
    if (result.razorpay) {
      openRazorpay(result); // keeps "submitting" until Razorpay finishes
      return;
    }
    setSubmitting(false);
    finishOrder(result);
  };

  const payLabel = (id) => {
    if (submitting) return 'Processing…';
    return id === 'cod' ? 'Place Order' : `Pay ₹${payable}`;
  };

  const codNote = paymentConfig.cod_instructions || 'Pay in cash or by UPI when your order is delivered.';

  // ── Detail pane (desktop right side) ─────────────────────────────
  const renderDesktopDetail = (id) => {
    if (id === 'upi') {
      return (
        <div className="py-2">
          <div className="d-flex align-items-center justify-content-between mb-3">
            <label className="d-flex align-items-center gap-2 text-dark fw-semibold mb-0" style={{ fontSize: '13.5px' }}>
              <input type="radio" defaultChecked className="form-check-input mt-0 shadow-none" style={{ width: '16px', height: '16px' }} />
              Add new UPI ID
            </label>
            <a
              href="#how-to-find"
              className="text-decoration-none fw-semibold"
              style={{ color: '#0b53a1', fontSize: '12px' }}
              onClick={(e) => { e.preventDefault(); toast('Your UPI ID looks like name@bank — find it in your UPI app profile.', { icon: '💡' }); }}
            >
              How to find?
            </a>
          </div>
          <label htmlFor="upi-id-desktop" className="d-block text-secondary mb-1" style={{ fontSize: '11.5px' }}>UPI ID</label>
          <div className="d-flex gap-2 mb-3">
            <input
              id="upi-id-desktop"
              type="text"
              placeholder="Enter your UPI ID"
              className="form-control checkout-input flex-grow-1"
              value={upiId}
              onChange={(e) => { setUpiId(e.target.value); setIsUpiVerified(false); }}
            />
            <button type="button" className="btn text-white fw-bold px-3" style={{ backgroundColor: '#0b53a1', borderRadius: '3px', fontSize: '13px' }} onClick={handleVerifyUpi}>
              {isUpiVerified ? 'Verified' : 'Verify'}
            </button>
          </div>
          <button
            type="button"
            className="btn w-100 py-2 text-white fw-bold text-uppercase"
            style={{ backgroundColor: '#0b53a1', borderRadius: '3px', fontSize: '14px' }}
            onClick={handlePayment}
            disabled={submitting}
          >
            {payLabel(id)}
          </button>
        </div>
      );
    }
    return (
      <div className="py-2">
        <p className="text-muted mb-3" style={{ fontSize: '12.5px' }}>{id === 'cod' ? codNote : METHOD_NOTES[id]}</p>
        <button
          type="button"
          className="btn w-100 py-2 text-white fw-bold text-uppercase"
          style={{ backgroundColor: '#0b53a1', borderRadius: '3px', fontSize: '14px' }}
          onClick={handlePayment}
          disabled={submitting}
        >
          {payLabel(id)}
        </button>
      </div>
    );
  };

  // ── Mobile accordion body ────────────────────────────────────────
  const renderMobileDetail = (id) => {
    if (id === 'upi') {
      return (
        <div className="pgm-card">
          <div className="pgm-upi__top">
            <label className="pgm-radio">
              <input type="radio" defaultChecked name="pgm-upi-option" />
              <span>Add new UPI ID</span>
            </label>
            <button
              type="button"
              className="pgm-link"
              onClick={() => toast('Your UPI ID looks like name@bank — find it in your UPI app profile.', { icon: '💡' })}
            >
              How to<br />find?
            </button>
          </div>
          <div className="pgm-upi__body">
            <label htmlFor="upi-id-mobile" className="pgm-upi__label">UPI ID</label>
            <div className="pgm-upi__row">
              <input
                id="upi-id-mobile"
                type="text"
                placeholder="Enter your UPI ID"
                value={upiId}
                onChange={(e) => { setUpiId(e.target.value); setIsUpiVerified(false); }}
              />
              <button type="button" className="pgm-verify" onClick={handleVerifyUpi}>
                {isUpiVerified ? 'Verified' : 'Verify'}
              </button>
            </div>
            <button type="button" className="pgm-pay" onClick={handlePayment} disabled={submitting}>
              {payLabel(id)}
            </button>
          </div>
        </div>
      );
    }
    return (
      <div className="pgm-card">
        <p className="pgm-note">{id === 'cod' ? codNote : METHOD_NOTES[id]}</p>
        <button type="button" className="pgm-pay" onClick={handlePayment} disabled={submitting}>
          {payLabel(id)}
        </button>
      </div>
    );
  };

  const noMethods = configLoaded && filteredOptions.length === 0;

  return (
    <div className="payment-step bg-white">

      {/* ═════════════ DESKTOP (≥ 768px) ═════════════ */}
      <div className="d-none d-md-block">
        {noMethods ? (
          <div className="p-4 text-danger">No payment method is available right now. Please contact support.</div>
        ) : (
          <div className="pay-split">
            <div className="pay-split__methods">
              {filteredOptions.map(({ id, title, subtitle, offers, Icon }) => {
                const isSelected = activeMethod === id;
                return (
                  <div key={id} className={`pay-method border-bottom bg-white ${isSelected ? 'pay-method--open' : ''}`}>
                    <button
                      type="button"
                      className="pay-method__head w-100 d-flex align-items-center justify-content-between p-3 border-0 bg-transparent text-start shadow-none"
                      onClick={() => handleMethodSelect(id)}
                      aria-expanded={isSelected}
                    >
                      <div className="d-flex align-items-start gap-3 flex-grow-1">
                        <Icon size={18} className="text-secondary flex-shrink-0 mt-1" />
                        <div>
                          <span className="pay-method__title fw-semibold text-dark d-block" style={{ fontSize: '13.5px' }}>{title}</span>
                          {subtitle && <span className="text-secondary d-block" style={{ fontSize: '11px', marginTop: '2px' }}>{subtitle}</span>}
                          {offers && <span className="d-block text-success fw-semibold" style={{ fontSize: '11.5px', marginTop: '2px' }}>{offers}</span>}
                        </div>
                      </div>
                      {isSelected ? <BsChevronUp size={13} className="text-secondary ms-2" /> : <BsChevronDown size={13} className="text-secondary ms-2" />}
                    </button>
                    {/* 768–991px: details open under the method */}
                    {isSelected && (
                      <div className="d-lg-none px-3 pb-3 border-top bg-light">
                        {renderDesktopDetail(id)}
                      </div>
                    )}
                  </div>
                );
              })}
              <div className="pay-method d-flex align-items-center justify-content-between p-3 bg-white border-bottom">
                <div className="d-flex align-items-center gap-3">
                  <BsGift size={18} className="text-secondary flex-shrink-0" />
                  <span className="fw-semibold text-dark" style={{ fontSize: '13.5px' }}>Have a Printmont Gift Card?</span>
                </div>
                <button
                  type="button"
                  className="btn btn-link p-0 text-decoration-none fw-bold"
                  style={{ color: '#0b53a1', fontSize: '13px' }}
                  onClick={() => toast('Gift card redemption is coming soon.', { icon: '🎁' })}
                >
                  Add
                </button>
              </div>
            </div>
            <div className="pay-split__detail d-none d-lg-block">
              {activeMethod && (
                <div className="pay-detail-card p-4 bg-white border rounded shadow-xs">
                  {renderDesktopDetail(activeMethod)}
                </div>
              )}
            </div>
          </div>
        )}
      </div>

      {/* ═════════════ MOBILE (< 768px) — "Payment" design ═════════════ */}
      <div className="pgm d-md-none">
        {/* Stepper */}
        <div className="pgm-steps">
          <button type="button" className="pgm-step is-done" onClick={() => setCheckoutStep(1)}>
            <span className="pgm-step__dot">1</span>
            <span className="pgm-step__label">Address</span>
          </button>
          <span className="pgm-step__line" />
          <button type="button" className="pgm-step is-done" onClick={() => setCheckoutStep(2)}>
            <span className="pgm-step__dot">2</span>
            <span className="pgm-step__label">Order Summary</span>
          </button>
          <span className="pgm-step__line" />
          <div className="pgm-step is-active">
            <span className="pgm-step__dot">3</span>
            <span className="pgm-step__label">Payment</span>
          </div>
        </div>

        {/* Total amount */}
        <div className="pgm-pad">
          <button type="button" className="pgm-total" onClick={() => setShowTotal((v) => !v)} aria-expanded={showTotal}>
            <span className="pgm-total__label">Total Amount {showTotal ? <BsChevronUp size={12} /> : <BsChevronDown size={12} />}</span>
            <span className="pgm-total__value">₹{payable}</span>
          </button>
          {showTotal && (
            <div className="pgm-total__body">
              <PriceDetails isMobile />
            </div>
          )}

          {/* Offers */}
          <div className="pgm-offer">
            <div>
              <div className="pgm-offer__title">10% instant discount</div>
              <div className="pgm-offer__sub">Claim now with payment offers</div>
            </div>
            <div className="pgm-offer__icons">
              <span><BsTagFill size={14} className="text-success" /></span>
              <span><BsCreditCard2Front size={14} /></span>
              <span className="pgm-offer__more">+3</span>
            </div>
          </div>
        </div>

        {/* Methods */}
        {noMethods ? (
          <div className="pgm-pad text-danger">No payment method is available right now. Please contact support.</div>
        ) : (
          <div className="pgm-methods">
            {filteredOptions.map(({ id, title, subtitle, offers, mobileOffer, Icon }) => {
              const isSelected = activeMethod === id;
              return (
                <div key={id} className={`pgm-method ${isSelected ? 'is-open' : ''}`}>
                  <button type="button" className="pgm-method__head" onClick={() => handleMethodSelect(id)} aria-expanded={isSelected}>
                    <Icon size={20} className="pgm-method__icon" />
                    <span className="pgm-method__text">
                      <span className="pgm-method__title">{title}</span>
                      {!isSelected && id !== 'upi' && subtitle && id === 'card' && <span className="pgm-method__sub">{subtitle}</span>}
                      {!isSelected && (offers || mobileOffer) && id !== 'upi' && <span className="pgm-method__offer">{id === 'emi' ? mobileOffer : offers}</span>}
                    </span>
                    {isSelected ? <BsChevronUp size={14} /> : <BsChevronDown size={14} />}
                  </button>
                  {isSelected && <div className="pgm-method__body">{renderMobileDetail(id)}</div>}
                </div>
              );
            })}
            <div className="pgm-method pgm-gift">
              <BsGift size={20} className="pgm-method__icon" />
              <span className="pgm-method__title flex-grow-1">Have a Printmont Gift Card?</span>
              <button type="button" className="pgm-link" onClick={() => toast('Gift card redemption is coming soon.', { icon: '🎁' })}>Add</button>
            </div>
          </div>
        )}

        {/* Price details */}
        <div className="pgm-band">
          <div className="pgm-band__title">PRICE DETAILS.</div>
          <div className="pgm-band__card">
            <PriceDetails isMobile />
          </div>
          <TrustBar />
        </div>

        {/* Sticky pay */}
        <div className="pgm-sticky">
          <button type="button" onClick={handlePayment} disabled={submitting || noMethods || cartItems.length === 0}>
            {submitting ? 'PROCESSING…' : activeMethod === 'cod' ? 'PLACE ORDER' : 'PROCEED TO PAY'}
          </button>
        </div>
      </div>

    </div>
  );
};

export default PaymentGateway;
