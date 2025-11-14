import React from "react";
import {
    FaTruck,
    FaShieldAlt,
    FaCertificate,
    FaFacebookF,
    FaInstagram,
    FaLinkedinIn,
    FaYoutube,
} from "react-icons/fa";
import { PiShieldCheckFill } from "react-icons/pi";
import { FaXTwitter } from "react-icons/fa6";
import { Link } from "react-router";

const MobileFooter = () => {
    return (
        <div className="mobile-footer text-center p-3 bg-light">
            {/* Top Icons with Text */}
            <div className="d-flex justify-content-around text-center mb-3">
                <div>
                    <div><img src={'/free.png'} alt="Free Shipping" className="me-2 feature-icon" /></div>
                    <p className="txsm mb-0 fw-semibold">Free Shipping.</p>
                    <p className="text-muted txex">No one rejects Dislike.</p>
                </div>
                <div>
                    <div><img src={'/secure.png'} alt="Free Shipping" className="me-2 feature-icon" /></div>
                    <p className="txsm mb-0 fw-semibold">Online Payment.</p>
                    <p className="text-muted txex">100% secure payment.</p>
                </div>
                <div>
                    <div><img src={'/original.png'} alt="Free Shipping" className="me-2 feature-icon" /></div>
                    <p className="txsm mb-0 fw-semibold">100% Original</p>
                    <p className="text-muted txex">Guaranteed products</p>
                </div>
            </div>

            {/* Rounded Buttons */}
            <div className="d-flex justify-content-center gap-2 mb-3 flex-wrap">
                <Link to={'/contact'} className=" px-3 py-2 btn-small-text fw-bold text-decoration-none">
                    CONTACT US
                </Link>
                <Link to={'/quick-links'} className=" px-3 py-2 btn-small-text fw-bold text-decoration-none">
                    QUICK LINKS
                </Link>
                <Link to={'/help-center'} className=" px-3 py-2 btn-small-text fw-bold text-decoration-none">
                    HELP NOW
                </Link>

            </div>

            {/* Logo */}
            <img src="/PrintLogo.png" alt="Xordox Logo" className="my-3" style={{ height: 40 }} />

            {/* Secure Text */}
            <div className="mb-2">
                <PiShieldCheckFill size={28} className="text-primary" />
                <p className="mb-0 fw-semibold mt-1">100% Safe and secure payments.</p>
            </div>

            {/* Social Media */}
            <div className="d-flex justify-content-center gap-3 my-3">
                <a href="https://facebook.com" target="_blank" className="social-icon facebook"><FaFacebookF className="fs-5"/></a>
                <a href="#" target="_blank" className="social-icon twitter"><FaXTwitter /></a>
                <a href="#" target="_blank" className="social-icon instagram"><FaInstagram className="fs-3"/></a>
                <a href="#" target="_blank" className="social-icon linkedin"><FaLinkedinIn /></a>
                <a href="#" target="_blank" className="social-icon youtube"><FaYoutube className="fs-5"/></a>

            </div>

            {/* Copyright */}
            <p className="text-muted small mb-0">
                © Copyright 2019 - 2024 Xordox.com All Rights Reserved.
            </p>
        </div>
    );
};

export default MobileFooter;
