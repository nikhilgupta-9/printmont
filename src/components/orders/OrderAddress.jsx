import React, { useState } from "react";
import "./orders.css";

const OrderAddress = ({ onAddressSaved }) => {
  const [formData, setFormData] = useState({
    name: "",
    mobile: "",
    company: "",
    address: "",
    city: "",
    state: "",
    pincode: "",
    landmark: "",
    email: "",
    altPhone: "",
    gstin: "",
    addressType: "Home",
    orderNotes: "",
  });

  const [errors, setErrors] = useState({});
  const [isFormVisible, setIsFormVisible] = useState(true);
  const [deliveryDetails, setDeliveryDetails] = useState(null);

  const [showCompanyInput, setShowCompanyInput] = useState(false);
  const [showOrderNotesInput, setShowOrderNotesInput] = useState(false);

  // ✅ Validation Logic
  const validateForm = () => {
    const newErrors = {};

    // ✅ Name required
    if (!formData.name.trim()) newErrors.name = "Name is required";

    // ✅ Mobile number (exactly 10 digits)
    if (!/^\d{10}$/.test(formData.mobile))
      newErrors.mobile = "Mobile number must be 10 digits";

    // ✅ Email format check
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email))
      newErrors.email = "Enter a valid email address";

    // ✅ Address only allows letters, numbers, spaces, commas, parentheses
    if (!/^[A-Za-z0-9\s,(),-]+$/.test(formData.address))
      newErrors.address =
        "Address can only include letters, numbers, commas, hyphen and parentheses";

    // ✅ City required
    if (!formData.city.trim()) newErrors.city = "City is required";

    // ✅ State required
    if (!formData.state.trim()) newErrors.state = "State is required";

    // ✅ Pincode exactly 6 digits
    if (!/^\d{6}$/.test(formData.pincode))
      newErrors.pincode = "Pincode must be 6 digits";

    // ✅ Landmark required
    if (!formData.landmark.trim()) newErrors.landmark = "Landmark is required";

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // ✅ Input change handler
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleCompanyCheckChange = (e) => {
    const checked = e.target.checked;
    setShowCompanyInput(checked);
    if (!checked) setFormData((prev) => ({ ...prev, company: "" }));
  };

  const handleOrderNotesCheckChange = (e) => {
    const checked = e.target.checked;
    setShowOrderNotesInput(checked);
    if (!checked) setFormData((prev) => ({ ...prev, orderNotes: "" }));
  };

  // ✅ Form Submit
  const handleSubmit = (e) => {
    e.preventDefault();
    if (!validateForm()) return; // stop if invalid

    setDeliveryDetails(formData);
    setIsFormVisible(false);
    if (onAddressSaved) onAddressSaved(formData);
  };

  const formatAddress = (data) => (
    <>
      <div className="fw-normal">
        {data.name} ({data.addressType})
      </div>
      {data.company && <div className="text-muted">Company: {data.company}</div>}
      <div className="text-muted">
        {data.address}, {data.city}, {data.state}, {data.pincode}
      </div>
      {data.gstin && <div className="text-muted">GSTIN: {data.gstin}</div>}
    </>
  );

  return (
    <div className="row justify-content-center">
      <div className="col-12">
        {/* --- DELIVERY DETAILS (AFTER SAVING) --- */}
        {!isFormVisible && deliveryDetails && (
          <div className="card shadow-sm border-0 rounded-bottom p-4 mb-4">
            <div className="d-flex justify-content-between align-items-start">
              <div>
                <h6 className="card-title fw-bold mb-2">DELIVERY ADDRESS</h6>
                {formatAddress(deliveryDetails)}
                <div className="mt-2">
                  <small>Mobile: {deliveryDetails.mobile}</small>
                </div>
              </div>
              <button
                className="btn btn-outline-secondary btn-sm"
                onClick={() => setIsFormVisible(true)}
              >
                CHANGE
              </button>
            </div>
          </div>
        )}

        {/* --- ADDRESS FORM --- */}
        {isFormVisible && (
          <div className="card shadow-sm border-0 rounded-bottom p-4">
            <h4 className="card-title fw-bold mb-4">
              ADD BILLING AND DELIVERY ADDRESS
            </h4>

            <form onSubmit={handleSubmit} id="addressForm">
              {/* --- NAME & MOBILE --- */}
              <div className="row mb-3">
                <div className="col-md-6 mb-3 mb-md-0">
                  <label className="form-label">Name *</label>
                  <input
                    type="text"
                    className="form-control"
                    name="name"
                    placeholder="Your Good Name"
                    value={formData.name}
                    onChange={handleChange}
                    required
                  />
                  {errors.name && (
                    <small className="text-danger">{errors.name}</small>
                  )}
                </div>
                <div className="col-md-6">
                  <label className="form-label">Mobile number *</label>
                  <input
                    type="tel"
                    className="form-control"
                    name="mobile"
                    placeholder="10-digit Mobile Number"
                    value={formData.mobile}
                    onChange={handleChange}
                    required
                  />
                  {errors.mobile && (
                    <small className="text-danger">{errors.mobile}</small>
                  )}
                </div>
              </div>

              {/* --- COMPANY DETAILS --- */}
              <div className="mb-3 row">
                <div className="col-12 pb-1 d-flex align-items-center gap-3">
                  <input
                    className="form-check-input shadow-sm border-2"
                    type="checkbox"
                    id="companyCheck"
                    checked={showCompanyInput}
                    onChange={handleCompanyCheckChange}
                  />
                  <label className="form-label red" htmlFor="companyCheck">
                    Company Details (optional)
                  </label>
                </div>
                {showCompanyInput && (
                  <>
                    <div className="col-md-6 mb-3 mb-md-0">
                      <label className="form-label">
                        Enter Your Company Name
                      </label>
                      <input
                        type="text"
                        className="form-control mt-2"
                        name="company"
                        placeholder="Enter Company Name"
                        value={formData.company}
                        onChange={handleChange}
                      />
                    </div>
                    <div className="col-6">
                      <label className="form-check-label">GST Number</label>
                      <input
                        type="text"
                        className="form-control mt-2"
                        name="gstin"
                        placeholder="Enter GSTIN"
                        value={formData.gstin}
                        onChange={handleChange}
                      />
                    </div>
                  </>
                )}
              </div>

              {/* --- ADDRESS --- */}
              <div className="mb-3">
                <label className="form-label">Address *</label>
                <textarea
                  className="form-control"
                  rows="3"
                  name="address"
                  placeholder="Address (Area and Street)"
                  value={formData.address}
                  onChange={handleChange}
                  required
                ></textarea>
                {errors.address && (
                  <small className="text-danger">{errors.address}</small>
                )}
              </div>

              {/* --- CITY / STATE --- */}
              <div className="row mb-3">
                <div className="col-md-6 mb-3 mb-md-0">
                  <label className="form-label">City *</label>
                  <input
                    type="text"
                    className="form-control"
                    placeholder="Enter Your City"
                    name="city"
                    value={formData.city}
                    onChange={handleChange}
                    required
                  />
                  {errors.city && (
                    <small className="text-danger">{errors.city}</small>
                  )}
                </div>
                <div className="col-md-6">
                  <label className="form-label">State *</label>
                  <select
                    className="form-select"
                    name="state"
                    value={formData.state}
                    onChange={handleChange}
                    required
                  >
                    <option value="">Select State</option>
                    <option value="Delhi">Delhi</option>
                    <option value="Maharashtra">Maharashtra</option>
                    <option value="Karnataka">Karnataka</option>
                  </select>
                  {errors.state && (
                    <small className="text-danger">{errors.state}</small>
                  )}
                </div>
              </div>

              {/* --- PINCODE / LANDMARK --- */}
              <div className="row mb-3">
                <div className="col-md-6 mb-3 mb-md-0">
                  <label className="form-label">Pincode *</label>
                  <input
                    type="text"
                    placeholder="6-digit Pincode"
                    className="form-control"
                    name="pincode"
                    value={formData.pincode}
                    onChange={handleChange}
                    required
                  />
                  {errors.pincode && (
                    <small className="text-danger">{errors.pincode}</small>
                  )}
                </div>
                <div className="col-md-6">
                  <label className="form-label">Landmark *</label>
                  <input
                    type="text"
                    className="form-control"
                    name="landmark"
                    value={formData.landmark}
                    onChange={handleChange}
                    required
                  />
                  {errors.landmark && (
                    <small className="text-danger">{errors.landmark}</small>
                  )}
                </div>
              </div>

              {/* --- EMAIL / ALT PHONE --- */}
              <div className="row mb-4">
                <div className="col-md-6 mb-3 mb-md-0">
                  <label className="form-label">Email *</label>
                  <input
                    type="email"
                    placeholder="Enter Email"
                    className="form-control"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    required
                  />
                  {errors.email && (
                    <small className="text-danger">{errors.email}</small>
                  )}
                </div>
                <div className="col-md-6">
                  <label className="form-label">
                    Alternate Phone (optional)
                  </label>
                  <input
                    type="tel"
                    className="form-control"
                    name="altPhone"
                    value={formData.altPhone}
                    onChange={handleChange}
                  />
                </div>
              </div>

              {/* --- ADDRESS TYPE --- */}
              <div className="mb-4">
                <h6 className="form-label fw-bold">Address Type</h6>
                <div className="d-flex gap-4">
                  {["Home", "Office", "Other"].map((type) => (
                    <div className="form-check" key={type}>
                      <input
                        className="form-check-input"
                        type="radio"
                        name="addressType"
                        value={type}
                        checked={formData.addressType === type}
                        onChange={handleChange}
                      />
                      <label className="form-check-label">{type}</label>
                    </div>
                  ))}
                </div>
              </div>

              {/* --- ORDER NOTES --- */}
              <div className="mb-4">
                <div className="form-check">
                  <input
                    className="form-check-input border-2 shadow-sm"
                    type="checkbox"
                    id="orderNotesCheck"
                    checked={showOrderNotesInput}
                    onChange={handleOrderNotesCheckChange}
                  />
                  <label className="form-check-label" htmlFor="orderNotesCheck">
                    Order notes (optional)
                  </label>
                </div>
                {showOrderNotesInput && (
                  <textarea
                    className="form-control mt-2"
                    name="orderNotes"
                    rows="2"
                    placeholder="Enter notes here..."
                    value={formData.orderNotes}
                    onChange={handleChange}
                  ></textarea>
                )}
              </div>

              {/* --- SUBMIT BUTTON --- */}
              <button
                type="submit"
                className="border-0 rounded bg-theme fw-bold px-4 py-2 d-none d-md-inline-block text-white"
              >
                SAVE AND DELIVER HERE
              </button>

              {/* Mobile Button */}
              <div className="fixed-bottom-bar d-flex d-md-none">
                <button
                  type="submit"
                  className="btn border-0 rounded bg-theme fw-bold px-4 py-2 w-100 text-white"
                >
                  SAVE AND DELIVER HERE
                </button>
              </div>
            </form>
          </div>
        )}
      </div>
    </div>
  );
};

export default OrderAddress;
