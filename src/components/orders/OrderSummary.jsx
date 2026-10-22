import React from 'react';
import CartProductCard from './CartProductCard';
import PriceDetails from './PriceDetails';
import PrintmontCoinsPromo from './PrintmontCoinsPromo';
import TrustBar from './TrustBar';
import { useCheckout } from '../../context/CheckoutContext';


/**
 * Step 2 of checkout: ORDER SUMMARY
 * 
 * Implements:
 * Desktop View (Image 1):
 *   - Pay Using Cash Coins box (Balance, Save 20, APPLIED/APPLY button)
 *   - Items list with detailed 3-column rows
 *   - Confirmation note with email & blue CONTINUE button
 * 
 * Mobile View (Image 2):
 *   - 3-Step horizontal stepper (1 Address done, 2 Order Summary active, 3 Payment)
 *   - Deliver to card with recipient, pincode, type badge (HOME/OFFICE) and CHANGE button
 *   - Pay Using Cash Coins toggle box with checkbox
 *   - Mobile product cards with quantity stepper and customization status
 *   - PRICE DETAILS. breakdown card, Coins banner, and Trust bar
 *   - Fixed bottom blue CONTINUE button
 */
const OrderSummary = ({ onContinue }) => {
  const {
    cartItems,
    useCashCoins, setUseCashCoins,
    cartTotals,
    buyerDetails,
    address,
    deliveryPincode,
    setCheckoutStep
  } = useCheckout();

  const recipientName = buyerDetails?.name || address?.name || 'Buyer';
  const pincode = address?.pincode || deliveryPincode || '110006';
  const addressType = address?.type || address?.addressType || 'HOME';
  const fullAddressLine = [
    address?.houseNo,
    address?.roadArea,
    address?.addressLine || address?.address,
    address?.landmark,
    address?.city,
    address?.state
  ].filter(Boolean).join(', ') || '3398 Bagichi Acchi ji Bara Hindu Rao Near Filmistan Cinema, Delhi, 110006';

  // Printmont coins from the server quote: balance, and how many this order can use
  const coinsBalance = cartTotals.coinsBalance || 0;
  const coinsUsable = cartTotals.coinsUsable || 0;
  const coinsHint = coinsUsable > 0
    ? `Save ₹${coinsUsable} using ${coinsUsable} Printmont Coins`
    : 'No coins available to use on this order';

  const confirmEmail = buyerDetails?.email || address?.email || 'buyer@gmail.com';

  return (
    <div className="order-summary-step bg-white">

      {/* 1. MOBILE 3-STEP PROGRESS STEPPER (Image 2) */}
      <div className="d-md-none p-3 pb-2 bg-white border-bottom">
        <div className="d-flex align-items-center justify-content-between px-2 text-center position-relative">
          {/* Step 1: Address (Done) */}
          <div 
            className="d-flex flex-column align-items-center flex-1 position-relative z-1 cursor-pointer" 
            onClick={() => setCheckoutStep(1)}
            style={{ cursor: 'pointer' }}
          >
            <div
              className="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs"
              style={{ width: '28px', height: '28px', backgroundColor: '#0b53a1', fontSize: '13px' }}
            >
              ✓
            </div>
            <span className="fw-semibold mt-1 text-dark" style={{ fontSize: '11px' }}>Address</span>
          </div>

          {/* Line 1 -> 2 */}
          <div className="flex-grow-1 border-top" style={{ borderColor: '#0b53a1', marginTop: '-18px' }}></div>

          {/* Step 2: Order Summary (Active) */}
          <div className="d-flex flex-column align-items-center flex-1 position-relative z-1">
            <div
              className="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs"
              style={{ width: '28px', height: '28px', backgroundColor: '#0b53a1', fontSize: '13px' }}
            >
              2
            </div>
            <span className="fw-bold mt-1 text-primary" style={{ fontSize: '11px' }}>Order Summary</span>
          </div>

          {/* Line 2 -> 3 */}
          <div className="flex-grow-1 border-top" style={{ borderColor: '#cbd5e1', marginTop: '-18px' }}></div>

          {/* Step 3: Payment */}
          <div className="d-flex flex-column align-items-center flex-1 position-relative z-1">
            <div
              className="rounded-circle d-flex align-items-center justify-content-center fw-bold border bg-white text-muted"
              style={{ width: '28px', height: '28px', fontSize: '13px', borderColor: '#94a3b8' }}
            >
              3
            </div>
            <span className="text-muted mt-1" style={{ fontSize: '11px' }}>Payment</span>
          </div>
        </div>
      </div>

      {/* 2. MOBILE "DELIVER TO :" CARD (Image 2) */}
      <div className="d-md-none bg-white p-3 border-bottom shadow-xs mb-2">
        <div className="d-flex align-items-center justify-content-between mb-1">
          <div className="d-flex align-items-center gap-2">
            <span className="fw-semibold text-dark" style={{ fontSize: '13.5px' }}>
              Deliver to : <span className="fw-bold">{recipientName}, {pincode}</span>
            </span>
            <span 
              className="badge px-1.5 py-0.5 text-uppercase fw-semibold"
              style={{ 
                fontSize: '10px', 
                backgroundColor: '#eff6ff', 
                color: '#0b53a1', 
                border: '1px solid #bfdbfe' 
              }}
            >
              {addressType}
            </span>
          </div>
          <button 
            type="button"
            className="btn btn-sm text-primary fw-bold p-0 border-0 bg-transparent text-uppercase"
            style={{ fontSize: '12px', letterSpacing: '0.4px' }}
            onClick={() => setCheckoutStep(1)}
          >
            CHANGE
          </button>
        </div>
        <p className="text-secondary small mb-0 text-truncate" style={{ fontSize: '12px', lineHeight: '1.4' }}>
          {fullAddressLine}
        </p>
      </div>

      {/* 3. PAY USING CASH COINS SECTION */}
      {/* Mobile Cash Coins (Image 2) */}
      <div className="d-md-none bg-white p-3 border-bottom shadow-xs mb-2">
        <div className="form-check d-flex align-items-center gap-2 mb-1 p-0">
          <input
            type="checkbox"
            id="mobileCashCoinsCheck"
            className="form-check-input ms-0 mt-0 shadow-none cursor-pointer"
            style={{ width: '16px', height: '16px', border: '1.5px solid #0b53a1' }}
            checked={useCashCoins}
            disabled={!useCashCoins && coinsUsable === 0}
            onChange={() => setUseCashCoins(!useCashCoins)}
          />
          <label htmlFor="mobileCashCoinsCheck" className="form-check-label fw-bold cursor-pointer" style={{ color: '#0b53a1', fontSize: '14px' }}>
            Pay Using Cash Coins
          </label>
        </div>
        <div className="ps-4">
          <div className="d-flex align-items-center gap-1.5" style={{ fontSize: '12.5px' }}>
            <span className="text-secondary">Balance</span>
            <img src="/printmont-coin.png" width={14} height={14} alt="Coin" />
            <span className="fw-bold text-dark">{coinsBalance}</span>
          </div>
          <div className="text-success fw-semibold mt-0.5" style={{ fontSize: '11.5px' }}>
            {coinsHint}
          </div>
        </div>
      </div>

      {/* Desktop Cash Coins (Image 1) */}
      <div className="d-none d-md-block px-4 py-3 border-bottom">
        <div className="d-flex align-items-center justify-content-between">
          <div>
            <h6 className="fw-bold mb-1" style={{ color: '#0b53a1', fontSize: '15px' }}>Pay Using Cash Coins</h6>
            <div className="d-flex align-items-center gap-1.5" style={{ fontSize: '13px' }}>
              <span className="text-muted">Balance</span>
              <img src="/printmont-coin.png" width={15} height={15} alt="Coin" />
              <span className="fw-bold text-dark">{coinsBalance}</span>
            </div>
            <p className="text-success small mb-0 mt-0.5 fw-semibold" style={{ fontSize: '12.5px' }}>
              {coinsHint}
            </p>
          </div>
          <button
            type="button"
            className={`btn px-4 py-1.5 fw-bold shadow-xs text-uppercase ${
              useCashCoins 
                ? 'btn-primary text-white' 
                : 'btn-outline-primary bg-white'
            }`}
            style={{ 
              fontSize: '13px', 
              letterSpacing: '0.4px',
              backgroundColor: useCashCoins ? '#0b53a1' : 'transparent',
              borderColor: '#0b53a1'
            }}
            onClick={() => setUseCashCoins(!useCashCoins)}
            disabled={!useCashCoins && coinsUsable === 0}
          >
            {useCashCoins ? 'APPLIED' : 'APPLY'}
          </button>
        </div>
      </div>

      {/* 4. PRODUCT ITEMS LIST */}
      <div className="cart-products-list">
        {cartItems.map((item) => (
          <CartProductCard key={item.id} item={item} readOnly />
        ))}
      </div>

      {/* 5. MOBILE PRICE DETAILS, COINS BANNER & TRUST BAR (Image 2) */}
      <div className="checkout-panel-band d-md-none mb-0">
        <div className="px-3 py-2 fw-bold text-uppercase" style={{ color: '#0b53a1', fontSize: '14px', letterSpacing: '0.3px' }}>
          PRICE DETAILS.
        </div>
        <div className="px-3 pb-3">
          <div className="bg-white rounded-3 p-3 shadow-xs border mb-3">
            <PriceDetails
              itemCount={cartTotals.totalItems || cartItems.length}
              totalPrice={cartTotals.originalTotalPrice}
              discount={cartTotals.totalDiscount}
              couponApplied={cartTotals.couponApplied}
              deliveryCharges={cartTotals.deliveryCharges}
              cashCoinsApplied={cartTotals.cashCoinsApplied}
              isMobile
            />
          </div>
          <PrintmontCoinsPromo />
          <div className="mt-2 text-center">
            <TrustBar />
          </div>
        </div>
      </div>

      {/* 6. DESKTOP CONFIRMATION EMAIL & CONTINUE BUTTON (Image 1) */}
      <div className="d-none d-md-flex align-items-center justify-content-between px-4 py-3 bg-white border-top">
        <span className="text-dark" style={{ fontSize: '13.5px' }}>
          Order confirmation email will be sent to <strong className="text-danger">{confirmEmail}</strong>
        </span>
        <button 
          className="btn text-white fw-bold px-4 py-2 text-uppercase shadow-sm" 
          style={{ backgroundColor: '#0b53a1', fontSize: '14px', letterSpacing: '0.5px', borderRadius: '3px' }}
          onClick={onContinue}
        >
          CONTINUE
        </button>
      </div>

      {/* 7. MOBILE STICKY BOTTOM CONTINUE BUTTON (Image 2) */}
      <div 
        className="d-md-none position-fixed bottom-0 start-0 w-100 bg-white border-top shadow-lg"
        style={{ zIndex: 1040 }}
      >
        <button 
          type="button"
          className="btn w-100 py-3 text-white fw-bold text-uppercase rounded-0 shadow-sm" 
          style={{ 
            backgroundColor: '#0b53a1', 
            borderColor: '#0b53a1', 
            fontSize: '15px', 
            letterSpacing: '0.6px' 
          }}
          onClick={onContinue}
        >
          CONTINUE
        </button>
      </div>

    </div>
  );
};

export default OrderSummary;
