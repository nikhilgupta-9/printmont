import React, { useRef, useState, useEffect } from 'react';
import { IoIosArrowBack, IoIosArrowForward } from 'react-icons/io';
import { MdExpandLess } from 'react-icons/md';

const SingleProduct = ({
  products,
  title = "Products",
  backgroundImageUrl // ⬅ New prop
}) => {
  const scrollRef = useRef(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(false);

  const updateScrollButtons = () => {
    const container = scrollRef.current;
    if (!container) return;

    const { scrollLeft, scrollWidth, clientWidth } = container;
    setCanScrollLeft(scrollLeft > 0);
    setCanScrollRight(scrollLeft + clientWidth < scrollWidth - 1);
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
    updateScrollButtons();

    const container = scrollRef.current;
    if (!container) return;

    container.addEventListener("scroll", updateScrollButtons);
    window.addEventListener("resize", updateScrollButtons);

    return () => {
      container.removeEventListener("scroll", updateScrollButtons);
      window.removeEventListener("resize", updateScrollButtons);
    };
  }, []);

  return (
    <div
      className={`horizontal-scroll-wrapper position-relative cus-bg mx-1 pt-3 pb-2  border bd ${backgroundImageUrl ? 'custom-bg-image' : ''}`}
      style={{ backgroundImage: backgroundImageUrl ? `url(${backgroundImageUrl})` :  {backgroundColor: 'white'} }}
    >
      <div className='d-flex justify-content-between align-items-start'>
        <p className="fw-semibold fs-5 fs-md-4 mb-1 mb-lg-3 ms-0 ms-lg-3">{title}</p>
        <button className="bg-theme border bd px-2 py-1 rounded d-none d-lg-flex">
          View All{" "}
          <MdExpandLess size={20} style={{ transform: "rotate(90deg)" }} />
        </button>
      </div>

      {canScrollLeft && (
        <button className="scroll-arrow left" onClick={() => scroll('left')}>
          <span className="left-arr-carousel text-black bg-white">
            <IoIosArrowBack />
          </span>
        </button>
      )}

      {canScrollRight && (
        <button className="scroll-arrow right d-sm-none d-lg-flex align-items-center justify-content-end"
          onClick={() => scroll('right')}>
          <span className="right-arr-carousel text-black bg-white">
            <IoIosArrowForward />
          </span>
        </button>
      )}

      <div className="scroll-container m-0 p-0" ref={scrollRef}>
        {products.map((product, index) => (
          <div className="scroll-card bd border m-0 p-0" key={index}>
            <div className="image-container m-0 p-0">
              <img
                src={product.img}
                alt={product.title}
                className="product-image zoom-hover mt-3"
              />
            </div>
            <div className="card-body text-center">
              <p className=" my-2 product-name small text-truncate">{product.title}</p>
              <p className="small my-2 text-success fw-bold">{product.discount}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default SingleProduct;
