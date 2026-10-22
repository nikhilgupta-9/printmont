import React, { useState, useEffect } from 'react';
import { IoSearch } from 'react-icons/io5';
import { AiOutlineClose } from 'react-icons/ai';

const FLIPKART_SEARCH_PLACEHOLDERS = [
  'Search for Customized T-Shirts, Hoodies & Polos...',
  'Search for Photo Mugs, Magic Mugs & Drinkware...',
  'Search for Corporate Joining Kits & Gift Combos...',
  'Search for Personalized Photo Frames & Canvas...',
  'Search for Visiting Cards, Diaries & Engraved Pens...',
  'Search for Stickers, Labels, Badges & Signages...'
];

const SearchInput = ({
  query,
  onChange,
  onClear,
  onKeyDown,
  onFocus,
  isLoading,
  placeholder,
  inputRef
}) => {
  const [currentPlaceholderIdx, setCurrentPlaceholderIdx] = useState(0);

  useEffect(() => {
    if (placeholder) return;
    const interval = setInterval(() => {
      setCurrentPlaceholderIdx((prev) => (prev + 1) % FLIPKART_SEARCH_PLACEHOLDERS.length);
    }, 3200);
    return () => clearInterval(interval);
  }, [placeholder]);

  const activePlaceholder = placeholder || FLIPKART_SEARCH_PLACEHOLDERS[currentPlaceholderIdx];

  return (
    <div className="d-flex align-items-center w-100 pm-search-input-box position-relative">
      {/* Input Field */}
      <input
        ref={inputRef}
        type="text"
        className="form-control border-0 bg-transparent ps-3 pe-1 py-1 flex-grow-1 pm-search-input-field"
        value={query}
        onChange={onChange}
        onKeyDown={onKeyDown}
        onFocus={onFocus}
        placeholder={activePlaceholder}
        aria-label="Search products, brands and personalized gifts"
        autoComplete="off"
        spellCheck="false"
      />

      {/* Middle Action: Loading Spinner OR Clear Button */}
      {isLoading ? (
        <div className="me-2 d-flex align-items-center flex-shrink-0">
          <div 
            className="spinner-border spinner-border-sm text-primary" 
            role="status" 
            style={{ width: '1.1rem', height: '1.1rem', borderWidth: '2px' }}
          >
            <span className="visually-hidden">Loading...</span>
          </div>
        </div>
      ) : query ? (
        <button
          type="button"
          className="pm-search-clear-btn me-1"
          onClick={onClear}
          title="Clear Search"
          aria-label="Clear Search Input"
        >
          <AiOutlineClose size={15} />
        </button>
      ) : null}

      {/* Right Search Button (Flipkart / Amazon Signature CTA) */}
      <button
        type="submit"
        className="pm-search-submit-btn flex-shrink-0"
        title="Search Printmont"
        aria-label="Search"
      >
        <IoSearch size={18} />
      </button>
    </div>
  );
};

export default React.memo(SearchInput);
