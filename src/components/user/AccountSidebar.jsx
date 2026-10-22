import React, { useState, useEffect } from 'react';
import { IoIosArrowForward } from 'react-icons/io';
import {
  FaUser,
  FaWallet,
  FaGift,
  FaLock,
  FaBoxOpen,
  FaPowerOff,
  FaMapMarkerAlt,
  FaCoins,
  FaStar
} from "react-icons/fa";
import { NavLink, useLocation, Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { ASSET_URL, API_ENDPOINTS } from '../../config/apiEndpoints';
import './AccountSidebar.css';

const AccountSidebar = ({ activeKey = '', children }) => {
  const { user, logout, getUsernamePath } = useAuth();
  const location = useLocation();
  const [avatarError, setAvatarError] = useState(false);
  const usernamePath = getUsernamePath ? getUsernamePath() : 'user';

  const handleLogout = (e) => {
    e.preventDefault();
    if (logout) logout();
  };

  const [walletBalance, setWalletBalance] = useState(user?.wallet_balance !== undefined ? Number(user.wallet_balance) : 0);
  const [coinBalance, setCoinBalance] = useState(user?.coins !== undefined ? Number(user.coins) : 100);

  useEffect(() => {
    let isMounted = true;
    const fetchBalances = async () => {
      try {
        const token = localStorage.getItem('token') || sessionStorage.getItem('token');
        const headers = token ? { Authorization: `Bearer ${token}` } : {};
        const res = await fetch(API_ENDPOINTS.WALLET || '/api/wallet-api.php', { headers });
        const json = await res.json();
        if (isMounted && json && json.success && json.data) {
          if (json.data.wallet?.balance !== undefined) {
            setWalletBalance(Number(json.data.wallet.balance));
          }
          if (json.data.coins?.balance !== undefined) {
            setCoinBalance(Number(json.data.coins.balance));
          }
        }
      } catch (err) {
        // silent fallback
      }
    };
    if (user) {
      fetchBalances();
    }
    return () => { isMounted = false; };
  }, [user]);

  const firstName = user?.firstName || user?.first_name || (user?.name ? user.name.split(' ')[0] : 'Guest');
  const displayName = user
    ? (user.firstName || user.first_name
        ? `${user.firstName || user.first_name} ${user.lastName || user.last_name || ''}`.trim()
        : user.name || (user.email ? user.email.split('@')[0] : 'User'))
    : 'Buyer name here';

  const getInitials = () => {
    if (!user) return 'G';
    const first = (user.firstName || user.first_name || '').trim();
    const last = (user.lastName || user.last_name || '').trim();
    if (first || last) {
      return ((first[0] || '') + (last[0] || '')).toUpperCase();
    }
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
  const showAvatarImage = Boolean(avatarUrl) && !avatarError;

  // Determine current active item
  const currentPath = location.pathname;
  const isOrdersActive = activeKey === 'orders' || currentPath.endsWith('/orders') || currentPath === '/orders';
  const isCoinsActive = activeKey === 'coins' || currentPath.endsWith('/coins');
  const isProfileActive = activeKey === 'profile' || currentPath.endsWith('/profile') || currentPath === `/${usernamePath}` || currentPath === '/user';
  const isWalletActive = activeKey === 'wallet' || currentPath.endsWith('/wallet');
  const isReviewsActive = activeKey === 'reviews' || currentPath.endsWith('/reviews');
  const isAddressActive = activeKey === 'address' || currentPath.endsWith('/manage-address');
  const isCouponsActive = activeKey === 'giftcard' || currentPath.endsWith('/giftcard');
  const isPasswordActive = activeKey === 'password' || currentPath.endsWith('/change-password');
  const isTrackOrderActive = activeKey === 'track-order' || currentPath.includes('track-order');

  return (
    <div className="account-sidebar-container">
      {/* 1. User Info Header Card (Hi Guest / Buyer name here) */}
      <div className="sidebar-user-box">
        {showAvatarImage ? (
          <img
            src={avatarUrl}
            alt={displayName}
            className="sidebar-user-avatar"
            style={{ objectFit: 'cover' }}
            onError={() => setAvatarError(true)}
          />
        ) : (
          <div className="sidebar-user-avatar">
            {getInitials()}
          </div>
        )}
        <div className="sidebar-user-info">
          <span className="sidebar-user-greeting">
            Hi {user ? firstName : 'Guest'}
          </span>
          <span className="sidebar-user-name" title={displayName}>
            {displayName}
          </span>
        </div>
      </div>

      {/* Prominently render Contextual Children if any */}
      {children}

      {/* 2. Menu Cards Stack (Exact Layout matching Wireframe Image 1) */}
      <div className="sidebar-menu-list">
        
        {/* Card 1: Cash Coins */}
        <Link
          to={`/${usernamePath}/coins`}
          className={`sidebar-menu-card ${isCoinsActive ? 'active' : ''}`}
        >
          <div className="sidebar-menu-card-left">
            <span className="sidebar-menu-card-icon">
              <FaCoins style={{ color: isCoinsActive ? '#ffffff' : '#f59e0b' }} />
            </span>
            <div className="sidebar-menu-card-text">
              <span className="sidebar-menu-card-title">Cash Coins</span>
              <span className="sidebar-menu-card-sub">Quick Checkout, Easy Adjust Coins.</span>
            </div>
          </div>
          <IoIosArrowForward className="sidebar-menu-card-chevron" />
        </Link>

        {/* Card 2: My Profile Settings */}
        <Link
          to={`/${usernamePath}/profile`}
          className={`sidebar-menu-card ${isProfileActive ? 'active' : ''}`}
        >
          <div className="sidebar-menu-card-left">
            <span className="sidebar-menu-card-icon">
              <FaUser style={{ color: isProfileActive ? '#ffffff' : '#00529b' }} />
            </span>
            <div className="sidebar-menu-card-text">
              <span className="sidebar-menu-card-title">My Profile Settings</span>
              <span className="sidebar-menu-card-sub">Manage your profile and setting.</span>
            </div>
          </div>
          <IoIosArrowForward className="sidebar-menu-card-chevron" />
        </Link>

        {/* Card 3: My Orders (ACTIVE on Orders Page) */}
        <Link
          to={`/${usernamePath}/orders`}
          className={`sidebar-menu-card ${isOrdersActive ? 'active' : ''}`}
        >
          <div className="sidebar-menu-card-left">
            <span className="sidebar-menu-card-icon">
              <FaBoxOpen style={{ color: isOrdersActive ? '#ffffff' : '#00529b' }} />
            </span>
            <div className="sidebar-menu-card-text">
              <span className="sidebar-menu-card-title">My Orders</span>
              <span className="sidebar-menu-card-sub">Order Related issues, Track Order &amp; Download Invoice.</span>
            </div>
          </div>
          <IoIosArrowForward className="sidebar-menu-card-chevron" />
        </Link>

        {/* Card 4: My Wallets */}
        <Link
          to={`/${usernamePath}/wallet`}
          className={`sidebar-menu-card ${isWalletActive ? 'active' : ''}`}
        >
          <div className="sidebar-menu-card-left">
            <span className="sidebar-menu-card-icon">
              <FaWallet style={{ color: isWalletActive ? '#ffffff' : '#00529b' }} />
            </span>
            <div className="sidebar-menu-card-text">
              <span className="sidebar-menu-card-title">My Wallets</span>
              <span className="sidebar-menu-card-sub">Manage all your refund &amp; gift cards.</span>
            </div>
          </div>
          <IoIosArrowForward className="sidebar-menu-card-chevron" />
        </Link>

        {/* Card 5: Products Review */}
        <Link
          to={`/${usernamePath}/reviews`}
          className={`sidebar-menu-card ${isReviewsActive ? 'active' : ''}`}
        >
          <div className="sidebar-menu-card-left">
            <span className="sidebar-menu-card-icon">
              <FaStar style={{ color: isReviewsActive ? '#ffffff' : '#f59e0b' }} />
            </span>
            <div className="sidebar-menu-card-text">
              <span className="sidebar-menu-card-title">Products Review</span>
              <span className="sidebar-menu-card-sub">Good feedback and review your product .</span>
            </div>
          </div>
          <IoIosArrowForward className="sidebar-menu-card-chevron" />
        </Link>

        {/* Card 6: Addresses Settings */}
        <Link
          to={`/${usernamePath}/manage-address`}
          className={`sidebar-menu-card ${isAddressActive ? 'active' : ''}`}
        >
          <div className="sidebar-menu-card-left">
            <span className="sidebar-menu-card-icon">
              <FaMapMarkerAlt style={{ color: isAddressActive ? '#ffffff' : '#00529b' }} />
            </span>
            <div className="sidebar-menu-card-text">
              <span className="sidebar-menu-card-title">Addresses Settings</span>
              <span className="sidebar-menu-card-sub">Manage profile and address for a hassle-free checkout.</span>
            </div>
          </div>
          <IoIosArrowForward className="sidebar-menu-card-chevron" />
        </Link>

        {/* Card 7: Coupons */}
        <Link
          to={`/${usernamePath}/giftcard`}
          className={`sidebar-menu-card ${isCouponsActive ? 'active' : ''}`}
        >
          <div className="sidebar-menu-card-left">
            <span className="sidebar-menu-card-icon">
              <FaGift style={{ color: isCouponsActive ? '#ffffff' : '#00529b' }} />
            </span>
            <div className="sidebar-menu-card-text">
              <span className="sidebar-menu-card-title">Coupons</span>
              <span className="sidebar-menu-card-sub">Manage coupon for additional discounts.</span>
            </div>
          </div>
          <IoIosArrowForward className="sidebar-menu-card-chevron" />
        </Link>

        {/* Card 8: Change Password */}
        <Link
          to={`/${usernamePath}/change-password`}
          className={`sidebar-menu-card ${isPasswordActive ? 'active' : ''}`}
        >
          <div className="sidebar-menu-card-left">
            <span className="sidebar-menu-card-icon">
              <FaLock style={{ color: isPasswordActive ? '#ffffff' : '#00529b' }} />
            </span>
            <div className="sidebar-menu-card-text">
              <span className="sidebar-menu-card-title">Change Password</span>
              <span className="sidebar-menu-card-sub">Manage your password and setting.</span>
            </div>
          </div>
          <IoIosArrowForward className="sidebar-menu-card-chevron" />
        </Link>

        {/* Card 9: Logout */}
        {user ? (
          <div
            className="sidebar-menu-card"
            onClick={handleLogout}
            role="button"
            tabIndex={0}
            onKeyDown={(e) => { if (e.key === 'Enter') handleLogout(e); }}
          >
            <div className="sidebar-menu-card-left">
              <span className="sidebar-menu-card-icon">
                <FaPowerOff style={{ color: '#dc2626' }} />
              </span>
              <div className="sidebar-menu-card-text">
                <span className="sidebar-menu-card-title text-danger">Logout</span>
              </div>
            </div>
            <IoIosArrowForward className="sidebar-menu-card-chevron" />
          </div>
        ) : (
          <Link
            to="/login?redirect=/orders"
            className="sidebar-menu-card"
          >
            <div className="sidebar-menu-card-left">
              <span className="sidebar-menu-card-icon">
                <FaUser style={{ color: '#00529b' }} />
              </span>
              <div className="sidebar-menu-card-text">
                <span className="sidebar-menu-card-title text-primary">Login / Sign In</span>
              </div>
            </div>
            <IoIosArrowForward className="sidebar-menu-card-chevron" />
          </Link>
        )}

      </div>

      {/* 3. Frequently Visited (Exact Wireframe Image 1: Track Orders. & Help Center.) */}
      <div className="sidebar-frequently-visited">
        <div className="sidebar-fv-title">Frequently Visited:</div>
        <div className="sidebar-fv-buttons">
          <Link
            to="/track-order"
            className={`sidebar-fv-btn ${isTrackOrderActive ? 'sidebar-fv-btn-primary' : 'sidebar-fv-btn-outline'}`}
          >
            Track Orders.
          </Link>
          <Link
            to="/help-center"
            className={`sidebar-fv-btn ${activeKey === 'help-center' || currentPath.includes('help-center') ? 'sidebar-fv-btn-primary' : 'sidebar-fv-btn-outline'}`}
          >
            Help Center.
          </Link>
        </div>
      </div>

    </div>
  );
};

export default AccountSidebar;
