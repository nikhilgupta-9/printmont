import React, { useState, useEffect, useRef } from "react";
import { Button, Pagination, Offcanvas, Alert } from "react-bootstrap";
import { GoSortDesc } from "react-icons/go";
import { FaFilter, FaTimes } from "react-icons/fa";
import { Link } from "react-router-dom";
import ProductCard from "./ProductCard";
import FilterSidebar from "./FilterSidebar";
import { ProductCardSkeleton, FilterSkeleton } from "./ProductCardSkeleton";
import useProductFilters from "./useProductFilters";
import "./Product.css";

// Lazy Item Wrapper using IntersectionObserver
const LazyProductItem = ({ product }) => {
  const [isVisible, setIsVisible] = useState(false);
  const ref = useRef(null);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;

    if (!("IntersectionObserver" in window)) {
      setIsVisible(true);
      return;
    }

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setIsVisible(true);
          observer.unobserve(el);
        }
      },
      { rootMargin: "200px 0px" }
    );

    observer.observe(el);
    return () => {
      if (el) observer.unobserve(el);
    };
  }, []);

  return (
    <div ref={ref} className="col-6 col-md-4 col-lg-3 d-flex flex-column mb-3">
      {isVisible ? <ProductCard product={product} /> : <ProductCardSkeleton />}
    </div>
  );
};

const AllProducts = () => {
  const {
    products,
    totalProductsCount,
    totalPages,
    currentPage,
    loading,
    error,
    facets,
    selectedCategory,
    selectedBrands,
    selectedSizes,
    priceMin,
    priceMax,
    minDiscount,
    excludeOutOfStock,
    currentSort,
    searchQuery,
    setCategoryFilter,
    toggleBrandFilter,
    toggleSizeFilter,
    setPriceRangeFilter,
    setMinDiscountFilter,
    toggleInStockFilter,
    setSortOption,
    setPageNumber,
    clearAllFilters
  } = useProductFilters();

  const [showMobileFilter, setShowMobileFilter] = useState(false);
  const [showMobileSort, setShowMobileSort] = useState(false);

  const sortOptions = [
    "Popularity",
    "Price -- Low to High",
    "Price -- High to Low",
    "Newest First",
    "Discount"
  ];

  // Scroll to top on page change
  const handlePageChange = (p) => {
    setPageNumber(p);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  // Build list of active filter chips
  const activeChips = [];
  if (selectedCategory) {
    activeChips.push({
      key: "category",
      label: `Category: ${selectedCategory}`,
      onRemove: () => setCategoryFilter("")
    });
  }
  selectedBrands.forEach((b) => {
    activeChips.push({
      key: `brand-${b}`,
      label: b,
      onRemove: () => toggleBrandFilter(b)
    });
  });
  selectedSizes.forEach((s) => {
    activeChips.push({
      key: `size-${s}`,
      label: `Size: ${s}`,
      onRemove: () => toggleSizeFilter(s)
    });
  });
  if (priceMin > facets.minPrice || priceMax < facets.maxPrice) {
    activeChips.push({
      key: "price",
      label: `₹${priceMin} - ₹${priceMax}`,
      onRemove: () => setPriceRangeFilter(facets.minPrice, facets.maxPrice)
    });
  }
  if (minDiscount > 0) {
    activeChips.push({
      key: "discount",
      label: `${minDiscount}%+ Off`,
      onRemove: () => setMinDiscountFilter(0)
    });
  }
  if (excludeOutOfStock) {
    activeChips.push({
      key: "instock",
      label: "In Stock Only",
      onRemove: () => toggleInStockFilter()
    });
  }

  return (
    <div className="ap-page-wrapper bg-light min-vh-100 pb-5">
      {/* Top Breadcrumb Navigation */}
      <div className="bg-white border-bottom py-2 px-3">
        <div className="container-fluid max-width-1400">
          <nav aria-label="breadcrumb">
            <ol className="breadcrumb mb-0 fs-7">
              <li className="breadcrumb-item">
                <Link to="/" className="text-decoration-none text-muted">
                  Home
                </Link>
              </li>
              <li className="breadcrumb-item active fw-semibold text-dark" aria-current="page">
                {selectedCategory ? selectedCategory : "All Products"}
              </li>
            </ol>
          </nav>
        </div>
      </div>

      {/* Main Layout */}
      <div className="container-fluid max-width-1400 py-3">
        {/* Mobile Sticky Bar for Sort & Filter */}
        <div className="d-flex d-lg-none bg-white shadow-sm rounded mb-3 p-2 sticky-top ap-mobile-bar" style={{ zIndex: 100 }}>
          <Button
            variant="light"
            size="sm"
            onClick={() => setShowMobileSort(true)}
            className="flex-fill me-1 border-0 bg-transparent text-dark fw-semibold d-flex align-items-center justify-content-center"
          >
            <GoSortDesc size={20} className="me-1" /> Sort
          </Button>
          <div className="vr my-1 text-muted" />
          <Button
            variant="light"
            size="sm"
            onClick={() => setShowMobileFilter(true)}
            className="flex-fill ms-1 border-0 bg-transparent text-dark fw-semibold d-flex align-items-center justify-content-center"
          >
            <FaFilter size={14} className="me-1" /> Filter {activeChips.length ? `(${activeChips.length})` : ""}
          </Button>
        </div>

        <div className="row g-3">
          {/* DESKTOP FILTER SIDEBAR */}
          <div className="col-lg-3 col-xl-2-5 d-none d-lg-block">
            <div className="bg-white border rounded shadow-sm sticky-top ap-desktop-sidebar">
              {loading ? (
                <FilterSkeleton />
              ) : (
                <FilterSidebar
                  facets={facets}
                  selectedCategory={selectedCategory}
                  selectedBrands={selectedBrands}
                  selectedSizes={selectedSizes}
                  priceMin={priceMin}
                  priceMax={priceMax}
                  minDiscount={minDiscount}
                  excludeOutOfStock={excludeOutOfStock}
                  onCategoryChange={setCategoryFilter}
                  onBrandToggle={toggleBrandFilter}
                  onSizeToggle={toggleSizeFilter}
                  onPriceChange={setPriceRangeFilter}
                  onDiscountChange={setMinDiscountFilter}
                  onInStockToggle={toggleInStockFilter}
                  onClearAll={clearAllFilters}
                />
              )}
            </div>
          </div>

          {/* PRODUCT LISTING CONTENT AREA */}
          <div className="col-12 col-lg-9 col-xl-9-5">
            <div className="bg-white border rounded shadow-sm p-3">
              {/* DESKTOP HEADER & SORT BAR */}
              <div className="d-none d-lg-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                <div className="d-flex align-items-baseline">
                  <h1 className="h5 fw-bold mb-0 me-2 text-dark">
                    {selectedCategory ? selectedCategory : "All Products"}
                  </h1>
                  <span className="text-muted fs-7">
                    (Showing {totalProductsCount} {totalProductsCount === 1 ? "product" : "products"})
                  </span>
                </div>

                {/* Sort buttons matching Flipkart */}
                <div className="d-flex align-items-center fs-7">
                  <span className="fw-semibold me-3 text-muted">Sort By</span>
                  {sortOptions.map((option) => (
                    <button
                      key={option}
                      type="button"
                      className={`btn btn-link text-decoration-none px-2 py-1 me-1 fs-7 ${
                        currentSort === option
                          ? "text-primary border-bottom border-primary border-2 fw-bold"
                          : "text-dark opacity-75"
                      }`}
                      onClick={() => setSortOption(option)}
                    >
                      {option}
                    </button>
                  ))}
                </div>
              </div>

              {/* ACTIVE FILTER CHIPS */}
              {activeChips.length > 0 && (
                <div className="d-flex flex-wrap align-items-center gap-2 mb-3 pb-2 border-bottom">
                  <span className="fs-7 fw-semibold text-muted">Active Filters:</span>
                  {activeChips.map((chip) => (
                    <span key={chip.key} className="ap-filter-chip badge bg-light text-dark border d-inline-flex align-items-center p-2 fs-7">
                      {chip.label}
                      <button
                        type="button"
                        onClick={chip.onRemove}
                        className="btn-close ms-2 fs-8"
                        aria-label="Remove"
                      />
                    </span>
                  ))}
                  <button
                    type="button"
                    onClick={clearAllFilters}
                    className="btn btn-link text-primary text-decoration-none fs-7 p-0 ms-2 fw-semibold"
                  >
                    Clear All
                  </button>
                </div>
              )}

              {/* SEARCH QUERY BANNER */}
              {searchQuery && (
                <div className="alert alert-info py-2 px-3 fs-7 mb-3 d-flex justify-content-between align-items-center">
                  <span>Results for <strong>"{searchQuery}"</strong></span>
                </div>
              )}

              {/* PRODUCT GRID / STATES */}
              {loading ? (
                <div className="row g-2 g-md-3">
                  {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
                    <div className="col-6 col-md-4 col-lg-3 mb-3" key={i}>
                      <ProductCardSkeleton />
                    </div>
                  ))}
                </div>
              ) : error ? (
                <Alert variant="danger" className="m-3">
                  Failed to load products: {error}
                </Alert>
              ) : products.length === 0 ? (
                <div className="text-center py-5">
                  <div className="mb-3">
                    <FaFilter size={40} className="text-muted opacity-50" />
                  </div>
                  <h4 className="h6 fw-bold text-dark">No products found</h4>
                  <p className="text-muted fs-7 mb-3">Try clearing some filters or searching for something else.</p>
                  <Button variant="primary" size="sm" onClick={clearAllFilters}>
                    Clear All Filters
                  </Button>
                </div>
              ) : (
                <div className="row g-2 g-md-3">
                  {products.map((p) => (
                    <LazyProductItem key={p.id} product={p} />
                  ))}
                </div>
              )}

              {/* PAGINATION */}
              {!loading && !error && totalPages > 1 && (
                <div className="d-flex justify-content-center mt-4 mb-2">
                  <Pagination className="ap-pagination">
                    <Pagination.Prev
                      disabled={currentPage === 1}
                      onClick={() => handlePageChange(currentPage - 1)}
                    />
                    {Array.from({ length: totalPages }, (_, idx) => idx + 1)
                      .filter((p) => p === 1 || p === totalPages || Math.abs(p - currentPage) <= 2)
                      .map((p, idx, arr) => {
                        const prev = arr[idx - 1];
                        return (
                          <React.Fragment key={p}>
                            {prev && p - prev > 1 && <Pagination.Ellipsis disabled />}
                            <Pagination.Item
                              active={p === currentPage}
                              onClick={() => handlePageChange(p)}
                            >
                              {p}
                            </Pagination.Item>
                          </React.Fragment>
                        );
                      })}
                    <Pagination.Next
                      disabled={currentPage === totalPages}
                      onClick={() => handlePageChange(currentPage + 1)}
                    />
                  </Pagination>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* MOBILE FILTER OFFCANVAS DRAWER */}
      <Offcanvas
        show={showMobileFilter}
        onHide={() => setShowMobileFilter(false)}
        placement="start"
        className="d-lg-none ap-mobile-offcanvas"
      >
        <Offcanvas.Body className="p-0">
          <FilterSidebar
            facets={facets}
            selectedCategory={selectedCategory}
            selectedBrands={selectedBrands}
            selectedSizes={selectedSizes}
            priceMin={priceMin}
            priceMax={priceMax}
            minDiscount={minDiscount}
            excludeOutOfStock={excludeOutOfStock}
            onCategoryChange={setCategoryFilter}
            onBrandToggle={toggleBrandFilter}
            onSizeToggle={toggleSizeFilter}
            onPriceChange={setPriceRangeFilter}
            onDiscountChange={setMinDiscountFilter}
            onInStockToggle={toggleInStockFilter}
            onClearAll={clearAllFilters}
            onCloseMobile={() => setShowMobileFilter(false)}
          />
        </Offcanvas.Body>
      </Offcanvas>

      {/* MOBILE SORT BOTTOM SHEET */}
      <Offcanvas
        show={showMobileSort}
        onHide={() => setShowMobileSort(false)}
        placement="bottom"
        className="d-lg-none rounded-top"
        style={{ height: "auto" }}
      >
        <Offcanvas.Header closeButton className="border-bottom py-2 px-3">
          <Offcanvas.Title className="fs-6 fw-bold">Sort By</Offcanvas.Title>
        </Offcanvas.Header>
        <Offcanvas.Body className="p-0">
          {sortOptions.map((opt) => (
            <button
              key={opt}
              type="button"
              className={`w-100 text-start btn btn-light rounded-0 border-bottom py-3 px-3 fs-7 ${
                currentSort === opt ? "text-primary fw-bold bg-light" : "text-dark"
              }`}
              onClick={() => {
                setSortOption(opt);
                setShowMobileSort(false);
              }}
            >
              {opt}
            </button>
          ))}
        </Offcanvas.Body>
      </Offcanvas>
    </div>
  );
};

export default AllProducts;
