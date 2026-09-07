import React from "react";
import { FaSignOutAlt, FaRegUser,  FaGift } from "react-icons/fa";
import { Link,} from "react-router-dom";
import { LiaShoppingBagSolid, LiaTruckMovingSolid } from "react-icons/lia";
import { CiWallet } from "react-icons/ci";
import { CgProfile } from "react-icons/cg";
import { GiCardboardBoxClosed, GiWallet } from "react-icons/gi";
import { RiLogoutCircleRLine, RiTruckLine } from "react-icons/ri";



import { useAuth } from "../../context/AuthContext";

const LoginDropdown = () => {
  const { user, logout, getUsernamePath } = useAuth();
  const usernamePath = getUsernamePath();
  
  return (
    <div className="dropdown-container position-relative d-inline-block">
      {/* Trigger */}
      <div className=" px-3 py-1 rounded d-flex align-items-center gap-2">
        <FaRegUser color="#007bff" size={20} />
        {user ? (
          <Link to={`/${usernamePath}/profile`} className="text-muted fw-semibold text-decoration-none text-truncate" style={{ maxWidth: '100px' }}>
            {user.firstName || user.first_name || user.name || (user.email ? user.email.split('@')[0] : 'User')}
          </Link>
        ) : (
          <Link to="/login" className="text-muted fw-semibold text-decoration-none">Login</Link>
        )}
      </div>

      {/* Dropdown */}
      <div className="dropdown-menu-box position-absolute bg-white shadow rounded mt-0 end-0">
        {!user && (
          <div className="px-3 py-2 border-bottom d-flex justify-content-between">
            <span className="fw-bold">New Customer?</span>
            <Link to="/login" className="text-primary text-decoration-none fw-semibold">Signup</Link>
          </div>
        )}

        <ul className="list-unstyled mb-0">
          <li className="dropdown-item-custom">
            <CgProfile size={19} className="me-2 text-theme" /> 
            <Link to={user ? `/${usernamePath}/profile` : "/login"} className="text-decoration-none text-black">My Profile</Link>
          </li>
          <li className="dropdown-item-custom">
            <img src="/printmont-coin.png" className="me-2 rounded-circle shadow-md" alt="" width={20} height={20}/>
            <Link to={user ? '/printmont-coin' : "/login"} className="text-decoration-none text-black">Printmont Coins</Link>
          </li>
          <li className="dropdown-item-custom text-black">
            <GiCardboardBoxClosed className="me-2 text-theme" size={21} /> 
            <Link to={user ? '/orders' : "/login"} className="text-decoration-none text-black">Orders</Link>
          </li>
          <li className="dropdown-item-custom text-black">
            <RiTruckLine className="me-2 text-theme" size={20} /> 
            <Link to="/track-order" className="text-decoration-none text-black">Track your Orders</Link>
          </li>
          <li className="dropdown-item-custom text-black">
            <GiWallet className="me-2 text-theme" size={18} /> 
            <Link to={user ? '#' : "/login"} className="text-decoration-none text-black">My Wallet</Link>
          </li>
          <li className="dropdown-item-custom text-black">
            <FaGift size={17} className="me-2 text-theme" /> 
            <Link to="/orders" className="text-decoration-none text-black">Coupons</Link>
          </li>
          {user && (
            <li className="dropdown-item-custom">
              <RiLogoutCircleRLine size={20} className="me-2 text-theme" /> 
              <button onClick={logout} className="btn btn-link text-decoration-none text-black p-0 border-0 bg-transparent text-start">Log Out</button>
            </li>
          )}
        </ul>
      </div>
    </div>
  );
};

export default LoginDropdown;
