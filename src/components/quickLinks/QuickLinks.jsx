import React, { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { Container } from "react-bootstrap";
import { IoIosArrowForward } from "react-icons/io";
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
                { icon: "Icon", text: "Account Related Queries", subtext: "Answer to all account related queries.", arrow: true },
                { icon: "Icon", text: "All Help Topics", subtext: "Answer to frequently asked questions.", arrow: true },
            ],
        },
        {
            title: "SUPPORT :",
            links: [
                { icon: "Icon", text: "Business Solutions", subtext: "Answer to all your business related solutions.", arrow: true },
                { icon: "Icon", text: "Bulk Orders", subtext: "Best discount to all product on bulk orders.", arrow: true },
                { icon: "Icon", text: "Help Center", subtext: "How can we help?.", arrow: true },
            ],
        },
        {
            title: "Need More Help?",
            links: [
                { icon: "Icon", text: "Franchise", subtext: "Low investment high return I promise.", arrow: true },
                { icon: "Icon", text: "Become a seller", subtext: "Sell online to crores of customers at 0% Commission.", arrow: true },
            ],
        },
        {
            title: "OUR COMPANY :",
            links: [
                { icon: "Icon", text: "About Us", subtext: "Since Printmont inception in 2019.", arrow: true },
                { icon: "Icon", text: "Blog", subtext: "Celebrate Relations.", arrow: true },
                { icon: "Icon", text: "Careers", subtext: "Where Passion Meets Career.", arrow: true },
                { icon: "Icon", text: "Affiliate Program", subtext: "3 Simple Steps to Partner with us!.", arrow: true },
                { icon: "Icon", text: "Security", subtext: "Safe and Secure Shopping.", arrow: true },
                { icon: "Icon", text: "Sitemap", subtext: "Trending Product Pages.", arrow: true },
                { icon: "Icon", text: "Contact Us", subtext: "Contact Details and General Queries.", arrow: true },
            ],
        },
        {
            title: "POLICY INFO :",
            links: [
                { icon: "Icon", text: "Terms & Conditions", subtext: "The following Terms and Conditions.", arrow: true },
                { icon: "Icon", text: "Privacy Policy", subtext: "The following Privacy Policy.", arrow: true },
                { icon: "Icon", text: "Shipping Policy", subtext: "The following Shipping Policy.", arrow: true },
                { icon: "Icon", text: "Return & Refund Policy", subtext: "The following Return and Refund Policy.", arrow: true },
                { icon: "Icon", text: "Terms of use", subtext: "The following Terms of use.", arrow: true },
            ],
        },
    ];

  return (
    <Container fluid className="quicklinks-container p-0">
      <div className="bg-white">
        <div className="d-flex align-items-center justify-content-between pe-3 ps-2 border-bottom border-2 py-2">
          <div className="d-flex align-items-center gap-3">
            <div className="icon-placeholder bg-light rounded-circle d-flex align-items-center justify-content-center">
              <img src="delivery.png" width={25} alt="" />
            </div>
            <div>
              <div className="fw-semibold small">Track Your Order</div>
            </div>
          </div>
          <IoIosArrowForward className="text-secondary" />
        </div>

        {sections.map((section, i) => (
          <div key={i}>
            {section.title && (
              <div className="px-3 py-2 text-uppercase quick-title small fw-bold border-bottom border-2">
                {section.title}
              </div>
            )}
            {section.links.map((link, idx) => (
              <div
                key={idx}
                className="d-flex align-items-center justify-content-between border-bottom border-2 px-3 py-2"
              >
                <div className="d-flex align-items-center gap-3">
                  <div className="icon-placeholder bg-gray rounded-circle d-flex align-items-center justify-content-center">
                    {typeof link.icon === "string" ? (
                      <span className="text-secondary small">{link.icon}</span>
                    ) : (
                      link.icon
                    )}
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
              </div>
            ))}
          </div>
        ))}
      </div>
    </Container>
  );
};

export default QuickLinks;
