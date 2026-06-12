import React, { useState } from 'react';
import { initialReviews } from '../../../data/reviewData';
import { Col, Container, Row } from 'react-bootstrap';
import { RatingSummary, ReviewCard } from '../review/ReviewHelper';
import { Modal } from 'react-bootstrap';
import { FaChevronLeft, FaChevronRight } from 'react-icons/fa';

const ProductReview = () => {
  const [reviews, setReviews] = useState(initialReviews);
  const [showAllInline, setShowAllInline] = useState(false);
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const [lightboxIndex, setLightboxIndex] = useState(0);

  // Flattened array of all customer images
  const allImages = reviews.flatMap(r => r.images || []);

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

  return (
    <>
      <Container className="my-2 px-0">
        <Row className='m-0 p-0'>
          {/* === Left Column: Rating Summary and Distribution === */}
          <Col md={12} className="mb-2 m-0 p-0">
            <RatingSummary reviews={reviews} />
          </Col>

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
      </Container>
    </>
  );
};

export default ProductReview;