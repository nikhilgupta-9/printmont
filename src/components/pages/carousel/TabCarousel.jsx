import React, { useState } from "react";
import { ProductCarousel } from "../../home";
import { bestsellerProduct, bestsellerProducts, dealsandcategories, discount } from "../../../../data/data";

const TabCarousel = ({ allProducts, casual, formal, shorts, jackets, trousers }) => {
  const [activeCategory, setActiveCategory] = useState("All Categories");

  return (
    <div className="boughtTogetherContainer">
      <h2>Bought Together</h2>

      {/* Tabs (no map used) */}
      <div className="tabs">
        <button
          className={`tabButton ${activeCategory === "All Categories" ? "active" : ""}`}
          onClick={() => setActiveCategory("All Categories")}
        >
          All Categories
        </button>
        <button
          className={`tabButton ${activeCategory === "Casual Shirts" ? "active" : ""}`}
          onClick={() => setActiveCategory("Casual Shirts")}
        >
          Casual Shirts
        </button>
        <button
          className={`tabButton ${activeCategory === "Formal Shirts" ? "active" : ""}`}
          onClick={() => setActiveCategory("Formal Shirts")}
        >
          Formal Shirts
        </button>
        <button
          className={`tabButton ${activeCategory === "Men's Shorts" ? "active" : ""}`}
          onClick={() => setActiveCategory("Men's Shorts")}
        >
          Men's Shorts
        </button>
        <button
          className={`tabButton ${activeCategory === "Men's Jackets" ? "active" : ""}`}
          onClick={() => setActiveCategory("Men's Jackets")}
        >
          Men's Jackets
        </button>
        <button
          className={`tabButton ${activeCategory === "Men's Trousers" ? "active" : ""}`}
          onClick={() => setActiveCategory("Men's Trousers")}
        >
          Men's Trousers
        </button>
      </div>

      {/* Carousel Section */}
      <div className="carouselSection">
        {activeCategory === "All Categories" && (
           <ProductCarousel products={bestsellerProducts} title="New" />
        )}
        {activeCategory === "Casual Shirts" && (
          <ProductCarousel products={bestsellerProduct} title="Top Selection"/>
        )}
        {activeCategory === "Formal Shirts" && (
          <ProductCarousel products={discount} title="Discount For You" />
        )}
        {activeCategory === "Men's Shorts" && (
          <ProductCarousel products={dealsandcategories} title="Discount For You" />
        )}
        {activeCategory === "Men's Jackets" && (
          <ProductCarousel products={discount} title="Discount For You" />
        )}
        {activeCategory === "Men's Trousers" && (
          <ProductCarousel products={bestsellerProduct} title="Discount For You" />
        )}
        
      </div>
    </div>
  );
};

export default TabCarousel;
