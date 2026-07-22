import React from 'react';
import { Link } from 'react-router-dom';
import { ASSET_URL } from '../../config/apiEndpoints';
import { SearchGridSkeleton } from './SearchSkeleton';
import { FiFilter, FiRefreshCw, FiShoppingBag } from 'react-icons/fi';

const SearchResults = ({
  query,
  results,
  isLoading,
  filters,
  onFilterChange,
  onResetFilters,
  onPageChange,
  onSortChange
}) => {
  const { items = [], total = 0, page = 1, totalPages = 1, facets = {} } = results || {};
  const categoriesFacet = facets.categories || [];
  const brandsFacet = facets.brands || [];

  return (
    <div className="container-fluid px-3 px-md-4 px-xl-5 py-4">
      {/* Search Header Bar */}
      <div className="d-flex flex-column flex-md-row align-items-md-center justify-content-between pb-3 mb-4 border-bottom gap-3">
        <div>
          <h2 className="fw-bold text-dark mb-1" style={{ fontSize: '1.65rem' }}>
            {query ? `Search Results for "${query}"` : 'All Products Catalog'}
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

      <div className="row g-4">
        {/* Left Column: Fixed / Sticky Non-Scrolling Sidebar */}
        <div className="col-12 col-lg-3 col-xl-2.5">
          <div 
            className="card border-0 shadow-sm rounded-4 p-3.5"
            style={{
              position: 'sticky',
              top: '95px',
              maxHeight: 'calc(100vh - 110px)',
              overflowY: 'auto',
              zIndex: 10
            }}
          >
            <div className="d-flex align-items-center justify-content-between pb-2.5 border-bottom mb-3">
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
            {categoriesFacet.length > 0 && (
              <div className="mb-4">
                <h6 className="fw-bold text-dark mb-2" style={{ fontSize: '0.85rem' }}>Categories</h6>
                <div className="d-flex flex-column gap-1.5" style={{ maxHeight: '220px', overflowY: 'auto' }}>
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
                <div className="d-flex flex-column gap-1.5" style={{ maxHeight: '180px', overflowY: 'auto' }}>
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
        <div className="col-12 col-lg-9 col-xl-9.5">
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
              <div className="row g-4 mb-4">
                {items.map((prod) => {
                  const imgUrl = prod.image
                    ? prod.image.startsWith('http')
                      ? prod.image
                      : `${ASSET_URL}${prod.image}`
                    : 'https://via.placeholder.com/300x300?text=No+Image';

                  return (
                    <div key={prod.id} className="col-12 col-sm-6 col-md-4 col-xl-3">
                      <div className="card border-0 shadow-sm rounded-4 h-100 overflow-hidden hover-shadow transition-all bg-white">
                        {/* Image Box */}
                        <Link to={`/product/${prod.id}`} className="position-relative d-block overflow-hidden bg-light" style={{ paddingTop: '85%' }}>
                          <img
                            src={imgUrl}
                            alt={prod.name}
                            className="position-absolute top-0 start-0 w-100 h-100 object-fit-cover hover-scale"
                            onError={(e) => {
                              e.target.src = 'https://via.placeholder.com/300x300?text=No+Image';
                            }}
                          />
                        </Link>

                        {/* Product Info */}
                        <div className="card-body p-3 d-flex flex-column">
                          <div className="badge bg-primary-subtle text-primary border-primary-subtle align-self-start mb-2 small fw-normal">
                            {prod.category_name || 'General'}
                          </div>
                          <h6 className="card-title fw-bold text-dark mb-1 text-truncate" style={{ fontSize: '0.92rem' }}>
                            <Link to={`/product/${prod.id}`} className="text-dark text-decoration-none hover-text-primary">
                              {prod.name}
                            </Link>
                          </h6>
                          {prod.brand && <div className="text-secondary small mb-2">{prod.brand}</div>}

                          <div className="mt-auto d-flex align-items-center justify-content-between pt-2 border-top">
                            <div>
                              <span className="fw-bold text-dark fs-6">
                                ₹{prod.discount_price ? prod.discount_price.toLocaleString('en-IN') : prod.price.toLocaleString('en-IN')}
                              </span>
                              {prod.discount_price && (
                                <span className="text-muted text-decoration-line-through ms-1.5 small" style={{ fontSize: '0.75rem' }}>
                                  ₹{prod.price.toLocaleString('en-IN')}
                                </span>
                              )}
                            </div>
                            <Link to={`/product/${prod.id}`} className="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-medium" style={{ fontSize: '0.8rem' }}>
                              View
                            </Link>
                          </div>
                        </div>
                      </div>
                    </div>
                  );
                })}
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
