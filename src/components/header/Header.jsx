import React, { useEffect, useState, useRef } from "react";
import { Navbar, Container, Nav } from "react-bootstrap";
import { GoHeart } from "react-icons/go";
import { PiDotsThreeOutlineVerticalFill } from "react-icons/pi";
import { LuChartNoAxesCombined } from "react-icons/lu";
import { IoMdNotifications, IoMdNotificationsOutline } from "react-icons/io";
import { FcAdvertising, FcCustomerSupport } from "react-icons/fc";
import { IoSearch } from "react-icons/io5";
import { BsCart4 } from "react-icons/bs";
import LoginDropdown from "./LoginDropdown";
import MobileHeader from "./MobileHeader";
import { BiSupport } from "react-icons/bi";
import { CiBullhorn } from "react-icons/ci";

const Header = () => {
  const [isMobile, setIsMobile] = useState(window.innerWidth < 992);
  const [showPreferences, setShowPreferences] = useState(false);
  const [showLoginDropdown, setShowLoginDropdown] = useState(false);

  const preferenceRef = useRef();
  const loginRef = useRef();

  // Detect screen resize
  useEffect(() => {
    const handleResize = () => setIsMobile(window.innerWidth < 992);
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  // If mobile view, render mobile header
  if (isMobile) {
    return <MobileHeader />;
  }

  // Otherwise render desktop header
  return (
    <div className="container-fluid p-0 sticky-navbar">
      <div className="container-fluid bg-white headon">
        <Navbar expand="lg" className="border-bottom py-2 container-fluid px-5">
          <Container fluid className="d-flex align-items-center">
            <Navbar.Brand href="/" className="me-3">
              <img src="/PrintLogo.png" alt="Logo" height="40" className="me-lg-5" />
            </Navbar.Brand>

            <Navbar.Toggle aria-controls="main-navbar" />

            <Navbar.Collapse id="main-navbar" className="flex-grow-1 d-flex">
              {/* Searchbar */}
              <div className="nav-searchbar rounded border col-lg-7 me-4">
                <div className="input-group">
                  <input
                    id="desktop-all-product-search"
                    name="desktop-all-product-search"
                    type="text"
                    className="form-control bg-transparent border-0"
                    placeholder="Search for products, Brands and more"
                    aria-label="Search"
                    autoComplete="off"
                  />
                  <button
                    className="rounded border-0 bg-transparent me-3 mb-1"
                    type="button"
                  >
                    <IoSearch size={20} color="rgb(41, 117, 240)" />
                  </button>
                </div>
              </div>

              {/* Nav Links */}
              <Nav className="col-lg-5 d-flex align-items-center justify-content-evenly text-center gap-4">
                <div className="d-flex align-items-center justify-content-evenly w-100">
                  {/* Wishlist */}
                  <a
                    href="/user/wishlist"
                    className="d-flex align-items-center gap-3 text-decoration-none text-dark"
                  >
                    <div className="position-relative">
                      <GoHeart size={25} color="#007bff" />
                      <div className="notify-mes">
                        <span className="notify-num">4</span>
                      </div>
                    </div>
                    <span className="fs-7">Wishlist</span>
                  </a>

                  {/* Cart */}
                  <a
                    href="/cart"
                    className="d-flex align-items-center gap-3 text-decoration-none text-dark"
                  >
                    <div className="position-relative">
                      <BsCart4 size={25} color="#007bff" />
                      <div className="notify-mes">
                        <span className="notify-num">4</span>
                      </div>
                    </div>
                    <span className="fs-7">Cart</span>
                  </a>

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

                {/* Preferences */}
                <div
                  className="position-relative rounded d-flex flex-column align-items-center"
                  ref={preferenceRef}
                  onMouseEnter={() => setShowPreferences(true)}
                  onMouseLeave={() => setShowPreferences(false)}
                  style={{ cursor: "pointer" }}
                >
                  <PiDotsThreeOutlineVerticalFill
                    size={27}
                    className="p-1 text-dark"
                  />
                  {showPreferences && (
                    <div className="dropdown-menu show preference-menu position-absolute end-0 top-100 z-3 d-block min-w-200 p-2 shadow rounded bg-white border-0">
                      <a
                        href="/seller"
                        className="dropdown-item d-flex align-items-center gap-2"
                      >
                        <LuChartNoAxesCombined size={18} /> Become a Seller
                      </a>
                      <a
                        href="/notifications"
                        className="dropdown-item d-flex align-items-center gap-2"
                      >
                        <IoMdNotificationsOutline size={18} /> Notification Preferences
                      </a>
                      <a
                        href="/support"
                        className="dropdown-item d-flex align-items-center gap-2"
                      >
                        <BiSupport size={18} /> 24x7 Customer Care
                      </a>
                      <a
                        href="/advertise"
                        className="dropdown-item d-flex align-items-center gap-2"
                      >
                        <CiBullhorn size={18} /> Advertise
                      </a>
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
