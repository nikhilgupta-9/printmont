import React, { useEffect, useState, useRef } from 'react';
import { useSearchParams } from 'react-router-dom';
import { fetchSearchResults } from '../services/searchApi';
import SearchResults from '../components/search/SearchResults';

const SearchPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const query = searchParams.get('q') || '';
  const page = parseInt(searchParams.get('page') || '1', 10);
  const sort = searchParams.get('sort') || 'relevance';
  const categoryId = searchParams.get('category_id') || '';
  const brand = searchParams.get('brand') || '';
  const minPrice = searchParams.get('min_price') || '';
  const maxPrice = searchParams.get('max_price') || '';

  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const abortControllerRef = useRef(null);

  useEffect(() => {
    window.scrollTo({ top: 0, behavior: 'smooth' });

    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }

    const controller = new AbortController();
    abortControllerRef.current = controller;

    setIsLoading(true);

    const apiParams = {
      q: query,
      page,
      sort,
      category_id: categoryId,
      brand,
      min_price: minPrice,
      max_price: maxPrice,
      limit: 12
    };

    fetchSearchResults(apiParams, controller.signal).then((res) => {
      if (res) {
        setData(res.results || { items: [], total: 0, page: 1, totalPages: 1, facets: {} });
      }
      setIsLoading(false);
    });

    return () => {
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
    };
  }, [query, page, sort, categoryId, brand, minPrice, maxPrice]);

  const handleFilterChange = (key, value) => {
    const updatedParams = new URLSearchParams(searchParams);
    if (value) {
      updatedParams.set(key, value);
    } else {
      updatedParams.delete(key);
    }
    updatedParams.set('page', '1'); // Reset to page 1 on filter change
    setSearchParams(updatedParams);
  };

  const handleResetFilters = () => {
    const updatedParams = new URLSearchParams();
    if (query) updatedParams.set('q', query);
    setSearchParams(updatedParams);
  };

  const handlePageChange = (newPage) => {
    const updatedParams = new URLSearchParams(searchParams);
    updatedParams.set('page', newPage.toString());
    setSearchParams(updatedParams);
  };

  const handleSortChange = (newSort) => {
    const updatedParams = new URLSearchParams(searchParams);
    updatedParams.set('sort', newSort);
    updatedParams.set('page', '1');
    setSearchParams(updatedParams);
  };

  const activeFilters = {
    category_id: categoryId,
    brand,
    min_price: minPrice,
    max_price: maxPrice,
    sort
  };

  return (
    <div className="bg-light min-vh-100 py-4">
      <SearchResults
        query={query}
        results={data}
        isLoading={isLoading}
        filters={activeFilters}
        onFilterChange={handleFilterChange}
        onResetFilters={handleResetFilters}
        onPageChange={handlePageChange}
        onSortChange={handleSortChange}
      />
    </div>
  );
};

export default SearchPage;
