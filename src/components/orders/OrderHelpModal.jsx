import React, { useState } from 'react';
import { IoClose, IoHeadsetOutline, IoCheckmarkCircleOutline } from "react-icons/io5";
import { FaTruck, FaExclamationTriangle, FaTimesCircle, FaFileInvoice, FaRedo, FaComments } from "react-icons/fa";
import { Spinner } from 'react-bootstrap';
import toast from 'react-hot-toast';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';

const HELP_TOPICS = [
  { id: 'tracking', label: 'Where is my order / Delay inquiry', icon: <FaTruck className="text-primary" /> },
  { id: 'damaged', label: 'Damaged, blurry, or incorrect print', icon: <FaExclamationTriangle className="text-warning" /> },
  { id: 'cancel', label: 'I want to cancel this order / Refund', icon: <FaTimesCircle className="text-danger" /> },
  { id: 'invoice', label: 'GST Invoice / Invoicing query', icon: <FaFileInvoice className="text-info" /> },
  { id: 'return', label: 'Return or replacement request', icon: <FaRedo className="text-success" /> },
  { id: 'other', label: 'Other order questions', icon: <FaComments className="text-secondary" /> },
];

const OrderHelpModal = ({ order, onClose }) => {
  const { user } = useAuth();
  const [selectedTopic, setSelectedTopic] = useState('tracking');
  const [message, setMessage] = useState('');
  const [contactMethod, setContactMethod] = useState('email');
  const [submitting, setSubmitting] = useState(false);
  const [ticketId, setTicketId] = useState(null);

  if (!order) return null;

  const orderNumber = order.order_number || `#${order.id}`;

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!message.trim()) {
      toast.error('Please provide details about your query.');
      return;
    }

    setSubmitting(true);
    setTimeout(() => {
      const generatedTicket = `TKT-${Math.floor(100000 + Math.random() * 900000)}`;
      setTicketId(generatedTicket);
      setSubmitting(false);
      toast.success('Support ticket created successfully!');
    }, 600);
  };

  return (
    <div className="order-modal-backdrop" onClick={onClose}>
      <div className="order-help-modal-sheet bg-white shadow-lg" onClick={(e) => e.stopPropagation()}>
        
        {/* Header */}
        <div className="d-flex align-items-center justify-content-between p-3 border-bottom sticky-top bg-white">
          <div className="d-flex align-items-center gap-2">
            <div className="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style={{ width: 34, height: 34 }}>
              <IoHeadsetOutline size={20} />
            </div>
            <div>
              <h6 className="mb-0 fw-bold text-dark">Need Help with Order</h6>
              <small className="text-muted">Order ID: {orderNumber}</small>
            </div>
          </div>
          <button 
            type="button" 
            className="btn-close-custom" 
            onClick={onClose}
            aria-label="Close"
          >
            <IoClose size={20} />
          </button>
        </div>

        {/* Body */}
        <div className="p-3 p-md-4">
          {ticketId ? (
            /* Ticket Submitted State */
            <div className="text-center py-4">
              <div className="rounded-circle bg-success-subtle text-success p-3 d-inline-flex mb-3">
                <IoCheckmarkCircleOutline size={48} />
              </div>
              <h5 className="fw-bold text-dark mb-1">Ticket Raised Successfully!</h5>
              <p className="text-muted small mb-3">
                Your support request has been linked to Order <strong>{orderNumber}</strong>.
              </p>
              
              <div className="card bg-light border-0 p-3 mb-4 rounded-3 text-start">
                <div className="d-flex justify-content-between mb-1 small">
                  <span className="text-muted">Ticket Number:</span>
                  <strong className="text-primary">{ticketId}</strong>
                </div>
                <div className="d-flex justify-content-between mb-1 small">
                  <span className="text-muted">Expected Response:</span>
                  <span className="text-dark fw-semibold">Within 2 to 4 hours</span>
                </div>
                <div className="d-flex justify-content-between small">
                  <span className="text-muted">Support Channel:</span>
                  <span className="text-dark fw-semibold text-capitalize">{contactMethod}</span>
                </div>
              </div>

              <div className="d-flex gap-2 justify-content-center">
                <Link to="/help-center" className="btn btn-outline-primary btn-sm rounded-pill px-4" onClick={onClose}>
                  Visit Help Center
                </Link>
                <button type="button" className="btn btn-primary btn-sm rounded-pill px-4" onClick={onClose}>
                  Done
                </button>
              </div>
            </div>
          ) : (
            /* Help Topic Selection & Message Form */
            <form onSubmit={handleSubmit}>
              <div className="mb-3">
                <label className="form-label fw-bold text-dark small mb-2">What issue are you facing?</label>
                <div className="d-flex flex-column gap-2">
                  {HELP_TOPICS.map((topic) => {
                    const isSelected = selectedTopic === topic.id;
                    return (
                      <div
                        key={topic.id}
                        className={`p-2 px-3 border rounded-3 cursor-pointer d-flex align-items-center gap-3 transition-all ${
                          isSelected ? 'border-primary bg-primary-subtle' : 'bg-white hover-bg-light'
                        }`}
                        onClick={() => setSelectedTopic(topic.id)}
                      >
                        <div className="fs-5 d-flex align-items-center">{topic.icon}</div>
                        <span className={`small ${isSelected ? 'fw-bold text-primary' : 'text-dark'}`}>{topic.label}</span>
                        <input
                          type="radio"
                          name="help_topic"
                          className="form-check-input ms-auto my-0"
                          checked={isSelected}
                          onChange={() => setSelectedTopic(topic.id)}
                        />
                      </div>
                    );
                  })}
                </div>
              </div>

              <div className="mb-3">
                <label className="form-label fw-bold text-dark small">Describe your issue in detail</label>
                <textarea
                  className="form-control"
                  rows={3}
                  placeholder="Provide details about what you need assistance with..."
                  value={message}
                  onChange={(e) => setMessage(e.target.value)}
                  required
                ></textarea>
              </div>

              <div className="mb-4">
                <label className="form-label fw-bold text-dark small mb-1">Preferred Response Mode</label>
                <div className="d-flex gap-3">
                  <label className="form-check-label d-flex align-items-center gap-1 small cursor-pointer">
                    <input
                      type="radio"
                      name="contact_pref"
                      className="form-check-input my-0"
                      checked={contactMethod === 'email'}
                      onChange={() => setContactMethod('email')}
                    />
                    <span>Email ({user?.email || 'Registered Email'})</span>
                  </label>
                  <label className="form-check-label d-flex align-items-center gap-1 small cursor-pointer">
                    <input
                      type="radio"
                      name="contact_pref"
                      className="form-check-input my-0"
                      checked={contactMethod === 'whatsapp'}
                      onChange={() => setContactMethod('whatsapp')}
                    />
                    <span>WhatsApp / SMS</span>
                  </label>
                </div>
              </div>

              <div className="d-flex gap-2 justify-content-between pt-2 border-top align-items-center">
                <Link to="/help-center" className="text-decoration-none text-muted small" onClick={onClose}>
                  Visit 24x7 Help Center →
                </Link>
                <div className="d-flex gap-2">
                  <button type="button" className="btn btn-outline-secondary btn-sm rounded-pill px-3" onClick={onClose}>
                    Cancel
                  </button>
                  <button type="submit" className="btn btn-primary btn-sm rounded-pill px-4 fw-semibold" disabled={submitting}>
                    {submitting ? <Spinner animation="border" size="sm" /> : 'Raise Ticket'}
                  </button>
                </div>
              </div>
            </form>
          )}
        </div>

      </div>
    </div>
  );
};

export default OrderHelpModal;
