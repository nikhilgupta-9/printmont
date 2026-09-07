import React, { useState, useEffect } from "react";
import "./orders.css";
import { useCheckout } from '../../context/CheckoutContext';
import { useAuth } from '../../context/AuthContext';

const BuyerDetails = ({ onContinue }) => {
  const { user } = useAuth();
  const { buyerDetails, setBuyerDetails } = useCheckout();

  const [formData, setFormData] = useState({
    name: buyerDetails?.name || user?.name || (user?.first_name ? `${user.first_name} ${user.last_name || ''}`.trim() : ""),
    mobile: buyerDetails?.mobile || user?.phone || user?.mobile || user?.contact || "",
    email: buyerDetails?.email || user?.email || "",
  });

  useEffect(() => {
    if (user && !buyerDetails?.name) {
      setFormData(prev => ({
        name: prev.name || user.name || (user.first_name ? `${user.first_name} ${user.last_name || ''}`.trim() : ""),
        mobile: prev.mobile || user.phone || user.mobile || user.contact || "",
        email: prev.email || user.email || ""
      }));
    }
  }, [user]);

  const [errors, setErrors] = useState({});

  const validateForm = () => {
    const newErrors = {};
    if (!formData.name.trim()) newErrors.name = "Name is required";
    if (!/^\d{10}$/.test(formData.mobile)) newErrors.mobile = "Mobile number must be 10 digits";
    if (formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) newErrors.email = "Enter a valid email address";

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!validateForm()) return; 

    setBuyerDetails(formData);

    if (onContinue) onContinue();
  };

  return (
    <div className="bg-white p-4">
      <h6 className="fw-bold text-dark mb-4" style={{ fontSize: '15px' }}>BUYER DETAILS</h6>
      
      <form onSubmit={handleSubmit}>
        <div className="row g-3 mb-4">
          <div className="col-12 col-md-6">
            <label className="form-label text-dark mb-1" style={{ fontSize: '14px' }}>Name *</label>
            <input type="text" className="form-control shadow-none rounded-1 border-secondary" name="name" placeholder="Your Good name" value={formData.name} onChange={handleChange} required />
            {errors.name && <small className="text-danger">{errors.name}</small>}
          </div>
          <div className="col-12 col-md-6">
            <label className="form-label text-dark mb-1" style={{ fontSize: '14px' }}>Mobile number *</label>
            <input type="tel" className="form-control shadow-none rounded-1 border-secondary" name="mobile" placeholder="your contact number" value={formData.mobile} onChange={handleChange} required />
            {errors.mobile && <small className="text-danger">{errors.mobile}</small>}
          </div>
        </div>

        <div className="mb-4">
          <label className="form-label text-dark mb-1" style={{ fontSize: '14px' }}>Email address (optional)</label>
          <input type="email" placeholder="Email address optional" className="form-control shadow-none rounded-1 border-secondary" name="email" value={formData.email} onChange={handleChange} />
          {errors.email && <small className="text-danger">{errors.email}</small>}
        </div>

        {/* Desktop Submit Button */}
        <div className="d-none d-md-block mt-4">
          <button type="submit" className="btn btn-theme text-white fw-bold px-4 py-2 rounded-1" style={{ backgroundColor: '#0b53a1', fontSize: '13px' }}>
            CONTINUE TO ADDRESS
          </button>
        </div>

        {/* Mobile Submit Button (Fixed Bottom) */}
        <div className="d-md-none fixed-bottom bg-white border-top shadow-lg z-3">
          <button type="submit" className="btn btn-theme w-100 py-3 fw-bold text-uppercase text-white rounded-0" style={{ backgroundColor: '#0b53a1', fontSize: '15px' }}>
            CONTINUE TO ADDRESS
          </button>
        </div>

      </form>
    </div>
  );
};

export default BuyerDetails;
