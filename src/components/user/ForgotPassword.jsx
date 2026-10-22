import React, { useState, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import { FaArrowLeft, FaEye, FaEyeSlash, FaSpinner, FaCheckCircle, FaTimes } from "react-icons/fa";
import toast from "react-hot-toast";
import { API_ENDPOINTS, ASSET_URL, assetUrl } from "../../config/apiEndpoints";
import { updatePageSEO } from "../../utils/seo";
import "./Login.css";

const ForgotPassword = () => {
  const navigate = useNavigate();

  const handleBack = () => {
    if (step === 2) {
      setStep(1);
      setError(null);
      setSuccessMessage("");
      return;
    }
    navigate('/login');
  };

  const [step, setStep] = useState(1); // Step 1: Enter Email, Step 2: Enter OTP & New Password
  const [email, setEmail] = useState("");
  const [otp, setOtp] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [successMessage, setSuccessMessage] = useState("");

  // Dynamic Auth Settings from Backend (login-background.php)
  const [authSettings, setAuthSettings] = useState({
    desktop_auth_image: "/login_products_art.png",
    mobile_promo_banner: "/mobile_offer_banner.jpg",
    mobile_banner_enabled: "1",
  });

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: step === 1 
        ? "Forgot Password - Recover Account | Printmont" 
        : "Reset Password - Set New Password | Printmont",
      description: "Recover and reset your Printmont account password safely with one-time verification.",
      keywords: "printmont forgot password, reset password, account recovery",
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
  }, [step]);

  // Helper to resolve image paths
  const resolveArtUrl = (imgPath, fallback) => {
    if (!imgPath) return fallback;
    if (imgPath.startsWith("http://") || imgPath.startsWith("https://")) return imgPath;
    if (imgPath.startsWith("/")) return imgPath;
    return assetUrl(imgPath);
  };

  // Step 1: Send OTP to Email
  const handleSendOtp = async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    if (!email.trim()) {
      setError("Please enter your registered email address.");
      toast.error("Please enter your email address.");
      return;
    }

    setLoading(true);
    setError(null);
    setSuccessMessage("");

    try {
      const endpoint = API_ENDPOINTS.FORGOT_PASSWORD;
      const response = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: email.trim() }),
      });

      const data = await response.json();

      if (data.success || data.status === "success") {
        setSuccessMessage("An OTP code has been sent to your email address.");
        toast.success("OTP sent to your email!");
        setStep(2);
      } else {
        const msg = data.message || data.error || "Failed to send OTP. Please check your email.";
        setError(msg);
        toast.error(msg);
      }
    } catch (err) {
      console.error("Forgot Password Error:", err);
      setError("Network error. Please try again later.");
      toast.error("Network error. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  // Step 2: Verify OTP & Set New Password
  const handleResetPassword = async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    setError(null);
    setSuccessMessage("");

    if (!otp.trim() || otp.trim().length < 4) {
      setError("Please enter the 6-digit OTP received on your email.");
      toast.error("Please enter a valid OTP code.");
      return;
    }

    if (!newPassword || newPassword.length < 6) {
      setError("Password must be at least 6 characters long.");
      toast.error("Password must be at least 6 characters.");
      return;
    }

    if (newPassword !== confirmPassword) {
      setError("Passwords do not match.");
      toast.error("Passwords do not match.");
      return;
    }

    setLoading(true);

    try {
      const endpoint = API_ENDPOINTS.RESET_PASSWORD;
      const response = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          email: email.trim(),
          otp: otp.trim(),
          new_password: newPassword,
        }),
      });

      const data = await response.json();

      if (data.success || data.status === "success") {
        toast.success("Password reset successfully! Redirecting to login...");
        setSuccessMessage("Password reset successfully! Please login with your new password.");
        setTimeout(() => {
          navigate("/login");
        }, 1500);
      } else {
        const msg = data.message || data.error || "Invalid OTP or failed to reset password.";
        setError(msg);
        toast.error(msg);
      }
    } catch (err) {
      console.error("Reset Password Error:", err);
      setError("Network error. Please try again.");
      toast.error("Network error. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  const handleMasterSubmit = (e) => {
    if (step === 1) {
      handleSendOtp(e);
    } else {
      handleResetPassword(e);
    }
  };

  return (
    <div className="auth-page-wrapper">
      {/* ========================================================
          📱 MOBILE VIEW (< 992px)
          ======================================================== */}
      <div className="d-block d-lg-none w-100">
        <div className="auth-mobile-container">
          {/* Top Promotional Offer Banner (If enabled) */}
          {authSettings.mobile_banner_enabled !== "0" && (
            <div className="auth-mobile-offer-banner-wrap">
              <img
                src={resolveArtUrl(authSettings.mobile_promo_banner, "/mobile_offer_banner.jpg")}
                alt="Printmont Offers"
                className="auth-mobile-offer-banner-img"
                onError={(e) => {
                  e.currentTarget.src = "/mobile_offer_banner.jpg";
                }}
              />
            </div>
          )}

          {/* Mobile Form Body */}
          <div className="auth-mobile-body">
            <h1 className="auth-mobile-heading">
              {step === 1 ? "Forgot Password" : "Reset Password"}
            </h1>
            <p className="auth-mobile-subheading">
              {step === 1
                ? "Enter your email ID to receive a verification OTP code"
                : "Enter the OTP sent to your email and set your new password"}
            </p>

            {error && (
              <div className="alert alert-danger py-2 px-3 mb-3 small" style={{ borderRadius: "4px" }}>
                {error}
              </div>
            )}
            {successMessage && (
              <div className="alert alert-success py-2 px-3 mb-3 small" style={{ borderRadius: "4px" }}>
                {successMessage}
              </div>
            )}

            {step === 1 ? (
              /* Mobile Step 1 Form */
              <form onSubmit={handleSendOtp}>
                <div className="auth-mobile-form-group">
                  <label className="auth-mobile-label">Enter Email Address</label>
                  <input
                    type="email"
                    name="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="Enter your registered email ID*"
                    className="auth-mobile-input"
                    autoFocus
                    required
                  />
                </div>

                <div className="auth-switch-prompt">
                  Remember your password?{" "}
                  <Link to="/login" className="auth-switch-link">
                    Login here
                  </Link>
                </div>
              </form>
            ) : (
              /* Mobile Step 2 Form */
              <form onSubmit={handleResetPassword}>
                <div className="auth-mobile-form-group">
                  <label className="auth-mobile-label">Email Address</label>
                  <input
                    type="email"
                    value={email}
                    disabled
                    className="auth-mobile-input bg-light"
                    style={{ color: "#64748b" }}
                  />
                </div>

                <div className="auth-mobile-form-group">
                  <label className="auth-mobile-label">Enter 6-Digit OTP</label>
                  <input
                    type="text"
                    name="otp"
                    maxLength={6}
                    value={otp}
                    onChange={(e) => setOtp(e.target.value)}
                    placeholder="Enter 6-digit OTP from email*"
                    className="auth-mobile-input"
                    autoFocus
                    required
                  />
                </div>

                <div className="auth-mobile-form-group">
                  <label className="auth-mobile-label">New Password</label>
                  <div className="auth-input-password-wrap">
                    <input
                      type={showPassword ? "text" : "password"}
                      name="newPassword"
                      value={newPassword}
                      onChange={(e) => setNewPassword(e.target.value)}
                      placeholder="Enter minimum 6 characters*"
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
                  <label className="auth-mobile-label">Confirm New Password</label>
                  <div className="auth-input-password-wrap">
                    <input
                      type={showConfirmPassword ? "text" : "password"}
                      name="confirmPassword"
                      value={confirmPassword}
                      onChange={(e) => setConfirmPassword(e.target.value)}
                      placeholder="Re-enter new password*"
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

                <div className="text-center my-3">
                  <button
                    type="button"
                    onClick={() => {
                      setStep(1);
                      setError(null);
                      setSuccessMessage("");
                    }}
                    className="btn btn-link text-decoration-none small text-muted p-0"
                    style={{ fontSize: "13px" }}
                  >
                    ← Change Email or Resend OTP
                  </button>
                </div>

                <div className="auth-switch-prompt">
                  Back to{" "}
                  <Link to="/login" className="auth-switch-link">
                    Login
                  </Link>
                </div>
              </form>
            )}
          </div>

          {/* Mobile Fixed Bottom Action Button */}
          <div className="auth-mobile-bottom-bar">
            <button
              type="button"
              onClick={handleMasterSubmit}
              disabled={loading}
              className="auth-mobile-btn-submit"
            >
              {loading ? (
                <>
                  <FaSpinner className="fa-spin me-1" />
                  <span>{step === 1 ? "Sending OTP..." : "Resetting Password..."}</span>
                </>
              ) : step === 1 ? (
                "Send OTP Code"
              ) : (
                "Reset Password"
              )}
            </button>
          </div>
        </div>
      </div>

      {/* ========================================================
          🖥️ DESKTOP VIEW (>= 992px)
          Uniform 960px x 580px min-height, 50-50 split, Theme Blue
          ======================================================== */}
      <div className="d-none d-lg-block w-100">
        <div className="auth-desktop-wrapper">
          <div className="d-flex align-items-center justify-content-between mb-3" style={{ width: '960px', maxWidth: '100%' }}>
            <button
              type="button"
              onClick={handleBack}
              className="btn btn-link text-decoration-none p-0 d-flex align-items-center text-secondary"
              style={{ fontSize: '14px', fontWeight: '500' }}
              title="Back to Login"
            >
              <FaArrowLeft size={13} className="me-2" />
              <span>Back to Login</span>
            </button>
          </div>
          <div className="auth-desktop-card login-card" style={{ position: 'relative' }}>
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
            {/* Left Theme Panel (50% Equal Width) */}
            <div className="auth-desktop-left-panel">
              <div className="auth-dt-left-content">
                <h2 className="auth-dt-title">
                  {step === 1 ? "Forgot Password?" : "Reset Password"}
                </h2>
                <p className="auth-dt-subtitle">
                  {step === 1
                    ? "Enter your registered email address to receive a secure OTP code."
                    : "Enter the OTP code sent to your email and choose your new password."}
                </p>
              </div>

              {/* Bottom Products Illustration Graphic */}
              <div className="auth-dt-products-art-wrap">
                <img
                  src={resolveArtUrl(authSettings.desktop_auth_image, "/login_products_art.png")}
                  alt="Printmont Custom Merchandise"
                  className="auth-dt-products-art"
                  onError={(e) => {
                    e.currentTarget.src = "/login_products_art.png";
                  }}
                />
              </div>
            </div>

            {/* Right White Form Panel (50% Equal Width) */}
            <div className="auth-desktop-right-panel">
              {step === 1 ? (
                /* Step 1: Request OTP Form */
                <form onSubmit={handleSendOtp} className="d-flex flex-column h-100 justify-content-between">
                  <div>
                    <h3 className="auth-mobile-heading text-dark mb-3" style={{ fontSize: "20px" }}>
                      Recover Account
                    </h3>

                    {error && (
                      <div className="alert alert-danger py-2 px-3 mb-3 small" style={{ borderRadius: "4px" }}>
                        {error}
                      </div>
                    )}
                    {successMessage && (
                      <div className="alert alert-success py-2 px-3 mb-3 small" style={{ borderRadius: "4px" }}>
                        {successMessage}
                      </div>
                    )}

                    <div className="auth-dt-form-group mb-4">
                      <label className="auth-dt-label">Enter Email Address</label>
                      <input
                        type="email"
                        name="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        placeholder="Enter your registered email address"
                        className="auth-dt-input"
                        autoFocus
                        required
                      />
                    </div>

                    <button
                      type="submit"
                      disabled={loading}
                      className="auth-dt-btn-submit"
                    >
                      {loading ? (
                        <>
                          <FaSpinner className="fa-spin me-1" />
                          <span>Sending OTP...</span>
                        </>
                      ) : (
                        "Send OTP Code"
                      )}
                    </button>
                  </div>

                  <div className="auth-dt-switch-link-wrap">
                    <Link to="/login" className="auth-dt-switch-link">
                      ← Back to Login
                    </Link>
                  </div>
                </form>
              ) : (
                /* Step 2: Verify OTP & Reset Password Form */
                <form onSubmit={handleResetPassword} className="d-flex flex-column h-100 justify-content-between">
                  <div>
                    <h3 className="auth-mobile-heading text-dark mb-3" style={{ fontSize: "20px" }}>
                      Set New Password
                    </h3>

                    {error && (
                      <div className="alert alert-danger py-2 px-3 mb-3 small" style={{ borderRadius: "4px" }}>
                        {error}
                      </div>
                    )}
                    {successMessage && (
                      <div className="alert alert-success py-2 px-3 mb-3 small" style={{ borderRadius: "4px" }}>
                        {successMessage}
                      </div>
                    )}

                    <div className="auth-dt-form-group mb-2">
                      <label className="auth-dt-label">Email Address</label>
                      <input
                        type="email"
                        value={email}
                        disabled
                        className="auth-dt-input bg-light"
                        style={{ color: "#64748b" }}
                      />
                    </div>

                    <div className="auth-dt-form-group mb-2">
                      <label className="auth-dt-label">Enter 6-Digit OTP</label>
                      <input
                        type="text"
                        name="otp"
                        maxLength={6}
                        value={otp}
                        onChange={(e) => setOtp(e.target.value)}
                        placeholder="Enter 6-digit OTP from email"
                        className="auth-dt-input"
                        autoFocus
                        required
                      />
                    </div>

                    <div className="auth-dt-form-group mb-2">
                      <label className="auth-dt-label">New Password</label>
                      <div className="auth-input-password-wrap">
                        <input
                          type={showPassword ? "text" : "password"}
                          name="newPassword"
                          value={newPassword}
                          onChange={(e) => setNewPassword(e.target.value)}
                          placeholder="Enter new password (min 6 chars)"
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

                    <div className="auth-dt-form-group mb-2">
                      <label className="auth-dt-label">Confirm New Password</label>
                      <div className="auth-input-password-wrap">
                        <input
                          type={showConfirmPassword ? "text" : "password"}
                          name="confirmPassword"
                          value={confirmPassword}
                          onChange={(e) => setConfirmPassword(e.target.value)}
                          placeholder="Confirm new password"
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

                    <button
                      type="submit"
                      disabled={loading}
                      className="auth-dt-btn-submit"
                    >
                      {loading ? (
                        <>
                          <FaSpinner className="fa-spin me-1" />
                          <span>Resetting Password...</span>
                        </>
                      ) : (
                        "Reset Password"
                      )}
                    </button>

                    <div className="text-center mt-3">
                      <button
                        type="button"
                        onClick={() => {
                          setStep(1);
                          setError(null);
                          setSuccessMessage("");
                        }}
                        className="btn btn-link text-decoration-none small text-muted p-0"
                        style={{ fontSize: "13px" }}
                      >
                        ← Change Email or Resend OTP
                      </button>
                    </div>
                  </div>

                  <div className="auth-dt-switch-link-wrap">
                    <Link to="/login" className="auth-dt-switch-link">
                      ← Back to Login
                    </Link>
                  </div>
                </form>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ForgotPassword;
