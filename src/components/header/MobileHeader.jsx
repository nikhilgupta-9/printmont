import React, { useEffect, useRef, useState } from 'react';
// import './App.css';
import { GiHamburgerMenu, GiShoppingCart } from "react-icons/gi";
import { FaRegUser, FaUser, FaBoxOpen, FaWallet, FaPaperPlane, FaStore } from 'react-icons/fa';
import { CiHeart } from "react-icons/ci";
import { IoSearchSharp } from "react-icons/io5";
import { Link } from 'react-router-dom';

const MobileHeader = () => {
  const [dropdowns, setDropdowns] = useState({});
  const toggleDropdown = (key) => {
    setDropdowns((prev) => ({
      ...prev,
      [key]: !prev[key],
    }));
  };
  

  return (
    <>
      <div className='bg-white shadow-sm position-relative'>
      {/* === TOPBAR (logo + icons) === */}
      <div className=" d-flex justify-content-between align-items-center px-3 py-2">
        <div className="d-flex align-items-center gap-3">
          <button
            className="btn p-0 border-0"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#offcanvasNavbar"
          >
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

      {/* === SEARCHBAR (stays visible when top hides) === */}
      <div className="navbar-search border-top px-3 py-2 d-flex align-items-center gap-2">
        <IoSearchSharp className="fs-4 text-primary" />
        <input id="mobile-all-product-search" name="mobile-all-product-search" type="text" className="form-control ms-2"
          placeholder="Search..."
          autoComplete="off"
        />
      </div>
    </div>


      {/* Offcanvas Sidebar (unchanged) */}
      <div
        className="offcanvas offcanvas-start w-75"
        tabIndex="-1"
        id="offcanvasNavbar"
        aria-labelledby="offcanvasNavbarLabel"
      >
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
                  <div
                    className="d-flex justify-content-between align-items-center"
                    onClick={() => toggleDropdown(key)}
                    style={{ cursor: 'pointer' }}
                  >
                    <span className="text-muted small fw-semibold">Set {i + 1}</span>
                    <span className="fw-bold fs-5">{dropdowns[key] ? '−' : '+'}</span>
                  </div>
                  {dropdowns[key] && (
                    <ul className="list-unstyled mt-2 ps-3">
                      <li>
                        <Link to="/corporate-gifts" className="text-decoration-none text-secondary d-block py-1">
                          Corporate Gifts
                        </Link>
                      </li>
                      <li>
                        <Link to="/employee-gifts" className="text-decoration-none text-secondary d-block py-1">
                          Employee Gifts
                        </Link>
                      </li>
                    </ul>
                  )}
                </li>
              );
            })}
          </ul>

          <div className="bg-light mt-2 pt-1">
            <ul className="list-group list-group-flush m-0 p-0">
              <li className="list-group-item d-flex align-items-center gap-2">
                <FaUser /> <Link to="/account" className="text-decoration-none text-dark">My Account</Link>
              </li>
              <li className="list-group-item d-flex align-items-center gap-2">
                <FaBoxOpen /> <Link to="/orders" className="text-decoration-none text-dark">My Orders</Link>
              </li>
              <li className="list-group-item d-flex align-items-center gap-2">
                <FaPaperPlane /> <Link to="/track-order" className="text-decoration-none text-dark">Track Order</Link>
              </li>
              <li className="list-group-item d-flex align-items-center gap-2">
                <FaWallet /> <Link to="/wallet" className="text-decoration-none text-dark">My Wallet</Link>
              </li>
              <li className="list-group-item d-flex align-items-center gap-2">
                <FaStore /> <Link to="/sell" className="text-decoration-none text-dark">Sell On Printmont</Link>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </>
  );
};

export default MobileHeader;
