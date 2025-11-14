import React, { useState } from 'react';
import 'bootstrap/dist/css/bootstrap.min.css';
// import './orders.css'; // Assuming this contains custom styles like .custom-pay-btn and .bg-theme

const paymentOptions = [
  { id: 'upi', title: 'UPI', subtitle: 'Pay by any UPI app', offers: 'Save upto ₹50 • 5 offers available', iconClass: 'bi-phone', offerColor: 'text-success' },
  { id: 'card', title: 'Credit / Debit / ATM Card', subtitle: 'Add and secure cards as per RBI guidelines', offers: 'Get upto 5% cashback* • 2 offers available', iconClass: 'bi-credit-card', offerColor: 'text-success' },
  { id: 'emi', title: 'EMI', subtitle: 'Pay via your bank account', offers: null, iconClass: 'bi-calendar-check', offerColor: '' },
  { id: 'netbanking', title: 'Net Banking', subtitle: null, offers: null, iconClass: 'bi-bank', offerColor: '' },
  { id: 'cod', title: 'Cash on Delivery', subtitle: 'Pay ₹483 as advance and balance amount as Cash on delivery', offers: null, iconClass: 'bi-cash-stack', offerColor: '' },
  { id: 'giftcard', title: 'Have a Flipkart Gift Card?', subtitle: null, offers: null, iconClass: 'bi-gift', offerColor: '' },
];

const finalTotalAmount = 1649; // Mock total for button display

const PaymentGateway = ({ onPaymentSuccess }) => {
  const [activeMethod, setActiveMethod] = useState('upi');

  const handlePayment = () => {
    // In a real app, this would trigger the payment process
    console.log(`Initiating payment using ${activeMethod} for ₹${finalTotalAmount}`);
    if (onPaymentSuccess) onPaymentSuccess();
  };

  const getButtonText = (method) => {
    if (method === 'upi' || method === 'card') {
      return `Proceed To Pay ₹${finalTotalAmount.toLocaleString('en-IN')}`;
    }
    // For methods like COD or Net Banking
    return 'Complete Payment';
  };


  // Removed internal payment button
  const UPIForm = () => (
    <div className="py-2">
      <div className="d-flex align-items-center mb-4">
        <input type="radio" id="new-upi" name="upi-option" defaultChecked className="me-2 custom-radio" />
        <label htmlFor="new-upi" className="fw-bold me-auto">Add new UPI ID</label>
        <a href="#" className="text-decoration-none" style={{ color: '#1a73e8', fontSize: '13px' }}>
          How to find?
        </a>
      </div>

      <div className="custom-upi-input-group p-2 mb-3">
        <label className="custom-upi-label">UPI ID</label>
        <div className="d-flex align-items-center">
          <input
            type="text"
            placeholder="Enter your UPI ID"
            className="form-control border-0 shadow-none py-2"
          />
          <button className="rounded border custom-verify-btn ms-2 px-3 py-2 btn border fw-bold">
            Verify
          </button>
        </div>
      </div>
    </div>
  );

  // Removed internal payment button
  const PlaceholderForm = ({ title }) => (
    <div className="p-2">
      <p className="text-muted">Content for <strong>{title}</strong> goes here.</p>
    </div>
  );

  const renderFormContent = () => {
    switch (activeMethod) {
      case 'upi': return <UPIForm />;
      case 'card': return <PlaceholderForm title="Credit / Debit / ATM Card" />;
      case 'emi': return <PlaceholderForm title="EMI" />;
      case 'netbanking': return <PlaceholderForm title="Net Banking" />;
      case 'cod': return <PlaceholderForm title="Cash on Delivery" />;
      case 'giftcard': return <PlaceholderForm title="Flipkart Gift Card" />;
      default: return null;
    }
  };

  return (
    <>
      <div className="d-flex flex-column flex-lg-row border bg-white shadow-sm custom-component-box">
        {/* Left Column */}
        <div className="custom-left-panel custom-left-panel-mobile p-0">
          {paymentOptions.map((option) => (
            <div
              key={option.id}
              className={`p-3 border-bottom custom-option-item ${activeMethod === option.id ? 'active' : ''}`}
              onClick={() => setActiveMethod(option.id)}
              role="button"
            >
              <div className="d-flex align-items-start">
                <div className="me-3 custom-icon-placeholder">
                  <i className={`bi ${option.iconClass}`}></i>
                </div>
                <div className="text-content flex-grow-1">
                  <div className="fw-bold mb-1" style={{ fontSize: '14px' }}>{option.title}</div>
                  {option.subtitle && <div className="text-muted mb-1" style={{ fontSize: '12px' }}>{option.subtitle}</div>}
                  {option.offers && (
                    <div className="offers" style={{ fontSize: '12px' }}>
                      <span className={option.offerColor}>{option.offers.split(' • ')[0]}</span>
                      {' • '}
                      <span className="text-muted">{option.offers.split(' • ')[1]}</span>
                    </div>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Right Column */}
        <div className="custom-right-panel p-4 flex-grow-1">
          {renderFormContent()}

          {/* 🛑 INLINE BUTTON (Visible only on large screens) */}
          <div className="d-none d-lg-flex align-items-center justify-content-center  pt-3 mt-4 px-0 px-xl-5">
            <button
              className=" py-3 fw-bold custom-pay-btn border w-100 w-lg-50"
              onClick={handlePayment}
            >
              {getButtonText(activeMethod)}
            </button>
          </div>

          {/* 🛑 SPACER (Visible only on small screens to prevent fixed bar overlap) */}
          <div className="d-lg-none" style={{ height: '70px' }}></div>

        </div>
      </div>

      {/* 🛑 FIXED BUTTON BAR (Visible only on small screens) */}
      <div
        className="fixed-bottom-bar d-lg-none"
        style={{
          position: 'fixed',
          bottom: 0,
          left: 0,
          right: 0,
          padding: '10px 15px',
          backgroundColor: 'white',
          boxShadow: '0 -2px 5px rgba(0,0,0,0.1)',
          zIndex: 10
        }}
      >
        <button
          className="btn w-100 py-2 fw-bold custom-pay-btn" // Assuming custom-pay-btn has background color
          onClick={handlePayment}
          style={{ backgroundColor: '#ff9800', color: 'white', border: 'none' }} // Fallback style for clarity
        >
          {getButtonText(activeMethod)}
        </button>
      </div>
    </>
  );
};

export default PaymentGateway;
