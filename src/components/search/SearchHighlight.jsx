import React from 'react';

/**
 * SearchHighlight component
 * Highlights matching portions of a suggestion string while preserving casing.
 * Mimics Amazon and Flipkart search completion typography.
 */
const SearchHighlight = ({ text = '', query = '' }) => {
  if (!text) return null;
  if (!query || !query.trim()) {
    return <span className="pm-match-normal">{text}</span>;
  }

  const cleanQuery = query.trim();
  const escapedQuery = cleanQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const regex = new RegExp(`(${escapedQuery})`, 'gi');
  const parts = text.split(regex);

  return (
    <span>
      {parts.map((part, index) => {
        const isMatch = part.toLowerCase() === cleanQuery.toLowerCase();
        return isMatch ? (
          <span key={index} className="pm-match-highlight">
            {part}
          </span>
        ) : (
          <span key={index} className="pm-match-normal">
            {part}
          </span>
        );
      })}
    </span>
  );
};

export default React.memo(SearchHighlight);
