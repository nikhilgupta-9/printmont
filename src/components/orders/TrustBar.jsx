import React from 'react';
import { BsShieldFillCheck } from 'react-icons/bs';

const TrustBar = () => {
  return (
    <div className="trust-bar bg-transparent py-1 text-center text-md-start">
      {/* 100% Safe and Secure Badge */}
      <div className="d-flex align-items-center justify-content-center justify-content-md-start mb-2 gap-2">
        <BsShieldFillCheck size={28} style={{ color: '#475569' }} className="flex-shrink-0" />
        <div className="text-start" style={{ fontSize: '12px', color: '#475569', lineHeight: '1.3', fontWeight: '600' }}>
          <div>100% Safe and secure payments Easy</div>
          <div>returns.100% Authentic products.</div>
        </div>
      </div>
      
      {/* Payment Logos Image Strip */}
      <div className="payment-logos d-flex justify-content-center justify-content-md-start mt-2 px-1">
        <img 
          src="/payment-method.svg" 
          alt="Supported Payment Methods - Visa, MasterCard, Maestro, Amex, Discover, RuPay, NetBanking, Cash on Delivery, EMI Options" 
          className="img-fluid w-100"
          style={{ maxWidth: '360px', height: 'auto', objectFit: 'contain' }}
        />
      </div>
    </div>
  );
};

export default TrustBar;
