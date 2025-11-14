import React, { useRef, useState, useEffect } from 'react';
import { IoIosArrowBack, IoIosArrowForward } from 'react-icons/io';
import { MdExpandLess } from 'react-icons/md';
import { Link } from 'react-router';

const SingleProduct = ({
  products,
  title = "Products",
  backgroundImageUrl // optional background
}) => {
  const scrollRef = useRef(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(false);
  const [screenSize, setScreenSize] = useState('sm');

  const updateScreenSize = () => {
    const width = window.innerWidth;
    if (width >= 1200) setScreenSize('xl');
    else if (width >= 992) setScreenSize('lg');
    else if (width >= 768) setScreenSize('md');
    else setScreenSize('sm');
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
    const cardElement = container.querySelector('.scroll-card');
    const cardWidth = cardElement ? cardElement.offsetWidth : 200;
    const scrollAmount = cardWidth * 2;

    if (container) {
      container.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth'
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
      case 'xl': widthPercentage = '20%'; break; // 5 cards
      case 'lg': widthPercentage = '25%'; break; // 4 cards
      case 'md': widthPercentage = '33.333%'; break; // 3 cards
      default: widthPercentage = '45%'; break; // 2 cards
    }
    const margin = 5;
    const totalGutter = 2 * margin;
    return {
      width: `calc(${widthPercentage} - ${totalGutter}px)`,
      flexShrink: 0,
      margin: `${margin}px`
    };
  };

  return (
    <div
      className={`horizontal-scroll-wrapper position-relative mx-0 pt-3 pb-2 border bd ${backgroundImageUrl ? 'custom-bg-image' : ''}`}
      style={{
        backgroundImage: backgroundImageUrl ? `url(${backgroundImageUrl})` : 'none',
        backgroundColor: backgroundImageUrl ? 'transparent' : 'white',
        backgroundSize: 'cover',
        backgroundRepeat: 'no-repeat',
      }}
    >
      {/* Header */}
      <div className='d-flex justify-content-between align-items-start'>
        <p className="fw-semibold fs-5 fs-md-4 mb-1 mb-lg-3 ms-0 ms-lg-3">{title}</p>
        <button className="bg-theme border bd px-2 py-1 rounded d-none d-lg-flex">
          View All{" "}
          <MdExpandLess size={20} style={{ transform: "rotate(90deg)" }} />
        </button>
      </div>

      {/* Scroll Buttons */}
      {canScrollLeft && (
        <button className="scroll-arrow left" onClick={() => scroll('left')}>
          <span className="left-arr-carousel text-black bg-white">
            <IoIosArrowBack />
          </span>
        </button>
      )}

      {canScrollRight && (
        <button
          className="scroll-arrow right d-sm-none d-lg-flex align-items-center justify-content-end"
          onClick={() => scroll('right')}
        >
          <span className="right-arr-carousel text-black bg-white">
            <IoIosArrowForward />
          </span>
        </button>
      )}

      {/* Scrollable Cards */}
      <div
        className="scroll-container d-flex flex-nowrap align-items-start overflow-x-scroll px-0 m-0 gap-1 px-1 py-1"
        ref={scrollRef}
        style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
      >
        {products.map((product, index) => (
          <div
            className="scroll-card border d-flex flex-column p-1 h-100 m-0"
            key={index}
            style={getResponsiveCardStyle(screenSize)}
          >
            {/* ✅ Same Image Style as SecondCarousel */}
            <div
              className="image-container bg-white position-relative w-100"
              style={{
                overflow: 'hidden',
                aspectRatio: '1 / 1',
                marginBottom: '8px',
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center',
                flexShrink: 0,
              }}
            >
              <img
                src={product.img}
                alt={product.title}
                className="product-image w-100 h-100 zoom-hover"
                style={{ objectFit: 'contain' }}
              />
              {product.badge && (
                <span className="badge bg-primary position-absolute top-0 start-0 m-1">
                  {product.badge}
                </span>
              )}
            </div>

            <div className="card-body text-center">
              <Link
                to="#"
                className="product-name text-truncate d-block fs-6 mt-1 text-center li"
                style={{ maxWidth: "100%", textDecoration: 'none',}}
              >
                {product.title}
              </Link>
              <p className="mb-0 p-0 small text-center">

                
                <span className="text-success fw-bold small">{product.discount}</span>
              </p>
            </div>
          </div>
        ))}
        {/* Padding for spacing */}
        <div style={{ width: '10px', flexShrink: 0 }}></div>
      </div>
    </div>
  );
};

export default SingleProduct;
