import React, { useRef, useState, useEffect } from "react";
import { IoIosArrowBack, IoIosArrowForward } from "react-icons/io";
import { MdKeyboardArrowRight } from "react-icons/md";
import { Link } from "react-router-dom"; // ✅ Correct import

const SecondCarousel = ({ apiUrl, title = "Products", badgeText = "" }) => {
  const scrollRef = useRef(null);
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(false);
  const [screenSize, setScreenSize] = useState("sm");

  // 🧠 Fetch Data from API
  useEffect(() => {
    const fetchProducts = async () => {
      try {
        const res = await fetch(apiUrl);
        if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
        const data = await res.json();
        setProducts(data);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };
    fetchProducts();
  }, [apiUrl]);

  // 🧩 Responsive and Scroll Logic
  const updateScreenSize = () => {
    const width = window.innerWidth;
    if (width >= 1200) setScreenSize("xl");
    else if (width >= 992) setScreenSize("lg");
    else if (width >= 768) setScreenSize("md");
    else setScreenSize("sm");
  };

  const updateScrollButtons = () => {
    const container = scrollRef.current;
    if (!container) return;
    const { scrollLeft, scrollWidth, clientWidth } = container;
    setCanScrollLeft(scrollLeft > 5);
    setCanScrollRight(scrollLeft + clientWidth < scrollWidth - 5);
  };

  const scroll = (direction) => {
    const container = scrollRef.current;
    const cardElement = container.querySelector(".scroll-card");
    const cardWidth = cardElement ? cardElement.offsetWidth : 200;
    const scrollAmount = cardWidth * 2;

    if (container) {
      container.scrollBy({
        left: direction === "left" ? -scrollAmount : scrollAmount,
        behavior: "smooth",
      });
    }
  };

  useEffect(() => {
    updateScreenSize();
    updateScrollButtons();

    const container = scrollRef.current;
    if (!container) return;

    container.addEventListener("scroll", updateScrollButtons);
    window.addEventListener("resize", updateScrollButtons);
    window.addEventListener("resize", updateScreenSize);

    return () => {
      container.removeEventListener("scroll", updateScrollButtons);
      window.removeEventListener("resize", updateScrollButtons);
      window.removeEventListener("resize", updateScreenSize);
    };
  }, [products]);

  const getResponsiveCardStyle = (size) => {
    let widthPercentage;
    switch (size) {
      case "xl":
        widthPercentage = "20%";
        break; // 5 cards
      case "lg":
        widthPercentage = "25%";
        break; // 4 cards
      case "md":
        widthPercentage = "33.333%";
        break; // 3 cards
      default:
        widthPercentage = "45%";
        break; // 2 cards
    }
    const margin = 5;
    const totalGutter = 2 * margin;
    return {
      width: `calc(${widthPercentage} - ${totalGutter}px)`,
      flexShrink: 0,
      margin: `${margin}px`,
    };
  };

  if (loading) return <div className="text-center p-5">Loading products...</div>;
  if (error) return <div className="text-center text-danger p-5">Error: {error}</div>;

  return (
    <div className="horizontal-scroll-wrapper position-relative bg-white m-0 px-0 border">
      <div className="d-flex justify-content-between align-items-center">
        <p className="fw-semibold fs-5 fs-lg-4 my-2 ms-1">{title}</p>

        {/* View All Button */}
        <Link
          to="/cart"
          className="d-none d-lg-flex align-items-center justify-content-center rounded bg-theme px-2 py-1 text-white text-decoration-none me-1 my-2"
        >
          View All <MdKeyboardArrowRight size={19} />
        </Link>
      </div>

      {/* Scroll Arrows */}
      {canScrollLeft && (
        <button className="scroll-arrow left" onClick={() => scroll("left")}>
          <span className="left-arr-carousel text-black bg-white ms-3">
            <IoIosArrowBack />
          </span>
        </button>
      )}
      {canScrollRight && (
        <button
          className="scroll-arrow right d-sm-none d-lg-flex align-items-center justify-content-end"
          onClick={() => scroll("right")}
        >
          <span className="right-arr-carousel text-black bg-white">
            <IoIosArrowForward />
          </span>
        </button>
      )}

      {/* Scrollable Container */}
      <div
        className="scroll-container d-flex flex-nowrap align-items-start overflow-x-scroll px-0 m-0 gap-1 px-1 py-1"
        ref={scrollRef}
        style={{ scrollbarWidth: "none", msOverflowStyle: "none" }}
      >
        {products.map((product, index) => (
          <Link
            key={index}
            to="#"
            className="scroll-card border d-flex flex-column p-1 h-100 m-0 text-decoration-none text-dark"
            style={getResponsiveCardStyle(screenSize)}
          >
            <div
              className="image-container bg-white position-relative w-100"
              style={{
                overflow: "hidden",
                aspectRatio: "1 / 1",
                marginBottom: "8px",
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
              }}
            >
              <img
                src={product.img}
                alt={product.title}
                className="product-image w-100 h-100"
                style={{ objectFit: "contain" }}
              />
              {product.badge && (
                <span className="badge bg-primary position-absolute top-0 start-0 m-1">
                  {product.badge}
                </span>
              )}
            </div>

            <div className="d-flex flex-column p-1 w-100 flex-grow-1 justify-content-end">
              {badgeText && (
                <div className="d-none d-lg-flex justify-content-center align-items-center bg-theme text-uppercase text-white fw-medium mt-1 fs-7 border w-100">
                  {badgeText}
                </div>
              )}
              <p className="product-name text-truncate fs-6 mt-1 text-center mb-1">
                {product.title}
              </p>
              <p className="mb-0 p-0 small text-center">
                <span className="fw-bold fs-6">₹{product.price}</span>{" "}
                <del className="text-muted" style={{ fontSize: "0.7rem" }}>
                  ₹{product.originalPrice}
                </del>{" "}
                <span className="text-success fw-bold small">
                  {product.discount}
                </span>
              </p>
            </div>
          </Link>
        ))}
        <div style={{ width: "10px", flexShrink: 0 }}></div>
      </div>
    </div>
  );
};

export default SecondCarousel;
