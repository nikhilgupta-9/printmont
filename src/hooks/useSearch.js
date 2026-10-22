import { useState, useEffect, useRef, useCallback, useMemo } from 'react';
import { useDebounce } from './useDebounce';
import { fetchSuggestions } from '../services/searchApi';

const HISTORY_STORAGE_KEY = 'pm_search_history';
const MAX_HISTORY_ITEMS = 6;
const DEFAULT_POPULAR_SEARCHES = [
  'Custom T-Shirt',
  'Coffee Mug',
  'Photo Frame',
  'Visiting Cards',
  'Corporate Gift',
  'Hoodies',
  'Water Bottle',
  'Keychains'
];

export const useSearch = (initialQuery = '') => {
  const [query, setQuery] = useState(initialQuery);
  const debouncedQuery = useDebounce(query, 250);

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
    if (!term) return;
    const cleanTerm = typeof term === 'string' ? term.trim() : (term.name || '').trim();
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

  // Build Flipkart / Amazon style structured keyword suggestions & category qualifiers
  const structuredKeywords = useMemo(() => {
    const cleanQ = query.trim();
    if (!cleanQ) return [];

    const items = [];

    // 1. Direct search row: Search for "{query}"
    items.push({
      id: 'direct-query',
      itemType: 'direct_query',
      name: cleanQ,
      queryText: cleanQ
    });

    // 2. Category Qualifiers (Flipkart signature: "mug in Personalised Gifts")
    const categoryMap = new Map();
    (suggestions.products || []).forEach((p) => {
      if (p.category_name && !categoryMap.has(p.category_name)) {
        categoryMap.set(p.category_name, p.category_id);
      }
    });

    // Take top 2 unique categories
    let catCount = 0;
    categoryMap.forEach((catId, catName) => {
      if (catCount < 2) {
        items.push({
          id: `cat-filter-${catId || catName}`,
          itemType: 'category_filter',
          queryText: cleanQ,
          categoryName: catName,
          categoryId: catId,
          name: `${cleanQ} in ${catName}`
        });
        catCount++;
      }
    });

    // 3. Sub-subcategories (e.g. "Photo Mugs", "T-shirts")
    (suggestions.subSubCategories || []).forEach((ssc) => {
      if (!items.some((it) => it.name.toLowerCase() === ssc.name.toLowerCase())) {
        items.push({
          ...ssc,
          itemType: 'subSubCategory'
        });
      }
    });

    // 4. Product Types (e.g. "Custom Coffee Mugs")
    (suggestions.productTypes || []).forEach((pt) => {
      if (!items.some((it) => it.name.toLowerCase() === pt.name.toLowerCase())) {
        items.push({
          ...pt,
          itemType: 'productType'
        });
      }
    });

    // 5. Parent Categories & Subcategories
    (suggestions.categories || []).forEach((c) => {
      if (!items.some((it) => it.name.toLowerCase() === c.name.toLowerCase())) {
        items.push({
          ...c,
          itemType: 'category'
        });
      }
    });

    (suggestions.subCategories || []).forEach((sc) => {
      if (!items.some((it) => it.name.toLowerCase() === sc.name.toLowerCase())) {
        items.push({
          ...sc,
          itemType: 'subCategory'
        });
      }
    });

    return items;
  }, [query, suggestions]);

  // Flattened items used by keyboard navigation (Up/Down arrow keys)
  const flattenedItems = useMemo(() => {
    return structuredKeywords;
  }, [structuredKeywords]);

  return {
    query,
    setQuery,
    debouncedQuery,
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
    popularSearches: DEFAULT_POPULAR_SEARCHES
  };
};

export default useSearch;
