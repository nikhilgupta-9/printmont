import React, { useEffect, useRef, useState } from 'react';
import { GiHamburgerMenu, GiShoppingCart } from "react-icons/gi";
import { FaRegUser, FaUser, FaBoxOpen, FaWallet, FaPaperPlane, FaStore } from 'react-icons/fa';
import { CiHeart } from "react-icons/ci";
import { IoSearchSharp } from "react-icons/io5";
import { AiOutlineClockCircle, AiOutlineClose } from "react-icons/ai";
import { Link, useNavigate } from 'react-router-dom';
import { API_ENDPOINTS, ASSET_URL } from '../../config/apiEndpoints';

const MobileHeader = () => {
  const navigate = useNavigate();
  const [dropdowns, setDropdowns] = useState({});
  const [searchQuery, setSearchQuery] = useState('');
  const [showSearchDropdown, setShowSearchDropdown] = useState(false);
  const [recentSearches, setRecentSearches] = useState(["T-Shirt", "Mug", "Notebook"]);
  const [logo, setLogo] = useState(null);

  // Search API States
  const [searchResults, setSearchResults] = useState([]);
  const [isSearching, setIsSearching] = useState(false);

  const searchRef = useRef();
  const headerRef = useRef();
  const [categoriesData, setCategoriesData] = useState([]);
  const [showAllCategories, setShowAllCategories] = useState(false);

  // Fetch Categories for Sidebar
  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.CATEGORIES);
        const data = await response.json();
        if (data.success) {
          setCategoriesData(data.data);
        }
      } catch (error) {
        console.error("Error fetching categories in MobileHeader:", error);
      }
    };
    fetchCategories();
  }, []);

  const getArray = (data) => {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    if (typeof data === 'object') return Object.values(data);
    return [];
  };

  const safeCategories = getArray(categoriesData);
  const visibleCategories = showAllCategories ? safeCategories : safeCategories.slice(0, 8);

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

  const selectKeyword = (keyword) => {
    setSearchQuery(keyword);
    setShowSearchDropdown(false);
    navigate(`/allproducts?search=${encodeURIComponent(keyword)}`);
  };

  return (
    <>
      <div ref={headerRef} className="site-header bg-white shadow-sm w-100">
        {/* === TOPBAR (logo + icons) === */}
        <div className="d-flex justify-content-between align-items-center px-3 py-2">
          <div className="d-flex align-items-center gap-3">
            <button 
              className="btn p-0 border-0" 
              type="button" 
              data-bs-toggle="offcanvas" 
              data-bs-target="#offcanvasNavbar"
              aria-controls="offcanvasNavbar"
            >
              <GiHamburgerMenu size={24} />
            </button>
            <Link to="/">
              <img src="/PrintLogo.png" alt="Printmont Logo" style={{ height: "30px", objectFit: "contain" }} />
            </Link>
          </div>

          <div className="d-flex align-items-center gap-3">
            <Link to="/user/wishlist" className="position-relative">
              <CiHeart size={25} color="#007bff" />
            </Link>

            <Link to="/cart" className="position-relative">
              <GiShoppingCart size={25} color="#007bff" />
            </Link>

            <Link to="/login" className="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-bold">
              Login
            </Link>
          </div>
        </div>

        {/* === SEARCHBAR === */}
        <div className="navbar-search border-top px-3 py-2 position-relative d-flex align-items-center gap-2" ref={searchRef} style={{ zIndex: "4" }}>
          <IoSearchSharp className="fs-4 text-primary" />
          <input
            id="mobile-all-product-search"
            type="text"
            className="form-control ms-2"
            placeholder="Search..."
            autoComplete="off"
            value={searchQuery}
            onFocus={() => setShowSearchDropdown(true)}
            onChange={(e) => setSearchQuery(e.target.value)}
          />

          {showSearchDropdown && (
            <div
              className="position-absolute bg-white border rounded shadow w-100 mt-2 z-10"
              style={{ maxHeight: '400px', overflowY: 'auto', top: '45px', left: 0 }}
            >
              {recentSearches.length > 0 && (
                <div className="py-2">
                  {recentSearches.map((item, idx) => (
                    <div
                      key={idx}
                      className="d-flex align-items-center justify-content-between px-3 py-2"
                      style={{ cursor: "pointer" }}
                      onMouseEnter={(e) => e.currentTarget.style.backgroundColor = "#f8f9fa"}
                      onMouseLeave={(e) => e.currentTarget.style.backgroundColor = "transparent"}
                    >
                      <div
                        className="d-flex align-items-center gap-2 w-100"
                        onClick={() => selectKeyword(item)}
                      >
                        <AiOutlineClockCircle className="text-secondary" />
                        <span className="text-dark" style={{ fontSize: "14px" }}>{item}</span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>
      </div>
      <div className="site-header-spacer" aria-hidden="true" />

      {/* Offcanvas Sidebar */}
      <div className="offcanvas offcanvas-start w-75" tabIndex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div className="offcanvas-header text-white py-3 d-flex align-items-center justify-content-between" style={{ backgroundColor: "rgb(11, 83, 161)" }}>
          <Link to="/login" className="d-flex align-items-center gap-2 text-white text-decoration-none" onClick={closeOffcanvas}>
            <FaUser className="fs-5" />
            <h6 className="mb-0 fw-semibold fs-5 text-white">Login & Signup</h6>
          </Link>
          <button type="button" className="btn-close btn-close-white ms-auto" onClick={closeOffcanvas} style={{ opacity: 1 }}></button>
        </div>

        <div className="offcanvas-body p-0">
          <ul className="list-group rounded-0">
            {visibleCategories.map((item, index) => {
              const itemChildren = getArray(item.children);
              return (
                <li className="list-group-item px-3 py-2" key={item.id || index} style={{ borderBottom: "1px solid #f0f0f0" }}>
                  {itemChildren.length > 0 ? (
                    <>
                      <div 
                        className="d-flex justify-content-between align-items-center" 
                        onClick={() => toggleDropdown(item.id)} 
                        style={{ cursor: 'pointer' }}
                      >
                        <span className="text-dark fw-medium" style={{ fontSize: "14px" }}>{item.name}</span>
                        <span className="fw-bold fs-5 text-secondary" style={{ width: "20px", textAlign: "center" }}>
                          {dropdowns[item.id] ? '−' : '+'}
                        </span>
                      </div>
                      {dropdowns[item.id] && (
                        <ul className="list-unstyled mt-2 ps-3 border-start" style={{ borderColor: "#eee" }}>
                          <li>
                            <Link 
                              to={`/category/${item.slug || item.id}`} 
                              className="text-decoration-none text-secondary d-block py-1"
                              style={{ fontSize: "13px" }}
                              onClick={closeOffcanvas}
                            >
                              All {item.name}
                            </Link>
                          </li>
                          {itemChildren.map((sub, sIdx) => (
                            <li key={sIdx}>
                              <Link 
                                to={`/category/${sub.slug || sub.id}`} 
                                className="text-decoration-none text-secondary d-block py-1"
                                style={{ fontSize: "13px" }}
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
                      className="d-flex justify-content-between align-items-center text-decoration-none text-dark"
                      onClick={closeOffcanvas}
                    >
                      <span className="fw-medium" style={{ fontSize: "14px" }}>{item.name}</span>
                    </Link>
                  )}
                </li>
              );
            })}
            
            {safeCategories.length > 8 && (
              <li className="list-group-item px-3 py-2 text-center" style={{ backgroundColor: "#f8f9fa", borderBottom: "1px solid #f0f0f0" }}>
                <button 
                  className="btn btn-sm btn-link text-primary fw-semibold text-decoration-none p-0"
                  onClick={() => setShowAllCategories(!showAllCategories)}
                >
                  {showAllCategories ? "Show Less" : "+ More Categories"}
                </button>
              </li>
            )}
          </ul> 

          <div className="mt-3 border-top">
            <ul className="list-group rounded-0">
              <li className="list-group-item d-flex align-items-center gap-3 px-3 py-3" style={{ borderBottom: "1px solid #f0f0f0" }}>
                <FaUser className="text-secondary fs-5" /> 
                <Link to="/my-account" className="text-decoration-none text-dark fw-medium" style={{ fontSize: "14px" }} onClick={closeOffcanvas}>My Account</Link>
              </li>
              <li className="list-group-item d-flex align-items-center gap-3 px-3 py-3" style={{ borderBottom: "1px solid #f0f0f0" }}>
                <FaBoxOpen className="text-secondary fs-5" /> 
                <Link to="/orders" className="text-decoration-none text-dark fw-medium" style={{ fontSize: "14px" }} onClick={closeOffcanvas}>My Orders</Link>
              </li>
              <li className="list-group-item d-flex align-items-center gap-3 px-3 py-3" style={{ borderBottom: "1px solid #f0f0f0" }}>
                <FaPaperPlane className="text-secondary fs-5" /> 
                <Link to="/track-order" className="text-decoration-none text-dark fw-medium" style={{ fontSize: "14px" }} onClick={closeOffcanvas}>Track Order</Link>
              </li>
              <li className="list-group-item d-flex align-items-center gap-3 px-3 py-3" style={{ borderBottom: "1px solid #f0f0f0" }}>
                <FaWallet className="text-secondary fs-5" /> 
                <Link to="/printmont-coin" className="text-decoration-none text-dark fw-medium" style={{ fontSize: "14px" }} onClick={closeOffcanvas}>My Wallet & PrintCoins</Link>
              </li>
              <li className="list-group-item d-flex align-items-center gap-3 px-3 py-3" style={{ borderBottom: "1px solid #f0f0f0" }}>
                <FaStore className="text-secondary fs-5" /> 
                <Link to="/become-a-seller" className="text-decoration-none text-dark fw-medium" style={{ fontSize: "14px" }} onClick={closeOffcanvas}>Sell On Printmont</Link>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </>
  );
};

export default MobileHeader;
