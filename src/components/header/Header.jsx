import React, { useEffect, useState, useRef } from "react";
import { Navbar, Container, Nav } from "react-bootstrap";
import { Link, useLocation } from "react-router-dom";
import Categories from "../pages/category-list/Categories";
import { GoHeart } from "react-icons/go";
import { PiDotsThreeOutlineVerticalFill, PiHeadsetBold } from "react-icons/pi";
import { LuChartNoAxesCombined } from "react-icons/lu";
import { BsCart4 } from "react-icons/bs";
import { FaBell, FaHandshake, FaChevronRight } from "react-icons/fa";
import { RiDownload2Line } from "react-icons/ri";
import { IoChevronDown } from "react-icons/io5";
import LoginDropdown from "./LoginDropdown";
import HeaderPreferencesDropdown from "./HeaderPreferencesDropdown";
import MobileHeader from "./MobileHeader";
import SearchBar from "../search/SearchBar";
import { API_ENDPOINTS, ASSET_URL, assetUrl } from "../../config/apiEndpoints";
import { useAuth } from "../../context/AuthContext";
import { useCheckout } from "../../context/CheckoutContext";
import { useWishlist } from "../../context/WishlistContext";
import { useLocationContext } from "../../context/LocationContext";
import "./Header.css";

const Header = ({ showCategories, showImages = false, isSticky = true, bg, color, desktopDesign = 'design1', mobileDesign = 'design1' }) => {
  const { user, getUsernamePath } = useAuth();
  const { wishlistCount } = useWishlist();
  const { location, openLocationModal } = useLocationContext();
  const checkoutContext = useCheckout();
  
  const cartItems = checkoutContext?.cartItems || [];
  const cartCount = cartItems.reduce((acc, item) => acc + (item.quantity || 1), 0);
  const usernamePath = getUsernamePath();
  const locationPath = useLocation();
  const [isMobile, setIsMobile] = useState(window.innerWidth < 992);
  const [showPreferences, setShowPreferences] = useState(false);
  const [showLoginDropdown, setShowLoginDropdown] = useState(false);
  const [logo, setLogo] = useState(null);

  const preferenceRef = useRef();
  const loginRef = useRef();
  const headerRef = useRef();

  // Handle mobile view
  useEffect(() => {
    const handleResize = () => setIsMobile(window.innerWidth < 992);
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

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
            const imageFullUrl = assetUrl(filePath + logoData.file_name);
            setLogo(imageFullUrl);
          }
        }
      } catch (error) {
        console.error("Error fetching logo:", error);
      }
    };

    fetchLogo();
  }, []);

  useEffect(() => {
    if (isMobile || !headerRef.current) return;

    const updateHeaderHeight = () => {
      if (!headerRef.current) return;
      document.documentElement.style.setProperty(
        "--site-header-height",
        `${headerRef.current.getBoundingClientRect().height}px`
      );
    };

    updateHeaderHeight();
    const resizeObserver = new ResizeObserver(updateHeaderHeight);
    resizeObserver.observe(headerRef.current);
    window.addEventListener("resize", updateHeaderHeight);

    return () => {
      resizeObserver.disconnect();
      window.removeEventListener("resize", updateHeaderHeight);
    };
  }, [isMobile, logo]);

  if (isMobile) return <MobileHeader design={mobileDesign} />;

  return (
    <>
      <div ref={headerRef} className="container-fluid p-0 sticky-navbar">
        {/* Flipkart / FNP Clean White Main Header */}
        <div className="container-fluid bg-white fnp-main-navbar">
          <Navbar expand="lg" className="border-bottom py-2 container-fluid px-4 px-xl-5">
            <Container className="d-flex align-items-center justify-content-between py-1" style={{ maxWidth: '1440px' }}>
              
              {/* Brand Logo, Vertical Divider & Exact FNP Location Block */}
              <div className="d-flex align-items-center me-2">
                <Navbar.Brand as={Link} to="/" className="m-0 p-0 d-flex flex-column">
                  <img 
                    src={logo || "/PrintLogo.png"} 
                    alt="PrintMont Logo" 
                    style={{ height: "42px", objectFit: "contain" }} 
                    onError={(e) => { e.target.src = "/PrintLogo.png"; }}
                  />
                </Navbar.Brand>

                {/* Vertical Divider Line */}
                <div style={{ width: "1px", height: "36px", backgroundColor: "#e2e8f0", margin: "0 16px" }} />

                {/* Location Trigger (Flag + Where to deliver? + City / Pincode) */}
                <div
                  className="d-flex align-items-center text-nowrap pm-location-trigger"
                  onClick={openLocationModal}
                  title="Click to select delivery location"
                >
                  {/* Indian Flag */}
                  <span
                    className="d-inline-flex align-items-center justify-content-center overflow-hidden flex-shrink-0"
                    style={{
                      width: "22px",
                      height: "15px",
                      borderRadius: "2.5px",
                      boxShadow: "0 1px 2px rgba(0,0,0,0.12)",
                      border: "1px solid rgba(0,0,0,0.08)"
                    }}
                  >
                    <svg viewBox="0 0 640 480" width="100%" height="100%">
                      <path fill="#f93" d="M0 0h640v160H0z"/>
                      <path fill="#fff" d="M0 160h640v160H0z"/>
                      <path fill="#128807" d="M0 320h640v160H0z"/>
                      <g transform="matrix(3.2 0 0 3.2 320 240)">
                        <circle r="20" fill="#008"/>
                        <circle r="17.5" fill="#fff"/>
                        <circle r="3.5" fill="#008"/>
                        <g id="d">
                          <g id="c">
                            <g id="b">
                              <g id="a">
                                <path fill="#008" d="M0-17.5L.6-3.5 0 0l-.6-3.5z"/>
                              </g>
                              <use href="#a" transform="rotate(15)"/>
                            </g>
                            <use href="#b" transform="rotate(30)"/>
                          </g>
                          <use href="#c" transform="rotate(60)"/>
                        </g>
                        <use href="#d" transform="rotate(120)"/>
                        <use href="#d" transform="rotate(240)"/>
                      </g>
                    </svg>
                  </span>

                  {/* Text Column */}
                  <div className="d-flex flex-column text-start" style={{ lineHeight: "1.2" }}>
                    <span style={{ fontSize: "11px", fontWeight: "600", color: "#64748b", letterSpacing: "0.2px", textTransform: "uppercase" }}>
                      Deliver to
                    </span>
                    {location?.is_set ? (
                      <span
                        className="d-flex align-items-center gap-1"
                        style={{ fontSize: "12.5px", fontWeight: "700", color: "#00529b" }}
                      >
                        <span className="text-truncate" style={{ maxWidth: "120px" }}>
                          {location.city_name}{location.pincode ? `, ${location.pincode}` : ''}
                        </span>
                        <IoChevronDown size={11} style={{ color: "#00529b" }} />
                      </span>
                    ) : (
                      <span
                        className="d-flex align-items-center gap-1"
                        style={{ fontSize: "12px", fontWeight: "600", color: "#ea580c" }}
                      >
                        Select Location
                        <IoChevronDown size={11} style={{ color: "#ea580c" }} />
                      </span>
                    )}
                  </div>
                </div>
              </div>

              {/* Centered Wide Flipkart Search Bar */}
              <div className="flex-grow-1 mx-3 mx-xl-4 fnp-search-wrapper">
                <SearchBar />
              </div>

              {/* Action Icons & Links (Flipkart Layout) */}
              <Nav className="d-flex align-items-center gap-3 gap-xl-4 flex-row flex-nowrap">
                {/* Wishlist */}
                <Link
                  to={`/${usernamePath}/wishlist`}
                  className="fnp-header-action-item"
                  title="My Wishlist"
                >
                  <div className="position-relative d-flex align-items-center">
                    <GoHeart size={21} color="#0b53a1" />
                    {wishlistCount > 0 && (
                      <span className="fnp-badge-counter">{wishlistCount}</span>
                    )}
                  </div>
                  <span className="fnp-header-action-text">Wishlist</span>
                </Link>

                {/* Cart */}
                <Link
                  to="/cart"
                  className="fnp-header-action-item"
                  title="Shopping Cart"
                >
                  <div className="position-relative d-flex align-items-center">
                    <BsCart4 size={20} color="#0b53a1" />
                    {cartCount > 0 && (
                      <span className="fnp-badge-counter">{cartCount}</span>
                    )}
                  </div>
                  <span className="fnp-header-action-text">Cart</span>
                </Link>

                {/* User Sign In / Account Dropdown */}
                <div
                  className="d-flex flex-column align-items-center gap-1 nav-link text-dark p-0"
                  ref={loginRef}
                  onMouseEnter={() => setShowLoginDropdown(true)}
                  onMouseLeave={() => setShowLoginDropdown(false)}
                  style={{ cursor: "pointer" }}
                >
                  <LoginDropdown isHovered={showLoginDropdown} />
                </div>

                {/* Three-Dots More Preferences Dropdown */}
                <HeaderPreferencesDropdown />
              </Nav>

            </Container>
          </Navbar>

          {/* Category Navigation Bar on Inner Pages if configured */}
          {(showCategories !== undefined ? showCategories : (locationPath.pathname !== '/')) && (
            <div className="d-none d-lg-block">
              <Categories 
                showImages={showImages} 
                space="0" 
                bg={bg || "#ffffff"} 
                color={color || "#212121"} 
                isSticky={false} 
              />
            </div>
          )}
        </div>
      </div>

      <div className="site-header-spacer" aria-hidden="true" />
    </>
  );
};

export default Header;
