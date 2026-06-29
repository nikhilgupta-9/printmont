import React, { useEffect, useState, useRef } from "react";
import { Navbar, Container, Nav } from "react-bootstrap";
import { Link, useLocation } from "react-router-dom"; // ✅ React Router Link
import Categories from "../pages/category-list/Categories";
import { GoHeart } from "react-icons/go";
import { PiDotsThreeOutlineVerticalFill, PiHeadsetBold } from "react-icons/pi";
import { LuChartNoAxesCombined } from "react-icons/lu";
import { IoIosSearch, IoMdNotificationsOutline } from "react-icons/io";
import { IoSearch } from "react-icons/io5";
import { BsCart4, BsDownload } from "react-icons/bs";
import { CiBullhorn } from "react-icons/ci";
import { FaBell, FaHandshake } from "react-icons/fa";
import { AiOutlineClockCircle, AiOutlineClose } from "react-icons/ai";
import LoginDropdown from "./LoginDropdown";
import MobileHeader from "./MobileHeader";
import axios from "axios";
import { RiDownload2Line } from "react-icons/ri";
import { API_ENDPOINTS, ASSET_URL } from "../../config/apiEndpoints";
import { useAuth } from "../../context/AuthContext";

const Header = () => {
  const { user, getUsernamePath } = useAuth();
  const usernamePath = getUsernamePath();
  const location = useLocation();
  const [isMobile, setIsMobile] = useState(window.innerWidth < 992);
  const [showPreferences, setShowPreferences] = useState(false);
  const [showLoginDropdown, setShowLoginDropdown] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [showSearchDropdown, setShowSearchDropdown] = useState(false);
  const [recentSearches, setRecentSearches] = useState(["T-Shirt", "Mug", "Notebook"]);
  const [logo, setLogo] = useState(null);
  
  // Search API States
  const [searchResults, setSearchResults] = useState([]);
  const [isSearching, setIsSearching] = useState(false);

  const preferenceRef = useRef();
  const loginRef = useRef();
  const searchRef = useRef();
  const headerRef = useRef();

  // Handle mobile view
  useEffect(() => {
    const handleResize = () => setIsMobile(window.innerWidth < 992);
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

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

  useEffect(() => {
    const fetchLogo = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.LOGO);
        const data = await response.json();

        if (data.success && data.data.length > 0) {
          // Find the desktop logo, otherwise fallback to the first active logo
          let logoData = data.data.find(l => l.asset_type === "desktop_logo" && l.is_active === "1");
          if (!logoData) {
            logoData = data.data.find(l => l.is_active === "1") || data.data[0];
          }

          // Ensure path formatting is safe
          const filePath = logoData.file_path.startsWith('/') ? logoData.file_path.substring(1) : logoData.file_path;
          const imageFullUrl = `${ASSET_URL}${filePath}${logoData.file_name}`;

          setLogo(imageFullUrl);
        } else {
          console.warn("No logo found in response.");
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
  }, [isMobile, logo]);

  if (isMobile) return <MobileHeader />;

  return (
    <>
      <div ref={headerRef} className="container-fluid p-0 sticky-navbar">
        <div className="container-fluid bg-white">
          <Navbar expand="lg" className="border-bottom py-1 container-fluid px-5">
            <Container className="d-flex align-items-center" style={{ maxWidth: '1440px' }}>
            
            {/* ✅ React Router Link for Logo */}
            <Navbar.Brand as={Link} to="/" className="me-3">
              {logo ? (
                <img src={logo} alt="Site Logo" style={{ height: "45px" }} />
              ) : (
                <p>Loading...</p>
              )}
            </Navbar.Brand>

            <Navbar.Toggle aria-controls="main-navbar" />
            <Navbar.Collapse id="main-navbar" className="flex-grow-1 d-flex">
              
              {/* Searchbar */}
              <div className="nav-searchbar rounded border col-lg-7 me-4" ref={searchRef}>
                <div className="input-group position-relative">
                  <input
                    id="desktop-all-product-search"
                    type="text"
                    className="form-control bg-transparent border-0"
                    placeholder="Search for products, Brands and more"
                    autoComplete="off"
                    value={searchQuery}
                    onFocus={() => setShowSearchDropdown(true)}
                    onChange={(e) => setSearchQuery(e.target.value)}
                  />
                  <button className="rounded border-0 bg-transparent me-3 mb-1" type="button">
                    <IoSearch size={20} color="rgb(41, 117, 240)" />
                  </button>

                  {/* Search Dropdown */}
                  {showSearchDropdown && (
                    <div
                      className="position-absolute bg-white rounded shadow w-100 mt-1"
                      style={{ maxHeight: "400px", overflowY: "auto", top: "100%", left: 0, zIndex: 1050 }}
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

              {/* Nav Links */}
              <Nav className="col-lg-5 d-flex align-items-center justify-content-evenly text-center gap-4">
                <div className="d-flex align-items-center justify-content-evenly w-100">

                  {/* ✅ Wishlist */}
                  <Link
                    to={`/${usernamePath}/wishlist`}
                    className="d-flex align-items-center gap-3 text-decoration-none text-dark"
                  >
                    <div className="position-relative">
                      <GoHeart size={25} color="#007bff" />
                      <div className="notify-mes">
                        <span className="notify-num">4</span>
                      </div>
                    </div>
                    <span className="fs-7">Wishlist</span>
                  </Link>

                  {/* ✅ Cart */}
                  <Link
                    to="/cart"
                    className="d-flex align-items-center gap-3 text-decoration-none text-dark"
                  >
                    <div className="position-relative">
                      <BsCart4 size={25} color="#007bff" />
                      <div className="notify-mes">
                        <span className="notify-num">4</span>
                      </div>
                    </div>
                    <span className="fs-7">Cart</span>
                  </Link>

                  {/* Login Dropdown */}
                  <div
                    className="d-flex flex-column align-items-center gap-1 nav-link text-dark p-0"
                    ref={loginRef}
                    onMouseEnter={() => setShowLoginDropdown(true)}
                    onMouseLeave={() => setShowLoginDropdown(false)}
                    style={{ cursor: "pointer" }}
                  >
                    <LoginDropdown isHovered={showLoginDropdown} />
                  </div>
                </div>

                {/* ✅ Preferences Dropdown */}
                <div
                  className="position-relative rounded d-flex flex-column align-items-center"
                  ref={preferenceRef}
                  onMouseEnter={() => setShowPreferences(true)}
                  onMouseLeave={() => setShowPreferences(false)}
                  style={{ cursor: "pointer" }}
                >
                  <PiDotsThreeOutlineVerticalFill size={27} className="p-1 text-dark" />
                  {showPreferences && (
                    <div className="dropdown-menu show preference-menu position-absolute end-0 top-100 z-3 d-block min-w-200 p-2 shadow rounded bg-white border-0">
                      
                      <Link to={user ? "/notification-preference" : "/login"} className="dropdown-item d-flex align-items-center gap-2">
                        <FaBell size={23}  className="text-warning  pa"/> Notification Preferences
                      </Link>

                      <Link to={user ? "/support" : "/login"} className="dropdown-item d-flex align-items-center gap-2">
                        <PiHeadsetBold size={21} className="me-1 rounded-circle bg-theme pa"/> Support
                      </Link>

                      <Link to={user ? "/business-solutions" : "/login"} className="dropdown-item d-flex align-items-center gap-2">
                        <LuChartNoAxesCombined size={21} className="me-1 rounded-circle bg-theme pa" /> Business Solutions
                      </Link>

                      <Link to={user ? "/become-a-seller" : "/login"} className="dropdown-item d-flex align-items-center gap-2">
                        <FaHandshake size={21} className="me-1 rounded-circle bg-theme pa" /> Become a Seller
                      </Link>

                      <Link to={user ? "/app-download" : "/login"} className="dropdown-item d-flex align-items-center gap-2">
                        <RiDownload2Line size={21} className="me-1 rounded-circle bg-theme pa" /> Download the App
                      </Link>
                    </div>
                  )}
                </div>
              </Nav>
            </Navbar.Collapse>
            </Container>
          </Navbar>
        </div>
      </div>
      {location.pathname !== '/' && (
        <div className="d-none d-lg-block">
          <Categories showImages={false} space="5px 0" bg="rgb(11, 83, 161)" color="white" isSticky={true} />
        </div>
      )}
      <div className="site-header-spacer" aria-hidden="true" />
    </>
  );
};

export default Header;
