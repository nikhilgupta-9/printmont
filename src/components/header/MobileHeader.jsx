import React, { useEffect, useRef, useState } from 'react';
import { GiHamburgerMenu } from "react-icons/gi";
import { FaUserCircle, FaShoppingBag, FaRegUserCircle, FaChevronRight } from 'react-icons/fa';
import { CiHeart } from "react-icons/ci";
import { BsCart3 } from "react-icons/bs";
import { IoLocationOutline, IoChevronDown } from "react-icons/io5";
import { AiOutlineClose } from "react-icons/ai";
import {
  RiUser3Line,
  RiShoppingBag3Line,
  RiTruckLine,
  RiWallet3Line,
  RiCustomerService2Line,
  RiBuilding4Line,
  RiStore3Line,
  RiFolder3Line,
  RiPriceTag3Line
} from "react-icons/ri";
import { Link, useNavigate } from 'react-router-dom';
import SearchBar from '../search/SearchBar';
import { API_ENDPOINTS, ASSET_URL, resolveImageUrl, assetUrl } from '../../config/apiEndpoints';
import { useCheckout } from '../../context/CheckoutContext';
import { useWishlist } from '../../context/WishlistContext';
import { useAuth } from '../../context/AuthContext';
import useAccountNavigation from '../../hooks/useAccountNavigation';
import { useLocationContext } from '../../context/LocationContext';
import "./MobileHeader.css";

const MobileHeader = () => {
  const navigate = useNavigate();
  const checkoutContext = useCheckout();
  const { wishlistCount } = useWishlist();
  const { user } = useAuth();
  const { accountPath, goToAccount } = useAccountNavigation();
  const { location, openLocationModal } = useLocationContext();
  const cartItems = checkoutContext?.cartItems || [];
  const cartCount = cartItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
  const [dropdowns, setDropdowns] = useState({});
  const [logo, setLogo] = useState("/PrintLogo.png");
  const [categoriesData, setCategoriesData] = useState([]);
  const [showAllCategories, setShowAllCategories] = useState(false);
  const headerRef = useRef();

  // Measure and set --site-header-height CSS variable for mobile
  useEffect(() => {
    if (!headerRef.current) return;

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

    return () => {
      resizeObserver.disconnect();
    };
  }, []);

  useEffect(() => {
    const fetchLogo = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.LOGO);
        const data = await response.json();
        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
          let logoData = data.data.find(l => l.asset_type === "mobile_logo" && (l.is_active == 1 || l.is_active === "1"));
          if (!logoData) {
            logoData = data.data.find(l => (l.asset_type === "desktop_logo" || l.asset_type === "header_logo") && (l.is_active == 1 || l.is_active === "1"));
          }
          if (!logoData) {
            logoData = data.data.find(l => l.is_active == 1 || l.is_active === "1") || data.data[0];
          }
          if (logoData) {
            const filePath = logoData.file_path.startsWith('/') ? logoData.file_path.substring(1) : logoData.file_path;
            setLogo(assetUrl(filePath + logoData.file_name));
          }
        }
      } catch (error) {
        console.error("Error fetching mobile logo:", error);
      }
    };
    fetchLogo();
  }, []);

  // Fetch Categories for Sidebar
  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const cacheBuster = `_t=${Date.now()}`;
        const url = API_ENDPOINTS.CATEGORIES.includes('?')
          ? `${API_ENDPOINTS.CATEGORIES}&${cacheBuster}`
          : `${API_ENDPOINTS.CATEGORIES}?${cacheBuster}`;

        const response = await fetch(url, { cache: 'no-store' });
        const data = await response.json();
        let catList = [];
        if (data && data.success && data.data) {
          catList = Array.isArray(data.data) ? data.data : (typeof data.data === 'object' ? Object.values(data.data) : []);
        } else if (Array.isArray(data)) {
          catList = data;
        } else if (data && data.categories) {
          catList = Array.isArray(data.categories) ? data.categories : (typeof data.categories === 'object' ? Object.values(data.categories) : []);
        } else if (data && typeof data === 'object' && !data.error) {
          catList = Object.values(data);
        }

        if (catList.length > 0) {
          setCategoriesData(catList);
        }
      } catch (error) {
        console.error("Error fetching categories in MobileHeader:", error);
      }
    };
    fetchCategories();

    const handleSync = () => {
      if (document.visibilityState === 'visible') {
        fetchCategories();
      }
    };
    window.addEventListener('focus', handleSync);
    document.addEventListener('visibilitychange', handleSync);
    return () => {
      window.removeEventListener('focus', handleSync);
      document.removeEventListener('visibilitychange', handleSync);
    };
  }, []);

  const getArray = (data) => {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    if (typeof data === 'object') return Object.values(data);
    return [];
  };

  const getSidebarOrder = (cat) => {
    if (!cat) return 999999;
    const ord = Number(cat.mobile?.sidebar_order ?? cat.mobile_sidebar_order ?? cat.mobile?.order ?? cat.mobile_topbar_order ?? cat.display_order ?? cat.order ?? 0);
    return ord > 0 ? ord : 999999;
  };

  const isSidebarVisible = (cat) => {
    if (!cat) return false;
    if (cat.status && (cat.status === 'inactive' || cat.status === 'deactive' || cat.status === 'disabled')) return false;
    if (cat.mobile?.sidebar_status === 'no' || cat.mobile_sidebar_status === 'no') return false;
    const mobStatus = cat.mobile?.status ?? cat.mobile_topbar_status;
    if (mobStatus === 'hide') return false;
    return true;
  };

  const safeCategories = getArray(categoriesData)
    .filter(isSidebarVisible)
    .slice()
    .sort((a, b) => {
      const diff = getSidebarOrder(a) - getSidebarOrder(b);
      if (diff !== 0) return diff;
      return (a.name || '').localeCompare(b.name || '');
    });

  const visibleCategories = showAllCategories ? safeCategories : safeCategories.slice(0, 12);

  const toggleDropdown = (key) => {
    setDropdowns((prev) => ({
      ...prev,
      [key]: !prev[key],
    }));
  };

  // Close offcanvas menu programmatically
  const closeOffcanvas = () => {
    const offcanvasEl = document.getElementById("offcanvasNavbar");
    if (offcanvasEl) {
      if (window.bootstrap && window.bootstrap.Offcanvas) {
        const instance = window.bootstrap.Offcanvas.getInstance(offcanvasEl) || new window.bootstrap.Offcanvas(offcanvasEl);
        if (instance) instance.hide();
      } else {
        offcanvasEl.classList.remove('show');
      }
    }
    // Remove lingering backdrop overlays
    document.querySelectorAll('.offcanvas-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
  };

  const userName = user ? (user.firstName || user.name || 'Customer') : 'Guest';

  return (
    <>
      <div ref={headerRef} className="site-header bg-white shadow-sm w-100">
        {/* === ROW 1: HAMBURGER + LOGO + WISHLIST + CART + LOGIN === */}
        <div className="d-flex justify-content-between align-items-center mt-1 px-3 pt-2 pb-1.5">
          {/* Left: Hamburger + Logo */}
          <div className="d-flex align-items-center gap-2.5">
            <button 
              className="btn p-1 border-0 flex-shrink-0 d-flex align-items-center justify-content-center" 
              type="button" 
              data-bs-toggle="offcanvas" 
              data-bs-target="#offcanvasNavbar"
              aria-controls="offcanvasNavbar"
              aria-label="Open navigation menu"
              style={{ borderRadius: '6px', background: '#f8fafc' }}
            >
              <svg width="22" height="17" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 1.5H21M1 9H21M1 16.5H21" stroke="#0f172a" strokeWidth="2.4" strokeLinecap="round"/>
              </svg>
            </button>
            <Link to="/" className="d-inline-flex align-items-center">
              <img 
                src={logo || "/PrintLogo.png"} 
                alt="Printmont Logo" 
                style={{ height: "32px", maxWidth: "160px", objectFit: "contain" }} 
                onError={(e) => { e.target.src = "/PrintLogo.png"; }} 
              />
            </Link>
          </div>

          {/* Right: Wishlist + Cart + Login */}
          <div className="d-flex align-items-center gap-2.5">
            {/* Wishlist */}
            <Link to="/wishlist" className="position-relative d-inline-flex align-items-center text-decoration-none p-1" aria-label="Wishlist">
              <CiHeart size={26} color="#00529b" style={{ strokeWidth: 0.8 }} />
              <span 
                className="badge rounded-pill position-absolute d-flex align-items-center justify-content-center" 
                style={{ 
                  top: "-2px", 
                  right: "-4px", 
                  fontSize: "9px", 
                  padding: "2px 4px", 
                  lineHeight: "1",
                  minWidth: "16px",
                  height: "16px",
                  backgroundColor: "#e11d48",
                  color: "#ffffff",
                  boxShadow: "0 1px 3px rgba(225,29,72,0.4)"
                }}
              >
                {wishlistCount || 0}
              </span>
            </Link>

            {/* Cart */}
            <Link to="/cart" className="position-relative d-inline-flex align-items-center text-decoration-none p-1" aria-label="Cart">
              <BsCart3 size={21} color="#00529b" />
              <span 
                className="badge rounded-pill position-absolute d-flex align-items-center justify-content-center" 
                style={{ 
                  top: "-2px", 
                  right: "-4px", 
                  fontSize: "9px", 
                  padding: "2px 4px", 
                  lineHeight: "1",
                  minWidth: "16px",
                  height: "16px",
                  backgroundColor: "#e11d48",
                  color: "#ffffff",
                  boxShadow: "0 1px 3px rgba(225,29,72,0.4)"
                }}
              >
                {cartCount || 0}
              </span>
            </Link>

            {/* Login / User */}
            <Link to={accountPath} onClick={goToAccount} className="d-flex align-items-center gap-1 text-decoration-none px-2 py-1 rounded-pill" style={{ fontSize: "13px", backgroundColor: "#f0f5ff" }}>
              <FaRegUserCircle size={18} color="#00529b" />
              <span style={{ color: "#00529b", fontWeight: 600, fontSize: "13px" }}>
                {user
                  ? (user.first_name || user.firstName || (user.name || '').trim().split(/\s+/)[0] || (user.email ? user.email.split('@')[0] : '') || 'Account')
                  : 'Login'}
              </span>
            </Link>
          </div>
        </div>

        {/* === ROW 2: LOCATION STRIP === */}
        <div className="px-3 pb-1 pt-0.5">
          <div 
            className="d-flex align-items-center justify-content-between px-2.5 py-1.5 cursor-pointer rounded-2" 
            onClick={openLocationModal}
            style={{ 
              cursor: "pointer", 
              fontSize: "12px", 
              backgroundColor: "#f8fafc",
              border: "1px solid #e2e8f0"
            }}
            title="Click to select delivery location"
          >
            <div className="d-flex align-items-center gap-1.5 text-truncate">
              <IoLocationOutline size={15} color="#00529b" className="flex-shrink-0" />
              {location?.is_set ? (
                <span className="text-dark text-truncate" style={{ fontSize: "12px" }}>
                  Deliver to <strong style={{ color: "#00529b" }}>{location.city_name} {location.pincode}</strong>
                </span>
              ) : (
                <span className="text-secondary text-truncate" style={{ fontSize: "12px" }}>
                  Deliver to <span style={{ color: "#00529b", fontWeight: 600 }}>Select City &amp; Pincode</span>
                </span>
              )}
            </div>
            <IoChevronDown size={12} color="#64748b" className="flex-shrink-0 ms-1" />
          </div>
        </div>

        {/* === ROW 3: SEARCHBAR === */}
        <div className="navbar-search px-3 pb-2 pt-1 position-relative" style={{ zIndex: "4" }}>
          <SearchBar isMobileOverlay={true} />
        </div>
      </div>
      <div className="site-header-spacer" aria-hidden="true" />

      {/* =========================================================================
          OFFCANVAS SIDEBAR (Matches provided wireframe layout precisely)
          ========================================================================= */}
      <div
        className="offcanvas offcanvas-start mob-sidebar-offcanvas"
        tabIndex="-1"
        id="offcanvasNavbar"
        aria-labelledby="offcanvasNavbarLabel"
      >
        {/* 1. Header (Deep Blue with User Avatar & Close button) */}
        <div className="mob-sidebar-header">
          <div className="mob-sidebar-user-row">
            <div className="d-flex align-items-center gap-2.5">
              <div className="mob-sidebar-user-avatar">
                <FaRegUserCircle size={24} />
              </div>
              <h5 className="mob-sidebar-user-name px-1">Hi {userName}</h5>
            </div>
            <button
              type="button"
              className="mob-sidebar-close-btn"
              onClick={closeOffcanvas}
              aria-label="Close menu"
            >
              <AiOutlineClose size={16} />
            </button>
          </div>

          {/* 1.1 Action Bar underneath user (Track your order | Login & Signup) */}
          <div className="mob-sidebar-actions-row">
            <Link
              to="/track-order"
              className="mob-sidebar-action-link"
              onClick={closeOffcanvas}
            >
              Track your order
            </Link>
            <div className="mob-sidebar-action-divider" />
            {user ? (
              <Link
                to={accountPath}
                className="mob-sidebar-action-link"
                onClick={(e) => { closeOffcanvas(); goToAccount(e); }}
              >
                My Account
              </Link>
            ) : (
              <Link
                to="/login"
                className="mob-sidebar-action-link"
                onClick={closeOffcanvas}
              >
                Login &amp; Signup
              </Link>
            )}
          </div>
        </div>

        {/* 2. Middle Category Accordion List (White Background) */}
        <div className="mob-sidebar-body">
          <ul className="mob-sidebar-cat-list">
            {visibleCategories.map((item, index) => {
              const itemChildren = getArray(item.children || item.subcategories)
                .filter(isSidebarVisible)
                .slice()
                .sort((a, b) => {
                  const diff = getSidebarOrder(a) - getSidebarOrder(b);
                  if (diff !== 0) return diff;
                  return (a.name || '').localeCompare(b.name || '');
                });

              const hasChildren = itemChildren.length > 0;
              const isExpanded = !!dropdowns[item.id];
              const rawImg = item.images?.image || item.images?.desktop || item.image || item.icon;
              const imgUrl = rawImg && !rawImg.startsWith('fa') ? resolveImageUrl(rawImg) : null;

              return (
                <li className="mob-sidebar-cat-item" key={item.id || index}>
                  {hasChildren ? (
                    <>
                      <div
                        className="mob-sidebar-cat-row"
                        onClick={() => toggleDropdown(item.id)}
                      >
                        <div className="mob-sidebar-cat-left">
                          {imgUrl ? (
                            <img src={imgUrl} alt="" className="mob-sidebar-cat-icon rounded-2 object-fit-cover" style={{ width: '28px', height: '28px' }} />
                          ) : (
                            <span className="mob-sidebar-cat-icon rounded-2" style={{ width: '28px', height: '28px', background: '#eff6ff', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                              <RiPriceTag3Line size={16} color="#00529b" />
                            </span>
                          )}
                          <span className="mob-sidebar-cat-title">{item.name}</span>
                        </div>
                        <IoChevronDown
                          size={15}
                          style={{
                            color: '#64748b',
                            transition: 'transform 0.25s cubic-bezier(0.16, 1, 0.3, 1)',
                            transform: isExpanded ? 'rotate(180deg)' : 'rotate(0deg)'
                          }}
                        />
                      </div>

                      {isExpanded && (
                        <ul className="mob-sidebar-sub-list">
                          <li>
                            <Link
                              to={`/category/${item.slug || item.id}`}
                              className="mob-sidebar-sub-link fw-semibold"
                              onClick={closeOffcanvas}
                            >
                              All {item.name}
                            </Link>
                          </li>
                          {itemChildren.map((sub, sIdx) => (
                            <li key={sub.id || sIdx}>
                              <Link
                                to={`/category/${sub.slug || sub.id}`}
                                className="mob-sidebar-sub-link"
                                onClick={closeOffcanvas}
                              >
                                {sub.name}
                              </Link>
                            </li>
                          ))}
                        </ul>
                      )}
                    </>
                  ) : (
                    <Link
                      to={`/category/${item.slug || item.id}`}
                      className="mob-sidebar-cat-row"
                      onClick={closeOffcanvas}
                    >
                      <div className="mob-sidebar-cat-left">
                        {imgUrl ? (
                          <img src={imgUrl} alt="" className="mob-sidebar-cat-icon rounded-2 object-fit-cover" style={{ width: '28px', height: '28px' }} />
                        ) : (
                          <span className="mob-sidebar-cat-icon rounded-2" style={{ width: '28px', height: '28px', background: '#eff6ff', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                            <RiPriceTag3Line size={16} color="#00529b" />
                          </span>
                        )}
                        <span className="mob-sidebar-cat-title">{item.name}</span>
                      </div>
                      <span style={{ fontSize: '13px', color: '#94a3b8' }}>&gt;</span>
                    </Link>
                  )}
                </li>
              );
            })}

            {safeCategories.length > 12 && (
              <li className="p-2 text-center bg-light border-bottom">
                <button
                  type="button"
                  className="btn btn-sm btn-link text-primary fw-semibold text-decoration-none p-0"
                  onClick={() => setShowAllCategories(!showAllCategories)}
                >
                  {showAllCategories ? "Show Less" : "+ More Categories"}
                </button>
              </li>
            )}
          </ul>

          {/* 3. Bottom Light-Blue Block (Account & Support Services - now inside scrollable body) */}
          <div className="mob-sidebar-bottom-nav">
            <ul className="mob-sidebar-nav-list">
              <li className="mob-sidebar-nav-item">
                <Link
                  to={accountPath}
                  className="mob-sidebar-nav-link"
                  onClick={(e) => { closeOffcanvas(); goToAccount(e); }}
                >
                  <span className="mob-sidebar-nav-icon">
                    <RiUser3Line />
                  </span>
                  <span>My Account</span>
                </Link>
              </li>

              <li className="mob-sidebar-nav-item">
                <Link
                  to="/orders"
                  className="mob-sidebar-nav-link"
                  onClick={closeOffcanvas}
                >
                  <span className="mob-sidebar-nav-icon">
                    <RiShoppingBag3Line />
                  </span>
                  <span>Order History</span>
                </Link>
              </li>

              <li className="mob-sidebar-nav-item">
                <Link
                  to="/printmont-coin"
                  className="mob-sidebar-nav-link"
                  onClick={closeOffcanvas}
                >
                  <span className="mob-sidebar-nav-icon">
                    <RiWallet3Line />
                  </span>
                  <span>My Wallet</span>
                </Link>
              </li>

              <li className="mob-sidebar-nav-item">
                <Link
                  to="/help-center"
                  className="mob-sidebar-nav-link"
                  onClick={closeOffcanvas}
                >
                  <span className="mob-sidebar-nav-icon">
                    <RiCustomerService2Line />
                  </span>
                  <span>Help Center</span>
                </Link>
              </li>

              <li className="mob-sidebar-nav-item">
                <Link
                  to="/franchise"
                  className="mob-sidebar-nav-link"
                  onClick={closeOffcanvas}
                >
                  <span className="mob-sidebar-nav-icon">
                    <RiBuilding4Line />
                  </span>
                  <span>Franchise Enquire</span>
                </Link>
              </li>

              <li className="mob-sidebar-nav-item">
                <Link
                  to="/become-a-seller"
                  className="mob-sidebar-nav-link"
                  onClick={closeOffcanvas}
                >
                  <span className="mob-sidebar-nav-icon">
                    <RiStore3Line />
                  </span>
                  <span>Become A Seller</span>
                </Link>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </>
  );
};

export default MobileHeader;
