import React, { useState, useEffect } from 'react';
import { BsArrowLeft, BsChevronDown } from 'react-icons/bs';
import { FaRegCircleUser } from 'react-icons/fa6';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import useAccountNavigation from '../../hooks/useAccountNavigation';
import { useCheckout } from '../../context/CheckoutContext';
import { API_ENDPOINTS, ASSET_URL } from '../../config/apiEndpoints';
import LoginDropdown from './LoginDropdown';
import './Header.css';

/**
 * InnerMobileNavbar Component
 * 
 * Specifically designed for Cart, Add Delivery Address, and Checkout flows.
 * 
 * Mobile view (Matches Image 1):
 *   - Left: Back button (Arrow) + Brand Icon Logo (PM) + Page Name (e.g. "Add Delivery Address", "My Cart")
 *   - Right: User profile icon with brand theme (#085885 / #0b53a1)
 * 
 * Desktop view (Matches Image 3):
 *   - Clean, distraction-free checkout header
 *   - Left: Brand Logo (PRINTMONT)
 *   - Right: Buyer profile dropdown (👤 Buyer name ∨)
 */
const InnerMobileNavbar = ({
  pageTitle = "Add Delivery Address",
  showBackButton = true,
  onBack,
  bg = "#085885",
  textColor = "#ffffff",
  isSticky = true
}) => {
  const navigate = useNavigate();
  const location = useLocation();
  const { user, getUsernamePath } = useAuth();
  const { accountPath, goToAccount } = useAccountNavigation();
  const usernamePath = getUsernamePath();
  const checkoutContext = useCheckout();
  const buyerDetails = checkoutContext ? checkoutContext.buyerDetails : null;
  const [logo, setLogo] = useState("/PrintLogo.png");

  // Format display name: priority to user profile, fallback to buyerDetails or 'Buyer name'
  const displayName = user
    ? (user.first_name || user.firstName || user.name || (user.email ? user.email.split('@')[0] : 'Buyer name'))
    : (buyerDetails?.name || 'Buyer name');

  useEffect(() => {
    const fetchLogo = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.LOGO);
        const data = await response.json();
        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
          let logoData = data.data.find(l => (l.asset_type === "desktop_logo" || l.asset_type === "header_logo") && (l.is_active == 1 || l.is_active === "1"));
          if (!logoData) {
            logoData = data.data.find(l => l.is_active == 1 || l.is_active === "1") || data.data[0];
          }
          if (logoData) {
            const filePath = logoData.file_path.startsWith('/') ? logoData.file_path.substring(1) : logoData.file_path;
            setLogo(`${ASSET_URL}${filePath}${logoData.file_name}`);
          }
        }
      } catch (error) {
        console.error("Error fetching logo in InnerMobileNavbar:", error);
      }
    };
    fetchLogo();
  }, []);

  const handleBack = () => {
    if (onBack) {
      onBack();
      return;
    }

    // Checkout flow step-back logic
    if ((location.pathname.startsWith('/cart') || location.pathname.startsWith('/checkout')) && checkoutContext?.checkoutStep > 0) {
      checkoutContext.setCheckoutStep(checkoutContext.checkoutStep - 1);
      window.scrollTo(0, 0);
      return;
    }

    const searchParams = new URLSearchParams(location.search);
    const redirectParam = searchParams.get('redirect') || location.state?.from;
    const hasPreviousHistory = window.history.state && typeof window.history.state.idx === 'number' && window.history.state.idx > 0;

    if (hasPreviousHistory) {
      navigate(-1);
    } else if (redirectParam && redirectParam !== location.pathname) {
      navigate(redirectParam);
    } else {
      navigate('/');
    }
  };

  return (
    <>
      <header
        className={`w-100 ${isSticky ? 'position-fixed top-0 start-0' : 'position-relative'}`}
        style={{ zIndex: 1030 }}
      >
        {/* 1. MOBILE VIEW NAVBAR (Theme Header with Brand Icon, Page Title & User Icon) */}
        <div
          className="d-flex d-lg-none align-items-center justify-content-between px-3 py-2 shadow-sm"
          style={{
            backgroundColor: bg,
            color: textColor,
            minHeight: '56px'
          }}
        >
          {/* Left Side: Back Arrow + Brand Icon + Page Title */}
          <div className="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden me-2">
            {showBackButton && (
              <button
                type="button"
                className="btn btn-link p-0 text-white border-0 d-flex align-items-center me-1"
                onClick={handleBack}
                aria-label="Go Back"
                style={{ textDecoration: 'none' }}
              >
                <BsArrowLeft size={22} color={textColor} />
              </button>
            )}

            {/* Brand Logo Icon */}
            <Link to="/" className="d-flex align-items-center flex-shrink-0 me-1">
              <img
                src="/pm-white-icon.png"
                alt="Brand Icon"
                style={{
                  width: '32px',
                  height: '32px',
                  borderRadius: '50%',
                  objectFit: 'contain',
                  border: '1.5px solid rgba(255,255,255,0.4)',
                  padding: '1px',
                  backgroundColor: 'rgba(255,255,255,0.1)'
                }}
                onError={(e) => { e.target.src = '/PrintLogo.png'; }}
              />
            </Link>

            {/* Page Name */}
            <span
              className="fw-bold text-truncate"
              style={{
                fontSize: '16.5px',
                letterSpacing: '0.2px',
                color: textColor
              }}
            >
              {pageTitle}
            </span>
          </div>

          {/* Right Side: User Icon with Theme */}
          <div className="d-flex align-items-center flex-shrink-0">
            <Link
              to={accountPath}
              onClick={goToAccount}
              className="d-flex align-items-center justify-content-center text-white text-decoration-none"
              title={user ? "My Account" : "Login"}
              style={{
                width: '36px',
                height: '36px',
                borderRadius: '50%',
                backgroundColor: 'rgba(255, 255, 255, 0.15)',
                transition: 'background-color 0.2s'
              }}
            >
              <FaRegCircleUser size={20} color={textColor} />
            </Link>
          </div>
        </div>

        {/* 2. DESKTOP VIEW NAVBAR (Matches Image 3: Clean Header with Brand Logo & Buyer Name Dropdown) */}
        <div className="d-none d-lg-block bg-white border-bottom shadow-xs">
          <div className="container py-2.5 d-flex align-items-center justify-content-between" style={{ minHeight: '68px', maxWidth: '1440px' }}>
            {/* Left: Brand Logo */}
            <Link to="/" className="d-flex align-items-center text-decoration-none">
              <img
                src={logo || "/PrintLogo.png"}
                alt="PrintMont Logo"
                height={48}
                style={{ objectFit: "contain", maxHeight: '48px' }}
                onError={(e) => { e.target.src = "/PrintLogo.png"; }}
              />
            </Link>

            {/* Right: Buyer Name User Dropdown (Image 3 Style: 👤 Buyer name ∨) */}
            <div className="d-flex align-items-center">
              <LoginDropdown customLabel={displayName} />
            </div>
          </div>
        </div>
      </header>

      {/* Header height spacer */}
      <div style={{ height: '56px' }} className="d-block d-lg-none" />
      <div style={{ height: '68px' }} className="d-none d-lg-block" />
    </>
  );
};

export default InnerMobileNavbar;
