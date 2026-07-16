import React, { useEffect } from "react";
import { useNavigate, Link } from "react-router-dom";
import { Container } from "react-bootstrap";
import { IoIosArrowForward } from "react-icons/io";
import { 
  MdOutlineManageAccounts, 
  MdHelpOutline, 
  MdOutlineBusinessCenter, 
  MdSupportAgent,
  MdStorefront, 
  MdMonetizationOn, 
  MdInfoOutline, 
  MdOutlineArticle, 
  MdWorkOutline, 
  MdPeopleOutline, 
  MdOutlineSecurity, 
  MdOutlinePhone, 
  MdOutlineDescription, 
  MdOutlinePrivacyTip, 
  MdOutlineLocalShipping, 
  MdOutlineAssignmentReturn, 
  MdOutlineAssignment 
} from "react-icons/md";
import { FaBoxes, FaSitemap } from "react-icons/fa";
import "./quick.css";

const QuickLinks = () => {
  const navigate = useNavigate();

  // 👉 Redirect if screen width > 768px (desktop)
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth > 768) {
        navigate("/"); // redirect to homepage
      }
    };

    // run once on mount
    handleResize();

    // also run on resize
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, [navigate]);

  const sections = [
    {
      title: "",
      links: [
        { icon: <MdOutlineManageAccounts size={20} className="text-secondary" />, text: "Account Related Queries", subtext: "Answer to all account related queries.", to: "/my-account", arrow: true },
        { icon: <MdHelpOutline size={20} className="text-secondary" />, text: "All Help Topics", subtext: "Answer to frequently asked questions.", to: "/help-center", arrow: true },
      ],
    },
    {
      title: "SUPPORT :",
      links: [
        { icon: <MdOutlineBusinessCenter size={20} className="text-secondary" />, text: "Business Solutions", subtext: "Answer to all your business related solutions.", to: "/business-solutions", arrow: true },
        { icon: <FaBoxes size={18} className="text-secondary" />, text: "Bulk Orders", subtext: "Best discount to all product on bulk orders.", to: "/support", arrow: true },
        { icon: <MdSupportAgent size={20} className="text-secondary" />, text: "Help Center", subtext: "How can we help?.", to: "/help-center", arrow: true },
      ],
    },
    {
      title: "Need More Help?",
      links: [
        { icon: <MdStorefront size={20} className="text-secondary" />, text: "Franchise", subtext: "Low investment high return I promise.", to: "/contact", arrow: true },
        { icon: <MdMonetizationOn size={20} className="text-secondary" />, text: "Become a seller", subtext: "Sell online to crores of customers at 0% Commission.", to: "/become-a-seller", arrow: true },
      ],
    },
    {
      title: "OUR COMPANY :",
      links: [
        { icon: <MdInfoOutline size={20} className="text-secondary" />, text: "About Us", subtext: "Since Printmont inception in 2019.", to: "/about", arrow: true },
        { icon: <MdOutlineArticle size={20} className="text-secondary" />, text: "Blog", subtext: "Celebrate Relations.", to: "/blog", arrow: true },
        { icon: <MdWorkOutline size={20} className="text-secondary" />, text: "Careers", subtext: "Where Passion Meets Career.", to: "/careers", arrow: true },
        { icon: <MdPeopleOutline size={20} className="text-secondary" />, text: "Affiliate Program", subtext: "3 Simple Steps to Partner with us!.", to: "/affiliate-program", arrow: true },
        { icon: <MdOutlineSecurity size={20} className="text-secondary" />, text: "Security", subtext: "Safe and Secure Shopping.", to: "/security", arrow: true },
        { icon: <FaSitemap size={18} className="text-secondary" />, text: "Sitemap", subtext: "Trending Product Pages.", to: "#", arrow: true },
        { icon: <MdOutlinePhone size={20} className="text-secondary" />, text: "Contact Us", subtext: "Contact Details and General Queries.", to: "/contact", arrow: true },
      ],
    },
    {
      title: "POLICY INFO :",
      links: [
        { icon: <MdOutlineDescription size={20} className="text-secondary" />, text: "Terms & Conditions", subtext: "The following Terms and Conditions.", to: "/policy/terms", arrow: true },
        { icon: <MdOutlinePrivacyTip size={20} className="text-secondary" />, text: "Privacy Policy", subtext: "The following Privacy Policy.", to: "/policy/privacy", arrow: true },
        { icon: <MdOutlineLocalShipping size={20} className="text-secondary" />, text: "Shipping Policy", subtext: "The following Shipping Policy.", to: "/policy/shipping", arrow: true },
        { icon: <MdOutlineAssignmentReturn size={20} className="text-secondary" />, text: "Return & Refund Policy", subtext: "The following Return and Refund Policy.", to: "/policy/refund", arrow: true },
        { icon: <MdOutlineAssignment size={20} className="text-secondary" />, text: "Terms of use", subtext: "The following Terms of use.", to: "/terms-of-use", arrow: true },
      ],
    },
  ];

  return (
    <Container fluid className="quicklinks-container p-0">
      <div className="bg-white">
        <Link 
          to="/track-order" 
          className="d-flex align-items-center justify-content-between pe-3 ps-2 border-bottom border-2 py-2 text-decoration-none text-dark quicklinks-row-hover"
        >
          <div className="d-flex align-items-center gap-3">
            <div className="icon-placeholder bg-light rounded-circle d-flex align-items-center justify-content-center">
              <img src="delivery.png" width={25} alt="" />
            </div>
            <div>
              <div className="fw-semibold small">Track Your Order</div>
            </div>
          </div>
          <IoIosArrowForward className="text-secondary" />
        </Link>

        {sections.map((section, i) => (
          <div key={i}>
            {section.title && (
              <div className="px-3 py-2 text-uppercase quick-title small fw-bold border-bottom border-2">
                {section.title}
              </div>
            )}
            {section.links.map((link, idx) => (
              <Link
                key={idx}
                to={link.to}
                className="d-flex align-items-center justify-content-between border-bottom border-2 px-3 py-2 text-decoration-none text-dark quicklinks-row-hover"
              >
                <div className="d-flex align-items-center gap-3">
                  <div className="icon-placeholder bg-light rounded-circle d-flex align-items-center justify-content-center">
                    {link.icon}
                  </div>
                  <div>
                    <div className="fw-normal small head-text quick-text">
                      {link.text}
                    </div>
                    {link.subtext && (
                      <div className="text-muted small inner-text">
                        {link.subtext}
                      </div>
                    )}
                  </div>
                </div>
                {link.arrow && <IoIosArrowForward className="text-secondary" />}
              </Link>
            ))}
          </div>
        ))}
      </div>
    </Container>
  );
};

export default QuickLinks;
