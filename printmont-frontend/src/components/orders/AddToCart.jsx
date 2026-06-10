import React, { useState } from "react";

const AddToCart = () => {
  const [pincode, setPincode] = useState("");
  const [cartItems, setCartItems] = useState([]);
  const [savedItems, setSavedItems] = useState([]);
  const deliveryAddress =
    "B-106, Shreenathji Park, Chanod, Vapi, Gujarat - 396195";

  const itemCount = cartItems.length;
  const totalPrice = cartItems.reduce(
    (acc, item) => acc + item.price * item.quantity,
    0
  );
  const discount = 0;
  const deliveryCharges = 0;
  const savings = discount;

  return (
    <div className="custom-bg">
      <div className="container pb-5 py-2 my-lg-2 flip-bg  ">
      <div className="row">
        {/* LEFT SIDE: Address + Saved for Later */}
        <div className="col-lg-8">
          {/* Saved Address */}
          <div className="card mb-3">
            <div className="card-header d-flex justify-content-between align-items-center">
              <strong>From Saved Address</strong>
              <button className="bg-light px-2 py-1 rounded border-1 border-dark ">Enter Pincode</button>
              
            </div>
            <div className="card-body d-flex justify-content-between align-items-end">
              <div>
                <strong>Delivery Address:</strong>
                <p className="mb-0">{deliveryAddress}</p>
              </div>
              <button className=" px-3 px-lg-5 py-lg-2 py-1 rounded-0 bg-theme border-0 txsm def-btn">PLACE ORDER</button>
            </div>
          </div>

          {/* Saved for Later */}
          <div className="card">
            <div className="card-header">
              <strong>Saved For Later</strong>
            </div>
            <div className="card-body">
              {savedItems.length === 0 ? (
                <p>No items saved for later.</p>
              ) : (
                savedItems.map((item) => <div>{item.name}</div>)
              )}
            </div>
          </div>
        </div>

        {/* RIGHT SIDE: Price Details */}
        <div className="col-lg-4">
          <div className="card">
            <div className="card-header">
              <strong>PRICE DETAILS</strong>
            </div>
            <div className="card-body">
              <ul className="list-unstyled mb-3">
                <li className="d-flex justify-content-between mb-2">
                  <span>Price ({itemCount} items)</span>
                  <span>₹{totalPrice}</span>
                </li>
                <li className="d-flex justify-content-between mb-2">
                  <span>Discount</span>
                  <span className="text-success">-₹{discount}</span>
                </li>
                <li className="d-flex justify-content-between mb-2">
                  <span>Delivery Charges</span>
                  <span className="text-success">
                    {deliveryCharges === 0 ? "FREE" : `₹${deliveryCharges}`}
                  </span>
                </li>
              </ul>
              <hr />
              <div className="d-flex justify-content-between mb-2">
                <strong>Total Amount</strong>
                <strong>₹{totalPrice - discount}</strong>
              </div>
              <p className="text-success mt-2">
                You will save ₹{savings} on this order
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
  );
};

export default AddToCart;
