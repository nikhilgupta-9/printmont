import React, { useState } from 'react';
import { useCheckout } from '../../context/CheckoutContext';

const inr = (n) => `₹${Math.round(Number(n) || 0).toLocaleString('en-IN')}`;

// Price breakdown of the cart. Amounts come from the server quote (CheckoutContext.cartTotals);
// the coupon box checks codes with the server (admin coupons.php).
const PriceDetails = ({ isCart = false, isMobile = false, showCoupon = true }) => {
  const { cartTotals, couponCode, applyCoupon, removeCoupon } = useCheckout();
  const [code, setCode] = useState('');
  const [couponMsg, setCouponMsg] = useState(null);
  const [checking, setChecking] = useState(false);

  const {
    totalItems = 0,
    originalTotalPrice = 0,
    totalDiscount = 0,
    couponApplied = 0,
    deliveryCharges = 0,
    cashCoinsApplied = 0,
    totalPayable = 0,
    totalSaved = 0,
  } = cartTotals;

  const totalLabel = isCart ? 'Total Amount :' : 'Total Payable :';

  const handleApply = async () => {
    if (couponCode) {
      removeCoupon();
      setCouponMsg(null);
      setCode('');
      return;
    }
    setChecking(true);
    const res = await applyCoupon(code);
    setChecking(false);
    setCouponMsg({ ok: res.valid, text: res.message });
  };

  return (
    <div className={isMobile ? 'price-details-inner' : 'price-details-card bg-white p-3 mb-3 border border-light shadow-sm rounded-0'}>

      {!isMobile && (
        <>
          <h6 className="text-dark fw-bold text-uppercase text-center mb-4 mt-2" style={{ fontSize: '15px' }}>YOUR ORDERS</h6>
          <h6 className="fw-bold border-bottom pb-3 mb-3 text-uppercase" style={{ fontSize: '14px', color: '#0b53a1' }}>
            PRICE DETAILS.
          </h6>
        </>
      )}

      <div className="d-flex justify-content-between mb-3 small fs-6 text-dark">
        <span>Price ({totalItems} {totalItems === 1 ? 'item' : 'items'})</span>
        <span className="fw-semibold">{inr(originalTotalPrice)}</span>
      </div>

      {totalDiscount > 0 && (
        <div className="d-flex justify-content-between mb-3 small fs-6 text-dark">
          <span>Discount :</span>
          <span className="text-success fw-semibold">-{inr(totalDiscount)}</span>
        </div>
      )}

      {couponApplied > 0 && (
        <div className="d-flex justify-content-between mb-3 small fs-6 text-dark">
          <span>Coupon Applied{couponCode ? ` (${couponCode})` : ''} :</span>
          <span className="text-success fw-semibold">-{inr(couponApplied)}</span>
        </div>
      )}

      <div className="d-flex justify-content-between mb-3 small fs-6 text-dark">
        <span>Delivery Charges :</span>
        {deliveryCharges === 0 ? (
          <span className="text-success fw-semibold">
            <span className="text-decoration-line-through text-muted me-1">₹160</span> Free
          </span>
        ) : (
          <span className="fw-semibold">{inr(deliveryCharges)}</span>
        )}
      </div>

      {cashCoinsApplied > 0 && (
        <div className="d-flex justify-content-between mb-3 small fs-6 text-dark">
          <span>Cash Coins Applied :</span>
          <span className="text-success fw-semibold">-{inr(cashCoinsApplied)}</span>
        </div>
      )}

      <hr className="my-2 border-secondary-subtle" />

      <div className="d-flex justify-content-between py-2 fw-bold text-dark" style={{ fontSize: '17px' }}>
        <span>{totalLabel}</span>
        <span className="text-dark">{inr(totalPayable)}</span>
      </div>

      {totalSaved > 0 && (
        <div className="text-success fw-bold small fs-6 mb-3">
          You will save {inr(totalSaved)} on this order
        </div>
      )}

      {/* Coupon box: the code is checked on the server */}
      {showCoupon && (
        <>
          <div className="d-flex align-items-stretch border rounded-1 overflow-hidden mb-1 bg-white" style={{ borderColor: '#cbd5e1' }}>
            <input
              type="text"
              placeholder="Have a Discount Coupon?"
              value={couponCode || code}
              disabled={Boolean(couponCode)}
              onChange={(e) => { setCode(e.target.value.toUpperCase()); setCouponMsg(null); }}
              onKeyDown={(e) => { if (e.key === 'Enter') handleApply(); }}
              className="form-control border-0 rounded-0 shadow-none px-2"
              style={{ fontSize: '13px', color: '#1a73e8' }}
              aria-label="Coupon code"
            />
            <button
              type="button"
              className="btn rounded-0 fw-bold border-0 px-3 text-white"
              style={{ fontSize: '13px', backgroundColor: couponCode ? '#388e3c' : '#707070' }}
              onClick={handleApply}
              disabled={checking || (!couponCode && !code.trim())}
            >
              {checking ? 'Checking…' : couponCode ? 'Remove' : 'Apply'}
            </button>
          </div>
          {couponMsg && (
            <div className={`small mb-3 ${couponMsg.ok ? 'text-success' : 'text-danger'}`} style={{ fontSize: '12px' }}>
              {couponMsg.text}
            </div>
          )}
        </>
      )}

    </div>
  );
};

export default PriceDetails;
