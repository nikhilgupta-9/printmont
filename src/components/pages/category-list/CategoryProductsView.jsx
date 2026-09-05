import React, { useEffect, useState } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { API_ENDPOINTS } from "../../../config/apiEndpoints";
import SearchResults from "../../search/SearchResults";

// The category-products API returns the same rich product shape as the rest of
// the app (images[]/primary_image, product_slug as "slug", etc.) — flatten it
// down to the plain {image, ...} shape SearchResults/getProductUrl expect.
const normalizeItem = (item) => ({
  ...item,
  image: item.primary_image || item.images?.[0]?.image_url || '',
});

// Rendered for leaf categories (no subcategories left, e.g. "Bamboo Bottles") —
// a Flipkart-style product grid scoped to that category, reusing the same
// filter/sort/pagination UI as the global search results page.
const CategoryProductsView = ({ categoryId, categoryName }) => {
  const [searchParams, setSearchParams] = useSearchParams();
  const page = parseInt(searchParams.get('page') || '1', 10);
  const sort = searchParams.get('sort') || 'relevance';
  const brand = searchParams.get('brand') || '';
  const minPrice = searchParams.get('min_price') || '';
  const maxPrice = searchParams.get('max_price') || '';

  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  // Landing on a different leaf category should start with a clean slate,
  // not carry over the previous category's page/sort/price filters.
  useEffect(() => {
    if (searchParams.toString() !== '') {
      setSearchParams({}, { replace: true });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [categoryId]);

  useEffect(() => {
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // Deliberately not using AbortController: the app's global fetch
    // interceptor (utils/fetchInterceptor.js) retries any rejected fetch —
    // including an aborted one — by replaying the exact same (already-aborted)
    // signal, so every retry fails instantly too and the request never
    // recovers. A stale-response guard is used instead of cancelling in flight.
    let cancelled = false;
    setIsLoading(true);

    const params = { page, sort, limit: 12 };
    if (brand) params.brand = brand;
    if (minPrice) params.min_price = minPrice;
    if (maxPrice) params.max_price = maxPrice;

    fetch(API_ENDPOINTS.CATEGORY_PRODUCTS(categoryId, params))
      .then((res) => res.json())
      .then((res) => {
        if (cancelled) return;
        if (res?.success && res.data) {
          setData({ ...res.data, items: (res.data.items || []).map(normalizeItem) });
        } else {
          setData({ items: [], total: 0, page: 1, totalPages: 1, facets: {} });
        }
        setIsLoading(false);
      })
      .catch((error) => {
        if (cancelled) return;
        console.error('Error fetching category products:', error);
        setData({ items: [], total: 0, page: 1, totalPages: 1, facets: {} });
        setIsLoading(false);
      });

    return () => { cancelled = true; };
  }, [categoryId, page, sort, brand, minPrice, maxPrice]);

  const handleFilterChange = (key, value) => {
    const updated = new URLSearchParams(searchParams);
    if (value) updated.set(key, value); else updated.delete(key);
    updated.set('page', '1');
    setSearchParams(updated);
  };

  const handleResetFilters = () => setSearchParams({});

  const handlePageChange = (newPage) => {
    const updated = new URLSearchParams(searchParams);
    updated.set('page', newPage.toString());
    setSearchParams(updated);
  };

  const handleSortChange = (newSort) => {
    const updated = new URLSearchParams(searchParams);
    updated.set('sort', newSort);
    updated.set('page', '1');
    setSearchParams(updated);
  };

  const activeFilters = { brand, min_price: minPrice, max_price: maxPrice, sort };

  return (
    <div className="bg-light min-vh-100">
      <div className="container-fluid px-3 px-md-4 px-xl-5 pt-3">
        <nav aria-label="breadcrumb">
          <ol className="breadcrumb mb-0 small">
            <li className="breadcrumb-item"><Link to="/">Home</Link></li>
            <li className="breadcrumb-item active" aria-current="page">{categoryName}</li>
          </ol>
        </nav>
      </div>
      <SearchResults
        query=""
        title={categoryName}
        results={data}
        isLoading={isLoading}
        filters={activeFilters}
        onFilterChange={handleFilterChange}
        onResetFilters={handleResetFilters}
        onPageChange={handlePageChange}
        onSortChange={handleSortChange}
        hideCategoryFilter
      />
    </div>
  );
};

export default CategoryProductsView;
