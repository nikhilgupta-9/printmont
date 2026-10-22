import React, { useState, useEffect, useRef } from "react";
import "./orders.css";
import { useCheckout } from '../../context/CheckoutContext';
import { useAuth } from '../../context/AuthContext';
import { INDIAN_STATES } from '../../config/indianStates';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import { toast } from 'react-hot-toast';
import { FaMapMarkerAlt, FaCheck, FaBuilding, FaStickyNote } from 'react-icons/fa';

/**
 * Step 1/2 of checkout: ADD BILLING AND DELIVERY ADDRESS
 * 
 * Implements exact mobile view (Image 1) and desktop view (Image 2),
 * with real-time validation, automatic pre-fill from user profile/saved addresses,
 * and live backend API persistence.
 */
const OrderAddress = ({ onAddressSaved }) => {
  const { address, setAddress, buyerDetails, setBuyerDetails, deliveryPincode, deliveryInfo } = useCheckout();
  const { user } = useAuth();

  const [formData, setFormData] = useState({
    name: buyerDetails?.name || [user?.first_name, user?.last_name].filter(Boolean).join(' ') || user?.name || "",
    mobile: buyerDetails?.mobile || user?.phone || user?.mobile || "",
    email: buyerDetails?.email || user?.email || "",
    company: address?.company || "",
    gstin: address?.gstin || "",
    houseNo: address?.houseNo || "",
    roadArea: address?.roadArea || "",
    addressLine: address?.addressLine || address?.address || "",
    city: address?.city || (deliveryInfo?.pincode === deliveryPincode ? deliveryInfo?.city_name : "") || "",
    landmark: address?.landmark || "",
    pincode: address?.pincode || deliveryPincode || "",
    state: address?.state || (deliveryInfo?.pincode === deliveryPincode ? deliveryInfo?.state_name : "") || "",
    altPhone: address?.altPhone || "",
    addressType: address?.addressType || "Home",
    orderNotes: address?.orderNotes || "",
  });

  const [errors, setErrors] = useState({});
  const [showCompanyInput, setShowCompanyInput] = useState(!!address?.company);
  const [showOrderNotesInput, setShowOrderNotesInput] = useState(!!address?.orderNotes);
  const [isSubmitting, setIsSubmitting] = useState(false);
  // Default saved address from the account; a new one is saved only when the form differs from it
  const savedAddressRef = useRef(null);

  // Fetch saved address from Backend API on mount
  useEffect(() => {
    const loadSavedAddresses = async () => {
      const token = localStorage.getItem('token') || sessionStorage.getItem('token');
      if (!token) return;

      try {
        const res = await fetch(API_ENDPOINTS.GET_ADDRESSES, {
          headers: { Authorization: `Bearer ${token}` }
        });
        const json = await res.json();
        const list = Array.isArray(json?.addresses) ? json.addresses : (Array.isArray(json?.data) ? json.data : []);

        if (json && json.success && list.length > 0) {
          const defaultAddr = list.find((a) => a.is_default == 1 || a.is_default === '1') || list[0];
          if (defaultAddr) {
            savedAddressRef.current = defaultAddr;
            setFormData((prev) => ({
              ...prev,
              name: prev.name || defaultAddr.name || "",
              mobile: prev.mobile || defaultAddr.phone || defaultAddr.mobile || "",
              addressLine: prev.addressLine || defaultAddr.address || "",
              houseNo: prev.houseNo || defaultAddr.house_no || "",
              roadArea: prev.roadArea || defaultAddr.locality || defaultAddr.road_area || "",
              city: prev.city || defaultAddr.city || "",
              state: prev.state || defaultAddr.state || "",
              pincode: prev.pincode || defaultAddr.pincode || "",
              landmark: prev.landmark || defaultAddr.landmark || "",
              altPhone: prev.altPhone || defaultAddr.altPhone || defaultAddr.alt_phone || defaultAddr.alternate_phone || "",
              addressType: defaultAddr.type || defaultAddr.address_type || prev.addressType || "Home"
            }));
          }
        }
      } catch (err) {
        console.warn("Could not fetch saved addresses from API:", err);
      }
    };

    loadSavedAddresses();
  }, []);

  const validateForm = () => {
    const e = {};

    if (!formData.name.trim()) e.name = "Full name is required";
    if (!/^\d{10}$/.test(formData.mobile.trim())) e.mobile = "Enter a valid 10-digit mobile number";

    // For mobile: check houseNo or addressLine; for desktop: check addressLine or houseNo
    const hasAddress = formData.addressLine.trim() || formData.houseNo.trim();
    if (!hasAddress) {
      e.addressLine = "House / Building and street address is required";
      e.houseNo = "House No. / Building is required";
    }

    if (!formData.city.trim()) e.city = "City / District / Town is required";
    if (!/^\d{6}$/.test(formData.pincode.trim())) e.pincode = "Enter a valid 6-digit pincode";
    if (!formData.state.trim()) e.state = "Please select your state";

    if (formData.altPhone.trim() && !/^\d{10}$/.test(formData.altPhone.trim())) {
      e.altPhone = "Alternate number must be 10 digits";
    }

    // Needed for the order confirmation email
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email.trim())) {
      e.email = "Enter a valid email address";
    }

    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => {
      const updated = { ...prev, [name]: value };
      if (name === 'houseNo' || name === 'roadArea') {
        updated.addressLine = [updated.houseNo, updated.roadArea].filter(Boolean).join(', ');
      }
      return updated;
    });
    setErrors((prev) => (prev[name] ? { ...prev, [name]: undefined } : prev));
  };

  const handleCompanyCheckChange = (e) => {
    const checked = e.target.checked;
    setShowCompanyInput(checked);
    if (!checked) setFormData((prev) => ({ ...prev, company: "", gstin: "" }));
  };

  const handleOrderNotesCheckChange = (e) => {
    const checked = e.target.checked;
    setShowOrderNotesInput(checked);
    if (!checked) setFormData((prev) => ({ ...prev, orderNotes: "" }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validateForm()) {
      toast.error("Please fill in all required delivery fields.");
      return;
    }

    setIsSubmitting(true);

    const fullAddressLine = formData.addressLine.trim() || [formData.houseNo, formData.roadArea].filter(Boolean).join(', ');

    const addressPayload = {
      name: formData.name.trim(),
      phone: formData.mobile.trim(),
      mobile: formData.mobile.trim(),
      email: formData.email.trim(),
      pincode: formData.pincode.trim(),
      locality: formData.roadArea ? formData.roadArea.trim() : formData.city.trim(),
      address: fullAddressLine.trim(),
      addressLine: fullAddressLine.trim(),
      houseNo: formData.houseNo.trim(),
      roadArea: formData.roadArea.trim(),
      city: formData.city.trim(),
      state: formData.state.trim(),
      landmark: formData.landmark.trim(),
      altPhone: formData.altPhone.trim(),
      type: formData.addressType,
      company: formData.company.trim(),
      gstin: formData.gstin.trim(),
      orderNotes: formData.orderNotes.trim()
    };

    // 1. Update Context State
    setBuyerDetails({
      name: formData.name.trim(),
      mobile: formData.mobile.trim(),
      email: formData.email.trim()
    });
    setAddress(addressPayload);

    // 2. Save to the account when it is a new address (not the saved one used as is)
    const saved = savedAddressRef.current;
    const sameAsSaved = saved
      && String(saved.name || '').trim() === addressPayload.name
      && String(saved.phone || '').trim() === addressPayload.phone
      && String(saved.pincode || '').trim() === addressPayload.pincode
      && String(saved.address || '').trim() === addressPayload.address
      && String(saved.city || '').trim() === addressPayload.city
      && String(saved.state || '').trim() === addressPayload.state;
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    if (token && !sameAsSaved) {
      try {
        await fetch(API_ENDPOINTS.ADD_ADDRESS, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`
          },
          body: JSON.stringify(addressPayload)
        });
      } catch (apiErr) {
        console.warn("Could not save address to server:", apiErr);
      }
    }

    setIsSubmitting(false);
    toast.success("Delivery address saved successfully!");

    if (onAddressSaved) {
      onAddressSaved(addressPayload);
    }
  };

  const field = (name) => `form-control checkout-input${errors[name] ? ' is-invalid' : ''}`;
  const err = (name) => errors[name] && <div className="invalid-feedback d-block">{errors[name]}</div>;

  return (
    <div className="bg-white checkout-address-form p-3 p-md-4">
      {/* 1. MOBILE 3-STEP PROGRESS INDICATOR (Image 1) */}
      <div className="d-md-none mb-3 pb-2 border-bottom">
        <div className="d-flex align-items-center justify-content-between px-2 text-center position-relative">
          {/* Step 1: Address */}
          <div className="d-flex flex-column align-items-center flex-1 position-relative z-1">
            <div
              className="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs"
              style={{ width: '28px', height: '28px', backgroundColor: '#0b53a1', fontSize: '13px' }}
            >
              1
            </div>
            <span className="fw-bold mt-1 text-dark" style={{ fontSize: '11.5px' }}>Address</span>
          </div>

          {/* Line 1 -> 2 */}
          <div className="flex-grow-1 border-top" style={{ borderColor: '#cbd5e1', marginTop: '-18px' }}></div>

          {/* Step 2: Order Summary */}
          <div className="d-flex flex-column align-items-center flex-1 position-relative z-1">
            <div
              className="rounded-circle d-flex align-items-center justify-content-center fw-bold border bg-white text-muted"
              style={{ width: '28px', height: '28px', fontSize: '13px', borderColor: '#94a3b8' }}
            >
              2
            </div>
            <span className="text-muted mt-1" style={{ fontSize: '11px' }}>Order Summary</span>
          </div>

          {/* Line 2 -> 3 */}
          <div className="flex-grow-1 border-top" style={{ borderColor: '#cbd5e1', marginTop: '-18px' }}></div>

          {/* Step 3: Payment */}
          <div className="d-flex flex-column align-items-center flex-1 position-relative z-1">
            <div
              className="rounded-circle d-flex align-items-center justify-content-center fw-bold border bg-white text-muted"
              style={{ width: '28px', height: '28px', fontSize: '13px', borderColor: '#94a3b8' }}
            >
              3
            </div>
            <span className="text-muted mt-1" style={{ fontSize: '11px' }}>Payment</span>
          </div>
        </div>
      </div>

      {/* 2. FORM HEADING (Both Mobile & Desktop) */}
      <h5 className="fw-bold text-dark text-uppercase mb-3" style={{ fontSize: '14px', letterSpacing: '0.3px' }}>
        ADD BILLING AND DELIVERY ADDRESS
      </h5>

      <form onSubmit={handleSubmit} id="addressForm" noValidate className="checkout-form-body">

        {/* Full Name & Mobile Number */}
        <div className="row g-2 g-md-3 mb-3">
          <div className="col-12 col-md-6 mb-2 mb-md-0">
            <label className="checkout-label d-none d-md-block" htmlFor="addr-name">Name <span className="req">*</span></label>
            <input
              id="addr-name"
              type="text"
              name="name"
              className={field('name')}
              placeholder="Full Name (Required) *"
              value={formData.name}
              onChange={handleChange}
            />
            {err('name')}
          </div>
          <div className="col-12 col-md-6">
            <label className="checkout-label d-none d-md-block" htmlFor="addr-mobile">Mobile number <span className="req">*</span></label>
            <input
              id="addr-mobile"
              type="tel"
              name="mobile"
              inputMode="numeric"
              maxLength={10}
              className={field('mobile')}
              placeholder="Mobile number (Required) *"
              value={formData.mobile}
              onChange={handleChange}
            />
            {err('mobile')}
          </div>
        </div>

        {/* Company Name Checkbox (Optional) */}
        <div className="mb-3">
          <div className="form-check checkout-check">
            <input
              className="form-check-input shadow-none cursor-pointer"
              type="checkbox"
              id="companyCheck"
              checked={showCompanyInput}
              onChange={handleCompanyCheckChange}
            />
            <label className="form-check-label cursor-pointer text-dark" htmlFor="companyCheck" style={{ fontSize: '13px' }}>
              Company name (optional)
            </label>
          </div>
          {showCompanyInput && (
            <div className="row g-2 g-md-3 mt-2">
              <div className="col-12 col-md-6 mb-2 mb-md-0">
                <input
                  type="text"
                  className="form-control checkout-input"
                  name="company"
                  placeholder="Company Name"
                  value={formData.company}
                  onChange={handleChange}
                />
              </div>
              <div className="col-12 col-md-6">
                <input
                  type="text"
                  className="form-control checkout-input"
                  name="gstin"
                  placeholder="GSTIN (Optional)"
                  value={formData.gstin}
                  onChange={handleChange}
                />
              </div>
            </div>
          )}
        </div>

        {/* Mobile: Split House No. & Road Name | Desktop: Single Address / Combined */}
        <div className="d-md-none mb-3">
          <input
            id="addr-house"
            type="text"
            name="houseNo"
            className={field('houseNo')}
            placeholder="House No., Building name (Required) *"
            value={formData.houseNo}
            onChange={handleChange}
          />
          {err('houseNo')}
        </div>
        <div className="d-md-none mb-3">
          <input
            id="addr-road"
            type="text"
            name="roadArea"
            className={field('roadArea')}
            placeholder="Road name, Area, Colony (Required) *"
            value={formData.roadArea}
            onChange={handleChange}
          />
          {err('roadArea')}
        </div>

        {/* Desktop Address Textarea */}
        <div className="d-none d-md-block mb-3">
          <label className="checkout-label" htmlFor="addr-line">Address <span className="req">*</span></label>
          <textarea
            id="addr-line"
            name="addressLine"
            rows="3"
            className={field('addressLine')}
            placeholder="Address (Area and Street)"
            value={formData.addressLine}
            onChange={handleChange}
          />
          {err('addressLine')}
        </div>

        {/* City & Landmark (Mobile) / City & State (Desktop) */}
        <div className="row g-2 g-md-3 mb-3">
          <div className="col-6 col-md-6">
            <label className="checkout-label d-none d-md-block" htmlFor="addr-city">City / District / Town <span className="req">*</span></label>
            <input
              id="addr-city"
              type="text"
              name="city"
              className={field('city')}
              placeholder="City / District / Town *"
              value={formData.city}
              onChange={handleChange}
            />
            {err('city')}
          </div>

          {/* Mobile Landmark | Desktop State */}
          <div className="col-6 d-md-none">
            <input
              id="addr-landmark-mob"
              type="text"
              name="landmark"
              className={field('landmark')}
              placeholder="Landmark *"
              value={formData.landmark}
              onChange={handleChange}
            />
            {err('landmark')}
          </div>

          <div className="col-12 col-md-6 d-none d-md-block">
            <label className="checkout-label" htmlFor="addr-state-desk">State <span className="req">*</span></label>
            <select
              id="addr-state-desk"
              name="state"
              className={`form-select checkout-input${errors.state ? ' is-invalid' : ''}`}
              value={formData.state}
              onChange={handleChange}
            >
              <option value="">State *</option>
              {INDIAN_STATES.map((s) => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>
            {err('state')}
          </div>
        </div>

        {/* Pincode & State (Mobile) / Pincode & Landmark (Desktop) */}
        <div className="row g-2 g-md-3 mb-3">
          <div className="col-6 col-md-6">
            <label className="checkout-label d-none d-md-block" htmlFor="addr-pincode">Pincode <span className="req">*</span></label>
            <input
              id="addr-pincode"
              type="text"
              name="pincode"
              inputMode="numeric"
              maxLength={6}
              className={field('pincode')}
              placeholder="Pincode *"
              value={formData.pincode}
              onChange={handleChange}
            />
            {err('pincode')}
          </div>

          {/* Mobile State Dropdown */}
          <div className="col-6 d-md-none">
            <select
              id="addr-state-mob"
              name="state"
              className={`form-select checkout-input${errors.state ? ' is-invalid' : ''}`}
              value={formData.state}
              onChange={handleChange}
            >
              <option value="">State *</option>
              {INDIAN_STATES.map((s) => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>
            {err('state')}
          </div>

          {/* Desktop Landmark */}
          <div className="col-12 col-md-6 d-none d-md-block">
            <label className="checkout-label" htmlFor="addr-landmark-desk">Landmark</label>
            <input
              id="addr-landmark-desk"
              type="text"
              name="landmark"
              className={field('landmark')}
              placeholder="Landmark (Optional)"
              value={formData.landmark}
              onChange={handleChange}
            />
          </div>
        </div>

        {/* Email & Alternate Phone */}
        <div className="row g-2 g-md-3 mb-3">
          <div className="col-6 col-md-6">
            <label className="checkout-label d-none d-md-block" htmlFor="addr-email">Email address <span className="req">*</span></label>
            <input
              id="addr-email"
              type="email"
              name="email"
              className={field('email')}
              placeholder="Email address optional *"
              value={formData.email}
              onChange={handleChange}
            />
            {err('email')}
          </div>
          <div className="col-6 col-md-6">
            <label className="checkout-label d-none d-md-block" htmlFor="addr-alt">Alternate Phone Number</label>
            <input
              id="addr-alt"
              type="tel"
              name="altPhone"
              inputMode="numeric"
              maxLength={10}
              className={field('altPhone')}
              placeholder="Alternate Number Required *"
              value={formData.altPhone}
              onChange={handleChange}
            />
            {err('altPhone')}
          </div>
        </div>

        {/* Address Type Radios */}
        <div className="mb-3">
          <label className="fw-bold text-dark d-block mb-2" style={{ fontSize: '13.5px' }}>
            Address Type
          </label>
          <div className="d-flex align-items-center gap-4">
            {["Home", "Office", "Other"].map((type) => (
              <div className="form-check checkout-check" key={type}>
                <input
                  className="form-check-input shadow-none cursor-pointer"
                  type="radio"
                  name="addressType"
                  id={`type-${type}`}
                  value={type}
                  checked={formData.addressType === type}
                  onChange={handleChange}
                />
                <label className="form-check-label cursor-pointer text-dark" htmlFor={`type-${type}`} style={{ fontSize: '13.5px' }}>
                  {type}
                </label>
              </div>
            ))}
          </div>
        </div>

        {/* Order Notes Checkbox */}
        <div className="mb-3 pb-2 pb-md-0">
          <div className="form-check checkout-check">
            <input
              className="form-check-input shadow-none cursor-pointer"
              type="checkbox"
              id="orderNotesCheck"
              checked={showOrderNotesInput}
              onChange={handleOrderNotesCheckChange}
            />
            <label className="form-check-label cursor-pointer text-dark" htmlFor="orderNotesCheck" style={{ fontSize: '13px' }}>
              Order notes (optional)?
            </label>
          </div>
          {showOrderNotesInput && (
            <textarea
              className="form-control checkout-input mt-2"
              name="orderNotes"
              rows="2"
              placeholder="Add special instructions for delivery..."
              value={formData.orderNotes}
              onChange={handleChange}
            />
          )}
        </div>

        {/* Desktop Save Button (Image 2) */}
        <div className="d-none d-md-block mt-4">
          <button
            type="submit"
            className="btn text-white fw-bold py-2.5 px-4 shadow-sm"
            style={{ backgroundColor: '#0b53a1', borderRadius: '4px', fontSize: '14px', letterSpacing: '0.4px' }}
            disabled={isSubmitting}
          >
            {isSubmitting ? "SAVING..." : "SAVE AND DELIVERY HERE"}
          </button>
        </div>

        {/* Mobile Full-Width Sticky Button (Image 1) */}
        <div
          className="d-md-none position-fixed bottom-0 start-0 w-100 bg-white border-top shadow-lg"
          style={{ zIndex: 1040 }}
        >
          <button
            type="submit"
            className="btn w-100 py-3 text-white fw-bold text-uppercase rounded-0 shadow-sm"
            style={{
              backgroundColor: '#0b53a1',
              borderColor: '#0b53a1',
              fontSize: '15px',
              letterSpacing: '0.5px'
            }}
            disabled={isSubmitting}
          >
            {isSubmitting ? "SAVING..." : "SAVE AND DELIVERY HERE"}
          </button>
        </div>

      </form>
    </div>
  );
};

export default OrderAddress;
