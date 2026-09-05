import React, { useState } from 'react';

const PriceDetails = ({ 
  itemCount = 0, 
  totalPrice = 0, 
  discount = 0, 
  couponApplied = 0, 
  deliveryCharges = 0, 
  cashCoinsApplied = 0,
  isCart = false,
  isMobile = false
}) => {
  const totalAmount = totalPrice - discount - couponApplied + deliveryCharges - cashCoinsApplied;
  const totalSavings = discount + couponApplied + cashCoinsApplied;

  // The cart shows "Total Amount"; once checkout starts it becomes what is
  // actually being charged.
  const totalLabel = isCart ? "Total Amount :" : "Total Payable";

  return (
    <div className={isMobile ? "price-details-inner" : "price-details-card bg-white p-3 mb-3 border border-light shadow-sm rounded-0"}>
      
      {!isMobile && (
        <>
          {/* YOUR ORDERS Header added here to be in the same box */}
          <h6 className="text-dark fw-bold text-uppercase text-center mb-4 mt-2" style={{ fontSize: '15px' }}>YOUR ORDERS</h6>
          
          <h6 className="fw-bold border-bottom pb-3 mb-3 text-uppercase" style={{ fontSize: '14px', color: '#0b53a1' }}>
            PRICE DETAILS.
          </h6>
        </>
      )}
      
      <div className="d-flex justify-content-between mb-3 small fs-6 text-dark">
        <span>Price ({itemCount} items)</span>
        <span>₹{totalPrice.toLocaleString('en-IN')}</span>
      </div>
      
      <div className="d-flex justify-content-between mb-3 small fs-6 text-dark">
        <span>Discount :</span>
        <span className="text-success">-₹{discount.toLocaleString('en-IN')}</span>
      </div>
      
      {couponApplied > 0 && (
        <div className="d-flex justify-content-between mb-3 small fs-6 text-dark">
          <span>Coupon Applied :</span>
          <span className="text-success">-₹{couponApplied.toLocaleString('en-IN')}</span>
        </div>
      )}
      
      <div className="d-flex justify-content-between mb-3 small fs-6 text-dark">
        <span>Delivery Charges :</span>
        <span className="text-success">
          {deliveryCharges === 0 ? (
            <>
              <span className="text-decoration-line-through text-muted me-1">₹160</span> Free
            </>
          ) : (
            `₹${deliveryCharges.toLocaleString('en-IN')}`
          )}
        </span>
      </div>

      {cashCoinsApplied > 0 && (
        <div className="d-flex justify-content-between mb-3 small fs-6 text-dark">
          <span>Cash Coins Applied :</span>
          <span className="text-success">-₹{cashCoinsApplied.toLocaleString('en-IN')}</span>
        </div>
      )}
      
      <div className="d-flex justify-content-between border-top py-3 my-3 fw-bold fs-5 text-dark" style={{ borderBottom: isMobile ? 'none' : '1px solid #dee2e6' }}>
        <span>{totalLabel}</span>
        <span>₹{totalAmount.toLocaleString('en-IN')}</span>
      </div>
      
      {/* Coupon box lives in the cart's desktop sidebar only. */}
      {isCart && !isMobile && (
        <div className="d-flex align-items-stretch mb-3 mt-4" style={{ border: '1px solid #ccc' }}>
          <input 
            type="text" 
            placeholder="Have a Discount Coupon?" 
            className="form-control border-0 rounded-0 shadow-none px-2"
            style={{ fontSize: '14px', color: '#1a73e8' }} 
          />
          <button className="btn rounded-0 fw-bold border-start px-3" style={{ fontSize: '14px', backgroundColor: '#e7eefa', color: '#0b53a1' }}>
            APPLIED
          </button>
        </div>
      )}

      {totalSavings > 0 && (
        <div className="text-success fw-bold small fs-6">
          You will save ₹{totalSavings.toLocaleString('en-IN')} on this order
        </div>
      )}
    </div>
  );
};

export default PriceDetails;
