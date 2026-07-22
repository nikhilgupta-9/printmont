import React, { useMemo, useState } from "react";
import { FaStar } from "react-icons/fa";
import "./Product.css";

const products = [
  {
    id: 1,
    image: "/electro/mobile-5.jpeg",
    title: "OPPO K13 5G with 7000mAh",
    brand: "OPPO",
    storage: "128 GB",
    rating: 4.5,
    reviews: 46713,
    price: 17999,
    oldPrice: 22999,
    discount: "21% off",
    isMain: true, // Mark the main product
  },
  {
    id: 2,
    image: "/electro/mobile-4.jpeg",
    title: "45 W GaN 3 A Wall Charger",
    brand: "GLOBAL NOMAD",
    storage: null,
    rating: 4.2,
    reviews: 177,
    price: 1549,
    oldPrice: 2000,
    discount: "22% off",
  },
  {
    id: 3,
    image: "/electro/mobile-1.jpeg",
    title: "Edge To Edge Tempered Glass",
    brand: "Flipkart SmartBuy",
    storage: null,
    rating: 4.0,
    reviews: 341,
    price: 749,
    oldPrice: 899,
    discount: "16% off",
  },
];

// Helper component for the individual item in the vertical list (Mobile View)
const FBTListItem = ({ item, isSelected, onToggle }) => {
  return (
    <div className="fbt-mobile-item">
      {/* Checkbox: Placed on the right via CSS 'order' property */}
      <input
        type="checkbox"
        className="fbt-mobile-checkbox"
        checked={isSelected}
        onChange={() => onToggle(item.id)}
      />

      {/* Main content: image and details */}
      <div className="fbt-mobile-content">
        <div className="fbt-mobile-image-container">
          <img src={item.image} alt={item.title} className="fbt-mobile-image" />
        </div>
        <div className="fbt-mobile-details">
          {/* Brand/Product Status (e.g., "vivo • This product") */}
          <p className="fbt-mobile-brand">
            {item.brand}
            {item.isMain && <span className="fbt-main-indicator"> • This product</span>}
          </p>
          
          {/* Title/Description */}
          <p className="fbt-mobile-title">
            {item.title}
            {item.storage && <span className="fbt-mobile-storage"> ({item.storage})</span>}
          </p>

          {/* Discount and Price Info */}
          <div className="fbt-mobile-price-row">
            {item.discount && (
              <span className="fbt-mobile-discount-percent">
                ↓{item.discount}
              </span>
            )}
            <span className="fbt-mobile-oldprice">
              {item.oldPrice && `₹${item.oldPrice.toLocaleString()}`}
            </span>
            <span className="fbt-mobile-currentprice">
              ₹{item.price.toLocaleString()}
            </span>
          </div>
        </div>
      </div>
    </div>
  );
};

const FrequentlyBoughtTogether = ({ addonIds }) => {
  // 1. Initialize state with IDs of all products
  const [selectedIds, setSelectedIds] = useState(products.map((p) => p.id));

  // 2. Handler to toggle selection of a product
  const handleToggle = (id) => {
    setSelectedIds((prevIds) => {
      if (prevIds.includes(id)) {
        return prevIds.filter((productId) => productId !== id);
      } else {
        return [...prevIds, id];
      }
    });
  };

  // 3. Memoize the price calculations
  const { total, selectedCount, mainProductPrice, addonsPrice } = useMemo(() => {
    const mainProduct = products[0];
    
    // Calculate total price and count for selected items
    const selectedItems = products.filter((item) => selectedIds.includes(item.id));
    
    const grandTotal = selectedItems.reduce((sum, item) => sum + item.price, 0);
    
    // Calculate breakdown for desktop summary
    const currentMainPrice = selectedIds.includes(mainProduct.id) ? mainProduct.price : 0;
    const currentAddonsPrice = selectedItems
      .filter((item) => item.id !== mainProduct.id)
      .reduce((sum, item) => sum + item.price, 0);

    return {
      total: grandTotal,
      selectedCount: selectedItems.length,
      mainProductPrice: currentMainPrice,
      addonsPrice: currentAddonsPrice,
    };
  }, [selectedIds]);

  // Determine the button text based on the selection
  const buttonTextMobile = selectedCount > 0
    ? `Add ${selectedCount} item${selectedCount !== 1 ? "s" : ""} to cart • ₹${total.toLocaleString()}`
    : "Select items to add";

  const buttonTextDesktop = `Add ${selectedCount} Item${selectedCount !== 1 ? "s" : ""} to Cart`;
    
  return (
    <>
      {/* 🖥️ DESKTOP VIEW (Visible on screens >= 768px via CSS) */}
      <div className="fbt-container">
        <h3>Frequently Bought Together</h3>
        <div className="fbt-row row justify-content-center">
          
          {/* Product Cards */}
          <div className="d-flex justify-content-around align-items-stretch col-12 col-lg-8 gap-1 gx-2 ">
            {products.map((item, index) => (
              <React.Fragment key={item.id}>
                <div className="fbt-card position-relative border rounded py-0 px-0 py-lg-1 px-2">
                  <input
                    type="checkbox"
                    name="frequentlyadded"
                    className="fbt-checkbox"
                    checked={selectedIds.includes(item.id)}
                    onChange={() => handleToggle(item.id)}
                  />
                  <div className="fbt-image-container">
                    <img src={item.image} alt={item.title} className="fbt-image" />
                  </div>
                  <div className="fbt-details">
                    <p className="fbt-title">{item.title}</p>
                    <div className="fbt-rating">
                      <span className="fbt-rating-box">
                        {item.rating} <FaStar size={10} color="white" />
                      </span>
                      <span className="fbt-reviews">
                        ({item.reviews.toLocaleString()})
                      </span>
                    </div>
                  </div>
                  <div className="fbt-price">
                    <span className="fbt-current">₹{item.price.toLocaleString()}</span>
                    {item.oldPrice && (
                      <span className="fbt-old">₹{item.oldPrice.toLocaleString()}</span>
                    )}
                    {item.discount && (
                      <span className="fbt-discount">{item.discount}</span>
                    )}
                  </div>
                </div>
                {index < products.length - 1 && <span className="fbt-plus">+</span>}
              </React.Fragment>
            ))}
          </div>

          {/* Price Summary Section */}
          <div className="priceSummaryContainer col-12 col-lg-3">
            <h4 className="priceTitle">Price Summary</h4>

            <div className="priceInfo">
              <div className="priceRow">
                <span className="label">Main Product ({products[0].title.split(' ')[0]})</span>
                <span className="value">₹{mainProductPrice.toLocaleString()}</span>
              </div>

              <div className="priceRowInFre">
                <span className="label">Addons ({selectedCount - (selectedIds.includes(products[0].id) ? 1 : 0)} selected)</span>
                <span className="value">+ ₹{addonsPrice.toLocaleString()}</span>
              </div>

              <div className="priceLine"></div>
            </div>

            <div className="priceRow totalRow">
              <span className="totalLabel">Total</span>
              <span className="totalValue">₹{total.toLocaleString()}</span>
            </div>

            <button className="addToCartBtnInFre" disabled={selectedCount === 0}>
              {buttonTextDesktop}
            </button>
          </div>
        </div>
      </div>

      {/* 📱 MOBILE VIEW (Visible on screens <= 767px via CSS) */}
      <div className="fbt-mobile-container">
        {/* Header with collapsible arrow */}
        <div className="fbt-mobile-header">
          <h3 className="fbt-mobile-heading">Frequently Bought Together</h3>
          <span className="fbt-mobile-collapse-arrow">^</span>
        </div>
        
        {/* List of Products in vertical format */}
        <div className="fbt-mobile-list">
          {products.map((item) => (
            <FBTListItem
              key={item.id}
              item={item}
              isSelected={selectedIds.includes(item.id)}
              onToggle={handleToggle}
            />
          ))}
        </div>

        {/* Action Button: Includes total price and item count */}
        <button className="fbt-mobile-add-to-cart-btn" disabled={selectedCount === 0}>
          {buttonTextMobile}
        </button>
      </div>
    </>
  );
};

export default FrequentlyBoughtTogether;