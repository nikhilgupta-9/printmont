import React from 'react';
import { IoSearch } from 'react-icons/io5';
import { AiOutlineClose } from 'react-icons/ai';

const SearchInput = ({
  query,
  onChange,
  onClear,
  onKeyDown,
  onFocus,
  isLoading,
  placeholder = 'Search for products, brands and more',
  inputRef
}) => {
  return (
    <div 
      className="position-relative w-100 d-flex align-items-center rounded-3 border transition-all search-input-container"
      style={{
        backgroundColor: '#f0f5ff',
        borderColor: '#e2e8f0',
        borderRadius: '8px',
        padding: '2px 4px'
      }}
    >
      {/* Left Search Icon (Subtle Gray) */}
      <IoSearch 
        className="ms-3 flex-shrink-0" 
        style={{ fontSize: '1.2rem', color: '#64748b', pointerEvents: 'none' }} 
      />

      {/* Input Field */}
      <input
        ref={inputRef}
        type="text"
        className="form-control border-0 bg-transparent px-3 py-1 text-dark shadow-none"
        value={query}
        onChange={onChange}
        onKeyDown={onKeyDown}
        onFocus={onFocus}
        placeholder={placeholder}
        aria-label="Search"
        autoComplete="off"
        style={{
          color: '#1e293b',
          fontSize: '0.92rem',
          fontWeight: 400,
          boxShadow: 'none'
        }}
      />

      {/* Right Action Container: Loading, Clear, or Blue Search Icon */}
      <div className="me-3 d-flex align-items-center gap-2 flex-shrink-0" style={{ zIndex: 5 }}>
        {isLoading ? (
          <div 
            className="spinner-border spinner-border-sm text-primary" 
            role="status" 
            style={{ width: '1.1rem', height: '1.1rem', borderWidth: '2px' }}
          >
            <span className="visually-hidden">Loading...</span>
          </div>
        ) : query ? (
          <button
            type="button"
            className="btn btn-link text-secondary p-0 border-0"
            onClick={onClear}
            title="Clear Search"
            style={{ fontSize: '1rem', cursor: 'pointer', lineHeight: 1 }}
          >
            <AiOutlineClose />
          </button>
        ) : null}

        {/* Right Search Icon (Vibrant Blue) */}
        <IoSearch 
          style={{ fontSize: '1.25rem', color: '#2563eb', cursor: 'pointer' }}
        />
      </div>
    </div>
  );
};

export default SearchInput;
