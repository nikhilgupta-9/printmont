import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import toast from 'react-hot-toast';

const Profile = () => {
  const { user, setUser } = useAuth();

  // Helper to normalize gender case
  const formatGender = (g) => {
    if (!g) return "Male";
    return g.charAt(0).toUpperCase() + g.slice(1).toLowerCase();
  };

  // Initialize profile state with context or defaults
  const [profile, setProfile] = useState({
    firstName: user?.firstName || user?.first_name || "",
    lastName: user?.lastName || user?.last_name || "",
    gender: formatGender(user?.gender),
    email: user?.email || "",
    mobile: user?.mobile || user?.phone || "",
  });

  // Update profile state if user context loads later
  useEffect(() => {
    if (user) {
      const updatedProfile = {
        firstName: user.firstName || user.first_name || "",
        lastName: user.lastName || user.last_name || "",
        gender: formatGender(user.gender),
        email: user.email || "",
        mobile: user.mobile || user.phone || "",
      };
      setProfile(updatedProfile);
      setTempProfile(updatedProfile);
    }
  }, [user]);

  // Track if edit mode is on for names
  const [isEditing, setIsEditing] = useState(false);

  // Temporary state to hold edits before saving
  const [tempProfile, setTempProfile] = useState({ ...profile });

  // Handle input change
  const handleChange = (e) => {
    const { name, value } = e.target;
    setTempProfile(prev => ({
      ...prev,
      [name]: value
    }));
  };

  // When Edit clicked
  const handleEdit = (e) => {
    e.preventDefault();
    setIsEditing(true);
  };

  // When Save clicked
  const handleSave = (e) => {
    e.preventDefault();
    setProfile(tempProfile);
    setIsEditing(false);
    
    // Update global context and local storage
    if (setUser) {
      const updatedUser = { ...user, ...tempProfile };
      setUser(updatedUser);
      localStorage.setItem('user', JSON.stringify(updatedUser));
      toast.success("Profile updated successfully!");
    }
  };

  // When Cancel clicked (optional)
  const handleCancel = (e) => {
    e.preventDefault();
    setTempProfile(profile); // reset edits
    setIsEditing(false);
  };

  return (
    <div className="card p-4 border bd">
      <div className="d-flex justify-content-start gap-3 align-items-center mb-3">
        <h5 className="mb-0">Personal Information</h5>
        {!isEditing ? (
          <a href="#" className="text-primary text-decoration-none small" onClick={handleEdit}>Edit</a>
        ) : (
          <div>
            <button className="btn btn-sm btn-success me-2" onClick={handleSave}>Save</button>
            <button className="btn btn-sm btn-secondary" onClick={handleCancel}>Cancel</button>
          </div>
        )}
      </div>

      <div className="row">
        <div className="col-md-6 mb-3">
          <input
            type="text"
            name="firstName"
            className="form-control p-3"
            value={tempProfile.firstName}
            onChange={handleChange}
            readOnly={!isEditing}
            placeholder='Enter your first name'
          />
        </div>
        <div className="col-md-6 mb-3">
          <input
            type="text"
            name="lastName"
            className="form-control p-3"
            value={tempProfile.lastName}
            onChange={handleChange}
            readOnly={!isEditing}
            placeholder='Enter your last name'
          />
        </div>
      </div>

      {/* Gender */}
      <div className="mb-5">
        <label className="form-label d-block">Your Gender</label>
        <div className="form-check form-check-inline">
          <input
            className="form-check-input p-2"
            type="radio"
            name="gender"
            value="Male"
            checked={tempProfile.gender === "Male"}
            onChange={handleChange}
            disabled={!isEditing}
          />
          <label className="form-check-label">Male</label>
        </div>
        <div className="form-check form-check-inline">
          <input
            className="form-check-input p-2"
            type="radio"
            name="gender"
            value="Female"
            checked={tempProfile.gender === "Female"}
            onChange={handleChange}
            disabled={!isEditing}
          />
          <label className="form-check-label">Female</label>
        </div>
      </div>

      {/* Email */}
      <div className="mb-5">
        <div className="d-flex justify-content-start gap-3 align-items-center mb-3">
          <h6 className="">Email Address</h6>
          {!isEditing && (
            <a href="#" className="text-primary text-decoration-none small" onClick={handleEdit}>Edit</a>
          )}
        </div>
        <input
          type="email"
          name="email"
          className="form-control p-3"
          value={tempProfile.email}
          onChange={handleChange}
          readOnly={!isEditing}
        />
      </div>

      {/* Mobile */}
      <div className="mb-3">
        <div className="d-flex justify-content-start gap-3 align-items-center mb-3">
          <h6 className="mb-1">Mobile Number</h6>
          {!isEditing && (
            <a href="#" className="text-primary small text-decoration-none" onClick={handleEdit}>Edit</a>
          )}
        </div>
        <input
          type="text"
          name="mobile"
          className="form-control p-3"
          value={tempProfile.mobile}
          onChange={handleChange}
          readOnly={!isEditing}
        />
      </div>
    </div>
  );
};

export default Profile;
