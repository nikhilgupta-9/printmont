import React from 'react';

const CouponSection = ({ isApplied, onApply, onRemove }) => {
  return (
    <div className="coupon-section d-flex align-items-center justify-content-between p-2 bg-white border border-light">
      <div className="text-theme fs-6 d-flex align-items-center w-100 me-2" style={{ color: '#1a73e8' }}>
        <input 
          type="text" 
          placeholder="Have a Discount Coupon?" 
          className="form-control border-0 shadow-none px-2 text-theme placeholder-theme bg-transparent"
          style={{ fontSize: '14px' }}
          disabled={isApplied}
        />
      </div>
      <button 
        className={`btn btn-sm px-3 fw-bold flex-shrink-0 ${isApplied ? 'text-white' : 'text-theme bg-light'}`}
        onClick={isApplied ? onRemove : onApply}
        style={{ 
          backgroundColor: isApplied ? '#1a73e8' : '#f8f9fa', 
          borderColor: '#e0e0e0',
          fontSize: '13px'
        }}
      >
        {isApplied ? 'APPLIED' : 'APPLY'}
      </button>
    </div>
  );
};

export default CouponSection;
