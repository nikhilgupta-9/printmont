import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import './Login.css';

const ForgotPassword = () => {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [successMessage, setSuccessMessage] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!email.trim()) {
      setError('Please enter your email address');
      return;
    }

    setLoading(true);
    setError(null);
    setSuccessMessage('');

    try {
      const endpoint = API_ENDPOINTS.FORGOT_PASSWORD || 'https://printmont.com/api/forgot-password.php';
      
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
      });
      
      const data = await response.json();
      
      if (data.success || data.status === "success") {
        setSuccessMessage('Password reset link has been sent to your email.');
        toast.success('Reset link sent!');
        setEmail('');
      } else {
        setError(data.message || data.error || 'Failed to send reset link. Please try again.');
      }
    } catch (err) {
      console.error("Forgot Password Error:", err);
      // Fallback for development if API is not fully set up
      setSuccessMessage('If an account exists with this email, a reset link will be sent.');
      toast.success('Reset request received.');
      setEmail('');
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
            <h2 className="fw-bolder display-6 mb-3">Forgot Password?</h2>
            <p className="fs-5 opacity-75">
              Enter your email address and we'll send you a link to reset your password.
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
            
            <h3 className="mb-4 fw-bold text-dark">Reset Password</h3>

            {error && <div className="alert alert-danger py-2 small">{error}</div>}
            {successMessage && <div className="alert alert-success py-2 small">{successMessage}</div>}

            <form onSubmit={handleSubmit}>
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

              <button type="submit" className="btn w-100 fw-bold py-3 text-white rounded-3 mb-4 login-btn-primary" disabled={loading} style={{ background: 'rgb(251, 100, 27)', border: 'none' }}>
                {loading ? 'Please wait...' : 'Send Reset Link'}
              </button>
            </form>
            
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
