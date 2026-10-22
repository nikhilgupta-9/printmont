import React, { useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import useSearch from '../../hooks/useSearch';
import SearchInput from './SearchInput';
import SearchDropdown from './SearchDropdown';
import { getProductUrl } from '../../utils/seo';
import './search.css';

const SearchBar = ({ isMobileOverlay = false, onCloseMobile = null }) => {
  const navigate = useNavigate();
  const searchRef = useRef(null);
  const inputRef = useRef(null);

  const {
    query,
    setQuery,
    suggestions,
    structuredKeywords,
    flattenedItems,
    isLoading,
    showDropdown,
    setShowDropdown,
    selectedIndex,
    setSelectedIndex,
    history,
    saveSearch,
    removeSearch,
    clearHistory,
    popularSearches
  } = useSearch();

  // Close dropdown on click outside
  useEffect(() => {
    const handleClickOutside = (e) => {
      if (searchRef.current && !searchRef.current.contains(e.target)) {
        setShowDropdown(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [setShowDropdown]);

  // Execute Search Navigation (Search results list)
  const executeSearch = (searchTerm) => {
    const cleanTerm = (searchTerm || '').trim();
    if (!cleanTerm) return;

    saveSearch(cleanTerm);
    setShowDropdown(false);
    if (onCloseMobile) onCloseMobile();
    navigate(`/allproducts?q=${encodeURIComponent(cleanTerm)}`);
  };

  // Direct Product Navigation (Clicking product card in right column or dropdown)
  const handleSelectProduct = (product) => {
    if (!product) return;
    saveSearch(product.name);
    setShowDropdown(false);
    if (onCloseMobile) onCloseMobile();
    const productUrl = getProductUrl(product);
    navigate(productUrl);
  };

  // Select Item Handler (keywords, category filters, categories, products)
  const handleSelectItem = (item) => {
    setShowDropdown(false);
    if (onCloseMobile) onCloseMobile();

    if (!item) return;

    if (typeof item === 'string') {
      executeSearch(item);
      return;
    }

    if (item.itemType === 'product') {
      handleSelectProduct(item);
      return;
    }

    if (item.itemType === 'category_filter') {
      saveSearch(item.queryText);
      const catParam = encodeURIComponent(item.categoryName);
      const qParam = encodeURIComponent(item.queryText);
      navigate(`/allproducts?q=${qParam}&category=${catParam}`);
      return;
    }

    if (item.itemType === 'category' || item.itemType === 'subCategory' || item.itemType === 'subSubCategory') {
      saveSearch(item.name);
      if (item.slug) {
        navigate(`/category/${item.slug}`);
      } else {
        navigate(`/allproducts?category=${encodeURIComponent(item.name)}`);
      }
      return;
    }

    if (item.itemType === 'productType') {
      saveSearch(item.name);
      if (item.slug) {
        navigate(`/category/${item.slug}`);
      } else {
        navigate(`/allproducts?q=${encodeURIComponent(item.name)}`);
      }
      return;
    }

    // Default: execute search with item.name
    executeSearch(item.name || item.queryText || query);
  };

  // Keyboard navigation handler (Arrows, Enter, Escape)
  const handleKeyDown = (e) => {
    if (e.key === 'Escape') {
      setShowDropdown(false);
      return;
    }

    if (!showDropdown && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
      setShowDropdown(true);
      return;
    }

    const itemsCount = flattenedItems ? flattenedItems.length : 0;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (itemsCount > 0) {
        setSelectedIndex((prev) => (prev < itemsCount - 1 ? prev + 1 : 0));
      }
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (itemsCount > 0) {
        setSelectedIndex((prev) => (prev > 0 ? prev - 1 : itemsCount - 1));
      }
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (selectedIndex >= 0 && selectedIndex < itemsCount) {
        handleSelectItem(flattenedItems[selectedIndex]);
      } else if (query.trim()) {
        executeSearch(query);
      }
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (query.trim()) {
      executeSearch(query);
    }
  };

  return (
    <>
      {/* Amazon Focus Backdrop Dimming: Dims page when search dropdown is active */}
      {showDropdown && (
        <div
          className="pm-search-backdrop"
          onClick={() => setShowDropdown(false)}
          aria-hidden="true"
        />
      )}

      <div
        ref={searchRef}
        className={`pm-search-bar-container ${showDropdown ? 'is-active' : ''}`}
      >
        <form onSubmit={handleSubmit} className="w-100 mb-0" role="search">
          <SearchInput
            inputRef={inputRef}
            query={query}
            onChange={(e) => {
              setQuery(e.target.value);
              setShowDropdown(true);
            }}
            onClear={() => {
              setQuery('');
              setShowDropdown(true);
              if (inputRef.current) inputRef.current.focus();
            }}
            onKeyDown={handleKeyDown}
            onFocus={() => setShowDropdown(true)}
            isLoading={isLoading}
          />
        </form>

        {/* Live Flipkart & Amazon Style Suggestion Dropdown */}
        {showDropdown && (
          <SearchDropdown
            query={query}
            suggestions={suggestions}
            structuredKeywords={structuredKeywords}
            flattenedItems={flattenedItems}
            isLoading={isLoading}
            selectedIndex={selectedIndex}
            onSelectSuggestion={handleSelectItem}
            onSelectProduct={handleSelectProduct}
            onViewAllResults={executeSearch}
            history={history}
            popularSearches={popularSearches}
            onRemoveHistoryItem={removeSearch}
            onClearHistory={clearHistory}
            onHoverIndex={(idx) => setSelectedIndex(idx)}
          />
        )}
      </div>
    </>
  );
};

export default SearchBar;
