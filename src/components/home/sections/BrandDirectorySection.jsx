// BrandDirectorySection.jsx
import React, { useState, useRef, useEffect } from "react";
import { FiMinus, FiPlus } from "react-icons/fi";

const BrandDirectorySection = () => {
  const [isMobile, setIsMobile] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  const contentRef = useRef(null);

  // Detect screen size
  useEffect(() => {
    const checkScreen = () => setIsMobile(window.innerWidth < 768); // mobile breakpoint
    checkScreen();
    window.addEventListener("resize", checkScreen);
    return () => window.removeEventListener("resize", checkScreen);
  }, []);

  const content = (
    <div style={{ fontFamily: "Arial", fontSize: "12px" }} className="bg-white p-3 rounded-3 border">
      <h6 className="text-secondary fw-bold">Top Stories: <span className="text-dark">Printmont Brand Directory</span></h6>

      <div className="d-flex flex-column mb-3 justify-content-center align-items-starstan p-0 m-0">
        <span><span className="fw-bold">MOST SEARCHED ON PRINTMONT:</span> <span className="text-dark">Custom T-Shirts | Corporate Hoodies | Photo Coffee Mugs | Acrylic Frames | Neon Signage | Metal Keychains | Personalized Pens | ID Cards &amp; Lanyards | Gift Hampers | Canvas Wall Prints</span></span>
        <span><span className="text-secondary fs-6">CUSTOM APPAREL:</span> <span className="text-dark">Round Neck T-Shirts | Polo T-Shirts | Oversized Hoodies | Embroidered Caps | Corporate Uniforms</span></span>
        <span><span className="text-secondary fs-6">PERSONALIZED GIFTS:</span> <span className="text-dark">Magic Mugs | Customized Water Bottles | Engraved Wooden Plaques | LED Photo Cushions | Desktop Organizers</span></span>
        <span><span className="text-secondary fs-6">CORPORATE MERCHANDISE:</span> <span className="text-dark">Welcome Joining Kits | Executive Diaries | Metal Pen Sets | Tech Accessories | Custom Tote Bags</span></span>
      </div>

      <strong className="text-secondary fs-6">Printmont: India's Premier Custom Printing &amp; Personalized Gifting Destination</strong>
      <p className="mt-2 text-secondary">
        Printmont is revolutionizing personalized gifting and customized corporate merchandise across India. From individual custom prints to bulk business orders, we deliver top-tier print quality, durable materials, and lightning-fast dispatch. Design your own clothing, branded drinkware, customized office supplies, and bespoke gifts in just a few clicks.
      </p>
    </div>
  );

  return (
    <div className="w-100 p-0 m-0">
      {isMobile ? (
        <div>
          {/* Collapsible button only on mobile */}
          <button
            className="d-flex w-100 shadow-large py-2 border bd py-2 align-items-center justify-content-center gap-3 text-muted fs-5 fw-semibold"
            onClick={() => setIsOpen(!isOpen)}
          >
            Why Us{isOpen ? <FiMinus /> : <FiPlus/>}
          </button>

          <div
            ref={contentRef}
            style={{
              maxHeight: isOpen ? contentRef.current.scrollHeight + "px" : "0px",
              overflow: "hidden",
              transition: "max-height 0.4s ease"
            }}
          >
            <div className="p-2">{content}</div>
          </div>
        </div>
      ) : (
        // On big screens → show normally
        content
      )}
    </div>
  );
};

export default BrandDirectorySection;
