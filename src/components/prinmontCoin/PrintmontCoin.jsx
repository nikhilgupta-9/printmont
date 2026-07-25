import React from "react";
import { Container, Row, Col, Card, Button, Badge, ProgressBar, ListGroup } from "react-bootstrap";
import {
  FaCoins,
  FaCrown,
  FaHistory,
  FaGift,
  FaExchangeAlt,
  FaInfoCircle,
  FaWallet
} from "react-icons/fa";
import { useAuth } from "../../context/AuthContext";
import { Link, useNavigate } from "react-router-dom";
import "./PrintmontCoin.css";

const PrintmontCoin = () => {
  const navigate = useNavigate();
  const { user, logout } = useAuth();

  const transactions = [
    { text: "Redeemed: ₹200 Discount Coupon on Custom Hoodies", change: -20, date: "Debited on 29 Apr 2025", type: "debit" },
    { text: "Earned: Order Placement Reward (Custom Polo Shirt)", change: 25, date: "Credited on 05 May 2025", type: "credit" },
    { text: "Earned: Signup Welcome Bonus Coins", change: 440, date: "Credited on 20 Apr 2025", type: "credit" },
  ];

  const rewardVouchers = [
    { title: "₹100 Off Coupon", coins: 100, desc: "Applicable on any customized gift mugs or keychains.", code: "MUG100" },
    { title: "Free Standard Delivery", coins: 150, desc: "Get free delivery on your next 3 consecutive orders.", code: "FREESHIP" },
    { title: "₹300 Off Coupon", coins: 300, desc: "Applicable on corporate gift hampers or customized hoodies.", code: "HOODIE300" },
  ];

  const handleLogout = () => {
    logout();
    navigate("/login");
  };

  // Helper to get initials
  const getUserInitials = () => {
    if (!user) return "U";
    const first = user.firstName || user.first_name || user.name || "U";
    const last = user.lastName || user.last_name || "";
    return (first[0] + (last ? last[0] : "")).toUpperCase();
  };

  // Helper to get display name
  const getUserDisplayName = () => {
    if (!user) return "Store Guest";
    if (user.firstName || user.first_name) {
      return `${user.firstName || user.first_name} ${user.lastName || user.last_name || ""}`.trim();
    }
    return user.name || (user.email ? user.email.split("@")[0] : "User");
  };

  return (
    <div className="bg-light pt-2 pb-4 pt-md-2 pb-md-5" style={{ minHeight: "85vh" }}>
      <Container>
        <Row className="g-4">
          {/* MAIN WALLET PORTLET */}
          <Col xs={12}>
            {/* HERO WALLET CARD */}
            <Card
              className="border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 text-white position-relative overflow-hidden"
              style={{
                background: "linear-gradient(135deg, #0b53a1 0%, #002b66 100%)",
                boxShadow: "0 15px 30px rgba(11, 83, 161, 0.15)"
              }}
            >
              {/* Decorative Background Circles */}
              <div className="position-absolute bg-white bg-opacity-5 rounded-circle" style={{ width: "300px", height: "300px", top: "-100px", right: "-100px" }} />
              <div className="position-absolute bg-white bg-opacity-5 rounded-circle" style={{ width: "150px", height: "150px", bottom: "-50px", left: "-50px" }} />

              <Row className="align-items-center g-4 position-relative">
                <Col md={7}>
                  <div className="d-flex align-items-center gap-2 mb-3">
                    <FaCrown size={22} className="text-warning animate-bounce" />
                    <Badge bg="warning" className="text-dark fw-bold text-uppercase px-3 py-1.5 rounded-pill fs-7">
                      Gold Tier Member
                    </Badge>
                  </div>
                  <h6 className="text-white-50 text-uppercase fw-bold tracking-wider mb-1" style={{ fontSize: "0.85rem" }}>
                    Available PrintCoins Balance
                  </h6>
                  <h1 className="fw-bold display-4 mb-2 d-flex align-items-center gap-2">
                    <FaCoins className="text-warning" />
                    445 <span className="fs-6 fw-normal text-white-50">Coins</span>
                  </h1>
                  <p className="small text-white-50 mb-0">
                    💡 1 Coin = ₹1. Use these coins for additional order discounts at checkout.
                  </p>
                </Col>
                <Col md={5}>
                  <div className="bg-white bg-opacity-10 rounded-4 p-3 border border-white border-opacity-10 text-white">
                    <div className="d-flex justify-content-between align-items-center mb-2 small fw-semibold">
                      <span>Next Tier: Platinum</span>
                      <span>55 Coins Needed</span>
                    </div>
                    <ProgressBar now={89} variant="warning" className="mb-2" style={{ height: "6px" }} />
                    <span className="text-white-50 xsmall d-block">
                      Spend ₹55 to unlock 2x cashback rate and free priority processing!
                    </span>
                  </div>
                </Col>
              </Row>
            </Card>

            {/* EXPIRY ALERTS BAR */}
            <div className="alert alert-warning border-0 shadow-sm rounded-4 p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2" style={{ background: "#fff9db" }}>
              <div className="d-flex align-items-center gap-2 text-dark small">
                <span className="fs-5">⚠️</span>
                <span><strong>128 SuperCoins expiring soon:</strong> Redeem these coins before they expire to avoid losing your cashback balance.</span>
              </div>
              <Button variant="warning" size="sm" className="rounded-pill fw-bold text-dark fs-7 px-3 py-1">
                Redeem Coins
              </Button>
            </div>

            {/* VOUCHER & REWARDS DECK */}
            <h5 className="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
              <FaGift className="text-primary" /> Claim Reward Vouchers
            </h5>
            <Row className="g-3 mb-4">
              {rewardVouchers.map((voucher, idx) => (
                <Col key={idx} xs={12} md={4}>
                  <Card className="border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-top border-4 border-warning">
                    <h6 className="fw-bold text-dark mb-1 fs-6">{voucher.title}</h6>
                    <p className="small text-muted mb-3" style={{ fontSize: "0.8rem", lineHeight: "1.4" }}>
                      {voucher.desc}
                    </p>
                    <div className="d-flex align-items-center justify-content-between pt-2 border-top mt-auto">
                      <span className="fw-bold text-primary small d-flex align-items-center gap-1">
                        <FaCoins size={12} className="text-warning" /> {voucher.coins} Coins
                      </span>
                      <Button variant="outline-primary" size="sm" className="rounded-pill fw-bold fs-8 px-3">
                        Claim Code
                      </Button>
                    </div>
                  </Card>
                </Col>
              ))}
            </Row>

            {/* RECENT TRANSACTIONS FEED */}
            <Card className="border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-0 mb-lg-5">
              <div className="d-flex align-items-center gap-2 p-3 border-bottom bg-white">
                <FaHistory className="text-primary" />
                <h6 className="fw-bold text-dark m-0">Recent Coin Transactions</h6>
              </div>
              <ListGroup variant="flush">
                {transactions.map((t, idx) => (
                  <ListGroup.Item key={idx} className="d-flex justify-content-between align-items-center py-3 border-bottom px-3">
                    <div className="min-w-0 me-3">
                      <p className="mb-0.5 text-dark fw-semibold small text-truncate" style={{ fontSize: "0.85rem" }}>
                        {t.text}
                      </p>
                      <span className="text-muted" style={{ fontSize: "0.72rem" }}>
                        <FaExchangeAlt size={10} className="me-1" /> {t.date}
                      </span>
                    </div>
                    <span className={`fw-bold small fs-6 flex-shrink-0 ${t.type === 'credit' ? 'text-success' : 'text-danger'}`}>
                      {t.type === 'credit' ? `+${t.change}` : t.change}
                    </span>
                  </ListGroup.Item>
                ))}
              </ListGroup>
            </Card>
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default PrintmontCoin;
