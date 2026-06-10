import React, { useState } from "react";
import "./orders.css";
import { useCheckout } from '../../context/CheckoutContext';

const OrderAddress = ({ onAddressSaved }) => {
  const { address, setAddress } = useCheckout();

  const [formData, setFormData] = useState({
    company: address?.company || "",
    addressArea: address?.addressArea || "",
    city: address?.city || "",
    state: address?.state || "",
    pincode: address?.pincode || "",
    landmark: address?.landmark || "",
    altPhone: address?.altPhone || "",
    gstin: address?.gstin || "",
    addressType: address?.addressType || "Home",
    orderNotes: address?.orderNotes || "",
  });

  const [errors, setErrors] = useState({});
  const [showCompanyInput, setShowCompanyInput] = useState(!!address?.company);
  const [showOrderNotesInput, setShowOrderNotesInput] = useState(!!address?.orderNotes);

  // Validation Logic
  const validateForm = () => {
    const newErrors = {};

    if (!formData.addressArea.trim()) newErrors.addressArea = "Address is required";
    if (!formData.city.trim()) newErrors.city = "City is required";
    if (!formData.state.trim()) newErrors.state = "State is required";
    if (!/^\d{6}$/.test(formData.pincode)) newErrors.pincode = "Pincode must be 6 digits";
    if (!formData.landmark.trim()) newErrors.landmark = "Landmark is required";

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
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

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!validateForm()) return; 

    const addressData = {
        ...formData,
        address: formData.addressArea
    };

    setAddress(addressData);

    if (onAddressSaved) onAddressSaved(addressData);
  };

  return (
    <div className="bg-white p-4">
      <form onSubmit={handleSubmit} id="addressForm">
        
        {/* Company Details */}
        <div className="mb-3">
          <div className="form-check mb-2">
            <input className="form-check-input border-secondary shadow-none" type="checkbox" id="companyCheck" checked={showCompanyInput} onChange={handleCompanyCheckChange} style={{ marginTop: '0.3rem' }} />
            <label className="form-check-label text-dark fw-semibold" htmlFor="companyCheck" style={{ fontSize: '14px' }}>
              Company name (optional)
            </label>
          </div>
          {showCompanyInput && (
            <div className="row g-3 mt-1">
              <div className="col-12 col-md-6">
                <input type="text" className="form-control shadow-none rounded-1 border-secondary" name="company" placeholder="Enter Company Name" value={formData.company} onChange={handleChange} />
              </div>
              <div className="col-12 col-md-6">
                <input type="text" className="form-control shadow-none rounded-1 border-secondary" name="gstin" placeholder="Enter GSTIN" value={formData.gstin} onChange={handleChange} />
              </div>
            </div>
          )}
        </div>

        {/* Address Field (Textarea) */}
        <div className="mb-3">
          <label className="form-label text-dark mb-1" style={{ fontSize: '14px' }}>Address *</label>
          <textarea className="form-control shadow-none rounded-1 border-secondary" name="addressArea" rows="2" placeholder="Address (Area and Street)" value={formData.addressArea} onChange={handleChange} required></textarea>
          {errors.addressArea && <small className="text-danger">{errors.addressArea}</small>}
        </div>

        {/* City / State */}
        <div className="row g-3 mb-3">
          <div className="col-12 col-md-6">
            <label className="form-label text-dark mb-1" style={{ fontSize: '14px' }}>City / District / Town *</label>
            <input type="text" className="form-control shadow-none rounded-1 border-secondary" name="city" value={formData.city} onChange={handleChange} required />
            {errors.city && <small className="text-danger">{errors.city}</small>}
          </div>
          <div className="col-12 col-md-6">
            <label className="form-label text-dark mb-1" style={{ fontSize: '14px' }}>State *</label>
            <select className="form-select shadow-none rounded-1 border-secondary" name="state" value={formData.state} onChange={handleChange} required>
              <option value=""></option>
              <option value="Delhi">Delhi</option>
              <option value="Maharashtra">Maharashtra</option>
              <option value="Karnataka">Karnataka</option>
            </select>
            {errors.state && <small className="text-danger">{errors.state}</small>}
          </div>
        </div>

        {/* Pincode / Landmark */}
        <div className="row g-3 mb-3">
          <div className="col-12 col-md-6">
            <label className="form-label text-dark mb-1" style={{ fontSize: '14px' }}>Pincode *</label>
            <input type="text" className="form-control shadow-none rounded-1 border-secondary" name="pincode" value={formData.pincode} onChange={handleChange} required />
            {errors.pincode && <small className="text-danger">{errors.pincode}</small>}
          </div>
          <div className="col-12 col-md-6">
            <label className="form-label text-dark mb-1" style={{ fontSize: '14px' }}>Landmark *</label>
            <input type="text" className="form-control shadow-none rounded-1 border-secondary" name="landmark" value={formData.landmark} onChange={handleChange} required />
            {errors.landmark && <small className="text-danger">{errors.landmark}</small>}
          </div>
        </div>

        {/* Alt Phone */}
        <div className="row g-3 mb-4">
          <div className="col-12 col-md-6">
            <label className="form-label text-dark mb-1" style={{ fontSize: '14px' }}>Alternate Phone Number *</label>
            <input type="tel" className="form-control shadow-none rounded-1 border-secondary" name="altPhone" value={formData.altPhone} onChange={handleChange} />
          </div>
        </div>

        {/* Address Type */}
        <div className="mb-4">
          <label className="form-label text-dark mb-2" style={{ fontSize: '15px' }}>Adderss Type</label>
          <div className="d-flex gap-4">
            {["Home", "Office", "Other"].map((type) => (
              <div className="form-check d-flex align-items-center gap-1" key={type}>
                <input className="form-check-input border-secondary shadow-none m-0" type="radio" name="addressType" id={`type-${type}`} value={type} checked={formData.addressType === type} onChange={handleChange} style={{ width: '16px', height: '16px' }} />
                <label className="form-check-label text-dark" htmlFor={`type-${type}`} style={{fontSize: '15px'}}>{type}</label>
              </div>
            ))}
          </div>
        </div>

        {/* Order Notes */}
        <div className="mb-4">
          <div className="form-check mb-2 d-flex align-items-center gap-1">
            <input className="form-check-input border-secondary shadow-none m-0" type="checkbox" id="orderNotesCheck" checked={showOrderNotesInput} onChange={handleOrderNotesCheckChange} style={{ width: '16px', height: '16px' }} />
            <label className="form-check-label text-dark fw-semibold" htmlFor="orderNotesCheck" style={{ fontSize: '14px' }}>
              Order notes (optional)?
            </label>
          </div>
          {showOrderNotesInput && (
            <textarea className="form-control mt-2 shadow-none rounded-1 border-secondary" name="orderNotes" rows="2" placeholder="Enter notes here..." value={formData.orderNotes} onChange={handleChange}></textarea>
          )}
        </div>

        {/* Desktop Submit Button */}
        <div className="d-none d-md-block mt-4">
          <button type="submit" className="btn btn-theme text-white fw-bold px-4 py-2 rounded-1" style={{ backgroundColor: '#0b53a1', fontSize: '13px' }}>
            SAVE AND DELIVERY HERE
          </button>
        </div>

        {/* Mobile Submit Button (Fixed Bottom) */}
        <div className="d-md-none fixed-bottom bg-white border-top shadow-lg z-3">
          <button type="submit" className="btn btn-theme w-100 py-3 fw-bold text-uppercase text-white rounded-0" style={{ backgroundColor: '#0b53a1', fontSize: '15px' }}>
            SAVE AND DELIVERY HERE
          </button>
        </div>

      </form>
    </div>
  );
};

export default OrderAddress;
