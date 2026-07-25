import { IoIosArrowForward } from 'react-icons/io';
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
import { useAuth } from '../../context/AuthContext';
import { useNavigate, NavLink, Outlet } from 'react-router-dom';
import React, { useEffect, useState } from 'react';
import { ASSET_URL } from '../../config/apiEndpoints';

const User = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [avatarError, setAvatarError] = useState(false);

  useEffect(() => {
    if (!user) {
      navigate('/login');
    }
  }, [user, navigate]);

  const handleLogout = (e) => {
    e.preventDefault();
    logout();
  };

  const displayName = user
    ? (user.firstName || user.first_name
        ? `${user.firstName || user.first_name} ${user.lastName || user.last_name || ''}`.trim()
        : user.name || (user.email ? user.email.split('@')[0] : 'User'))
    : 'Guest';

  // First + last initial, falling back to the first letters of the name/email.
  const getInitials = () => {
    if (!user) return 'G';
    const first = (user.firstName || user.first_name || '').trim();
    const last = (user.lastName || user.last_name || '').trim();
    if (first || last) {
      return ((first[0] || '') + (last[0] || '')).toUpperCase();
    }
    // Fall back to the display name, or the email's local part only — never the
    // domain, or "amitsingh@example.com" would initial as "AE".
    const raw = (user.name || user.email || '').trim();
    const source = raw.includes('@') ? raw.split('@')[0] : raw;
    if (!source) return 'U';
    const parts = source.split(/[\s._-]+/).filter(Boolean);
    return parts.length > 1
      ? (parts[0][0] + parts[1][0]).toUpperCase()
      : source.slice(0, 2).toUpperCase();
  };

  const storedAvatar = user?.profile_picture || user?.profilePicture || user?.avatar || '';
  const avatarUrl = storedAvatar
    ? (storedAvatar.startsWith('http') ? storedAvatar : `${ASSET_URL}${storedAvatar.replace(/^\//, '')}`)
    : '';
  // Show initials when no picture is set, or when the stored one fails to load.
  const showAvatarImage = Boolean(avatarUrl) && !avatarError;

  return (
    <div className="container bg-transparent p-0">
      <div className="row p-0 m-0">
        {/* Sidebar */}
        <div className="card bg-transparent border-0 m-0 p-2 col-3 d-none d-lg-block">
          {/* User Info */}
          <div className=' bg-white shadow-sm rounded p-2'>
            <div className="card-body d-flex align-items-center">
            {showAvatarImage ? (
              <img
                src={avatarUrl}
                alt={displayName}
                className="rounded-circle me-3 flex-shrink-0"
                width="50"
                height="50"
                style={{ objectFit: 'cover' }}
                onError={() => setAvatarError(true)}
              />
            ) : (
              <div
                className="rounded-circle me-3 flex-shrink-0 d-flex align-items-center justify-content-center text-white fw-bold"
                style={{ width: 50, height: 50, backgroundColor: '#0b53a1', fontSize: '1.05rem', letterSpacing: '0.5px' }}
                title={displayName}
                aria-label={displayName}
              >
                {getInitials()}
              </div>
            )}
            <div className="d-flex gap-1 flex-column min-width-0">
              <p className="mb-0 text-muted small">Hello,</p>
              <h6 className="mb-0 fw-bold text-truncate">
                {displayName}
              </h6>
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
          <a
            href="#"
            onClick={handleLogout}
            className="d-flex justify-content-between align-items-center w-100 px-3 py-2 product text-decoration-none text-dark"
          >
            <span>
              <FaPowerOff className="me-2 text-danger" /> Logout
            </span>
            <IoIosArrowForward />
          </a>
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
