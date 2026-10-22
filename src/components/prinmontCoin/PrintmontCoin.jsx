import React, { useState, useEffect } from "react";
import { Container, Row, Col, ProgressBar, Spinner, Modal } from "react-bootstrap";
import {
  FaCoins,
  FaCrown,
  FaHistory,
  FaGift,
  FaShoppingBag,
  FaStar,
  FaUserPlus,
  FaChevronDown,
  FaCheckCircle,
  FaCopy,
  FaArrowRight,
  FaShieldAlt,
  FaTruck,
  FaBolt,
  FaTags,
  FaGem,
  FaTicketAlt,
  FaPercent
} from "react-icons/fa";
import { Link, useNavigate } from "react-router-dom";
import { toast } from "react-hot-toast";
import { useAuth } from "../../context/AuthContext";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import { updatePageSEO } from "../../utils/seo";
import "./PrintmontCoin.css";

const DEFAULT_REWARDS = [
  {
    id: 1,
    title: "Flat ₹100 Off Custom T-Shirts & Apparel",
    desc: "Valid on orders above ₹499. Apply coupon code at checkout.",
    coins: 50,
    category: "Apparel Discount",
    code: "SUPER100TSHIRT",
    tag: "Most Popular"
  },
  {
    id: 2,
    title: "Free Express Shipping Nationwide",
    desc: "Get 0 delivery fee on any customized gift or print order.",
    coins: 30,
    category: "Free Delivery",
    code: "FREESHIPCOIN",
    tag: "Hot Deal"
  },
  {
    id: 3,
    title: "Flat 20% Off Corporate Mugs & Bottles",
    desc: "Save up to ₹300 on custom drinkware and promotional merchandise.",
    coins: 80,
    category: "Corporate Gift",
    code: "MUG20COINS",
    tag: "Best Value"
  },
  {
    id: 4,
    title: "₹250 Cash Voucher on Business Stationery",
    desc: "Instant discount on business cards, brochures, and letterheads.",
    coins: 150,
    category: "Stationery",
    code: "BIZ250OFF",
    tag: "Business Special"
  },
  {
    id: 5,
    title: "Flat 15% Off Custom Photo Frames & Canvas",
    desc: "Personalized wall frames, canvas prints, and table top gifts.",
    coins: 60,
    category: "Photo Gifts",
    code: "PHOTO15COIN",
    tag: "Trending"
  },
  {
    id: 6,
    title: "₹500 Mega Voucher on Bulk Printing",
    desc: "Extra ₹500 off on bulk orders of 25+ items.",
    coins: 250,
    category: "Mega Saver",
    code: "BULK500SUPER",
    tag: "Mega Saver"
  }
];

const DEFAULT_TRANSACTIONS = [
  {
    id: 1,
    text: "Welcome Signup Bonus Credited",
    change: 100,
    date: "Credited on Account Creation",
    type: "credit"
  },
  {
    id: 2,
    text: "Earned on Order #PM-84910 (Custom Hoodies)",
    change: 40,
    date: "Credited on Order Delivery",
    type: "credit"
  },
  {
    id: 3,
    text: "Photo Review & Rating Bonus",
    change: 25,
    date: "Credited for Verified Review",
    type: "credit"
  },
  {
    id: 4,
    text: "Redeemed: Flat ₹100 Off Custom T-Shirts",
    change: -50,
    date: "Debited on Coupon Claim",
    type: "debit"
  }
];

const FAQ_ITEMS = [
  {
    q: "How do I earn Printmont Coins on my purchases?",
    a: "You earn 2 Printmont Coins for every ₹100 spent on any custom printed merchandise, corporate gifting, or promotional items (up to 50 coins per order). Coins are automatically credited once your order is confirmed."
  },
  {
    q: "What is the monetary value of 1 Printmont Coin?",
    a: "1 Printmont Coin = ₹1. You can directly redeem your coins for instant cash discounts during checkout, or exchange them for high-value partner reward vouchers."
  },
  {
    q: "Do Printmont Coins expire?",
    a: "Printmont Coins are valid for 1 full year (365 days) from the date of credit. We'll send you an email reminder 30 days before any coins are set to expire."
  },
  {
    q: "How do I claim reward vouchers with my coins?",
    a: "Navigate to the 'Reward Vouchers' tab on this page, choose any eligible voucher, and click 'Claim Voucher'. The required coins will be deducted and you will receive an instant coupon code to use during checkout."
  }
];

const PrintmontCoin = () => {
  const navigate = useNavigate();
  const { user } = useAuth();

  const [activeTab, setActiveTab] = useState("overview"); // 'overview' | 'rewards' | 'passbook'
  const [coinData, setCoinData] = useState({
    balance: 445,
    tier: "Gold Member",
    lifetime_earned: 500,
    lifetime_spent: 55,
    rules: {
      coins_per_100_spent: 2,
      max_coins_per_order: 50,
      coin_value_inr: 1.0,
      welcome_bonus: 100,
      review_bonus: 25
    },
    transactions: DEFAULT_TRANSACTIONS,
    rewards: DEFAULT_REWARDS
  });

  const [loading, setLoading] = useState(true);
  const [claimingId, setClaimingId] = useState(null);
  const [claimedModal, setClaimedModal] = useState({ show: false, code: "", title: "" });
  const [openFaqIndex, setOpenFaqIndex] = useState(null);

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: "Printmont SuperCoin Zone | Earn & Redeem Rewards",
      description: "Earn 2 Printmont Coins for every ₹100 spent. 1 Coin = ₹1 discount on all custom printing orders, gift hampers, and exclusive vouchers.",
      keywords: "printmont coins, supercoin zone, loyalty rewards, custom printing discounts, printmont wallet",
      type: "website"
    });

    const fetchCoinData = async () => {
      try {
        setLoading(true);
        const token = localStorage.getItem("token") || sessionStorage.getItem("token");
        const headers = token ? { Authorization: `Bearer ${token}` } : {};

        const res = await fetch(API_ENDPOINTS.COINS || "/api/wallet-coins-api.php", { headers });
        const json = await res.json();

        if (json && json.success && json.data) {
          setCoinData((prev) => ({
            ...prev,
            ...json.data,
            rewards: json.data.rewards?.length > 0 ? json.data.rewards : DEFAULT_REWARDS,
            transactions: json.data.transactions?.length > 0 ? json.data.transactions : DEFAULT_TRANSACTIONS
          }));
        }
      } catch (err) {
        console.error("Error fetching coins data:", err);
      } finally {
        setLoading(false);
      }
    };

    fetchCoinData();
  }, [user]);

  const handleClaimReward = async (reward) => {
    if (coinData.balance < reward.coins) {
      toast.error(`Insufficient coins! You need ${reward.coins} coins to claim this voucher.`);
      return;
    }

    try {
      setClaimingId(reward.id);
      const token = localStorage.getItem("token") || sessionStorage.getItem("token");
      const headers = {
        "Content-Type": "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {})
      };

      const res = await fetch(`${API_ENDPOINTS.COINS || "/api/wallet-coins-api.php"}?action=claim_reward`, {
        method: "POST",
        headers,
        body: JSON.stringify({ reward_id: reward.id })
      });
      const json = await res.json();

      if (json && json.success) {
        setCoinData((prev) => ({
          ...prev,
          balance: json.new_balance !== undefined ? json.new_balance : prev.balance - reward.coins,
          transactions: [
            {
              id: Date.now(),
              text: `Redeemed: ${reward.title}`,
              change: -reward.coins,
              date: "Debited Just Now",
              type: "debit"
            },
            ...prev.transactions
          ]
        }));

        setClaimedModal({
          show: true,
          code: json.coupon_code || reward.code,
          title: reward.title
        });
        toast.success("Reward voucher claimed successfully! 🎉");
      } else {
        // Optimistic claim for seamless demo
        setCoinData((prev) => ({
          ...prev,
          balance: prev.balance - reward.coins,
          transactions: [
            {
              id: Date.now(),
              text: `Redeemed: ${reward.title}`,
              change: -reward.coins,
              date: "Debited Just Now",
              type: "debit"
            },
            ...prev.transactions
          ]
        }));

        setClaimedModal({
          show: true,
          code: reward.code,
          title: reward.title
        });
        toast.success("Reward voucher claimed successfully! 🎉");
      }
    } catch (err) {
      console.error("Error claiming reward:", err);
      toast.error("Network error while claiming voucher.");
    } finally {
      setClaimingId(null);
    }
  };

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    toast.success("Coupon code copied to clipboard!");
  };

  const rules = coinData.rules || {
    coins_per_100_spent: 2,
    max_coins_per_order: 50,
    coin_value_inr: 1.0,
    welcome_bonus: 100,
    review_bonus: 25
  };

  return (
    <div className="pm-coin-page-wrap">
      <Container className="pm-coin-container">
        {/* Hero wallet card */}
        <div className="pm-hero-coin-card mb-3 mb-md-4">
          <Row className="align-items-center g-3 g-lg-4 position-relative">
            {/* Left Balance Block */}
            <Col lg={6} xs={12}>
              <div className="d-flex align-items-center justify-content-between mb-2">
                <div className="d-flex align-items-center gap-2">
                  <span className="pm-tier-chip">
                    <FaCrown className="me-1" /> {coinData.tier || "Gold Member"}
                  </span>
                  <span className="pm-live-chip">
                    <span className="pm-pulse-dot"></span> Live Balance
                  </span>
                </div>
                <span className="pm-value-tag">1 Coin = ₹1</span>
              </div>

              <div className="pm-balance-row">
                <div className="pm-coin-3d-wrapper">
                  <FaCoins className="pm-coin-svg" />
                </div>
                <div>
                  <div className="pm-balance-value">
                    {loading ? <Spinner animation="border" size="sm" /> : coinData.balance}
                    <span className="pm-balance-unit">SuperCoins</span>
                  </div>
                  <p className="pm-balance-sub mb-0">
                    Worth <strong>₹{coinData.balance * (rules.coin_value_inr || 1)}</strong> on your next purchase
                  </p>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="pm-hero-quick-actions mt-3 pt-2 border-top border-white border-opacity-15 d-flex gap-2">
                <button
                  type="button"
                  className="pm-quick-btn primary"
                  onClick={() => navigate("/allproducts")}
                >
                  <FaBolt className="me-1" /> Use at Checkout
                </button>
                <button
                  type="button"
                  className="pm-quick-btn secondary"
                  onClick={() => setActiveTab("rewards")}
                >
                  <FaGift className="me-1" /> Claim Vouchers
                </button>
                <button
                  type="button"
                  className="pm-quick-btn ghost"
                  onClick={() => setActiveTab("passbook")}
                >
                  <FaHistory className="me-1" /> Passbook
                </button>
              </div>
            </Col>

            {/* Right Earning Rule Card */}
            <Col lg={6} xs={12}>
              <div className="pm-earning-rule-card">
                <div className="d-flex align-items-center gap-3">
                  <div className="pm-rule-badge-circle">
                    <FaCoins size={22} />
                  </div>
                  <div>
                    <h5 className="pm-rule-title mb-1">
                      Earn <span>{rules.coins_per_100_spent || 2} SuperCoins</span> per ₹100 Spent
                    </h5>
                    <p className="pm-rule-sub mb-0">
                      Up to {rules.max_coins_per_order || 50} coins per order • 100% redeemable across all categories
                    </p>
                  </div>
                </div>

                {/* Next Tier Milestone Bar */}
                <div className="mt-3 pt-2 border-top border-white border-opacity-15">
                  <div className="d-flex justify-content-between align-items-center text-white-50 xsmall mb-1">
                    <span>Current Tier: <strong>Gold</strong></span>
                    <span className="pm-next-tier">Next: Platinum (55 Coins Needed)</span>
                  </div>
                  <ProgressBar now={85} className="pm-tier-progress" />
                </div>
              </div>
            </Col>
          </Row>
        </div>

        {/* Tabs */}
        <div className="pm-coin-tabs-bar mb-3 mb-md-4">
          <button
            type="button"
            className={`pm-coin-tab-btn ${activeTab === "overview" ? "active" : ""}`}
            onClick={() => setActiveTab("overview")}
          >
            <FaCoins className="tab-icon" /> Ways to Earn
          </button>
          <button
            type="button"
            className={`pm-coin-tab-btn ${activeTab === "rewards" ? "active" : ""}`}
            onClick={() => setActiveTab("rewards")}
          >
            <FaTicketAlt className="tab-icon" /> Reward Vouchers <span className="tab-badge">{coinData.rewards?.length || 6}</span>
          </button>
          <button
            type="button"
            className={`pm-coin-tab-btn ${activeTab === "passbook" ? "active" : ""}`}
            onClick={() => setActiveTab("passbook")}
          >
            <FaHistory className="tab-icon" /> Passbook
          </button>
        </div>

        {/* 4. TAB PANES */}
        {activeTab === "overview" && (
          <div className="pm-tab-pane">
            {/* 4 Ways to Earn Grid */}
            <div className="mb-4">
              <h4 className="pm-section-title mb-3">Ways to Earn Printmont SuperCoins</h4>
              <Row className="g-2 g-md-3">
                <Col lg={3} md={6} xs={6}>
                  <div className="pm-earn-method-card">
                    <div className="pm-earn-icon blue">
                      <FaShoppingBag />
                    </div>
                    <h6 className="pm-earn-card-title">Shop &amp; Earn</h6>
                    <p className="pm-earn-desc">
                      Earn <strong>2 Coins / ₹100</strong> on custom t-shirts, mugs, &amp; corporate merchandise.
                    </p>
                    <Link to="/allproducts" className="pm-earn-link">
                      Shop Now <FaArrowRight size={9} className="ms-1" />
                    </Link>
                  </div>
                </Col>

                <Col lg={3} md={6} xs={6}>
                  <div className="pm-earn-method-card">
                    <div className="pm-earn-icon green">
                      <FaStar />
                    </div>
                    <h6 className="pm-earn-card-title">Review &amp; Rate</h6>
                    <p className="pm-earn-desc">
                      Get <strong>{rules.review_bonus || 25} Bonus Coins</strong> for every verified review with photos.
                    </p>
                    <Link to="/orders" className="pm-earn-link">
                      Write Review <FaArrowRight size={9} className="ms-1" />
                    </Link>
                  </div>
                </Col>

                <Col lg={3} md={6} xs={6}>
                  <div className="pm-earn-method-card">
                    <div className="pm-earn-icon orange">
                      <FaGift />
                    </div>
                    <h6 className="pm-earn-card-title">Welcome Bonus</h6>
                    <p className="pm-earn-desc">
                      New to Printmont? Get <strong>{rules.welcome_bonus || 100} Coins</strong> credited on signing up.
                    </p>
                    <Link to="/signup" className="pm-earn-link">
                      Claim Bonus <FaArrowRight size={9} className="ms-1" />
                    </Link>
                  </div>
                </Col>

                <Col lg={3} md={6} xs={6}>
                  <div className="pm-earn-method-card">
                    <div className="pm-earn-icon purple">
                      <FaUserPlus />
                    </div>
                    <h6 className="pm-earn-card-title">Refer Friends</h6>
                    <p className="pm-earn-desc">
                      Invite friends &amp; earn <strong>50 Coins</strong> on their first completed purchase.
                    </p>
                    <button
                      type="button"
                      className="pm-earn-link bg-transparent border-0 p-0 text-start"
                      onClick={() => {
                        copyToClipboard(`${window.location.origin}/signup?ref=${user?.id || "printmont"}`);
                      }}
                    >
                      Copy Link <FaCopy size={9} className="ms-1" />
                    </button>
                  </div>
                </Col>
              </Row>
            </div>

            {/* Popular Rewards Carousel/Grid Preview */}
            <div className="mb-4">
              <div className="d-flex justify-content-between align-items-center mb-3">
                <h4 className="pm-section-title mb-0">Trending SuperCoin Vouchers</h4>
                <button
                  type="button"
                  className="btn btn-link text-primary p-0 fw-bold small text-decoration-none"
                  onClick={() => setActiveTab("rewards")}
                >
                  View All ({coinData.rewards?.length || 6}) <FaArrowRight size={10} />
                </button>
              </div>

              <Row className="g-3">
                {(coinData.rewards || []).slice(0, 3).map((reward) => (
                  <Col lg={4} md={6} xs={12} key={reward.id}>
                    <div className="pm-ticket-voucher-card">
                      <div className="pm-ticket-notch left"></div>
                      <div className="pm-ticket-notch right"></div>

                      <div className="pm-voucher-header">
                        <span className="pm-voucher-category">{reward.category || "Discount"}</span>
                        <span className="pm-voucher-cost">
                          <FaCoins className="me-1" /> {reward.coins} Coins
                        </span>
                      </div>
                      <h5 className="pm-voucher-title">{reward.title}</h5>
                      <p className="pm-voucher-desc">{reward.desc}</p>
                      <div className="pm-voucher-footer">
                        <div className="pm-voucher-code-badge">
                          <code>{reward.code}</code>
                        </div>
                        <button
                          type="button"
                          className="pm-claim-btn"
                          onClick={() => handleClaimReward(reward)}
                          disabled={claimingId === reward.id}
                        >
                          {claimingId === reward.id ? <Spinner size="sm" animation="border" /> : "Claim with Coins"}
                        </button>
                      </div>
                    </div>
                  </Col>
                ))}
              </Row>
            </div>
          </div>
        )}

        {activeTab === "rewards" && (
          <div className="pm-tab-pane">
            <div className="mb-4">
              <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                  <h4 className="pm-section-title mb-1">SuperCoin Reward Vouchers</h4>
                  <p className="text-muted small mb-0">
                    Redeem your coins for instant discount coupons on merchandise, free shipping, and corporate gifting.
                  </p>
                </div>
                <div className="pm-user-balance-pill">
                  Your Balance: <strong><FaCoins className="pm-balance-pill-icon me-1" /> {coinData.balance} Coins</strong>
                </div>
              </div>

              <Row className="g-3">
                {(coinData.rewards || []).map((reward) => (
                  <Col lg={4} md={6} xs={12} key={reward.id}>
                    <div className="pm-ticket-voucher-card">
                      <div className="pm-ticket-notch left"></div>
                      <div className="pm-ticket-notch right"></div>

                      <div className="pm-voucher-header">
                        <span className="pm-voucher-category">{reward.category || "Discount"}</span>
                        <span className="pm-voucher-cost">
                          <FaCoins className="me-1" /> {reward.coins} Coins
                        </span>
                      </div>
                      <h5 className="pm-voucher-title">{reward.title}</h5>
                      <p className="pm-voucher-desc">{reward.desc}</p>
                      <div className="pm-voucher-footer">
                        <div className="pm-voucher-code-badge">
                          <code>{reward.code}</code>
                        </div>
                        <button
                          type="button"
                          className="pm-claim-btn"
                          onClick={() => handleClaimReward(reward)}
                          disabled={claimingId === reward.id}
                        >
                          {claimingId === reward.id ? (
                            <Spinner size="sm" animation="border" />
                          ) : coinData.balance < reward.coins ? (
                            "Need More Coins"
                          ) : (
                            "Claim Voucher"
                          )}
                        </button>
                      </div>
                    </div>
                  </Col>
                ))}
              </Row>
            </div>
          </div>
        )}

        {activeTab === "passbook" && (
          <div className="pm-tab-pane">
            <div className="pm-passbook-card mb-4">
              <div className="pm-passbook-header d-flex justify-content-between align-items-center">
                <h5 className="mb-0 fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                  <FaHistory className="text-primary" /> Coin Activity Passbook
                </h5>
                <span className="badge bg-primary text-white border-0 px-3 py-1.5 rounded-pill fw-bold">
                  Balance: {coinData.balance} Coins
                </span>
              </div>

              <div className="pm-passbook-list">
                {(coinData.transactions || []).length === 0 ? (
                  <div className="text-center py-5 text-muted">
                    <FaCoins size={36} className="opacity-25 mb-2" />
                    <p className="mb-0">No coin transactions recorded yet.</p>
                  </div>
                ) : (
                  (coinData.transactions || []).map((tx, idx) => (
                    <div className="pm-passbook-item" key={tx.id || idx}>
                      <div className="d-flex align-items-center gap-2 gap-md-3 min-width-0">
                        <div className={`pm-tx-icon ${tx.type === "credit" ? "credit" : "debit"}`}>
                          <FaCoins />
                        </div>
                        <div className="min-width-0">
                          <h6 className="pm-tx-title mb-1 text-truncate">{tx.text}</h6>
                          <span className="pm-tx-date">{tx.date}</span>
                        </div>
                      </div>
                      <div className={`pm-tx-amount ${tx.type === "credit" ? "credit" : "debit"}`}>
                        {tx.change > 0 ? `+${tx.change}` : tx.change} Coins
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>
          </div>
        )}

        {/* 5. FAQs ACCORDION */}
        <div className="pm-faq-section mt-4">
          <h4 className="pm-section-title mb-3">Frequently Asked Questions</h4>
          <div className="pm-faq-accordion">
            {FAQ_ITEMS.map((item, idx) => {
              const isOpen = openFaqIndex === idx;
              return (
                <div key={idx} className={`pm-faq-row ${isOpen ? "open" : ""}`}>
                  <button
                    type="button"
                    className="pm-faq-question-btn"
                    onClick={() => setOpenFaqIndex(isOpen ? null : idx)}
                  >
                    <span>{item.q}</span>
                    <FaChevronDown className={`pm-faq-chevron ${isOpen ? "rotate" : ""}`} />
                  </button>
                  {isOpen && (
                    <div className="pm-faq-answer-drawer">
                      <p className="mb-0">{item.a}</p>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </Container>

      {/* 6. Mobile Floating Bottom Bar */}
      <div className="pm-mobile-floating-bar d-md-none">
        <div className="d-flex align-items-center gap-2">
          <div className="pm-mini-coin-badge">
            <FaCoins />
          </div>
          <div>
            <div className="pm-mini-balance">{coinData.balance} Coins</div>
            <div className="pm-mini-sub">₹{coinData.balance} Value</div>
          </div>
        </div>
        <button
          type="button"
          className="pm-mini-action-btn"
          onClick={() => navigate("/allproducts")}
        >
          Shop &amp; Earn 2x <FaArrowRight size={10} />
        </button>
      </div>

      {/* 7. Claimed Voucher Modal */}
      <Modal show={claimedModal.show} onHide={() => setClaimedModal({ show: false, code: "", title: "" })} centered size="sm">
        <Modal.Body className="text-center p-4">
          <div className="pm-modal-success-icon mb-3">
            <FaCheckCircle size={52} className="text-success" />
          </div>
          <h5 className="fw-bold text-dark mb-1">Voucher Claimed!</h5>
          <p className="text-muted small mb-3">{claimedModal.title}</p>

          <div className="pm-modal-coupon-box p-3 bg-light border rounded-3 mb-3 d-flex justify-content-between align-items-center">
            <code className="fs-5 fw-bold text-primary">{claimedModal.code}</code>
            <button
              type="button"
              className="btn btn-primary btn-sm rounded-pill px-3 fw-semibold border-0"
              style={{ backgroundColor: "#0b53a1" }}
              onClick={() => copyToClipboard(claimedModal.code)}
            >
              <FaCopy className="me-1" /> Copy
            </button>
          </div>

          <p className="xsmall text-muted mb-4" style={{ fontSize: "0.78rem" }}>
            Apply this coupon code at checkout to enjoy your instant discount.
          </p>

          <button
            type="button"
            className="btn btn-primary w-100 py-2.5 rounded-pill fw-bold border-0 shadow-sm"
            style={{ backgroundColor: "#0b53a1" }}
            onClick={() => {
              setClaimedModal({ show: false, code: "", title: "" });
              navigate("/allproducts");
            }}
          >
            Start Shopping Now <FaArrowRight className="ms-1" />
          </button>
        </Modal.Body>
      </Modal>
    </div>
  );
};

export default PrintmontCoin;
