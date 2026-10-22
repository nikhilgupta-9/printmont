import React, { useState, useEffect } from 'react';
import { Form, Button, Row, Col, Badge, Modal, Spinner, Card } from 'react-bootstrap';
import { 
  FaCheckCircle, 
  FaUser, 
  FaEnvelope, 
  FaPhoneAlt, 
  FaExclamationTriangle, 
  FaIdCard, 
  FaQuestionCircle, 
  FaArrowLeft,
  FaLock,
  FaShieldAlt,
  FaMars,
  FaVenus,
  FaEdit,
  FaCheck,
  FaCoins
} from 'react-icons/fa';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { API_ENDPOINTS, ASSET_URL } from '../../config/apiEndpoints';
import MyAccount from '../myAccount/MyAccount';
import toast from 'react-hot-toast';
import './Profile.css';

const Profile = ({ forceEditForm = false }) => {
  const { user, token, setUser, logout, getUsernamePath } = useAuth();
  const usernamePath = getUsernamePath();
  const navigate = useNavigate();
  const location = useLocation();

  const isEditRoute = location.pathname.includes('profile-edit') || forceEditForm;

  const [saving, setSaving] = useState(false);
  const [showDeactivateModal, setShowDeactivateModal] = useState(false);
  const [deactivating, setDeactivating] = useState(false);
  const [isMobile, setIsMobile] = useState(window.innerWidth < 992);
  const [showMobileEditForm, setShowMobileEditForm] = useState(isEditRoute);

  // Sync state if route changes
  useEffect(() => {
    setShowMobileEditForm(isEditRoute);
  }, [isEditRoute]);

  // Monitor window resize (aligned with Bootstrap 992px lg breakpoint)
  useEffect(() => {
    const handleResize = () => {
      setIsMobile(window.innerWidth < 992);
    };
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  // Helper to normalize gender case
  const formatGender = (g) => {
    if (!g) return "Male";
    return g.charAt(0).toUpperCase() + g.slice(1).toLowerCase();
  };

  // Profile fields state
  const [profile, setProfile] = useState({
    firstName: user?.firstName || user?.first_name || "",
    lastName: user?.lastName || user?.last_name || "",
    gender: formatGender(user?.gender),
    email: user?.email || "",
    mobile: user?.mobile || user?.phone || "",
    pan_number: user?.pan_number || "",
    pan_name: user?.pan_name || "",
    gst_number: user?.gst_number || user?.gstin || "",
    company_name: user?.company_name || user?.business_name || "",
  });

  const [activeEdit, setActiveEdit] = useState(null);
  const [tempProfile, setTempProfile] = useState({ ...profile });

  useEffect(() => {
    if (user) {
      const updated = {
        firstName: user.firstName || user.first_name || "",
        lastName: user.lastName || user.last_name || "",
        gender: formatGender(user.gender),
        email: user.email || "",
        mobile: user.mobile || user.phone || "",
        pan_number: user.pan_number || "",
        pan_name: user.pan_name || "",
        gst_number: user.gst_number || user.gstin || "",
        company_name: user.company_name || user.business_name || "",
      };
      setProfile(updated);
      setTempProfile(updated);
    }
  }, [user]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setTempProfile(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleGenderSelect = (genderVal) => {
    setTempProfile(prev => ({
      ...prev,
      gender: genderVal
    }));
  };

  const handleSave = async (e) => {
    if (e && e.preventDefault) e.preventDefault();

    if (!tempProfile.firstName?.trim()) {
      toast.error("First name is required");
      return;
    }
    const cleanMobile = (tempProfile.mobile || "").replace(/\D/g, '');
    if (cleanMobile.length < 10) {
      toast.error("Please enter a valid 10-digit mobile number");
      return;
    }

    setSaving(true);
    try {
      const activeToken = token || localStorage.getItem('token') || '';
      const headers = { 'Content-Type': 'application/json' };
      if (activeToken) headers['Authorization'] = `Bearer ${activeToken}`;

      const payload = {
        firstName: tempProfile.firstName.trim(),
        lastName: tempProfile.lastName.trim(),
        mobile: cleanMobile.slice(-10),
        gender: tempProfile.gender || 'Male',
        company_name: tempProfile.company_name.trim(),
        gst_number: tempProfile.gst_number.trim(),
        pan_number: tempProfile.pan_number.trim(),
        pan_name: tempProfile.pan_name.trim(),
        address: user?.address || '',
      };

      const res = await fetch(API_ENDPOINTS.UPDATE_PROFILE, {
        method: 'POST',
        headers,
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (data.success) {
        setProfile(tempProfile);
        setActiveEdit(null);
        const updatedUser = { ...user, ...data.user, ...payload };
        if (setUser) setUser(updatedUser);
        localStorage.setItem('user', JSON.stringify(updatedUser));
        toast.success("Profile updated successfully!");

        if (isMobile) {
          navigate(`/${usernamePath}/profile`);
        }
      } else {
        toast.error(data.error || data.message || "Failed to update profile");
      }
    } catch (err) {
      console.error("Profile update error:", err);
      toast.error("Network error. Could not update profile.");
    } finally {
      setSaving(false);
    }
  };

  const handleBackToHub = () => {
    navigate(`/${usernamePath}/profile`);
  };

  const handleCancel = () => {
    setTempProfile({ ...profile });
    setActiveEdit(null);
    if (isMobile) {
      navigate(`/${usernamePath}/profile`);
    }
  };

  const handleDeactivate = async () => {
    setDeactivating(true);
    try {
      const activeToken = token || localStorage.getItem('token') || '';
      const headers = { 'Content-Type': 'application/json' };
      if (activeToken) headers['Authorization'] = `Bearer ${activeToken}`;

      const res = await fetch(`${API_ENDPOINTS.AUTH_SETTINGS}?action=soft_delete_account`, {
        method: 'POST',
        headers
      });
      const data = await res.json();
      if (data.success) {
        toast.success("Account deactivated successfully.");
        setShowDeactivateModal(false);
        if (logout) logout();
      } else {
        toast.error(data.error || "Failed to deactivate account.");
      }
    } catch (err) {
      toast.error("Network error occurred.");
    } finally {
      setDeactivating(false);
    }
  };

  const isVerified = Boolean(user?.is_verified == 1 || user?.is_verified === true || user?.status === 'active');

  const displayName = user
    ? (user.firstName || user.first_name
        ? `${user.firstName || user.first_name} ${user.lastName || user.last_name || ''}`.trim()
        : user.name || (user.email ? user.email.split('@')[0] : 'User'))
    : 'User Profile';

  const storedAvatar = user?.profile_picture || user?.profilePicture || user?.avatar || '';
  const avatarUrl = storedAvatar
    ? (storedAvatar.startsWith('http') ? storedAvatar : `${ASSET_URL || ''}${storedAvatar.replace(/^\//, '')}`)
    : '';
  const [avatarError, setAvatarError] = useState(false);
  const showAvatarImage = Boolean(avatarUrl) && !avatarError;

  // User initials for avatar
  const getUserInitials = () => {
    if (!user) return "PM";
    const first = (user.firstName || user.first_name || user.name || "U").trim();
    const last = (user.lastName || user.last_name || "").trim();
    if (first && last) {
      return (first[0] + last[0]).toUpperCase();
    }
    return first.slice(0, 2).toUpperCase();
  };

  // If on mobile screen and not in edit mode -> render the exact MyAccount Hub
  if (isMobile && !showMobileEditForm && !isEditRoute) {
    return <MyAccount onEditProfile={() => navigate(`/${usernamePath}/profile-edit`)} />;
  }

  // =========================================================================
  // 📱 MOBILE FLIPKART-GRADE EDIT PERSONAL INFORMATION VIEW
  // =========================================================================
  if (isMobile) {
    return (
      <div className="bg-light min-vh-100 pb-5" style={{ maxWidth: '520px', margin: '0 auto' }}>
        {/* Flipkart Mobile Top Header Bar with Back Navigation */}
        <div className="d-flex align-items-center justify-content-between px-3 py-3 bg-white border-bottom shadow-sm sticky-top" style={{ zIndex: 10 }}>
          <div className="d-flex align-items-center gap-3">
            <button 
              type="button" 
              onClick={handleBackToHub} 
              className="btn btn-link p-0 text-dark border-0 d-flex align-items-center"
              aria-label="Back to Account"
            >
              <FaArrowLeft size={18} />
            </button>
            <h6 className="mb-0 fw-bold text-dark fs-6">Personal Information</h6>
          </div>
          <button 
            type="button" 
            onClick={handleSave} 
            disabled={saving}
            className="btn btn-sm btn-link text-primary fw-bold text-decoration-none p-0"
          >
            {saving ? <Spinner size="sm" animation="border" /> : 'SAVE'}
          </button>
        </div>

        {/* User Avatar Summary Card */}
        <div className="p-3 bg-white mb-2 border-bottom d-flex align-items-center gap-3">
          <div 
            className="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm flex-shrink-0"
            style={{ width: '54px', height: '54px', background: 'linear-gradient(135deg, #0b53a1 0%, #008ad3 100%)', fontSize: '1.2rem' }}
          >
            {getUserInitials()}
          </div>
          <div className="min-width-0 flex-grow-1">
            <div className="fw-bold text-dark text-truncate fs-6">
              {tempProfile.firstName || tempProfile.lastName ? `${tempProfile.firstName} ${tempProfile.lastName}`.trim() : 'Printmont Customer'}
            </div>
            <div className="text-muted small text-truncate">
              {tempProfile.email || tempProfile.mobile || 'Account Profile'}
            </div>
          </div>
          {isVerified && (
            <Badge bg="success-subtle" className="text-success border border-success-subtle rounded-pill px-2 py-1 small d-flex align-items-center gap-1">
              <FaCheckCircle size={10} /> Verified
            </Badge>
          )}
        </div>

        <Form onSubmit={handleSave} className="p-3">
          {/* Card 1: Name & Gender */}
          <Card className="border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
            <h6 className="fw-bold text-dark mb-3 small d-flex align-items-center gap-2">
              <FaUser className="text-primary" /> BASIC DETAILS
            </h6>

            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold text-secondary mb-1">First Name *</Form.Label>
              <Form.Control
                type="text"
                name="firstName"
                value={tempProfile.firstName}
                onChange={handleChange}
                placeholder="Enter First Name"
                className="p-2.5 rounded-3 border bg-white"
                required
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label className="small fw-semibold text-secondary mb-1">Last Name</Form.Label>
              <Form.Control
                type="text"
                name="lastName"
                value={tempProfile.lastName}
                onChange={handleChange}
                placeholder="Enter Last Name"
                className="p-2.5 rounded-3 border bg-white"
              />
            </Form.Group>

            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary d-block mb-2">Gender</Form.Label>
              <div className="d-flex gap-2">
                <Button
                  type="button"
                  variant={tempProfile.gender === 'Male' ? 'primary' : 'outline-secondary'}
                  className="d-flex align-items-center justify-content-center gap-2 flex-grow-1 py-2 rounded-3 fw-semibold border"
                  style={tempProfile.gender === 'Male' ? { backgroundColor: '#0b53a1', borderColor: '#0b53a1' } : {}}
                  onClick={() => handleGenderSelect('Male')}
                >
                  <FaMars size={14} /> Male
                </Button>
                <Button
                  type="button"
                  variant={tempProfile.gender === 'Female' ? 'primary' : 'outline-secondary'}
                  className="d-flex align-items-center justify-content-center gap-2 flex-grow-1 py-2 rounded-3 fw-semibold border"
                  style={tempProfile.gender === 'Female' ? { backgroundColor: '#0b53a1', borderColor: '#0b53a1' } : {}}
                  onClick={() => handleGenderSelect('Female')}
                >
                  <FaVenus size={14} /> Female
                </Button>
              </div>
            </Form.Group>
          </Card>

          {/* Card 2: Contact Information */}
          <Card className="border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
            <h6 className="fw-bold text-dark mb-3 small d-flex align-items-center gap-2">
              <FaPhoneAlt className="text-primary" /> CONTACT DETAILS
            </h6>

            <Form.Group className="mb-3">
              <div className="d-flex justify-content-between align-items-center mb-1">
                <Form.Label className="small fw-semibold text-secondary mb-0">Mobile Number *</Form.Label>
                {isVerified && <span className="text-success small fw-semibold"><FaCheckCircle size={11} /> Verified</span>}
              </div>
              <div className="input-group">
                <span className="input-group-text bg-light border-end-0 text-muted small fw-bold">+91</span>
                <Form.Control
                  type="tel"
                  name="mobile"
                  maxLength={10}
                  value={tempProfile.mobile}
                  onChange={handleChange}
                  placeholder="10-digit mobile number"
                  className="p-2.5 rounded-end-3 border border-start-0 bg-white"
                  required
                />
              </div>
            </Form.Group>

            <Form.Group>
              <div className="d-flex justify-content-between align-items-center mb-1">
                <Form.Label className="small fw-semibold text-secondary mb-0">Email Address</Form.Label>
                {isVerified && <span className="text-success small fw-semibold"><FaCheckCircle size={11} /> Verified</span>}
              </div>
              <Form.Control
                type="email"
                name="email"
                value={tempProfile.email}
                readOnly
                className="p-2.5 rounded-3 border bg-light text-muted"
              />
              <Form.Text className="text-muted" style={{ fontSize: '0.72rem' }}>
                Primary login email is verified and protected.
              </Form.Text>
            </Form.Group>
          </Card>

          {/* Card 3: Business Invoicing (PAN & GSTIN) */}
          <Card className="border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
            <div className="d-flex justify-content-between align-items-center mb-3">
              <h6 className="fw-bold text-dark mb-0 small d-flex align-items-center gap-2">
                <FaIdCard className="text-primary" /> BUSINESS & TAX DETAILS
              </h6>
              <Link to={`/${usernamePath}/pan-gst`} className="text-primary text-decoration-none small fw-semibold">
                Manage
              </Link>
            </div>

            <Row className="g-2 mb-2">
              <Col xs={6}>
                <Form.Group>
                  <Form.Label className="small text-secondary mb-1" style={{ fontSize: '0.75rem' }}>PAN Number</Form.Label>
                  <Form.Control
                    type="text"
                    name="pan_number"
                    maxLength={10}
                    placeholder="e.g. ABCDE1234F"
                    value={tempProfile.pan_number}
                    onChange={(e) => setTempProfile(p => ({ ...p, pan_number: e.target.value.toUpperCase() }))}
                    className="p-2 rounded-3 border text-uppercase small"
                  />
                </Form.Group>
              </Col>
              <Col xs={6}>
                <Form.Group>
                  <Form.Label className="small text-secondary mb-1" style={{ fontSize: '0.75rem' }}>GSTIN Number</Form.Label>
                  <Form.Control
                    type="text"
                    name="gst_number"
                    maxLength={15}
                    placeholder="e.g. 07AAAAA0000A1Z5"
                    value={tempProfile.gst_number}
                    onChange={(e) => setTempProfile(p => ({ ...p, gst_number: e.target.value.toUpperCase() }))}
                    className="p-2 rounded-3 border text-uppercase small"
                  />
                </Form.Group>
              </Col>
            </Row>

            <Form.Group>
              <Form.Label className="small text-secondary mb-1" style={{ fontSize: '0.75rem' }}>Company / Business Name</Form.Label>
              <Form.Control
                type="text"
                name="company_name"
                placeholder="Enterprise or firm name"
                value={tempProfile.company_name}
                onChange={handleChange}
                className="p-2 rounded-3 border small"
              />
            </Form.Group>
          </Card>

          {/* Card 4: Security & Quick Links */}
          <Card className="border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <h6 className="fw-bold text-dark mb-3 small d-flex align-items-center gap-2">
              <FaShieldAlt className="text-primary" /> SECURITY & ACTIONS
            </h6>

            <div className="d-flex flex-column gap-2">
              <Link 
                to={`/${usernamePath}/change-password`} 
                className="d-flex align-items-center justify-content-between p-2.5 rounded-3 text-decoration-none text-dark bg-light hover-bg-light"
              >
                <span className="d-flex align-items-center gap-2 small fw-semibold">
                  <FaLock className="text-primary" /> Change Password
                </span>
                <span className="text-muted small">Update ›</span>
              </Link>

              <button
                type="button"
                onClick={() => setShowDeactivateModal(true)}
                className="btn btn-link text-danger text-start text-decoration-none p-2 small fw-semibold"
              >
                Deactivate Account
              </button>
            </div>
          </Card>

          {/* Sticky Bottom Save Button */}
          <div className="position-fixed bottom-0 start-0 end-0 p-3 bg-white border-top shadow" style={{ zIndex: 100 }}>
            <div style={{ maxWidth: '520px', margin: '0 auto' }}>
              <Button
                type="submit"
                variant="primary"
                disabled={saving}
                className="w-100 py-2.5 rounded-pill fw-bold border-0 shadow-sm d-flex align-items-center justify-content-center gap-2"
                style={{ backgroundColor: '#0b53a1', letterSpacing: '0.5px' }}
              >
                {saving ? (
                  <>
                    <Spinner size="sm" animation="border" /> SAVING CHANGES...
                  </>
                ) : (
                  'SAVE CHANGES'
                )}
              </Button>
            </div>
          </div>
        </Form>

        {/* Deactivate Modal */}
        <Modal show={showDeactivateModal} onHide={() => setShowDeactivateModal(false)} centered size="sm">
          <Modal.Header closeButton className="border-0 pb-0">
            <Modal.Title className="fw-bold fs-6 text-danger d-flex align-items-center gap-2">
              <FaExclamationTriangle /> Deactivate Account
            </Modal.Title>
          </Modal.Header>
          <Modal.Body className="py-3 text-secondary small">
            Are you sure you want to deactivate your account? You can reactivate anytime by signing back in.
          </Modal.Body>
          <Modal.Footer className="border-0 pt-0 d-flex justify-content-end gap-2">
            <Button variant="light" size="sm" className="rounded-pill px-3 border" onClick={() => setShowDeactivateModal(false)}>
              Cancel
            </Button>
            <Button variant="danger" size="sm" className="rounded-pill px-4 fw-bold" disabled={deactivating} onClick={handleDeactivate}>
              {deactivating ? 'Deactivating...' : 'Deactivate'}
            </Button>
          </Modal.Footer>
        </Modal>
      </div>
    );
  }

  // =========================================================================
  // 💻 DESKTOP FLIPKART 2-COLUMN PROFILE VIEW
  // =========================================================================
  return (
    <div className="profile-desktop-page">
      {/* 1. Desktop Breadcrumb Navigation (Rule 9: Desktop Only) */}
      <div className="profile-breadcrumb-wrapper d-none d-lg-flex">
        <button 
          type="button" 
          className="profile-back-link" 
          onClick={handleBackToHub}
          aria-label="Back to Account"
        >
          <FaArrowLeft size={12} /> Back to My Account
        </button>
        <nav aria-label="breadcrumb">
          <ol className="profile-breadcrumb">
            <li><Link to="/">Home</Link></li>
            <span>/</span>
            <li><Link to={`/${usernamePath}/profile`}>My Account</Link></li>
            <span>/</span>
            <li className="active" aria-current="page">Personal Information</li>
          </ol>
        </nav>
      </div>

      {/* 2. Desktop Profile Header Banner Card */}
      <div className="profile-header-card d-none d-lg-flex">
        <div className="profile-header-left">
          {showAvatarImage ? (
            <img
              src={avatarUrl}
              alt={displayName}
              className="profile-avatar-circle"
              onError={() => setAvatarError(true)}
            />
          ) : (
            <div className="profile-avatar-circle">
              {getUserInitials()}
            </div>
          )}
          <div className="profile-user-meta">
            <div className="profile-user-name-row">
              <h4 className="profile-user-name">{displayName}</h4>
              {isVerified && (
                <span className="profile-status-badge">
                  <FaCheckCircle size={10} /> Verified Customer
                </span>
              )}
            </div>
            <div className="profile-user-contacts">
              <span className="profile-user-contact-item">
                <FaEnvelope size={12} /> {profile.email || user?.email || 'No email registered'}
              </span>
              <span>•</span>
              <span className="profile-user-contact-item">
                <FaPhoneAlt size={12} /> +91 {profile.mobile || user?.phone || user?.mobile || 'Not set'}
              </span>
            </div>
          </div>
        </div>

        <div className="d-flex align-items-center gap-2">
          <Link 
            to={`/${usernamePath}/wallet`} 
            className="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold d-flex align-items-center gap-2 text-decoration-none"
            style={{ fontSize: '0.8125rem' }}
          >
            <FaCoins className="text-warning fs-6" /> Wallet &amp; Coins
          </Link>
        </div>
      </div>

      {/* 3. SECTION 1: Personal Information Card */}
      <div className="profile-section-card">
        <div className="profile-section-header">
          <div className="profile-section-title-wrap">
            <div className="profile-section-icon-box">
              <FaUser />
            </div>
            <div>
              <h5 className="profile-section-title">Personal Information</h5>
              <p className="profile-section-desc">Manage your identity details across Printmont</p>
            </div>
          </div>

          {activeEdit !== 'personal' ? (
            <button 
              type="button"
              className="profile-btn-edit" 
              onClick={() => setActiveEdit('personal')}
              aria-label="Edit Personal Information"
            >
              <FaEdit size={13} /> Edit Information
            </button>
          ) : (
            <div className="profile-action-btn-group">
              <button 
                type="button"
                className="profile-btn-save"
                disabled={saving}
                onClick={handleSave}
              >
                {saving ? (
                  <>
                    <Spinner size="sm" animation="border" className="me-1" /> Saving...
                  </>
                ) : (
                  <>
                    <FaCheck size={12} className="me-1" /> Save Changes
                  </>
                )}
              </button>
              <button 
                type="button"
                className="profile-btn-cancel"
                onClick={handleCancel}
                disabled={saving}
              >
                Cancel
              </button>
            </div>
          )}
        </div>

        <Row className="g-3 mb-4">
          <Col md={6}>
            <div className="profile-form-group mb-0">
              <label className="profile-field-label">
                First Name {activeEdit === 'personal' && <span className="required">*</span>}
              </label>
              <div className="profile-input-wrapper">
                <span className="profile-input-icon">
                  <FaUser />
                </span>
                <input
                  type="text"
                  name="firstName"
                  className={`profile-input-control ${activeEdit !== 'personal' ? 'is-readonly' : ''}`}
                  value={tempProfile.firstName}
                  onChange={handleChange}
                  readOnly={activeEdit !== 'personal'}
                  placeholder="Enter your first name"
                  required
                />
              </div>
            </div>
          </Col>
          <Col md={6}>
            <div className="profile-form-group mb-0">
              <label className="profile-field-label">Last Name</label>
              <div className="profile-input-wrapper">
                <span className="profile-input-icon">
                  <FaUser />
                </span>
                <input
                  type="text"
                  name="lastName"
                  className={`profile-input-control ${activeEdit !== 'personal' ? 'is-readonly' : ''}`}
                  value={tempProfile.lastName}
                  onChange={handleChange}
                  readOnly={activeEdit !== 'personal'}
                  placeholder="Enter your last name"
                />
              </div>
            </div>
          </Col>
        </Row>

        {/* Gender Selection */}
        <div>
          <label className="profile-field-label">Your Gender</label>
          <div className="profile-gender-group">
            <div
              className={`profile-gender-pill ${tempProfile.gender === 'Male' ? 'active' : ''} ${activeEdit !== 'personal' ? 'is-disabled' : ''}`}
              onClick={() => activeEdit === 'personal' && handleGenderSelect('Male')}
              role="button"
              tabIndex={0}
              aria-label="Select Male Gender"
            >
              <FaMars /> Male
            </div>
            <div
              className={`profile-gender-pill ${tempProfile.gender === 'Female' ? 'active' : ''} ${activeEdit !== 'personal' ? 'is-disabled' : ''}`}
              onClick={() => activeEdit === 'personal' && handleGenderSelect('Female')}
              role="button"
              tabIndex={0}
              aria-label="Select Female Gender"
            >
              <FaVenus /> Female
            </div>
          </div>
        </div>
      </div>

      {/* 4. SECTION 2: Contact Information Card (Email & Mobile) */}
      <div className="profile-section-card">
        <div className="profile-section-header">
          <div className="profile-section-title-wrap">
            <div className="profile-section-icon-box">
              <FaEnvelope />
            </div>
            <div>
              <h5 className="profile-section-title">Contact Information</h5>
              <p className="profile-section-desc">Manage your email and phone communication settings</p>
            </div>
          </div>

          {activeEdit !== 'mobile' ? (
            <button 
              type="button"
              className="profile-btn-edit" 
              onClick={() => setActiveEdit('mobile')}
              aria-label="Edit Mobile Number"
            >
              <FaEdit size={13} /> Edit Mobile
            </button>
          ) : (
            <div className="profile-action-btn-group">
              <button 
                type="button"
                className="profile-btn-save"
                disabled={saving}
                onClick={handleSave}
              >
                {saving ? (
                  <>
                    <Spinner size="sm" animation="border" className="me-1" /> Saving...
                  </>
                ) : (
                  <>
                    <FaCheck size={12} className="me-1" /> Save Mobile
                  </>
                )}
              </button>
              <button 
                type="button"
                className="profile-btn-cancel"
                onClick={handleCancel}
                disabled={saving}
              >
                Cancel
              </button>
            </div>
          )}
        </div>

        <Row className="g-4">
          {/* Email Address (Protected) */}
          <Col md={6}>
            <div className="profile-form-group mb-0">
              <label className="profile-field-label">Email Address</label>
              <div className="profile-input-wrapper">
                <span className="profile-input-icon">
                  <FaEnvelope />
                </span>
                <input
                  type="email"
                  name="email"
                  className="profile-input-control is-readonly"
                  value={tempProfile.email}
                  readOnly={true}
                  title="Primary account email is verified and protected."
                />
                {isVerified && (
                  <span className="profile-input-badge">
                    <FaCheckCircle size={10} /> Verified
                  </span>
                )}
              </div>
              <div className="profile-input-note">
                <FaLock size={10} /> Verified primary email is protected for security reasons.
              </div>
            </div>
          </Col>

          {/* Mobile Number (Editable) */}
          <Col md={6}>
            <div className="profile-form-group mb-0">
              <label className="profile-field-label">
                Mobile Number {activeEdit === 'mobile' && <span className="required">*</span>}
              </label>
              <div className="profile-input-wrapper">
                <span className="profile-phone-prefix">
                  +91
                </span>
                <input
                  type="tel"
                  name="mobile"
                  maxLength={10}
                  className={`profile-input-control profile-phone-input ${activeEdit !== 'mobile' ? 'is-readonly' : ''}`}
                  value={tempProfile.mobile}
                  onChange={handleChange}
                  readOnly={activeEdit !== 'mobile'}
                  placeholder="10-digit mobile number"
                  required
                />
                {isVerified && activeEdit !== 'mobile' && (
                  <span className="profile-input-badge">
                    <FaCheckCircle size={10} /> Verified
                  </span>
                )}
              </div>
              <div className="profile-input-note">
                Used for order tracking, OTP delivery, and shipping dispatch alerts.
              </div>
            </div>
          </Col>
        </Row>
      </div>

      {/* 5. SECTION 3: Business Invoicing (PAN & GST Banner) */}
      <div className="profile-section-card p-3">
        <div className="profile-business-box">
          <div className="profile-business-left">
            <div className="profile-business-icon">
              <FaIdCard />
            </div>
            <div>
              <h6 className="profile-business-title">Business Invoicing &amp; Tax Details (PAN / GST)</h6>
              <p className="profile-business-sub">
                {user?.gst_number || user?.pan_number
                  ? `GSTIN: ${user?.gst_number || 'Not Set'} • PAN: ${user?.pan_number || 'Not Set'}`
                  : 'Add PAN or GSTIN for commercial B2B tax invoices, bulk business purchases, and input tax credit claims.'}
              </p>
            </div>
          </div>
          <Link 
            to={`/${usernamePath}/pan-gst`} 
            className="profile-btn-manage"
          >
            {user?.gst_number || user?.pan_number ? 'Manage PAN/GST' : '+ Add PAN/GST'}
          </Link>
        </div>
      </div>

      {/* 6. SECTION 4: Account Security & Actions */}
      <div className="profile-section-card">
        <div className="profile-section-header">
          <div className="profile-section-title-wrap">
            <div className="profile-section-icon-box">
              <FaShieldAlt />
            </div>
            <div>
              <h5 className="profile-section-title">Account Security &amp; Privacy</h5>
              <p className="profile-section-desc">Manage credentials and sensitive account actions</p>
            </div>
          </div>
        </div>

        <div className="profile-security-grid">
          <Link 
            to={`/${usernamePath}/change-password`} 
            className="profile-security-item"
          >
            <div className="profile-security-item-left">
              <div className="profile-security-item-icon">
                <FaLock />
              </div>
              <div>
                <div className="fw-bold">Password &amp; Security</div>
                <small className="text-muted" style={{ fontSize: '0.74rem' }}>Update your account password</small>
              </div>
            </div>
            <span className="profile-security-item-link">Update ›</span>
          </Link>

          <div className="profile-security-item">
            <div className="profile-security-item-left">
              <div className="profile-security-item-icon" style={{ color: '#dc2626' }}>
                <FaExclamationTriangle />
              </div>
              <div>
                <div className="fw-bold text-danger">Deactivate Account</div>
                <small className="text-muted" style={{ fontSize: '0.74rem' }}>Temporarily disable your account</small>
              </div>
            </div>
            <button
              type="button"
              onClick={() => setShowDeactivateModal(true)}
              className="profile-btn-deactivate"
            >
              Deactivate
            </button>
          </div>
        </div>
      </div>

      {/* 7. SECTION 5: Frequently Asked Questions (FAQs) */}
      <div className="profile-section-card">
        <div className="profile-section-header">
          <div className="profile-section-title-wrap">
            <div className="profile-section-icon-box" style={{ background: '#fef3c7', borderColor: '#fde68a', color: '#b45309' }}>
              <FaQuestionCircle />
            </div>
            <div>
              <h5 className="profile-section-title">Frequently Asked Questions</h5>
              <p className="profile-section-desc">Quick answers about profile updates and account security</p>
            </div>
          </div>
        </div>

        <div className="profile-faq-list">
          <div className="profile-faq-item">
            <div className="profile-faq-q">What happens when I update my mobile number?</div>
            <p className="profile-faq-a">
              Your login mobile number is updated instantly. You will receive all future OTPs, dispatch alerts, and delivery status messages on your updated number.
            </p>
          </div>
          <div className="profile-faq-item">
            <div className="profile-faq-q">What happens to my order history and saved addresses?</div>
            <p className="profile-faq-a">
              Updating your personal profile does not affect your account data. Your entire order history, saved addresses, wallet balance, and SuperCoins remain 100% safe and intact.
            </p>
          </div>
          <div className="profile-faq-item">
            <div className="profile-faq-q">Can I get a tax invoice with my company's GSTIN?</div>
            <p className="profile-faq-a">
              Yes! You can link your GSTIN and PAN under the <strong>Business Invoicing &amp; Tax Details</strong> section. Tax invoices with full input tax credit will be automatically generated for your orders.
            </p>
          </div>
        </div>
      </div>

      {/* 8. Deactivate Confirmation Modal */}
      <Modal show={showDeactivateModal} onHide={() => setShowDeactivateModal(false)} centered size="sm">
        <Modal.Header closeButton className="border-0 pb-0">
          <Modal.Title className="fw-bold fs-6 text-danger d-flex align-items-center gap-2">
            <FaExclamationTriangle /> Deactivate Account
          </Modal.Title>
        </Modal.Header>
        <Modal.Body className="py-3 text-secondary small">
          Are you sure you want to deactivate your Printmont account? You can reactivate anytime simply by signing back in with your credentials.
        </Modal.Body>
        <Modal.Footer className="border-0 pt-0 d-flex justify-content-end gap-2">
          <button 
            type="button"
            className="profile-btn-cancel"
            onClick={() => setShowDeactivateModal(false)}
          >
            Cancel
          </button>
          <button 
            type="button"
            className="btn btn-danger btn-sm rounded-pill px-4 fw-bold shadow-xs"
            disabled={deactivating}
            onClick={handleDeactivate}
          >
            {deactivating ? (
              <>
                <Spinner size="sm" animation="border" className="me-1" /> Deactivating...
              </>
            ) : (
              'Deactivate'
            )}
          </button>
        </Modal.Footer>
      </Modal>
    </div>
  );
};

export default Profile;
