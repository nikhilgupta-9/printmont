import React, { useState, useEffect } from 'react';
import { Form, Button, Card, Row, Col, Alert, Spinner } from 'react-bootstrap';
import { FaIdCard, FaBuilding, FaCheckCircle, FaShieldAlt, FaInfoCircle } from 'react-icons/fa';
import { useAuth } from '../../context/AuthContext';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import toast from 'react-hot-toast';

const PAN_REGEX = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
const GST_REGEX = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;

const PanGstDetails = () => {
  const { user, token, setUser } = useAuth();
  const [saving, setSaving] = useState(false);
  const [isEditing, setIsEditing] = useState(false);

  const [formData, setFormData] = useState({
    pan_number: user?.pan_number || '',
    pan_name: user?.pan_name || '',
    gst_number: user?.gst_number || user?.gstin || '',
    company_name: user?.company_name || user?.business_name || '',
    address: user?.address || '',
    declaration: false,
  });

  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (user) {
      setFormData(prev => ({
        ...prev,
        pan_number: user.pan_number || '',
        pan_name: user.pan_name || (user.firstName ? `${user.firstName} ${user.lastName || ''}`.trim() : ''),
        gst_number: user.gst_number || user.gstin || '',
        company_name: user.company_name || user.business_name || '',
        address: user.address || '',
      }));
    }
  }, [user]);

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    const finalVal = type === 'checkbox' ? checked : (name === 'pan_number' || name === 'gst_number' ? value.toUpperCase() : value);
    
    setFormData(prev => ({ ...prev, [name]: finalVal }));
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const validate = () => {
    const errs = {};
    if (formData.pan_number && !PAN_REGEX.test(formData.pan_number)) {
      errs.pan_number = 'Invalid PAN format. Example: ABCDE1234F';
    }
    if (formData.gst_number && !GST_REGEX.test(formData.gst_number)) {
      errs.gst_number = 'Invalid GSTIN format. Example: 07AAAAA0000A1Z5';
    }
    if (formData.gst_number && !formData.company_name) {
      errs.company_name = 'Company / Business Name is required for GST invoicing';
    }
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const handleSave = async (e) => {
    e.preventDefault();
    if (!validate()) return;

    setSaving(true);
    try {
      const activeToken = token || localStorage.getItem('token') || '';
      const headers = { 'Content-Type': 'application/json' };
      if (activeToken) headers['Authorization'] = `Bearer ${activeToken}`;

      const payload = {
        firstName: user?.firstName || user?.first_name || 'User',
        lastName: user?.lastName || user?.last_name || '',
        mobile: user?.mobile || user?.phone || '',
        gender: user?.gender || 'Male',
        pan_number: formData.pan_number.trim(),
        pan_name: formData.pan_name.trim(),
        gst_number: formData.gst_number.trim(),
        company_name: formData.company_name.trim(),
        address: formData.address.trim(),
      };

      const res = await fetch(API_ENDPOINTS.UPDATE_PROFILE, {
        method: 'POST',
        headers,
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (data.success) {
        toast.success('PAN & GST details updated successfully!');
        setIsEditing(false);
        const updatedUser = { ...user, ...data.user, ...payload };
        if (setUser) setUser(updatedUser);
        localStorage.setItem('user', JSON.stringify(updatedUser));
      } else {
        toast.error(data.error || data.message || 'Failed to update PAN/GST details.');
      }
    } catch (err) {
      console.error('PAN/GST update error:', err);
      toast.error('Network error. Failed to save details.');
    } finally {
      setSaving(false);
    }
  };

  const hasPan = Boolean(user?.pan_number);
  const hasGst = Boolean(user?.gst_number);

  return (
    <div className="card p-4 p-md-5 border-0 shadow-sm rounded-4 bg-white">
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
        <div>
          <h4 className="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
            <FaIdCard className="text-primary" /> PAN & GST Information
          </h4>
          <p className="text-muted small mb-0">
            Add your PAN and GSTIN details for business invoicing, claiming GST input tax credit, and tax compliance.
          </p>
        </div>
        {!isEditing ? (
          <Button 
            variant="outline-primary" 
            size="sm" 
            className="rounded-pill px-4 fw-semibold"
            onClick={() => setIsEditing(true)}
          >
            {hasPan || hasGst ? 'Edit Details' : '+ Add PAN / GST'}
          </Button>
        ) : (
          <Button 
            variant="outline-secondary" 
            size="sm" 
            className="rounded-pill px-4 fw-semibold"
            onClick={() => setIsEditing(false)}
          >
            Cancel
          </Button>
        )}
      </div>

      {(hasPan || hasGst) && !isEditing && (
        <Alert variant="success" className="rounded-3 border-0 py-3 mb-4 d-flex align-items-center gap-2">
          <FaCheckCircle className="text-success flex-shrink-0" size={18} />
          <div className="small">
            Your Business Invoicing details are registered. GST tax invoices will be generated automatically on your business orders.
          </div>
        </Alert>
      )}

      <Form onSubmit={handleSave}>
        {/* PAN Card Section */}
        <div className="mb-5">
          <h6 className="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
            <FaIdCard className="text-secondary" /> 1. PAN Card Details (Personal / Business)
          </h6>
          <Row className="g-3">
            <Col md={6}>
              <Form.Group>
                <Form.Label className="small fw-semibold text-secondary">PAN Number</Form.Label>
                <Form.Control
                  type="text"
                  name="pan_number"
                  placeholder="e.g. ABCDE1234F"
                  maxLength={10}
                  value={formData.pan_number}
                  onChange={handleChange}
                  readOnly={!isEditing}
                  isInvalid={!!errors.pan_number}
                  className="p-3 rounded-3 text-uppercase fw-semibold"
                />
                <Form.Control.Feedback type="invalid">
                  {errors.pan_number}
                </Form.Control.Feedback>
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group>
                <Form.Label className="small fw-semibold text-secondary">Full Name (As on PAN Card)</Form.Label>
                <Form.Control
                  type="text"
                  name="pan_name"
                  placeholder="Full name as printed on PAN card"
                  value={formData.pan_name}
                  onChange={handleChange}
                  readOnly={!isEditing}
                  className="p-3 rounded-3"
                />
              </Form.Group>
            </Col>
          </Row>
        </div>

        {/* GSTIN Section */}
        <div className="mb-5">
          <h6 className="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
            <FaBuilding className="text-secondary" /> 2. GSTIN Information (For Business Invoicing)
          </h6>
          <Row className="g-3 mb-3">
            <Col md={6}>
              <Form.Group>
                <Form.Label className="small fw-semibold text-secondary">GSTIN Number (15 Digits)</Form.Label>
                <Form.Control
                  type="text"
                  name="gst_number"
                  placeholder="e.g. 07AAAAA0000A1Z5"
                  maxLength={15}
                  value={formData.gst_number}
                  onChange={handleChange}
                  readOnly={!isEditing}
                  isInvalid={!!errors.gst_number}
                  className="p-3 rounded-3 text-uppercase fw-semibold"
                />
                <Form.Control.Feedback type="invalid">
                  {errors.gst_number}
                </Form.Control.Feedback>
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group>
                <Form.Label className="small fw-semibold text-secondary">Registered Business / Entity Name</Form.Label>
                <Form.Control
                  type="text"
                  name="company_name"
                  placeholder="e.g. Acme Tech Solutions Pvt Ltd"
                  value={formData.company_name}
                  onChange={handleChange}
                  readOnly={!isEditing}
                  isInvalid={!!errors.company_name}
                  className="p-3 rounded-3"
                />
                <Form.Control.Feedback type="invalid">
                  {errors.company_name}
                </Form.Control.Feedback>
              </Form.Group>
            </Col>
          </Row>

          <Row className="g-3">
            <Col md={12}>
              <Form.Group>
                <Form.Label className="small fw-semibold text-secondary">Registered Business Address</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={2}
                  name="address"
                  placeholder="Registered business office address for GST invoice"
                  value={formData.address}
                  onChange={handleChange}
                  readOnly={!isEditing}
                  className="p-3 rounded-3"
                />
              </Form.Group>
            </Col>
          </Row>
        </div>

        {/* Declaration & Action */}
        {isEditing && (
          <div className="pt-3 border-top">
            <Form.Group className="mb-4">
              <Form.Check
                type="checkbox"
                id="declaration"
                name="declaration"
                checked={formData.declaration}
                onChange={handleChange}
                label={
                  <span className="small text-muted">
                    I declare that the PAN and GSTIN information entered above is true, correct, and legally belongs to me or my authorized enterprise.
                  </span>
                }
              />
            </Form.Group>

            <div className="d-flex gap-3">
              <Button
                type="submit"
                variant="primary"
                disabled={saving || (formData.pan_number && !formData.declaration)}
                className="px-5 py-2 fw-bold rounded-pill border-0 shadow-sm"
                style={{ backgroundColor: '#0b53a1' }}
              >
                {saving ? (
                  <>
                    <Spinner size="sm" animation="border" className="me-2" /> Saving...
                  </>
                ) : (
                  'Save & Update Details'
                )}
              </Button>
              <Button
                type="button"
                variant="light"
                onClick={() => setIsEditing(false)}
                className="px-4 py-2 fw-semibold rounded-pill border text-secondary"
              >
                Cancel
              </Button>
            </div>
          </div>
        )}
      </Form>

      {/* Flipkart Style FAQs */}
      <div className="mt-5 pt-4 border-top">
        <h6 className="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
          <FaInfoCircle className="text-primary" /> Frequently Asked Questions
        </h6>
        <div className="d-flex flex-column gap-3 text-secondary small">
          <div>
            <strong className="text-dark d-block mb-1">Why do I need to provide PAN details?</strong>
            As per Indian tax laws, PAN is required for high-value transactions and for issuing compliant tax invoices.
          </div>
          <div>
            <strong className="text-dark d-block mb-1">How can I claim Input Tax Credit (ITC) using GSTIN?</strong>
            When you enter your valid 15-digit GSTIN, all subsequent business orders will include your GSTIN on the tax invoice, enabling you to claim full Input Tax Credit under GST.
          </div>
          <div>
            <strong className="text-dark d-block mb-1">Is my information safe?</strong>
            Yes, your PAN and GST details are stored with military-grade encryption and are only used for government invoicing compliance.
          </div>
        </div>
      </div>
    </div>
  );
};

export default PanGstDetails;
