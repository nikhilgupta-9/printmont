import React from 'react';
import { AiOutlineClockCircle, AiOutlineClose } from 'react-icons/ai';
import { HiOutlineFire } from 'react-icons/hi';
import { BiTrendingUp } from 'react-icons/bi';
import { IoGridOutline } from 'react-icons/io5';

const POPULAR_PRINTMONT_TAGS = [
  'Custom T-Shirts',
  'Photo Mugs',
  'Corporate Gift Kits',
  'Personalized Frames',
  'Visiting Cards',
  'Insulated Bottles',
  'Sweatshirts & Hoodies',
  'Custom Keychains',
  'Engraved Pens'
];

const QUICK_CATEGORIES = [
  { name: 'Apparel & Clothing', slug: 'apparel' },
  { name: 'Tableware & Mugs', slug: 'tableware-drinkware' },
  { name: 'Personalized Gifts', slug: 'personalised-gifts' },
  { name: 'Corporate Gifts', slug: 'corporate-gifting' },
  { name: 'Office Stationery', slug: 'stationery-stamps' }
];

const SearchHistory = ({
  history = [],
  popularSearches = POPULAR_PRINTMONT_TAGS,
  onSelectTerm,
  onRemoveHistoryItem,
  onClearHistory
}) => {
  const displayTags = popularSearches && popularSearches.length > 0 ? popularSearches : POPULAR_PRINTMONT_TAGS;

  return (
    <div className="p-3">
      {/* 1. Recent Searches (Flipkart Style) */}
      {history && history.length > 0 && (
        <div className="mb-3">
          <div className="d-flex align-items-center justify-content-between mb-1.5 px-1">
            <div className="d-flex align-items-center gap-1.5 text-secondary fw-bold" style={{ fontSize: '11px', letterSpacing: '0.05em' }}>
              <AiOutlineClockCircle size={14} className="text-secondary" />
              <span>RECENT SEARCHES</span>
            </div>
            <button
              type="button"
              className="btn btn-link text-primary p-0 border-0 text-decoration-none"
              onClick={(e) => {
                e.stopPropagation();
                onClearHistory();
              }}
              style={{ fontSize: '11.5px', fontWeight: 600 }}
            >
              Clear All
            </button>
          </div>

          <div className="d-flex flex-column gap-1">
            {history.map((term, idx) => (
              <div
                key={idx}
                className="pm-search-history-row"
                onClick={() => onSelectTerm(term)}
              >
                <div className="d-flex align-items-center gap-2.5 text-dark">
                  <AiOutlineClockCircle className="text-muted flex-shrink-0" size={15} />
                  <span style={{ fontSize: '13.5px', fontWeight: 500 }}>{term}</span>
                </div>
                <button
                  type="button"
                  className="pm-search-history-del"
                  onClick={(e) => {
                    e.stopPropagation();
                    onRemoveHistoryItem(term);
                  }}
                  title="Remove from history"
                  aria-label="Remove search history item"
                >
                  <AiOutlineClose size={13} />
                </button>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* 2. Trending Searches (Flipkart Pill Style) */}
      <div className="mb-2">
        <div className="d-flex align-items-center gap-1.5 text-secondary fw-bold mb-2 px-1" style={{ fontSize: '11px', letterSpacing: '0.05em' }}>
          <HiOutlineFire className="text-danger" size={16} />
          <span>TRENDING SEARCHES</span>
        </div>
        <div className="pm-search-trending-chips">
          {displayTags.map((term, idx) => (
            <span
              key={idx}
              className="pm-search-trending-chip"
              onClick={() => onSelectTerm(term)}
            >
              <BiTrendingUp size={14} className="text-primary pm-trending-icon" />
              <span>{term}</span>
            </span>
          ))}
        </div>
      </div>

      {/* 3. Discover Top Categories */}
      <div className="pt-2 border-top">
        <div className="d-flex align-items-center gap-1.5 text-secondary fw-bold mb-2 px-1" style={{ fontSize: '11px', letterSpacing: '0.05em' }}>
          <IoGridOutline size={14} className="text-secondary" />
          <span>EXPLORE POPULAR DEPARTMENTS</span>
        </div>
        <div className="d-flex flex-wrap gap-1.5">
          {QUICK_CATEGORIES.map((cat, cIdx) => (
            <span
              key={cIdx}
              className="badge bg-light text-secondary border px-2.5 py-1.5 rounded-2 cursor-pointer hover-bg-primary hover-text-white"
              style={{ fontSize: '12px', fontWeight: 500, cursor: 'pointer', transition: 'all 0.15s ease' }}
              onClick={() => onSelectTerm({ name: cat.name, slug: cat.slug, itemType: 'category' })}
            >
              {cat.name}
            </span>
          ))}
        </div>
      </div>
    </div>
  );
};

export default React.memo(SearchHistory);
