import React, { useState, useMemo } from "react";
import { Accordion, Button, Offcanvas, Pagination } from "react-bootstrap";
import { GoSortDesc } from "react-icons/go";
import { FaFilter } from "react-icons/fa6";
import ProductCard from "./ProductCard";
import FilterSidebar from "./FilterSidebar"; // ✅ Import your FilterSidebar
import { productsData } from "../../../data/reviewData"; // assuming you have this
import "./Product.css";

// --- PAGINATION CONSTANTS ---
const PRODUCTS_PER_PAGE = 40; // The required limit
// --- END PAGINATION CONSTANTS ---

const AllProducts = () => {
  const [selectedFilters, setSelectedFilters] = useState([]);
  const [priceRange, setPriceRange] = useState({ min: "", max: "" });
  const [showFilters, setShowFilters] = useState(false);
  const [currentSort, setCurrentSort] = useState("Popularity");
  const [showSort, setShowSort] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);

  const handleCloseSort = () => setShowSort(false);
  const handleShowSort = () => setShowSort(true);

  // --- FILTER HANDLERS (optional if you integrate filtering later) ---
  const handleClearAll = () => {
    setSelectedFilters([]);
    setPriceRange({ min: "", max: "" });
    setCurrentPage(1);
  };

  const handleSortChange = (sortOption) => {
    setCurrentSort(sortOption);
    handleCloseSort();
    setCurrentPage(1);
  };
  // --- END FILTER HANDLERS ---

  // --- SORT + PAGINATION LOGIC ---
  const { paginatedProducts, totalPages } = useMemo(() => {
    let sortableProducts = [...productsData];

    // Sorting logic
    switch (currentSort) {
      case "Price -- Low to High":
        sortableProducts.sort((a, b) => a.discountedPrice - b.discountedPrice);
        break;
      case "Price -- High to Low":
        sortableProducts.sort((a, b) => b.discountedPrice - a.discountedPrice);
        break;
      case "Newest First":
        sortableProducts.sort((a, b) => b.id - a.id);
        break;
      case "Popularity":
      default:
        sortableProducts.sort((a, b) => b.discountPercent - a.discountPercent);
        break;
    }

    // Pagination logic
    const totalCount = sortableProducts.length;
    const totalPages = Math.ceil(totalCount / PRODUCTS_PER_PAGE);
    const startIndex = (currentPage - 1) * PRODUCTS_PER_PAGE;
    const endIndex = startIndex + PRODUCTS_PER_PAGE;
    const paginatedProducts = sortableProducts.slice(startIndex, endIndex);

    return { paginatedProducts, totalPages };
  }, [currentSort, currentPage]);

  // --- PAGINATION UI ---
  const renderPagination = () => {
    if (totalPages <= 1) return null;

    const items = [];
    const MAX_VISIBLE_PAGES = 5;
    let startPage, endPage;

    if (totalPages <= MAX_VISIBLE_PAGES) {
      startPage = 1;
      endPage = totalPages;
    } else {
      const half = Math.floor(MAX_VISIBLE_PAGES / 2);
      if (currentPage <= half) {
        startPage = 1;
        endPage = MAX_VISIBLE_PAGES;
      } else if (currentPage + half >= totalPages) {
        startPage = totalPages - MAX_VISIBLE_PAGES + 1;
        endPage = totalPages;
      } else {
        startPage = currentPage - half;
        endPage = currentPage + half;
      }
    }

    if (startPage > 1) {
      items.push(
        <Pagination.First key="first" onClick={() => setCurrentPage(1)} />
      );
      if (startPage > 2) items.push(<Pagination.Ellipsis key="startEllipsis" />);
    }

    for (let number = startPage; number <= endPage; number++) {
      items.push(
        <Pagination.Item
          key={number}
          active={number === currentPage}
          onClick={() => setCurrentPage(number)}
        >
          {number}
        </Pagination.Item>
      );
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1)
        items.push(<Pagination.Ellipsis key="endEllipsis" />);
      items.push(
        <Pagination.Last
          key="last"
          onClick={() => setCurrentPage(totalPages)}
        />
      );
    }

    return (
      <Pagination className="justify-content-center mt-4 mb-5">
        <Pagination.Prev
          onClick={() => setCurrentPage((prev) => Math.max(prev - 1, 1))}
          disabled={currentPage === 1}
        />
        {items}
        <Pagination.Next
          onClick={() =>
            setCurrentPage((prev) => Math.min(prev + 1, totalPages))
          }
          disabled={currentPage === totalPages}
        />
      </Pagination>
    );
  };
  // --- END PAGINATION UI ---

  return (
    <div className="all-products-container p-0 m-0 position-relative">
      {/* --- Mobile Header for Filter/Sort --- */}
      <div className="d-flex justify-content-between align-items-center p-1 d-lg-none bg-white shadow-sm sticky-top">
        <div className="w-100 d-flex justify-content-evenly align-items-center">
          <Button
            size="sm"
            onClick={handleShowSort}
            className="d-block d-md-none text-dark bg-transparent border-0 fs-6"
          >
            <GoSortDesc size={25} /> Sort
          </Button>
          <div className="w-auto bg-secondary d-flex d-md-none">
            <hr className="rotate-90 w-100 border-1 border" />
          </div>
          <Button
            size="sm"
            className="text-dark bg-transparent border-0 fs-6"
            onClick={() => setShowFilters(!showFilters)}
          >
            <FaFilter /> Filter
          </Button>
        </div>
      </div>

      {/* --- Main Layout: Sidebar + Products --- */}
      <div className="d-flex flex-wrap">
        {/* ✅ Filter Sidebar */}
        <div
          className={`filters-sidebar bg-white border-end p-2 ${
            showFilters ? "show" : ""
          }`}
        >
          <FilterSidebar onClose={() => setShowFilters(false)} />
        </div>

        {/* ✅ Product Grid Section */}
        <div className="products-area flex-grow-1 bg-white p-2">
          {/* Desktop Sort Buttons */}
          <div className="d-flex align-items-center mb-3 border-bottom pb-2 d-none d-md-flex">
            <span className="fw-semibold me-3">Sort By</span>
            {[
              "Popularity",
              "Price -- Low to High",
              "Price -- High to Low",
              "Newest First",
            ].map((sortOption) => (
              <Button
                key={sortOption}
                variant="link"
                className={`text-decoration-none px-2 py-1 me-2 ${
                  sortOption === currentSort
                    ? "text-primary border-bottom border-primary border-2 fw-bold"
                    : "text-muted"
                }`}
                onClick={() => handleSortChange(sortOption)}
              >
                {sortOption}
              </Button>
            ))}
          </div>

          {/* Product Cards */}
          <div className="row g-2">
            {paginatedProducts.map((product) => (
              <div className="col-6 col-md-4 col-lg-3" key={product.id}>
                <ProductCard product={product} />
              </div>
            ))}
          </div>

          {/* Pagination */}
          {renderPagination()}
        </div>
      </div>

      {/* --- Sort Bottom Sheet (Mobile) --- */}
      <Offcanvas
        show={showSort}
        onHide={handleCloseSort}
        placement="bottom"
        className="d-md-none"
        style={{ height: "max-content" }}
      >
        <Offcanvas.Header closeButton>
          <Offcanvas.Title>Sort By</Offcanvas.Title>
        </Offcanvas.Header>
        <Offcanvas.Body className="p-0">
          {[
            "Popularity",
            "Price -- Low to High",
            "Price -- High to Low",
            "Newest First",
          ].map((sortOption) => (
            <Button
              key={sortOption}
              variant="light"
              className={`w-100 text-start border-bottom rounded-0 py-3 ${
                sortOption === currentSort
                  ? "text-primary fw-bold"
                  : "text-dark"
              }`}
              onClick={() => handleSortChange(sortOption)}
            >
              {sortOption}
            </Button>
          ))}
        </Offcanvas.Body>
      </Offcanvas>
    </div>
  );
};

export default AllProducts;
