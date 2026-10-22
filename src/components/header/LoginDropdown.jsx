import React from "react";
import { FaSignOutAlt, FaGift } from "react-icons/fa";
import { FaRegCircleUser } from "react-icons/fa6";
import { BsChevronDown } from "react-icons/bs";
import { Link } from "react-router-dom";
import { LiaShoppingBagSolid, LiaTruckMovingSolid } from "react-icons/lia";
import { CiWallet } from "react-icons/ci";
import { CgProfile } from "react-icons/cg";
import { GiCardboardBoxClosed, GiWallet } from "react-icons/gi";
import { RiLogoutCircleRLine, RiTruckLine } from "react-icons/ri";

import { useAuth } from "../../context/AuthContext";
import useAccountNavigation from '../../hooks/useAccountNavigation';

const LoginDropdown = ({ customLabel }) => {
  const { user, logout } = useAuth();
  const { accountPath, goToAccount } = useAccountNavigation();

  const labelText = customLabel || (user 
    ? (user.first_name || user.firstName || user.name || (user.email ? user.email.split('@')[0] : 'User'))
    : 'Login');
  
  return (
    <div className="dropdown-container position-relative d-inline-block">
      {/* Trigger */}
      <div
        className="px-2.5 py-1.5 rounded-pill d-flex align-items-center gap-1.5 cursor-pointer pm-user-trigger"
        style={{ 
          cursor: 'pointer',
          transition: 'all 0.2s cubic-bezier(0.16, 1, 0.3, 1)'
        }}
        role="button"
        tabIndex={0}
        onClick={goToAccount}
        onKeyDown={(e) => { if (e.key === 'Enter') goToAccount(e); }}
        title={user ? 'My Account' : 'Login'}
      >
        <FaRegCircleUser color="#00529b" size={20} />
        <span className="text-dark fw-semibold text-truncate mx-1" style={{ fontSize: '14px', maxWidth: '120px', letterSpacing: '-0.1px' }}>
          {labelText}
        </span>
        <BsChevronDown size={11} className="text-secondary ms-0.5" />
      </div>

      {/* Dropdown */}
      <div 
        className="dropdown-menu-box position-absolute bg-white rounded-3 mt-1 end-0 shadow-lg"
        style={{
          minWidth: '230px',
          border: '1px solid #e2e8f0',
          boxShadow: '0 14px 34px -4px rgba(0, 0, 0, 0.14)',
          zIndex: 1060,
          overflow: 'hidden'
        }}
      >
        {!user ? (
          <div className="px-3 py-2.5 border-bottom d-flex justify-content-between align-items-center bg-light">
            <span className="fw-semibold text-secondary" style={{ fontSize: '12.5px' }}>New to Printmont?</span>
            <Link to="/login" className="btn btn-sm btn-primary py-0 px-2.5 fw-semibold" style={{ fontSize: '12px', borderRadius: '4px', backgroundColor: '#00529b', borderColor: '#00529b' }}>Sign In</Link>
          </div>
        ) : (
          <div className="px-3 py-2 border-bottom bg-light">
            <span className="fw-bold text-dark d-block text-truncate" style={{ fontSize: '13.5px' }}>
              Hi, {user.first_name || user.firstName || (user.name || '').trim().split(/\s+/)[0] || 'Member'}
            </span>
            <span className="badge bg-success-subtle text-success fw-medium" style={{ fontSize: '10.5px' }}>Verified Account</span>
          </div>
        )}

        <ul className="list-unstyled mb-0 py-1">
          <li className="dropdown-item-custom">
            <CgProfile size={18} className="me-2 text-primary" style={{ color: '#00529b' }} /> 
            <Link to={accountPath} onClick={goToAccount} className="text-decoration-none text-dark fw-medium" style={{ fontSize: '13px' }}>My Profile</Link>
          </li>
          <li className="dropdown-item-custom">
            <img src="/printmont-coin.png" className="me-2 rounded-circle" alt="" width={18} height={18}/>
            <Link to={user ? '/printmont-coin' : "/login"} className="text-decoration-none text-dark fw-medium" style={{ fontSize: '13px' }}>Printmont Coins</Link>
          </li>
          <li className="dropdown-item-custom">
            <GiCardboardBoxClosed className="me-2 text-primary" size={19} style={{ color: '#00529b' }} /> 
            <Link to={user ? '/orders' : "/login"} className="text-decoration-none text-dark fw-medium" style={{ fontSize: '13px' }}>Orders &amp; History</Link>
          </li>
          <li className="dropdown-item-custom">
            <RiTruckLine className="me-2 text-primary" size={18} style={{ color: '#00529b' }} /> 
            <Link to="/track-order" className="text-decoration-none text-dark fw-medium" style={{ fontSize: '13px' }}>Track Order</Link>
          </li>
          <li className="dropdown-item-custom">
            <GiWallet className="me-2 text-primary" size={18} style={{ color: '#00529b' }} /> 
            <Link to={user ? '/orders' : "/login"} className="text-decoration-none text-dark fw-medium" style={{ fontSize: '13px' }}>My Wallet</Link>
          </li>
          <li className="dropdown-item-custom">
            <FaGift size={16} className="me-2 text-primary" style={{ color: '#00529b' }} /> 
            <Link to="/orders" className="text-decoration-none text-dark fw-medium" style={{ fontSize: '13px' }}>Coupons &amp; Offers</Link>
          </li>
          {user && (
            <li className="dropdown-item-custom border-top mt-1 pt-1">
              <RiLogoutCircleRLine size={18} className="me-2 text-danger" /> 
              <button onClick={logout} className="btn btn-link text-decoration-none text-danger fw-semibold p-0 border-0 bg-transparent text-start" style={{ fontSize: '13px' }}>Log Out</button>
            </li>
          )}
        </ul>
      </div>
    </div>
  );
};

export default LoginDropdown;
