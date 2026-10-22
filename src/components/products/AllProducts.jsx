import React, { useState, useEffect, useRef } from "react";
import { Button, Offcanvas, Alert } from "react-bootstrap";
import { MdFilterList } from "react-icons/md";
import { BiSortAlt2 } from "react-icons/bi";
import { FaFilter } from "react-icons/fa";
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
      { rootMargin: "250px 0px" }
    );

    observer.observe(el);
    return () => {
      if (el) observer.unobserve(el);
    };
  }, []);

  return (
    <div ref={ref} className="col-6 col-md-4 col-lg-3 d-flex flex-column">
      {isVisible ? <ProductCard product={product} /> : <ProductCardSkeleton />}
    </div>
  );
};

const AllProducts = ({ categoryScope = null }) => {
  const {
    products,
    totalProductsCount,
    totalPages,
    currentPage,
    loading,
    error,
    facets,
    displayTitle,
    isBestsellerFilter,
    isTopRatedFilter,
    isTopDealFilter,
    isDiscountFilter,
    selectedCategory,
    selectedBrands,
    selectedSizes,
    selectedColors,
    selectedFabrics,
    selectedTypes,
    selectedPatterns,
    selectedGenders,
    selectedFits,
    selectedOccasions,
    priceMin,
    priceMax,
    minDiscount,
    excludeOutOfStock,
    assuredOnly,
    minRating,
    currentSort,
    searchQuery,
    setCategoryFilter,
    toggleBrandFilter,
    toggleSizeFilter,
    toggleColorFilter,
    toggleFabricFilter,
    toggleTypeFilter,
    togglePatternFilter,
    toggleGenderFilter,
    toggleFitFilter,
    toggleOccasionFilter,
    setPriceRangeFilter,
    setMinDiscountFilter,
    toggleInStockFilter,
    toggleAssuredFilter,
    setMinRatingFilter,
    setSortOption,
    setPageNumber,
    clearCollectionFilter,
    clearAllFilters
  } = useProductFilters(categoryScope);

  const [showMobileFilter, setShowMobileFilter] = useState(false);
  const [showMobileSort, setShowMobileSort] = useState(false);

  const sortOptions = [
    "Relevance",
    "Popularity",
    "Price -- Low to High",
    "Price -- High to Low",
    "Newest First"
  ];

  // Scroll to top on page change
  const handlePageChange = (p) => {
    setPageNumber(p);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  // Build active filter chips
  const activeChips = [];
  if (assuredOnly) {
    activeChips.push({ key: "assured", label: "PM Assured", onRemove: toggleAssuredFilter });
  }
  if (selectedCategory) {
    activeChips.push({ key: "category", label: `Category: ${selectedCategory}`, onRemove: () => setCategoryFilter("") });
  }
  selectedBrands.forEach((b) => {
    activeChips.push({ key: `brand-${b}`, label: b, onRemove: () => toggleBrandFilter(b) });
  });
  selectedSizes.forEach((s) => {
    activeChips.push({ key: `size-${s}`, label: `Size: ${s}`, onRemove: () => toggleSizeFilter(s) });
  });
  selectedColors.forEach((c) => {
    activeChips.push({ key: `color-${c}`, label: `Color: ${c}`, onRemove: () => toggleColorFilter(c) });
  });
  selectedFabrics.forEach((f) => {
    activeChips.push({ key: `fabric-${f}`, label: `Fabric: ${f}`, onRemove: () => toggleFabricFilter(f) });
  });
  selectedTypes.forEach((t) => {
    activeChips.push({ key: `type-${t}`, label: `Type: ${t}`, onRemove: () => toggleTypeFilter(t) });
  });
  selectedPatterns.forEach((p) => {
    activeChips.push({ key: `pattern-${p}`, label: `Pattern: ${p}`, onRemove: () => togglePatternFilter(p) });
  });
  selectedGenders.forEach((g) => {
    activeChips.push({ key: `gender-${g}`, label: `Gender: ${g}`, onRemove: () => toggleGenderFilter(g) });
  });
  selectedFits.forEach((fit) => {
    activeChips.push({ key: `fit-${fit}`, label: `Fit: ${fit}`, onRemove: () => toggleFitFilter(fit) });
  });
  selectedOccasions.forEach((o) => {
    activeChips.push({ key: `occasion-${o}`, label: `Occasion: ${o}`, onRemove: () => toggleOccasionFilter(o) });
  });
  if (priceMin > (facets.minPrice || 0) || priceMax < (facets.maxPrice || 5000)) {
    activeChips.push({ key: "price", label: `₹${priceMin} - ₹${priceMax}`, onRemove: () => setPriceRangeFilter(facets.minPrice, facets.maxPrice) });
  }
  if (minDiscount > 0) {
    activeChips.push({ key: "discount", label: `${minDiscount}%+ Off`, onRemove: () => setMinDiscountFilter(0) });
  }
  if (minRating > 0) {
    activeChips.push({ key: "rating", label: `${minRating}★ & above`, onRemove: () => setMinRatingFilter(0) });
  }

  const queryOrCategory = displayTitle || searchQuery || selectedCategory || "All Products";

  return (
    <div className="bg-light min-vh-100 pb-4">
      
      {/* ═══════════════════════════════════════════════════════════
          MOBILE SUB-HEADER FILTER & SORT BAR (Light grey bar)
         ═══════════════════════════════════════════════════════════ */}
      <div className="pm-mobile-filter-sort-bar d-flex d-lg-none">
        <button
          type="button"
          className="pm-m-bar-btn"
          onClick={() => setShowMobileFilter(true)}
        >
          <MdFilterList size={20} />
          <span>Filter</span>
          {activeChips.length > 0 && (
            <span className="pm-m-filter-pill">{activeChips.length}</span>
          )}
        </button>
        <div className="pm-m-bar-divider" />
        <button
          type="button"
          className="pm-m-bar-btn"
          onClick={() => setShowMobileSort(true)}
        >
          <BiSortAlt2 size={20} />
          <span>Sort</span>
        </button>
      </div>

      {/* Main Layout Container */}
      <div className="container-fluid max-width-1400 pt-1 pb-2 pt-lg-1 pb-lg-3 px-1 px-lg-1">
        <div className="row g-2 g-md-3 g-lg-1">
          
          {/* DESKTOP FILTER SIDEBAR (Left Column) */}
          <div className="col-lg-3 col-xl-2-5 d-none d-lg-block">
            <div className="bg-white border rounded shadow-sm sticky-top" style={{ top: "100px" }}>
              {loading ? (
                <FilterSkeleton />
              ) : (
                <FilterSidebar
                  facets={facets}
                  selectedCategory={selectedCategory}
                  selectedBrands={selectedBrands}
                  selectedSizes={selectedSizes}
                  selectedColors={selectedColors}
                  selectedFabrics={selectedFabrics}
                  selectedTypes={selectedTypes}
                  selectedPatterns={selectedPatterns}
                  selectedGenders={selectedGenders}
                  selectedFits={selectedFits}
                  selectedOccasions={selectedOccasions}
                  priceMin={priceMin}
                  priceMax={priceMax}
                  minDiscount={minDiscount}
                  excludeOutOfStock={excludeOutOfStock}
                  assuredOnly={assuredOnly}
                  minRating={minRating}
                  onCategoryChange={setCategoryFilter}
                  onBrandToggle={toggleBrandFilter}
                  onSizeToggle={toggleSizeFilter}
                  onColorToggle={toggleColorFilter}
                  onFabricToggle={toggleFabricFilter}
                  onTypeToggle={toggleTypeFilter}
                  onPatternToggle={togglePatternFilter}
                  onGenderToggle={toggleGenderFilter}
                  onFitToggle={toggleFitFilter}
                  onOccasionToggle={toggleOccasionFilter}
                  onPriceChange={setPriceRangeFilter}
                  onDiscountChange={setMinDiscountFilter}
                  onInStockToggle={toggleInStockFilter}
                  onAssuredToggle={toggleAssuredFilter}
                  onRatingChange={setMinRatingFilter}
                  onClearAll={clearAllFilters}
                />
              )}
            </div>
          </div>

          {/* PRODUCT LISTING CONTENT AREA (Right Column) */}
          <div className="col-12 col-lg-9 col-xl-9-5 px-1 px-lg-0">
            <div className="pm-product-listing-box">
              
              {/* DESKTOP TOP HEADER: BREADCRUMBS, RESULTS COUNTER & SORT BAR */}
              <div className="d-none d-lg-flex flex-column gap-0 border-bottom pb-1 mb-2">
                {/* 1. Breadcrumbs */}
                <nav aria-label="breadcrumb">
                  <ol className="breadcrumb mb-0 fs-8 text-muted" style={{ padding: 0, background: 'transparent' }}>
                    <li className="breadcrumb-item">
                      <Link to="/" className="text-decoration-none text-muted">Home</Link>
                    </li>
                    {selectedCategory ? (
                      <>
                        <li className="breadcrumb-item">
                          <Link to="/allproducts" className="text-decoration-none text-muted">All Products</Link>
                        </li>
                        <li className={`breadcrumb-item ${!isBestsellerFilter && !isTopRatedFilter && !isTopDealFilter ? "active fw-semibold text-dark" : ""}`}>
                          {isBestsellerFilter || isTopRatedFilter || isTopDealFilter ? (
                            <Link to={`/allproducts?category=${encodeURIComponent(selectedCategory)}`} className="text-decoration-none text-muted">
                              {selectedCategory}
                            </Link>
                          ) : (
                            selectedCategory
                          )}
                        </li>
                      </>
                    ) : (
                      <li className={`breadcrumb-item ${!isBestsellerFilter && !isTopRatedFilter && !isTopDealFilter ? "active fw-semibold text-dark" : ""}`}>
                        {isBestsellerFilter || isTopRatedFilter || isTopDealFilter ? (
                          <Link to="/allproducts" className="text-decoration-none text-muted">All Products</Link>
                        ) : (
                          "All Products"
                        )}
                      </li>
                    )}
                    {isBestsellerFilter && (
                      <li className="breadcrumb-item active fw-semibold text-dark" aria-current="page">
                        Best Sellers
                      </li>
                    )}
                    {isTopRatedFilter && (
                      <li className="breadcrumb-item active fw-semibold text-dark" aria-current="page">
                        Top Rated
                      </li>
                    )}
                    {isTopDealFilter && (
                      <li className="breadcrumb-item active fw-semibold text-dark" aria-current="page">
                        Top Deals
                      </li>
                    )}
                  </ol>
                </nav>

                {/* 2. Results count: Showing 1 – 40 of 15,352 results for "t shirt" */}
                <div className="fs-7 text-dark fw-bold mt-0">
                  Showing 1 – {Math.min(currentPage * 40, totalProductsCount || 40)} of {(totalProductsCount || 40).toLocaleString()} results for "{queryOrCategory}"
                </div>

                {/* 3. Sort tabs */}
                <div className="d-flex align-items-center flex-wrap gap-2 fs-7 mt-0">
                  <span className="fw-bold text-dark me-1">Sort By</span>
                  {sortOptions.map((option) => (
                    <button
                      key={option}
                      type="button"
                      className={`pm-sort-tab-btn ${currentSort === option ? "active" : ""}`}
                      onClick={() => setSortOption(option)}
                    >
                      {option}
                    </button>
                  ))}
                </div>
              </div>

              {/* ACTIVE FILTER CHIPS */}
              {activeChips.length > 0 && (
                <div className="d-flex flex-wrap align-items-center gap-1.5 mb-2.5 pb-2 border-bottom">
                  <span className="fs-7 fw-semibold text-muted">Active:</span>
                  {activeChips.map((chip) => (
                    <span key={chip.key} className="badge bg-light text-dark border d-inline-flex align-items-center p-1.5 fs-8">
                      {chip.label}
                      <button
                        type="button"
                        onClick={chip.onRemove}
                        className="btn-close ms-1.5 fs-9"
                        aria-label="Remove"
                      />
                    </span>
                  ))}
                  <button
                    type="button"
                    onClick={clearAllFilters}
                    className="btn btn-link text-primary text-decoration-none fs-8 p-0 ms-1 fw-semibold"
                  >
                    Clear All
                  </button>
                </div>
              )}

              {/* PRODUCT GRID (2 COLUMNS ON MOBILE, 4 COLUMNS ON DESKTOP) */}
              {loading ? (
                <div className="row g-2">
                  {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
                    <div className="col-6 col-md-4 col-lg-3 d-flex flex-column" key={i}>
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
                <div className="row g-2">
                  {products.map((p) => (
                    <LazyProductItem key={p.id} product={p} />
                  ))}
                </div>
              )}

              {/* PAGINATION */}
              {!loading && !error && totalPages > 1 && (
                <div className="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-3 border-top gap-2">
                  <div className="text-muted fs-7">
                    Page {currentPage} of {totalPages}
                  </div>
                  <div className="d-flex align-items-center gap-1">
                    {currentPage > 1 && (
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-secondary px-3"
                        onClick={() => handlePageChange(currentPage - 1)}
                      >
                        Previous
                      </button>
                    )}
                    {Array.from({ length: Math.min(totalPages, 5) }, (_, i) => {
                      let pageNum = i + 1;
                      if (totalPages > 5 && currentPage > 3) {
                        pageNum = currentPage - 2 + i;
                        if (pageNum > totalPages) pageNum = totalPages - (4 - i);
                      }
                      return (
                        <button
                          key={pageNum}
                          type="button"
                          className={`btn btn-sm ${
                            currentPage === pageNum ? "btn-primary" : "btn-outline-secondary"
                          }`}
                          style={{ minWidth: "36px" }}
                          onClick={() => handlePageChange(pageNum)}
                        >
                          {pageNum}
                        </button>
                      );
                    })}
                    {currentPage < totalPages && (
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-secondary px-3"
                        onClick={() => handlePageChange(currentPage + 1)}
                      >
                        Next
                      </button>
                    )}
                  </div>
                </div>
              )}

            </div>
          </div>
        </div>
      </div>

      {/* MOBILE OFFCANVAS FOR FILTERS */}
      <Offcanvas
        show={showMobileFilter}
        onHide={() => setShowMobileFilter(false)}
        placement="start"
        className="w-100"
      >
        <Offcanvas.Body className="p-0">
          <FilterSidebar
            facets={facets}
            selectedCategory={selectedCategory}
            selectedBrands={selectedBrands}
            selectedSizes={selectedSizes}
            selectedColors={selectedColors}
            selectedFabrics={selectedFabrics}
            selectedTypes={selectedTypes}
            selectedPatterns={selectedPatterns}
            selectedGenders={selectedGenders}
            selectedFits={selectedFits}
            selectedOccasions={selectedOccasions}
            priceMin={priceMin}
            priceMax={priceMax}
            minDiscount={minDiscount}
            excludeOutOfStock={excludeOutOfStock}
            assuredOnly={assuredOnly}
            minRating={minRating}
            onCategoryChange={(cat) => { setCategoryFilter(cat); setShowMobileFilter(false); }}
            onBrandToggle={toggleBrandFilter}
            onSizeToggle={toggleSizeFilter}
            onColorToggle={toggleColorFilter}
            onFabricToggle={toggleFabricFilter}
            onTypeToggle={toggleTypeFilter}
            onPatternToggle={togglePatternFilter}
            onGenderToggle={toggleGenderFilter}
            onFitToggle={toggleFitFilter}
            onOccasionToggle={toggleOccasionFilter}
            onPriceChange={setPriceRangeFilter}
            onDiscountChange={setMinDiscountFilter}
            onInStockToggle={toggleInStockFilter}
            onAssuredToggle={toggleAssuredFilter}
            onRatingChange={setMinRatingFilter}
            onClearAll={clearAllFilters}
            onCloseMobile={() => setShowMobileFilter(false)}
          />
        </Offcanvas.Body>
      </Offcanvas>

      {/* MOBILE OFFCANVAS FOR SORT */}
      <Offcanvas
        show={showMobileSort}
        onHide={() => setShowMobileSort(false)}
        placement="bottom"
        style={{ height: "auto" }}
      >
        <Offcanvas.Header closeButton className="border-bottom pb-2">
          <Offcanvas.Title className="fs-6 fw-bold">Sort By</Offcanvas.Title>
        </Offcanvas.Header>
        <Offcanvas.Body className="p-2">
          <div className="d-flex flex-column">
            {sortOptions.map((opt) => (
              <Button
                key={opt}
                variant="light"
                className={`text-start border-0 py-2.5 px-3 fs-7 ${
                  currentSort === opt ? "fw-bold text-primary bg-primary-subtle" : "text-dark"
                }`}
                onClick={() => {
                  setSortOption(opt);
                  setShowMobileSort(false);
                }}
              >
                {opt}
              </Button>
            ))}
          </div>
        </Offcanvas.Body>
      </Offcanvas>
    </div>
  );
};

export default AllProducts;
