import React, { useState } from "react";

const GiftCard = () => {
  const [cardValue, setCardValue] = useState(0);
  const [cards, setCards] = useState(1);

  return (
    <div className="container pt-4 bg-white">
      {/* Top Links */}
      <div className="d-flex justify-content-between mb-3">
        <h5 className="fw-semibold">PrintMont GiftCard</h5>

        <div>
            <a href="#" className="me-3 text-primary fw-semibold text-decoration-none small">
          Buy a Gift Card
        </a>
        <a href="#" className="text-primary fw-semibold text-decoration-none small">
          Check Gift Card Balance
        </a>
        </div>
      </div>

      {/* Add a Gift Card */}
      <div className="card mb-3">
        <div className="card-body">
          <a href="#" className="text-primary fw-semibold text-decoration-none">
            + ADD A GIFT CARD
          </a>
        </div>
      </div>

      {/* Buy Gift Card Section */}
      <div className="card">
        <div className="card-header bg-white fw-bold">Buy a Flipkart Gift Card</div>

        <div className="card-body">
          {/* Tabs */}
          <ul className="nav nav-tabs mb-3">
            <li className="nav-item">
              <button className="nav-link active fw-semibold">PERSONAL GIFT CARDS</button>
            </li>
            <li className="nav-item">
              <button className="nav-link fw-semibold">CORPORATE REQUIREMENTS</button>
            </li>
          </ul>

          <div className="row">
            {/* Left Side Form */}
            <div className="col-md-7">
              <form>
                <div className="mb-3">
                  <input id="gift-receiver-email" name="gift-receiver-email" type="email" className="form-control" placeholder="Receiver's Email ID *" required />
                </div>
                <div className="mb-3">
                  <input id="gift-receiver-name" name="gift-receiver-name" type="text" className="form-control" placeholder="Receiver's Name *" required/>
                </div>

                <div className="row g-2 mb-3">
                  <div className="col-md-6">
                    <select
                      className="form-select"
                      onChange={(e) => setCardValue(Number(e.target.value))}
                    >
                      <option id="gift-card-0" value="0">Card Value in ₹</option>
                      <option id="gift-card-500" value="500">₹500</option>
                      <option id="gift-card-1000" value="1000">₹1000</option>
                      <option id="gift-card-2000" value="2000">₹2000</option>
                    </select>
                  </div>
                  <div className="col-md-6">
                    <input
                    id="card-amount-quantity"
                    name="card-amount-quantity"
                      type="number"
                      className="form-control"
                      min="1"
                      value={cards}
                      onChange={(e) => setCards(Number(e.target.value))}
                    />
                  </div>
                </div>

                <div className="mb-3">
                  <input
                    name="gifter-name"
                    id="gifter-name"
                    type="text"
                    className="form-control"
                    placeholder="Gifter's Name (Optional)"
                  />
                </div>

                <div className="mb-3">
                  <textarea
                    className="form-control"
                    id="gifter-message"
                    name="gifter-message"
                    placeholder="Write a message (Optional, 100 characters)"
                    maxLength={100}
                  ></textarea>
                </div>

                <a href="#" className="text-primary fw-semibold text-decoration-none">
                  + Buy Another Gift Card
                </a>
              </form>
            </div>

            {/* Right Side Preview */}
            <div className="col-md-5 text-center">
              <div className="border p-3">
                <h6 className="text-muted">Gift Card Value</h6>
                <h3 className="fw-bold text-primary">₹{cardValue * cards}</h3>
                <img
                  src="https://img.freepik.com/free-vector/happy-birthday-gift-card-template_23-2148693090.jpg"
                  alt="Gift Card"
                  className="img-fluid mt-3 rounded"
                />
              </div>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="card-footer bg-white">
          <button className="btn btn-warning fw-bold w-100">
            BUY GIFT CARD FOR ₹{cardValue * cards}
          </button>
        </div>
      </div>
    </div>
  );
};

export default GiftCard;
