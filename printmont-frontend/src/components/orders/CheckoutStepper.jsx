import React from 'react';

const CheckoutStepper = ({ currentStep, onStepClick }) => {
  // Mobile only stepper. Visible on steps 2, 3, 4, 5.
  if (currentStep === 1) return null;

  const handleStepClick = (targetStep) => {
    // Only allow clicking back to previous steps
    if (onStepClick && targetStep < currentStep) {
      onStepClick(targetStep);
    }
  };



  return (
    <div className="checkout-stepper bg-white p-3 d-md-none border-bottom">
      <div className="d-flex justify-content-between position-relative">
        {/* Background Line */}
        <div className="position-absolute start-0" style={{ top: '12px', height: '2px', backgroundColor: '#e0e0e0', zIndex: 0, left: '12.5%', right: '12.5%' }}></div>
        
        {/* Progress Line */}
        <div className="position-absolute start-0 bg-theme" 
             style={{ 
               top: '12px',
               height: '2px', 
               zIndex: 0, 
               left: '12.5%', 
               width: currentStep === 2 ? '0%' : currentStep === 3 ? '25%' : currentStep === 4 ? '50%' : '75%',
               transition: 'width 0.3s ease'
             }}>
        </div>

        {/* Node 1: Buyer Details (Step 2) */}
        <div 
          className="step-item d-flex flex-column align-items-center position-relative" 
          style={{ zIndex: 1, width: '25%', cursor: currentStep > 2 ? 'pointer' : 'default' }}
          onClick={() => handleStepClick(2)}
        >
          <div className={`step-circle rounded-circle d-flex align-items-center justify-content-center text-white mb-1 ${currentStep >= 2 ? 'bg-theme' : 'bg-secondary'}`} style={{ width: '24px', height: '24px', fontSize: '12px' }}>
            {currentStep > 2 ? '✓' : '1'}
          </div>
          <span className="small text-center" style={{ fontSize: '10px', color: currentStep >= 2 ? '#000' : '#888', fontWeight: currentStep >= 2 ? '600' : 'normal' }}>Buyer</span>
        </div>

        {/* Node 2: Address (Step 3) */}
        <div 
          className="step-item d-flex flex-column align-items-center position-relative" 
          style={{ zIndex: 1, width: '25%', cursor: currentStep > 3 ? 'pointer' : 'default' }}
          onClick={() => handleStepClick(3)}
        >
          <div className={`step-circle rounded-circle d-flex align-items-center justify-content-center text-white mb-1 ${currentStep >= 3 ? 'bg-theme' : 'bg-secondary'}`} style={{ width: '24px', height: '24px', fontSize: '12px', backgroundColor: currentStep < 3 ? '#bdbdbd' : '' }}>
            {currentStep > 3 ? '✓' : '2'}
          </div>
          <span className="small text-center" style={{ fontSize: '10px', color: currentStep >= 3 ? '#000' : '#888', fontWeight: currentStep >= 3 ? '600' : 'normal' }}>Address</span>
        </div>

        {/* Node 3: Order Summary (Step 4) */}
        <div 
          className="step-item d-flex flex-column align-items-center position-relative" 
          style={{ zIndex: 1, width: '25%', cursor: currentStep > 4 ? 'pointer' : 'default' }}
          onClick={() => handleStepClick(4)}
        >
          <div className={`step-circle rounded-circle d-flex align-items-center justify-content-center text-white mb-1 ${currentStep >= 4 ? 'bg-theme' : 'bg-secondary'}`} style={{ width: '24px', height: '24px', fontSize: '12px', backgroundColor: currentStep < 4 ? '#bdbdbd' : '' }}>
            {currentStep > 4 ? '✓' : '3'}
          </div>
          <span className="small text-center" style={{ fontSize: '10px', color: currentStep >= 4 ? '#000' : '#888', fontWeight: currentStep >= 4 ? '600' : 'normal' }}>Summary</span>
        </div>

        {/* Node 4: Payment (Step 5) */}
        <div 
          className="step-item d-flex flex-column align-items-center position-relative" 
          style={{ zIndex: 1, width: '25%', cursor: 'default' }}
          onClick={() => handleStepClick(5)}
        >
          <div className={`step-circle rounded-circle d-flex align-items-center justify-content-center text-white mb-1 ${currentStep >= 5 ? 'bg-theme' : 'bg-secondary'}`} style={{ width: '24px', height: '24px', fontSize: '12px', backgroundColor: currentStep < 5 ? '#bdbdbd' : '' }}>
            4
          </div>
          <span className="small text-center" style={{ fontSize: '10px', color: currentStep >= 5 ? '#000' : '#888', fontWeight: currentStep >= 5 ? '600' : 'normal' }}>Payment</span>
        </div>
      </div>
    </div>
  );
};

export default CheckoutStepper;
