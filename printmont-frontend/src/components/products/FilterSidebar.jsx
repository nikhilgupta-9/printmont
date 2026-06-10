import React, { useState } from "react";
import { Col, Accordion, Form, Button, Row } from "react-bootstrap";
import { IoIosArrowBack } from "react-icons/io";
import { Link } from "react-router";
import { accordionFilters } from "../../../data/reviewData";

// --- Library Imports for the Slider ---
//
// 1. THIS IS THE FIX: Use the default import 'Slider'
import Slider from 'rc-slider'; 
//
// 2. Import the default CSS for rc-slider
import 'rc-slider/assets/index.css';

// ====================================================================
// This component now uses the <Slider> component with the 'range' prop
// ====================================================================
const MultiRangeSlider = ({ min, max, minRange, maxRange, onChange }) => {
  
  /**
   * rc-slider's onChange provides an array: [min, max].
   * This handler converts it to the {min, max} object your
   * parent component (FilterSidebar) expects.
   */
  const handleSliderChange = (values) => {
    const [newMin, newMax] = values;
    onChange({ min: newMin, max: newMax });
  };

  return (
    // We add some padding so the slider handles don't get cut off
    <div style={{ padding: '10px 5px' }}>
      {/* THIS IS THE SECOND PART OF THE FIX:
        We use <Slider> and add the 'range' prop to it.
      */}
      <Slider
        range 
        min={min}
        max={max}
        value={[minRange, maxRange]}
        onChange={handleSliderChange}
        pushable={true} 
      />
    </div>
  );
};
// ====================================================================

const FilterSidebar = ({ onClose, onFilterChange }) => {
  const [selectedFilters, setSelectedFilters] = useState({});
  const [activeKeys, setActiveKeys] = useState([]);

  // Define breakpoints and max price
  const priceBreakpoints = [0, 500, 1000, 1500, 2000, 3000, 4000, 5000];
  const maxPrice = priceBreakpoints[priceBreakpoints.length - 1];
  const [priceRange, setPriceRange] = useState({ min: 0, max: maxPrice });

  // Handle checkbox selection
  const handleCheckboxChange = (filterKey, option) => {
    setSelectedFilters((prev) => {
      const prevOptions = prev[filterKey] || [];
      const isSelected = prevOptions.includes(option);
      const updatedFilters = {
        ...prev,
        [filterKey]: isSelected
          ? prevOptions.filter((o) => o !== option)
          : [...prevOptions, option],
      };
      onFilterChange(updatedFilters, priceRange);
      return updatedFilters;
    });
  };

  // Accordion toggle
  const handleAccordionSelect = (key) => {
    setActiveKeys((prev) =>
      prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]
    );
  };

  /**
   * Handler for the MultiRangeSlider's onChange event.
   * It snaps the raw slider values to the closest defined breakpoint.
   */
  const handleMultiRangeChange = ({ min: rawMin, max: rawMax }) => {
    // Logic to snap raw slider values to the closest price breakpoint
    const closestMin = priceBreakpoints.reduce((prev, curr) =>
      Math.abs(curr - rawMin) < Math.abs(prev - rawMin) ? curr : prev
    );
    const closestMax = priceBreakpoints.reduce((prev, curr) =>
      Math.abs(curr - rawMax) < Math.abs(prev - rawMax) ? curr : prev
    );

    let newMin = closestMin;
    let newMax = closestMax;

    // Ensure min never exceeds max
    if (newMin > newMax) {
      newMin = newMax;
    }

    const newRange = { min: newMin, max: newMax };
    setPriceRange(newRange);
    onFilterChange(selectedFilters, newRange);
  };

  // Clear all
  const handleClearFilters = () => {
    const resetFilters = {};
    const resetPrice = { min: 0, max: maxPrice };
    setSelectedFilters(resetFilters);
    setPriceRange(resetPrice);
    setActiveKeys([]);
    onFilterChange(resetFilters, resetPrice);
  };

  return (
    <Col className="m-0 p-1 px-0">
      {/* Mobile Header */}
      <div className="d-md-none bg-white border-bottom p-3 sticky-top" style={{ zIndex: 100 }}>
        <Row className="align-items-center">
          <Col xs={2}>
            <Button variant="link" onClick={onClose} className="p-0 text-dark fs-5">
              <IoIosArrowBack />
            </Button>
          </Col>
          <Col xs={10}>
            <h5 className="m-0 text-dark fw-bold">Filters</h5>
          </Col>
        </Row>
      </div>

      <div className="d-block d-md-block" style={{ maxHeight: "calc(100vh - 120px)", overflowY: "auto" }}>
        {/* Categories */}
        <div className="mb-3 text-dark pro-filter-cate">
          <h6>CATEGORIES</h6>
          <ul className="list-unstyled ms-2 mt-2">
            <Link to={"#"} className="text-decoration-none">
              <li className="text-muted text-truncated">
                <IoIosArrowBack /> Clothing and Accesso...
              </li>
            </Link>
            <Link to={"#"}>
              <li className="fw-semibold">
                <IoIosArrowBack /> Formal Shirts
              </li>
            </Link>
          </ul>
        </div>

        {/* Accordion filters */}
        <Accordion activeKey={activeKeys} alwaysOpen flush>
          {accordionFilters.map((filter, index) => {
            const key = filter.key || `filter-${index}`;
            return (
              <Accordion.Item key={key} eventKey={key} onClick={() => handleAccordionSelect(key)}>
                <Accordion.Header>{filter.title}</Accordion.Header>
                <Accordion.Body>
                  {filter.options?.map((option, i) => (
                    <Form.Check
                      key={`${key}-${i}`}
                      type="checkbox"
                      id={`${key}-${i}`}
                      label={option}
                      checked={selectedFilters[key]?.includes(option) || false}
                      onChange={() => handleCheckboxChange(key, option)}
                    />
                  ))}
                </Accordion.Body>
              </Accordion.Item>
            );
          })}
        </Accordion>

        {/* Price Filter - Updated as requested */}
        <div className="price-filter mt-3 bg-white p-2 border-top border-bottom">
          <h6 className="fw-semibold mb-3">PRICE</h6>

          {/* THE SINGLE DUAL-RANGE SLIDER (using rc-slider) */}
          <MultiRangeSlider
            min={0}
            max={maxPrice}
            minRange={priceRange.min}
            maxRange={priceRange.max}
            onChange={handleMultiRangeChange}
          />

          {/* Display current MIN and MAX range values */}
          <div className="d-flex justify-content-between mt-3 mb-2 fw-bold">
            <span>Min: ₹{priceRange.min}</span>
            <span>Max: ₹{priceRange.max}</span>
          </div>

        </div>
      </div>

      {/* Mobile Footer */}
      <div className="d-md-none fixed-bottom bg-white border-top p-2" style={{ zIndex: 100 }}>
        <Row className="g-2">
          <Col xs={6}>
            <Button variant="outline-primary" className="w-100 fw-bold" onClick={handleClearFilters}>
              Clear
            </Button>
          </Col>
          <Col xs={6}>
            <Button variant="danger" className="w-100 fw-bold" onClick={() => onClose()}>
              Apply Filter
            </Button>
          </Col>
        </Row>
      </div>
    </Col>
  );
};

export default FilterSidebar;