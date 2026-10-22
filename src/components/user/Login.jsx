import React, { useState, useEffect } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import { FaArrowLeft, FaRegUserCircle, FaEye, FaEyeSlash, FaSpinner, FaShieldAlt, FaTimes } from "react-icons/fa";
import toast from "react-hot-toast";
import { API_ENDPOINTS, resolveImageUrl, ASSET_URL, assetUrl } from "../../config/apiEndpoints";
import { useAuth } from "../../context/AuthContext";
import { updatePageSEO } from "../../utils/seo";
import "./Login.css";

const Login = ({ isSignupPage = false }) => {
  const navigate = useNavigate();
  const location = useLocation();
  const queryParams = new URLSearchParams(location.search);
  const redirectPath = queryParams.get("redirect") || "/";
  const { user, login: authLogin } = useAuth();

  const handleBack = () => {
    // If on signup or register, toggle back to login
    if (isSignup || location.pathname.startsWith('/signup') || location.pathname.startsWith('/register')) {
      handleToggleMode(false);
      return;
    }

    const lastPage = sessionStorage.getItem('pm_last_visited_page');
    const isProtected = (url) => url && (url.startsWith('/user') || url.includes('profile-edit'));

    if (window.history.length > 1) {
      navigate(-1);
      return;
    }

    if (
      lastPage &&
      lastPage !== location.pathname &&
      !lastPage.startsWith('/login') &&
      !lastPage.startsWith('/signup') &&
      !lastPage.startsWith('/register') &&
      !lastPage.startsWith('/forgot-password') &&
      (!isProtected(lastPage) || user)
    ) {
      navigate(lastPage, { replace: true });
      return;
    }

    navigate('/', { replace: true });
  };

  // Determine active mode from URL or prop
  const isUrlSignup = location.pathname === "/signup" || location.pathname === "/register" || isSignupPage;
  const [isSignup, setIsSignup] = useState(isUrlSignup);

  useEffect(() => {
    setIsSignup(location.pathname === "/signup" || location.pathname === "/register" || isSignupPage);
  }, [location.pathname, isSignupPage]);

  // Redirect if already authenticated
  useEffect(() => {
    if (user) {
      navigate(redirectPath, { replace: true });
    }
  }, [user, navigate, redirectPath]);

  // Dynamic Auth Settings from Backend (login-background.php)
  const [authSettings, setAuthSettings] = useState({
    desktop_auth_image: "/login_products_art.png",
    mobile_promo_banner: "/mobile_offer_banner.jpg",
    mobile_banner_enabled: "1",
    login_heading: "Login",
    login_subheading: "Get access to your Orders, Wishlist and Recommendations",
    signup_heading: "Looks like you're new here!",
    signup_subheading: "Sign up with your mobile number to get started",
  });

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: isSignup ? "Account Signup - Create an Account | Printmont" : "Account Login - Access Your Orders | Printmont",
      description: "Login or create an account on Printmont to track orders, manage addresses, and save personalized print creations.",
      keywords: "printmont login, printmont signup, account sign in, user register",
    });

    let isMounted = true;
    const fetchSettings = async () => {
      try {
        const res = await fetch(`${API_ENDPOINTS.AUTH_SETTINGS}?_t=${Date.now()}`);
        if (!res.ok) return;
        const json = await res.json();
        if (isMounted && json?.success && json?.data) {
          setAuthSettings((prev) => ({
            ...prev,
            ...json.data,
          }));
        }
      } catch (err) {
        console.warn("Could not fetch auth dynamic settings, using defaults.", err);
      }
    };

    fetchSettings();
    return () => {
      isMounted = false;
    };
  }, [isSignup]);

  // Form States
  const [formData, setFormData] = useState({
    identifier: "", // Email or Mobile for Login
    firstName: "",
    lastName: "",
    email: "",
    mobile: "",
    gender: "Male",
    password: "",
    confirmPassword: "",
  });

  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [loading, setLoading] = useState(false);

  // Flipkart-style Signup OTP Modal States
  const [showOtpModal, setShowOtpModal] = useState(false);
  const [registrationOtp, setRegistrationOtp] = useState("");
  const [resendTimer, setResendTimer] = useState(30);
  const [verifyingOtp, setVerifyingOtp] = useState(false);
  const [resendingOtp, setResendingOtp] = useState(false);

  // 30s Countdown Timer for OTP Resend
  useEffect(() => {
    let timer = null;
    if (showOtpModal && resendTimer > 0) {
      timer = setInterval(() => {
        setResendTimer((prev) => prev - 1);
      }, 1000);
    }
    return () => {
      if (timer) clearInterval(timer);
    };
  }, [showOtpModal, resendTimer]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleGenderChange = (val) => {
    setFormData((prev) => ({ ...prev, gender: val }));
  };

  // Helper to resolve images
  const resolveArtUrl = (imgPath, fallback) => {
    if (!imgPath) return fallback;
    if (imgPath.startsWith("http://") || imgPath.startsWith("https://")) return imgPath;
    if (imgPath.startsWith("/")) return imgPath;
    return assetUrl(imgPath);
  };

  // Switch between Login & Signup
  const handleToggleMode = (signupMode) => {
    setIsSignup(signupMode);
    setShowOtpModal(false);
    if (signupMode) {
      navigate("/signup", { replace: true });
    } else {
      navigate("/login", { replace: true });
    }
  };

  // Handle Form Submission (Initiates OTP for Signup, Logins directly for Login)
  const handleSubmit = async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    if (loading) return;

    if (isSignup) {
      // Signup Validations
      if (!formData.firstName.trim()) {
        toast.error("Please enter your First Name.");
        return;
      }
      if (!formData.lastName.trim()) {
        toast.error("Please enter your Last Name.");
        return;
      }
      if (!formData.mobile.trim() || !/^\d{10}$/.test(formData.mobile.trim())) {
        toast.error("Please enter a valid 10-digit mobile number.");
        return;
      }
      if (!formData.email.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email.trim())) {
        toast.error("Please enter a valid email address.");
        return;
      }
      if (!formData.password || formData.password.length < 6) {
        toast.error("Password must be at least 6 characters long.");
        return;
      }
      if (formData.password !== formData.confirmPassword) {
        toast.error("Password and Confirm Password do not match.");
        return;
      }

      // Step 1: Send Registration Verification OTP
      setLoading(true);
      try {
        const payload = {
          firstName: formData.firstName.trim(),
          lastName: formData.lastName.trim(),
          mobile: formData.mobile.trim(),
          email: formData.email.trim(),
          gender: formData.gender || "Male",
          password: formData.password,
        };

        const res = await fetch(API_ENDPOINTS.SEND_REGISTRATION_OTP, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });

        const json = await res.json();
        if (res.ok && json.success) {
          toast.success(json.message || "Verification OTP sent to your email!");
          setShowOtpModal(true);
          setResendTimer(30);
          setRegistrationOtp("");
        } else {
          toast.error(json.error || json.message || "Registration failed. Please check your details.");
        }
      } catch (err) {
        console.error("Signup error:", err);
        toast.error("Network error. Please check your connection.");
      } finally {
        setLoading(false);
      }
    } else {
      // Login Validations
      const cleanIdentifier = formData.identifier.trim();
      if (!cleanIdentifier) {
        toast.error("Please enter your Email or Mobile number.");
        return;
      }
      if (!formData.password) {
        toast.error("Please enter your Password.");
        return;
      }

      // Login API Call
      setLoading(true);
      try {
        const payload = {
          email: cleanIdentifier,
          identifier: cleanIdentifier,
          mobile: cleanIdentifier,
          password: formData.password,
        };

        const res = await fetch(API_ENDPOINTS.LOGIN, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });

        const json = await res.json();
        if (res.ok && json.success) {
          toast.success("Welcome back! Login successful.");
          const token = json.tokens?.access_token || json.tokens?.accessToken || json.tokens?.token || json.token || "session_token";
          authLogin(json.user, token, json.tokens?.refresh_token);
          navigate(redirectPath, { replace: true });
        } else {
          toast.error(json.error || json.message || "Invalid credentials. Please try again.");
        }
      } catch (err) {
        console.error("Login error:", err);
        toast.error("Could not reach server. Please try again.");
      } finally {
        setLoading(false);
      }
    }
  };

  // Step 2: Verify Registration OTP (Modal Action)
  const handleVerifyOtp = async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    const cleanOtp = registrationOtp.trim();
    if (!cleanOtp || cleanOtp.length !== 6) {
      toast.error("Please enter the complete 6-digit OTP code.");
      return;
    }

    setVerifyingOtp(true);
    try {
      const res = await fetch(API_ENDPOINTS.VERIFY_REGISTRATION_OTP, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          email: formData.email.trim(),
          otp: cleanOtp,
        }),
      });

      const json = await res.json();
      if (res.ok && json.success) {
        toast.success(json.message || "Account verified successfully! Welcome to Printmont.");
        const token = json.tokens?.access_token || json.tokens?.accessToken || json.tokens?.token || json.token || "session_token";
        if (json.user) {
          authLogin(json.user, token, json.tokens?.refresh_token);
        }
        setShowOtpModal(false);
        navigate(redirectPath, { replace: true });
      } else {
        toast.error(json.error || json.message || "Invalid or expired OTP code.");
      }
    } catch (err) {
      console.error("OTP verification error:", err);
      toast.error("Network error. Please try again.");
    } finally {
      setVerifyingOtp(false);
    }
  };

  // Resend OTP Action (Modal Action)
  const handleResendOtp = async () => {
    if (resendTimer > 0 || resendingOtp) return;
    setResendingOtp(true);
    try {
      const res = await fetch(API_ENDPOINTS.RESEND_REGISTRATION_OTP, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          email: formData.email.trim(),
        }),
      });

      const json = await res.json();
      if (res.ok && json.success) {
        toast.success(json.message || "A new 6-digit OTP code has been sent to your email.");
        setResendTimer(30);
        setRegistrationOtp("");
      } else {
        toast.error(json.error || json.message || "Could not resend OTP. Please try again.");
      }
    } catch (err) {
      console.error("Resend OTP error:", err);
      toast.error("Network error. Please try again.");
    } finally {
      setResendingOtp(false);
    }
  };

  return (
    <div className="auth-page-wrapper">
      {/* ========================================================
          📱 MOBILE VIEW (< 992px)
          Matches media_1791289193338.jpg & media_1791289206430.jpg
          ======================================================== */}
      <div className="d-block d-lg-none w-100">
        <div className="auth-mobile-container">
          {/* 1. Top Promotional Offer Banner (If enabled) */}
          {authSettings.mobile_banner_enabled !== "0" && (
            <div className="auth-mobile-offer-banner-wrap">
              <img
                src={resolveArtUrl(authSettings.mobile_promo_banner, "/mobile_offer_banner.jpg")}
                alt="Special Offer Sale"
                className="auth-mobile-offer-banner-img"
                onError={(e) => {
                  e.currentTarget.src = "/mobile_offer_banner.jpg";
                }}
              />
            </div>
          )}

          {/* 3. Mobile Form Body */}
          <div className="auth-mobile-body">
            <h1 className="auth-mobile-heading">
              {isSignup ? "Looks Like your new Here" : "Login"}
            </h1>
            <p className="auth-mobile-subheading">
              {isSignup
                ? "Create your account to get access to Orders, Wishlist and Recommendations"
                : "Get access to your Orders, Wishlist and Recommendations"}
            </p>

            {isSignup ? (
              /* Mobile Signup Form */
              <form onSubmit={handleSubmit}>
                <div className="auth-mobile-form-group">
                  <label className="auth-mobile-label">First Name</label>
                  <input
                    type="text"
                    name="firstName"
                    value={formData.firstName}
                    onChange={handleInputChange}
                    placeholder="Your first good name here*"
                    className="auth-mobile-input"
                    required
                  />
                </div>

                <div className="auth-mobile-form-group">
                  <label className="auth-mobile-label">Last Name</label>
                  <input
                    type="text"
                    name="lastName"
                    value={formData.lastName}
                    onChange={handleInputChange}
                    placeholder="Your last good name here*"
                    className="auth-mobile-input"
                    required
                  />
                </div>

                <div className="auth-mobile-form-group">
                  <label className="auth-mobile-label">Enter Mob no.</label>
                  <input
                    type="tel"
                    name="mobile"
                    maxLength={10}
                    value={formData.mobile}
                    onChange={handleInputChange}
                    placeholder="Enter your contact number here*"
                    className="auth-mobile-input"
                    required
                  />
                </div>

                <div className="auth-mobile-form-group">
                  <label className="auth-mobile-label">Enter E-mail.Id.</label>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleInputChange}
                    placeholder="Enter your email id here*"
                    className="auth-mobile-input"
                    required
                  />
                </div>

                <div className="auth-mobile-form-group">
                  <label className="auth-mobile-label">Your Gender.</label>
                  <div className="auth-gender-radio-group">
                    <label className="auth-radio-label">
                      <input
                        type="radio"
                        name="gender"
                        value="Male"
                        checked={formData.gender === "Male"}
                        onChange={() => handleGenderChange("Male")}
                      />
                      <span>Male</span>
                    </label>
                    <label className="auth-radio-label">
                      <input
                        type="radio"
                        name="gender"
                        value="Female"
                        checked={formData.gender === "Female"}
                        onChange={() => handleGenderChange("Female")}
                      />
                      <span>Female</span>
                    </label>
                  </div>
                </div>

                <div className="auth-mobile-form-group">
                  <label className="auth-mobile-label">Password</label>
                  <div className="auth-input-password-wrap">
                    <input
                      type={showPassword ? "text" : "password"}
                      name="password"
                      value={formData.password}
                      onChange={handleInputChange}
                      placeholder="Enter your password here*"
                      className="auth-mobile-input"
                      required
                    />
                    <button
                      type="button"
                      className="auth-password-toggle-btn"
                      onClick={() => setShowPassword(!showPassword)}
                    >
                      {showPassword ? <FaEyeSlash size={15} /> : <FaEye size={15} />}
                    </button>
                  </div>
                </div>

                <div className="auth-mobile-form-group">
                  <label className="auth-mobile-label">Confirm Password</label>
                  <div className="auth-input-password-wrap">
                    <input
                      type={showConfirmPassword ? "text" : "password"}
                      name="confirmPassword"
                      value={formData.confirmPassword}
                      onChange={handleInputChange}
                      placeholder="Enter your Confirm password here*"
                      className="auth-mobile-input"
                      required
                    />
                    <button
                      type="button"
                      className="auth-password-toggle-btn"
                      onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                    >
                      {showConfirmPassword ? <FaEyeSlash size={15} /> : <FaEye size={15} />}
                    </button>
                  </div>
                </div>

                <div className="auth-switch-prompt">
                  Already have an account?{" "}
                  <span
                    className="auth-switch-link"
                    onClick={() => handleToggleMode(false)}
                  >
                    Login here
                  </span>
                </div>
              </form>
            ) : (
              /* Mobile Login Form */
              <form onSubmit={handleSubmit}>
                <div className="auth-mobile-form-group">
                  <label className="auth-mobile-label">Enter Email / Mobile Number.</label>
                  <input
                    type="text"
                    name="identifier"
                    value={formData.identifier}
                    onChange={handleInputChange}
                    placeholder="Enter your email or mobile number*"
                    className="auth-mobile-input"
                    required
                  />
                </div>

                <div className="auth-mobile-form-group mb-2">
                  <label className="auth-mobile-label">Enter Password.</label>
                  <div className="auth-input-password-wrap">
                    <input
                      type={showPassword ? "text" : "password"}
                      name="password"
                      value={formData.password}
                      onChange={handleInputChange}
                      placeholder="Enter your password*"
                      className="auth-mobile-input"
                      required
                    />
                    <button
                      type="button"
                      className="auth-password-toggle-btn"
                      onClick={() => setShowPassword(!showPassword)}
                    >
                      {showPassword ? <FaEyeSlash size={15} /> : <FaEye size={15} />}
                    </button>
                  </div>
                </div>

                <div className="mb-4">
                  <Link to="/forgot-password" className="auth-forget-link">
                    Forget Password?
                  </Link>
                </div>

                <div className="auth-switch-prompt">
                  New to Printmont?{" "}
                  <span
                    className="auth-switch-link"
                    onClick={() => handleToggleMode(true)}
                  >
                    Create an account
                  </span>
                </div>

                <div className="auth-terms-notice">
                  By continuing, you agree to Printmont's{" "}
                  <Link to="/policy/terms">Terms of Use</Link> and{" "}
                  <Link to="/policy/privacy">Privacy Policy</Link>.
                </div>

                <div className="text-center mt-3 mb-2">
                  <button
                    type="button"
                    onClick={handleBack}
                    className="btn btn-link text-decoration-none p-0 text-muted"
                    style={{ fontSize: "13.5px", fontWeight: "500" }}
                  >
                    ← Back to Store
                  </button>
                </div>
              </form>
            )}
          </div>

          {/* 4. Mobile Fixed Bottom Button */}
          <div className="auth-mobile-bottom-bar">
            <button
              type="button"
              onClick={handleSubmit}
              disabled={loading}
              className="auth-mobile-btn-submit"
            >
              {loading ? (
                <>
                  <FaSpinner className="fa-spin me-1" />
                  <span>Processing...</span>
                </>
              ) : isSignup ? (
                "Continue"
              ) : (
                "Login"
              )}
            </button>
          </div>
        </div>
      </div>

      {/* ========================================================
          🖥️ DESKTOP VIEW (>= 992px)
          Matches media_1791289217870.jpg & media_1791289226247.jpg
          ======================================================== */}
      <div className="d-none d-lg-block w-100">
        <div className="auth-desktop-wrapper">
          <div className="d-flex align-items-center justify-content-between mb-3" style={{ width: '960px', maxWidth: '100%' }}>
            <button
              type="button"
              onClick={handleBack}
              className="btn btn-link text-decoration-none p-0 d-flex align-items-center text-secondary"
              style={{ fontSize: '14px', fontWeight: '500' }}
              title="Back to Store"
            >
              <FaArrowLeft size={13} className="me-2" />
              <span>Back to Store</span>
            </button>
          </div>
          <div className={`auth-desktop-card ${isSignup ? "signup-card" : "login-card"}`} style={{ position: 'relative' }}>
            {/* Desktop Top-right Close Button */}
            <button
              type="button"
              className="auth-desktop-close-btn"
              onClick={handleBack}
              title="Close & Go Back"
              aria-label="Close and go back"
            >
              <FaTimes size={15} />
            </button>
            {/* Left Blue Panel */}
            <div className="auth-desktop-left-panel">
              <div className="auth-dt-left-content">
                <h2 className="auth-dt-title">
                  {isSignup ? authSettings.signup_heading : authSettings.login_heading}
                </h2>
                <p className="auth-dt-subtitle">
                  {isSignup ? authSettings.signup_subheading : authSettings.login_subheading}
                </p>
              </div>

              {/* Bottom Products Illustration Graphic */}
              <div className="auth-dt-products-art-wrap">
                <img
                  src={resolveArtUrl(authSettings.desktop_auth_image, "/login_products_art.png")}
                  alt="Custom Printmont Merchandise Lineup"
                  className="auth-dt-products-art"
                  onError={(e) => {
                    e.currentTarget.src = "/login_products_art.png";
                  }}
                />
              </div>
            </div>

            {/* Right White Form Panel */}
            <div className="auth-desktop-right-panel">
              {isSignup ? (
                /* Desktop Signup Form (2-Column Grid) */
                <form onSubmit={handleSubmit} className="d-flex flex-column h-100 justify-content-between">
                  <div>
                    <div className="auth-dt-grid-2">
                      <div className="auth-dt-form-group">
                        <label className="auth-dt-label">First Name</label>
                        <input
                          type="text"
                          name="firstName"
                          value={formData.firstName}
                          onChange={handleInputChange}
                          placeholder="Enter first name"
                          className="auth-dt-input"
                          required
                        />
                      </div>
                      <div className="auth-dt-form-group">
                        <label className="auth-dt-label">Last Name</label>
                        <input
                          type="text"
                          name="lastName"
                          value={formData.lastName}
                          onChange={handleInputChange}
                          placeholder="Enter last name"
                          className="auth-dt-input"
                          required
                        />
                      </div>
                    </div>

                    <div className="auth-dt-grid-2">
                      <div className="auth-dt-form-group">
                        <label className="auth-dt-label">Enter Mob no.</label>
                        <input
                          type="tel"
                          name="mobile"
                          maxLength={10}
                          value={formData.mobile}
                          onChange={handleInputChange}
                          placeholder="Enter your mobile number"
                          className="auth-dt-input"
                          required
                        />
                      </div>
                      <div className="auth-dt-form-group">
                        <label className="auth-dt-label">Enter E-mail Id</label>
                        <input
                          type="email"
                          name="email"
                          value={formData.email}
                          onChange={handleInputChange}
                          placeholder="Enter your email id"
                          className="auth-dt-input"
                          required
                        />
                      </div>
                    </div>

                    <div className="auth-dt-form-group">
                      <label className="auth-dt-label">Your Gender :</label>
                      <div className="auth-gender-radio-group">
                        <label className="auth-radio-label">
                          <input
                            type="radio"
                            name="gender"
                            value="Male"
                            checked={formData.gender === "Male"}
                            onChange={() => handleGenderChange("Male")}
                          />
                          <span>Male</span>
                        </label>
                        <label className="auth-radio-label">
                          <input
                            type="radio"
                            name="gender"
                            value="Female"
                            checked={formData.gender === "Female"}
                            onChange={() => handleGenderChange("Female")}
                          />
                          <span>Female</span>
                        </label>
                      </div>
                    </div>

                    <div className="auth-dt-grid-2">
                      <div className="auth-dt-form-group">
                        <label className="auth-dt-label">Password</label>
                        <div className="auth-input-password-wrap">
                          <input
                            type={showPassword ? "text" : "password"}
                            name="password"
                            value={formData.password}
                            onChange={handleInputChange}
                            placeholder="Enter your password"
                            className="auth-dt-input"
                            required
                          />
                          <button
                            type="button"
                            className="auth-password-toggle-btn"
                            onClick={() => setShowPassword(!showPassword)}
                          >
                            {showPassword ? <FaEyeSlash size={14} /> : <FaEye size={14} />}
                          </button>
                        </div>
                      </div>
                      <div className="auth-dt-form-group">
                        <label className="auth-dt-label">Confirm Password</label>
                        <div className="auth-input-password-wrap">
                          <input
                            type={showConfirmPassword ? "text" : "password"}
                            name="confirmPassword"
                            value={formData.confirmPassword}
                            onChange={handleInputChange}
                            placeholder="Enter your confirm password"
                            className="auth-dt-input"
                            required
                          />
                          <button
                            type="button"
                            className="auth-password-toggle-btn"
                            onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                          >
                            {showConfirmPassword ? <FaEyeSlash size={14} /> : <FaEye size={14} />}
                          </button>
                        </div>
                      </div>
                    </div>

                    <button
                      type="submit"
                      disabled={loading}
                      className="auth-dt-btn-submit"
                    >
                      {loading ? (
                        <>
                          <FaSpinner className="fa-spin me-1" />
                          <span>Signing up...</span>
                        </>
                      ) : (
                        "Signup"
                      )}
                    </button>
                  </div>

                  <div className="auth-dt-switch-link-wrap">
                    <span
                      className="auth-dt-switch-link"
                      onClick={() => handleToggleMode(false)}
                    >
                      Existing User? Log in
                    </span>
                  </div>
                </form>
              ) : (
                /* Desktop Login Form */
                <form onSubmit={handleSubmit} className="d-flex flex-column h-100 justify-content-between">
                  <div>
                    <div className="auth-dt-form-group mb-3">
                      <label className="auth-dt-label">Enter Email/Mobile Number</label>
                      <input
                        type="text"
                        name="identifier"
                        value={formData.identifier}
                        onChange={handleInputChange}
                        placeholder="Enter your email or mobile number"
                        className="auth-dt-input"
                        required
                      />
                    </div>

                    <div className="auth-dt-form-group mb-2">
                      <label className="auth-dt-label">Enter Password</label>
                      <div className="auth-input-password-wrap">
                        <input
                          type={showPassword ? "text" : "password"}
                          name="password"
                          value={formData.password}
                          onChange={handleInputChange}
                          placeholder="Enter your password"
                          className="auth-dt-input"
                          required
                        />
                        <button
                          type="button"
                          className="auth-password-toggle-btn"
                          onClick={() => setShowPassword(!showPassword)}
                        >
                          {showPassword ? <FaEyeSlash size={14} /> : <FaEye size={14} />}
                        </button>
                      </div>
                    </div>

                    <div className="mb-3">
                      <Link to="/forgot-password" className="auth-forget-link" style={{ fontSize: "13.5px" }}>
                        Forget Password?
                      </Link>
                    </div>

                    <button
                      type="submit"
                      disabled={loading}
                      className="auth-dt-btn-submit"
                    >
                      {loading ? (
                        <>
                          <FaSpinner className="fa-spin me-1" />
                          <span>Logging in...</span>
                        </>
                      ) : (
                        "Log in"
                      )}
                    </button>
                  </div>

                  <div className="auth-dt-switch-link-wrap">
                    <span
                      className="auth-dt-switch-link"
                      onClick={() => handleToggleMode(true)}
                    >
                      New Printmont? Create an account
                    </span>
                  </div>
                </form>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* ========================================================
          🔐 FLIPKART-STYLE SIGNUP OTP VERIFICATION MODAL
          ======================================================== */}
      {showOtpModal && (
        <div
          className="auth-otp-modal-backdrop"
          onClick={(e) => {
            if (e.target === e.currentTarget) {
              setShowOtpModal(false);
            }
          }}
        >
          <div className="auth-otp-modal-dialog" onClick={(e) => e.stopPropagation()}>
            <div className="auth-otp-modal-header">
              <div className="auth-otp-header-left">
                <div className="auth-otp-badge-icon">
                  <FaShieldAlt />
                </div>
                <h3 className="auth-otp-modal-title">Verify Your Account</h3>
              </div>
              <button
                type="button"
                className="auth-otp-modal-close-btn"
                onClick={() => setShowOtpModal(false)}
                aria-label="Close"
              >
                <FaTimes size={16} />
              </button>
            </div>

            <div className="auth-otp-modal-body">
              <p className="auth-otp-modal-subtitle">
                Please enter the 6-digit verification code sent to:
              </p>

              <div className="auth-otp-target-pill">
                <span>{formData.email}</span>
                {formData.mobile && <span> • +91 {formData.mobile}</span>}
              </div>

              <form onSubmit={handleVerifyOtp}>
                <div className="auth-otp-input-container">
                  <input
                    type="text"
                    inputMode="numeric"
                    autoComplete="one-time-code"
                    maxLength={6}
                    value={registrationOtp}
                    onChange={(e) => {
                      const val = e.target.value.replace(/[^0-9]/g, "");
                      setRegistrationOtp(val);
                    }}
                    placeholder="••••••"
                    className="auth-otp-input-large"
                    autoFocus
                    required
                  />
                </div>

                <div className="auth-otp-resend-row">
                  {resendTimer > 0 ? (
                    <span>
                      Didn't receive OTP? Resend in <strong>{resendTimer}s</strong>
                    </span>
                  ) : (
                    <>
                      <span>Didn't receive code?</span>{" "}
                      <button
                        type="button"
                        className="auth-otp-resend-btn"
                        onClick={handleResendOtp}
                        disabled={resendingOtp}
                      >
                        {resendingOtp ? "Sending..." : "Resend OTP"}
                      </button>
                    </>
                  )}
                </div>

                <button
                  type="submit"
                  className="auth-otp-verify-btn"
                  disabled={verifyingOtp || registrationOtp.length !== 6}
                >
                  {verifyingOtp ? (
                    <>
                      <FaSpinner className="fa-spin" />
                      <span>Verifying & Creating Account...</span>
                    </>
                  ) : (
                    "Verify & Create Account"
                  )}
                </button>

                <div className="auth-otp-edit-btn-wrap">
                  <button
                    type="button"
                    className="auth-otp-edit-btn"
                    onClick={() => setShowOtpModal(false)}
                  >
                    ← Wrong email or phone? Edit details
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Login;

