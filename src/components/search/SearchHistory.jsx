import React from 'react';
import { AiOutlineClockCircle, AiOutlineClose } from 'react-icons/ai';
import { HiOutlineFire } from 'react-icons/hi';

const SearchHistory = ({
  history = [],
  popularSearches = [],
  onSelectTerm,
  onRemoveHistoryItem,
  onClearHistory
}) => {
  return (
    <div className="p-3">
      {/* Recent Searches Section */}
      {history.length > 0 && (
        <div className="mb-3">
          <div className="d-flex align-items-center justify-content-between mb-2">
            <div className="d-flex align-items-center gap-1.5 text-secondary fw-semibold uppercase" style={{ fontSize: '0.75rem', letterSpacing: '0.05em' }}>
              <AiOutlineClockCircle style={{ fontSize: '0.9rem' }} />
              <span>RECENT SEARCHES</span>
            </div>
            <button
              type="button"
              className="btn btn-link text-primary p-0 border-0 text-decoration-none small"
              onClick={onClearHistory}
              style={{ fontSize: '0.75rem', fontWeight: 500 }}
            >
              Clear All
            </button>
          </div>

          <div className="d-flex flex-column gap-1">
            {history.map((term, idx) => (
              <div
                key={idx}
                className="d-flex align-items-center justify-content-between py-1.5 px-2.5 rounded-2 hover-bg-light cursor-pointer"
                onClick={() => onSelectTerm(term)}
                style={{ cursor: 'pointer', transition: 'background-color 0.15s ease' }}
              >
                <div className="d-flex align-items-center gap-2 text-dark" style={{ fontSize: '0.875rem' }}>
                  <AiOutlineClockCircle className="text-secondary" style={{ fontSize: '0.85rem' }} />
                  <span>{term}</span>
                </div>
                <button
                  type="button"
                  className="btn btn-link text-secondary p-0 border-0"
                  onClick={(e) => {
                    e.stopPropagation();
                    onRemoveHistoryItem(term);
                  }}
                  title="Remove from history"
                  style={{ fontSize: '0.85rem' }}
                >
                  <AiOutlineClose />
                </button>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Popular Trending Searches Section */}
      <div>
        <div className="d-flex align-items-center gap-1.5 text-secondary fw-semibold uppercase mb-2" style={{ fontSize: '0.75rem', letterSpacing: '0.05em' }}>
          <HiOutlineFire className="text-danger" style={{ fontSize: '0.95rem' }} />
          <span>POPULAR SEARCHES</span>
        </div>
        <div className="d-flex flex-wrap gap-1.5">
          {popularSearches.map((term, idx) => (
            <span
              key={idx}
              className="badge bg-light text-dark border py-1.5 px-3 rounded-pill hover-bg-primary hover-text-white cursor-pointer"
              onClick={() => onSelectTerm(term)}
              style={{
                cursor: 'pointer',
                fontSize: '0.8rem',
                fontWeight: 400,
                transition: 'all 0.15s ease'
              }}
            >
              {term}
            </span>
          ))}
        </div>
      </div>
    </div>
  );
};

export default SearchHistory;
