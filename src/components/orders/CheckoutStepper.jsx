import React from 'react';

/**
 * Three-node checkout progress bar: Address -> Order Summary -> Payment.
 *
 * checkoutStep 1 is the cart itself, which has no stepper. Steps 2/3/4 map to
 * nodes 1/2/3, so the number the shopper sees never matches the internal step.
 */
const STEPS = [
  { step: 2, label: 'Address' },
  { step: 3, label: 'Order Summary' },
  { step: 4, label: 'Payment' },
];

const THEME = '#0b53a1';

const CheckoutStepper = ({ currentStep, onStepClick }) => {
  if (currentStep < 2) return null;

  const handleStepClick = (targetStep) => {
    // Backwards only — you cannot skip ahead past an unfinished step.
    if (onStepClick && targetStep < currentStep) {
      onStepClick(targetStep);
    }
  };

  return (
    <div className="checkout-stepper bg-white px-3 pt-3 pb-2 border-bottom">
      <div className="d-flex align-items-start justify-content-between position-relative">
        {STEPS.map(({ step, label }, index) => {
          const isActive = currentStep === step;
          const isDone = currentStep > step;
          const isPast = isActive || isDone;
          const clickable = step < currentStep;

          return (
            <React.Fragment key={step}>
              {index > 0 && (
                <div
                  className="flex-grow-1"
                  style={{
                    height: '1px',
                    backgroundColor: isPast ? THEME : '#c9ced6',
                    marginTop: '13px',
                  }}
                  aria-hidden="true"
                />
              )}

              <button
                type="button"
                onClick={() => handleStepClick(step)}
                disabled={!clickable}
                className="btn p-0 border-0 bg-transparent d-flex flex-column align-items-center shadow-none"
                style={{ cursor: clickable ? 'pointer' : 'default', flex: '0 0 auto', width: '92px' }}
                aria-current={isActive ? 'step' : undefined}
              >
                <span
                  className="d-flex align-items-center justify-content-center rounded-circle"
                  style={{
                    width: '27px',
                    height: '27px',
                    fontSize: '13px',
                    fontWeight: 600,
                    lineHeight: 1,
                    border: `1.5px solid ${THEME}`,
                    backgroundColor: isPast ? THEME : '#fff',
                    color: isPast ? '#fff' : THEME,
                  }}
                >
                  {index + 1}
                </span>
                <span
                  className="text-center mt-1"
                  style={{
                    fontSize: '12px',
                    lineHeight: 1.25,
                    color: isPast ? '#1a1a1a' : '#7a828c',
                    fontWeight: isActive ? 600 : 400,
                  }}
                >
                  {label}
                </span>
              </button>
            </React.Fragment>
          );
        })}
      </div>
    </div>
  );
};

export default CheckoutStepper;
