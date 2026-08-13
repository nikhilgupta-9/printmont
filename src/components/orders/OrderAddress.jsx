import React, { useState } from "react";
import "./orders.css";
import { useCheckout } from '../../context/CheckoutContext';
import { useAuth } from '../../context/AuthContext';

const STATES = [
  "Andhra Pradesh", "Assam", "Bihar", "Chhattisgarh", "Delhi", "Goa", "Gujarat",
  "Haryana", "Himachal Pradesh", "Jharkhand", "Karnataka", "Kerala", "Madhya Pradesh",
  "Maharashtra", "Odisha", "Punjab", "Rajasthan", "Tamil Nadu", "Telangana",
  "Uttar Pradesh", "Uttarakhand", "West Bengal",
];

/**
 * Step 1 of checkout. Name and mobile used to live in a separate BuyerDetails
 * step; the design folds them into this one form, so this component now fills
 * both buyerDetails and address in the checkout context.
 *
 * Labels sit inside the controls as placeholders, per the design.
 */
const OrderAddress = ({ onAddressSaved }) => {
  const { address, setAddress, buyerDetails, setBuyerDetails } = useCheckout();
  const { user } = useAuth();

  const [formData, setFormData] = useState({
    name: buyerDetails?.name || [user?.first_name, user?.last_name].filter(Boolean).join(' '),
    mobile: buyerDetails?.mobile || user?.phone || "",
    email: buyerDetails?.email || user?.email || "",
    company: address?.company || "",
    gstin: address?.gstin || "",
    houseNo: address?.houseNo || "",
    roadArea: address?.roadArea || address?.addressArea || "",
    city: address?.city || "",
    landmark: address?.landmark || "",
    pincode: address?.pincode || "",
    state: address?.state || "",
    altPhone: address?.altPhone || "",
    addressType: address?.addressType || "Home",
    orderNotes: address?.orderNotes || "",
  });

  const [errors, setErrors] = useState({});
  const [showCompanyInput, setShowCompanyInput] = useState(!!address?.company);
  const [showOrderNotesInput, setShowOrderNotesInput] = useState(!!address?.orderNotes);

  const validateForm = () => {
    const e = {};

    if (!formData.name.trim()) e.name = "Full name is required";
    if (!/^\d{10}$/.test(formData.mobile.trim())) e.mobile = "Enter a 10-digit mobile number";
    if (!formData.houseNo.trim()) e.houseNo = "House no. / building name is required";
    if (!formData.roadArea.trim()) e.roadArea = "Road name, area, colony is required";
    if (!formData.city.trim()) e.city = "City is required";
    if (!formData.landmark.trim()) e.landmark = "Landmark is required";
    if (!/^\d{6}$/.test(formData.pincode.trim())) e.pincode = "Pincode must be 6 digits";
    if (!formData.state.trim()) e.state = "State is required";
    if (!/^\d{10}$/.test(formData.altPhone.trim())) e.altPhone = "Enter a 10-digit alternate number";
    if (formData.email.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email.trim())) {
      e.email = "Enter a valid email address";
    }

    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
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

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!validateForm()) return;

    setBuyerDetails({
      name: formData.name.trim(),
      mobile: formData.mobile.trim(),
      email: formData.email.trim(),
    });

    // The order API composes a shipping line from address/addressArea, landmark,
    // city, state and pincode, so keep those keys populated alongside the new
    // split house-number / road fields.
    const addressData = {
      ...formData,
      addressArea: `${formData.houseNo.trim()}, ${formData.roadArea.trim()}`,
      address: `${formData.houseNo.trim()}, ${formData.roadArea.trim()}`,
      locality: formData.roadArea.trim(),
      phone: formData.mobile.trim(),
      type: formData.addressType,
    };

    setAddress(addressData);

    if (onAddressSaved) onAddressSaved(addressData);
  };

  const field = (name) => `form-control checkout-input${errors[name] ? ' is-invalid' : ''}`;

  return (
    <div className="bg-white checkout-address-form px-3 py-3 p-md-4">
      <h2 className="checkout-form-heading">ADD BILLING AND DELIVERY ADDRESS</h2>

      <form onSubmit={handleSubmit} id="addressForm" noValidate>

        <div className="mb-3">
          <input
            type="text" name="name" className={field('name')}
            placeholder="Full Name (Required) *"
            value={formData.name} onChange={handleChange}
          />
          {errors.name && <div className="invalid-feedback d-block">{errors.name}</div>}
        </div>

        <div className="mb-3">
          <input
            type="tel" name="mobile" inputMode="numeric" maxLength={10} className={field('mobile')}
            placeholder="Mobile number (Required) *"
            value={formData.mobile} onChange={handleChange}
          />
          {errors.mobile && <div className="invalid-feedback d-block">{errors.mobile}</div>}
        </div>

        <div className="mb-3">
          <div className="form-check checkout-check">
            <input
              className="form-check-input shadow-none" type="checkbox" id="companyCheck"
              checked={showCompanyInput} onChange={handleCompanyCheckChange}
            />
            <label className="form-check-label" htmlFor="companyCheck">Company name (optional)</label>
          </div>
          {showCompanyInput && (
            <div className="row g-3 mt-1">
              <div className="col-12 col-md-6">
                <input type="text" className="form-control checkout-input" name="company"
                  placeholder="Company name" value={formData.company} onChange={handleChange} />
              </div>
              <div className="col-12 col-md-6">
                <input type="text" className="form-control checkout-input" name="gstin"
                  placeholder="GSTIN" value={formData.gstin} onChange={handleChange} />
              </div>
            </div>
          )}
        </div>

        <div className="mb-3">
          <input
            type="text" name="houseNo" className={field('houseNo')}
            placeholder="House No., Building name (Required) *"
            value={formData.houseNo} onChange={handleChange}
          />
          {errors.houseNo && <div className="invalid-feedback d-block">{errors.houseNo}</div>}
        </div>

        <div className="mb-3">
          <input
            type="text" name="roadArea" className={field('roadArea')}
            placeholder="Road name, Area, Colony (Required) *"
            value={formData.roadArea} onChange={handleChange}
          />
          {errors.roadArea && <div className="invalid-feedback d-block">{errors.roadArea}</div>}
        </div>

        <div className="row g-3 mb-3">
          <div className="col-6">
            <input type="text" name="city" className={field('city')}
              placeholder="City / District / Town *" value={formData.city} onChange={handleChange} />
            {errors.city && <div className="invalid-feedback d-block">{errors.city}</div>}
          </div>
          <div className="col-6">
            <input type="text" name="landmark" className={field('landmark')}
              placeholder="Landmark *" value={formData.landmark} onChange={handleChange} />
            {errors.landmark && <div className="invalid-feedback d-block">{errors.landmark}</div>}
          </div>
        </div>

        <div className="row g-3 mb-3">
          <div className="col-6">
            <input type="text" name="pincode" inputMode="numeric" maxLength={6} className={field('pincode')}
              placeholder="Pincode *" value={formData.pincode} onChange={handleChange} />
            {errors.pincode && <div className="invalid-feedback d-block">{errors.pincode}</div>}
          </div>
          <div className="col-6">
            <select name="state" className={`form-select checkout-input${errors.state ? ' is-invalid' : ''}`}
              value={formData.state} onChange={handleChange}>
              <option value="">State *</option>
              {STATES.map((s) => <option key={s} value={s}>{s}</option>)}
            </select>
            {errors.state && <div className="invalid-feedback d-block">{errors.state}</div>}
          </div>
        </div>

        <div className="row g-3 mb-3">
          <div className="col-6">
            <input type="email" name="email" className={field('email')}
              placeholder="Email address optional *" value={formData.email} onChange={handleChange} />
            {errors.email && <div className="invalid-feedback d-block">{errors.email}</div>}
          </div>
          <div className="col-6">
            <input type="tel" name="altPhone" inputMode="numeric" maxLength={10} className={field('altPhone')}
              placeholder="Alternate Number Required *" value={formData.altPhone} onChange={handleChange} />
            {errors.altPhone && <div className="invalid-feedback d-block">{errors.altPhone}</div>}
          </div>
        </div>

        <div className="mb-3 mt-4">
          <p className="checkout-group-label">Address Type</p>
          <div className="d-flex gap-4">
            {["Home", "Office", "Other"].map((type) => (
              <div className="form-check checkout-check" key={type}>
                <input
                  className="form-check-input shadow-none" type="radio" name="addressType"
                  id={`type-${type}`} value={type}
                  checked={formData.addressType === type} onChange={handleChange}
                />
                <label className="form-check-label" htmlFor={`type-${type}`}>{type}</label>
              </div>
            ))}
          </div>
        </div>

        <div className="mb-3">
          <div className="form-check checkout-check">
            <input
              className="form-check-input shadow-none" type="checkbox" id="orderNotesCheck"
              checked={showOrderNotesInput} onChange={handleOrderNotesCheckChange}
            />
            <label className="form-check-label" htmlFor="orderNotesCheck">Order notes (optional)</label>
          </div>
          {showOrderNotesInput && (
            <textarea className="form-control checkout-input mt-2" name="orderNotes" rows="2"
              placeholder="Enter notes here..." value={formData.orderNotes} onChange={handleChange} />
          )}
        </div>

        {/* Desktop keeps the action inline; mobile pins it to the bottom bar. */}
        <div className="d-none d-md-block mt-4">
          <button type="submit" className="btn checkout-cta">SAVE AND DELIVERY HERE</button>
        </div>

        <div className="d-md-none checkout-bottom-bar">
          <button type="submit" className="btn checkout-cta w-100">SAVE AND DELIVERY HERE</button>
        </div>

      </form>
    </div>
  );
};

export default OrderAddress;
