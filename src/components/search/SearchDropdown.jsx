import React from 'react';
import { FiSearch, FiArrowRight } from 'react-icons/fi';
import { BiTrendingUp } from 'react-icons/bi';
import SearchSuggestion from './SearchSuggestion';
import SearchHistory from './SearchHistory';
import { SearchSuggestionSkeleton } from './SearchSkeleton';
import { resolveImageUrl } from '../../config/apiEndpoints';

const SearchDropdown = ({
  query,
  suggestions,
  structuredKeywords = [],
  flattenedItems = [],
  isLoading,
  selectedIndex,
  onSelectSuggestion,
  onSelectProduct,
  onViewAllResults,
  history,
  popularSearches,
  onRemoveHistoryItem,
  onClearHistory,
  onHoverIndex
}) => {
  const hasQuery = query && query.trim().length >= 1;
  const products = suggestions?.products || [];
  const topProducts = products.slice(0, 4);
  const hasResults = (structuredKeywords && structuredKeywords.length > 0) || topProducts.length > 0;

  return (
    <div
      className="pm-search-dropdown"
      role="listbox"
      aria-label="Search suggestions"
    >
      {/* State 1: Query is Empty -> Show Recent Searches & Trending Tags */}
      {!hasQuery && (
        <SearchHistory
          history={history}
          popularSearches={popularSearches}
          onSelectTerm={onSelectSuggestion}
          onRemoveHistoryItem={onRemoveHistoryItem}
          onClearHistory={onClearHistory}
        />
      )}

      {/* State 2: Query entered & Loading without cached results */}
      {hasQuery && isLoading && !hasResults && (
        <div className="p-3">
          <SearchSuggestionSkeleton />
        </div>
      )}

      {/* State 3: Query entered, not loading, but NO results match */}
      {hasQuery && !isLoading && !hasResults && (
        <div className="p-4 text-center text-muted">
          <FiSearch className="mb-2 text-secondary" style={{ fontSize: '2.2rem' }} />
          <div className="fw-bold text-dark" style={{ fontSize: '15px' }}>
            No matching results found for "{query}"
          </div>
          <div className="small text-secondary mt-1" style={{ maxWidth: '320px', margin: '0 auto' }}>
            Check for spelling mistakes, or try searching for generic terms like <strong>"T-Shirt"</strong>, <strong>"Mug"</strong> or <strong>"Frame"</strong>.
          </div>
        </div>
      )}

      {/* State 4: Results available -> Flipkart 2-Column Split Layout */}
      {hasQuery && hasResults && (
        <>
          <div className="pm-search-split-container">
            {/* LEFT COLUMN: Keyword Autocomplete & Category Qualifiers */}
            <div className="pm-search-left-col">
              <div className="pm-search-section-header">
                <FiSearch size={12} />
                <span>SUGGESTIONS</span>
              </div>

              {structuredKeywords.map((item, idx) => (
                <SearchSuggestion
                  key={item.id || idx}
                  item={item}
                  query={query}
                  isSelected={selectedIndex === idx}
                  onClick={() => onSelectSuggestion(item)}
                  onMouseEnter={() => onHoverIndex(idx)}
                />
              ))}
            </div>

            {/* RIGHT COLUMN: Flipkart Style Top Matching Products Showcase */}
            {topProducts.length > 0 && (
              <div className="pm-search-right-col">
                <div className="pm-search-section-header justify-content-between mb-2">
                  <span className="d-flex align-items-center gap-1.5">
                    <BiTrendingUp size={13} className="text-primary" />
                    <span>TOP PRODUCTS</span>
                  </span>
                  <span className="badge bg-white text-secondary border fw-normal" style={{ fontSize: '10px' }}>
                    {products.length} found
                  </span>
                </div>

                <div className="d-flex flex-column">
                  {topProducts.map((prod) => {
                    const imgUrl = resolveImageUrl(prod.image);
                    const originalPrice = prod.discount_price && prod.discount_price < prod.price ? prod.price : null;
                    const displayPrice = prod.discount_price && prod.discount_price < prod.price ? prod.discount_price : prod.price;
                    const discountPercent = originalPrice 
                      ? Math.round(((originalPrice - displayPrice) / originalPrice) * 100)
                      : null;

                    return (
                      <div
                        key={prod.id}
                        className="pm-search-product-card"
                        onClick={() => onSelectProduct(prod)}
                        title={`View ${prod.name}`}
                      >
                        <img
                          src={imgUrl}
                          alt={prod.name}
                          className="pm-search-prod-thumb"
                          onError={(e) => {
                            e.target.src = '/default-img.jpg';
                          }}
                        />
                        <div className="pm-search-prod-info">
                          <div className="pm-search-prod-title">
                            {prod.name}
                          </div>
                          {prod.category_name && (
                            <div className="pm-search-prod-cat">
                              in {prod.category_name}
                            </div>
                          )}
                          <div className="pm-search-prod-pricing">
                            <span className="pm-search-prod-price">
                              ₹{displayPrice}
                            </span>
                            {originalPrice && (
                              <span className="pm-search-prod-mrp">
                                ₹{originalPrice}
                              </span>
                            )}
                            {discountPercent && discountPercent > 0 && (
                              <span className="pm-search-prod-discount">
                                {discountPercent}% off
                              </span>
                            )}
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}
          </div>

          {/* FOOTER BAR: Flipkart style "See all results for {query}" */}
          <div
            className="pm-search-footer-bar"
            onClick={() => onViewAllResults(query)}
            role="button"
            tabIndex={0}
            onKeyDown={(e) => {
              if (e.key === 'Enter') onViewAllResults(query);
            }}
          >
            <span>
              View all results for "<strong>{query}</strong>"
            </span>
            <div className="d-flex align-items-center gap-2">
              <span className="pm-search-kbd">↵ Enter</span>
              <FiArrowRight size={15} />
            </div>
          </div>
        </>
      )}
    </div>
  );
};

export default React.memo(SearchDropdown);
