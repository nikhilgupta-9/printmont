import React, { useState } from 'react'
import { initialReviews } from '../../../data/reviewData';
import { Col, Container, Row } from 'react-bootstrap';
import { RatingSummary, ReviewCard, ReviewForm } from '../review/ReviewHelper';
import { Button, Modal } from 'react-bootstrap';


const ProductReview = () => {

  const [reviews, setReviews] = useState(initialReviews);
  const [showGallery, setShowGallery] = useState(false);
  const [allImages, setAllImages] = useState([]);


  const collectAllImages = () => {
    const images = reviews.flatMap(r => r.images);
    setAllImages(images);
    setShowGallery(true);
  };



  // Collect all images for the preview section
  const customerImagesPreview = reviews.flatMap(r => r.images).slice(0, 5);

  return (
    <>
      <Container className="my-2 px-0">

        <Row className='m-0 p-0'>
          {/* === Left Column: Rating Summary and Distribution (md=4) === */}
          <Col md={12} className="mb-2 m-0 p-0">
            <RatingSummary reviews={reviews} />
          </Col>

          {/* === Right Column: Review Form and List (md=8) === */}
          <Col md={12}>

            {/* 2. All Customer Images Preview Section */}
            {/* === Customer Images Preview Section === */}
            <div className="bg-white rounded shadow-sm p-2 mb-3 gap-2 gap-md-4 d-none d-md-flex">
              <div className="d-flex justify-content-between align-items-center flex-wrap flex-md- mb-2">
                <h5 className="mb-0">
                  Customer Images ({reviews.reduce((acc, r) => acc + r.images.length, 0)})
                </h5>
              </div>

              <div className="d-flex flex-wrap gap-2 customer-image-preview">
                {reviews.flatMap(r => r.images).slice(0, 5).map((img, index, arr) => {
                  const totalImages = reviews.flatMap(r => r.images).length;
                  const remaining = totalImages - 5;

                  // 4th image with overlay if more exist
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
                        onClick={collectAllImages}
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
                      onClick={collectAllImages}
                    >
                      <img
                        src={img}
                        alt={`Customer ${index}`}
                        className="w-100 h-100"
                        style={{
                          objectFit: 'cover'
                        }}
                      />
                    </div>
                  );
                })}
              </div>
            </div>


            <hr className='my-2' />

            {/* <ReviewForm onSubmitReview={handleNewReview} specKeys={productSpecs} /> */}

            <h4 className="mb-3">Verified Buyer Reviews ({reviews.length})</h4>
            <div className='p-0 bg-white'>
              {reviews.map((r, i) => <ReviewCard key={i} review={r} />)}
            </div>
          </Col>

        </Row>

        {/* === Image Gallery Modal (Popup) === */}
        <Modal show={showGallery} onHide={() => setShowGallery(false)} size="lg" centered>
          <Modal.Header closeButton>
            <Modal.Title>All Customer Images ({allImages.length})</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Row className='p-0 m-0'>
              {allImages.map((img, index) => (
                <Col xs={4} className="mb-3" key={index}>
                  <img src={img} alt={`Gallery ${index}`} className="img-fluid rounded" style={{ height: '200px', width: '100%', objectFit: 'cover' }} />
                </Col>
              ))}
            </Row>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => setShowGallery(false)}>
              Close Gallery
            </Button>
          </Modal.Footer>
        </Modal>
      </Container>
    </>
  )
}

export default ProductReview