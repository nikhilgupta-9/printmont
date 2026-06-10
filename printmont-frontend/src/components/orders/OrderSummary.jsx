import React from 'react';
import OrderProductCard from './OrderProductCard';
import { useCheckout } from '../../context/CheckoutContext';

const OrderSummary = ({ onContinue }) => {
  const { cartItems, useCashCoins, setUseCashCoins, PRINTMONT_COINS_BALANCE } = useCheckout();

  const handleContinue = () => {
    // Trigger next step (Payment)
    if (onContinue) onContinue();
  };

  return (
    <div className='bg-white'>
      
      {/* Cash Coins Toggle Section */}
      <div className='p-3 d-flex justify-content-between align-items-center border-bottom'>
        <div>
          <h6 className='fw-bold mb-1' style={{ color: '#0b53a1', fontSize: '15px' }}>Pay Using Cash Coins</h6>
          <div className='d-flex align-items-center gap-1 mb-1'>
            <span className='small fw-semibold'>Balance</span>
            <img src="/printmont-coin.png" width={16} alt="coin" />
            <span className='small fw-bold'>{PRINTMONT_COINS_BALANCE}</span>
          </div>
          <p className='small text-success my-0 fw-semibold' style={{ fontSize: '12px' }}>
            Save ₹{PRINTMONT_COINS_BALANCE} Using from {PRINTMONT_COINS_BALANCE} SuperCoins
          </p>
        </div>
        <div>
          <button 
            className={`btn fw-bold px-4 py-2 border-0 ${useCashCoins ? 'bg-success text-white' : 'btn-light text-theme'}`} 
            onClick={() => setUseCashCoins(!useCashCoins)} 
            style={{ minWidth: '100px', fontSize: '14px', backgroundColor: useCashCoins ? '#198754' : '#f8f9fa', color: useCashCoins ? '#fff' : '#0b53a1' }}
          >
            {useCashCoins ? "APPLIED" : "APPLY"}
          </button>
        </div>
      </div>

      {/* Cart Items List (Read Only) */}
      <div className="p-3 bg-white">
        {cartItems.map(item => (
          <OrderProductCard key={item.id} item={item} />
        ))}
      </div>

      {/* Email confirmation message (Desktop only typically, but adding for fidelity) */}
      <div className="px-3 pb-3">
        <p className="small text-muted mb-0">Order confirmation email will be sent to <span className="fw-semibold text-dark">amit@example.com</span></p>
      </div>

      {/* 🛑 INLINE BUTTON - Visible ONLY on medium (md) and larger screens */}
      <div className="d-none d-md-flex justify-content-end p-3 mt-2 bg-white border-top">
        <button
          className="btn btn-theme text-white fw-bold px-5 py-2"
          style={{ backgroundColor: '#0b53a1', fontSize: '16px' }}
          onClick={handleContinue}
        >
          CONTINUE
        </button>
      </div>

      {/* 🛑 FIXED BUTTON BAR - Visible ONLY on small screens */}
      <div className="d-md-none fixed-bottom bg-white border-top shadow-lg z-3">
        <button
          className="btn btn-theme w-100 py-3 fw-bold text-uppercase text-white rounded-0"
          style={{ backgroundColor: '#0b53a1', fontSize: '15px' }}
          onClick={handleContinue}
        >
          Continue
        </button>
      </div>

    </div>
  );
};

export default OrderSummary;