import React, { useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import useSearch from '../../hooks/useSearch';
import SearchInput from './SearchInput';
import SearchDropdown from './SearchDropdown';

const SearchBar = ({ isMobileOverlay = false, onCloseMobile = null }) => {
  const navigate = useNavigate();
  const searchRef = useRef(null);
  const inputRef = useRef(null);

  const {
    query,
    setQuery,
    suggestions,
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

  // Execute Search Navigation
  const executeSearch = (searchTerm) => {
    const cleanTerm = searchTerm.trim();
    if (!cleanTerm) return;

    saveSearch(cleanTerm);
    setShowDropdown(false);
    if (onCloseMobile) onCloseMobile();
    navigate(`/allproducts?q=${encodeURIComponent(cleanTerm)}`);
  };

  // Select item handler (product, category, subcategory, or term string)
  const handleSelectItem = (item) => {
    setShowDropdown(false);
    if (onCloseMobile) onCloseMobile();

    if (typeof item === 'string') {
      executeSearch(item);
      return;
    }

    if (item.itemType === 'product') {
      saveSearch(item.name);
      navigate(`/allproducts?q=${encodeURIComponent(item.name)}`);
    } else if (item.slug) {
      saveSearch(item.name);
      navigate(`/allproducts?category=${encodeURIComponent(item.name)}`);
    } else {
      executeSearch(item.name);
    }
  };

  // Keyboard navigation handler
  const handleKeyDown = (e) => {
    if (e.key === 'Escape') {
      setShowDropdown(false);
      return;
    }

    if (!showDropdown && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
      setShowDropdown(true);
      return;
    }

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setSelectedIndex((prev) => (prev < flattenedItems.length - 1 ? prev + 1 : 0));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setSelectedIndex((prev) => (prev > 0 ? prev - 1 : flattenedItems.length - 1));
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (selectedIndex >= 0 && selectedIndex < flattenedItems.length) {
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
    <div ref={searchRef} className="position-relative w-100">
      <form onSubmit={handleSubmit} className="w-100 mb-0">
        <SearchInput
          inputRef={inputRef}
          query={query}
          onChange={(e) => {
            setQuery(e.target.value);
            setShowDropdown(true);
          }}
          onClear={() => {
            setQuery('');
            setShowDropdown(false);
            if (inputRef.current) inputRef.current.focus();
          }}
          onKeyDown={handleKeyDown}
          onFocus={() => setShowDropdown(true)}
          isLoading={isLoading}
        />
      </form>

      {/* Live Suggestion Dropdown */}
      {showDropdown && (
        <SearchDropdown
          query={query}
          suggestions={suggestions}
          flattenedItems={flattenedItems}
          isLoading={isLoading}
          selectedIndex={selectedIndex}
          onSelectSuggestion={handleSelectItem}
          onViewAllResults={executeSearch}
          history={history}
          popularSearches={popularSearches}
          onRemoveHistoryItem={removeSearch}
          onClearHistory={clearHistory}
          onHoverIndex={(idx) => setSelectedIndex(idx)}
        />
      )}
    </div>
  );
};

export default SearchBar;
