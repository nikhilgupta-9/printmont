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

const MobileHeader = () => {
  const [dropdowns, setDropdowns] = useState({});
  const [searchQuery, setSearchQuery] = useState('');
  const [showSearchDropdown, setShowSearchDropdown] = useState(false);
  const [recentSearches, setRecentSearches] = useState(["T-Shirt", "Mug", "Notebook"]);

  const searchRef = useRef();
  const headerRef = useRef();

  const productSuggestions = [
    { name: "Custom Hoodie", icon: <LuChartNoAxesCombined size={16} /> },
    { name: "Sticker Pack", icon: <FaHandshake size={16} /> },
  ];

  const reservedKeywords = ["Best Seller", "Limited Edition", "Discount"];

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
  }, []);

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
              <img src="./PrintLogo.png" alt="Printmont Logo" style={{ height: "30px" }} />
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
              className="position-absolute bg-white border rounded shadow px-2 w-100 mt-2 z-10"
              style={{ maxHeight:'400px', overflowY:'auto', top:'45px', left:0 }}
            >
              {/* Recent Searches */}
              {recentSearches.length > 0 && (
                <div className="mb-2">
                  <div className="fw-bold text-muted fs-6 mb-1">Recent Searches</div>
                  {recentSearches.map((item, idx) => (
                    <div key={idx} className="d-flex align-items-center justify-content-between px-2 py-1 hover-bg-light">
                      <div className="d-flex align-items-center gap-2 w-100 p-2" style={{ cursor: "pointer" }} onClick={() => selectKeyword(item)}>
                        <AiOutlineClockCircle className="text-secondary" />
                        <span className="w-100">{item}</span>
                      </div>
                      <AiOutlineClose className="text-secondary" style={{ cursor: "pointer" }} onClick={() => removeRecent(idx)} />
                    </div>
                  ))}
                </div>
              )}

              {/* Product Suggestions */}
              {productSuggestions.length > 0 && (
                <div className="mb-2">
                  <div className="fw-bold text-muted fs-6 mb-1">Products</div>
                  {productSuggestions.map((item, idx) => (
                    <div key={idx} className="d-flex align-items-center px-2 py-1 hover-bg-light" style={{ cursor: "pointer" }} onClick={() => selectKeyword(item.name)}>
                      <span className="me-2"><IoSearchSharp /></span>
                      <span>{item.name}</span>
                    </div>
                  ))}
                </div>
              )}

              {/* Reserved Keywords */}
              {reservedKeywords.length > 0 && (
                <div>
                  <div className="fw-bold text-muted fs-6 mb-1">Trending Keywords</div>
                  {reservedKeywords.map((item, idx) => (
                    <div key={idx} className="d-flex align-items-center px-2 py-1 hover-bg-light" style={{ cursor: "pointer" }} onClick={() => selectKeyword(item)}>
                      <CiBullhorn className="me-2 text-warning" />
                      <span>{item}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>
      </div>
      <div className="site-header-spacer" aria-hidden="true" />

      {/* Offcanvas Sidebar (unchanged) */}
      <div className="offcanvas offcanvas-start w-75" tabIndex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div className="offcanvas-header bg-primary text-white py-3">
          <div className="d-flex align-items-center gap-2">
            <FaUser />
            <h6 className="mb-0">Login & Signup</h6>
          </div>
          <button type="button" className="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>

        <div className="offcanvas-body p-0">
          <ul className="list-group rounded-0">
            {Array.from({ length: 8 }, (_, i) => {
              const key = `set${i + 1}`;
              return (
                <li className="list-group-item px-3 py-1" key={key}>
                  <div className="d-flex justify-content-between align-items-center" onClick={() => toggleDropdown(key)} style={{ cursor: 'pointer' }}>
                    <span className="text-muted small fw-semibold">Set {i + 1}</span>
                    <span className="fw-bold fs-5">{dropdowns[key] ? '−' : '+'}</span>
                  </div>
                  {dropdowns[key] && (
                    <ul className="list-unstyled mt-2 ps-3">
                      <li>
                        <Link to="/corporate-gifts" className="text-decoration-none text-secondary d-block py-1">Corporate Gifts</Link>
                      </li>
                      <li>
                        <Link to="/employee-gifts" className="text-decoration-none text-secondary d-block py-1">Employee Gifts</Link>
                      </li>
                    </ul>
                  )}
                </li>
              );
            })}
          </ul> 

          <div className="bg-light mt-2 pt-1">
            <ul className="list-group list-group-flush m-0 p-0">
              <li className="list-group-item d-flex align-items-center gap-2"><FaUser /> <Link to="/account" className="text-decoration-none text-dark">My Account</Link></li>
              <li className="list-group-item d-flex align-items-center gap-2"><FaBoxOpen /> <Link to="/orders" className="text-decoration-none text-dark">My Orders</Link></li>
              <li className="list-group-item d-flex align-items-center gap-2"><FaPaperPlane /> <Link to="/track-order" className="text-decoration-none text-dark">Track Order</Link></li>
              <li className="list-group-item d-flex align-items-center gap-2"><FaWallet /> <Link to="/wallet" className="text-decoration-none text-dark">My Wallet</Link></li>
              <li className="list-group-item d-flex align-items-center gap-2"><FaStore /> <Link to="/sell" className="text-decoration-none text-dark">Sell On Printmont</Link></li>
            </ul>
          </div>
        </div>
      </div>
    </>
  );
};

export default MobileHeader;
