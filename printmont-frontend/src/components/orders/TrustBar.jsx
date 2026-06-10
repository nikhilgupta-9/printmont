import React from 'react';
import { BsShieldCheck } from 'react-icons/bs';

const TrustBar = () => {
  return (
    <div className="trust-bar bg-transparent py-3 text-start">
      <div className="d-flex align-items-center mb-2 gap-2 text-secondary fw-semibold">
        <BsShieldCheck size={28} className="text-secondary" />
        <span className="small lh-sm" style={{ fontSize: '13px' }}>
          100% Safe and secure payments. Easy returns. 100% Authentic products.
        </span>
      </div>
      <div className="payment-logos d-flex gap-2 align-items-center mt-3 opacity-75">
        <img src="https://upload.wikimedia.org/wikipedia/commons/4/41/Visa_Logo.png" alt="Visa" height="12" />
        <img src="https://upload.wikimedia.org/wikipedia/commons/a/a4/Mastercard_2019_logo.svg" alt="MasterCard" height="16" />
        {/* Placeholder images for other cards. We can use real icons later */}
        <span className="small border rounded px-1 fw-bold" style={{ fontSize: '10px' }}>RuPay</span>
        <span className="small border rounded px-1 fw-bold" style={{ fontSize: '10px' }}>UPI</span>
        <span className="small border rounded px-1 fw-bold" style={{ fontSize: '10px' }}>NetBanking</span>
      </div>
    </div>
  );
};

export default TrustBar;
