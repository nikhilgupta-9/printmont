import React, { useState, useRef, useEffect } from "react";
import { FiMinus, FiPlus } from "react-icons/fi";



const BrandDirectory = () => {
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
    <div style={{ fontFamily: "Arial", fontSize: "12px" }} className="bg-white">
      <h6 className="text-secondary">Top Stories: <span className="text-dark">Brand Directory</span></h6>

      <div className="d-flex flex-column mb-3 justify-content-center align-items-starstan p-0 m-0">
        <span><span>MOST SEARCHED FOR ON XORDOX:</span> <a href="#" className="text-dark text-decoration-none">{Array(30).fill("iPhone 13 Pro").join(" | ")}</a></span>
      <span><span className="text-secondary fs-6">LARGE APPLIANCES:</span> <a href="#" className="text-dark text-decoration-none">{Array(20).fill("Refrigerators").join(" | ")}</a></span>
      <span><span className="text-secondary fs-6">GROCERIES:</span> <a href="#" className="text-dark text-decoration-none">{Array(20).fill("Dal Pulses").join(" | ")}</a></span>
      <span><span className="text-secondary fs-6">FOOTWEAR:</span> <a href="#" className="text-dark text-decoration-none">{Array(20).fill("Adidas Shoes").join(" | ")}</a></span>
      <span><span className="text-secondary fs-6">BEST SELLING ON XORDOX:</span> <a href="#" className="text-dark text-decoration-none">{Array(20).fill("Books").join(" | ")}</a></span>
      </div>

      <strong className="text-secondary fs-6">Xordox: The One-stop Shopping Destination</strong>
      <p className="mt-2 text-secondary ">
        E-commerce is revolutionizing the way we all shop in India. Why do you want to hop from one store to another in search of the latest phone when you can find it on the Internet in a single click? Not only mobiles. Xordox houses everything you can possibly imagine from trending electronics like laptops, tablets, smartphones, and mobile accessories to in-vogue fashion staples like shoes, clothing and lifestyle accessories; from modern furniture like sofa sets, dining tables, and wardrobes to appliances that make your life easy like washing machines, TVs, ACs, mixer grinder juicers and other time-saving kitchen and small appliances; from home furnishings like cushion covers, mattresses and bedsheets to toys and musical instruments, we got them all covered. You name it, and you can stay assured about finding them all here. For those of you with erratic working hours, Xordox is your best bet. Shop in your PJs, at night or in the wee hours of the morning. This e-commerce never shuts down.
      </p>
      <p className="txsm text-secondary">
        What's more, with our year-round shopping festivals and events, our prices are irresistible. We're sure you'll find yourself picking up more than what you had in mind. If you are wondering why you should shop from Xordox when there are multiple options available to you, well, the below will answer your question.
      </p>
      <strong className="mt-2 text-secondary fs-6 ">Xordox</strong>
      <p className="txsm text-secondary">product-name-font <br />
      <span>What's more, you can even use the Xordox supercoins for a number of exciting services, like:</span><br />
      <span>What's more, you can even use the Xordox supercoins for a number of exciting services, like:</span><br />
      <span>An annual Zomato Gold membership</span><br />
      <span>An annual Hotstar Premium membership</span><br />
      <span>6 months Gaana plus subscription</span><br />
      <span>Rupees 550 instant discount on flights on ixigo</span><br />
      <span>anCheck out for the entire list. Terms and conditions apply.</span><br />
        </p>
        <strong className="mt-2 fs-6 text-secondary">No Cost EMI</strong>
        <p className="txsm text-secondary">In an attempt to make high-end products accessible to all, our No Cost EMI plan enables you to shop with us under EMI, without shelling out any processing fee. Applicable on select mobiles, laptops, large and small appliances, furniture, electronics and watches, you can now shop without burning a hole in your pocket. If you've been eyeing a product for a long time, chances are it may be up for a no cost EMI. Take a look ASAP! Terms and conditions apply.</p>

        <strong className="mt-4 fs-6 text-secondary">EMI on Debit Cards</strong>
        <p className="txsm text-secondary">Did you know debit card holders account for 79.38 crore in the country, while there are only 3.14 crore credit card holders? After enabling EMI on Credit Cards, in another attempt to make online shopping accessible to everyone, Xordox introduces EMI on Debit Cards, empowering you to shop confidently with us without having to worry about pauses in monthly cash flow. At present, we have partnered with Axis Bank, HDFC Bank, State Bank of India and ICICI Bank for this facility. More power to all our shoppers! Terms and conditions apply.</p>
        <strong className="mt-4 fs-6 text-secondary">Mobile Exchange Offers</strong>
        <p className="txsm text-secondary">Get an instant discount on the phone that you have been eyeing on. Exchange your old mobile for a new one after the Xordox experts calculate the value of your old phone, provided it is in a working condition without damage to the screen. If a phone is applicable for an exchange offer, you will see the 'Buy with Exchange' option on the product description of the phone. So, be smart, always opt for an exchange wherever possible. Terms and conditions apply.</p>
        <strong className="mt-4 fs-6 text-secondary">What Can You Buy From Xordox?</strong>
        <strong className= "mt-4 fs-6 text-secondary">Mobile Changes</strong>
        <p className="txsm text-secondary">From budget phones to state-of-the-art smartphones, we have a mobile for everybody out there. Whether you're looking for larger, fuller screens, power-packed batteries, blazing-fast processors, beautification apps, high-tech selfie cameras or just large internal space, we take care of all the essentials. Shop from top brands in the country like Samsung, Apple, Oppo, Xiaomi, Realme, Vivo, and Honor to name a few. Rest assured, you're buying from only the most reliable names in the market. What's more, with Xordox's Complete Mobile Protection Plan, you will never again find the need to run around service centres. This plan entails you to a number of post-purchase solutions, starting at as low as Rupees 99 only! Broken screens, liquid damage to phone, hardware and software glitches, and replacements - the Flipkart Complete Mobile Protection covers a comprehensive range of post-purchase problems, with door-to-door services.</p>



    </div>
  );

  return (
    <div className="container-fluid">
      {isMobile ? (
        <div>
          {/* Collapsible button only on mobile */}
          <button
            // className="btn btn-light border w-100 text-start shadow-large p-3 text-center text-muted fw-bold fs-5"
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

export default BrandDirectory;
