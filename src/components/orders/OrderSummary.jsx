import React from 'react';
import CartProductCard from './CartProductCard';
import PriceDetails from './PriceDetails';
import PrintmontCoinsPromo from './PrintmontCoinsPromo';
import TrustBar from './TrustBar';
import { useCheckout } from '../../context/CheckoutContext';

const CASH_COINS_DISCOUNT = 20;
const COINS_SPENT = 20;

/**
 * Step 2 of checkout: confirm what is being delivered where, optionally spend
 * coins, then continue to payment. The product cards are the cart's own cards
 * in read-only mode so the two screens stay identical.
 */
const OrderSummary = ({ onContinue, onChangeAddress }) => {
  const {
    cartItems,
    useCashCoins, setUseCashCoins,
    PRINTMONT_COINS_BALANCE,
    cartTotals,
    buyerDetails,
    address,
  } = useCheckout();

  const deliverToName = buyerDetails?.name || 'Your address';
  const deliverToPin = address?.pincode || '';
  const addressType = (address?.addressType || 'Home').toUpperCase();
  const fullAddress = [
    address?.address || address?.addressArea,
    address?.landmark,
    address?.city,
    address?.state,
  ].filter(Boolean).join(', ');

  return (
    <div className="order-summary-step">

      {/* Deliver to */}
      <div className="deliver-to-bar">
        <div className="d-flex align-items-center gap-2">
          <span style={{ fontSize: '13px' }}>
            <span className="text-secondary">Deliver to :</span>{' '}
            <span className="fw-semibold text-dark">{deliverToName}{deliverToPin && `, ${deliverToPin}`}</span>
          </span>
          <span className="addr-type">{addressType}</span>
          <button type="button" className="change-link ms-auto" onClick={onChangeAddress}>
            CHANGE
          </button>
        </div>
        {fullAddress && (
          <p className="text-secondary mb-0 mt-1 text-truncate" style={{ fontSize: '12px' }}>
            {fullAddress}
          </p>
        )}
      </div>

      {/* Cash coins */}
      <div className="bg-white px-3 py-3 border-bottom">
        <div className="form-check checkout-check mb-2">
          <input
            className="form-check-input shadow-none" type="checkbox" id="useCashCoins"
            checked={useCashCoins} onChange={(e) => setUseCashCoins(e.target.checked)}
          />
          <label className="form-check-label fw-semibold" htmlFor="useCashCoins" style={{ color: '#0b53a1' }}>
            Pay Using Cash Coins
          </label>
        </div>

        <div className="d-flex align-items-center gap-2 ps-4" style={{ fontSize: '12px' }}>
          <span className="fw-semibold" style={{ color: '#0b53a1' }}>Printmont Coin</span>
          <img src="/printmont-coin.png" width={14} height={14} alt="" />
          <span className="fw-bold text-dark">{PRINTMONT_COINS_BALANCE}</span>
        </div>
        <p className="text-success fw-semibold mb-0 ps-4" style={{ fontSize: '12px' }}>
          Save extra ₹{CASH_COINS_DISCOUNT} using {COINS_SPENT} Printmont Coins
        </p>
      </div>

      {/* Items */}
      <div className="pt-2">
        {cartItems.map((item) => (
          <CartProductCard key={item.id} item={item} readOnly />
        ))}
      </div>

      {/* Price details */}
      <div className="checkout-panel-band d-md-none">
        <div className="px-3 py-2 checkout-panel-title">PRICE DETAILS.</div>
        <div className="px-3 pb-3">
          <div className="bg-white rounded-3 p-3 shadow-sm border">
            <PriceDetails
              itemCount={cartTotals.totalItems}
              totalPrice={cartTotals.originalTotalPrice}
              discount={cartTotals.totalDiscount}
              couponApplied={cartTotals.couponApplied}
              deliveryCharges={cartTotals.deliveryCharges}
              cashCoinsApplied={cartTotals.cashCoinsApplied}
              step={3}
              isMobile
            />
            <div className="mt-3">
              <PrintmontCoinsPromo />
            </div>
          </div>
          <div className="mt-3 bg-light rounded text-center">
            <TrustBar />
          </div>
        </div>
      </div>

      {/* Desktop keeps the action inline; mobile pins it. */}
      <div className="d-none d-md-flex justify-content-end p-3 bg-white border-top">
        <button className="btn checkout-cta" onClick={onContinue}>CONTINUE</button>
      </div>

      <div className="d-md-none checkout-bottom-bar">
        <button className="btn checkout-cta w-100" onClick={onContinue}>CONTINUE</button>
      </div>

    </div>
  );
};

export default OrderSummary;
