import { useState, useEffect, useRef, useCallback } from 'react';
import { useDebounce } from './useDebounce';
import { fetchSuggestions } from '../services/searchApi';

const HISTORY_STORAGE_KEY = 'pm_search_history';
const MAX_HISTORY_ITEMS = 6;
const DEFAULT_POPULAR_SEARCHES = ['Custom T-Shirt', 'Coffee Mug', 'Photo Frame', 'Notebook', 'Corporate Gift', 'Custom Cap'];

export const useSearch = (initialQuery = '') => {
  const [query, setQuery] = useState(initialQuery);
  const debouncedQuery = useDebounce(query, 300);

  const [suggestions, setSuggestions] = useState({
    products: [],
    categories: [],
    subCategories: [],
    subSubCategories: [],
    productTypes: []
  });

  const [isLoading, setIsLoading] = useState(false);
  const [showDropdown, setShowDropdown] = useState(false);
  const [selectedIndex, setSelectedIndex] = useState(-1);

  // Local Storage History State
  const [history, setHistory] = useState(() => {
    try {
      const saved = localStorage.getItem(HISTORY_STORAGE_KEY);
      return saved ? JSON.parse(saved) : [];
    } catch {
      return [];
    }
  });

  const abortControllerRef = useRef(null);

  // Save query to localStorage history
  const saveSearch = useCallback((term) => {
    const cleanTerm = term.trim();
    if (!cleanTerm) return;

    setHistory((prev) => {
      const filtered = prev.filter((item) => item.toLowerCase() !== cleanTerm.toLowerCase());
      const updated = [cleanTerm, ...filtered].slice(0, MAX_HISTORY_ITEMS);
      try {
        localStorage.setItem(HISTORY_STORAGE_KEY, JSON.stringify(updated));
      } catch (err) {
        console.error('Failed to save search history:', err);
      }
      return updated;
    });
  }, []);

  // Remove single item from search history
  const removeSearch = useCallback((termToRemove) => {
    setHistory((prev) => {
      const updated = prev.filter((item) => item.toLowerCase() !== termToRemove.toLowerCase());
      try {
        localStorage.setItem(HISTORY_STORAGE_KEY, JSON.stringify(updated));
      } catch (err) {
        console.error('Failed to update search history:', err);
      }
      return updated;
    });
  }, []);

  // Clear all search history
  const clearHistory = useCallback(() => {
    setHistory([]);
    try {
      localStorage.removeItem(HISTORY_STORAGE_KEY);
    } catch (err) {
      console.error('Failed to clear search history:', err);
    }
  }, []);

  // Fetch Suggestions Effect
  useEffect(() => {
    if (!debouncedQuery || debouncedQuery.trim().length < 1) {
      setSuggestions({
        products: [],
        categories: [],
        subCategories: [],
        subSubCategories: [],
        productTypes: []
      });
      setIsLoading(false);
      return;
    }

    // Abort previous pending request
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }

    const controller = new AbortController();
    abortControllerRef.current = controller;

    setIsLoading(true);
    fetchSuggestions(debouncedQuery, controller.signal).then((data) => {
      if (data) {
        setSuggestions(data);
        setShowDropdown(true);
        setSelectedIndex(-1);
      }
      setIsLoading(false);
    });

    return () => {
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
    };
  }, [debouncedQuery]);

  // Flatten all suggestion items into a single array for keyboard arrow navigation
  const flattenedItems = [
    ...suggestions.categories.map((c) => ({ ...c, itemType: 'category' })),
    ...suggestions.subCategories.map((sc) => ({ ...sc, itemType: 'subCategory' })),
    ...suggestions.subSubCategories.map((ssc) => ({ ...ssc, itemType: 'subSubCategory' })),
    ...suggestions.productTypes.map((pt) => ({ ...pt, itemType: 'productType' })),
    ...suggestions.products.map((p) => ({ ...p, itemType: 'product' }))
  ];

  return {
    query,
    setQuery,
    debouncedQuery,
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
    popularSearches: DEFAULT_POPULAR_SEARCHES
  };
};

export default useSearch;
