import React, { useEffect, useRef, useState } from 'react';
import { GiHamburgerMenu, GiShoppingCart } from "react-icons/gi";
import { FaRegUser, FaUser, FaBoxOpen, FaWallet, FaPaperPlane, FaStore } from 'react-icons/fa';
import { CiHeart } from "react-icons/ci";
import { IoSearchSharp } from "react-icons/io5";
import { AiOutlineClockCircle, AiOutlineClose } from "react-icons/ai";
import { LuChartNoAxesCombined } from "react-icons/lu";
import { FaHandshake } from "react-icons/fa";
import { CiBullhorn } from "react-icons/ci";
import { Link } from 'react-router-dom';
import axios from 'axios';
import { API_ENDPOINTS, ASSET_URL } from '../../config/apiEndpoints';

const MobileHeader = () => {
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

  // Close search dropdown if clicked outside
  useEffect(() => {
    const handleClickOutside = (e) => {
      if (searchRef.current && !searchRef.current.contains(e.target)) {
        setShowSearchDropdown(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  // Search API Effect
  useEffect(() => {
    if (!searchQuery.trim()) {
      setSearchResults([]);
      return;
    }
    const timer = setTimeout(async () => {
      setIsSearching(true);
      try {
        const response = await fetch(`${API_ENDPOINTS.SEARCH}?q=${encodeURIComponent(searchQuery)}`);
        const data = await response.json();
        if (data.success && data.data) {
          setSearchResults(data.data);
        } else {
          setSearchResults([]);
        }
      } catch (error) {
        console.error("Search API Error:", error);
        setSearchResults([]);
      } finally {
        setIsSearching(false);
      }
    }, 300);
    return () => clearTimeout(timer);
  }, [searchQuery]);

  // Fetch Logo
  useEffect(() => {
    const fetchLogo = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.LOGO);
        const data = await response.json();

        if (data.success && data.data.length > 0) {
          // Prioritize mobile logo, then desktop logo, then any active logo
          let logoData = data.data.find(l => l.asset_type === "mobile_logo" && l.is_active === "1");
          if (!logoData) {
            logoData = data.data.find(l => l.asset_type === "desktop_logo" && l.is_active === "1");
          }
          if (!logoData) {
            logoData = data.data.find(l => l.is_active === "1") || data.data[0];
          }

          const filePath = logoData.file_path.startsWith('/') ? logoData.file_path.substring(1) : logoData.file_path;
          const imageFullUrl = `${ASSET_URL}${filePath}${logoData.file_name}`;

          setLogo(imageFullUrl);
        }
      } catch (error) {
        console.error("Error fetching mobile logo:", error);
      }
    };

    fetchLogo();
  }, []);

  useEffect(() => {
    if (!headerRef.current) return;

    const updateHeaderHeight = () => {
      document.documentElement.style.setProperty(
        "--site-header-height",
        `${headerRef.current.offsetHeight}px`
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
  }, [logo]);

  const selectKeyword = (keyword) => {
    setSearchQuery(keyword);
    setShowSearchDropdown(false);
    if (!recentSearches.includes(keyword)) {
      setRecentSearches([keyword, ...recentSearches].slice(0, 10));
    }
  };

  const removeRecent = (index) => {
    setRecentSearches((prev) => prev.filter((_, i) => i !== index));
  };

  return (
    <>
      <div ref={headerRef} className="site-header bg-white shadow-sm w-100">
        {/* === TOPBAR (logo + icons) === */}
        <div className="d-flex justify-content-between align-items-center px-3 py-2">
          <div className="d-flex align-items-center gap-3">
            <button className="btn p-0 border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar">
              <GiHamburgerMenu size={24} />
            </button>
            <Link to="/">
              {logo ? (
                <img src={logo} alt="Printmont Logo" style={{ height: "30px", objectFit: "contain" }} />
              ) : (
                <div style={{ height: "30px", width: "100px" }} className="shimmer-bg"></div>
              )}
            </Link>
          </div>

          <div className="d-flex align-items-center gap-3">
            <Link to="/user/wishlist" className="position-relative">
              <CiHeart size={25} color="#007bff" />
              <div className="notify-mes"><span className="notify-num">4</span></div>
            </Link>

            <Link to="/cart" className="position-relative">
              <GiShoppingCart size={25} color="#007bff" />
              <div className="notify-mes"><span className="notify-num">4</span></div>
            </Link>

            <div className="d-flex align-items-center gap-1 border px-2 py-1 rounded login-button">
              <FaRegUser size={20} color="#007bff" />
              <Link to="/login" className="text-decoration-none fw-semibold">Login</Link>
            </div>
          </div>
        </div>

        {/* === SEARCHBAR === */}
        <div className="navbar-search border-top px-3 py-2 position-relative d-flex align-items-center gap-2 " ref={searchRef} style={{zIndex:"4",}}>
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

          {/* Search Suggestions Dropdown */}
          {showSearchDropdown && (
            <div
              className="position-absolute bg-white border rounded shadow w-100 mt-2 z-10"
              style={{ maxHeight:'400px', overflowY:'auto', top:'45px', left:0 }}
            >
              {searchQuery.trim().length > 0 ? (
                isSearching ? (
                  <div className="p-3 text-center text-muted small">Searching...</div>
                ) : searchResults.length > 0 ? (
                  searchResults.map((item, idx) => {
                    // Extract image
                    let imagePath = "/placeholder.jpg";
                    if (item.images && item.images.length > 0) {
                      const img = item.images[0].image_path || item.images[0].file_path;
                      if (img) {
                        imagePath = `${ASSET_URL}${img.startsWith('/') ? img.substring(1) : img}`;
                      }
                    } else if (item.image) {
                       imagePath = `${ASSET_URL}${item.image.startsWith('/') ? item.image.substring(1) : item.image}`;
                    } else if (item.thumbnail) {
                       imagePath = `${ASSET_URL}${item.thumbnail.startsWith('/') ? item.thumbnail.substring(1) : item.thumbnail}`;
                    }

                    return (
                      <Link
                        to={`/product/${item.slug || item.id}`}
                        key={idx}
                        className="d-flex align-items-center px-3 py-2 text-decoration-none border-bottom"
                        style={{ cursor: "pointer", transition: "background 0.2s" }}
                        onClick={() => {
                          setShowSearchDropdown(false);
                          selectKeyword(item.name);
                        }}
                        onMouseEnter={(e) => e.currentTarget.style.backgroundColor = "#f8f9fa"}
                        onMouseLeave={(e) => e.currentTarget.style.backgroundColor = "transparent"}
                      >
                        <div className="flex-shrink-0" style={{ width: "35px", height: "35px" }}>
                          <img src={imagePath} alt={item.name} className="w-100 h-100 object-fit-contain" />
                        </div>
                        <div className="ms-3 flex-grow-1 text-truncate">
                          <div className="text-dark text-truncate" style={{ fontSize: "14px", fontWeight: "400" }}>{item.name}</div>
                          {item.category_name && (
                            <div className="text-primary" style={{ fontSize: "12px", marginTop: "1px" }}>
                              in {item.category_name}
                            </div>
                          )}
                        </div>
                      </Link>
                    );
                  })
                ) : (
                  <div className="p-3 text-center text-muted small">No results found for "{searchQuery}"</div>
                )
              ) : (
                /* Recent Searches when empty */
                recentSearches.length > 0 && (
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
                        <AiOutlineClose
                          className="text-secondary"
                          onClick={(e) => {
                            e.stopPropagation();
                            removeRecent(idx);
                          }}
                        />
                      </div>
                    ))}
                  </div>
                )
              )}
            </div>
          )}
        </div>
      </div>
      <div className="site-header-spacer" aria-hidden="true" />

      {/* Offcanvas Sidebar */}
      <div className="offcanvas offcanvas-start w-75" tabIndex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div className="offcanvas-header text-white py-3 d-flex align-items-center justify-content-between" style={{ backgroundColor: "rgb(11, 83, 161)" }}>
          <div className="d-flex align-items-center gap-2">
            <FaUser className="fs-5" />
            <h6 className="mb-0 fw-semibold fs-5">Login & Signup</h6>
          </div>
          <button type="button" className="btn-close btn-close-white ms-auto" data-bs-dismiss="offcanvas" aria-label="Close" style={{ opacity: 1 }}></button>
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
                              to={`/category/${item.slug}`} 
                              className="text-decoration-none text-secondary d-block py-1"
                              style={{ fontSize: "13px" }}
                              data-bs-dismiss="offcanvas"
                            >
                              All {item.name}
                            </Link>
                          </li>
                          {itemChildren.map((sub, sIdx) => (
                            <li key={sIdx}>
                              <Link 
                                to={`/category/${sub.slug}`} 
                                className="text-decoration-none text-secondary d-block py-1"
                                style={{ fontSize: "13px" }}
                                data-bs-dismiss="offcanvas"
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
                      to={`/category/${item.slug}`} 
                      className="d-flex justify-content-between align-items-center text-decoration-none text-dark"
                      data-bs-dismiss="offcanvas"
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
                <Link to="/account" className="text-decoration-none text-dark fw-medium" style={{ fontSize: "14px" }} data-bs-dismiss="offcanvas">My Account</Link>
              </li>
              <li className="list-group-item d-flex align-items-center gap-3 px-3 py-3" style={{ borderBottom: "1px solid #f0f0f0" }}>
                <FaBoxOpen className="text-secondary fs-5" /> 
                <Link to="/orders" className="text-decoration-none text-dark fw-medium" style={{ fontSize: "14px" }} data-bs-dismiss="offcanvas">My Orders</Link>
              </li>
              <li className="list-group-item d-flex align-items-center gap-3 px-3 py-3" style={{ borderBottom: "1px solid #f0f0f0" }}>
                <FaPaperPlane className="text-secondary fs-5" /> 
                <Link to="/track-order" className="text-decoration-none text-dark fw-medium" style={{ fontSize: "14px" }} data-bs-dismiss="offcanvas">Track Order</Link>
              </li>
              <li className="list-group-item d-flex align-items-center gap-3 px-3 py-3" style={{ borderBottom: "1px solid #f0f0f0" }}>
                <FaWallet className="text-secondary fs-5" /> 
                <Link to="/wallet" className="text-decoration-none text-dark fw-medium" style={{ fontSize: "14px" }} data-bs-dismiss="offcanvas">My Wallet</Link>
              </li>
              <li className="list-group-item d-flex align-items-center gap-3 px-3 py-3" style={{ borderBottom: "1px solid #f0f0f0" }}>
                <FaStore className="text-secondary fs-5" /> 
                <Link to="/become-a-seller" className="text-decoration-none text-dark fw-medium" style={{ fontSize: "14px" }} data-bs-dismiss="offcanvas">Sell On Printmont</Link>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </>
  );
};

export default MobileHeader;
