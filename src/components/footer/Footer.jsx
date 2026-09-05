import React from "react";
import "bootstrap/dist/css/bootstrap.min.css";
import {
  FaYoutube,
  FaFacebookF,
  FaInstagram,
  FaLinkedinIn,
} from "react-icons/fa";
import { FaXTwitter } from "react-icons/fa6";
import MobileFooter from "./MobileFooter";
import IconX from "./IconX";
import { Link } from "react-router";

const Footer = () => {
  return (
    <>
      <div className="d-none d-xl-block"><IconX /></div>
      <footer className="foot-bg text-light pt-5 pb-2 small-screen-foot">
        <div className="container-fluid px-5">
          <div className="row g-4">

            <div className="col-12 col-lg-6">
              <div className="row g-4">

                <div className="col-6 col-md-3 ">
                  <h6 className="text-uppercase small text-white-50">Our Company</h6>
                  <ul className="list-unstyled txsm d-flex flex-column gap-1">
                    <li><Link to="/contact" className="text-light text-decoration-none">Contact Us</Link></li>
                    <li><Link to="/about" className="text-light text-decoration-none">About Us</Link></li>
                    <li><Link to="/careers" className="text-light text-decoration-none">Careers</Link></li>
                    <li><Link to="/blog" className="text-light text-decoration-none">Blog</Link></li>
                    <li><Link to="/affiliate-program" className="text-light text-decoration-none">Affiliate Program</Link></li>
                  </ul>
                </div>


                <div className="col-6 col-md-3">
                  <h6 className="text-uppercase small text-white-50">Policy Info</h6>
                  <ul className="list-unstyled txsm d-flex flex-column gap-1">
                    <li><Link to="/terms-of-use" className="text-light text-decoration-none">Terms of Use</Link></li>
                    <li><Link to="/policy/terms" className="text-light text-decoration-none">Terms & Conditions</Link></li>
                    <li><Link to="/policy/privacy" className="text-light text-decoration-none">Privacy Policy</Link></li>
                    <li><Link to="/policy/shipping" className="text-light text-decoration-none">Shipping Policy</Link></li>
                    <li><Link to="/policy/refund" className="text-light text-decoration-none">Return & Refund Policy</Link></li>
                  </ul>

                </div>


                <div className="col-6 col-md-3">
                  <h6 className="text-uppercase small text-white-50">Quick Links</h6>
                  <ul className="list-unstyled txsm d-flex flex-column gap-1">
                    <li><Link to="/help-center" className="text-light text-decoration-none">Help Center</Link></li>
                    <li><Link to="/security" className="text-light text-decoration-none">Security</Link></li>
                    <li><Link to="/sitemap" className="text-light text-decoration-none">Sitemap</Link></li>
                    <li><Link to="/faq" className="text-light text-decoration-none">FAQ</Link></li>
                    <li><Link to="/product" className="text-light text-decoration-none text-warning fw-semibold">Product Details (Temp)</Link></li>
                    <li><Link to="/business-solutions" className="text-light text-decoration-none">Business Solutions</Link></li>
                  </ul>

                </div>



                <div className="col-6 col-md-3">
                  <h6 className="text-uppercase small text-white-50">Support</h6>
                  <ul className="list-unstyled txsm d-flex flex-column gap-1">
                    <li><Link to="/user/profile" className="text-light text-decoration-none">Account Settings</Link></li>
                    <li><Link to="/orders" className="text-light text-decoration-none">My Orders</Link></li>
                    <li><Link to="/wallet" className="text-light text-decoration-none">My Wallet</Link></li>
                    <li><Link to="/track-order" className="text-light text-decoration-none">Track Orders</Link></li>
                  </ul>

                </div>
              </div>
            </div>

            <div className="col-12 col-lg-6 wbd border-start border-light ps-4 ">
              <div className="row g-4">

                <div className="col-12 col-md-6">
                  <h6 className="medium text-white-50">Registered Address:</h6>
                  <address className="txsm">
                    Xordox International Pvt. Ltd.,<br />
                    3398, Bagichi Acchi ji Bara Hindu Rao,<br />
                    Near Filmistan Cinema, New Delhi 110006,<br />
                    New Delhi, India<br />
                    Customer Care:{" "}
                    <a href="tel:+919818532463" className="text-white">
                      +91-9818532463
                    </a>
                  </address>

                  <div className="mt-2 d-flex flex-column justify-content-start align-items-start">
                    <span className="small text-white-50">Follow Us:</span>
                    <div className="d-flex justify-content-center gap-2 my-2">
                      <a href="#" className="social-icon facebook"><FaFacebookF className="fs-5" /></a>
                      <a href="#" className="social-icon twitter"><FaXTwitter /></a>
                      <a href="#" className="social-icon instagram"><FaInstagram className="fs-3" /></a>
                      <a href="#" className="social-icon linkedin"><FaLinkedinIn /></a>
                      <a href="#" className="social-icon youtube"><FaYoutube className="fs-5" /></a>

                    </div>
                  </div>
                </div>

                <div className="col-12 col-md-6">
                  <h6 className="small">Subscribe</h6>
                  <form className="d-flex">
                    <input
                      id="con-email"
                      type="email"
                      placeholder="Email Address"
                      className="form-control rounded-0"
                    />
                    <button className="btn btn-secondary rounded-0">Submit</button>
                  </form>
                  <small className="text-gray d-block mt-2">
                    Get updates on new products and offers Coupons
                  </small>
                </div>
              </div>
            </div>
          </div>

          <hr className="border-light border-1 my-4" />

          {/*
            Links, copyright and payment strip on one row from 1200px up.
            A 6/3/3 grid could not do it: the links alone need ~590px and
            half the row is 580px after column padding, so the last link
            kept dropping out of line. Flexbox gives each block the width
            it actually needs and lets the copyright take up the slack.
          */}
          <div className="footer-bottom pb-3">
            <div className="footer-bottom__links small">
              <span className="footer-bottom__link">
                <img src="/become-seller.png" alt="" width="20" height="20" />
                <Link to="/become-a-seller" className="link-text-white">Become a Seller</Link>
              </span>
              <span className="footer-bottom__link">
                <img src="/advertising.png" alt="" width="20" height="20" />
                <Link to="/business-solutions" className="link-text-white">Business Solutions</Link>
              </span>
              <span className="footer-bottom__link">
                <img src="/printmont-coin.png" alt="" width="20" height="20" />
                <Link to="/printmont-coin" className="link-text-white">Printmont Coins</Link>
              </span>
              <span className="footer-bottom__link">
                <img src="/Help-center.png" alt="" width="20" height="20" />
                <Link to="/help-center" className="link-text-white">Help Center</Link>
              </span>
            </div>
            <div className="footer-bottom__copy small">
              © 2019-2024 printmont.com All Rights Reserved.
            </div>
            <div className="footer-bottom__pay">
              <img src="./payment-method.svg" alt="Payment Methods" className="img-fluid" />
            </div>
          </div>
        </div>
      </footer>
      <div className="big-screen-foot">
        <MobileFooter />
      </div>
    </>
  );
};

export default Footer;
