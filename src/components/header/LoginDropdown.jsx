import React from "react";
import { FaSignOutAlt, FaRegUser } from "react-icons/fa";
import { Link,} from "react-router-dom";
import { LiaShoppingBagSolid, LiaTruckMovingSolid } from "react-icons/lia";
import { CiWallet } from "react-icons/ci";


const LoginDropdown = () => {
  return (
    <div className="dropdown-container position-relative d-inline-block">
      {/* Trigger */}
      <div className=" px-3 py-1 rounded d-flex align-items-center gap-2">
        <FaRegUser color="#007bff"size={20} />
        <Link to="/login" className="text-muted fw-semibold text-decoration-none">Login</Link>
      </div>

      {/* Dropdown */}
      <div className="dropdown-menu-box position-absolute bg-white shadow rounded mt-0 end-0">
        <div className="px-3 py-2 border-bottom d-flex justify-content-between">
          <span className="fw-bold">New Customer?</span>
          <a href="/signup" className="text-primary text-decoration-none fw-semibold">Signup</a>
        </div>

        <ul className="list-unstyled mb-0">
          <li className="dropdown-item-custom"><img src="/printmont-coin.png" className="me-1 rounded-circle shadow-md" alt="" width={18} height={18}/>
          <Link to={'#'} className="text-decoration-none text-black">Printmont Coins</Link>
          </li>
          <li className="dropdown-item-custom"><FaRegUser className="me-2" /> <Link to={'/user/profile'} className="text-decoration-none text-black">My Profile</Link></li>
          <li className="dropdown-item-custom text-black"><LiaTruckMovingSolid  className="me-2" /> <Link to={'#'} className="text-decoration-none text-black">Track Your Orders</Link></li>
          <li className="dropdown-item-custom text-black"><CiWallet className="me-2" /> <Link to={'#'} className="text-decoration-none text-black">Printmont Wallet</Link></li>
          <li className="dropdown-item-custom text-black"><LiaShoppingBagSolid className="me-2 text-black" /> <Link to={'/orders'} className="text-decoration-none text-black">My Orders</Link></li>
          <li className="dropdown-item-custom"><FaSignOutAlt className="me-2 text-dark" /> <Link to={'#'} className="text-decoration-none text-black">Log Out</Link></li>
        </ul>
      </div>
    </div>
  );
};

export default LoginDropdown;
