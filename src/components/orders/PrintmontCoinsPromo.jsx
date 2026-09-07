import React from 'react';

const PrintmontCoinsPromo = () => {
  return (
    <div className="pm-coins-promo bg-white p-3 border mb-3 shadow-sm rounded-1" style={{ borderColor: '#e5e5e5' }}>
      <div className="d-flex flex-wrap align-items-center justify-content-center justify-content-xl-between gap-3">
        
        {/* Left Logo Section */}
        <div className="coins-logo d-flex align-items-center justify-content-center">
          <span className="fw-bold fst-italic me-2" style={{ color: '#0b53a1', fontSize: '20px' }}>Printmont</span>
          <img src="/printmont-coin.png" alt="Coin" height="30" className="me-2" />
          <span className="fw-bold text-dark" style={{ fontSize: '16px' }}>Coin</span>
        </div>

        {/* Right Text Section */}
        <div className="lh-base text-dark text-center text-xl-start flex-grow-1" style={{ fontSize: '13px', minWidth: '130px' }}>
          <div className="fw-bold mb-1">
            For Every ₹100 Spent, you earn <span className="rounded-circle d-inline-flex align-items-center justify-content-center border border-secondary" style={{width: '18px', height: '18px', fontSize: '11px', fontWeight: 'bold'}}>2</span> Printmont Coins
          </div>
          <div className="text-muted" style={{ fontSize: '11px' }}>
            Max 50 Coin per order
          </div>
        </div>
        
      </div>
    </div>
  );
};

export default PrintmontCoinsPromo;
