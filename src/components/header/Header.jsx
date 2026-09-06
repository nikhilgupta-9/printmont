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
import { FaBell, FaHandshake, FaShoppingBag } from "react-icons/fa";
import { AiOutlineClockCircle, AiOutlineClose } from "react-icons/ai";
import LoginDropdown from "./LoginDropdown";
import MobileHeader from "./MobileHeader";
import SearchBar from "../search/SearchBar";
import axios from "axios";
import { RiDownload2Line } from "react-icons/ri";
import { API_ENDPOINTS, ASSET_URL } from "../../config/apiEndpoints";
import { useAuth } from "../../context/AuthContext";
import { useCheckout } from "../../context/CheckoutContext";
import { useWishlist } from "../../context/WishlistContext";

const Header = ({ showCategories, showImages = false, isSticky = true, bg, color }) => {
  const { user, getUsernamePath } = useAuth();
  const { wishlistCount } = useWishlist();
  const checkoutContext = useCheckout();
  const cartItems = checkoutContext?.cartItems || [];
  const cartCount = cartItems.reduce((acc, item) => acc + (item.quantity || 1), 0);
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
        const response = await fetch(`${API_ENDPOINTS.SEARCH}?type=suggestions&q=${encodeURIComponent(searchQuery)}`);
        const data = await response.json();
        // search-api.php returns { success, query, suggestions: { products, categories, ... } }
        if (data.success && Array.isArray(data.suggestions?.products)) {
          setSearchResults(data.suggestions.products);
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

        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
          let logoData = data.data.find(l => (l.asset_type === "desktop_logo" || l.asset_type === "header_logo") && (l.is_active == 1 || l.is_active === "1"));
          if (!logoData) {
            logoData = data.data.find(l => l.is_active == 1 || l.is_active === "1") || data.data[0];
          }

          if (logoData) {
            const filePath = logoData.file_path.startsWith('/') ? logoData.file_path.substring(1) : logoData.file_path;
            const imageFullUrl = `${ASSET_URL}${filePath}${logoData.file_name}`;
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

  if (isMobile) return <MobileHeader />;

  return (
    <>
      <div ref={headerRef} className="container-fluid p-0 sticky-navbar">
        <div className="container-fluid bg-white">
          <Navbar expand="lg" className="border-bottom py-2 container-fluid px-4 px-xl-5">
            {/* py-2 = 8px top/bottom — a little breathing room around the header bar. */}
            <Container className="d-flex align-items-center py-2" style={{ maxWidth: '1440px' }}>
            
            {/* ✅ React Router Link for Logo */}
            <Navbar.Brand as={Link} to="/" className="me-3 p-0">
              <img 
                src={logo || "/PrintLogo.png"} 
                alt="PrintMont Logo" 
                style={{ height: "45px", objectFit: "contain" }} 
                onError={(e) => { e.target.src = "/PrintLogo.png"; }}
              />
            </Navbar.Brand>

            <Navbar.Toggle aria-controls="main-navbar" />
            <Navbar.Collapse id="main-navbar" className="flex-grow-1 d-flex">
              
              {/* Searchbar */}
              <div className="col-lg-7 me-4">
                <SearchBar />
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
                      {wishlistCount > 0 && (
                        <div className="notify-mes">
                          <span className="notify-num">{wishlistCount}</span>
                        </div>
                      )}
                    </div>
                    <span className="fs-7">Wishlist</span>
                  </Link>

                  {/* ✅ Cart */}
                  <Link
                    to="/cart"
                    className="d-flex align-items-center gap-3 text-decoration-none text-dark"
                  >
                    <div className="position-relative">
                      <FaShoppingBag size={22} color="#007bff" />
                      {cartCount > 0 && (
                        <div className="notify-mes">
                          <span className="notify-num">{cartCount}</span>
                        </div>
                      )}
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
          {(showCategories !== undefined ? showCategories : (location.pathname !== '/')) && (
            <div className="d-none d-lg-block">
              <Categories 
                showImages={showImages} 
                space="6px 0" 
                bg={bg || "rgb(11, 83, 161)"} 
                color={color || "white"} 
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
