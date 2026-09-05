import React from 'react';
import CartProductCard from './CartProductCard';
import PriceDetails from './PriceDetails';
import PrintmontCoinsPromo from './PrintmontCoinsPromo';
import TrustBar from './TrustBar';
import { useCheckout } from '../../context/CheckoutContext';

const CASH_COINS_DISCOUNT = 20;
const COINS_SPENT = 20;

/**
 * Step 3 of checkout: spend coins if wanted, check the items, continue to
 * payment. The delivery address is shown by the collapsed step-2 bar that
 * Cart renders above this, so it is not repeated here.
 *
 * The product cards are the cart's own cards in read-only mode so the two
 * screens stay identical.
 */
const OrderSummary = ({ onContinue }) => {
  const {
    cartItems,
    useCashCoins, setUseCashCoins,
    PRINTMONT_COINS_BALANCE,
    cartTotals,
    buyerDetails,
  } = useCheckout();

  const confirmEmail = buyerDetails?.email || 'your registered email';

  return (
    <div className="order-summary-step bg-white">

      {/* Cash coins */}
      <div className="px-3 px-md-4 py-3 border-bottom">
        <div className="d-flex align-items-center gap-3">
          <span className="cash-coins-head">Pay Using Cash Coins</span>
          <button
            type="button"
            className={`coins-applied-btn ms-auto${useCashCoins ? '' : ' coins-applied-btn--off'}`}
            onClick={() => setUseCashCoins(!useCashCoins)}
          >
            {useCashCoins ? 'APPLIED' : 'APPLY'}
          </button>
        </div>

        <div className="d-flex align-items-center gap-1 mt-1" style={{ fontSize: '13px' }}>
          <span className="text-dark">Balance</span>
          <img src="/printmont-coin.png" width={14} height={14} alt="" />
          <span className="fw-semibold text-dark">{PRINTMONT_COINS_BALANCE}</span>
        </div>

        <p className="text-success mb-0" style={{ fontSize: '13px' }}>
          Save {CASH_COINS_DISCOUNT} using {COINS_SPENT} Printmont Coins
        </p>
      </div>

      {/* Items */}
      <div className="pt-2">
        {cartItems.map((item) => (
          <CartProductCard key={item.id} item={item} readOnly />
        ))}
      </div>

      {/* Price details — desktop gets these in the sidebar instead. */}
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

      {/* Desktop keeps the action inline next to the confirmation note. */}
      <div className="d-none d-md-flex align-items-center gap-3 px-4 py-3 border-top">
        <span className="order-confirm-note">
          Order confirmation email will be sent to <strong>{confirmEmail}</strong>
        </span>
        <button className="btn checkout-cta ms-auto" onClick={onContinue}>CONTINUE</button>
      </div>

      <div className="d-md-none checkout-bottom-bar">
        <button className="btn checkout-cta w-100" onClick={onContinue}>CONTINUE</button>
      </div>

    </div>
  );
};

export default OrderSummary;
