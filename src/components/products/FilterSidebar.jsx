import React, { useState } from "react";
import { Accordion, Form, Button, Row, Col, InputGroup } from "react-bootstrap";
import { IoIosArrowBack } from "react-icons/io";
import { FiSearch } from "react-icons/fi";
import Slider from "rc-slider";
import "rc-slider/assets/index.css";
import "./Product.css";

const FilterSidebar = ({
  facets,
  selectedCategory,
  selectedBrands,
  selectedSizes,
  priceMin,
  priceMax,
  minDiscount,
  excludeOutOfStock,
  onCategoryChange,
  onBrandToggle,
  onSizeToggle,
  onPriceChange,
  onDiscountChange,
  onInStockToggle,
  onClearAll,
  onCloseMobile
}) => {
  const [brandSearch, setBrandSearch] = useState("");
  const [showAllBrands, setShowAllBrands] = useState(false);

  // Price slider state local handler
  const handleSliderChange = (values) => {
    const [min, max] = values;
    onPriceChange(min, max);
  };

  // Filter brand list based on sub-search
  const filteredBrands = (facets.brands || []).filter((b) =>
    b.name.toLowerCase().includes(brandSearch.toLowerCase())
  );
  const visibleBrands = showAllBrands ? filteredBrands : filteredBrands.slice(0, 7);

  const discountOptions = [
    { label: "50% or more", value: 50 },
    { label: "40% or more", value: 40 },
    { label: "30% or more", value: 30 },
    { label: "20% or more", value: 20 },
    { label: "10% or more", value: 10 }
  ];

  return (
    <div className="ap-filter-sidebar bg-white p-2">
      {/* Mobile Top Header */}
      <div className="d-md-none border-bottom pb-2 mb-2 d-flex justify-content-between align-items-center">
        <div className="d-flex align-items-center">
          <Button variant="link" onClick={onCloseMobile} className="p-0 text-dark me-2">
            <IoIosArrowBack size={22} />
          </Button>
          <h5 className="mb-0 fw-bold fs-6">Filters</h5>
        </div>
        <Button variant="link" size="sm" onClick={onClearAll} className="text-primary text-decoration-none p-0 fw-semibold">
          Clear All
        </Button>
      </div>

      {/* Desktop Header */}
      <div className="d-none d-md-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
        <span className="fw-bold fs-6 text-dark">Filters</span>
        <Button
          variant="link"
          size="sm"
          onClick={onClearAll}
          className="text-primary text-decoration-none p-0 fw-semibold text-uppercase fs-7"
        >
          Clear All
        </Button>
      </div>

      <div className="ap-filter-scroll-container">
        {/* CATEGORIES SECTION */}
        {facets.categories && facets.categories.length > 0 && (
          <div className="ap-filter-section mb-3 border-bottom pb-3">
            <h6 className="ap-filter-heading text-uppercase text-muted fw-bold mb-2">Categories</h6>
            <div className="ap-category-tree ms-1">
              <button
                type="button"
                className={`ap-cat-item btn btn-link p-0 text-start text-decoration-none ${
                  !selectedCategory ? "fw-bold text-primary" : "text-dark"
                }`}
                onClick={() => onCategoryChange("")}
              >
                All Categories
              </button>

              <div className="ms-2 mt-1 d-flex flex-column gap-1">
                {facets.categories.map((cat) => (
                  <button
                    key={cat.name}
                    type="button"
                    className={`ap-cat-item btn btn-link p-0 text-start text-decoration-none ${
                      selectedCategory.toLowerCase() === cat.name.toLowerCase()
                        ? "fw-bold text-primary"
                        : "text-secondary"
                    }`}
                    onClick={() => onCategoryChange(cat.name)}
                  >
                    {cat.name} <span className="ap-count">({cat.count})</span>
                  </button>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* ACCORDION FILTERS */}
        <Accordion defaultActiveKey={["brand", "price", "discount"]} alwaysOpen flush className="ap-filter-accordion">
          {/* BRAND FILTER */}
          {facets.brands && facets.brands.length > 0 && (
            <Accordion.Item eventKey="brand" className="border-bottom">
              <Accordion.Header className="py-1">
                <span className="fw-semibold text-uppercase fs-7">Brand</span>
              </Accordion.Header>
              <Accordion.Body className="pt-1 pb-3 px-1">
                {facets.brands.length > 6 && (
                  <InputGroup size="sm" className="mb-2">
                    <InputGroup.Text className="bg-light border-end-0">
                      <FiSearch size={13} className="text-muted" />
                    </InputGroup.Text>
                    <Form.Control
                      placeholder="Search Brand"
                      value={brandSearch}
                      onChange={(e) => setBrandSearch(e.target.value)}
                      className="border-start-0 bg-light fs-7"
                    />
                  </InputGroup>
                )}

                <div className="ap-checkbox-group">
                  {visibleBrands.map((b) => (
                    <Form.Check
                      key={b.name}
                      type="checkbox"
                      id={`brand-${b.name}`}
                      label={
                        <span className="d-flex justify-content-between w-100 fs-7">
                          <span>{b.name}</span>
                          <span className="text-muted ms-1">({b.count})</span>
                        </span>
                      }
                      checked={selectedBrands.includes(b.name)}
                      onChange={() => onBrandToggle(b.name)}
                      className="mb-1"
                    />
                  ))}
                  {filteredBrands.length === 0 && (
                    <small className="text-muted d-block py-1">No brands match search</small>
                  )}
                </div>

                {filteredBrands.length > 7 && (
                  <button
                    type="button"
                    onClick={() => setShowAllBrands((prev) => !prev)}
                    className="btn btn-link p-0 text-primary text-decoration-none fs-7 fw-semibold mt-1"
                  >
                    {showAllBrands ? "Show Less" : `+ ${filteredBrands.length - 7} More`}
                  </button>
                )}
              </Accordion.Body>
            </Accordion.Item>
          )}

          {/* PRICE FILTER */}
          <Accordion.Item eventKey="price" className="border-bottom">
            <Accordion.Header className="py-1">
              <span className="fw-semibold text-uppercase fs-7">Price</span>
            </Accordion.Header>
            <Accordion.Body className="pt-2 pb-3 px-2">
              <div className="px-1 py-2">
                <Slider
                  range
                  min={facets.minPrice}
                  max={facets.maxPrice}
                  value={[priceMin, priceMax]}
                  onChange={handleSliderChange}
                  pushable={true}
                  trackStyle={[{ backgroundColor: "#2874f0", height: 5 }]}
                  handleStyle={[
                    { borderColor: "#2874f0", backgroundColor: "#fff" },
                    { borderColor: "#2874f0", backgroundColor: "#fff" }
                  ]}
                  railStyle={{ backgroundColor: "#e0e0e0", height: 5 }}
                />
              </div>
              <div className="d-flex justify-content-between align-items-center mt-2 fs-7 fw-semibold">
                <span className="border px-2 py-1 bg-light rounded">₹{priceMin}</span>
                <span className="text-muted">to</span>
                <span className="border px-2 py-1 bg-light rounded">₹{priceMax}</span>
              </div>
            </Accordion.Body>
          </Accordion.Item>

          {/* DISCOUNT FILTER */}
          <Accordion.Item eventKey="discount" className="border-bottom">
            <Accordion.Header className="py-1">
              <span className="fw-semibold text-uppercase fs-7">Discount</span>
            </Accordion.Header>
            <Accordion.Body className="pt-1 pb-3 px-1">
              {discountOptions.map((opt) => (
                <Form.Check
                  key={opt.value}
                  type="radio"
                  name="discount-filter"
                  id={`discount-${opt.value}`}
                  label={<span className="fs-7">{opt.label}</span>}
                  checked={minDiscount === opt.value}
                  onChange={() => onDiscountChange(minDiscount === opt.value ? 0 : opt.value)}
                  className="mb-1"
                />
              ))}
            </Accordion.Body>
          </Accordion.Item>

          {/* SIZES FILTER */}
          {facets.sizes && facets.sizes.length > 0 && (
            <Accordion.Item eventKey="size" className="border-bottom">
              <Accordion.Header className="py-1">
                <span className="fw-semibold text-uppercase fs-7">Size</span>
              </Accordion.Header>
              <Accordion.Body className="pt-1 pb-3 px-1">
                <div className="d-flex flex-wrap gap-1">
                  {facets.sizes.map((s) => {
                    const isSelected = selectedSizes.includes(s);
                    return (
                      <button
                        key={s}
                        type="button"
                        onClick={() => onSizeToggle(s)}
                        className={`btn btn-sm ${
                          isSelected ? "btn-primary" : "btn-outline-secondary"
                        } ap-size-chip`}
                      >
                        {s}
                      </button>
                    );
                  })}
                </div>
              </Accordion.Body>
            </Accordion.Item>
          )}

          {/* AVAILABILITY */}
          <Accordion.Item eventKey="availability" className="border-0">
            <Accordion.Header className="py-1">
              <span className="fw-semibold text-uppercase fs-7">Availability</span>
            </Accordion.Header>
            <Accordion.Body className="pt-1 pb-2 px-1">
              <Form.Check
                type="checkbox"
                id="stock-filter"
                label={<span className="fs-7">Exclude Out of Stock</span>}
                checked={excludeOutOfStock}
                onChange={onInStockToggle}
              />
            </Accordion.Body>
          </Accordion.Item>
        </Accordion>
      </div>

      {/* Mobile Footer Apply Button */}
      <div className="d-md-none border-top pt-2 mt-2">
        <Button variant="danger" className="w-100 fw-bold" onClick={onCloseMobile}>
          Apply Filters
        </Button>
      </div>
    </div>
  );
};

export default FilterSidebar;