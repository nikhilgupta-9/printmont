import React, { useState } from "react";
import { Link } from "react-router";
// import "./giftcard.css";

const GiftCard = () => {
  const [cardValue, setCardValue] = useState(0);
  const [cards, setCards] = useState(1);
  const [activeTab, setActiveTab] = useState("personal"); // "personal" | "corporate"

  return (
    <div className="container pt-4 bg-white p-2 rounded">
      {/* Top Links */}
      <div className="d-flex justify-content-between mb-3 flex-wrap">
        <h5 className="fw-semibold">PrintMont GiftCard</h5>
        <div>
          <Link
            to={'#'}
            className="me-3 text-primary fw-semibold text-decoration-none small"
          >
            Buy a Gift Card
          </Link>
          <Link
          to={'#'}
            className="text-primary fw-semibold text-decoration-none small"
          >
            Check Gift Card Balance
          </Link>
        </div>
      </div>

      {/* Add a Gift Card */}
      <div className="card mb-3">
        <div className="card-body">
          <Link to={'#'} className="text-primary fw-semibold text-decoration-none">
            + ADD A GIFT CARD
          </Link>
        </div>
      </div>

      {/* Buy Gift Card Section */}
      <div className="card">
        <div className="card-header bg-white fw-bold">
          Buy a PrintMont Gift Card
        </div>

        <div className="card-body">
          {/* Tabs */}
          <ul className="nav nav-tabs mb-3">
            <li className="nav-item">
              <button
                className={`nav-link fw-semibold ${
                  activeTab === "personal" ? "active" : ""
                }`}
                onClick={() => setActiveTab("personal")}
              >
                PERSONAL GIFT CARDS
              </button>
            </li>
            <li className="nav-item">
              <button
                className={`nav-link fw-semibold ${
                  activeTab === "corporate" ? "active" : ""
                }`}
                onClick={() => setActiveTab("corporate")}
              >
                CORPORATE REQUIREMENTS
              </button>
            </li>
          </ul>

          {/* Content Switch */}
          {activeTab === "personal" ? (
            <>
              {/* PERSONAL FORM */}
              <div className="row">
                {/* Left Side Form */}
                <div className="col-md-7">
                  <form>
                    <div className="mb-3">
                      <input
                        type="email"
                        className="form-control"
                        placeholder="Receiver's Email ID *"
                        required
                      />
                    </div>
                    <div className="mb-3">
                      <input
                        type="text"
                        className="form-control"
                        placeholder="Receiver's Name *"
                        required
                      />
                    </div>

                    <div className="row g-2 mb-3">
                      <div className="col-md-6">
                        <select
                          className="form-select"
                          onChange={(e) => setCardValue(Number(e.target.value))}
                        >
                          <option value="0">Card Value in ₹</option>
                          <option value="500">₹500</option>
                          <option value="1000">₹1000</option>
                          <option value="2000">₹2000</option>
                        </select>
                      </div>
                      <div className="col-md-6">
                        <input
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
                        type="text"
                        className="form-control"
                        placeholder="Gifter's Name (Optional)"
                      />
                    </div>

                    <div className="mb-3">
                      <textarea
                        className="form-control"
                        placeholder="Write a message (Optional, 100 characters)"
                        maxLength={100}
                      ></textarea>
                    </div>

                    <Link to={'#'}
                      className="text-primary fw-semibold text-decoration-none"
                    >
                      + Buy Another Gift Card
                    </Link>
                  </form>
                </div>

                {/* Right Side Preview */}
                <div className="col-md-5 text-center">
                  <div className="border p-3">
                    <h6 className="text-muted">Gift Card Value</h6>
                    <h3 className="fw-bold text-primary">
                      ₹{cardValue * cards}
                    </h3>
                    <img
                      src="https://img.freepik.com/free-vector/happy-birthday-gift-card-template_23-2148693090.jpg"
                      alt="Gift Card"
                      className="img-fluid mt-3 rounded"
                    />
                  </div>
                </div>
              </div>
            </>
          ) : (
            <>
              {/* CORPORATE REQUIREMENT FORM */}
              <div className="row">
                <div className="col-md-8">
                  <form>
                    <div className="row g-3">
                      <div className="col-md-6">
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Company Name *"
                          required
                        />
                      </div>
                      <div className="col-md-6">
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Contact Person *"
                          required
                        />
                      </div>

                      <div className="col-md-6">
                        <input
                          type="email"
                          className="form-control"
                          placeholder="Email ID *"
                          required
                        />
                      </div>
                      <div className="col-md-6">
                        <input
                          type="tel"
                          className="form-control"
                          placeholder="Phone Number *"
                          required
                        />
                      </div>

                      <div className="col-md-6">
                        <input
                          type="number"
                          className="form-control"
                          placeholder="Total Quantity *"
                        />
                      </div>
                      <div className="col-md-6">
                        <select className="form-select">
                          <option value="">Select Card Value</option>
                          <option value="500">₹500</option>
                          <option value="1000">₹1000</option>
                          <option value="2000">₹2000</option>
                          <option value="5000">₹5000</option>
                        </select>
                      </div>

                      <div className="col-12">
                        <textarea
                          className="form-control"
                          placeholder="Additional Requirements / Message"
                          rows="3"
                        ></textarea>
                      </div>
                    </div>

                    <div className="mt-4">
                      <button className="btn btn-warning fw-semibold w-100">
                        SUBMIT CORPORATE ENQUIRY
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </>
          )}
        </div>

        {/* Footer (only visible in personal mode) */}
        {activeTab === "personal" && (
          <div className="card-footer bg-white">
            <button className="btn btn-warning fw-bold w-100">
              BUY GIFT CARD FOR ₹{cardValue * cards}
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default GiftCard;
