import React from 'react';
import SearchSuggestion from './SearchSuggestion';
import SearchHistory from './SearchHistory';
import { SearchSuggestionSkeleton } from './SearchSkeleton';
import { FiSearch, FiArrowRight } from 'react-icons/fi';

const SearchDropdown = ({
  query,
  suggestions,
  flattenedItems,
  isLoading,
  selectedIndex,
  onSelectSuggestion,
  onViewAllResults,
  history,
  popularSearches,
  onRemoveHistoryItem,
  onClearHistory,
  onHoverIndex
}) => {
  const hasQuery = query && query.trim().length >= 1;
  const hasSuggestions = flattenedItems && flattenedItems.length > 0;

  return (
    <div
      className="position-absolute start-0 end-0 bg-white rounded-4 shadow-lg border overflow-hidden mt-1"
      style={{
        top: '100%',
        zIndex: 1050,
        maxHeight: '480px',
        overflowY: 'auto',
        border: '1px solid #e2e8f0'
      }}
    >
      {/* State 1: Query Empty - Show History & Popular Searches */}
      {!hasQuery && (
        <SearchHistory
          history={history}
          popularSearches={popularSearches}
          onSelectTerm={onSelectSuggestion}
          onRemoveHistoryItem={onRemoveHistoryItem}
          onClearHistory={onClearHistory}
        />
      )}

      {/* State 2: Searching - Show Loading Skeleton */}
      {hasQuery && isLoading && !hasSuggestions && (
        <SearchSuggestionSkeleton />
      )}

      {/* State 3: No Results State */}
      {hasQuery && !isLoading && !hasSuggestions && (
        <div className="p-4 text-center text-muted">
          <FiSearch className="mb-2 text-secondary" style={{ fontSize: '2rem' }} />
          <div className="fw-medium text-dark">No matching results found</div>
          <div className="small text-secondary mt-1">
            Try checking for spelling errors or search for broader keywords like "Shirt" or "Mug"
          </div>
        </div>
      )}

      {/* State 4: Suggestions Loaded - Grouped Entity Sections */}
      {hasQuery && hasSuggestions && (
        <div className="p-2">
          {/* Categories / Sub-Categories Group */}
          {(suggestions.categories.length > 0 || suggestions.subCategories.length > 0 || suggestions.subSubCategories.length > 0 || suggestions.productTypes.length > 0) && (
            <div className="mb-2">
              <div className="px-2 py-1 text-secondary uppercase fw-semibold" style={{ fontSize: '0.7rem', letterSpacing: '0.05em' }}>
                CATEGORIES & DEPARTMENTS
              </div>
              {[
                ...suggestions.categories.map((c) => ({ ...c, itemType: 'category' })),
                ...suggestions.subCategories.map((sc) => ({ ...sc, itemType: 'subCategory' })),
                ...suggestions.subSubCategories.map((ssc) => ({ ...ssc, itemType: 'subSubCategory' })),
                ...suggestions.productTypes.map((pt) => ({ ...pt, itemType: 'productType' }))
              ].map((item) => {
                const globalIndex = flattenedItems.findIndex((fi) => fi.id === item.id && fi.itemType === item.itemType);
                return (
                  <SearchSuggestion
                    key={`${item.itemType}-${item.id}`}
                    item={item}
                    isSelected={selectedIndex === globalIndex}
                    onClick={onSelectSuggestion}
                    onMouseEnter={() => onHoverIndex(globalIndex)}
                  />
                );
              })}
            </div>
          )}

          {/* Products Group */}
          {suggestions.products.length > 0 && (
            <div>
              <div className="px-2 py-1 text-secondary uppercase fw-semibold border-top pt-2 mt-1" style={{ fontSize: '0.7rem', letterSpacing: '0.05em' }}>
                MATCHING PRODUCTS
              </div>
              {suggestions.products.map((item) => {
                const globalIndex = flattenedItems.findIndex((fi) => fi.id === item.id && fi.itemType === 'product');
                return (
                  <SearchSuggestion
                    key={`product-${item.id}`}
                    item={{ ...item, itemType: 'product' }}
                    isSelected={selectedIndex === globalIndex}
                    onClick={onSelectSuggestion}
                    onMouseEnter={() => onHoverIndex(globalIndex)}
                  />
                );
              })}
            </div>
          )}

          {/* Footer View All Bar */}
          <div 
            className="p-2.5 mt-2 bg-light border-top rounded-bottom-4 d-flex align-items-center justify-content-between text-primary fw-medium cursor-pointer hover-bg-primary hover-text-white"
            onClick={() => onViewAllResults(query)}
            style={{ cursor: 'pointer', fontSize: '0.875rem', transition: 'all 0.15s ease' }}
          >
            <span>View all results for "<strong>{query}</strong>"</span>
            <FiArrowRight style={{ fontSize: '1.1rem' }} />
          </div>
        </div>
      )}
    </div>
  );
};

export default SearchDropdown;
