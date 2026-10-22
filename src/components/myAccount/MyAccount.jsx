import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { IoIosArrowForward } from "react-icons/io";
import {
  FaBoxOpen,
  FaHeart,
  FaWallet,
  FaMapMarkerAlt,
  FaGift,
  FaShieldAlt,
  FaLock,
  FaStar,
  FaQuestionCircle,
  FaShoppingCart,
  FaStore,
  FaUserPlus,
  FaEnvelope,
  FaCoins
} from "react-icons/fa";
import { RiEditBoxLine } from "react-icons/ri";
import "./MyAccount.css";
import { useAuth } from "../../context/AuthContext";
import { API_ENDPOINTS } from "../../config/apiEndpoints";

const MyAccount = ({ onEditProfile }) => {
    const { user, logout, getUsernamePath } = useAuth();
    const usernamePath = getUsernamePath ? getUsernamePath() : "user";

    const [walletBalance, setWalletBalance] = useState(user?.wallet_balance !== undefined ? Number(user.wallet_balance) : 0);
    const [coinBalance, setCoinBalance] = useState(user?.coins !== undefined ? Number(user.coins) : 100);

    useEffect(() => {
        let isMounted = true;
        const fetchBalances = async () => {
            try {
                const token = localStorage.getItem("token") || sessionStorage.getItem("token");
                const headers = token ? { Authorization: `Bearer ${token}` } : {};
                const res = await fetch(API_ENDPOINTS.WALLET || "/api/wallet-api.php", { headers });
                const json = await res.json();
                if (isMounted && json && json.success && json.data) {
                    if (json.data.wallet?.balance !== undefined) {
                        setWalletBalance(Number(json.data.wallet.balance));
                    }
                    if (json.data.coins?.balance !== undefined) {
                        setCoinBalance(Number(json.data.coins.balance));
                    }
                }
            } catch (e) {
                // fallback
            }
        };
        if (user) {
            fetchBalances();
        }
        return () => { isMounted = false; };
    }, [user]);

    const handleLogout = (e) => {
        e.preventDefault();
        logout();
    };

    // Helper to get initials (e.g., "NK" or "AK")
    const getUserInitials = () => {
        if (!user) return "PM";
        const first = (user.firstName || user.first_name || user.name || "U").trim();
        const last = (user.lastName || user.last_name || "").trim();
        if (first && last) {
            return (first[0] + last[0]).toUpperCase();
        }
        return first.slice(0, 2).toUpperCase();
    };

    // Helper to get display name
    const getUserDisplayName = () => {
        if (!user) return "Buyer Name";
        if (user.firstName || user.first_name) {
            return `${user.firstName || user.first_name} ${user.lastName || user.last_name || ""}`.trim();
        }
        return user.name || (user.email ? user.email.split("@")[0] : "Printmont Customer");
    };

    const sections = [
        {
            title: "",
            links: [
                {
                    img: "/printmont-coin.png",
                    fallbackIcon: <FaCoins className="text-warning fs-5" />,
                    text: `Printmont SuperCoins (${coinBalance} Coins)`,
                    subtext: "Earn on every purchase, redeem reward vouchers.",
                    tag: `${coinBalance} Coins`,
                    to: user ? `/${usernamePath}/coins` : "/login?redirect=/user/coins"
                },
                {
                    icon: <FaBoxOpen className="theme-icon fs-5" />,
                    text: "My Orders",
                    subtext: "Order related Issues, Track Order & Download invoice.",
                    to: "/orders"
                },
                {
                    icon: <FaHeart className="fs-5 text-danger" />,
                    text: "Collection & Wishlist",
                    subtext: "All your curated product collections.",
                    to: user ? `/${usernamePath}/wishlist` : "/login?redirect=/user/wishlist"
                },
                {
                    icon: <FaWallet className="fs-5 text-success" />,
                    text: `My Wallet (₹${walletBalance.toFixed(2)})`,
                    subtext: "Add money, instant refunds & order cancellation reversals.",
                    tag: `₹${walletBalance.toFixed(2)} Cash`,
                    to: user ? `/${usernamePath}/wallet` : "/login?redirect=/user/wallet"
                },
                {
                    icon: <FaMapMarkerAlt className="theme-icon fs-5" />,
                    text: "Saved Addresses",
                    subtext: "Save address for a hassle-free checkout.",
                    to: user ? `/${usernamePath}/manage-address` : "/login?redirect=/user/manage-address"
                },
                {
                    icon: <FaGift className="fs-5 text-primary" />,
                    text: "Gift Cards & Coupons",
                    subtext: "Manage discount coupons & gift vouchers.",
                    to: user ? `/${usernamePath}/giftcard` : "/login?redirect=/user/giftcard"
                },
                {
                    icon: <FaShieldAlt className="theme-icon fs-5" />,
                    text: "Privacy Center",
                    subtext: "The security for your personal information is important.",
                    to: "/privacy-policy"
                },
                {
                    icon: <FaLock className="theme-icon fs-5" />,
                    text: "Change Password",
                    subtext: "Change your password.",
                    to: user ? "/user/change-password" : "/login?redirect=/user/change-password"
                },
            ],
        },
        {
            title: "My Activity",
            links: [
                {
                    icon: <FaStar className="fs-5 text-warning" />,
                    text: "Review & Ratings",
                    subtext: "Write reviews and feedback for purchased products.",
                    to: user ? `/${usernamePath}/reviews` : "/login?redirect=/user/reviews"
                },
                {
                    icon: <FaQuestionCircle className="theme-icon fs-5" />,
                    text: "Questions and Answers",
                    subtext: "Find answers or ask questions about services.",
                    to: "/faq"
                },
            ],
        },
        {
            title: "Enquiries",
            links: [
                {
                    icon: <FaShoppingCart className="theme-icon fs-5" />,
                    text: "Bulk Orders",
                    subtext: "Best discount to all product on bulk orders.",
                    to: "/bulk-orders"
                },
                {
                    icon: <FaStore className="theme-icon fs-5" />,
                    text: "Franchise",
                    subtext: "Own a PrintMont express outlet or kiosk in your city.",
                    to: "/franchise"
                },
                {
                    icon: <FaUserPlus className="theme-icon fs-5" />,
                    text: "Become a seller",
                    subtext: "Sell online to crores of customers at 0% Commission.",
                    to: "/become-a-seller"
                },
                {
                    icon: <FaEnvelope className="theme-icon fs-5" />,
                    text: "Contact Us",
                    subtext: "Contact Details and General Queries.",
                    to: "/contact"
                },
            ],
        },
    ];

    return (
        <div className="account-container">
            {/* 1. Buyer Profile Header Card (Avatar + Buyer Name + Edit Pen Icon) */}
            <div className="buyer-profile-card">
                <div className="buyer-avatar-circle">
                    {getUserInitials()}
                </div>
                <div className="buyer-name-info">
                    <div className="buyer-name-text">{getUserDisplayName()}</div>
                    <div className="buyer-subtext">{user?.email || user?.mobile || 'Printmont Member'}</div>
                </div>
                {onEditProfile ? (
                    <button 
                        type="button" 
                        className="buyer-edit-btn" 
                        onClick={onEditProfile} 
                        aria-label="Edit Profile"
                    >
                        <RiEditBoxLine size={24} />
                    </button>
                ) : (
                    <Link 
                        to={user ? `/${getUsernamePath ? getUsernamePath() : 'user'}/profile-edit` : '/login?redirect=/user/profile-edit'} 
                        className="buyer-edit-btn" 
                        aria-label="Edit Profile"
                    >
                        <RiEditBoxLine size={24} />
                    </Link>
                )}
            </div>

            {/* 2. Grouped Menu Sections */}
            {sections.map((section, sIdx) => (
                <div key={sIdx}>
                    {section.title && (
                        <div className="account-section-bar">
                            {section.title}
                        </div>
                    )}
                    <div className="account-menu-list">
                        {section.links.map((link, lIdx) => (
                            <Link
                                key={lIdx}
                                to={link.to}
                                className="account-menu-row"
                            >
                                <div className="account-menu-left">
                                    <div className="account-icon-badge">
                                        {link.img ? (
                                            <img
                                                src={link.img}
                                                alt="icon"
                                                className="account-img-icon"
                                                onError={(e) => {
                                                    e.target.style.display = 'none';
                                                }}
                                            />
                                        ) : (
                                            link.icon
                                        )}
                                    </div>
                                    <div className="account-menu-text">
                                        <div className="account-menu-title">{link.text}</div>
                                        <div className="account-menu-subtext">{link.subtext}</div>
                                    </div>
                                </div>
                                <div className="account-menu-right">
                                    {link.tag && (
                                        <span className="account-new-pill">{link.tag}</span>
                                    )}
                                    <IoIosArrowForward className="account-chevron" />
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            ))}

            {/* 3. Sticky Bottom Theme-Matched Logout Button */}
            <div className="account-logout-wrapper">
                <button
                    type="button"
                    onClick={handleLogout}
                    className="account-logout-btn"
                >
                    LOGOUT
                </button>
            </div>
        </div>
    );
};

export default MyAccount;
