import React, { useState } from 'react';
import { BsEye, BsEyeSlash } from 'react-icons/bs';
import toast from 'react-hot-toast';
import { useAuth } from '../../context/AuthContext';
import { API_ENDPOINTS } from '../../config/apiEndpoints';

const MIN_LENGTH = 6;

/**
 * Signed-in password change. Unlike ForgotPassword, which proves ownership with
 * an emailed OTP, this proves it with the current password and the bearer
 * token. The backend re-checks both, so the validation here is only to save a
 * round trip.
 */
const ChangePassword = () => {
  const { token, logout } = useAuth();

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
      e.currentPassword = 'Enter your current password';
    }
    if (!form.newPassword) {
      e.newPassword = 'Enter a new password';
    } else if (form.newPassword.length < MIN_LENGTH) {
      e.newPassword = `New password must be at least ${MIN_LENGTH} characters`;
    } else if (form.newPassword === form.currentPassword) {
      e.newPassword = 'New password must be different from the current one';
    }
    if (form.confirmPassword !== form.newPassword) {
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
      const res = await fetch(API_ENDPOINTS.CHANGE_PASSWORD, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          current_password: form.currentPassword,
          new_password: form.newPassword,
        }),
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        const message = data.error || data.message || 'Could not change your password.';
        // The only field-specific failure the API reports.
        if (/current password/i.test(message)) {
          setErrors({ currentPassword: message });
        }
        toast.error(message);
        return;
      }

      toast.success(data.message || 'Password changed successfully');
      setForm({ currentPassword: '', newPassword: '', confirmPassword: '' });

      // The stored token was issued against the old password. Sending the user
      // back through login keeps the session honest.
      if (logout) logout();
    } catch (err) {
      console.error('Change password failed:', err);
      toast.error('Could not reach the server. Please try again.');
    } finally {
      setSaving(false);
    }
  };

  const passwordField = (name, label, autoComplete) => (
    <div className="mb-4">
      <label className="form-label" htmlFor={name}>{label}</label>
      <div className="input-group">
        <input
          id={name}
          name={name}
          type={visible[name] ? 'text' : 'password'}
          className={`form-control p-3${errors[name] ? ' is-invalid' : ''}`}
          value={form[name]}
          onChange={handleChange}
          autoComplete={autoComplete}
        />
        <button
          type="button"
          className="btn btn-outline-secondary"
          onClick={() => toggle(name)}
          aria-label={visible[name] ? `Hide ${label}` : `Show ${label}`}
        >
          {visible[name] ? <BsEyeSlash /> : <BsEye />}
        </button>
      </div>
      {errors[name] && (
        <div className="text-danger mt-1" style={{ fontSize: '13px' }}>{errors[name]}</div>
      )}
    </div>
  );

  return (
    <div className="card p-4 border bd">
      <h5 className="mb-1">Change Password</h5>
      <p className="text-muted mb-4" style={{ fontSize: '14px' }}>
        Use at least {MIN_LENGTH} characters. You will be signed out once it is changed.
      </p>

      <form onSubmit={handleSubmit} noValidate style={{ maxWidth: '480px' }}>
        {passwordField('currentPassword', 'Current Password', 'current-password')}
        {passwordField('newPassword', 'New Password', 'new-password')}
        {passwordField('confirmPassword', 'Confirm New Password', 'new-password')}

        <button type="submit" className="btn btn-primary px-4 py-2" disabled={saving}>
          {saving ? 'Saving…' : 'Change Password'}
        </button>
      </form>
    </div>
  );
};

export default ChangePassword;
