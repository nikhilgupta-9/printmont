// src/components/ReviewComponents.js

import React, { useEffect, useState } from 'react';
import { Form, Button, Card, Row, Col, ProgressBar } from 'react-bootstrap';
import { FaThumbsUp, FaThumbsDown } from 'react-icons/fa';
import { IoMdImages } from 'react-icons/io';
import { LuUpload } from 'react-icons/lu';
import { MdOutlineStar } from 'react-icons/md';
import { CircularProgressbar, buildStyles } from 'react-circular-progressbar';
import 'react-circular-progressbar/dist/styles.css';
import StarRating from './StarRating';
import { BiSolidCommentDetail } from 'react-icons/bi';

// =========================================================
// A. ReviewForm Component   {Comment And Review Box}
// =========================================================

export const ReviewForm = ({ onSubmitReview, specKeys }) => {
  const initialSpecs = specKeys.reduce((acc, key) => {
    acc[key.toLowerCase()] = 0;
    return acc;
  }, {});

  const initialFormData = {
    overallRating: 0,
    comment: '',
    ...initialSpecs,
    images: [],
  };

  const [formData, setFormData] = useState(initialFormData);

  // ✅ cleanup previews
  useEffect(() => {
    return () => {
      formData.images.forEach(img => {
        if (img.preview) URL.revokeObjectURL(img.preview);
      });
    };
  }, [formData.images]);

  const handleRatingChange = (field, rating) => {
    setFormData(prev => ({ ...prev, [field]: rating }));
  };

  const handleChange = (e) => {
    setFormData(prev => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const handleImageUpload = (e) => {
    const files = Array.from(e.target.files)
      .filter(file => file.type.startsWith("image/"))
      .slice(0, 5 - formData.images.length);

    const filesWithPreview = files.map(file => ({
      file,
      preview: URL.createObjectURL(file),
    }));

    setFormData(prev => ({
      ...prev,
      images: [...prev.images, ...filesWithPreview],
    }));

    e.target.value = null;
  };

  const handleRemoveImage = (index) => {
    const newImages = [...formData.images];
    URL.revokeObjectURL(newImages[index].preview);
    newImages.splice(index, 1);
    setFormData(prev => ({ ...prev, images: newImages }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (formData.overallRating === 0 || !formData.comment.trim()) {
      alert('Please provide an overall rating and a comment.');
      return;
    }

    const specsRating = specKeys.reduce((acc, key) => {
      acc[key.toLowerCase()] = formData[key.toLowerCase()];
      return acc;
    }, {});

    const newReview = {
      overallRating: formData.overallRating,
      comment: formData.comment,
      specsRating,
      images: formData.images.map(img => img.preview),
    };

    onSubmitReview(newReview);
    setFormData(initialFormData);
  };

  return (
    <Card className="mb-4 p-3">
      <Card.Title className="text-primary">Write a Review</Card.Title>
      <Form onSubmit={handleSubmit}>
        <Form.Group className="mb-3">
          <Form.Label className="fw-bold">Overall Rating</Form.Label>
          <StarRating
            rating={formData.overallRating}
            onRate={(r) => handleRatingChange('overallRating', r)}
            isInteractive={true}
            size={30}
          />
        </Form.Group>

        <div className="mb-3">
          <Form.Label className="mb-2 fw-bold">Rate Key Specs</Form.Label>
          <Row className="flex-column">
            {specKeys.map(spec => (
              <Col xs={12} key={spec}>
                <div className="d-flex align-items-start">
                  <span className="me-2">{spec}:</span>
                  <StarRating
                    rating={formData[spec.toLowerCase()]}
                    onRate={(r) => handleRatingChange(spec.toLowerCase(), r)}
                    isInteractive={true}
                    size={18}
                  />
                </div>
              </Col>
            ))}
          </Row>
        </div>

        {/* Upload Images */}
        <Form.Group className="d-flex flex-column align-items-start p-3 border rounded">
          <Form.Label className="fw-bold">
            <IoMdImages size={20} /> Upload Images
          </Form.Label>

          <div className="d-flex justify-content-center w-50 mb-2">
            <label
              htmlFor="fileUpload"
              className="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center"
            >
              <LuUpload size={18} className="me-2" /> Select Images
            </label>
          </div>

          <Form.Control
            id="fileUpload"
            type="file"
            multiple
            accept="image/*"
            onChange={handleImageUpload}
            disabled={formData.images.length >= 5}
            className="d-none"
          />

          {/* Preview Thumbnails */}
          <div className="d-flex flex-wrap gap-2 mt-2">
            {formData.images.length > 0 ? (
              formData.images.map((imgObj, index) => (
                <div
                  key={index}
                  className="border rounded overflow-hidden position-relative"
                  style={{ width: "60px", height: "60px" }}
                >
                  <img
                    src={imgObj.preview}
                    alt={`preview-${index}`}
                    className="w-100 h-100"
                    style={{ objectFit: "cover" }}
                  />
                  {/* Delete Button */}
                  <button
                    type="button"
                    onClick={() => handleRemoveImage(index)}
                    className="btn-close btn-close-white position-absolute top-0 end-0 bg-dark p-1"
                    style={{ fontSize: "0.6rem" }}
                  />
                </div>
              ))
            ) : (
              <small className="text-muted">No files chosen</small>
            )}
          </div>

          <small className="text-muted mt-2 d-block">
            Images added: {formData.images.length} / 5
          </small>
        </Form.Group>

        <Form.Group className="mt-3">
          <Form.Label className="fw-bold ms-3"><BiSolidCommentDetail /> Comment</Form.Label>
          <Form.Control
            as="textarea"
            rows={3}
            name="comment"
            value={formData.comment}
            onChange={handleChange}
            placeholder="Share your thoughts about the product..."
          />
        </Form.Group>

        <Button variant="success" type="submit" className="w-100 mt-3">
          Submit Review
        </Button>
      </Form>
    </Card>
  );
};

// =========================================================
// B. ReviewCard Component {Customer text comment box}
// =========================================================

export const ReviewCard = ({ review, onImageClick }) => {
  const [likes, setLikes] = useState(review.likes || 0);
  const [dislikes, setDislikes] = useState(review.dislikes || 0);
  const [showMoreImages, setShowMoreImages] = useState(false);

  const totalImages = review.images?.length || 0;
  const previewImages = review.images?.slice(0, 3) || [];
  const remainingImages = review.images?.slice(3) || [];

  return (
    <Card className="border-0 rounded-0 pb-2 mb-3">
      <Card.Body className="p-0">
        {/* === Rating Section === */}
        <div className="d-flex align-items-center p-2">
          <span
            className="fw-bold text-light bg-success rounded-1 d-inline-flex align-items-center justify-content-center"
            style={{
              lineHeight: "1",
              padding: "2px 6px",
              fontSize: "15px",
            }}
          >
            {review.overallRating}
          </span>
          <span className="fw-semibold ms-2">Excellent</span>
        </div>

        <Card.Text className="lead my-2 fs-6 text-dark fw-normal px-2">
          {review.comment}
        </Card.Text>

        <Card.Subtitle className="text-muted small px-2 mb-2">
          By: <b>{review.user || "Anonymous"}</b> | Verified Buyer
        </Card.Subtitle>

        {/* === Image Preview Section === */}
        {totalImages > 0 && (
          <div className="d-flex flex-wrap gap-2 mb-2 px-2 review-image-preview">
            {showMoreImages ? (
              // Expanded Mode: Render ALL images inline with a collapse button
              <>
                {review.images.map((img, index) => (
                  <div
                    key={index}
                    className="border rounded overflow-hidden"
                    style={{
                      width: "70px",
                      height: "70px",
                      cursor: "pointer",
                      flexShrink: 0,
                    }}
                    onClick={() => onImageClick && onImageClick(img)}
                  >
                    <img
                      src={img}
                      alt={`Review ${index}`}
                      className="w-100 h-100"
                      style={{
                        objectFit: "cover",
                      }}
                    />
                  </div>
                ))}
                {/* Collapse / Show Less tile */}
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
                  onClick={() => setShowMoreImages(false)}
                >
                  Show Less
                </div>
              </>
            ) : (
              // Collapsed Mode: Render only up to 3 thumbnails with a "+N" indicator on the third
              previewImages.map((img, index) => {
                const remaining = totalImages - 3;

                // 3rd visible thumbnail with remaining overlay
                if (index === 2 && remaining > 0) {
                  return (
                    <div
                      key={index}
                      className="position-relative border rounded overflow-hidden"
                      style={{
                        width: "70px",
                        height: "70px",
                        cursor: "pointer",
                        flexShrink: 0,
                      }}
                      onClick={() => setShowMoreImages(true)}
                    >
                      <img
                        src={img}
                        alt={`Review ${index}`}
                        className="w-100 h-100"
                        style={{
                          objectFit: "cover",
                          filter: "brightness(50%)",
                        }}
                      />
                      <span
                        className="position-absolute top-50 start-50 translate-middle text-white fw-semibold"
                        style={{
                          fontSize: "0.9rem",
                          whiteSpace: "nowrap",
                        }}
                      >
                        +{remaining}
                      </span>
                    </div>
                  );
                }

                // Normal image preview thumbnail
                return (
                  <div
                    key={index}
                    className="border rounded overflow-hidden"
                    style={{
                      width: "70px",
                      height: "70px",
                      cursor: "pointer",
                      flexShrink: 0,
                    }}
                    onClick={() => onImageClick && onImageClick(img)}
                  >
                    <img
                      src={img}
                      alt={`Review ${index}`}
                      className="w-100 h-100"
                      style={{
                        objectFit: "cover",
                      }}
                    />
                  </div>
                );
              })
            )}
          </div>
        )}

        {/* === Like / Dislike Section === */}
        <div className="d-flex flex-wrap align-items-center gap-2 px-2 mt-2">
          <span className="text-muted small text-nowrap">Was this review helpful?</span>
          <div className="d-flex align-items-center gap-1">
            <button
              onClick={() => setLikes((l) => l + 1)}
              className="btn btn-sm d-flex align-items-center gap-1 bg-transparent text-dark border-0 p-1"
            >
              <FaThumbsUp size="14" color="gray" />
              <span className="small">{likes}</span>
            </button>
            <button
              onClick={() => setDislikes((d) => d + 1)}
              className="btn btn-sm d-flex align-items-center gap-1 bg-transparent text-dark border-0 p-1"
            >
              <FaThumbsDown size="14" color="gray" />
              <span className="small">{dislikes}</span>
            </button>
          </div>
        </div>
      </Card.Body>
      <hr className="my-1" />
    </Card>

  );
};

// =========================================================
// C. RatingSummary Component
// =========================================================

export const RatingSummary = ({ reviews }) => {
  const totalReviews = reviews.length;

  const averageRating = totalReviews
    ? (reviews.reduce((acc, r) => acc + r.overallRating, 0) / totalReviews).toFixed(1)
    : 0;

  const starCounts = {};
  reviews.forEach((r) => {
    const rounded = Math.round(r.overallRating);
    starCounts[rounded] = (starCounts[rounded] || 0) + 1;
  });

  const specKeys = totalReviews ? Object.keys(reviews[0].specsRating) : [];

  const specAverages = {};
  specKeys.forEach((key) => {
    specAverages[key] =
      reviews.reduce((a, r) => a + (r.specsRating[key] || 0), 0) / totalReviews;
  });

  return (
    <Card className="shadow-sm p-3">


      <div className="row">
        <div className="col-12 col-md-6 col-xl-2 text-center border-end">
          <h1 className="display-3 fw-medium text-dark d-flex align-items-center justify-content-center">
            {averageRating}
            <MdOutlineStar size={53} className="text-center mt-1" />
          </h1>
          <p className="text-muted mt-2">Based on {totalReviews} Reviews</p>
        </div>

        <div className="col-12 col-md-6 px-3 col-xl-4">
          <h6>Rating Breakdown</h6>
          {[5, 4, 3, 2, 1].map((star) => {
            const count = starCounts[star] || 0;
            const percent = totalReviews ? (count / totalReviews) * 100 : 0;
            return (
              <div key={star} className="d-flex align-items-center my-0">
                <span className="me-2" style={{ width: "30px" }}>{star}★</span>
                <ProgressBar
                  now={percent}
                  label={`${count}`}
                  style={{ height: "10px", flexGrow: 1 }}
                  variant={star >= 4 ? "success" : star >= 3 ? "warning" : "danger"}
                />
                <span className="ms-2 small text-muted" style={{ width: "40px", textAlign: "right" }}>
                  {percent.toFixed(0)}%
                </span>
              </div>
            );
          })}
        </div>

        <div className="col-12 col-md-12 col-xl-6 d-flex flex-wrap justify-content-around">
          {specKeys.map((key, idx) => {
            const numericValue = Number(specAverages[key]) || 0;
            return (
              <div key={idx} className="text-center mt-4" style={{ width: "22%" }}>
                <CircularProgressbar className='fw-bold'
                  value={numericValue * 20}
                  text={numericValue > 0 ? numericValue.toFixed(1) : "N/A"}
                  styles={buildStyles({
                    textColor: "#000",
                    pathColor: "green",
                    trailColor: "#eee",
                    textSize: "18px",

                  })}
                />
                <p className="mt-2 fw-bold">{key.charAt(0).toUpperCase() + key.slice(1)}</p>
              </div>
            );
          })}
        </div>
      </div>
    </Card>
  );
};
