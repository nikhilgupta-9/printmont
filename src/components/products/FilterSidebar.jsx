import React, { useState } from "react";
import { Accordion, Form, Button, InputGroup } from "react-bootstrap";
import { IoIosArrowBack, IoIosArrowForward } from "react-icons/io";
import { FiSearch } from "react-icons/fi";
import Slider from "rc-slider";
import "rc-slider/assets/index.css";
import "./Product.css";

const FilterSidebar = ({
  facets = {},
  selectedCategory = "",
  selectedBrands = [],
  selectedSizes = [],
  selectedColors = [],
  selectedFabrics = [],
  selectedTypes = [],
  selectedPatterns = [],
  selectedGenders = [],
  selectedFits = [],
  selectedOccasions = [],
  priceMin = 0,
  priceMax = 5000,
  minDiscount = 0,
  excludeOutOfStock = false,
  assuredOnly = false,
  minRating = 0,
  onCategoryChange,
  onBrandToggle,
  onSizeToggle,
  onColorToggle,
  onFabricToggle,
  onTypeToggle,
  onPatternToggle,
  onGenderToggle,
  onFitToggle,
  onOccasionToggle,
  onPriceChange,
  onDiscountChange,
  onInStockToggle,
  onAssuredToggle,
  onRatingChange,
  onClearAll,
  onCloseMobile
}) => {
  const [brandSearch, setBrandSearch] = useState("");
  const [showAllBrands, setShowAllBrands] = useState(false);

  // Brand search filtering
  const filteredBrands = (facets.brands || []).filter((b) =>
    (b.name || "").toLowerCase().includes(brandSearch.toLowerCase())
  );
  const visibleBrands = showAllBrands ? filteredBrands : filteredBrands.slice(0, 6);

  const hasActiveFilters =
    selectedBrands.length > 0 ||
    selectedSizes.length > 0 ||
    selectedColors.length > 0 ||
    selectedFabrics.length > 0 ||
    selectedTypes.length > 0 ||
    selectedPatterns.length > 0 ||
    selectedGenders.length > 0 ||
    selectedFits.length > 0 ||
    selectedOccasions.length > 0 ||
    minDiscount > 0 ||
    minRating > 0 ||
    assuredOnly ||
    excludeOutOfStock ||
    (priceMin > facets.minPrice || priceMax < facets.maxPrice);

  const handleSliderChange = (values) => {
    const [min, max] = values;
    onPriceChange(min, max);
  };

  const discountOptions = [
    { label: "50% or more", value: 50 },
    { label: "40% or more", value: 40 },
    { label: "30% or more", value: 30 },
    { label: "20% or more", value: 20 },
    { label: "10% or more", value: 10 }
  ];

  const ratingOptions = [
    { label: "4★ & above", value: 4 },
    { label: "3★ & above", value: 3 },
    { label: "2★ & above", value: 2 },
    { label: "1★ & above", value: 1 }
  ];

  // Default fallback lists if dataset is small
  const defaultSizes = facets.sizes && facets.sizes.length ? facets.sizes : ["S", "M", "L", "XL", "XXL", "Free Size"];
  const defaultFabrics = facets.fabrics && facets.fabrics.length ? facets.fabrics : [
    { name: "Cotton", count: 42 },
    { name: "Polyester", count: 28 },
    { name: "Cotton Blend", count: 19 },
    { name: "Ceramic", count: 15 },
    { name: "Canvas", count: 12 },
    { name: "Paper", count: 10 }
  ];
  const defaultColors = facets.colors && facets.colors.length ? facets.colors : [
    { name: "Black", count: 54 },
    { name: "Navy Blue", count: 48 },
    { name: "White", count: 36 },
    { name: "Grey", count: 25 },
    { name: "Red", count: 18 },
    { name: "Blue", count: 15 }
  ];
  const defaultTypes = facets.types && facets.types.length ? facets.types : [
    { name: "T-Shirt", count: 65 },
    { name: "Casual Shirt", count: 32 },
    { name: "Polo", count: 24 },
    { name: "Hoodie", count: 18 },
    { name: "Coffee Mug", count: 14 }
  ];
  const defaultPatterns = facets.patterns && facets.patterns.length ? facets.patterns : [
    { name: "Solid", count: 70 },
    { name: "Graphic Print", count: 45 },
    { name: "Customized", count: 38 },
    { name: "Striped", count: 20 },
    { name: "Embroidered", count: 15 }
  ];
  const defaultGenders = facets.genders && facets.genders.length ? facets.genders : [
    { name: "Men", count: 110 },
    { name: "Women", count: 85 },
    { name: "Unisex", count: 60 },
    { name: "Kids", count: 25 }
  ];
  const defaultFits = facets.fits && facets.fits.length ? facets.fits : [
    { name: "Regular Fit", count: 85 },
    { name: "Slim Fit", count: 45 },
    { name: "Oversized", count: 30 },
    { name: "Relaxed Fit", count: 20 }
  ];
  const defaultOccasions = facets.occasions && facets.occasions.length ? facets.occasions : [
    { name: "Casual", count: 95 },
    { name: "Party", count: 40 },
    { name: "Formal", count: 30 },
    { name: "Corporate", count: 25 },
    { name: "Festive", count: 20 }
  ];

  return (
    <div className="pm-filter-sidebar bg-white px-2 px-lg-0 py-3 h-100 d-flex flex-column">
      {/* Mobile Header */}
      <div className="d-md-none border-bottom pb-2 mb-2 d-flex justify-content-between align-items-center">
        <div className="d-flex align-items-center">
          <Button variant="link" onClick={onCloseMobile} className="p-0 text-dark me-2">
            <IoIosArrowBack size={22} />
          </Button>
          <h5 className="mb-0 fw-bold fs-6">Filters</h5>
        </div>
        {hasActiveFilters && (
          <Button variant="link" size="sm" onClick={onClearAll} className="text-primary text-decoration-none p-0 fw-semibold">
            CLEAR ALL
          </Button>
        )}
      </div>

      {/* Desktop Header */}
      <div className="d-none d-md-flex justify-content-between align-items-center border-bottom pb-2.5 mb-2 px-lg-3">
        <span className="fw-bold fs-5 text-dark">Filters</span>
        {hasActiveFilters && (
          <Button
            variant="link"
            size="sm"
            onClick={onClearAll}
            className="text-primary text-decoration-none p-0 fw-bold text-uppercase fs-7"
          >
            CLEAR ALL
          </Button>
        )}
      </div>

      <div className="flex-grow-1 overflow-auto px-lg-3">
        
        {/* CATEGORIES SECTION */}
        <div className="border-bottom pb-3 mb-2">
          <div className="fw-bold text-uppercase fs-7 text-dark mb-2 d-flex justify-content-between align-items-center" style={{ letterSpacing: '0.5px' }}>
            <span>CATEGORIES</span>
            {selectedCategory && (
              <span
                className="text-primary text-capitalize fs-8 cursor-pointer"
                style={{ cursor: "pointer", fontWeight: 600 }}
                onClick={() => onCategoryChange && onCategoryChange("")}
              >
                Clear
              </span>
            )}
          </div>
          <div className="d-flex flex-column gap-1 ms-1 ms-lg-0" style={{ maxHeight: '200px', overflowY: 'auto' }}>
            <div
              className={`pm-cat-tree-item ${!selectedCategory ? "active fw-bold text-primary" : ""}`}
              style={{ cursor: "pointer" }}
              onClick={() => onCategoryChange && onCategoryChange("")}
            >
              All Categories
            </div>
            {(facets.categories || []).map((cat) => {
              const isSelected = selectedCategory && selectedCategory.toLowerCase() === cat.name.toLowerCase();
              return (
                <div
                  key={cat.name}
                  className={`pm-cat-tree-item d-flex justify-content-between align-items-center ${isSelected ? "active fw-bold text-primary" : ""}`}
                  style={{ cursor: "pointer" }}
                  onClick={() => onCategoryChange && onCategoryChange(cat.name)}
                >
                  <span className="text-truncate" title={cat.name}>
                    {isSelected ? "▶ " : ""}{cat.name}
                  </span>
                  <span className="text-muted fs-8 ms-1">({cat.count})</span>
                </div>
              );
            })}
          </div>
        </div>

        {/* ACCORDION FILTERS */}
        <Accordion
          defaultActiveKey={["brand", "size", "fabric", "color", "type", "pattern", "gender", "fit", "price", "occasion"]}
          alwaysOpen
          flush
          className="pm-accordion-wrap"
        >
          {/* BRAND */}
          <Accordion.Item eventKey="brand" className="border-bottom">
            <Accordion.Header>BRAND</Accordion.Header>
            <Accordion.Body>
              {(facets.brands || []).length > 5 && (
                <InputGroup size="sm" className="mb-2">
                  <InputGroup.Text className="bg-light border-end-0 py-1">
                    <FiSearch size={13} className="text-muted" />
                  </InputGroup.Text>
                  <Form.Control
                    placeholder="Search Brand"
                    value={brandSearch}
                    onChange={(e) => setBrandSearch(e.target.value)}
                    className="border-start-0 bg-light fs-7 py-1"
                  />
                </InputGroup>
              )}
              <div className="d-flex flex-column gap-1.5">
                {(visibleBrands.length ? visibleBrands : [
                  { name: "Printmont", count: 120 },
                  { name: "Roadster", count: 45 },
                  { name: "Highlander", count: 38 },
                  { name: "U.S. Polo", count: 29 },
                  { name: "WROGN", count: 24 }
                ]).map((b) => (
                  <Form.Check
                    key={b.name}
                    type="checkbox"
                    id={`brand-${b.name}`}
                    label={
                      <span className="d-flex justify-content-between w-100 pm-filter-checkbox-label">
                        <span>{b.name}</span>
                        <span className="pm-filter-count">({b.count})</span>
                      </span>
                    }
                    checked={selectedBrands.includes(b.name)}
                    onChange={() => onBrandToggle && onBrandToggle(b.name)}
                    className="mb-0 cursor-pointer"
                  />
                ))}
              </div>
              {filteredBrands.length > 6 && (
                <button
                  type="button"
                  onClick={() => setShowAllBrands((prev) => !prev)}
                  className="btn btn-link p-0 text-primary text-decoration-none fs-7 fw-semibold mt-1"
                >
                  {showAllBrands ? "Show Less" : `+ ${filteredBrands.length - 6} More`}
                </button>
              )}
            </Accordion.Body>
          </Accordion.Item>

          {/* SIZE */}
          <Accordion.Item eventKey="size" className="border-bottom">
            <Accordion.Header>SIZE</Accordion.Header>
            <Accordion.Body>
              <div className="d-flex flex-column gap-1.5">
                {defaultSizes.map((size) => (
                  <Form.Check
                    key={size}
                    type="checkbox"
                    id={`size-${size}`}
                    label={<span className="pm-filter-checkbox-label">{size}</span>}
                    checked={selectedSizes.includes(size)}
                    onChange={() => onSizeToggle && onSizeToggle(size)}
                    className="mb-0 cursor-pointer"
                  />
                ))}
              </div>
            </Accordion.Body>
          </Accordion.Item>

          {/* FABRIC */}
          <Accordion.Item eventKey="fabric" className="border-bottom">
            <Accordion.Header>FABRIC</Accordion.Header>
            <Accordion.Body>
              <div className="d-flex flex-column gap-1.5">
                {defaultFabrics.map((f) => (
                  <Form.Check
                    key={f.name}
                    type="checkbox"
                    id={`fabric-${f.name}`}
                    label={
                      <span className="d-flex justify-content-between w-100 pm-filter-checkbox-label">
                        <span>{f.name}</span>
                        {f.count !== undefined && <span className="pm-filter-count">({f.count})</span>}
                      </span>
                    }
                    checked={selectedFabrics.includes(f.name)}
                    onChange={() => onFabricToggle && onFabricToggle(f.name)}
                    className="mb-0 cursor-pointer"
                  />
                ))}
              </div>
            </Accordion.Body>
          </Accordion.Item>

          {/* COLOR */}
          <Accordion.Item eventKey="color" className="border-bottom">
            <Accordion.Header>COLOR</Accordion.Header>
            <Accordion.Body>
              <div className="d-flex flex-column gap-1.5">
                {defaultColors.map((c) => (
                  <Form.Check
                    key={c.name}
                    type="checkbox"
                    id={`color-${c.name}`}
                    label={
                      <span className="d-flex justify-content-between w-100 pm-filter-checkbox-label">
                        <span>{c.name}</span>
                        {c.count !== undefined && <span className="pm-filter-count">({c.count})</span>}
                      </span>
                    }
                    checked={selectedColors.includes(c.name)}
                    onChange={() => onColorToggle && onColorToggle(c.name)}
                    className="mb-0 cursor-pointer"
                  />
                ))}
              </div>
            </Accordion.Body>
          </Accordion.Item>

          {/* TYPE */}
          <Accordion.Item eventKey="type" className="border-bottom">
            <Accordion.Header>TYPE</Accordion.Header>
            <Accordion.Body>
              <div className="d-flex flex-column gap-1.5">
                {defaultTypes.map((t) => (
                  <Form.Check
                    key={t.name}
                    type="checkbox"
                    id={`type-${t.name}`}
                    label={
                      <span className="d-flex justify-content-between w-100 pm-filter-checkbox-label">
                        <span>{t.name}</span>
                        {t.count !== undefined && <span className="pm-filter-count">({t.count})</span>}
                      </span>
                    }
                    checked={selectedTypes.includes(t.name)}
                    onChange={() => onTypeToggle && onTypeToggle(t.name)}
                    className="mb-0 cursor-pointer"
                  />
                ))}
              </div>
            </Accordion.Body>
          </Accordion.Item>

          {/* PATTERN */}
          <Accordion.Item eventKey="pattern" className="border-bottom">
            <Accordion.Header>PATTERN</Accordion.Header>
            <Accordion.Body>
              <div className="d-flex flex-column gap-1.5">
                {defaultPatterns.map((p) => (
                  <Form.Check
                    key={p.name}
                    type="checkbox"
                    id={`pattern-${p.name}`}
                    label={
                      <span className="d-flex justify-content-between w-100 pm-filter-checkbox-label">
                        <span>{p.name}</span>
                        {p.count !== undefined && <span className="pm-filter-count">({p.count})</span>}
                      </span>
                    }
                    checked={selectedPatterns.includes(p.name)}
                    onChange={() => onPatternToggle && onPatternToggle(p.name)}
                    className="mb-0 cursor-pointer"
                  />
                ))}
              </div>
            </Accordion.Body>
          </Accordion.Item>

          {/* GENDER */}
          <Accordion.Item eventKey="gender" className="border-bottom">
            <Accordion.Header>GENDER</Accordion.Header>
            <Accordion.Body>
              <div className="d-flex flex-column gap-1.5">
                {defaultGenders.map((g) => (
                  <Form.Check
                    key={g.name}
                    type="checkbox"
                    id={`gender-${g.name}`}
                    label={
                      <span className="d-flex justify-content-between w-100 pm-filter-checkbox-label">
                        <span>{g.name}</span>
                        {g.count !== undefined && <span className="pm-filter-count">({g.count})</span>}
                      </span>
                    }
                    checked={selectedGenders.includes(g.name)}
                    onChange={() => onGenderToggle && onGenderToggle(g.name)}
                    className="mb-0 cursor-pointer"
                  />
                ))}
              </div>
            </Accordion.Body>
          </Accordion.Item>

          {/* FIT */}
          <Accordion.Item eventKey="fit" className="border-bottom">
            <Accordion.Header>FIT</Accordion.Header>
            <Accordion.Body>
              <div className="d-flex flex-column gap-1.5">
                {defaultFits.map((f) => (
                  <Form.Check
                    key={f.name}
                    type="checkbox"
                    id={`fit-${f.name}`}
                    label={
                      <span className="d-flex justify-content-between w-100 pm-filter-checkbox-label">
                        <span>{f.name}</span>
                        {f.count !== undefined && <span className="pm-filter-count">({f.count})</span>}
                      </span>
                    }
                    checked={selectedFits.includes(f.name)}
                    onChange={() => onFitToggle && onFitToggle(f.name)}
                    className="mb-0 cursor-pointer"
                  />
                ))}
              </div>
            </Accordion.Body>
          </Accordion.Item>

          {/* PRICE */}
          <Accordion.Item eventKey="price" className="border-bottom">
            <Accordion.Header>PRICE</Accordion.Header>
            <Accordion.Body>
              <div className="px-1 py-2">
                <Slider
                  range
                  min={facets.minPrice || 0}
                  max={facets.maxPrice || 5000}
                  value={[priceMin, priceMax]}
                  onChange={handleSliderChange}
                  pushable={true}
                  trackStyle={[{ backgroundColor: "#0d6efd", height: 5 }]}
                  handleStyle={[
                    { borderColor: "#0d6efd", backgroundColor: "#fff" },
                    { borderColor: "#0d6efd", backgroundColor: "#fff" }
                  ]}
                  railStyle={{ backgroundColor: "#e2e8f0", height: 5 }}
                />
              </div>
              <div className="d-flex align-items-center justify-content-between gap-2 mt-2">
                <select
                  className="form-select form-select-sm fs-7"
                  value={priceMin}
                  onChange={(e) => onPriceChange(Number(e.target.value), priceMax)}
                >
                  <option value={facets.minPrice || 0}>Min</option>
                  {[250, 500, 750, 1000, 1500].filter(v => v >= (facets.minPrice || 0) && v < (facets.maxPrice || 5000)).map(v => (
                    <option key={v} value={v}>₹{v}</option>
                  ))}
                </select>
                <span className="text-muted fs-7">to</span>
                <select
                  className="form-select form-select-sm fs-7"
                  value={priceMax}
                  onChange={(e) => onPriceChange(priceMin, Number(e.target.value))}
                >
                  {[500, 1000, 1500, 2000, 3000, 5000, facets.maxPrice || 5000].filter(v => v > (facets.minPrice || 0)).map(v => (
                    <option key={v} value={v}>₹{v}{v >= (facets.maxPrice || 5000) ? "+" : ""}</option>
                  ))}
                </select>
              </div>
            </Accordion.Body>
          </Accordion.Item>

          {/* OCCASION */}
          <Accordion.Item eventKey="occasion" className="border-bottom">
            <Accordion.Header>OCCASION</Accordion.Header>
            <Accordion.Body>
              <div className="d-flex flex-column gap-1.5">
                {defaultOccasions.map((o) => (
                  <Form.Check
                    key={o.name}
                    type="checkbox"
                    id={`occasion-${o.name}`}
                    label={
                      <span className="d-flex justify-content-between w-100 pm-filter-checkbox-label">
                        <span>{o.name}</span>
                        {o.count !== undefined && <span className="pm-filter-count">({o.count})</span>}
                      </span>
                    }
                    checked={selectedOccasions.includes(o.name)}
                    onChange={() => onOccasionToggle && onOccasionToggle(o.name)}
                    className="mb-0 cursor-pointer"
                  />
                ))}
              </div>
            </Accordion.Body>
          </Accordion.Item>

          {/* DISCOUNT */}
          <Accordion.Item eventKey="discount" className="border-bottom">
            <Accordion.Header>DISCOUNT</Accordion.Header>
            <Accordion.Body>
              <div className="d-flex flex-column gap-1.5">
                {discountOptions.map((opt) => (
                  <Form.Check
                    key={opt.value}
                    type="checkbox"
                    id={`disc-${opt.value}`}
                    label={<span className="pm-filter-checkbox-label">{opt.label}</span>}
                    checked={minDiscount === opt.value}
                    onChange={() => onDiscountChange && onDiscountChange(minDiscount === opt.value ? 0 : opt.value)}
                    className="mb-0 cursor-pointer"
                  />
                ))}
              </div>
            </Accordion.Body>
          </Accordion.Item>

          {/* CUSTOMER RATINGS */}
          <Accordion.Item eventKey="ratings" className="border-bottom">
            <Accordion.Header>CUSTOMER RATINGS</Accordion.Header>
            <Accordion.Body>
              <div className="d-flex flex-column gap-1.5">
                {ratingOptions.map((opt) => (
                  <Form.Check
                    key={opt.value}
                    type="checkbox"
                    id={`rating-${opt.value}`}
                    label={<span className="pm-filter-checkbox-label">{opt.label}</span>}
                    checked={minRating === opt.value}
                    onChange={() => onRatingChange && onRatingChange(minRating === opt.value ? 0 : opt.value)}
                    className="mb-0 cursor-pointer"
                  />
                ))}
              </div>
            </Accordion.Body>
          </Accordion.Item>
        </Accordion>

      </div>
    </div>
  );
};

export default FilterSidebar;