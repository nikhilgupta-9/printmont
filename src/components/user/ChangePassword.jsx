import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { BsEye, BsEyeSlash } from 'react-icons/bs';
import { 
  FaLock, 
  FaKey, 
  FaShieldAlt, 
  FaCheck, 
  FaExclamationCircle, 
  FaArrowLeft,
  FaCheckCircle,
  FaRegCircle
} from 'react-icons/fa';
import { Spinner } from 'react-bootstrap';
import toast from 'react-hot-toast';
import { useAuth } from '../../context/AuthContext';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import './ChangePassword.css';

const MIN_LENGTH = 6;

const ChangePassword = () => {
  const { user, token, logout, getUsernamePath } = useAuth();
  const navigate = useNavigate();
  const usernamePath = getUsernamePath ? getUsernamePath() : 'user';

  const [form, setForm] = useState({
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
  });

  const [visible, setVisible] = useState({
    currentPassword: false,
    newPassword: false,
    confirmPassword: false,
  });

  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);

  // Requirement checks for new password
  const reqChecks = {
    minLength: form.newPassword.length >= MIN_LENGTH,
    hasUpper: /[A-Z]/.test(form.newPassword),
    hasNumberOrSpecial: /[0-9!@#$%^&*(),.?":{}|<>]/.test(form.newPassword),
    isDifferent: form.newPassword && form.currentPassword ? form.newPassword !== form.currentPassword : true,
    matches: form.confirmPassword ? form.confirmPassword === form.newPassword : false
  };

  // Calculate password strength score (0-100)
  const getPasswordStrength = (pwd) => {
    if (!pwd) return { score: 0, label: 'None', color: '#94a3b8' };
    let score = 0;
    if (pwd.length >= 6) score += 25;
    if (pwd.length >= 8) score += 25;
    if (/[A-Z]/.test(pwd)) score += 25;
    if (/[0-9]/.test(pwd) || /[^A-Za-z0-9]/.test(pwd)) score += 25;

    if (score <= 25) return { score, label: 'Weak', color: '#ef4444' };
    if (score <= 75) return { score, label: 'Moderate', color: '#f59e0b' };
    return { score: 100, label: 'Strong', color: '#16a34a' };
  };

  const strength = getPasswordStrength(form.newPassword);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
    setErrors((prev) => (prev[name] ? { ...prev, [name]: undefined } : prev));
  };

  const toggle = (field) =>
    setVisible((prev) => ({ ...prev, [field]: !prev[field] }));

  const validate = () => {
    const e = {};

    if (!form.currentPassword) {
      e.currentPassword = 'Enter your current account password';
    }
    if (!form.newPassword) {
      e.newPassword = 'Enter a new password';
    } else if (form.newPassword.length < MIN_LENGTH) {
      e.newPassword = `Password must be at least ${MIN_LENGTH} characters`;
    } else if (form.newPassword === form.currentPassword) {
      e.newPassword = 'New password must be different from your current password';
    }
    if (!form.confirmPassword) {
      e.confirmPassword = 'Confirm your new password';
    } else if (form.confirmPassword !== form.newPassword) {
      e.confirmPassword = 'Passwords do not match';
    }

    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (saving || !validate()) return;

    setSaving(true);
    try {
      const activeToken = token || localStorage.getItem('token') || '';
      const res = await fetch(API_ENDPOINTS.CHANGE_PASSWORD, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${activeToken}`,
        },
        body: JSON.stringify({
          current_password: form.currentPassword,
          new_password: form.newPassword,
        }),
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        const message = data.error || data.message || 'Could not change your password.';
        if (/current password/i.test(message)) {
          setErrors({ currentPassword: message });
        }
        toast.error(message);
        return;
      }

      toast.success(data.message || 'Password changed successfully! Signing out for security...');
      setForm({ currentPassword: '', newPassword: '', confirmPassword: '' });

      setTimeout(() => {
        if (logout) logout();
      }, 1500);
    } catch (err) {
      console.error('Change password failed:', err);
      toast.error('Could not reach the server. Please try again.');
    } finally {
      setSaving(false);
    }
  };

  const handleCancel = () => {
    setForm({ currentPassword: '', newPassword: '', confirmPassword: '' });
    setErrors({});
    navigate(`/${usernamePath}/profile`);
  };

  const renderPasswordField = (name, label, placeholder, autoComplete) => (
    <div className="cp-form-group">
      <label className="cp-field-label" htmlFor={name}>
        {label} <span className="required">*</span>
      </label>
      <div className="cp-input-group">
        <span className="cp-input-icon">
          <FaKey />
        </span>
        <input
          id={name}
          name={name}
          type={visible[name] ? 'text' : 'password'}
          className={`cp-input-control${errors[name] ? ' is-invalid' : ''}`}
          placeholder={placeholder}
          value={form[name]}
          onChange={handleChange}
          autoComplete={autoComplete}
        />
        <button
          type="button"
          className="cp-input-toggle"
          onClick={() => toggle(name)}
          aria-label={visible[name] ? `Hide ${label}` : `Show ${label}`}
          tabIndex={-1}
        >
          {visible[name] ? <BsEyeSlash /> : <BsEye />}
        </button>
      </div>
      {errors[name] && (
        <div className="cp-field-error">
          <FaExclamationCircle size={12} /> {errors[name]}
        </div>
      )}
    </div>
  );

  return (
    <>
      {/* ─────────────────────────────────────────────────────────── */}
      {/* 1. DESKTOP VIEW (Rule 4: >= 992px)                          */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="d-none d-lg-block cp-desktop-container">
        {/* 1. Desktop Breadcrumbs (Rule 9: Desktop Only) */}
        <div className="cp-breadcrumb-wrapper">
          <button
            type="button"
            className="cp-back-link"
            onClick={() => navigate(`/${usernamePath}/profile`)}
            aria-label="Back to Account"
          >
            <FaArrowLeft size={12} /> Back to My Account
          </button>
          <nav aria-label="breadcrumb">
            <ol className="cp-breadcrumb">
              <li><Link to="/">Home</Link></li>
              <span>/</span>
              <li><Link to={`/${usernamePath}/profile`}>My Account</Link></li>
              <span>/</span>
              <li className="active" aria-current="page">Password &amp; Security</li>
            </ol>
          </nav>
        </div>

        {/* 2. Desktop Header Banner Card */}
        <div className="cp-header-card">
          <div className="cp-header-left">
            <div className="cp-header-icon-box">
              <FaLock />
            </div>
            <div className="cp-header-meta">
              <h4 className="cp-header-title">Password &amp; Account Security</h4>
              <p className="cp-header-sub">
                Update your password regularly to protect your account, orders, and payment methods
              </p>
            </div>
          </div>
          <div className="cp-header-badge">
            <FaShieldAlt size={14} /> Active Protection
          </div>
        </div>

        {/* 3. Main Form Card */}
        <div className="cp-form-card">
          <div className="cp-form-inner">
            <div className="cp-section-title-wrap">
              <h5 className="cp-section-title">Change Password</h5>
              <p className="cp-section-desc">
                Choose a strong password with letters, numbers, and symbols to ensure maximum security.
              </p>
            </div>

            <form onSubmit={handleSubmit} noValidate>
              {renderPasswordField(
                'currentPassword',
                'Current Password',
                'Enter your existing password',
                'current-password'
              )}

              {renderPasswordField(
                'newPassword',
                'New Password',
                'Enter new password (min. 6 characters)',
                'new-password'
              )}

              {/* Password Strength Meter & Requirement Checklist */}
              {form.newPassword && (
                <div className="cp-strength-box">
                  <div className="cp-strength-header">
                    <span className="cp-strength-label">Password Strength:</span>
                    <span className="cp-strength-val" style={{ color: strength.color }}>
                      {strength.label}
                    </span>
                  </div>
                  <div className="cp-strength-bar">
                    <div
                      className="cp-strength-bar-fill"
                      style={{
                        width: `${strength.score}%`,
                        backgroundColor: strength.color,
                      }}
                    />
                  </div>

                  <div className="cp-requirements-list">
                    <div className={`cp-requirement-item ${reqChecks.minLength ? 'valid' : ''}`}>
                      <span className="cp-req-icon">
                        {reqChecks.minLength ? <FaCheckCircle /> : <FaRegCircle />}
                      </span>
                      <span>At least 6 characters</span>
                    </div>
                    <div className={`cp-requirement-item ${reqChecks.hasUpper ? 'valid' : ''}`}>
                      <span className="cp-req-icon">
                        {reqChecks.hasUpper ? <FaCheckCircle /> : <FaRegCircle />}
                      </span>
                      <span>Contains at least one uppercase letter (A-Z)</span>
                    </div>
                    <div className={`cp-requirement-item ${reqChecks.hasNumberOrSpecial ? 'valid' : ''}`}>
                      <span className="cp-req-icon">
                        {reqChecks.hasNumberOrSpecial ? <FaCheckCircle /> : <FaRegCircle />}
                      </span>
                      <span>Contains at least one number or special symbol</span>
                    </div>
                    {form.currentPassword && (
                      <div className={`cp-requirement-item ${reqChecks.isDifferent ? 'valid' : ''}`}>
                        <span className="cp-req-icon">
                          {reqChecks.isDifferent ? <FaCheckCircle /> : <FaRegCircle />}
                        </span>
                        <span>Different from current password</span>
                      </div>
                    )}
                  </div>
                </div>
              )}

              {renderPasswordField(
                'confirmPassword',
                'Confirm New Password',
                'Re-enter your new password',
                'new-password'
              )}

              <div className="cp-btn-group">
                <button
                  type="submit"
                  className="cp-btn-submit"
                  disabled={saving}
                >
                  {saving ? (
                    <>
                      <Spinner size="sm" animation="border" /> Updating Password…
                    </>
                  ) : (
                    <>
                      <FaCheck size={13} /> Save &amp; Update Password
                    </>
                  )}
                </button>
                <button
                  type="button"
                  className="cp-btn-cancel"
                  onClick={handleCancel}
                  disabled={saving}
                >
                  Cancel
                </button>
              </div>
            </form>

            {/* Printmont Security Advice Box */}
            <div className="cp-security-box">
              <div className="cp-security-icon-circle">
                <FaShieldAlt />
              </div>
              <div className="cp-security-text">
                <strong>Printmont Security Advice:</strong>
                Never share your password, login credentials, or SMS OTP codes with anyone. For your safety, you will be automatically signed out on all active devices after password update.
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ─────────────────────────────────────────────────────────── */}
      {/* 2. MOBILE VIEW (Rule 4 & Rule 8: < 992px)                   */}
      {/* ─────────────────────────────────────────────────────────── */}
      <div className="d-lg-none cp-mobile-view">
        {/* Mobile Sticky Top Header */}
        <div className="cp-mobile-topbar">
          <button
            type="button"
            className="cp-mobile-back-btn"
            onClick={() => navigate(`/${usernamePath}/profile`)}
            aria-label="Back to Account"
          >
            <FaArrowLeft size={16} />
          </button>
          <h6 className="cp-mobile-topbar-title">Password &amp; Security</h6>
          <div style={{ width: 38 }} /> {/* Balancer */}
        </div>

        <div className="cp-mobile-content">
          <div className="cp-mobile-card">
            <div className="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
              <div className="cp-header-icon-box" style={{ width: 44, height: 44, fontSize: '1.15rem' }}>
                <FaLock />
              </div>
              <div>
                <h6 className="fw-bold text-dark mb-0 fs-6">Change Account Password</h6>
                <small className="text-muted">Protect your account and orders</small>
              </div>
            </div>

            <form onSubmit={handleSubmit} noValidate>
              {renderPasswordField(
                'currentPassword',
                'Current Password',
                'Enter current password',
                'current-password'
              )}

              {renderPasswordField(
                'newPassword',
                'New Password',
                'Enter new password (min. 6 chars)',
                'new-password'
              )}

              {/* Mobile Strength Bar */}
              {form.newPassword && (
                <div className="cp-strength-box mb-3">
                  <div className="cp-strength-header">
                    <span className="cp-strength-label">Strength:</span>
                    <span className="cp-strength-val" style={{ color: strength.color }}>
                      {strength.label}
                    </span>
                  </div>
                  <div className="cp-strength-bar">
                    <div
                      className="cp-strength-bar-fill"
                      style={{
                        width: `${strength.score}%`,
                        backgroundColor: strength.color,
                      }}
                    />
                  </div>
                  <div className="cp-requirements-list">
                    <div className={`cp-requirement-item ${reqChecks.minLength ? 'valid' : ''}`}>
                      <span className="cp-req-icon">
                        {reqChecks.minLength ? <FaCheckCircle /> : <FaRegCircle />}
                      </span>
                      <span>Min. 6 characters</span>
                    </div>
                    <div className={`cp-requirement-item ${reqChecks.hasUpper && reqChecks.hasNumberOrSpecial ? 'valid' : ''}`}>
                      <span className="cp-req-icon">
                        {reqChecks.hasUpper && reqChecks.hasNumberOrSpecial ? <FaCheckCircle /> : <FaRegCircle />}
                      </span>
                      <span>Uppercase &amp; numbers/symbols</span>
                    </div>
                  </div>
                </div>
              )}

              {renderPasswordField(
                'confirmPassword',
                'Confirm New Password',
                'Re-enter new password',
                'new-password'
              )}

              <button
                type="submit"
                className="cp-btn-submit w-100 py-2.5 mt-2"
                disabled={saving}
              >
                {saving ? (
                  <>
                    <Spinner size="sm" animation="border" /> Updating…
                  </>
                ) : (
                  <>
                    <FaCheck size={13} /> Update Password
                  </>
                )}
              </button>
            </form>
          </div>

          {/* Mobile Security Advice Box */}
          <div className="cp-security-box mt-3">
            <div className="cp-security-icon-circle">
              <FaShieldAlt />
            </div>
            <div className="cp-security-text">
              <strong>Printmont Security Advice:</strong>
              Never share your credentials with anyone. Updating your password signs you out on other devices for security.
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default ChangePassword;
