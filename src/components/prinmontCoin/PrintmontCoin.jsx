import React from "react";
import { Container, Card, Button } from "react-bootstrap";
import { FaBolt } from "react-icons/fa";
import "./PrintmontCoin.css";

const PrintmontCoin = () => {
  const transactions = [
    { text: "Redemption of Rs.800 Off on MediBuddy Full Body Health C...", change: -20, date: "Debited on 29 Apr 2025" },
    { text: "Hindware Smart Appliances 85 L Desert Air Cooler", change: +25, date: "To be credited by 05 May 2025" },
    { text: "nextwave LAPTOP STAND WITH ADJUSTMENT LEVELS F...", change: +4, date: "To be credited by 02 May 2025" },
    { text: "maeesa Women Ethnic Dress Grey Midi/Calf Length Dress", change: +10, date: "To be credited by 05 May 2025" },
    { text: "NIRLON Stainless Steel Fridge Water Bottle 1000 ml Bottle", change: +2, date: "Credited on 20 Apr 2025 | Valid till 20 Jul 2025" },
    { text: "Extra Coins Cashback on NIRLON Stainless Steel Fridge W...", change: +181, date: "Credited on 20 Apr 2025 | Expires in 21 days" },
    { text: "MILTON Glide 1000 Stainless Steel Single Walled Water Bot...", change: +4, date: "Credited on 19 Apr 2025 | Expires in 20 days" },
  ];

  return (
    <div className="pmc-wrapper">
      <Container className="pmc-container py-3">
        {/* Header */}
        <Card className="pmc-header-card text-center border-0 shadow-sm mb-3">
          <h2 className="fw-bold mb-1 d-flex justify-content-start justify-content-lg-center align-items-center">
            <img src="/printmont-coin.png" width={25} alt="" className="me-1"/>
            445
          </h2>
          <p className="text-muted text-start text-lg-center small mb-2">Available Balance</p>

          <div className="d-flex flex-column flex-md-row align-items-start gap-4 justify-content-center">
            <div className="pmc-info bg-light rounded p-2 mb-2 text-start text-md-center">
            <span className="fw-semibold text-primary">
              💡 114 SuperCoins on the way
            </span>
            <p className="text-muted small mb-0">
              SuperCoins are credited after return period is over.
            </p>
          </div>

          <div className="pmc-info bg-light rounded p-2 mb-2 text-start text-md-center">
            <span className="fw-semibold text-danger">
              ⚠️ 128 SuperCoins expiring in 1 day
            </span>
          </div>
          </div>

          {/* This button is visible on large screens */}
          <div className="d-none d-md-block">
            <Button className="rounded-sm bg-theme px-4 py-2 fw-semibold">
              Use SuperCoins
            </Button>
          </div>

          <p className="small text-muted mt-2 mb-0">
            SuperCoins now available on <b>Myntra</b> & <b>Cleartrip</b>
          </p>
        </Card>

        {/* Transactions */}
        <Card className="pmc-transactions border-0 shadow-sm mb-0 mb-lg-5">
          <h6 className="fw-bold p-3 border-bottom mb-0">Recent Transactions</h6>
          <div className="pmc-transaction-list">
            {transactions.map((t, i) => (
              <div
                key={i}
                className="pmc-transaction-item d-flex justify-content-between align-items-start border-bottom p-2"
              >
                <div className="">
                  <p className="mb-1 small fw-semibold text-dark">{t.text}</p>
                  <p className="mb-0 text-muted small">{t.date}</p>
                </div>
                <span
                  className={`fw-bold small ${
                    t.change > 0 ? "text-success" : "text-danger"
                  } ps-3`}
                >
                  {t.change > 0 ? `+${t.change}` : t.change}
                </span>
              </div>
            ))}
          </div>
        </Card>
      </Container>

      {/* Sticky button only for mobile */}
      <div className="pmc-sticky-btn shadow-lg d-md-none p-0 ">
        <Button className="w-100 fw-semibold py-2 bg-theme rounded-0">
          Use SuperCoins
        </Button>
      </div>
    </div>
  );
};

export default PrintmontCoin;
