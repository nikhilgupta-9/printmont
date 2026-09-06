import React, { useEffect, useState } from 'react';
import { Card, Form, Button, Spinner } from 'react-bootstrap';
import toast from 'react-hot-toast';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import { RatingSummary, ReviewCard } from '../review/ReviewHelper';
import StarRating from '../review/StarRating';
import { FaCheckCircle } from 'react-icons/fa';

// RatingSummary/ReviewCard were built around a fake review shape
// ({overallRating, user, images, specsRating}) — this maps the real
// product_reviews columns onto exactly the fields those components read,
// nothing more (no fake per-spec ratings or image galleries: the real
// schema has neither wired up).
const toDisplayShape = (r) => ({
  overallRating: r.rating,
  comment: r.comment,
  user: r.customer_name,
  specsRating: {},
});

const emptyForm = { customer_name: '', customer_email: '', rating: 0, title: '', comment: '' };

const ProductReview = ({ productId, hideRatingSummary = false }) => {
  const [reviews, setReviews] = useState([]);
  const [stats, setStats] = useState({ average: 0, count: 0 });
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState(emptyForm);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (!productId) {
      setLoading(false);
      return;
    }
    let cancelled = false;
    setLoading(true);

    fetch(API_ENDPOINTS.PRODUCT_REVIEWS(productId))
      .then((res) => res.json())
      .then((res) => {
        if (cancelled) return;
        if (res?.success && res.data) {
          setReviews(res.data.reviews || []);
          setStats(res.data.stats || { average: 0, count: 0 });
        }
      })
      .catch((err) => {
        if (!cancelled) console.error('Failed to load reviews:', err);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => { cancelled = true; };
  }, [productId]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (form.rating === 0 || !form.customer_name.trim() || !form.comment.trim()) {
      toast.error('Please add your name, a star rating, and a comment.');
      return;
    }

    setSubmitting(true);
    try {
      const res = await fetch(API_ENDPOINTS.SUBMIT_REVIEW, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...form, product_id: productId }),
      });
      const data = await res.json();
      if (data?.success) {
        toast.success(data.message || 'Review submitted for approval!');
        setForm(emptyForm);
        setShowForm(false);
      } else {
        toast.error(data?.error || 'Failed to submit review');
      }
    } catch (err) {
      toast.error('Failed to submit review');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="d-flex justify-content-center py-4">
        <Spinner animation="border" size="sm" variant="secondary" />
      </div>
    );
  }

  const displayReviews = reviews.map(toDisplayShape);

  return (
    <div>
      {!hideRatingSummary && stats.count > 0 && (
        <div className="mb-3">
          <RatingSummary reviews={displayReviews} />
        </div>
      )}

      <div className="d-flex align-items-center justify-content-between mb-3">
        <h5 className="fw-bold text-dark mb-0">
          {stats.count > 0 ? `${stats.count} Review${stats.count !== 1 ? 's' : ''}` : 'Reviews'}
        </h5>
        <Button variant="outline-primary" size="sm" onClick={() => setShowForm((v) => !v)}>
          {showForm ? 'Cancel' : 'Write a Review'}
        </Button>
      </div>

      {showForm && (
        <Card className="mb-4 p-3">
          <Form onSubmit={handleSubmit}>
            <Form.Group className="mb-3">
              <Form.Label className="fw-bold">Your Rating</Form.Label>
              <StarRating
                rating={form.rating}
                onRate={(r) => setForm((f) => ({ ...f, rating: r }))}
                isInteractive
                size={26}
              />
            </Form.Group>
            <div className="row g-2 mb-3">
              <div className="col-sm-6">
                <Form.Control
                  placeholder="Your name"
                  value={form.customer_name}
                  onChange={(e) => setForm((f) => ({ ...f, customer_name: e.target.value }))}
                  required
                />
              </div>
              <div className="col-sm-6">
                <Form.Control
                  type="email"
                  placeholder="Email (optional)"
                  value={form.customer_email}
                  onChange={(e) => setForm((f) => ({ ...f, customer_email: e.target.value }))}
                />
              </div>
            </div>
            <Form.Group className="mb-3">
              <Form.Control
                placeholder="Review title (optional)"
                value={form.title}
                onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))}
              />
            </Form.Group>
            <Form.Group className="mb-3">
              <Form.Control
                as="textarea"
                rows={3}
                placeholder="Share your experience with this product..."
                value={form.comment}
                onChange={(e) => setForm((f) => ({ ...f, comment: e.target.value }))}
                required
              />
            </Form.Group>
            <Button variant="success" type="submit" className="w-100" disabled={submitting}>
              {submitting ? 'Submitting...' : 'Submit Review'}
            </Button>
          </Form>
        </Card>
      )}

      {displayReviews.length === 0 ? (
        <div className="text-center text-secondary py-4 border rounded bg-light">
          <FaCheckCircle className="mb-2" size={22} />
          <p className="mb-0">No reviews yet — be the first to review this product!</p>
        </div>
      ) : (
        displayReviews.map((r, i) => <ReviewCard key={i} review={r} />)
      )}
    </div>
  );
};

export default ProductReview;
