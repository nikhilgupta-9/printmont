import React from 'react';
import { Link } from 'react-router-dom';

const PrintmontCoinsPromo = () => {
  return (
    <div className="pm-coins-promo bg-white p-2 p-md-3 border rounded-2 mb-3 shadow-xs" style={{ borderColor: '#e2e8f0' }}>
      <Link to="/user/wallet" className="d-block text-decoration-none">
        <img 
          src="/printmont-coin-banner.png" 
          alt="For every ₹100 Spent, you earn 2 Printmont Coins. Max 50 coins per order." 
          className="img-fluid w-100" 
          style={{ maxHeight: '72px', objectFit: 'contain', display: 'block', margin: '0 auto' }}
          onError={(e) => {
            // Graceful fallback if image is missing
            e.target.style.display = 'none';
          }}
        />
      </Link>
    </div>
  );
};

export default PrintmontCoinsPromo;
