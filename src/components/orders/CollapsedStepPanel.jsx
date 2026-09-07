import React from 'react';

const CollapsedStepPanel = ({ stepNumber, title, summaryText, subText, onChange, isCompleted }) => {
  return (
    <div 
      className="card border-light shadow-sm mb-3 rounded-0 d-none d-md-block" 
      onClick={onChange} 
      style={{ cursor: onChange ? 'pointer' : 'default' }}
    >
      <div className="card-body px-4 py-3 d-flex justify-content-between align-items-center bg-white">
        
        {/* Left Side: Number & Title */}
        <div className="d-flex align-items-center gap-3">
          <div 
            className="d-flex align-items-center justify-content-center text-white" 
            style={{ width: '22px', height: '22px', fontSize: '13px', fontWeight: 'bold', backgroundColor: '#0b53a1' }}
          >
            {stepNumber}
          </div>
          <span className="fw-semibold" style={{ fontSize: '15px', color: '#333' }}>
            {title}
          </span>
        </div>
        
        {/* Right Side: Subtext (e.g. Buyer Mobile no.) */}
        {subText && (
          <div className="text-secondary" style={{ fontSize: '14px' }}>
            {subText}
          </div>
        )}

      </div>
    </div>
  );
};

export default CollapsedStepPanel;
