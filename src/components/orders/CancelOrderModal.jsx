import React, { useState } from 'react';
import { IoClose, IoAlertCircleOutline, IoCheckmarkCircle, IoInformationCircleOutline } from "react-icons/io5";
import { FaBan, FaWallet, FaCreditCard } from "react-icons/fa";
import { Spinner } from 'react-bootstrap';
import toast from 'react-hot-toast';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import { useAuth } from '../../context/AuthContext';

const CANCEL_REASONS = [
  'Order created by mistake',
  'Delivery time is too long / won\'t arrive on time',
  'Found a better price / changed my mind',
  'Need to change delivery address or phone number',
  'Incorrect variant or size selected',
  'Applied wrong payment method or coupon',
  'Other reason'
];

const CancelOrderModal = ({ order, onClose, onSuccess }) => {
  const { token } = useAuth();
  const [reason, setReason] = useState(CANCEL_REASONS[0]);
  const [customReason, setCustomReason] = useState('');
  const [refundMethod, setRefundMethod] = useState('wallet');
  const [submitting, setSubmitting] = useState(false);

  if (!order) return null;

  const orderNumber = order.order_number || `#${order.id}`;
  const price = Number(order.grand_total ?? order.total_amount ?? 0);
  const isPrepaid = order.payment_method !== 'cod' && (order.payment_status === 'paid' || price > 0);

  const handleSubmit = async (e) => {
    e.preventDefault();

    const selectedReason = reason === 'Other reason' && customReason.trim()
      ? customReason.trim()
      : reason;

    if (!selectedReason) {
      toast.error('Please select a reason for cancellation.');
      return;
    }

    try {
      setSubmitting(true);

      const payload = {
        order_id: order.id,
        order_number: order.order_number || '',
        reason: selectedReason,
        refund_method: isPrepaid ? refundMethod : 'not_applicable',
        comments: customReason.trim()
      };

      const res = await fetch(API_ENDPOINTS.CANCEL_ORDER, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(token ? { 'Authorization': `Bearer ${token}` } : {})
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (data.success || data.status === 'success') {
        toast.success(data.message || 'Order has been cancelled successfully.');
        if (onSuccess) {
          onSuccess({ ...order, status: 'cancelled' });
        }
        onClose();
      } else {
        toast.error(data.error || data.message || 'Failed to cancel order.');
      }
    } catch (err) {
      console.error('Cancellation error:', err);
      toast.error('An error occurred while cancelling your order.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="order-modal-backdrop" onClick={onClose}>
      <div className="order-detail-sheet bg-white" style={{ maxWidth: 540 }} onClick={(e) => e.stopPropagation()}>
        
        {/* Modal Header */}
        <div className="d-flex align-items-center justify-content-between p-3 border-bottom sticky-top bg-white">
          <div className="d-flex align-items-center gap-2">
            <FaBan className="text-danger" size={18} />
            <h6 className="mb-0 fw-bold text-dark">Cancel Order</h6>
            <span className="text-muted small">| {orderNumber}</span>
          </div>
          <button 
            type="button" 
            className="btn-close-custom" 
            onClick={onClose}
            aria-label="Close"
          >
            <IoClose size={22} />
          </button>
        </div>

        {/* Modal Body */}
        <form onSubmit={handleSubmit} className="p-3 p-md-4">
          
          {/* Policy Notice Banner */}
          <div className="alert alert-warning d-flex align-items-start gap-2 p-3 rounded-3 mb-3 small">
            <IoAlertCircleOutline size={22} className="flex-shrink-0 text-warning mt-0" />
            <div>
              <strong className="d-block text-dark mb-1">Are you sure you want to cancel?</strong>
              <span>Once cancelled, your order cannot be reactivated and items will need to be re-ordered.</span>
            </div>
          </div>

          {/* Reason Selector */}
          <div className="mb-4">
            <label className="form-label fw-bold text-dark small mb-2">
              Please select reason for cancellation <span className="text-danger">*</span>
            </label>
            <div className="d-flex flex-column gap-2">
              {CANCEL_REASONS.map((r) => (
                <label 
                  key={r} 
                  className={`p-2 px-3 rounded-3 border d-flex align-items-center gap-2 cursor-pointer transition-all ${
                    reason === r ? 'border-primary bg-primary-subtle text-primary fw-semibold' : 'bg-white text-dark hover-bg-light'
                  }`}
                  style={{ cursor: 'pointer', fontSize: '0.88rem' }}
                >
                  <input
                    type="radio"
                    name="cancel_reason"
                    value={r}
                    checked={reason === r}
                    onChange={() => setReason(r)}
                    className="form-check-input mt-0"
                  />
                  <span>{r}</span>
                </label>
              ))}
            </div>
          </div>

          {/* Custom comment input if 'Other' selected or extra details */}
          <div className="mb-3">
            <label className="form-label fw-semibold text-muted small mb-1">
              Additional comments / feedback (Optional)
            </label>
            <textarea
              rows={2}
              className="form-control form-control-sm"
              placeholder="Tell us what went wrong..."
              value={customReason}
              onChange={(e) => setCustomReason(e.target.value)}
            ></textarea>
          </div>

          {/* Refund Method Section for Prepaid Orders */}
          {isPrepaid && (
            <div className="mb-4 p-3 rounded-3 bg-light border">
              <label className="form-label fw-bold text-dark small mb-2 d-flex align-items-center gap-1">
                <IoInformationCircleOutline className="text-primary" size={16} /> Refund Method for ₹{price.toLocaleString('en-IN')}
              </label>

              <div className="d-flex flex-column gap-2">
                <label 
                  className={`p-2 px-3 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer ${
                    refundMethod === 'wallet' ? 'border-success bg-success-subtle text-success-emphasis fw-semibold' : 'bg-white text-dark'
                  }`}
                  style={{ cursor: 'pointer', fontSize: '0.86rem' }}
                >
                  <div className="d-flex align-items-center gap-2">
                    <input
                      type="radio"
                      name="refund_method"
                      value="wallet"
                      checked={refundMethod === 'wallet'}
                      onChange={() => setRefundMethod('wallet')}
                      className="form-check-input mt-0"
                    />
                    <FaWallet className="text-success" />
                    <div>
                      <div>Printmont Wallet (Instant Cash Refund)</div>
                      <small className="text-muted fw-normal">Instant reversal to cash wallet • 0 wait time</small>
                    </div>
                  </div>
                  <span className="badge bg-success text-white">Recommended</span>
                </label>

                <label 
                  className={`p-2 px-3 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer ${
                    refundMethod === 'original' ? 'border-primary bg-primary-subtle text-primary fw-semibold' : 'bg-white text-dark'
                  }`}
                  style={{ cursor: 'pointer', fontSize: '0.86rem' }}
                >
                  <div className="d-flex align-items-center gap-2">
                    <input
                      type="radio"
                      name="refund_method"
                      value="original"
                      checked={refundMethod === 'original'}
                      onChange={() => setRefundMethod('original')}
                      className="form-check-input mt-0"
                    />
                    <FaCreditCard className="text-secondary" />
                    <div>
                      <div>Original Payment Source</div>
                      <small className="text-muted fw-normal">UPI / Card / NetBanking (Takes 3-5 business days)</small>
                    </div>
                  </div>
                </label>
              </div>
            </div>
          )}

          {/* Action Buttons */}
          <div className="d-flex gap-2 justify-content-end pt-3 border-top">
            <button
              type="button"
              className="btn btn-outline-secondary btn-sm rounded-pill px-3"
              onClick={onClose}
              disabled={submitting}
            >
              Keep Order
            </button>
            <button
              type="submit"
              className="btn btn-danger btn-sm rounded-pill px-4 fw-semibold d-inline-flex align-items-center gap-1"
              disabled={submitting}
            >
              {submitting ? (
                <>
                  <Spinner animation="border" size="sm" />
                  <span>Cancelling...</span>
                </>
              ) : (
                'Confirm Cancellation'
              )}
            </button>
          </div>

        </form>

      </div>
    </div>
  );
};

export default CancelOrderModal;
