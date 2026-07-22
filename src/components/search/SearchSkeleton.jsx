import React from 'react';

export const SearchSuggestionSkeleton = () => {
  return (
    <div className="p-2 d-flex flex-column gap-2">
      {[1, 2, 3, 4, 5].map((i) => (
        <div key={i} className="d-flex align-items-center gap-3 p-2 rounded-3 placeholder-glow">
          <div 
            className="placeholder rounded-2 flex-shrink-0" 
            style={{ width: '42px', height: '42px', backgroundColor: '#e2e8f0' }} 
          />
          <div className="flex-grow-1">
            <div className="placeholder col-7 rounded mb-1" style={{ height: '14px', backgroundColor: '#cbd5e1' }} />
            <div className="placeholder col-4 rounded" style={{ height: '10px', backgroundColor: '#e2e8f0' }} />
          </div>
          <div className="placeholder col-2 rounded" style={{ height: '14px', backgroundColor: '#cbd5e1' }} />
        </div>
      ))}
    </div>
  );
};

export const SearchGridSkeleton = () => {
  return (
    <div className="row g-4">
      {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
        <div key={i} className="col-12 col-sm-6 col-md-4 col-lg-3">
          <div className="card border-0 shadow-sm rounded-4 p-3 h-100 placeholder-glow">
            <div 
              className="placeholder rounded-3 mb-3 w-100" 
              style={{ height: '180px', backgroundColor: '#e2e8f0' }} 
            />
            <div className="placeholder col-10 rounded mb-2" style={{ height: '16px', backgroundColor: '#cbd5e1' }} />
            <div className="placeholder col-6 rounded mb-3" style={{ height: '12px', backgroundColor: '#e2e8f0' }} />
            <div className="d-flex justify-content-between align-items-center mt-auto">
              <div className="placeholder col-4 rounded" style={{ height: '20px', backgroundColor: '#cbd5e1' }} />
              <div className="placeholder col-3 rounded-pill" style={{ height: '32px', backgroundColor: '#e2e8f0' }} />
            </div>
          </div>
        </div>
      ))}
    </div>
  );
};

export default { SearchSuggestionSkeleton, SearchGridSkeleton };
