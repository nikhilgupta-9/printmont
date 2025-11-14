import React, { useEffect } from "react";
import { Container } from "react-bootstrap";
import { Link, useNavigate } from "react-router-dom";
import { IoIosArrowForward } from "react-icons/io";
import "./MyAccount.css";
import { FiEdit } from "react-icons/fi";

const MyAccount = () => {
    const navigate = useNavigate();

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
                    img: "/icons/booking.png",
                    text: "My Orders",
                    subtext: "Order related issues, Track Order & Download invoice.",
                    arrow: true,
                },
                {
                    img: "/icons/wishlist.png",
                    text: "Collection & Wishlist",
                    subtext: "All your curated product collections.",
                    arrow: true,
                },
                {
                    img: "/icons/wallet.png", // 🖼️ Example image path
                    text: "My Wallets",
                    subtext: "Manage all your refund & gift cards.",
                    arrow: true,
                },
                {
                    img: "/icons/location-pin.png",
                    text: "Saved Addresses",
                    subtext: "Save address for a hassle-free checkout.",
                    arrow: true,
                },
                {
                    img: "/icons/sale.png",
                    text: "Coupons",
                    subtext: "Manage coupons for additional discounts.",
                    arrow: true,
                },
                {
                    img: "/icons/privacy-policy.png",
                    text: "Privacy Center",
                    subtext: "The security for your personal information is important.",
                    arrow: true,
                },
                {
                    img: "/icons/reset-password.png",
                    text: "Change Password",
                    subtext: "Change your password.",
                    arrow: true,
                },
            ],
        },
        {
            title: "My Activity",
            links: [
                {
                    img: "/icons/rating.png",
                    text: "Review",
                    subtext: "Low investment, high return I promise.",
                    arrow: true,
                },
                {
                    img: "/icons/qa.png",
                    text: "Questions and Answers",
                    subtext: "Sell online to crores of customers at 0% Commission.",
                    arrow: true,
                },
            ],
        },
        {
            title: "Enquiries",
            links: [
                {
                    img: "/icons/bulk-buying.png",
                    text: "Bulk Orders",
                    subtext: "Best discount to all product on bulk orders.",
                    arrow: true,
                },
                {
                    img: "/icons/franchise.png",
                    text: "Franchise",
                    subtext: "Low investment, high return I promise.",
                    arrow: true,
                },
                {
                    icon: "Icon",
                    text: "Become a seller",
                    subtext: "Sell online to crores of customers at 0% Commission.",
                    arrow: true,
                },
                {
                    icon: "Icon",
                    text: "Contact Us",
                    subtext: "Contact Details and General Queries.",
                    arrow: true,
                },
            ],
        },
    ];

    return (
        <Container fluid className="account-container p-0">

            {/* 👤 Profile */}
            <div className="profile-section d-flex align-items-center justify-content-between p-3 border-bottom">
                <div className="d-flex align-items-center gap-3">
                    <div className="profile-icon rounded-circle d-flex align-items-center justify-content-center">
                        <span className="text-white fw-bold">AK</span>
                    </div>
                    <div className="fw-semibold text-dark small">Buyer name</div>
                    
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
                                        <span className="text-secondary small">{link.icon}</span>
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
                <button className="logout-btn w-100 py-2 fw-semibold text-white border-0">
                    LOGOUT
                </button>
            </div>
        </Container>
    );
};

export default MyAccount;
