import React from 'react';
import { NavLink, Outlet } from 'react-router-dom';
import {
  FaUser,
  FaHeart,
  FaWallet,
  FaGift,
  FaLock,
  FaBoxOpen,
  FaPowerOff,
  FaMapMarkerAlt,
  FaQuestionCircle
} from "react-icons/fa";
import { IoIosArrowForward } from 'react-icons/io';

const User = () => {
  return (
    <div className="container bg-transparent p-0">
      <div className="row p-0 m-0">
        {/* Sidebar */}
        <div className="card bg-transparent border-0 m-0 p-2 col-3 d-none d-lg-block">
          {/* User Info */}
          <div className=' bg-white shadow-sm rounded p-2'>
            <div className="card-body d-flex align-items-center">
            <img
              src="/default-img.jpg"
              alt="avatar"
              className="rounded-circle me-3"
              width="50"
              height="50"
            />
            <div className="d-flex gap-1 flex-column">
              <p className="mb-0 text-muted small">Hello,</p>
              <h6 className="mb-0 fw-bold">Jhon Doe</h6>
            </div>
          
          </div>

          {/* MY ORDERS */}
          <NavLink
            to="/orders"
            className={({ isActive }) =>
              `btn d-flex justify-content-between align-items-center w-100 px-3 py-3 border-bottom text-start fw-semibold ${
                isActive ? 'bg-light text-primary' : ''
              }`
            }
          >
            <span>
              <FaBoxOpen className="me-2 text-primary" />
              <span className="muted-name">MY ORDERS</span>
            </span>
            <IoIosArrowForward />
          </NavLink>

          {/* ACCOUNT SETTINGS */}
          <div className="border-bottom">
            <div className="px-3 py-2 text-muted fw-bold small d-flex align-items-center">
              <FaUser className="me-2 text-primary" /> ACCOUNT SETTINGS
            </div>
            <NavLink
              to="profile"
              className={({ isActive }) =>
                `d-flex justify-content-between align-items-center w-100 px-4 py-2 text-start product ${
                  isActive ? 'bg-light text-primary' : ''
                }`
              }
            >
              Profile Information <IoIosArrowForward />
            </NavLink>

            <NavLink
              to="manage-address"
              className={({ isActive }) =>
                `d-flex justify-content-between align-items-center w-100 px-4 py-2 text-start product ${
                  isActive ? 'bg-light text-primary' : ''
                }`
              }
            >
              Manage Addresses <IoIosArrowForward />
            </NavLink>
          </div>

          {/* Other Links */}
          <NavLink
            to="wishlist"
            className={({ isActive }) =>
              `d-flex align-items-center w-100 px-3 py-2 product ${
                isActive ? 'bg-light text-primary' : ''
              }`
            }
          >
            <FaHeart className="me-2 text-primary" /> My Wishlist
          </NavLink>

          <NavLink
            to="wallet"
            className={({ isActive }) =>
              `d-flex align-items-center w-100 px-3 py-2 product ${
                isActive ? 'bg-light text-primary' : ''
              }`
            }
          >
            <FaWallet className="me-2 text-primary" /> My Wallet
          </NavLink>

          <NavLink
            to="giftCard"
            className={({ isActive }) =>
              `d-flex align-items-center w-100 px-3 py-2 product ${
                isActive ? 'bg-light text-primary' : ''
              }`
            }
          >
            <FaGift className="me-2 text-primary" /> Gift Voucher
          </NavLink>

          <NavLink
            to="change-password"
            className={({ isActive }) =>
              `d-flex align-items-center w-100 px-3 py-2 product product ${
                isActive ? 'bg-light text-primary' : ''
              }`
            }
          >
            <FaLock className="me-2 text-primary" /> Change Password
          </NavLink>

          {/* Logout */}
          <NavLink
            to="/"
            className={({ isActive }) =>
              `d-flex justify-content-between align-items-center w-100 px-3 py-2 product ${
                isActive ? 'bg-light text-primary' : ''
              }`
            }
          >
            <span>
              <FaPowerOff className="me-2 text-primary" /> Logout
            </span>
            <IoIosArrowForward />
          </NavLink>
          </div>

          {/* Frequently Visited */}
          <div className="p-3 small text-muted mt-2 bg-white rounded shadow-sm">
            <strong className="d-block mb-1">Frequently Visited:</strong>
            <div className="d-flex gap-3 flex-wrap">
              <NavLink
                to="/track-order"
                className={({ isActive }) =>
                  `text-primary d-flex align-items-center ${
                    isActive ? 'text-decoration-underline' : ''
                  }`
                }
              >
                <FaMapMarkerAlt className="me-1" /> Track Order
              </NavLink>
              <NavLink
                to="/help-center"
                className={({ isActive }) =>
                  `text-primary d-flex align-items-center ${
                    isActive ? 'text-decoration-underline' : ''
                  }`
                }
              >
                <FaQuestionCircle className="me-1" /> Help Center
              </NavLink>
            </div>
          </div>
        </div>

        {/* Right Side Content */}
        <div className="col-12 col-lg-9  p-2">
          <Outlet />
        </div>
      </div>
    </div>
  );
};

export default User;
