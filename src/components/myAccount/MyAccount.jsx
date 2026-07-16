import React, { useEffect } from "react";
import { Container } from "react-bootstrap";
import { Link, useNavigate } from "react-router-dom";
import { IoIosArrowForward } from "react-icons/io";
import "./MyAccount.css";
import { FiEdit } from "react-icons/fi";
import { useAuth } from "../../context/AuthContext";
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
  FaEnvelope
} from "react-icons/fa";

const MyAccount = () => {
    const navigate = useNavigate();
    const { user, logout } = useAuth();

    const handleLogout = (e) => {
        e.preventDefault();
        logout();
        navigate("/login");
    };

    // ✅ Redirect if screen width > 768px (desktop)
    useEffect(() => {
        const handleResize = () => {
            if (window.innerWidth > 768) {
                navigate("/"); // redirect to homepage
            }
        };

        handleResize();
        window.addEventListener("resize", handleResize);
        return () => window.removeEventListener("resize", handleResize);
    }, [navigate]);

    const sections = [
        {
            title: "",
            links: [
                {
                    img: "/printmont-coin.png",
                    text: "Cash Coins ₹20",
                    subtext: "Quick Checkout, Easy Adjust Coins.",
                    tag: "New",
                    arrow: true,
                },
                {
                    icon: <FaBoxOpen className="text-primary fs-5" />,
                    text: "My Orders",
                    subtext: "Order related issues, Track Order & Download invoice.",
                    arrow: true,
                    to: "/orders"
                },
                {
                    icon: <FaHeart className="text-primary fs-5" />,
                    text: "Collection & Wishlist",
                    subtext: "All your curated product collections.",
                    arrow: true,
                    to: "/user/wishlist"
                },
                {
                    icon: <FaWallet className="text-primary fs-5" />,
                    text: "My Wallets",
                    subtext: "Manage all your refund & gift cards.",
                    arrow: true,
                    to: "/user/giftcard"
                },
                {
                    icon: <FaMapMarkerAlt className="text-primary fs-5" />,
                    text: "Saved Addresses",
                    subtext: "Save address for a hassle-free checkout.",
                    arrow: true,
                    to: "/user/manage-address"
                },
                {
                    icon: <FaGift className="text-primary fs-5" />,
                    text: "Coupons",
                    subtext: "Manage coupons for additional discounts.",
                    arrow: true,
                    to: "/user/giftcard"
                },
                {
                    icon: <FaShieldAlt className="text-primary fs-5" />,
                    text: "Privacy Center",
                    subtext: "The security for your personal information is important.",
                    arrow: true,
                    to: "/policy/privacy"
                },
                {
                    icon: <FaLock className="text-primary fs-5" />,
                    text: "Change Password",
                    subtext: "Change your password.",
                    arrow: true,
                    to: "/user/profile"
                },
            ],
        },
        {
            title: "My Activity",
            links: [
                {
                    icon: <FaStar className="text-primary fs-5" />,
                    text: "Review",
                    subtext: "Low investment, high return I promise.",
                    arrow: true,
                    to: "/user/profile"
                },
                {
                    icon: <FaQuestionCircle className="text-primary fs-5" />,
                    text: "Questions and Answers",
                    subtext: "Sell online to crores of customers at 0% Commission.",
                    arrow: true,
                    to: "/user/profile"
                },
            ],
        },
        {
            title: "Enquiries",
            links: [
                {
                    icon: <FaShoppingCart className="text-primary fs-5" />,
                    text: "Bulk Orders",
                    subtext: "Best discount to all product on bulk orders.",
                    arrow: true,
                    to: "/business-solutions"
                },
                {
                    icon: <FaStore className="text-primary fs-5" />,
                    text: "Franchise",
                    subtext: "Low investment, high return I promise.",
                    arrow: true,
                    to: "/become-a-seller"
                },
                {
                    icon: <FaUserPlus className="text-primary fs-5" />,
                    text: "Become a seller",
                    subtext: "Sell online to crores of customers at 0% Commission.",
                    arrow: true,
                    to: "/become-a-seller"
                },
                {
                    icon: <FaEnvelope className="text-primary fs-5" />,
                    text: "Contact Us",
                    subtext: "Contact Details and General Queries.",
                    arrow: true,
                    to: "/contact"
                },
            ],
        },
    ];

    // Helper to get initials
    const getUserInitials = () => {
        if (!user) return "G";
        const first = user.firstName || user.first_name || user.name || "U";
        const last = user.lastName || user.last_name || "";
        return (first[0] + (last ? last[0] : "")).toUpperCase();
    };

    // Helper to get display name
    const getUserDisplayName = () => {
        if (!user) return "Guest";
        if (user.firstName || user.first_name) {
            return `${user.firstName || user.first_name} ${user.lastName || user.last_name || ""}`.trim();
        }
        return user.name || (user.email ? user.email.split("@")[0] : "User");
    };

    return (
        <Container fluid className="account-container p-0">

            {/* 👤 Profile */}
            <div className="profile-section d-flex align-items-center justify-content-between p-3 border-bottom">
                <div className="d-flex align-items-center gap-3">
                    <div className="profile-icon rounded-circle d-flex align-items-center justify-content-center">
                        <span className="text-white fw-bold">{getUserInitials()}</span>
                    </div>
                    <div className="fw-semibold text-dark small">{getUserDisplayName()}</div>
                </div>
                <Link to={'/user/profile'}><FiEdit className="text-secondary fs-5" /></Link>
            </div>

            {/* 🧾 Menu Sections */}
            {sections.map((section, i) => (
                <div key={i}>
                    {section.title && (
                        <div className="section-title px-3 py-2 text-uppercase small fw-bold border-bottom">
                            {section.title}
                        </div>
                    )}
                    {section.links.map((link, idx) => (
                        <Link
                            key={idx}
                            to={link.to || "#"}
                            className="link-item d-flex align-items-center justify-content-between px-3 py-2 border-bottom text-decoration-none"
                        >
                            <div className="d-flex align-items-center gap-3">
                                <div className="link-icon rounded-circle d-flex align-items-center justify-content-center">
                                    {link.img ? (
                                        <img
                                            src={link.img}
                                            alt="icon"
                                            className="account-icon-img"
                                            style={{ width: 25, height: 25, objectFit: "contain" }}
                                        />
                                    ) : (
                                        link.icon
                                    )}
                                </div>
                                <div>
                                    <div className="fw-normal small text-dark d-flex align-items-center gap-1">
                                        {link.text}
                                    </div>
                                    {link.subtext && (
                                        <div className="text-muted xsmall">{link.subtext}</div>
                                    )}
                                </div>
                            </div>
                            <div>
                                {link.tag && (
                                    <span className=" new-text ms-1">
                                        {link.tag}
                                    </span>
                                )}
                                {link.arrow && <IoIosArrowForward className="text-secondary" />}
                            </div>
                        </Link>
                    ))}
                </div>
            ))}

            {/* 🚪 Logout */}
            <div className="logout-container">
                <button 
                    onClick={handleLogout}
                    className="logout-btn w-100 py-2 fw-semibold text-white border-0"
                >
                    LOGOUT
                </button>
            </div>
        </Container>
    );
};

export default MyAccount;
