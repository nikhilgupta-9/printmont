import React, { useState, useMemo } from 'react';
import { IoMdArrowDropdown } from 'react-icons/io';

const OrderSummary = ({ onContinue }) => {
  const [coupan, setCoupan] = useState(true)

  const printmontcoin = 36;

  const initialCartItems = [
    { id: 1, name: "Printmont Rust Brown Half-Sleeves Knitted Mens Shirt", size: "M", color: "Brown", price: 549, originalPrice: 645, discount: 35, offers: 2, quantity: 1 },
    { id: 2, name: "Printmont Blue Half-Sleeves Knitted Mens Shirt", size: "L", color: "Blue", price: 699, originalPrice: 999, discount: 30, offers: 1, quantity: 1 },
    { id: 3, name: "Printmont Green T-Shirt", size: "M", color: "Green", price: 349, originalPrice: 499, discount: 20, offers: 0, quantity: 1 },
    { id: 4, name: "Printmont White Casual Shirt", size: "XL", color: "White", price: 799, originalPrice: 1199, discount: 40, offers: 3, quantity: 1 },
  ];

  const [cartItems, setCartItems] = useState(initialCartItems);
  const [isSummaryDetailed, setIsSummaryDetailed] = useState(true);

  const totalItems = useMemo(() => {
    return cartItems.reduce((total, item) => total + item.quantity, 0);
  }, [cartItems]);

  const handleContinue = () => {
    setIsSummaryDetailed(false);

    // ✅ Trigger next step (Payment) after a short delay
    setTimeout(() => {
      if (onContinue) onContinue();
    }, 300);
  };

  const handleSeeDetails = () => {
    setIsSummaryDetailed(true);
  };

  const CartItem = ({ item }) => (
    <>
      <div className="d-flex pt-3 ">
        <div className="col-2 col-md-1 flex-shrink-0" style={{ width: '100px' }}>
          <img src="/men_shirt/men-shirt-2.jpeg" alt={item.name} className="img-fluid" />
        </div>

        <div className="col-8 col-md-7 ps-3 d-flex flex-column justify-content-between ">
          <div>
            <p className="fw-semibold mb-1">{item.name}</p>
            <p className="text-success fs-6 fw-bold mb-1">
              ₹{(item.price * item.quantity).toLocaleString('en-IN')}
              <span className="text-secondary mb-0 ms-2 text-decoration-line-through">
                ₹{(item.originalPrice * item.quantity).toLocaleString('en-IN')}
              </span>
              ({item.discount}% off) | {item.offers} offers applied
            </p>
            <p className="text-muted small mb-0">Size: {item.size}</p>
            <p className="text-muted small mb-0">Color: {item.color}</p>
            <p className="text-muted small mb-0">
              Seller: name here
              <img src="/Asured.png" className='ms-2' width={60} alt="Printmont Assured logo" />
            </p>
          </div>
        </div>

        <div className="text-start d-none d-md-block border-start ms-2 ps-3">
          <p className="text-uppercase text-dark fw-semibold mb-1 small fs-6">Delivery On</p>
          <p className="small mb-0 mt-4">
            <span className='fw-bold text-dark fs-6'>24</span><sup>th</sup> Oct, Friday 2025
          </p>
          <p className="small text-secondary mb-0">
            Delivery charges <span className="fw-bold text-success">FREE</span>
          </p>
          <p className="small text-secondary">Standard Delivery.</p>
        </div>
      </div>
      <hr className='m-0 my-2' />
    </>
  );

  return (
    <>
      <div className='bg-white'>
        {/* Condensed Summary (when collapsed) - REMAINS UNCHANGED */}
        <div className='p-3 d-flex justify-content-between'>
          <div>
            <h4 className='text-primary lh-1'>Pay Using Cash Coins</h4>
            <p className='mb-0'>
              Balance
              <img src="/printmont-coin.png" width={20} alt="" className='ms-1' />
              <span className='ms-1'>{printmontcoin}</span>
            </p>
            <p className='small text-success my-0'>Save {printmontcoin}rs Using from {printmontcoin} SuperCoins</p>
          </div>
          <div className='d-none d-md-block'>
            <button className='px-3 py-2 border-0 bg-theme' onClick={() => setCoupan(!coupan)} style={{minWidth:'120px'}}>
              {coupan ? "Applied" : "Apply"}
              </button>
          </div>
        </div>
        {!isSummaryDetailed && (
          <div className="card shadow-sm border-0 rounded-bottom p-3 mb-4 bg-white d-flex flex-row justify-content-between align-items-center">
            <div className="fs-6 fw-normal text-muted">
              ({totalItems}) Items in your order
            </div>
            <button
              className="btn btn-sm text-theme fw-bold p-0 d-flex align-items-center justify-content-center"
              onClick={handleSeeDetails}
              style={{ backgroundColor: 'transparent', border: 'none' }}
            >
              SEE <span className='ms-1'>&rarr;</span>
            </button>
          </div>
        )}

        {/* Detailed Summary (visible when expanded) */}
        {isSummaryDetailed && (
          <div className="card bg-white border-0 shadow-sm rounded-bottom">
            <div className="d-grid gap-3 p-3 bg-white">
              {cartItems.map(item => (
                <CartItem key={item.id} item={item} />
              ))}
            </div>

            {/* 🛑 INLINE BUTTON - Visible ONLY on medium (md) and larger screens */}
            <div className="d-none d-md-flex justify-content-end p-3 mt-2 bg-white border-top">
              <button
                className="fw-bold text-uppercase px-4 py-2 rounded border-0 bg-theme text-white"
                onClick={handleContinue}
              >
                Continue to Payment
              </button>
            </div>

            {/* 🛑 SPACER - Needed on small screens to prevent the fixed button from hiding content */}
            <div className="d-md-none" style={{ height: '70px' }}></div>
          </div>
        )}
      </div>

      {/* 🛑 FIXED BUTTON BAR - Visible ONLY on small screens */}
      {isSummaryDetailed && (
        <div className="fixed-bottom-bar d-md-none">
          <button
            className="btn fw-bold text-uppercase px-4 py-2 w-100 rounded border-0 bg-theme text-white"
            onClick={handleContinue}
          >
            Continue to Payment
          </button>
        </div>
      )}
    </>
  );
};

export default OrderSummary;