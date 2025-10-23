import React, { useState, useEffect } from 'react';
import { Container, Row, Col } from 'react-bootstrap';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';
import 'swiper/css';
import 'swiper/css/scrollbar';

import {
  FaShoppingCart,
  FaBolt,
  FaStar,
  FaTag,
  FaMapMarkerAlt,
  FaHeart,
  FaChevronUp,
  FaChevronDown,
  FaShare
} from 'react-icons/fa';

import SliderReact from 'react-slick';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Scrollbar } from 'swiper/modules';
import ProductReview from './ProductReview';
import ProductQASection from './ProductQASection';
import FrequentlyBoughtTogether from './FrequentlyBoughtTogether';
import SecondCarousel from "./../pages/carousel/SecondCarousel"
import { discount, girloutfit } from '../../../data/data';
import { Singleproductdata } from '../../../data/reviewData';
import TabCarousel from '../pages/carousel/TabCarousel';
import SingleProduct from '../pages/carousel/Singleproduct';

// Custom arrows for vertical thumbnails
const PrevArrow = ({ className, onClick }) => (
  <div className={`${className} custom-arrow-top`} onClick={onClick} style={{ cursor: 'pointer' }}>
    <FaChevronUp size="15" color="#333" />
  </div>
);
const NextArrow = ({ className, onClick }) => (
  <div className={`${className} custom-arrow-bottom`} onClick={onClick} style={{ cursor: 'pointer' }}>
    <FaChevronDown size="15" color="#333" />
  </div>
);

// Countdown Timer
const CountdownTimer = ({ targetDate, offerText }) => {
  const calculateTimeLeft = () => {
    const difference = +new Date(targetDate) - +new Date();
    let timeLeft = {};
    if (difference > 0) {
      timeLeft = {
        days: Math.floor(difference / (1000 * 60 * 60 * 24)),
        hours: Math.floor((difference / (1000 * 60 * 60)) % 24),
        minutes: Math.floor((difference / 1000 / 60) % 60),
        seconds: Math.floor((difference / 1000) % 60),
      };
    }
    return timeLeft;
  };

  const [timeLeft, setTimeLeft] = useState(calculateTimeLeft());

  useEffect(() => {
    const timer = setInterval(() => {
      setTimeLeft(calculateTimeLeft());
    }, 1000);
    return () => clearInterval(timer);
  }, [targetDate]);

  if (!timeLeft.days && !timeLeft.hours && !timeLeft.minutes && !timeLeft.seconds) {
    return null;
  }

  return (
    <div className="d-flex align-items-center blue-bg rounded px-3 py-2 mb-3 shadow-sm justifycenter">
      <div className=" text-dark fw-bold px-2  rounded me-2 d-none d-md-block">
        <img src="/sale-png.png" width={50} height={'auto'} alt="" />
      </div>
      <span className="text-dark small fw-semibold">
        sale ends in{" "}
        <span className="text-white text-lg-black fw-semibold">

          <small className='p-1 bg-primary w-auto fw-bold rounded '>{String(timeLeft.days).padStart(2, "0")}</small> <span className='text-dark '>Hrs:</span>{""}
          <small className='p-1 bg-primary w-auto fw-bold rounded'>{String(timeLeft.minutes).padStart(2, "0")}</small> <span className='text-dark'>mins:</span> {" "}
          <small className='p-1 bg-primary w-auto fw-bold rounded'>{String(timeLeft.seconds).padStart(2, "0")}</small> <span className='text-dark'>secs:</span>
        </span>
      </span>
    </div>
  );
};

// Product Data


const ProductDetails = () => {
  const [activeImage, setActiveImage] = useState(Singleproductdata.images[0]);
  const [isWishlisted, setIsWishlisted] = useState(false);
  const [useCarousel, setUseCarousel] = useState(window.innerWidth < 992);

  useEffect(() => {
    const handleResize = () => {
      setUseCarousel(window.innerWidth < 992);
    };
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  const thumbnailSettings = {
    dots: false,
    infinite: false,
    slidesToShow: 5,
    slidesToScroll: 1,
    vertical: true,
    verticalSwiping: true,
    arrows: true,
    prevArrow: <PrevArrow />,
    nextArrow: <NextArrow />,
  };

  return (
    <div className='px-0 px-xl-5 '>
      <Container fluid className="app-main-container px-0 px-xl-4 ">
        <Row className="justify-content-center m-0 p-0">
          <Col xs={12} className="product-page-container px-0 pt-1 bg-white rounded shadow-sm">
            <Row className="p-0 m-0 ">

              {/* Left Column: Images */}
              <Col xs={12} lg={5} className="p-0 d-flex flex-column ">
                <div className="position-sticky" style={{ top: "80px" }}>

                  {/* offer timer */}
                  <div className="d-block d-lg-none mt-2">
                    <CountdownTimer
                      targetDate={Singleproductdata.offerEnds}
                      offerText={Singleproductdata.offerText}
                    />
                  </div>

                  {useCarousel ? (
                    // ✅ Mobile Swiper
                    <Col xs={12} className="product-image-section px-0 product-image-main">
                      <Swiper
                        scrollbar={{ hide: true }}
                        modules={[Scrollbar]}
                        className="mySwiper"
                        slidesPerView={1}
                      >
                        {Singleproductdata.images.map((img, idx) => (
                          <SwiperSlide key={idx}>
                            <div className="product-image-main d-flex justify-content-center align-items-center">
                              <img
                                src={img}
                                alt={`Product ${idx}`}
                                className="img-fluid"
                                style={{ maxHeight: "100%", objectFit: "contain" }}
                              />
                            </div>
                          </SwiperSlide>
                        ))}
                      </Swiper>
                    </Col>
                  ) : (
                    // ✅ Desktop gallery
                    <div className="d-flex justify-content-center">
                      <Col lg={2} className="py-0 m-0 p-0 image-gallery-sidebar position-relative">
                        <SliderReact {...thumbnailSettings} className="position-sticky">
                          {Singleproductdata.images.map((img, index) => (
                            <div
                              key={index}
                              className="d-flex justify-content-center unselectable w-auto"
                            >
                              <img
                                src={img}
                                alt={`Thumbnail ${index + 1}`}
                                className={`thumbnail ${activeImage === img ? "active" : ""} border bd`}
                                onClick={() => setActiveImage(img)}
                                style={{
                                  border: activeImage === img ? "2px solid #007bff" : "none",
                                  borderRadius: "4px",
                                }}
                              />
                            </div>
                          ))}
                        </SliderReact>
                      </Col>

                      <Col lg={10} className="d-flex flex-column justify-content-between align-items-center position-relative product-image-section">
                        <div className="wishlist-icon-main position-absolute top-1 end-0" onClick={() => setIsWishlisted((prev) => !prev)} style={{ cursor: "pointer", zIndex: 10 }}>
                          <FaHeart size="13" className={`wishicon ${isWishlisted ? "active-heart" : ""}`} />
                        </div>

                        <div className="wishlist-icon-main position-absolute top-1 end-0 mt-5" style={{ zIndex: 10 }} >
                          <FaShare size="13" className="wishicon" />
                        </div>

                        <div className="product-image-main mb-3 d-flex align-items-start justify-content-center h-auto">
                          <img
                            src={activeImage}
                            alt="Main Product"
                            className="img-fluid unselectable"
                            style={{ maxHeight: "100%", objectFit: "contain" }}
                          />
                        </div>

                        <div className={`action-buttons-wrapper w-100 ${useCarousel ? "mb-1 px-2" : ""}`}>
                          <div className="d-flex w-100 gap-1">
                            <button className="w-50 product-but-font bg-warning">
                              <FaShoppingCart className="me-2" /> ADD TO CART
                            </button>
                            <button className="w-50 product-but-font">
                              <FaBolt className="me-2" /> BUY NOW
                            </button>
                          </div>
                        </div>
                      </Col>
                    </div>
                  )}
                </div>
              </Col>

                  {/* Right Side Content */}
              <Col lg={7} className="py-2 m-0 p-2  product-details-section rounded">

                {/* Banner on desktop */}
                 <div className='p-3 border bd rounded w-100'>
                  <div className="d-none d-lg-block">
                    <CountdownTimer
                      targetDate={Singleproductdata.offerEnds}
                      offerText={Singleproductdata.offerText}
                    />
                  </div>

                  <h1 className="product-title">{Singleproductdata.title}</h1>
                  <div className="d-flex align-items-center my-2">
                    <span className="rating-box me-2">
                      {Singleproductdata.rating} <FaStar size="0.8em" />
                    </span>
                    <span className="reviews-text text-muted">
                      {Singleproductdata.reviewCount} Ratings & {Singleproductdata.ratingCount} Reviews
                    </span>
                  </div>
                  <p className="special-price">Extra ₹2000 off</p>
                  <div className="price-box d-flex align-items-center mb-4">
                    <span className="current-price">₹{Singleproductdata.currentPrice}</span>
                    <span className="original-price text-muted mx-3">₹{Singleproductdata.originalPrice}</span>
                    <span className="discount">{Singleproductdata.discount} off</span>
                  </div>

                  <div className="available-offers my-3">
                    <h5 className="mb-3">Available offers</h5>
                    {Singleproductdata.offers.map((offer, index) => (
                      <p key={index} className="offer-item d-block small">
                        <FaTag className="text-success me-2" />
                        <strong>{offer.type}</strong> {offer.text} <a href="#" className="text-decoration-none">T&C</a>
                      </p>
                    ))}
                  </div>

                  <div className="delivery-details d-flex align-items-center my-4 border-top pt-3">
                    <FaMapMarkerAlt className="me-2 text-muted" />
                    <span className="me-3 text-dark">Deliver to</span>
                    <div className="input-group" style={{ maxWidth: '250px' }}>
                      <input type="text" className="form-control" placeholder="Enter Delivery Pincode" />
                      <button className="btn btn-link text-decoration-none" type="button">Check</button>
                    </div>
                  </div>

                  <div className="product-highlights border-top pt-3">
                    <h5 className="mb-3">Highlights</h5>
                    <ul>
                      {Singleproductdata.highlights.map((item, index) => (
                        <li key={index}>{item}</li>
                      ))}
                    </ul>
                  </div>

                  <div className="product-specifications-section px-0 px-lg-2 pt-4">
                    <h5 className="spec-heading mb-4">Specifications</h5>
                    {Singleproductdata.specifications.map((specGroup, groupIndex) => (
                      <div key={groupIndex} className="spec-group mb-4">
                        <h5 className="spec-group-title">{specGroup.group}</h5>
                        {specGroup.details.map((detail, detailIndex) => (
                          <Row key={detailIndex} className="spec-item py-1">
                            <Col xs={6} className="spec-label text-muted">{detail.label}</Col>
                            <Col xs={6} className="spec-value">{detail.value}</Col>
                          </Row>
                        ))}
                      </div>
                    ))}
                  </div>
                </div>
                <div className='w-100'>
                  <ProductReview />
                </div>
                    

                <div>
                  <ProductQASection />
                </div>


              </Col>
            </Row>

          </Col>
        </Row>

         <Row className='p-0 mx-0 my-2 w-100'>
          <FrequentlyBoughtTogether />

        </Row>
        <Row className='p-0 mx-0 my-2 w-100'>
          <SecondCarousel products={discount} title="Similar Products" />
        </Row>

        <Row className='p-0 mx-0 my-2 w-100 d-none d-lg-block'>
          <TabCarousel />
        </Row>


        <Row className='p-0 mx-0 my-2 w-100'>
          <SingleProduct products={girloutfit} title="Recent View" />
        </Row>
      </Container>
    </div>
  );
};

export default ProductDetails;
