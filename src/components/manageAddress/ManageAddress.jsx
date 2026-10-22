import React, { useState, useEffect, useCallback } from 'react';
import { Row, Col, Form, Button, Card, Spinner, Badge, Modal } from 'react-bootstrap';
import toast from 'react-hot-toast';
import { 
  FaHome, 
  FaBriefcase, 
  FaTrashAlt, 
  FaEdit, 
  FaMapMarkerAlt, 
  FaPlus, 
  FaPhoneAlt, 
  FaMapPin, 
  FaArrowLeft,
  FaExclamationTriangle,
  FaCheckCircle,
  FaCheck
} from 'react-icons/fa';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import { INDIAN_STATES as statesList } from '../../config/indianStates';
import './ManageAddress.css';

const emptyAddress = {
  id: null,
  name: "",
  phone: "",
  pincode: "",
  locality: "",
  address: "",
  city: "",
  state: "Delhi",
  landmark: "",
  altPhone: "",
  type: "Home",
  is_default: 0,
};

// ───────────────────────────────────────────────────────────
// 1. SAVED ADDRESS CARD COMPONENT (Rule 8 Compliant)
// ───────────────────────────────────────────────────────────
const SavedAddressCard = ({ data, onEdit, onDelete, onSetDefault }) => {
  const isDefault = Number(data.is_default) === 1;

  return (
    <div className={`saved-address-card ${isDefault ? 'is-default' : ''}`}>
      <div className="d-flex justify-content-between align-items-start gap-2 mb-2">
        {/* Name and Badges Header */}
        <div className="d-flex flex-wrap align-items-center gap-2">
          <h5 className="fw-bold text-dark mb-0 fs-6">{data.name}</h5>
          
          <span className={`address-type-badge ${data.type === 'Home' ? 'address-type-home' : 'address-type-work'}`}>
            {data.type === 'Home' ? <FaHome size={11} /> : <FaBriefcase size={11} />}
            {data.type || 'Home'}
          </span>

          {isDefault && (
            <span className="default-badge">
              <FaCheckCircle size={10} /> DEFAULT
            </span>
          )}
        </div>
      </div>

      {/* Contact Details */}
      <div className="d-flex flex-wrap align-items-center gap-2 text-secondary mb-2 small" style={{ fontSize: '0.82rem' }}>
        <a 
          href={`tel:${data.phone}`} 
          className="text-decoration-none text-dark fw-semibold d-inline-flex align-items-center gap-1"
        >
          <FaPhoneAlt size={11} className="text-muted" /> {data.phone}
        </a>
        {(data.alt_phone || data.altPhone) && (
          <span className="text-muted border-start ps-2">
            Alt: {data.alt_phone || data.altPhone}
          </span>
        )}
      </div>

      {/* Detailed Address Block */}
      <div className="d-flex gap-2 small mb-3" style={{ lineHeight: "1.5" }}>
        <FaMapPin className="text-danger flex-shrink-0 mt-1" size={13} />
        <div className="min-width-0">
          <div className="text-dark fw-semibold">{data.address}</div>
          <div className="text-muted">
            {data.locality}, {data.city}, {data.state} — <strong className="text-dark">{data.pincode}</strong>
          </div>
          {data.landmark && (
            <div className="mt-1 text-secondary" style={{ fontSize: '0.78rem' }}>
              Landmark: <em>{data.landmark}</em>
            </div>
          )}
        </div>
      </div>

      {/* Professional Action Buttons (Edit, Delete, Set Default) */}
      <div className="address-card-actions">
        {!isDefault && (
          <button
            type="button"
            className="address-btn-default"
            onClick={() => onSetDefault(data.id)}
            title="Set this address as default for checkout"
          >
            <FaCheckCircle size={11} />
            <span>Set as Default</span>
          </button>
        )}

        <button
          type="button"
          className="address-btn-edit"
          onClick={() => onEdit(data)}
          title="Edit address details"
        >
          <FaEdit size={11} />
          <span>Edit</span>
        </button>

        <button
          type="button"
          className="address-btn-delete"
          onClick={() => onDelete(data.id)}
          title="Delete address"
        >
          <FaTrashAlt size={11} />
          <span>Delete</span>
        </button>
      </div>
    </div>
  );
};

// ───────────────────────────────────────────────────────────
// 2. ADDRESS FORM COMPONENT WITH PINCODE & LOCATION AUTO-FILL
// ───────────────────────────────────────────────────────────
const AddressForm = ({ formData, handleInputChange, setFormData, handleSave, handleCancel }) => {
  const [locating, setLocating] = useState(false);
  const [lookingUpPin, setLookingUpPin] = useState(false);

  // Auto-fetch City and State when user enters 6-digit Pincode
  const handlePincodeChange = async (e) => {
    const pin = e.target.value.replace(/\D/g, '').slice(0, 6);
    setFormData(prev => ({ ...prev, pincode: pin }));

    if (pin.length === 6) {
      setLookingUpPin(true);
      try {
        const res = await fetch(`https://api.postalpincode.in/pincode/${pin}`);
        const data = await res.json();
        if (data && data[0] && data[0].Status === 'Success' && data[0].PostOffice?.length > 0) {
          const po = data[0].PostOffice[0];
          const district = po.District || po.Circle || '';
          const state = po.State || '';

          setFormData(prev => ({
            ...prev,
            city: district || prev.city,
            state: state || prev.state,
            locality: prev.locality || po.Name || ''
          }));
          toast.success(`Location detected: ${district}, ${state}`);
        }
      } catch (err) {
        console.warn("Pincode lookup error:", err);
      } finally {
        setLookingUpPin(false);
      }
    }
  };

  // Geo Location Auto-detect
  const handleGeoLocation = () => {
    if (!navigator.geolocation) {
      toast.error("Geolocation is not supported by your browser");
      return;
    }
    setLocating(true);
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        try {
          const { latitude, longitude } = position.coords;
          const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`);
          const geoData = await response.json();
          if (geoData && geoData.address) {
            const addr = geoData.address;
            setFormData(prev => ({
              ...prev,
              address: geoData.display_name || "",
              city: addr.city || addr.town || addr.village || addr.county || "",
              state: addr.state || "Delhi",
              pincode: addr.postcode ? addr.postcode.replace(/\D/g, '').slice(0, 6) : "",
              locality: addr.suburb || addr.neighbourhood || ""
            }));
            toast.success("Location retrieved successfully!");
          }
        } catch (error) {
          console.error("Geolocation fetch error:", error);
          toast.error("Could not fetch location details.");
        } finally {
          setLocating(false);
        }
      },
      () => {
        setLocating(false);
        toast.error("Unable to retrieve location. Please input fields manually.");
      }
    );
  };

  return (
    <div className="address-form-card">
      {/* Form Header with Back Button */}
      <div className="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
        <h5 className="fw-bold text-dark m-0 fs-5">
          {formData.id ? 'Modify Delivery Address' : 'Add New Delivery Address'}
        </h5>
        <Button 
          variant="light" 
          size="sm" 
          onClick={handleCancel} 
          className="rounded-pill d-flex align-items-center gap-1 border text-secondary px-3"
          style={{ minHeight: '36px' }}
        >
          <FaArrowLeft size={11} /> Back
        </Button>
      </div>

      {/* Auto Location Detect Helper */}
      <div className="mb-4">
        <button
          type="button"
          onClick={handleGeoLocation}
          disabled={locating}
          className="location-detect-btn"
        >
          {locating ? (
            <>
              <Spinner size="sm" animation="border" /> Detecting your location...
            </>
          ) : (
            <>
              <FaMapMarkerAlt /> Use My Current Location
            </>
          )}
        </button>
      </div>

      <Form onSubmit={handleSave}>
        {/* Row 1: Contact Name & Mobile */}
        <Row className="g-3 mb-3">
          <Col xs={12} md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">Contact Name *</Form.Label>
              <Form.Control 
                type="text" 
                name="name" 
                placeholder="Full Name" 
                value={formData.name} 
                onChange={handleInputChange} 
                className="rounded-3 shadow-none border"
                required 
              />
            </Form.Group>
          </Col>
          <Col xs={12} md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">10-Digit Mobile Number *</Form.Label>
              <Form.Control 
                type="tel" 
                name="phone" 
                placeholder="10-digit mobile number" 
                maxLength={10}
                value={formData.phone} 
                onChange={handleInputChange} 
                className="rounded-3 shadow-none border"
                required 
              />
            </Form.Group>
          </Col>
        </Row>

        {/* Row 2: Pincode & Locality */}
        <Row className="g-3 mb-3">
          <Col xs={12} md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary d-flex justify-content-between align-items-center">
                <span>Pincode *</span>
                {lookingUpPin && (
                  <span className="text-primary small" style={{ fontSize: '0.75rem' }}>
                    <Spinner size="sm" animation="border" className="me-1" /> Detecting city...
                  </span>
                )}
              </Form.Label>
              <Form.Control 
                type="text" 
                name="pincode" 
                placeholder="6-digit Pincode (Auto-fills city/state)" 
                maxLength={6}
                value={formData.pincode} 
                onChange={handlePincodeChange} 
                className="rounded-3 shadow-none border"
                required 
              />
            </Form.Group>
          </Col>
          <Col xs={12} md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">Locality / Sector / Area *</Form.Label>
              <Form.Control 
                type="text" 
                name="locality" 
                placeholder="Locality / Area" 
                value={formData.locality} 
                onChange={handleInputChange} 
                className="rounded-3 shadow-none border"
                required 
              />
            </Form.Group>
          </Col>
        </Row>

        {/* Row 3: Street Address */}
        <Form.Group className="mb-3">
          <Form.Label className="small fw-semibold text-secondary">
            Address Details (Flat No, House No, Building, Street) *
          </Form.Label>
          <Form.Control 
            as="textarea" 
            rows={3} 
            name="address" 
            placeholder="Complete delivery street address" 
            value={formData.address} 
            onChange={handleInputChange} 
            className="rounded-3 shadow-none border"
            required 
          />
        </Form.Group>

        {/* Row 4: City & State */}
        <Row className="g-3 mb-3">
          <Col xs={12} md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">City / District / Town *</Form.Label>
              <Form.Control 
                type="text" 
                name="city" 
                placeholder="City/District/Town" 
                value={formData.city} 
                onChange={handleInputChange} 
                className="rounded-3 shadow-none border"
                required 
              />
            </Form.Group>
          </Col>
          <Col xs={12} md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">State *</Form.Label>
              <Form.Select 
                name="state" 
                value={formData.state} 
                onChange={handleInputChange} 
                className="rounded-3 shadow-none border"
                required
              >
                {statesList.map(st => <option key={st} value={st}>{st}</option>)}
              </Form.Select>
            </Form.Group>
          </Col>
        </Row>

        {/* Row 5: Landmark & Alternate Phone */}
        <Row className="g-3 mb-3">
          <Col xs={12} md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">Landmark (Optional)</Form.Label>
              <Form.Control 
                type="text" 
                name="landmark" 
                placeholder="E.g. Near Metro Station / City Hospital" 
                value={formData.landmark} 
                onChange={handleInputChange} 
                className="rounded-3 shadow-none border"
              />
            </Form.Group>
          </Col>
          <Col xs={12} md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">Alternate Phone (Optional)</Form.Label>
              <Form.Control 
                type="tel" 
                name="altPhone" 
                placeholder="10-digit alternate phone" 
                maxLength={10}
                value={formData.altPhone} 
                onChange={handleInputChange} 
                className="rounded-3 shadow-none border"
              />
            </Form.Group>
          </Col>
        </Row>

        {/* Address Type 2-Card Responsive Selector */}
        <div className="mb-3">
          <Form.Label className="small fw-semibold text-secondary d-block mb-2">Address Type</Form.Label>
          <div className="address-type-selector">
            <div 
              className={`address-type-option ${formData.type === 'Home' ? 'active' : ''}`}
              onClick={() => setFormData(prev => ({ ...prev, type: 'Home' }))}
            >
              <div className="type-icon-box">
                <FaHome size={15} />
              </div>
              <div className="min-width-0">
                <div className="fw-bold small text-dark">Home</div>
                <div className="text-muted" style={{ fontSize: '0.72rem' }}>All-day delivery (7 AM - 9 PM)</div>
              </div>
            </div>

            <div 
              className={`address-type-option ${formData.type === 'Work' ? 'active' : ''}`}
              onClick={() => setFormData(prev => ({ ...prev, type: 'Work' }))}
            >
              <div className="type-icon-box">
                <FaBriefcase size={15} />
              </div>
              <div className="min-width-0">
                <div className="fw-bold small text-dark">Work</div>
                <div className="text-muted" style={{ fontSize: '0.72rem' }}>Delivery between 10 AM - 6 PM</div>
              </div>
            </div>
          </div>
        </div>

        {/* Default Address Checkbox Switch */}
        <div className="mb-4 p-3 bg-light rounded-3 border">
          <Form.Check 
            type="checkbox"
            id="is-default-switch"
            label="Make this my default shipping address for 1-click checkout"
            checked={Number(formData.is_default) === 1}
            onChange={(e) => setFormData(prev => ({ ...prev, is_default: e.target.checked ? 1 : 0 }))}
            className="fw-semibold text-dark small"
          />
        </div>

        {/* Form Action Controls (Rule 8: Full-width on mobile) */}
        <div className="form-footer-actions">
          <Button 
            type="button" 
            variant="light" 
            onClick={handleCancel} 
            className="rounded-pill px-4 fw-bold text-secondary border"
          >
            Cancel
          </Button>
          <Button
            type="submit"
            variant="primary"
            className="rounded-pill px-5 fw-bold border-0 shadow-sm"
            style={{ backgroundColor: '#0b53a1', minHeight: '44px' }}
          >
            {formData.id ? 'Update Address' : 'Save Address'}
          </Button>
        </div>
      </Form>
    </div>
  );
};

// ───────────────────────────────────────────────────────────
// 3. MAIN MANAGE ADDRESS COMPONENT
// ───────────────────────────────────────────────────────────
const ManageAddress = () => {
  const { user, token, getUsernamePath } = useAuth();
  const navigate = useNavigate();
  const usernamePath = getUsernamePath ? getUsernamePath() : 'user';

  const [addresses, setAddresses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [formData, setFormData] = useState(emptyAddress);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchAddresses = useCallback(async (showLoader = false) => {
    const userId = user?.id || user?.user_id || user?.customer_id;
    try {
      if (showLoader) setLoading(true);
      const activeToken = token || localStorage.getItem('token') || '';
      const headers = {};
      if (activeToken) headers['Authorization'] = `Bearer ${activeToken}`;

      let url = API_ENDPOINTS.GET_ADDRESSES;
      if (userId) {
        url += `&user_id=${userId}`;
      }

      const res = await fetch(url, { headers });
      const data = await res.json();
      if (data.success || data.status === 'success') {
        const list = data.data || data.addresses || [];
        setAddresses(Array.isArray(list) ? list : []);
      }
    } catch (err) {
      console.error("Failed to load addresses", err);
    } finally {
      if (showLoader) setLoading(false);
    }
  }, [user, token]);

  useEffect(() => {
    fetchAddresses(true);
  }, [fetchAddresses]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleCancel = () => {
    setFormData(emptyAddress);
    setShowForm(false);
  };

  const handleSave = async (e) => {
    e.preventDefault();
    const userId = user?.id || user?.user_id || user?.customer_id;

    const cleanedPhone = (formData.phone || "").replace(/\D/g, "");
    if (cleanedPhone.length < 10) {
      toast.error("Please enter a valid 10-digit mobile number.");
      return;
    }

    const payload = { 
      ...formData, 
      phone: cleanedPhone.slice(-10),
      user_id: userId 
    };
    const isEdit = !!formData.id;
    const endpoint = isEdit ? API_ENDPOINTS.UPDATE_ADDRESS : API_ENDPOINTS.ADD_ADDRESS;

    try {
      const activeToken = token || localStorage.getItem('token') || '';
      const headers = { 'Content-Type': 'application/json' };
      if (activeToken) headers['Authorization'] = `Bearer ${activeToken}`;

      const res = await fetch(endpoint, {
        method: 'POST',
        headers,
        body: JSON.stringify(payload)
      });
      
      const rawText = await res.text();
      let data = {};
      try {
        data = JSON.parse(rawText);
      } catch (parseErr) {
        console.error("Non-JSON API response:", rawText);
        toast.error(`Server Error (${res.status}). Failed to save address.`);
        return;
      }
      
      if (data.success || data.status === 'success' || data.address_id) {
        toast.success(isEdit ? "Address updated successfully!" : "Address saved successfully!");
        
        const savedId = data.address_id || formData.id || Date.now();
        const updatedItem = {
          ...payload,
          id: savedId,
          alt_phone: payload.altPhone || payload.alt_phone || ""
        };

        if (isEdit) {
          setAddresses(prev => prev.map(a => Number(a.id) === Number(formData.id) ? { ...a, ...updatedItem } : a));
        } else {
          setAddresses(prev => [updatedItem, ...prev]);
        }

        handleCancel();
        fetchAddresses(false);
      } else {
        toast.error(data.error || data.message || "Failed to save address");
      }
    } catch (err) {
      console.error("Error saving address", err);
      toast.error("Network error occurred while saving address");
    }
  };

  const handleEdit = (address) => {
    setFormData({
      ...address,
      altPhone: address.alt_phone || address.altPhone || ""
    });
    setShowForm(true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleSetDefault = async (id) => {
    try {
      const activeToken = token || localStorage.getItem('token') || '';
      const headers = { 'Content-Type': 'application/json' };
      if (activeToken) headers['Authorization'] = `Bearer ${activeToken}`;

      setAddresses(prev => prev.map(a => ({
        ...a,
        is_default: Number(a.id) === Number(id) ? 1 : 0
      })));
      toast.success("Default address updated!");

      await fetch(API_ENDPOINTS.SET_DEFAULT_ADDRESS, {
        method: 'POST',
        headers,
        body: JSON.stringify({ id, user_id: user?.id })
      });
      fetchAddresses(false);
    } catch (err) {
      console.error("Set default address error:", err);
    }
  };

  const handleDeleteClick = (id) => {
    const target = addresses.find(a => Number(a.id) === Number(id));
    setDeleteTarget(target || { id });
  };

  const confirmDeleteAddress = async () => {
    if (!deleteTarget) return;
    const targetId = deleteTarget.id;
    const userId = user?.id || user?.user_id || user?.customer_id;

    setAddresses(prev => prev.filter(a => Number(a.id) !== Number(targetId)));
    toast.success("Address deleted successfully!");
    setDeleteTarget(null);

    try {
      setDeleting(true);
      const activeToken = token || localStorage.getItem('token') || '';
      const headers = { 'Content-Type': 'application/json' };
      if (activeToken) headers['Authorization'] = `Bearer ${activeToken}`;

      const res = await fetch(API_ENDPOINTS.DELETE_ADDRESS, {
        method: 'POST',
        headers,
        body: JSON.stringify({ id: targetId, user_id: userId })
      });
      const data = await res.json();
      if (!data.success && data.status !== 'success') {
        toast.error(data.error || data.message || "Failed to sync delete with server.");
      }
      fetchAddresses(false);
    } catch (err) {
      console.error("Error deleting address", err);
      fetchAddresses(false);
    } finally {
      setDeleting(false);
    }
  };

  const getInitialFormData = () => {
    const defaultName = user?.name || (user?.first_name ? `${user.first_name} ${user.last_name || ''}`.trim() : "");
    const defaultPhone = user?.phone || user?.mobile || user?.contact || "";
    return {
      ...emptyAddress,
      name: defaultName,
      phone: defaultPhone
    };
  };

  const handleAddAddressClick = () => {
    setFormData(getInitialFormData());
    setShowForm(true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <div className="manage-address-page pb-4">
      {/* ─────────────────────────────────────────────────────────── */}
      {/* 1. Mobile App Topbar (Rule 4: Screens < 992px)              */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="d-lg-none mobile-address-topbar">
        <button
          type="button"
          className="mobile-address-back-btn"
          onClick={() => navigate('/my-account')}
          aria-label="Back to My Account"
        >
          <FaArrowLeft size={15} />
        </button>
        <div className="mobile-address-title">
          <h5 className="mb-0 fw-bold">Manage Addresses</h5>
          <span className="text-muted" style={{ fontSize: '0.74rem' }}>Saved Delivery Locations</span>
        </div>
        {!showForm && (
          <button
            type="button"
            className="mobile-add-btn"
            onClick={handleAddAddressClick}
          >
            <FaPlus size={11} /> Add
          </button>
        )}
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 2. Desktop Header Card (Rule 4: Hidden on Mobile)           */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="d-none d-lg-flex desktop-address-header">
        <div className="min-width-0">
          <h4 className="fw-bold text-dark mb-1">Manage Delivery Addresses</h4>
          <p className="text-secondary small mb-0">Save addresses for quick and hassle-free checkout on your orders.</p>
        </div>
        {!showForm && (
          <Button
            variant="primary"
            onClick={handleAddAddressClick}
            className="d-inline-flex align-items-center gap-2 rounded-pill px-4 py-2 fw-bold border-0 shadow-sm flex-shrink-0"
            style={{ backgroundColor: '#0b53a1', minHeight: '44px' }}
          >
            <FaPlus size={13} /> + ADD A NEW ADDRESS
          </Button>
        )}
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 3. Address Form Section                                     */}
      {/* ─────────────────────────────────────────────────────────── */}
      {showForm && (
        <AddressForm
          formData={formData}
          handleInputChange={handleInputChange}
          setFormData={setFormData}
          handleSave={handleSave}
          handleCancel={handleCancel}
        />
      )}

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 4. Saved Addresses List                                    */}
      {/* ─────────────────────────────────────────────────────────── */}
      {!showForm && (
        <>
          {loading ? (
            <div className="text-center py-5 bg-white rounded-4 shadow-sm border p-4">
              <Spinner animation="border" variant="primary" />
              <p className="text-muted small mt-3 mb-0">Loading your saved addresses...</p>
            </div>
          ) : addresses.length === 0 ? (
            <Card className="border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white align-items-center text-center">
              <div 
                className="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                style={{ width: 68, height: 68 }}
              >
                <FaMapMarkerAlt size={32} className="text-muted" />
              </div>
              <h5 className="fw-bold text-dark mb-2">No Addresses Found</h5>
              <p className="text-secondary small mb-4" style={{ maxWidth: "340px" }}>
                Add your delivery address to enjoy 1-click checkout and accurate delivery times.
              </p>
              <Button
                variant="primary"
                onClick={handleAddAddressClick}
                className="d-inline-flex align-items-center gap-2 rounded-pill px-4 py-2 fw-bold border-0 shadow-sm"
                style={{ backgroundColor: '#0b53a1', minHeight: '44px' }}
              >
                <FaPlus size={12} /> Add New Address
              </Button>
            </Card>
          ) : (
            <div className="d-flex flex-column">
              {addresses.map((address) => (
                <SavedAddressCard
                  key={address.id}
                  data={address}
                  onEdit={handleEdit}
                  onDelete={handleDeleteClick}
                  onSetDefault={handleSetDefault}
                />
              ))}
            </div>
          )}
        </>
      )}

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 5. Professional Delete Confirmation Dialog                  */}
      {/* ─────────────────────────────────────────────────────────── */}
      {deleteTarget && (
        <Modal show={true} onHide={() => setDeleteTarget(null)} centered contentClassName="rounded-4 border-0 shadow-lg overflow-hidden">
          <div className="p-4 text-center">
            <div className="address-delete-icon-box">
              <FaTrashAlt size={22} />
            </div>
            <h5 className="fw-bold text-dark mb-2">Delete Address?</h5>
            <p className="text-secondary small mb-3">
              Are you sure you want to delete this delivery address for <strong className="text-dark">{deleteTarget.name}</strong>?
            </p>

            <div className="address-delete-warning-box text-start">
              <FaExclamationTriangle size={16} className="flex-shrink-0 mt-1 text-danger" />
              <div>
                {deleteTarget.address ? `${deleteTarget.address}, ${deleteTarget.city}` : 'This address will be removed from your account.'}
              </div>
            </div>

            <div className="d-flex align-items-center justify-content-center gap-2 mt-4 pt-2">
              <button
                type="button"
                className="btn btn-light rounded-pill px-4 py-2 fw-semibold text-secondary"
                onClick={() => setDeleteTarget(null)}
                disabled={deleting}
              >
                Cancel &amp; Keep
              </button>
              <button
                type="button"
                className="btn btn-danger rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center gap-2 shadow-xs"
                onClick={confirmDeleteAddress}
                disabled={deleting}
                style={{ backgroundColor: '#dc2626', borderColor: '#dc2626' }}
              >
                {deleting ? (
                  <>
                    <Spinner animation="border" size="sm" /> Deleting...
                  </>
                ) : (
                  <>
                    <FaTrashAlt size={12} /> Yes, Delete
                  </>
                )}
              </button>
            </div>
          </div>
        </Modal>
      )}
    </div>
  );
};

export default ManageAddress;