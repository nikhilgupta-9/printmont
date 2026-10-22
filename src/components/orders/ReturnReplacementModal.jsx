import React, { useState } from 'react';
import { IoClose, IoAlertCircleOutline, IoCheckmarkCircle, IoCloudUploadOutline, IoTrashOutline } from "react-icons/io5";
import { FaSyncAlt, FaUndo, FaWallet, FaCreditCard, FaCamera } from "react-icons/fa";
import { Spinner } from 'react-bootstrap';
import toast from 'react-hot-toast';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import { useAuth } from '../../context/AuthContext';

const RETURN_REASONS = [
  'Damaged during delivery / broken package',
  'Defective print / misaligned design / wrong colors',
  'Wrong product or size received',
  'Missing parts or accessories',
  'Print quality not meeting expectations',
  'Item arrived defective or unusable',
  'Other quality issue'
];

const ReturnReplacementModal = ({ order, onClose, onSuccess }) => {
  const { token } = useAuth();
  const [requestType, setRequestType] = useState('replacement'); // 'replacement' or 'return'
  const [reason, setReason] = useState(RETURN_REASONS[0]);
  const [customReason, setCustomReason] = useState('');
  const [refundMethod, setRefundMethod] = useState('wallet');
  const [photos, setPhotos] = useState([]); // array of base64 strings
  const [submitting, setSubmitting] = useState(false);

  if (!order) return null;

  const orderNumber = order.order_number || `#${order.id}`;
  const price = Number(order.grand_total ?? order.total_amount ?? 0);

  const handlePhotoUpload = (e) => {
    const files = Array.from(e.target.files || []);
    if (photos.length + files.length > 3) {
      toast.error('You can upload a maximum of 3 proof photos.');
      return;
    }

    files.forEach((file) => {
      if (file.size > 5 * 1024 * 1024) {
        toast.error(`${file.name} is larger than 5MB. Please upload smaller images.`);
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

    const selectedReason = reason === 'Other quality issue' && customReason.trim()
      ? customReason.trim()
      : reason;

    if (!selectedReason) {
      toast.error('Please select a reason for your request.');
      return;
    }

    try {
      setSubmitting(true);

      const payload = {
        order_id: order.id,
        order_number: order.order_number || '',
        request_type: requestType,
        reason: selectedReason,
        refund_method: requestType === 'return' ? refundMethod : 'not_applicable',
        comments: customReason.trim(),
        images: photos
      };

      const res = await fetch(API_ENDPOINTS.REQUEST_RETURN_REPLACEMENT, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(token ? { 'Authorization': `Bearer ${token}` } : {})
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (data.success || data.status === 'success') {
        toast.success(data.message || 'Your request has been submitted successfully.');
        if (onSuccess) {
          onSuccess({ ...order, status: data.status || (requestType === 'replacement' ? 'replacement_requested' : 'return_requested') });
        }
        onClose();
      } else {
        toast.error(data.error || data.message || 'Failed to submit request.');
      }
    } catch (err) {
      console.error('Return/Replacement submit error:', err);
      toast.error('An error occurred while submitting your request.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="order-modal-backdrop" onClick={onClose}>
      <div className="order-detail-sheet bg-white" style={{ maxWidth: 580 }} onClick={(e) => e.stopPropagation()}>
        
        {/* Modal Header */}
        <div className="d-flex align-items-center justify-content-between p-3 border-bottom sticky-top bg-white">
          <div className="d-flex align-items-center gap-2">
            <FaSyncAlt className="text-primary" size={16} />
            <h6 className="mb-0 fw-bold text-dark">Return / Replacement Request</h6>
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
          
          {/* Policy Banner */}
          <div className="alert alert-info d-flex align-items-start gap-2 p-3 rounded-3 mb-3 small">
            <IoAlertCircleOutline size={22} className="flex-shrink-0 text-primary mt-0" />
            <div>
              <strong className="d-block text-dark mb-1">Printmont 15-Day Quality Guarantee</strong>
              <span>Items can be exchanged for a fresh reprint or returned for a 100% refund if defective or damaged.</span>
            </div>
          </div>

          {/* Request Type Selector (Replacement vs Return) */}
          <div className="mb-3">
            <label className="form-label fw-bold text-dark small mb-2">
              Select What You Need <span className="text-danger">*</span>
            </label>
            <div className="row g-2">
              <div className="col-6">
                <div 
                  className={`p-3 rounded-3 border text-center cursor-pointer transition-all ${
                    requestType === 'replacement' ? 'border-primary bg-primary-subtle text-primary fw-bold shadow-xs' : 'bg-white text-muted hover-bg-light'
                  }`}
                  onClick={() => setRequestType('replacement')}
                  role="button"
                  tabIndex={0}
                >
                  <FaSyncAlt size={18} className="mb-1 d-block mx-auto text-primary" />
                  <span className="d-block small">Replacement / Exchange</span>
                  <small className="fw-normal text-muted" style={{ fontSize: '0.72rem' }}>Get fresh reprint of product</small>
                </div>
              </div>
              <div className="col-6">
                <div 
                  className={`p-3 rounded-3 border text-center cursor-pointer transition-all ${
                    requestType === 'return' ? 'border-primary bg-primary-subtle text-primary fw-bold shadow-xs' : 'bg-white text-muted hover-bg-light'
                  }`}
                  onClick={() => setRequestType('return')}
                  role="button"
                  tabIndex={0}
                >
                  <FaUndo size={18} className="mb-1 d-block mx-auto text-primary" />
                  <span className="d-block small">Return & Refund</span>
                  <small className="fw-normal text-muted" style={{ fontSize: '0.72rem' }}>Return item & get money back</small>
                </div>
              </div>
            </div>
          </div>

          {/* Reason Selector */}
          <div className="mb-3">
            <label className="form-label fw-bold text-dark small mb-2">
              Reason for {requestType === 'replacement' ? 'Replacement' : 'Return'} <span className="text-danger">*</span>
            </label>
            <select
              className="form-select form-select-sm"
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              required
            >
              {RETURN_REASONS.map((r) => (
                <option key={r} value={r}>{r}</option>
              ))}
            </select>
          </div>

          {/* Photo Proof Upload */}
          <div className="mb-3">
            <label className="form-label fw-bold text-dark small mb-1 d-flex align-items-center justify-content-between">
              <span>Attach Photos of Defect/Damage (Optional)</span>
              <span className="text-muted fw-normal" style={{ fontSize: '0.75rem' }}>{photos.length}/3 photos</span>
            </label>
            
            <div className="d-flex flex-wrap gap-2 align-items-center mb-1">
              {photos.map((src, index) => (
                <div key={index} className="position-relative" style={{ width: 68, height: 68 }}>
                  <img 
                    src={src} 
                    alt={`Proof ${index + 1}`} 
                    className="w-100 h-100 object-fit-cover rounded border"
                  />
                  <button
                    type="button"
                    className="btn btn-danger btn-sm p-0 position-absolute top-0 end-0 rounded-circle d-flex align-items-center justify-content-center shadow-xs"
                    style={{ width: 20, height: 20, transform: 'translate(30%, -30%)' }}
                    onClick={() => handleRemovePhoto(index)}
                  >
                    <IoClose size={14} />
                  </button>
                </div>
              ))}

              {photos.length < 3 && (
                <label 
                  className="rounded border border-dashed d-flex flex-column align-items-center justify-content-center text-muted cursor-pointer hover-bg-light p-2"
                  style={{ width: 68, height: 68, cursor: 'pointer' }}
                >
                  <FaCamera size={18} className="text-primary mb-1" />
                  <span style={{ fontSize: '0.68rem' }}>Add Photo</span>
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
            <small className="text-muted" style={{ fontSize: '0.74rem' }}>
              Upload clear pictures showing the print flaw or damage for faster resolution.
            </small>
          </div>

          {/* Refund Mode Selection (only if Return is chosen) */}
          {requestType === 'return' && (
            <div className="mb-3 p-3 rounded-3 bg-light border">
              <label className="form-label fw-bold text-dark small mb-2">
                Refund Preference (₹{price.toLocaleString('en-IN')})
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
                      name="return_refund_mode"
                      value="wallet"
                      checked={refundMethod === 'wallet'}
                      onChange={() => setRefundMethod('wallet')}
                      className="form-check-input mt-0"
                    />
                    <FaWallet className="text-success" />
                    <div>
                      <div>Printmont Wallet (Instant Cash Refund)</div>
                      <small className="text-muted fw-normal">Instant reversal to cash wallet • Usable anytime</small>
                    </div>
                  </div>
                  <span className="badge bg-success text-white">Instant</span>
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
                      name="return_refund_mode"
                      value="original"
                      checked={refundMethod === 'original'}
                      onChange={() => setRefundMethod('original')}
                      className="form-check-input mt-0"
                    />
                    <FaCreditCard className="text-secondary" />
                    <div>
                      <div>Original Payment Source</div>
                      <small className="text-muted fw-normal">UPI / Card / Bank account (3-5 business days)</small>
                    </div>
                  </div>
                </label>
              </div>
            </div>
          )}

          {/* Details Comments */}
          <div className="mb-3">
            <label className="form-label fw-semibold text-muted small mb-1">
              Detailed comments / description
            </label>
            <textarea
              rows={2}
              className="form-control form-control-sm"
              placeholder="Describe the issue in detail..."
              value={customReason}
              onChange={(e) => setCustomReason(e.target.value)}
            ></textarea>
          </div>

          {/* Action Buttons */}
          <div className="d-flex gap-2 justify-content-end pt-3 border-top">
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
                `Submit ${requestType === 'replacement' ? 'Replacement' : 'Return'} Request`
              )}
            </button>
          </div>

        </form>

      </div>
    </div>
  );
};

export default ReturnReplacementModal;
