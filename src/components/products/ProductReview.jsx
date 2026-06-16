import React, { useState } from 'react';
import { initialReviews } from '../../../data/reviewData';
import { Col, Container, Row } from 'react-bootstrap';
import { RatingSummary, ReviewCard } from '../review/ReviewHelper';
import { Modal } from 'react-bootstrap';
import { FaChevronLeft, FaChevronRight, FaChevronDown, FaChevronUp, FaCheckCircle, FaThumbsUp, FaThumbsDown } from 'react-icons/fa';

const ProductReview = ({ hideRatingSummary = false }) => {
  const [reviews, setReviews] = useState(initialReviews);
  const [showAllInline, setShowAllInline] = useState(false);
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const [lightboxIndex, setLightboxIndex] = useState(0);
  const [isReviewsCollapsed, setIsReviewsCollapsed] = useState(false);
  const [allReviewsModalOpen, setAllReviewsModalOpen] = useState(false);
  const [likedReviews, setLikedReviews] = useState({});
  const [dislikedReviews, setDislikedReviews] = useState({});

  // Flattened array of all customer images
  const allImages = reviews.flatMap(r => r.images || []);

  const totalReviews = reviews.length;
  const averageRating = totalReviews
    ? (reviews.reduce((acc, r) => acc + r.overallRating, 0) / totalReviews).toFixed(1)
    : 0;

  const openLightbox = (imgSrc) => {
    const idx = allImages.indexOf(imgSrc);
    if (idx !== -1) {
      setLightboxIndex(idx);
      setLightboxOpen(true);
    }
  };

  const handlePrevImage = () => {
    setLightboxIndex(prev => (prev === 0 ? allImages.length - 1 : prev - 1));
  };

  const handleNextImage = () => {
    setLightboxIndex(prev => (prev === allImages.length - 1 ? 0 : prev + 1));
  };

  const handleLike = (index) => {
    const isLiked = likedReviews[index];
    const isDisliked = dislikedReviews[index];

    // Toggle liked state
    setLikedReviews(prev => ({ ...prev, [index]: !isLiked }));

    setReviews(prev => prev.map((rev, idx) => {
      if (idx === index) {
        let diffLikes = isLiked ? -1 : 1;
        let diffDislikes = 0;
        
        // If it was disliked, remove the dislike
        if (!isLiked && isDisliked) {
          diffDislikes = -1;
          setDislikedReviews(prevD => ({ ...prevD, [index]: false }));
        }

        return { 
          ...rev, 
          likes: Math.max(0, (rev.likes || 0) + diffLikes),
          dislikes: Math.max(0, (rev.dislikes || 0) + diffDislikes)
        };
      }
      return rev;
    }));
  };

  const handleDislike = (index) => {
    const isLiked = likedReviews[index];
    const isDisliked = dislikedReviews[index];

    // Toggle disliked state
    setDislikedReviews(prev => ({ ...prev, [index]: !isDisliked }));

    setReviews(prev => prev.map((rev, idx) => {
      if (idx === index) {
        let diffDislikes = isDisliked ? -1 : 1;
        let diffLikes = 0;

        // If it was liked, remove the like
        if (!isDisliked && isLiked) {
          diffLikes = -1;
          setLikedReviews(prevL => ({ ...prevL, [index]: false }));
        }

        return { 
          ...rev, 
          likes: Math.max(0, (rev.likes || 0) + diffLikes),
          dislikes: Math.max(0, (rev.dislikes || 0) + diffDislikes)
        };
      }
      return rev;
    }));
  };

  return (
    <>
      {/* === DESKTOP VIEW (Medium & Large screens, unchanged) === */}
      <div className="d-none d-md-block">
        <Container className="my-2 px-0">
          <Row className='m-0 p-0'>
            {/* === Left Column: Rating Summary and Distribution === */}
            {!hideRatingSummary && (
              <Col md={12} className="mb-2 m-0 p-0">
                <RatingSummary reviews={reviews} />
              </Col>
            )}

            {/* === Right Column: Review List === */}
            <Col md={12}>
              {/* Customer Images Section */}
              <div className="bg-white rounded border p-3 mb-3">
                <div className="mb-2">
                  <h5 className="mb-0 fw-semibold text-dark">
                    Customer Images ({allImages.length})
                  </h5>
                </div>

                <div className="d-flex flex-wrap gap-2 customer-image-preview mt-2">
                  {showAllInline ? (
                    // Show ALL images inline
                    <>
                      {allImages.map((img, index) => (
                        <div
                          key={index}
                          className="border rounded overflow-hidden"
                          style={{
                            width: '70px',
                            height: '70px',
                            cursor: 'pointer',
                            flexShrink: 0
                          }}
                          onClick={() => openLightbox(img)}
                        >
                          <img
                            src={img}
                            alt={`Customer ${index}`}
                            className="w-100 h-100"
                            style={{ objectFit: 'cover' }}
                          />
                        </div>
                      ))}
                      {/* Collapsing item */}
                      <div
                        className="d-flex align-items-center justify-content-center border rounded text-secondary bg-light"
                        style={{
                          width: '70px',
                          height: '70px',
                          cursor: 'pointer',
                          flexShrink: 0,
                          fontSize: '0.75rem',
                          fontWeight: 'bold'
                        }}
                        onClick={() => setShowAllInline(false)}
                      >
                        Show Less
                      </div>
                    </>
                  ) : (
                    // Show only 5 images with +remaining on the 5th
                    allImages.slice(0, 5).map((img, index) => {
                      const remaining = allImages.length - 5;

                      // 5th image with overlay
                      if (index === 4 && remaining > 0) {
                        return (
                          <div
                            key={index}
                            className="position-relative border rounded overflow-hidden"
                            style={{
                              width: '70px',
                              height: '70px',
                              cursor: 'pointer',
                              flexShrink: 0
                            }}
                            onClick={() => setShowAllInline(true)}
                          >
                            <img
                              src={img}
                              alt={`Customer ${index}`}
                              className="w-100 h-100"
                              style={{
                                objectFit: 'cover',
                                filter: 'brightness(50%)'
                              }}
                            />
                            <span
                              className="position-absolute top-50 start-50 translate-middle text-white fw-semibold"
                              style={{
                                fontSize: '0.9rem',
                                whiteSpace: 'nowrap'
                              }}
                            >
                              +{remaining}
                            </span>
                          </div>
                        );
                      }

                      // Normal image
                      return (
                        <div
                          key={index}
                          className="border rounded overflow-hidden"
                          style={{
                            width: '70px',
                            height: '70px',
                            cursor: 'pointer',
                            flexShrink: 0
                          }}
                          onClick={() => openLightbox(img)}
                        >
                          <img
                            src={img}
                            alt={`Customer ${index}`}
                            className="w-100 h-100"
                            style={{ objectFit: 'cover' }}
                          />
                        </div>
                      );
                    })
                  )}
                </div>
              </div>

              <hr className='my-2' />

              <h4 className="mb-3 text-dark fw-bold" style={{ fontSize: '1.2rem' }}>Verified Buyer Reviews ({reviews.length})</h4>
              <div className='p-0 bg-white'>
                {reviews.map((r, i) => (
                  <ReviewCard key={i} review={r} onImageClick={openLightbox} />
                ))}
              </div>
            </Col>
          </Row>
        </Container>
      </div>

      {/* === MOBILE VIEW (Figma screen, layout strictly isolated) === */}
      <div className="d-block d-md-none px-2 py-3 bg-white mb-2">
        {/* Header Title with collapse button */}
        <div className="d-flex align-items-center justify-content-between mb-3">
          <h4 className="fw-bold text-dark mb-0" style={{ fontSize: '1.3rem', letterSpacing: '-0.3px' }}>Ratings and reviews</h4>
          <button 
            type="button"
            className="btn p-0 bg-light rounded-3 d-flex align-items-center justify-content-center border"
            style={{ width: '32px', height: '32px' }}
            onClick={() => setIsReviewsCollapsed(!isReviewsCollapsed)}
          >
            {isReviewsCollapsed ? <FaChevronDown size={12} className="text-secondary" /> : <FaChevronUp size={12} className="text-secondary" />}
          </button>
        </div>

        {/* Collapsible Content */}
        {!isReviewsCollapsed && (
          <div>
            {/* Rating Overview */}
            {!hideRatingSummary && (
              <div className="mb-3">
                <div className="d-flex align-items-center gap-2 mb-1">
                  <span className="fw-bold text-dark fs-2" style={{ lineHeight: '1.1' }}>{averageRating}</span>
                  <span className="text-success fs-3" style={{ lineHeight: '1' }}>★</span>
                  <span className="badge text-success px-2 py-1 fw-bold text-xs" style={{ backgroundColor: '#eefcf5', border: '1px solid #d1f4e0', borderRadius: '4px' }}>Good</span>
                </div>
                <div className="text-muted text-xs d-flex align-items-center flex-wrap gap-1" style={{ fontSize: '0.8rem' }}>
                  based on 6,388 ratings by <span className="d-inline-flex align-items-center text-secondary gap-1"><FaCheckCircle size={12} className="text-secondary" />Verified Buyers</span>
                </div>
              </div>
            )}

            {/* Customer Images Custom Grid */}
            {allImages.length > 0 && (
              <div className="mb-4">
                <div className="d-flex gap-2">
                  {/* Left big image */}
                  <div 
                    className="border rounded overflow-hidden position-relative" 
                    style={{ flex: '1 1 50%', aspectRatio: '0.9', cursor: 'pointer' }}
                    onClick={() => openLightbox(allImages[0])}
                  >
                    <img src={allImages[0]} alt="Customer highlight" className="w-100 h-100 object-fit-cover" style={{ objectFit: 'cover' }} />
                  </div>
                  {/* Right 2x2 grid */}
                  <div style={{ flex: '1 1 50%', display: 'grid', gridTemplateColumns: '1fr 1fr', gridTemplateRows: '1fr 1fr', gap: '8px' }}>
                    {allImages.slice(1, 5).map((img, idx) => {
                      const isLast = idx === 3;
                      const remainingCount = allImages.length - 5;
                      return (
                        <div 
                          key={idx} 
                          className="border rounded overflow-hidden position-relative cursor-pointer"
                          style={{ aspectRatio: '1.2' }}
                          onClick={() => {
                            if (isLast && remainingCount > 0) {
                              setShowAllInline(true);
                            } else {
                              openLightbox(img);
                            }
                          }}
                        >
                          <img 
                            src={img} 
                            alt={`Customer thumbnail ${idx}`} 
                            className="w-100 h-100 object-fit-cover" 
                            style={{ objectFit: 'cover', filter: isLast && remainingCount > 0 ? 'brightness(50%)' : 'none' }}
                          />
                          {isLast && remainingCount > 0 && (
                            <div className="position-absolute top-50 start-50 translate-middle text-white fw-bold" style={{ fontSize: '0.95rem' }}>
                              +{remainingCount + 32}
                            </div>
                          )}
                        </div>
                      )
                    })}
                  </div>
                </div>
              </div>
            )}

            {/* Features customers loved pills */}
            <div className="mb-4">
              <div className="text-secondary fw-bold mb-2" style={{ fontSize: '0.88rem' }}>Features customers loved</div>
              <div className="d-flex gap-2 overflow-auto pb-2 scrollbar-hidden" style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}>
                <span className="badge text-dark border rounded-pill px-3 py-2 fw-semibold text-xs bg-white" style={{ border: '1px solid #dee2e6 !important', whiteSpace: 'nowrap', fontSize: '0.82rem' }}>Fabric Quality</span>
                <span className="badge text-dark border rounded-pill px-3 py-2 fw-semibold text-xs bg-white" style={{ border: '1px solid #dee2e6 !important', whiteSpace: 'nowrap', fontSize: '0.82rem' }}>Colour</span>
                <span className="badge text-dark border rounded-pill px-3 py-2 fw-semibold text-xs bg-white" style={{ border: '1px solid #dee2e6 !important', whiteSpace: 'nowrap', fontSize: '0.82rem' }}>Style</span>
                <span className="badge text-dark border rounded-pill px-3 py-2 fw-semibold text-xs bg-white" style={{ border: '1px solid #dee2e6 !important', whiteSpace: 'nowrap', fontSize: '0.82rem' }}>Comfort</span>
                <span className="badge text-dark border rounded-pill px-3 py-2 fw-semibold text-xs bg-white" style={{ border: '1px solid #dee2e6 !important', whiteSpace: 'nowrap', fontSize: '0.82rem' }}>True to Size</span>
              </div>
            </div>

            {/* Horizontal Scrolling Review Cards */}
            <div className="d-flex gap-3 overflow-auto pb-3 mb-3 scrollbar-hidden" style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}>
              {reviews.map((r, i) => (
                <div 
                  key={i} 
                  className="p-3 border rounded-3 flex-shrink-0"
                  style={{ width: '275px', backgroundColor: '#f6f6f6', border: '1px solid #eaeaea', borderRadius: '14px' }}
                >
                  <div className="d-flex align-items-center justify-content-between mb-2">
                    <div className="d-flex align-items-center gap-2">
                      <div className="border rounded px-2 py-0.5 d-flex align-items-center bg-white text-xs fw-bold" style={{ borderColor: '#dee2e6', borderRadius: '4px' }}>
                        <span style={{ fontSize: '0.8rem', color: '#000' }}>{Math.round(r.overallRating)}</span>
                        <span className="text-success" style={{ fontSize: '0.85rem', marginLeft: '2px', marginRight: '2px' }}>★</span>
                      </div>
                      <span className="fw-bold text-dark text-xs text-truncate" style={{ maxWidth: '140px', fontSize: '0.85rem' }}>
                        {r.overallRating >= 4.5 ? 'Mind-blowing purch...' : r.overallRating >= 4 ? 'Very Good purchase' : 'Good product'}
                      </span>
                    </div>
                    <span className="text-muted text-xs" style={{ fontSize: '0.75rem' }}>1 year ago</span>
                  </div>
                  <p className="text-dark mb-3 text-truncate-2" style={{ height: '38px', overflow: 'hidden', display: '-webkit-box', WebKitLineClamp: 2, WebKitBoxOrient: 'vertical', fontSize: '0.82rem', lineHeight: '1.4', color: '#495057' }}>
                    {r.comment}
                  </p>
                  <div className="d-flex align-items-center justify-content-between mt-2 pt-2 border-top" style={{ borderColor: '#e9ecef !important' }}>
                    <div>
                      <div className="fw-bold text-dark" style={{ fontSize: '0.8rem' }}>{r.user}</div>
                      <div className="text-muted d-flex align-items-center gap-1" style={{ fontSize: '0.7rem' }}>
                        <FaCheckCircle size={10} className="text-secondary" /> Verified Buyer
                      </div>
                    </div>
                    <div className="d-flex align-items-center gap-2">
                      <button 
                        type="button" 
                        className={`btn p-0 border-0 bg-transparent d-flex align-items-center gap-1 text-xs ${likedReviews[i] ? 'text-primary' : 'text-muted'}`} 
                        style={{ fontSize: '0.75rem' }}
                        onClick={() => handleLike(i)}
                      >
                        <FaThumbsUp size={11} /> {r.likes || 0}
                      </button>
                      <button 
                        type="button" 
                        className={`btn p-0 border-0 bg-transparent d-flex align-items-center gap-1 text-xs ${dislikedReviews[i] ? 'text-danger' : 'text-muted'}`} 
                        style={{ fontSize: '0.75rem' }}
                        onClick={() => handleDislike(i)}
                      >
                        <FaThumbsDown size={11} /> {r.dislikes || 0}
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* Show All Reviews Button */}
            <button 
              type="button"
              className="btn btn-white border w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2 mb-2 bg-white"
              style={{ fontSize: '0.9rem', borderColor: '#e5e5e5', color: '#212529', borderRadius: '12px' }}
              onClick={() => setAllReviewsModalOpen(true)}
            >
              Show all reviews <FaChevronRight size={10} className="text-secondary" />
            </button>
          </div>
        )}
      </div>

        {/* === Lightbox Carousel Modal === */}
        <Modal show={lightboxOpen} onHide={() => setLightboxOpen(false)} size="lg" centered contentClassName="bg-dark text-white border-0 shadow">
          <Modal.Header closeButton closeVariant="white" className="border-0 pb-0">
            <Modal.Title style={{ fontSize: '1.05rem', fontWeight: '600' }}>Customer Uploads</Modal.Title>
          </Modal.Header>
          
          <Modal.Body className="d-flex align-items-center justify-content-between p-4 position-relative" style={{ minHeight: '350px' }}>
            {/* Left Nav Arrow */}
            <button
              className="btn text-white position-absolute start-0 ms-3 border-0 bg-black bg-opacity-50 rounded-circle d-flex align-items-center justify-content-center"
              style={{ width: '40px', height: '40px', zIndex: 10, outline: 'none' }}
              onClick={handlePrevImage}
            >
              <FaChevronLeft size="18" />
            </button>

            {/* Main Active Image */}
            <div className="w-100 d-flex justify-content-center align-items-center" style={{ height: '420px' }}>
              <img
                src={allImages[lightboxIndex]}
                alt={`Customer upload ${lightboxIndex}`}
                style={{ maxHeight: '100%', maxWidth: '100%', objectFit: 'contain', borderRadius: '4px' }}
              />
            </div>

            {/* Right Nav Arrow */}
            <button
              className="btn text-white position-absolute end-0 me-3 border-0 bg-black bg-opacity-50 rounded-circle d-flex align-items-center justify-content-center"
              style={{ width: '40px', height: '40px', zIndex: 10, outline: 'none' }}
              onClick={handleNextImage}
            >
              <FaChevronRight size="18" />
            </button>
          </Modal.Body>

          {/* Modal bottom thumbnails list to select/jump */}
          <Modal.Footer className="border-0 pt-0 justify-content-center gap-2 flex-wrap pb-3">
            {allImages.map((img, idx) => (
              <div
                key={idx}
                className={`border rounded overflow-hidden cursor-pointer ${lightboxIndex === idx ? 'border-primary border-3' : 'border-secondary opacity-50'}`}
                style={{ width: '45px', height: '45px', transition: 'all 0.2s', transform: lightboxIndex === idx ? 'scale(1.05)' : 'none' }}
                onClick={() => setLightboxIndex(idx)}
              >
                <img src={img} alt={`Thumb ${idx}`} className="w-100 h-100" style={{ objectFit: 'cover' }} />
              </div>
            ))}
          </Modal.Footer>
        </Modal>

        {/* === All Reviews Modal === */}
        <Modal show={allReviewsModalOpen} onHide={() => setAllReviewsModalOpen(false)} scrollable centered size="lg">
          <Modal.Header closeButton>
            <Modal.Title className="fw-bold text-dark" style={{ fontSize: '1.2rem' }}>All Verified Reviews ({reviews.length})</Modal.Title>
          </Modal.Header>
          <Modal.Body className="bg-light p-3">
            {reviews.map((r, i) => (
              <div key={i} className="bg-white p-3 rounded mb-3 border shadow-sm">
                <div className="d-flex align-items-center gap-2 mb-2">
                  <div className="border rounded px-2 py-0.5 d-flex align-items-center bg-white text-xs fw-bold" style={{ borderColor: '#dee2e6', borderRadius: '4px' }}>
                    <span style={{ fontSize: '0.8rem', color: '#000' }}>{Math.round(r.overallRating)}</span>
                    <span className="text-success" style={{ fontSize: '0.85rem', marginLeft: '2px', marginRight: '2px' }}>★</span>
                  </div>
                  <span className="fw-bold text-dark text-xs" style={{ fontSize: '0.85rem' }}>
                    {r.overallRating >= 4.5 ? 'Mind-blowing purchase' : r.overallRating >= 4 ? 'Very Good purchase' : 'Good product'}
                  </span>
                  <span className="text-muted text-xs ms-auto" style={{ fontSize: '0.75rem' }}>1 year ago</span>
                </div>
                <p className="text-dark mb-2" style={{ fontSize: '0.85rem', lineHeight: '1.5' }}>
                  {r.comment}
                </p>
                {r.images && r.images.length > 0 && (
                  <div className="d-flex gap-2 my-2 overflow-auto pb-1 scrollbar-hidden">
                    {r.images.map((img, idx) => (
                      <img 
                        key={idx} 
                        src={img} 
                        alt="customer upload" 
                        style={{ width: '60px', height: '60px', objectFit: 'cover', borderRadius: '4px', cursor: 'pointer' }}
                        onClick={() => {
                          setAllReviewsModalOpen(false);
                          openLightbox(img);
                        }}
                      />
                    ))}
                  </div>
                )}
                <div className="d-flex align-items-center justify-content-between mt-2 pt-2 border-top" style={{ borderColor: '#f1f1f1' }}>
                  <div>
                    <div className="fw-bold text-dark" style={{ fontSize: '0.8rem' }}>{r.user}</div>
                    <div className="text-muted d-flex align-items-center gap-1" style={{ fontSize: '0.7rem' }}>
                      <FaCheckCircle size={10} className="text-success" /> Verified Buyer
                    </div>
                  </div>
                  <div className="d-flex align-items-center gap-2">
                    <button 
                      type="button" 
                      className={`btn p-0 border-0 bg-transparent d-flex align-items-center gap-1 text-xs ${likedReviews[i] ? 'text-primary' : 'text-muted'}`} 
                      style={{ fontSize: '0.75rem' }}
                      onClick={() => handleLike(i)}
                    >
                      <FaThumbsUp size={11} /> {r.likes || 0}
                    </button>
                    <button 
                      type="button" 
                      className={`btn p-0 border-0 bg-transparent d-flex align-items-center gap-1 text-xs ${dislikedReviews[i] ? 'text-danger' : 'text-muted'}`} 
                      style={{ fontSize: '0.75rem' }}
                      onClick={() => handleDislike(i)}
                    >
                      <FaThumbsDown size={11} /> {r.dislikes || 0}
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </Modal.Body>
        </Modal>
    </>
  );
};

export default ProductReview;