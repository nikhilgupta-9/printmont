import React, { useState } from 'react';
import { IoClose, IoCameraOutline } from "react-icons/io5";
import { FaStar, FaCamera } from "react-icons/fa";
import { Spinner } from 'react-bootstrap';
import toast from 'react-hot-toast';
import { API_ENDPOINTS, resolveImageUrl } from '../../config/apiEndpoints';
import { useAuth } from '../../context/AuthContext';

const RATING_LABELS = {
  1: 'Very Bad',
  2: 'Bad',
  3: 'Good',
  4: 'Very Good',
  5: 'Excellent'
};

const OCCASIONS = ['General / Personal', 'Corporate Event', 'Birthday Gift', 'Wedding / Anniversary', 'Festival / Celebration', 'Business Marketing'];

const OrderReviewModal = ({ item, order, onClose, onSuccess }) => {
  const { user, token } = useAuth();
  const [rating, setRating] = useState(5);
  const [hoverRating, setHoverRating] = useState(0);
  const [title, setTitle] = useState('');
  const [comment, setComment] = useState('');
  const [occasion, setOccasion] = useState(OCCASIONS[0]);
  const [photos, setPhotos] = useState([]);
  const [submitting, setSubmitting] = useState(false);

  if (!item) return null;

  const productName = item.product_name || 'Product';
  const productImage = item.product_image 
    ? resolveImageUrl(item.product_image) 
    : (item.image ? resolveImageUrl(item.image) : '/default-img.jpg');

  const customerName = user
    ? (user.firstName ? `${user.firstName} ${user.lastName || ''}`.trim() : (user.name || 'Customer'))
    : (order?.customer_name || 'Customer');
  const customerEmail = user?.email || order?.customer_email || '';

  const handlePhotoUpload = (e) => {
    const files = Array.from(e.target.files || []);
    if (photos.length + files.length > 3) {
      toast.error('You can upload a maximum of 3 photos.');
      return;
    }

    files.forEach((file) => {
      if (file.size > 5 * 1024 * 1024) {
        toast.error(`${file.name} is larger than 5MB.`);
        return;
      }
      const reader = new FileReader();
      reader.onloadend = () => {
        setPhotos((prev) => [...prev, reader.result]);
      };
      reader.readAsDataURL(file);
    });
  };

  const handleRemovePhoto = (index) => {
    setPhotos((prev) => prev.filter((_, idx) => idx !== index));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!comment.trim()) {
      toast.error('Please write a few words about your experience.');
      return;
    }

    try {
      setSubmitting(true);
      const payload = {
        product_id: item.product_id || order?.product_id || 0,
        customer_name: customerName,
        customer_email: customerEmail,
        rating: Number(rating),
        title: title.trim() || `${RATING_LABELS[rating] || 'Good'} Product`,
        comment: comment.trim(),
        occasion: occasion,
        order_number: order?.order_number || '',
        images: photos
      };

      const res = await fetch(API_ENDPOINTS.SUBMIT_REVIEW, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(token ? { 'Authorization': `Bearer ${token}` } : {})
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (data.success || data.status === 'success') {
        toast.success(data.message || 'Thank you! Your verified review has been submitted.');
        if (onSuccess) onSuccess();
        onClose();
      } else {
        toast.error(data.error || data.message || 'Failed to submit review.');
      }
    } catch (err) {
      console.error('Review submit error:', err);
      toast.error('An error occurred while submitting your review.');
    } finally {
      setSubmitting(false);
    }
  };

  const activeRating = hoverRating || rating;

  return (
    <div className="order-modal-backdrop" onClick={onClose}>
      <div className="order-review-sheet bg-white" onClick={(e) => e.stopPropagation()}>
        {/* Header */}
        <div className="d-flex align-items-center justify-content-between p-3 border-bottom sticky-top bg-white">
          <h6 className="mb-0 fw-bold text-dark">Rate & Review Product</h6>
          <button 
            type="button" 
            className="btn-close-custom" 
            onClick={onClose}
            aria-label="Close"
          >
            <IoClose size={22} />
          </button>
        </div>

        {/* Product Preview Bar */}
        <div className="p-3 bg-light border-bottom d-flex align-items-center gap-3">
          <img 
            src={productImage} 
            alt={productName} 
            className="rounded border object-fit-contain bg-white" 
            style={{ width: 52, height: 52 }}
            onError={(e) => { e.target.src = '/default-img.jpg'; }}
          />
          <div className="min-width-0">
            <h6 className="mb-0 text-truncate fw-semibold text-dark fs-6" style={{ maxWidth: 300 }}>
              {productName}
            </h6>
            <small className="text-muted">Order ID: {order?.order_number || `#${order?.id}`}</small>
          </div>
        </div>

        {/* Form Body */}
        <form onSubmit={handleSubmit} className="p-3 p-md-4">
          {/* Star Selection */}
          <div className="text-center mb-4">
            <label className="form-label d-block text-muted small fw-semibold mb-2">Rate this product</label>
            <div className="d-inline-flex align-items-center gap-2">
              {[1, 2, 3, 4, 5].map((star) => (
                <button
                  type="button"
                  key={star}
                  className="btn p-1 border-0 bg-transparent"
                  onMouseEnter={() => setHoverRating(star)}
                  onMouseLeave={() => setHoverRating(0)}
                  onClick={() => setRating(star)}
                >
                  <FaStar
                    size={32}
                    className={star <= activeRating ? 'text-warning' : 'text-secondary-subtle'}
                    style={{ transition: 'color 0.15s ease, transform 0.15s ease', transform: star <= activeRating ? 'scale(1.1)' : 'scale(1)' }}
                  />
                </button>
              ))}
            </div>
            <div className="mt-2 fw-bold text-primary" style={{ fontSize: '0.9rem' }}>
              {RATING_LABELS[activeRating] || 'Good'}
            </div>
          </div>

          {/* Title input */}
          <div className="mb-3">
            <label className="form-label text-dark fw-semibold small">Headline (Optional)</label>
            <input 
              type="text" 
              className="form-control"
              placeholder="e.g., Best custom print quality & vibrant colors!"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              maxLength={100}
            />
          </div>

          {/* Occasion input */}
          <div className="mb-3">
            <label className="form-label text-dark fw-semibold small">Product Purpose / Occasion</label>
            <select 
              className="form-select form-select-sm"
              value={occasion}
              onChange={(e) => setOccasion(e.target.value)}
            >
              {OCCASIONS.map((occ) => (
                <option key={occ} value={occ}>{occ}</option>
              ))}
            </select>
          </div>

          {/* Comment input */}
          <div className="mb-3">
            <label className="form-label text-dark fw-semibold small">Your Review <span className="text-danger">*</span></label>
            <textarea 
              rows={3}
              className="form-control"
              placeholder="What did you like or dislike about this product? How was the print quality?"
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              required
            ></textarea>
          </div>

          {/* Photos Upload */}
          <div className="mb-4">
            <label className="form-label text-dark fw-semibold small d-flex align-items-center justify-content-between mb-1">
              <span>Add Photos of the Product (Optional)</span>
              <span className="text-muted fw-normal" style={{ fontSize: '0.75rem' }}>{photos.length}/3 photos</span>
            </label>
            <div className="d-flex flex-wrap gap-2 align-items-center">
              {photos.map((src, index) => (
                <div key={index} className="position-relative" style={{ width: 60, height: 60 }}>
                  <img 
                    src={src} 
                    alt={`Review ${index + 1}`} 
                    className="w-100 h-100 object-fit-cover rounded border"
                  />
                  <button
                    type="button"
                    className="btn btn-danger btn-sm p-0 position-absolute top-0 end-0 rounded-circle d-flex align-items-center justify-content-center shadow-xs"
                    style={{ width: 18, height: 18, transform: 'translate(25%, -25%)' }}
                    onClick={() => handleRemovePhoto(index)}
                  >
                    <IoClose size={12} />
                  </button>
                </div>
              ))}

              {photos.length < 3 && (
                <label 
                  className="rounded border border-dashed d-flex flex-column align-items-center justify-content-center text-muted cursor-pointer hover-bg-light p-2"
                  style={{ width: 60, height: 60, cursor: 'pointer' }}
                >
                  <FaCamera size={16} className="text-primary mb-1" />
                  <span style={{ fontSize: '0.65rem' }}>Add Photo</span>
                  <input
                    type="file"
                    accept="image/*"
                    className="d-none"
                    onChange={handlePhotoUpload}
                    multiple={photos.length < 2}
                  />
                </label>
              )}
            </div>
          </div>

          {/* Action Buttons */}
          <div className="d-flex gap-2 justify-content-end pt-2 border-top">
            <button 
              type="button" 
              className="btn btn-outline-secondary btn-sm rounded-pill px-3"
              onClick={onClose}
              disabled={submitting}
            >
              Cancel
            </button>
            <button 
              type="submit" 
              className="btn btn-primary btn-sm rounded-pill px-4 fw-semibold d-inline-flex align-items-center gap-1"
              disabled={submitting}
            >
              {submitting ? (
                <>
                  <Spinner animation="border" size="sm" />
                  <span>Submitting...</span>
                </>
              ) : (
                'Submit Review'
              )}
            </button>
          </div>
        </form>

      </div>
    </div>
  );
};

export default OrderReviewModal;
