import React, { useEffect, useState, useRef } from "react";
import { Navbar, Container, Nav } from "react-bootstrap";
import { Link } from "react-router-dom"; // ✅ React Router Link
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


const Header = () => {
  const [isMobile, setIsMobile] = useState(window.innerWidth < 992);
  const [showPreferences, setShowPreferences] = useState(false);
  const [showLoginDropdown, setShowLoginDropdown] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [showSearchDropdown, setShowSearchDropdown] = useState(false);
  const [recentSearches, setRecentSearches] = useState(["T-Shirt", "Mug", "Notebook"]);
  const [logo, setLogo] = useState(null);

  const preferenceRef = useRef();
  const loginRef = useRef();
  const searchRef = useRef();

  const productSuggestions = [
    { name: "Custom Hoodie", icon: <LuChartNoAxesCombined size={16} /> },
    { name: "Sticker Pack", icon: <FaHandshake size={16} /> },
  ];

  const reservedKeywords = ["Best Seller", "Limited Edition", "Discount"];

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

  if (isMobile) return <MobileHeader />;

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

  // Fetch Logo
  // useEffect(() => {
  //   const fetchLogo = async () => {
  //     try {
  //       const baseURL = import.meta.env.VITE_BASE_URL;
  //       const response = await axios.get(`${baseURL}api/logo-api.php`);
  //       if (response.data.success) {
  //         setLogo(response.data.data[0].file_name);
  //         console.log(response.data.data[0].file_name);
  //       } else {
  //         console.warn("No logo found:", response.data.message);
  //       }
  //     } catch (error) {
  //       console.error("Error fetching logo:", error);
  //     }
  //   };
  //   fetchLogo();
  // }, []);
  

 useEffect(() => {
  const fetchLogo = async () => {
    try {
      const baseURL = import.meta.env.VITE_BASE_URL;
      const response = await axios.get(`${baseURL}api/logo-api.php`);

      if (response.data.success && response.data.data.length > 0) {
        const logoData = response.data.data[0];

        const imageFullUrl = `${baseURL}${logoData.file_path}${logoData.file_name}`;

        setLogo(imageFullUrl);

        console.log("Logo loaded:", imageFullUrl);
      } else {
        console.warn("No logo found:", response.data.message);
      }
    } catch (error) {
      console.error("Error fetching logo:", error);
    }
  };

  fetchLogo();
}, []);

  return (
    <div className="container-fluid p-0 sticky-navbar">
      <div className="container-fluid bg-white headon">
        <Navbar expand="lg" className="border-bottom py-2 container-fluid px-5">
          <Container fluid className="d-flex align-items-center">
            
            {/* ✅ React Router Link for Logo */}
            <Navbar.Brand as={Link} to="/" className="me-3">
              {logo ? (
                <img src={logo} alt="Site Logo" style={{ height: "60px" }} />
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
                      className="position-absolute bg-white rounded shadow px-2 w-100 mt-1 z-10"
                      style={{ height: "auto", overflowY: "auto", top: "40px" }}
                    >
                      {/* Recent Searches */}
                      {recentSearches.length > 0 && (
                        <div className="mb-0">
                          <div className="fw-bold text-muted fs-6">Recent Searches</div>
                          {recentSearches.map((item, idx) => (
                            <div
                              key={idx}
                              className="d-flex align-items-center justify-content-between px-2 py-0 hover-bg-light"
                            >
                              <div
                                className="d-flex align-items-center gap-2 w-100 p-2"
                                style={{ cursor: "pointer" }}
                                onClick={() => selectKeyword(item)}
                              >
                                <AiOutlineClockCircle className="text-secondary" />
                                <span className="w-100">{item}</span>
                              </div>
                              <AiOutlineClose
                                className="text-secondary"
                                style={{ cursor: "pointer" }}
                                onClick={() => removeRecent(idx)}
                              />
                            </div>
                          ))}
                        </div>
                      )}

                      {/* Product Suggestions */}
                      {productSuggestions.length > 0 && (
                        <div className="mb-2">
                          <div className="fw-bold text-muted fs-6 mb-1">Products</div>
                          {productSuggestions.map((item, idx) => (
                            <div
                              key={idx}
                              className="d-flex align-items-center px-2 py-1 hover-bg-light"
                              style={{ cursor: "pointer" }}
                              onClick={() => selectKeyword(item.name)}
                            >
                              <span className="me-2"><IoIosSearch /></span>
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
                            <div
                              key={idx}
                              className="d-flex align-items-center px-2 py-1 hover-bg-light"
                              style={{ cursor: "pointer" }}
                              onClick={() => selectKeyword(item)}
                            >
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

              {/* Nav Links */}
              <Nav className="col-lg-5 d-flex align-items-center justify-content-evenly text-center gap-4">
                <div className="d-flex align-items-center justify-content-evenly w-100">

                  {/* ✅ Wishlist */}
                  <Link
                    to="/user/wishlist"
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
                      
                      <Link to="/notification-preference" className="dropdown-item d-flex align-items-center gap-2">
                        <FaBell size={23}  className="text-warning  pa"/> Notification Preferences
                      </Link>

                      <Link to="/support" className="dropdown-item d-flex align-items-center gap-2">
                        <PiHeadsetBold size={21} className="me-1 rounded-circle bg-theme pa"/> Support
                      </Link>

                      <Link to="/seller" className="dropdown-item d-flex align-items-center gap-2">
                        <LuChartNoAxesCombined size={21} className="me-1 rounded-circle bg-theme pa" /> Advertise
                      </Link>

                      <Link to="/seller" className="dropdown-item d-flex align-items-center gap-2">
                        <FaHandshake size={21} className="me-1 rounded-circle bg-theme pa" /> Become a Seller
                      </Link>

                      <Link to="/app-download" className="dropdown-item d-flex align-items-center gap-2">
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
  );
};

export default Header;
