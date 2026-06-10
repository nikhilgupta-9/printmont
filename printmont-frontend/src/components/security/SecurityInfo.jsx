import React from 'react';
import { Link } from 'react-router';
import Categories from '../pages/category-list/Categories';

const SecurityInfo = () => {
  return (
    <div className="container p-4 bg-white ">
      
      
      <h4 className="mb-4">Safe and Secure Shopping</h4>

      <div className="security-faqs mb-4">
        
        {/* FAQ 1 */}
        <div className="mb-3">
          <h6 className="mb-0">Is making online payment secure on Printmont?</h6>
          <p className="ms-3 mb-0 small">Yes, making the online payment is secure on Printmont.</p>
        </div>

        {/* FAQ 2 */}
        <div className="mb-3">
          <h6 className="mb-0">Does Printmont store my credit/debit card information?</h6>
          <p className="ms-3 mb-0 small">No. Printmont only stores the last 4 digits of your card number for the purpose of card identification.</p>
        </div>

        {/* FAQ 3: Cards Accepted */}
        <div className="mb-3">
          <h6 className="mb-0">What credit/debit cards are accepted on Printmont?</h6>
          <p className="ms-3 mb-0 small">We accept VISA, MasterCard, Maestro, Rupay, American Express, Diner’s Club and Discover credit/debit cards.</p>
        </div>

        {/* FAQ 4: Cards from other countries */}
        <div className="mb-3">
          <h6 className="mb-0">Do you accept payment made by credit/debit cards issued in other countries?</h6>
          <p className="ms-3 mb-0 small">
            Yes! We accept VISA, MasterCard, Maestro, American Express credit/debit cards issued by banks in India and in the following countries: Australia, Austria, Belgium, Canada, Cyprus, Denmark, Finland, France, Germany, Ireland, Italy, Luxembourg, the Netherlands, New Zealand, Norway, Portugal, Singapore, Spain, Sweden, the UK and the US. Please note that we do not accept internationally issued credit/debit cards for EGV payments/top-ups.
          </p>
        </div>

        {/* FAQ 5: Other payment options */}
        <div className="mb-3">
          <h6 className="mb-0">What other payment options are available on Printmont?</h6>
          <p className="ms-3 mb-0 small">
            Apart from Credit and Debit Cards, we accept payments via Internet Banking (covering 44 banks), Cash on Delivery, Equated Monthly Installments (EMI), E-Gift Vouchers, Printmont Pay Later, UPI, Wallet, and Paytm Postpaid.
          </p>
        </div>
      </div>
      
      <hr className="my-3" /> 

      {/* Privacy Policy */}
      <div className="privacy-policy mb-3">
        <h6 className="mb-1">Privacy Policy</h6>
        <p className="ms-3 mb-0 small">
          Printmont.com respects your privacy and is committed to protecting it. For more details, please see our <Link to="/policy/privacy" className="text-decoration-none">Privacy Policy</Link>
        </p>
      </div>

      {/* Contact Us */}
      <div className="contact-us">
        <h6 className="mb-1">Contact Us</h6>
        <p className="ms-3 mb-0 small">
          Couldn’t find the information you need? Please 
          <Link to="/contact" className="text-decoration-none">Contact Us</Link>
        </p>
      </div>

    </div>
  );
};

export default SecurityInfo;