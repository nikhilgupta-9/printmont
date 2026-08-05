import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import './Login.css';

const ForgotPassword = () => {
  const navigate = useNavigate();
  const [step, setStep] = useState(1); // Step 1: Enter Email, Step 2: Enter OTP & New Password
  const [email, setEmail] = useState('');
  const [otp, setOtp] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [successMessage, setSuccessMessage] = useState('');

  // Step 1: Send OTP to Email
  const handleSendOtp = async (e) => {
    e.preventDefault();
    if (!email.trim()) {
      setError('Please enter your email address');
      return;
    }

    setLoading(true);
    setError(null);
    setSuccessMessage('');

    try {
      const endpoint = API_ENDPOINTS.FORGOT_PASSWORD || '/api/user-api.php?action=forgot_password';
      
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email.trim() })
      });
      
      const data = await response.json();
      
      if (data.success || data.status === "success") {
        setSuccessMessage('An OTP has been sent to your email address.');
        toast.success('OTP sent to your email!');
        setStep(2);
      } else {
        setError(data.message || data.error || 'Failed to send OTP. Please try again.');
      }
    } catch (err) {
      console.error("Forgot Password Error:", err);
      setError('Network error. Please try again later.');
    } finally {
      setLoading(false);
    }
  };

  // Step 2: Verify OTP & Set New Password
  const handleResetPassword = async (e) => {
    e.preventDefault();
    setError(null);
    setSuccessMessage('');

    if (!otp.trim() || otp.trim().length < 4) {
      setError('Please enter a valid OTP code');
      return;
    }

    if (!newPassword || newPassword.length < 6) {
      setError('Password must be at least 6 characters long');
      return;
    }

    if (newPassword !== confirmPassword) {
      setError('Passwords do not match');
      return;
    }

    setLoading(true);

    try {
      const endpoint = API_ENDPOINTS.CHANGE_PASSWORD || '/api/user-api.php?action=reset_password';
      
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: email.trim(),
          otp: otp.trim(),
          new_password: newPassword
        })
      });

      const data = await response.json();

      if (data.success || data.status === "success") {
        toast.success('Password reset successfully! Please log in.');
        setTimeout(() => {
          navigate('/login');
        }, 1500);
      } else {
        setError(data.message || data.error || 'Invalid OTP or failed to reset password.');
      }
    } catch (err) {
      console.error("Reset Password Error:", err);
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-wrapper">
      <div className="login-card row m-0">
        
        {/* Left Side */}
        <div className="col-12 col-md-5 p-5 text-white login-left-pane d-flex flex-column" 
             style={{ background: 'linear-gradient(135deg, rgb(11, 83, 161) 0%, rgb(41, 117, 240) 100%)' }}>
          
          <div className="z-1 d-flex flex-column h-100">
            <h2 className="fw-bolder display-6 mb-3">
              {step === 1 ? 'Forgot Password?' : 'Reset Password'}
            </h2>
            <p className="fs-5 opacity-75">
              {step === 1 
                ? "Enter your registered email address to receive a 6-digit OTP code."
                : "Enter the OTP code sent to your email along with your new password."}
            </p>
            <img
              src="/PrintLogo.png"
              alt="Printmont"
              className="login-illustration mt-auto d-none d-md-block"
              style={{ filter: "brightness(0) invert(1)", maxHeight: "45px", width: "auto", objectFit: "contain", alignSelf: "flex-start" }}
            />
          </div>
        </div>

        {/* Right Side */}
        <div className="col-12 col-md-7 bg-white p-4 p-md-5 d-flex flex-column justify-content-center position-relative">
          <div className="w-100 mx-auto" style={{ maxWidth: '450px' }}>
            
            <h3 className="mb-4 fw-bold text-dark">
              {step === 1 ? 'Recover Account' : 'Verify OTP & Set Password'}
            </h3>

            {error && <div className="alert alert-danger py-2 small">{error}</div>}
            {successMessage && <div className="alert alert-success py-2 small">{successMessage}</div>}

            {step === 1 ? (
              /* STEP 1: Enter Email */
              <form onSubmit={handleSendOtp}>
                <div className="form-floating mb-4">
                  <input 
                    type="email" 
                    name="email" 
                    className="form-control login-form-input" 
                    id="email" 
                    placeholder="Enter Email Address" 
                    value={email} 
                    onChange={(e) => setEmail(e.target.value)} 
                    autoFocus
                    required 
                  />
                  <label htmlFor="email">Email Address</label>
                </div>

                <button 
                  type="submit" 
                  className="btn w-100 fw-bold py-3 text-white rounded-3 mb-4 login-btn-primary" 
                  disabled={loading} 
                  style={{ background: 'rgb(251, 100, 27)', border: 'none' }}
                >
                  {loading ? 'Sending OTP...' : 'Send OTP Code'}
                </button>
              </form>
            ) : (
              /* STEP 2: Enter OTP & New Password */
              <form onSubmit={handleResetPassword}>
                <div className="form-floating mb-3">
                  <input 
                    type="email" 
                    className="form-control login-form-input bg-light" 
                    value={email} 
                    disabled 
                  />
                  <label>Email Address</label>
                </div>

                <div className="form-floating mb-3">
                  <input 
                    type="text" 
                    className="form-control login-form-input" 
                    id="otp" 
                    placeholder="6-Digit OTP" 
                    value={otp} 
                    onChange={(e) => setOtp(e.target.value)} 
                    maxLength={6}
                    autoFocus
                    required 
                  />
                  <label htmlFor="otp">Enter 6-Digit OTP</label>
                </div>

                <div className="form-floating mb-3">
                  <input 
                    type="password" 
                    className="form-control login-form-input" 
                    id="newPassword" 
                    placeholder="New Password" 
                    value={newPassword} 
                    onChange={(e) => setNewPassword(e.target.value)} 
                    minLength={6}
                    required 
                  />
                  <label htmlFor="newPassword">New Password</label>
                </div>

                <div className="form-floating mb-4">
                  <input 
                    type="password" 
                    className="form-control login-form-input" 
                    id="confirmPassword" 
                    placeholder="Confirm New Password" 
                    value={confirmPassword} 
                    onChange={(e) => setConfirmPassword(e.target.value)} 
                    minLength={6}
                    required 
                  />
                  <label htmlFor="confirmPassword">Confirm New Password</label>
                </div>

                <button 
                  type="submit" 
                  className="btn w-100 fw-bold py-3 text-white rounded-3 mb-3 login-btn-primary" 
                  disabled={loading} 
                  style={{ background: 'rgb(251, 100, 27)', border: 'none' }}
                >
                  {loading ? 'Resetting Password...' : 'Reset Password'}
                </button>

                <div className="text-center mb-4">
                  <button
                    type="button"
                    onClick={() => { setStep(1); setError(null); setSuccessMessage(''); }}
                    className="btn btn-link text-decoration-none small text-muted p-0"
                  >
                    ← Change Email or Resend OTP
                  </button>
                </div>
              </form>
            )}
            
            <div className="d-flex align-items-center mb-4">
              <hr className="flex-grow-1 text-muted opacity-25" />
              <span className="px-3 text-muted small fw-medium">OR</span>
              <hr className="flex-grow-1 text-muted opacity-25" />
            </div>

            <Link to="/login" className="btn w-100 py-3 rounded-3 fw-bold login-btn-secondary shadow-sm text-decoration-none text-center d-block">
              Back to Login
            </Link>
          </div>
        </div>

      </div>
    </div>
  );
};

export default ForgotPassword;
