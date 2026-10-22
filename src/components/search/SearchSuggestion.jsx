import React from 'react';
import { IoSearch } from 'react-icons/io5';
import { FiArrowUpRight, FiPackage } from 'react-icons/fi';
import { BiCategory } from 'react-icons/bi';
import SearchHighlight from './SearchHighlight';

const SearchSuggestion = ({ item, isSelected, onClick, onMouseEnter, query }) => {
  const isCategoryFilter = item.itemType === 'category_filter';
  const isDirectCategory = item.itemType === 'category' || item.itemType === 'subCategory' || item.itemType === 'subSubCategory';
  const isProductType = item.itemType === 'productType';

  return (
    <div
      className={`pm-search-suggestion-item ${isSelected ? 'is-selected' : ''}`}
      onClick={() => onClick(item)}
      onMouseEnter={onMouseEnter}
      role="option"
      aria-selected={isSelected}
    >
      <div className="d-flex align-items-center gap-2.5 overflow-hidden flex-grow-1 me-2">
        {/* Leading Icon */}
        <span className="pm-search-item-icon">
          {isDirectCategory ? (
            <BiCategory size={16} />
          ) : isProductType ? (
            <FiPackage size={15} />
          ) : (
            <IoSearch size={16} />
          )}
        </span>

        {/* Suggestion Text */}
        <div className="pm-search-item-text">
          {isCategoryFilter ? (
            <>
              <SearchHighlight text={item.queryText || query} query={query} />
              <span className="pm-search-category-qualifier">
                in <strong>{item.categoryName}</strong>
              </span>
            </>
          ) : (
            <SearchHighlight text={item.name} query={query} />
          )}

          {/* Breadcrumb path for deeper categories */}
          {item.path && item.path !== item.name && (
            <small className="d-block text-muted" style={{ fontSize: '11px', lineHeight: 1.1 }}>
              {item.path}
            </small>
          )}
        </div>
      </div>

      {/* Trailing Arrow */}
      <span className="pm-search-arrow-hint">
        <FiArrowUpRight size={15} />
      </span>
    </div>
  );
};

export default React.memo(SearchSuggestion);
