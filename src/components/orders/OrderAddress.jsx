import React, { useState } from "react";
import "./orders.css";
import { useCheckout } from '../../context/CheckoutContext';
import { useAuth } from '../../context/AuthContext';
import { INDIAN_STATES } from '../../config/indianStates';

/**
 * Step 2 of checkout. Name and mobile used to live in a separate BuyerDetails
 * step; the design folds them into this one form, so this component fills both
 * buyerDetails and address in the checkout context.
 *
 * The design labels every control above the box and keeps street details in a
 * single Address textarea rather than the split house-no / road pair.
 */
const OrderAddress = ({ onAddressSaved }) => {
  const { address, setAddress, buyerDetails, setBuyerDetails, deliveryPincode } = useCheckout();
  const { user } = useAuth();

  // Addresses saved before the form was merged still carry the split fields.
  const seedAddressLine = address?.addressLine
    || [address?.houseNo, address?.roadArea].filter(Boolean).join(', ')
    || address?.addressArea
    || "";

  const [formData, setFormData] = useState({
    name: buyerDetails?.name || [user?.first_name, user?.last_name].filter(Boolean).join(' '),
    mobile: buyerDetails?.mobile || user?.phone || "",
    email: buyerDetails?.email || user?.email || "",
    company: address?.company || "",
    gstin: address?.gstin || "",
    addressLine: seedAddressLine,
    city: address?.city || "",
    landmark: address?.landmark || "",
    // Falls back to whatever was entered on the cart's pincode button.
    pincode: address?.pincode || deliveryPincode || "",
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
    if (!formData.addressLine.trim()) e.addressLine = "Address is required";
    if (!formData.city.trim()) e.city = "City is required";
    if (!/^\d{6}$/.test(formData.pincode.trim())) e.pincode = "Pincode must be 6 digits";
    if (!formData.state.trim()) e.state = "State is required";

    // Landmark is optional: the order API never reads it, and formatAddress
    // filters it out of the shipping line when blank.

    // Optional too, but it still has to look like a phone number if given.
    if (formData.altPhone.trim() && !/^\d{10}$/.test(formData.altPhone.trim())) {
      e.altPhone = "Enter a 10-digit alternate number";
    }

    // Required, because createOrder rejects a blank or malformed address.
    // Catching it here surfaces the problem on this step instead of letting
    // the shopper reach Payment and fail on the final submit.
    const email = formData.email.trim();
    if (!email) {
      e.email = "Email address is required";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
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
    // city, state and pincode, so keep those keys populated from the single
    // address field.
    const line = formData.addressLine.trim();
    const addressData = {
      ...formData,
      addressLine: line,
      addressArea: line,
      address: line,
      locality: line,
      phone: formData.mobile.trim(),
      type: formData.addressType,
    };

    setAddress(addressData);

    if (onAddressSaved) onAddressSaved(addressData);
  };

  const field = (name) => `form-control checkout-input${errors[name] ? ' is-invalid' : ''}`;
  const err = (name) => errors[name] && <div className="invalid-feedback d-block">{errors[name]}</div>;

  return (
    <div className="bg-white checkout-address-form px-3 py-3 p-md-4">
      <h2 className="checkout-form-heading">ADD BILLING AND DELIVERY ADDRESS</h2>

      <form onSubmit={handleSubmit} id="addressForm" noValidate>

        <div className="row g-3 mb-3">
          <div className="col-12 col-md-6">
            <label className="checkout-label" htmlFor="addr-name">Name <span className="req">*</span></label>
            <input
              id="addr-name" type="text" name="name" className={field('name')}
              placeholder="Your Good name"
              value={formData.name} onChange={handleChange}
            />
            {err('name')}
          </div>
          <div className="col-12 col-md-6">
            <label className="checkout-label" htmlFor="addr-mobile">Mobile number <span className="req">*</span></label>
            <input
              id="addr-mobile" type="tel" name="mobile" inputMode="numeric" maxLength={10} className={field('mobile')}
              placeholder="your contact number"
              value={formData.mobile} onChange={handleChange}
            />
            {err('mobile')}
          </div>
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
          <label className="checkout-label" htmlFor="addr-line">Address <span className="req">*</span></label>
          <textarea
            id="addr-line" name="addressLine" rows="3" className={field('addressLine')}
            placeholder="Address (Area and Street)"
            value={formData.addressLine} onChange={handleChange}
          />
          {err('addressLine')}
        </div>

        <div className="row g-3 mb-3">
          <div className="col-12 col-md-6">
            <label className="checkout-label" htmlFor="addr-city">City / District / Town <span className="req">*</span></label>
            <input id="addr-city" type="text" name="city" className={field('city')}
              value={formData.city} onChange={handleChange} />
            {err('city')}
          </div>
          <div className="col-12 col-md-6">
            <label className="checkout-label" htmlFor="addr-state">State <span className="req">*</span></label>
            <select id="addr-state" name="state"
              className={`form-select checkout-input${errors.state ? ' is-invalid' : ''}`}
              value={formData.state} onChange={handleChange}>
              <option value="">Select State</option>
              {INDIAN_STATES.map((s) => <option key={s} value={s}>{s}</option>)}
            </select>
            {err('state')}
          </div>
        </div>

        <div className="row g-3 mb-3">
          <div className="col-12 col-md-6">
            <label className="checkout-label" htmlFor="addr-pincode">Pincode <span className="req">*</span></label>
            <input id="addr-pincode" type="text" name="pincode" inputMode="numeric" maxLength={6} className={field('pincode')}
              value={formData.pincode} onChange={handleChange} />
            {err('pincode')}
          </div>
          <div className="col-12 col-md-6">
            <label className="checkout-label" htmlFor="addr-landmark">Landmark <span className="opt">(optional)</span></label>
            <input id="addr-landmark" type="text" name="landmark" className={field('landmark')}
              value={formData.landmark} onChange={handleChange} />
            {err('landmark')}
          </div>
        </div>

        <div className="row g-3 mb-3">
          <div className="col-12 col-md-6">
            <label className="checkout-label" htmlFor="addr-email">Email address <span className="req">*</span></label>
            <input id="addr-email" type="email" name="email" className={field('email')}
              placeholder="Order confirmation is sent here" value={formData.email} onChange={handleChange} />
            {err('email')}
          </div>
          <div className="col-12 col-md-6">
            <label className="checkout-label" htmlFor="addr-alt">Alternate Phone Number <span className="opt">(optional)</span></label>
            <input id="addr-alt" type="tel" name="altPhone" inputMode="numeric" maxLength={10} className={field('altPhone')}
              value={formData.altPhone} onChange={handleChange} />
            {err('altPhone')}
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
