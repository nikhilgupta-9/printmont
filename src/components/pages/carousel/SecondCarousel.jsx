import React, { useRef, useState, useEffect } from 'react';
import { FaArrowCircleRight, FaArrowCircleLeft } from "react-icons/fa";
import { IoIosArrowBack, IoIosArrowForward } from 'react-icons/io';
import { MdExpandLess, MdKeyboardArrowRight } from "react-icons/md";
import { Link } from 'react-router';


const SecondCarousel = ({
  products,
  title = "Products",
  badgeText = ""
}) => {
  const scrollRef = useRef(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(false);

  const updateScrollButtons = () => {
    const container = scrollRef.current;
    if (!container) return;

    const { scrollLeft, scrollWidth, clientWidth } = container;
    setCanScrollLeft(scrollLeft > 0);
    setCanScrollRight(scrollLeft + clientWidth < scrollWidth - 1); // -1 for rounding errors
  };

  const scroll = (direction) => {
    const container = scrollRef.current;
    const scrollAmount = 300;
    if (container) {
      container.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth'
      });
    }
  };

  useEffect(() => {
    updateScrollButtons(); // Run once on mount

    const container = scrollRef.current;
    if (!container) return;

    // Update on scroll
    container.addEventListener("scroll", updateScrollButtons);

    // Update on resize
    window.addEventListener("resize", updateScrollButtons);

    // Cleanup
    return () => {
      container.removeEventListener("scroll", updateScrollButtons);
      window.removeEventListener("resize", updateScrollButtons);
    };
  }, []);

  return (
    <div className="horizontal-scroll-wrapper position-relative bg-white pt-3 m-0 border bd">
      <div className='d-flex justify-content-between align-items-start'>
        <p className="fw-semibold fs-5 fs-lg-4  mb-3 ms-3 ">{title}</p>
        {/* <button className="bg-theme border bd px-1 py-1 px-lg-2 py-lg-1 circle d-flex align-items-center justify-content-center">
          <span className='d-none d-lg-flex'>View All{" "}</span>
          <MdKeyboardArrowRight size={19} />
        </button> */}
        <button className="bg-theme border bd px-2 py-1 rounded d-none d-lg-flex">
          View All
          <MdExpandLess size={20} style={{ transform: "rotate(90deg)" }} />
        </button>

      </div>

      {/* Show arrows conditionally */}
      {canScrollLeft && (
        <button
          className="scroll-arrow left align-items-center justify-content-end border-0 ms-5 d-none d-lg-flex"
          onClick={() => scroll('left')}
        >
          <span className="left-arr-carousel text-black bg-white" ><IoIosArrowBack />
          </span>
        </button>
      )}

      {canScrollRight && (
        <button
          className="scroll-arrow right  align-items-center justify-content-end border-0 me-2 d-none d-lg-flex"
          onClick={() => scroll('right')}
          aria-label="Scroll right"
        >
          <span className=" right-arr-carousel text-black bg-white"><IoIosArrowForward /></span>
        </button>
      )}

      <div className="scroll-container" ref={scrollRef}>
        {products.map((product, index) => (
          <div className="scroll-card  border pb-1 pb-lg-0" key={index}>
            <div className="image-container bg-white ">
              <img
                src={product.img}
                alt={product.title}
                className="product-image zoom-hover"
              />
              <span className="badge bg-primary position-absolute top-0 start-0 m-1">
                {product.badge}
              </span>
            </div>

            <div className="text-center">
              <div className="d-none d-lg-flex justify-content-center align-item-center bg-theme text-uppercase text-white fw-medium mt-1 fs-7 fs-md-6">
                {badgeText}
              </div>
              {/* <p className="medium mb-0 title text-truncate px-2">{product.title}</p> */}
              <Link
                to="#"
                className="product-name text-truncate d-block small px-1 text-center">
                {product.title}
              </Link>
              <p className="mb-0 title p-1">
                ₹{product.price}{" "}
                <del className="txsm text-muted title">₹{product.originalPrice}</del>{" "}
                <span className="text-success fw-bold txsm">{product.discount}</span>
              </p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default SecondCarousel;
