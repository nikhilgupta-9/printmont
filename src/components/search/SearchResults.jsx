import React from 'react';
import { resolveImageUrl } from '../../config/apiEndpoints';
import { SearchGridSkeleton } from './SearchSkeleton';
import ProductCard from '../products/ProductCard';
import '../products/Product.css';
import { FiFilter, FiRefreshCw, FiShoppingBag } from 'react-icons/fi';

// Reshape one item from this page's various product APIs into the shape
// ProductCard expects (the same "ap-*" Flipkart-style card used on AllProducts).
const toProductCardShape = (prod) => {
  const price = parseFloat(prod.regular_price ?? prod.price) || 0;
  const discountedPrice = parseFloat(prod.discount_price) || price;
  const discountPercent = price > 0 && discountedPrice < price
    ? Math.round(((price - discountedPrice) / price) * 100)
    : 0;

  const images = Array.isArray(prod.images) && prod.images.length > 0
    ? prod.images.map((img) => resolveImageUrl(typeof img === 'string' ? img : img.image_url)).filter(Boolean)
    : [resolveImageUrl(prod.image || prod.primary_image)];

  return {
    id: prod.id,
    title: prod.name,
    brand: prod.brand,
    image: images,
    price,
    discountedPrice,
    originalPrice: price,
    discountPercent,
    ourBestseller: !!prod.our_bestseller,
    topRated: !!prod.top_rated,
    slug: prod.slug,
    // Same placeholder-rating formula useProductFilters.js uses for /allproducts —
    // there's no real reviews table yet, so this keeps the rating pill consistent
    // app-wide rather than showing it on one listing and not another.
    rating: 4.2 + ((parseInt(prod.id, 10) || 1) % 8) * 0.1,
    ratingCount: 15 + ((parseInt(prod.id, 10) || 1) % 50) * 12,
  };
};

const SearchResults = ({
  query,
  title,
  results,
  isLoading,
  filters,
  onFilterChange,
  onResetFilters,
  onPageChange,
  onSortChange,
  hideCategoryFilter = false
}) => {
  const { items = [], total = 0, page = 1, totalPages = 1, facets = {} } = results || {};
  const categoriesFacet = facets.categories || [];
  const brandsFacet = facets.brands || [];
  const heading = title || (query ? `Search Results for "${query}"` : 'All Products Catalog');

  return (
    <div className="container-fluid px-3 px-md-4 px-xl-5 py-4">
      {/* Search Header Bar */}
      <div className="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-4 border-bottom gap-3">
        <div>
          <h2 className="fw-bold text-dark mb-1" style={{ fontSize: '1.65rem' }}>
            {heading}
          </h2>
          <p className="text-secondary mb-0" style={{ fontSize: '0.9rem' }}>
            Showing <strong>{total}</strong> product{total !== 1 ? 's' : ''} found
          </p>
        </div>

        {/* Sort selector */}
        <div className="d-flex align-items-center gap-2">
          <label htmlFor="sort" className="form-label text-secondary mb-0 fw-medium small text-nowrap">
            Sort By:
          </label>
          <select
            id="sort"
            className="form-select form-select-sm rounded-3 border shadow-sm cursor-pointer"
            value={filters.sort || 'relevance'}
            onChange={(e) => onSortChange(e.target.value)}
            style={{ width: '180px', fontSize: '0.875rem' }}
          >
            <option value="relevance">Relevance</option>
            <option value="price_low_high">Price: Low to High</option>
            <option value="price_high_low">Price: High to Low</option>
            <option value="newest">Newest Arrivals</option>
          </select>
        </div>
      </div>

      {/* Sticky filter positioning only makes sense once the sidebar sits
          beside the grid (Bootstrap's lg breakpoint, >=992px) — on mobile the
          sidebar stacks full-width above the grid, where "sticky" just makes
          it float oddly as the page scrolls. */}
      <style>{`
        @media (min-width: 992px) {
          .search-filters-sidebar {
            position: sticky;
            top: 95px;
            max-height: calc(100vh - 110px);
            overflow-y: auto;
          }
        }
      `}</style>

      <div className="row g-4">
        {/* Left Column: Filter Sidebar — sticky on desktop, static on mobile */}
        <div className="col-12 col-lg-3">
          <div className="card border-0 shadow-sm rounded-4 p-3 search-filters-sidebar" style={{ zIndex: 10 }}>
            <div className="d-flex align-items-center justify-content-between pb-2 border-bottom mb-3">
              <div className="d-flex align-items-center gap-2 fw-bold text-dark" style={{ fontSize: '1rem' }}>
                <FiFilter className="text-primary" />
                <span>Filters</span>
              </div>
              <button
                type="button"
                className="btn btn-link text-secondary p-0 border-0 small d-flex align-items-center gap-1 text-decoration-none"
                onClick={onResetFilters}
                style={{ fontSize: '0.8rem' }}
              >
                <FiRefreshCw /> Reset
              </button>
            </div>

            {/* Categories Filter */}
            {!hideCategoryFilter && categoriesFacet.length > 0 && (
              <div className="mb-4">
                <h6 className="fw-bold text-dark mb-2" style={{ fontSize: '0.85rem' }}>Categories</h6>
                <div className="d-flex flex-column gap-2" style={{ maxHeight: '220px', overflowY: 'auto' }}>
                  <label className="d-flex align-items-center gap-2 cursor-pointer small text-secondary">
                    <input
                      type="radio"
                      name="category_filter"
                      checked={!filters.category_id}
                      onChange={() => onFilterChange('category_id', '')}
                    />
                    <span>All Categories</span>
                  </label>
                  {categoriesFacet.map((cat) => (
                    <label key={cat.id} className="d-flex align-items-center gap-2 cursor-pointer small text-dark hover-text-primary">
                      <input
                        type="radio"
                        name="category_filter"
                        checked={String(filters.category_id) === String(cat.id)}
                        onChange={() => onFilterChange('category_id', cat.id)}
                      />
                      <span className="text-truncate">{cat.name}</span>
                    </label>
                  ))}
                </div>
              </div>
            )}

            {/* Brands Filter */}
            {brandsFacet.length > 0 && (
              <div className="mb-4">
                <h6 className="fw-bold text-dark mb-2" style={{ fontSize: '0.85rem' }}>Brands</h6>
                <div className="d-flex flex-column gap-2" style={{ maxHeight: '180px', overflowY: 'auto' }}>
                  <label className="d-flex align-items-center gap-2 cursor-pointer small text-secondary">
                    <input
                      type="radio"
                      name="brand_filter"
                      checked={!filters.brand}
                      onChange={() => onFilterChange('brand', '')}
                    />
                    <span>All Brands</span>
                  </label>
                  {brandsFacet.map((b, idx) => (
                    <label key={idx} className="d-flex align-items-center gap-2 cursor-pointer small text-dark">
                      <input
                        type="radio"
                        name="brand_filter"
                        checked={filters.brand === b}
                        onChange={() => onFilterChange('brand', b)}
                      />
                      <span className="text-truncate">{b}</span>
                    </label>
                  ))}
                </div>
              </div>
            )}

            {/* Price Filter */}
            <div>
              <h6 className="fw-bold text-dark mb-2" style={{ fontSize: '0.85rem' }}>Price Range (₹)</h6>
              <div className="d-flex align-items-center gap-2">
                <input
                  type="number"
                  className="form-control form-control-sm text-center rounded-3 border"
                  placeholder="Min"
                  value={filters.min_price || ''}
                  onChange={(e) => onFilterChange('min_price', e.target.value)}
                />
                <span className="text-muted">-</span>
                <input
                  type="number"
                  className="form-control form-control-sm text-center rounded-3 border"
                  placeholder="Max"
                  value={filters.max_price || ''}
                  onChange={(e) => onFilterChange('max_price', e.target.value)}
                />
              </div>
            </div>
          </div>
        </div>

        {/* Right Column: Independent Product Grid Area */}
        <div className="col-12 col-lg-9">
          {isLoading ? (
            <SearchGridSkeleton />
          ) : items.length === 0 ? (
            <div className="card border-0 shadow-sm rounded-4 p-5 text-center my-3 bg-white">
              <FiShoppingBag className="text-secondary mx-auto mb-3" style={{ fontSize: '3rem' }} />
              <h4 className="fw-bold text-dark mb-2">No matching products found</h4>
              <p className="text-secondary small mx-auto mb-4" style={{ maxWidth: '420px' }}>
                We couldn't find any items matching your search criteria. Try resetting your active filters or searching for another query.
              </p>
              <button
                type="button"
                className="btn btn-primary rounded-pill px-4 py-2 mx-auto fw-medium"
                onClick={onResetFilters}
              >
                Reset All Filters
              </button>
            </div>
          ) : (
            <div>
              <div className="row g-3 g-md-4 mb-4">
                {items.map((prod) => (
                  <div key={prod.id} className="col-6 col-md-4 col-lg-3">
                    <ProductCard product={toProductCardShape(prod)} />
                  </div>
                ))}
              </div>

              {/* Pagination */}
              {totalPages > 1 && (
                <div className="d-flex justify-content-center mt-5">
                  <nav aria-label="Search pagination">
                    <ul className="pagination mb-0 gap-1">
                      <li className={`page-item ${page <= 1 ? 'disabled' : ''}`}>
                        <button className="page-link rounded-circle border-0 shadow-sm" onClick={() => onPageChange(page - 1)}>
                          &laquo;
                        </button>
                      </li>
                      {Array.from({ length: totalPages }, (_, i) => i + 1).map((pNum) => (
                        <li key={pNum} className={`page-item ${page === pNum ? 'active' : ''}`}>
                          <button className="page-link rounded-circle border-0 shadow-sm" onClick={() => onPageChange(pNum)}>
                            {pNum}
                          </button>
                        </li>
                      ))}
                      <li className={`page-item ${page >= totalPages ? 'disabled' : ''}`}>
                        <button className="page-link rounded-circle border-0 shadow-sm" onClick={() => onPageChange(page + 1)}>
                          &raquo;
                        </button>
                      </li>
                    </ul>
                  </nav>
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default SearchResults;
