import React from 'react';
import { useNavigate } from 'react-router-dom';
import { IoArrowBack, IoNotificationsOutline, IoCartOutline, IoSearchOutline, IoPersonOutline } from 'react-icons/io5';

const MobileCheckoutHeader = ({ title = "My Carts", itemCount, step }) => {
  const navigate = useNavigate();
  
  return (
    <div className="mobile-checkout-header bg-theme text-white p-2 d-md-none sticky-top">
      <div className="d-flex justify-content-between align-items-center">
        <div className="d-flex align-items-center gap-2">
          <IoArrowBack size={24} onClick={() => navigate(-1)} role="button" />
          <div className="bg-white rounded p-1 d-flex align-items-center justify-content-center" style={{ width: '32px', height: '32px' }}>
            <span className="text-theme fw-bold small">PM</span>
          </div>
        </div>
        
        <div className="fw-bold fs-6">
          {title} {itemCount ? `(${itemCount})` : ''}
        </div>
        
        <div className="d-flex align-items-center gap-3">
          {step === 1 ? (
            <>
              <IoNotificationsOutline size={20} />
              <IoCartOutline size={20} />
              <IoSearchOutline size={20} />
              <IoPersonOutline size={20} />
            </>
          ) : (
            <div className="d-flex align-items-center small">
              <IoPersonOutline size={18} className="me-1" />
              {step === 4 ? "Buyer name" : ""}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default MobileCheckoutHeader;
