import React from 'react';
import { useCheckout } from '../../context/CheckoutContext';

const paymentOptions = [
  { id: 'upi', title: 'UPI', subtitle: 'Pay by any UPI app', offers: 'Save upto ₹50 • 5 offers available', icon: '📱' },
  { id: 'card', title: 'Credit / Debit / ATM Card', subtitle: 'Add and secure cards as per RBI guidelines', offers: 'Get upto 5% cashback* • 2 offers available', icon: '💳' },
  { id: 'netbanking', title: 'Net Banking', subtitle: null, offers: null, icon: '🏦' },
  { id: 'cod', title: 'Cash on Delivery', subtitle: 'Pay ₹160 as advance and balance amount as Cash on delivery', offers: null, icon: '💵' }
];

const PaymentGateway = ({ onPaymentSuccess }) => {
  const { paymentMethod, setPaymentMethod, cartTotals, submitOrder } = useCheckout();

  const handlePayment = async () => {
    // Call the context function which posts to the backend API
    await submitOrder();
    if (onPaymentSuccess) onPaymentSuccess();
  };

  const getButtonText = (method) => {
    if (method === 'upi' || method === 'card' || method === 'netbanking') {
      return `PAY ₹${cartTotals.totalPayable.toLocaleString('en-IN')}`;
    }
    return 'CONFIRM ORDER';
  };

  const UPIForm = () => (
    <div className="pt-3 pb-2 border-top mt-3">
      <div className="d-flex align-items-center mb-3">
        <input type="radio" id="new-upi" name="upi-option" defaultChecked className="form-check-input mt-0 me-2 shadow-none border-2" />
        <label htmlFor="new-upi" className="fw-semibold me-auto text-dark" style={{fontSize: '14px'}}>Add new UPI ID</label>
        <a href="#how-to" className="text-decoration-none fw-semibold" style={{ color: '#0b53a1', fontSize: '13px' }}>How to find?</a>
      </div>
      <div className="d-flex align-items-center mb-3">
        <input type="text" placeholder="Enter your UPI ID" className="form-control shadow-none bg-white border" />
        <button className="btn btn-outline-secondary ms-2 fw-semibold px-3 text-uppercase" style={{fontSize: '14px'}}>Verify</button>
      </div>
    </div>
  );

  const PlaceholderForm = ({ title }) => (
    <div className="pt-3 pb-2 border-top mt-3">
      <p className="text-muted small mb-0">Enter details for <strong>{title}</strong> here.</p>
    </div>
  );

  const renderFormContent = (method) => {
    switch (method) {
      case 'upi': return <UPIForm />;
      case 'card': return <PlaceholderForm title="Credit / Debit / ATM Card" />;
      case 'netbanking': return <PlaceholderForm title="Net Banking" />;
      case 'cod': return <PlaceholderForm title="Cash on Delivery" />;
      default: return null;
    }
  };

  return (
    <div className="bg-white">
      
      {/* Payment Options Accordion */}
      <div className="payment-options">
        {paymentOptions.map((option) => (
          <div key={option.id} className={`p-3 border-bottom ${paymentMethod === option.id ? 'bg-light' : 'bg-white'}`}>
            <div 
              className="d-flex align-items-start" 
              onClick={() => setPaymentMethod(option.id)} 
              style={{ cursor: 'pointer' }}
            >
              <div className="me-3 mt-1">
                <input 
                  type="radio" 
                  className="form-check-input shadow-none border-2" 
                  checked={paymentMethod === option.id} 
                  onChange={() => setPaymentMethod(option.id)} 
                  style={{ width: '18px', height: '18px' }}
                />
              </div>
              <div className="flex-grow-1">
                <div className="d-flex align-items-center mb-1">
                  <span className="me-2 fs-5">{option.icon}</span>
                  <span className="fw-semibold text-dark" style={{ fontSize: '15px' }}>{option.title}</span>
                </div>
                {option.subtitle && <div className="text-muted mb-1" style={{ fontSize: '12px' }}>{option.subtitle}</div>}
                {option.offers && (
                  <div className="text-success fw-semibold" style={{ fontSize: '12px' }}>
                    {option.offers}
                  </div>
                )}
                
                {/* Expanded Form Content */}
                {paymentMethod === option.id && renderFormContent(option.id)}

              </div>
            </div>
          </div>
        ))}
      </div>

      {/* 🛑 INLINE BUTTON (Desktop) */}
      <div className="d-none d-md-flex justify-content-end p-3 bg-white mt-2">
        <button
          className="btn btn-theme text-white fw-bold px-5 py-2 text-uppercase"
          style={{ backgroundColor: '#0b53a1', fontSize: '16px' }}
          onClick={handlePayment}
        >
          {getButtonText(paymentMethod)}
        </button>
      </div>

      {/* Spacer for Mobile */}
      <div className="d-md-none" style={{ height: '70px' }}></div>

      {/* 🛑 FIXED BUTTON BAR (Mobile) */}
      <div className="d-md-none fixed-bottom bg-white border-top shadow-lg z-3">
        <div className="d-flex align-items-center justify-content-between p-3" style={{ padding: '0 !important' }}>
          <div className="flex-fill ps-3 bg-white h-100 d-flex flex-column justify-content-center border-end">
             <span className="text-muted small fw-semibold" style={{fontSize: '11px'}}>Total Amount</span>
             <span className="fw-bold fs-5 text-dark lh-1">₹{cartTotals.totalPayable.toLocaleString('en-IN')}</span>
          </div>
          <button
            className="btn btn-theme flex-fill py-3 fw-bold text-uppercase text-white rounded-0"
            onClick={handlePayment}
            style={{ backgroundColor: '#0b53a1', fontSize: '15px' }}
          >
            {getButtonText(paymentMethod).replace(`₹${cartTotals.totalPayable.toLocaleString('en-IN')}`, '').trim() || 'PAY NOW'} 
            {/* The button will say PAY on right, amount on left for mobile */}
            {getButtonText(paymentMethod).includes('PAY') && ' PAY'}
          </button>
        </div>
      </div>

    </div>
  );
};

export default PaymentGateway;
