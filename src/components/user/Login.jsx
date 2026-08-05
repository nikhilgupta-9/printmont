import React, { useState, useEffect } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { FaEye, FaEyeSlash } from 'react-icons/fa';
import toast from 'react-hot-toast';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import { useAuth } from '../../context/AuthContext';
import './Login.css';

const Login = () => {
  const [isSignup, setIsSignup] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();
  const queryParams = new URLSearchParams(location.search);
  const redirectPath = queryParams.get('redirect') || '/';
  const { user, login: authLogin } = useAuth();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Redirect if already logged in
  useEffect(() => {
    if (user) {
      navigate(redirectPath);
    }
  }, [user, navigate, redirectPath]);

  // Multi-step Login States
  const [step, setStep] = useState(1);
  const [identifierType, setIdentifierType] = useState('email'); // 'email' or 'mobile'
  const [loginMethod, setLoginMethod] = useState('password'); // 'password' or 'otp'
  const [showPassword, setShowPassword] = useState(false);

  // Form State
  const [formData, setFormData] = useState({
    identifier: '', // Used for the first step
    otp: '',
    firstName: '',
    lastName: '',
    email: '',
    mobile: '',
    gender: '',
    password: ''
  });

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (error) setError(null);
  };

  const handleContinue = (e) => {
    e.preventDefault();
    const cleanIdentifier = (formData.identifier || '').trim();
    if (!cleanIdentifier) {
      setError('Please enter Email or Mobile number');
      return;
    }

    // Basic Validation
    const isMobile = /^\d{10}$/.test(cleanIdentifier);
    const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(cleanIdentifier);

    if (isMobile) {
      setFormData(prev => ({ ...prev, identifier: cleanIdentifier }));
      setIdentifierType('mobile');
      setLoginMethod('otp');
      setStep(2);
      setError(null);
    } else if (isEmail) {
      setFormData(prev => ({ ...prev, identifier: cleanIdentifier }));
      setIdentifierType('email');
      setLoginMethod('password'); // Main priority for email
      setStep(2);
      setError(null);
    } else {
      setError('Please enter a valid Email or 10-digit Mobile number');
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    const endpoint = isSignup ? API_ENDPOINTS.REGISTER : API_ENDPOINTS.LOGIN;
    
    // Construct payload based on login method
    let payload;
    if (isSignup) {
      payload = {
        firstName: formData.firstName,
        lastName: formData.lastName,
        mobile: formData.mobile,
        email: formData.email,
        gender: formData.gender,
        password: formData.password
      };
    } else {
      if (loginMethod === 'password') {
        payload = { email: formData.identifier, password: formData.password };
      } else {
        payload = { email: formData.identifier, otp: formData.otp };
      }
    }

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      // The auth endpoints must return JSON. If they return HTML (a PHP error page or a
      // redirect to an admin login screen) parsing would throw and get reported as a
      // network failure, which hides the real cause — so surface it explicitly instead.
      const raw = await response.text();
      let data;
      try {
        data = JSON.parse(raw);
      } catch {
        console.error('Auth API returned non-JSON response:', response.status, raw.slice(0, 300));
        setError(`Server error (${response.status}). The login service returned an unexpected response.`);
        return;
      }

      if (data.success || data.status === "success" || data.id || data.token || data.tokens) {
        const authToken = data.tokens?.access_token || data.token || data.access_token || "dummy-token";
        if (data.tokens || data.token || data.id || data.user) {
            authLogin(data.user || { firstName: formData.firstName || formData.identifier?.split('@')[0] }, authToken);
        }

        if (isSignup) {
          toast.success('Account created successfully! Please log in.');
          setIsSignup(false);
          setStep(1);
        } else {
          toast.success('Login successful!');
          navigate(redirectPath); 
        }
      } else {
        // If it's a mocked OTP step and the backend doesn't support it yet, show a nice message
        if (loginMethod === 'otp' && data.error && data.error.includes("password")) {
            setError('OTP verification is not yet supported by the backend. Please use password if possible.');
        } else {
            setError(data.message || data.error || 'Authentication failed. Please try again.');
        }
      }
    } catch (err) {
      console.error("Auth API Error:", err);
      setError('Could not reach the server. Please check your connection and try again.');
    } finally {
      setLoading(false);
    }
  };

  const toggleForm = () => {
    setIsSignup(!isSignup);
    setError(null);
    setStep(1);
    setShowPassword(false);
    setFormData({
      identifier: '', otp: '', firstName: '', lastName: '', email: '', mobile: '', gender: '', password: ''
    });
  };

  // Render Login Flow based on steps
  const renderLoginFlow = () => {
    if (step === 1) {
      return (
        <form onSubmit={handleContinue}>
          <div className="form-floating mb-4">
            <input 
              type="text" 
              name="identifier" 
              className="form-control login-form-input" 
              id="identifier" 
              placeholder="Enter Email or Mobile number" 
              value={formData.identifier} 
              onChange={handleInputChange} 
              autoFocus
              required 
            />
            <label htmlFor="identifier">Enter Email or Mobile number</label>
          </div>
          
          <p className="text-muted small mb-4">
            By continuing, you agree to Printmont's{' '}
            <Link to="/policy/terms" className="text-primary text-decoration-none fw-medium">Terms of Use</Link> and{' '}
            <Link to="/policy/privacy" className="text-primary text-decoration-none fw-medium">Privacy Policy</Link>.
          </p>

          <button type="submit" className="btn w-100 fw-bold py-3 text-white rounded-3 mb-4 login-btn-primary" style={{ background: 'rgb(251, 100, 27)', border: 'none' }}>
            Continue
          </button>
        </form>
      );
    }

    if (step === 2) {
      return (
        <form onSubmit={handleSubmit}>
          <div className="mb-4 d-flex align-items-center justify-content-between pb-2 border-bottom">
            <div>
              <span className="text-muted small d-block" style={{ fontSize: '12px' }}>{loginMethod === 'otp' ? 'Sending OTP to' : 'Logging in as'}</span>
              <strong className="text-dark">{formData.identifier}</strong>
            </div>
            <button type="button" className="btn btn-sm btn-link text-decoration-none fw-semibold" onClick={() => { setStep(1); setError(null); }}>
              Change
            </button>
          </div>

          {loginMethod === 'password' ? (
            <>
              <div className="form-floating mb-3 position-relative">
                <input
                  type={showPassword ? 'text' : 'password'}
                  name="password"
                  className="form-control login-form-input"
                  id="password"
                  placeholder="Enter Password"
                  value={formData.password}
                  onChange={handleInputChange}
                  style={{ paddingRight: '3rem' }}
                  autoFocus
                  required
                />
                <label htmlFor="password">Enter Password</label>
                <button
                  type="button"
                  className="btn btn-link p-0 text-muted position-absolute top-50 end-0 translate-middle-y me-3"
                  style={{ zIndex: 5, lineHeight: 0 }}
                  onClick={() => setShowPassword(!showPassword)}
                  aria-label={showPassword ? 'Hide password' : 'Show password'}
                  title={showPassword ? 'Hide password' : 'Show password'}
                >
                  {showPassword ? <FaEyeSlash size={18} /> : <FaEye size={18} />}
                </button>
              </div>
              <div className="d-flex justify-content-between align-items-center mb-4">
                <Link to="/forgot-password" className="text-primary text-decoration-none small fw-medium">
                  Forgot Password?
                </Link>
                {identifierType === 'email' && (
                  <button type="button" className="btn btn-link text-decoration-none p-0 small fw-medium" onClick={() => { setLoginMethod('otp'); setError(null); }}>
                    Login with OTP instead
                  </button>
                )}
              </div>
            </>
          ) : (
            <>
              <div className="form-floating mb-3">
                <input 
                  type="text" 
                  name="otp" 
                  className="form-control login-form-input" 
                  id="otp" 
                  placeholder="Enter OTP" 
                  value={formData.otp} 
                  onChange={handleInputChange} 
                  autoFocus
                  required 
                />
                <label htmlFor="otp">Enter 6-digit OTP</label>
              </div>
              {identifierType === 'email' && (
                <div className="text-end mb-4">
                  <button type="button" className="btn btn-link text-decoration-none p-0 small fw-medium" onClick={() => { setLoginMethod('password'); setError(null); }}>
                    Login with Password instead
                  </button>
                </div>
              )}
            </>
          )}

          <button type="submit" className="btn w-100 fw-bold py-3 text-white rounded-3 mb-4 login-btn-primary" disabled={loading} style={{ background: 'rgb(251, 100, 27)', border: 'none' }}>
            {loading ? 'Please wait...' : (loginMethod === 'otp' ? 'Verify & Login' : 'Login')}
          </button>
        </form>
      );
    }
  };

  return (
    <div className="login-wrapper">
      <div className="login-card row m-0">
        
        {/* Left Side (Branding/Illustration) */}
        <div className="col-12 col-md-5 p-5 text-white login-left-pane d-flex flex-column" 
             style={{ background: isSignup ? 'linear-gradient(135deg, #0ba360 0%, #3cba92 100%)' : 'linear-gradient(135deg, rgb(11, 83, 161) 0%, rgb(41, 117, 240) 100%)' }}>
          
          <div className="z-1 d-flex flex-column h-100">
            <h2 className="fw-bolder display-6 mb-3">{isSignup ? "Looks like you're new here!" : "Welcome Back"}</h2>
            <p className="fs-5 opacity-75">
              {isSignup 
                ? "Sign up with your details to get started and unlock premium features." 
                : "Get access to your Orders, Wishlist, and personalized Recommendations."}
            </p>
            <img
              src="/PrintLogo.png"
              alt="Printmont"
              className="login-illustration mt-auto d-none d-md-block"
              style={{ filter: "brightness(0) invert(1)", maxHeight: "45px", width: "auto", objectFit: "contain", alignSelf: "flex-start" }}
            />
          </div>
        </div>

        {/* Right Side (Form) */}
        <div className="col-12 col-md-7 bg-white p-4 p-md-5 d-flex flex-column justify-content-center position-relative">
          <div className="w-100 mx-auto" style={{ maxWidth: '450px' }}>
            
            {error && <div className="alert alert-danger py-2 small">{error}</div>}

            {isSignup ? (
              <form onSubmit={handleSubmit}>
                <div className="row">
                  <div className="col-6 mb-3">
                    <div className="form-floating">
                      <input type="text" name="firstName" className="form-control login-form-input" id="firstName" placeholder="First Name" value={formData.firstName} onChange={handleInputChange} required />
                      <label htmlFor="firstName">First Name</label>
                    </div>
                  </div>
                  <div className="col-6 mb-3">
                    <div className="form-floating">
                      <input type="text" name="lastName" className="form-control login-form-input" id="lastName" placeholder="Last Name" value={formData.lastName} onChange={handleInputChange} required />
                      <label htmlFor="lastName">Last Name</label>
                    </div>
                  </div>
                </div>

                <div className="form-floating mb-3">
                  <input type="email" name="email" className="form-control login-form-input" id="email" placeholder="Email Address" value={formData.email} onChange={handleInputChange} required />
                  <label htmlFor="email">Email Address</label>
                </div>

                <div className="row">
                  <div className="col-6 mb-3">
                    <div className="form-floating">
                      <input type="tel" name="mobile" className="form-control login-form-input" id="mobile" placeholder="Mobile Number" value={formData.mobile} onChange={handleInputChange} required />
                      <label htmlFor="mobile">Mobile Number</label>
                    </div>
                  </div>
                  <div className="col-6 mb-3 d-flex align-items-center">
                    <select name="gender" className="form-select login-form-input py-3" value={formData.gender} onChange={handleInputChange} required>
                      <option value="" disabled>Select Gender</option>
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                </div>

                <div className="form-floating mb-4 position-relative">
                  <input
                    type={showPassword ? 'text' : 'password'}
                    name="password"
                    className="form-control login-form-input"
                    id="password"
                    placeholder="Password"
                    value={formData.password}
                    onChange={handleInputChange}
                    style={{ paddingRight: '3rem' }}
                    required
                  />
                  <label htmlFor="password">Password</label>
                  <button
                    type="button"
                    className="btn btn-link p-0 text-muted position-absolute top-50 end-0 translate-middle-y me-3"
                    style={{ zIndex: 5, lineHeight: 0 }}
                    onClick={() => setShowPassword(!showPassword)}
                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                    title={showPassword ? 'Hide password' : 'Show password'}
                  >
                    {showPassword ? <FaEyeSlash size={18} /> : <FaEye size={18} />}
                  </button>
                </div>

                <p className="text-muted small mb-4">
                  By continuing, you agree to Printmont's{' '}
                  <Link to="/policy/terms" className="text-primary text-decoration-none fw-medium">Terms of Use</Link> and{' '}
                  <Link to="/policy/privacy" className="text-primary text-decoration-none fw-medium">Privacy Policy</Link>.
                </p>

                <button type="submit" className="btn w-100 fw-bold py-3 text-white rounded-3 mb-4 login-btn-primary" disabled={loading} style={{ background: '#0ba360', border: 'none' }}>
                  {loading ? 'Please wait...' : 'Create Account'}
                </button>
              </form>
            ) : (
              // Login Flow Rendering
              renderLoginFlow()
            )}
            
            <div className="d-flex align-items-center mb-4">
              <hr className="flex-grow-1 text-muted opacity-25" />
              <span className="px-3 text-muted small fw-medium">OR</span>
              <hr className="flex-grow-1 text-muted opacity-25" />
            </div>

            <button type="button" className="btn w-100 py-3 rounded-3 fw-bold login-btn-secondary shadow-sm" onClick={toggleForm}>
              {isSignup ? 'Existing User? Log in' : 'New to Printmont? Create an account'}
            </button>
          </div>
        </div>

      </div>
    </div>
  );
};

export default Login;
