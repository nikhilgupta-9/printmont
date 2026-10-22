import React, { useState, useEffect } from "react";
import { Row, Col, ProgressBar, Spinner, Modal, Badge } from "react-bootstrap";
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
  FaBolt,
  FaTags,
  FaTicketAlt,
  FaFilter,
  FaInfoCircle,
  FaArrowUp,
  FaArrowDown,
  FaWallet,
  FaPlusCircle,
  FaUndoAlt,
  FaCreditCard,
  FaLock,
  FaUniversity,
  FaCheck
} from "react-icons/fa";
import { IoClose } from "react-icons/io5";
import { SiGooglepay, SiPhonepe, SiPaytm } from "react-icons/si";
import { Link, useNavigate, useLocation } from "react-router-dom";
import { toast } from "react-hot-toast";
import { useAuth } from "../../context/AuthContext";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import { updatePageSEO } from "../../utils/seo";
import "./UserWallet.css";

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
    desc: "Get ₹0 delivery fee on any customized gift or print order.",
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

const DEFAULT_COIN_TRANSACTIONS = [
  {
    id: 1,
    text: "Welcome Signup Bonus Credited",
    change: 100,
    date: "Credited on Account Creation",
    type: "credit",
    description: "Welcome bonus credited for registering on Printmont"
  },
  {
    id: 2,
    text: "Earned on Order #PM-84910 (Custom Hoodies)",
    change: 40,
    date: "Credited on Order Delivery",
    type: "credit",
    description: "2 Coins per ₹100 spent on delivered order"
  },
  {
    id: 3,
    text: "Photo Review & Rating Bonus",
    change: 25,
    date: "Credited for Verified Review",
    type: "credit",
    description: "Bonus reward for customer review with photos"
  },
  {
    id: 4,
    text: "Redeemed: Flat ₹55 Discount at Checkout",
    change: -55,
    date: "Debited on Order #PM-81204",
    type: "debit",
    description: "SuperCoins deducted for instant cart discount"
  }
];

const UserWallet = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { user, getUsernamePath } = useAuth();
  const usernamePath = getUsernamePath ? getUsernamePath() : "user";

  // Check URL to determine initial active mode: 'wallet' vs 'coins'
  const isCoinsRoute = location.pathname.endsWith("/coins");
  const [mainTab, setMainTab] = useState(isCoinsRoute ? "coins" : "wallet");

  // Coins state
  const [activeTab, setActiveTab] = useState("passbook"); // 'passbook' | 'rewards' | 'earn_rules'
  const [coinFilterType, setCoinFilterType] = useState("all"); // 'all' | 'credit' | 'debit'
  const [coinData, setCoinData] = useState({
    balance: 100,
    tier: "Gold Member",
    lifetime_earned: 100,
    lifetime_spent: 0,
    rules: {
      coins_per_100_spent: 2,
      max_coins_per_order: 50,
      coin_value_inr: 1.0,
      welcome_bonus: 100,
      review_bonus: 25
    },
    transactions: DEFAULT_COIN_TRANSACTIONS,
    rewards: DEFAULT_REWARDS
  });

  // Wallet state (Real Money in ₹)
  const [walletData, setWalletData] = useState({
    balance: 0.00,
    total_added: 0.00,
    total_refunded: 0.00,
    total_spent: 0.00,
    status: "active"
  });
  // Real passbook from wallet-api.php (no demo entries)
  const [walletTransactions, setWalletTransactions] = useState([]);
  const [walletRefresh, setWalletRefresh] = useState(0);
  const [walletFilterType, setWalletFilterType] = useState("all"); // 'all' | 'topup' | 'refund' | 'debit'

  // Add Money Modal state
  const [showAddMoneyModal, setShowAddMoneyModal] = useState(false);
  const [addAmount, setAddAmount] = useState(500);
  const [addingMoney, setAddingMoney] = useState(false);
  const [paymentMode, setPaymentMode] = useState("upi");

  const [loading, setLoading] = useState(true);
  const [claimingId, setClaimingId] = useState(null);
  const [claimedModal, setClaimedModal] = useState({ show: false, code: "", title: "" });

  // Sync main tab if route changes
  useEffect(() => {
    if (location.pathname.endsWith("/coins")) {
      setMainTab("coins");
    } else if (location.pathname.endsWith("/wallet")) {
      setMainTab("wallet");
    }
  }, [location.pathname]);

  const handleSwitchTab = (tab) => {
    setMainTab(tab);
    navigate(`/${usernamePath}/${tab}`, { replace: true });
  };

  useEffect(() => {
    updatePageSEO({
      title: mainTab === "wallet" ? "My Cash Wallet (₹) | Printmont" : "My SuperCoins & Rewards | Printmont",
      description: "Manage your live Printmont Cash Wallet balance, add money, track order cancellation refund reversals, and check SuperCoins passbook.",
      keywords: "wallet, supercoins, cash balance, passbook, refund reversal, printmont"
    });

    const fetchData = async () => {
      try {
        setLoading(true);
        const token = localStorage.getItem("token") || sessionStorage.getItem("token");
        const headers = token ? { Authorization: `Bearer ${token}` } : {};

        // Fetch Wallet Data
        const walletRes = await fetch(API_ENDPOINTS.WALLET || "/api/wallet-api.php", { headers });
        const walletJson = await walletRes.json();

        if (walletJson && walletJson.success && walletJson.data) {
          if (walletJson.data.wallet) {
            setWalletData(walletJson.data.wallet);
          }
          setWalletTransactions(Array.isArray(walletJson.data.transactions) ? walletJson.data.transactions : []);
        }

        // Fetch Coins Data
        const coinsRes = await fetch(API_ENDPOINTS.COINS || "/api/wallet-coins-api.php", { headers });
        const coinsJson = await coinsRes.json();

        if (coinsJson && coinsJson.success && coinsJson.data) {
          setCoinData((prev) => ({
            ...prev,
            ...coinsJson.data,
            rewards: coinsJson.data.rewards?.length > 0 ? coinsJson.data.rewards : DEFAULT_REWARDS,
            transactions: coinsJson.data.transactions?.length > 0 ? coinsJson.data.transactions : DEFAULT_COIN_TRANSACTIONS
          }));
        }
      } catch (err) {
        console.error("Error fetching wallet & coin data:", err);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [user, mainTab, walletRefresh]);

  // Add money: Razorpay order from the server → Razorpay payment → server verifies the signature
  // and only then credits the wallet (wallet-api.php add_money / verify_add_money).
  const loadRazorpay = () => new Promise((resolve) => {
    if (window.Razorpay) return resolve(true);
    const script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.async = true;
    script.onload = () => resolve(true);
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });

  const handleAddMoneySubmit = async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    const amountVal = parseFloat(addAmount);

    if (isNaN(amountVal) || amountVal < 10) {
      toast.error("Please enter a valid amount (Minimum ₹10)");
      return;
    }

    const authHeaders = () => {
      const token = localStorage.getItem("token") || sessionStorage.getItem("token");
      return { "Content-Type": "application/json", ...(token ? { Authorization: `Bearer ${token}` } : {}) };
    };

    try {
      setAddingMoney(true);
      const res = await fetch(`${API_ENDPOINTS.WALLET}?action=add_money`, {
        method: "POST",
        headers: authHeaders(),
        body: JSON.stringify({ amount: amountVal }),
      });
      const json = await res.json().catch(() => null);
      if (!json?.success || !json.razorpay) {
        toast.error(json?.error || "Could not start the payment. Please try again.");
        setAddingMoney(false);
        return;
      }

      const ready = await loadRazorpay();
      if (!ready || !window.Razorpay) {
        toast.error("Could not load the payment window. Please check your connection.");
        setAddingMoney(false);
        return;
      }

      const rzp = new window.Razorpay({
        key: json.razorpay.key_id,
        order_id: json.razorpay.order_id,
        amount: json.razorpay.amount,
        currency: json.razorpay.currency || "INR",
        name: "Printmont",
        description: `Add ₹${amountVal} to Printmont Wallet`,
        prefill: { name: user?.first_name || user?.name || "", email: user?.email || "" },
        method: { upi: paymentMode === "upi", card: paymentMode === "card", netbanking: paymentMode === "netbanking" },
        theme: { color: "#0c53a1" },
        handler: async (response) => {
          try {
            const vRes = await fetch(`${API_ENDPOINTS.WALLET}?action=verify_add_money`, {
              method: "POST",
              headers: authHeaders(),
              body: JSON.stringify({
                razorpay_order_id: response.razorpay_order_id,
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_signature: response.razorpay_signature,
              }),
            });
            const vJson = await vRes.json().catch(() => null);
            if (vJson?.success) {
              setShowAddMoneyModal(false);
              setWalletRefresh((k) => k + 1); // reload balance + passbook from the server
              toast.success(`₹${amountVal} added to your Printmont Wallet!`);
            } else {
              toast.error(vJson?.error || "Payment could not be confirmed. Please contact support.");
            }
          } catch {
            toast.error("Could not confirm the payment. Please contact support if money was deducted.");
          } finally {
            setAddingMoney(false);
          }
        },
        modal: {
          ondismiss: () => {
            toast("Payment cancelled. No money was added.", { icon: "ℹ️" });
            setAddingMoney(false);
          },
        },
      });
      rzp.on("payment.failed", (resp) => {
        toast.error(resp.error?.description || "Payment failed. Please try again.");
        setAddingMoney(false);
      });
      rzp.open();
    } catch (err) {
      console.error("Error adding money:", err);
      toast.error("Network error while adding money.");
      setAddingMoney(false);
    }
  };

  // Handle Claim Coin Voucher
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
          lifetime_spent: prev.lifetime_spent + reward.coins,
          transactions: [
            {
              id: Date.now(),
              text: `Redeemed: ${reward.title}`,
              change: -reward.coins,
              date: "Debited Just Now",
              type: "debit",
              description: `Coupon code ${json.coupon_code || reward.code} claimed`
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

  // Filtered lists
  const filteredCoinTransactions = (coinData.transactions || []).filter((tx) => {
    if (coinFilterType === "credit") return tx.type === "credit" || tx.change > 0;
    if (coinFilterType === "debit") return tx.type === "debit" || tx.change < 0;
    return true;
  });

  const filteredWalletTransactions = (walletTransactions || []).filter((tx) => {
    if (walletFilterType === "topup") return tx.category === "topup";
    if (walletFilterType === "refund") return tx.category === "refund_reversal";
    if (walletFilterType === "debit") return tx.type === "debit" || tx.category === "order_payment";
    return true;
  });

  const rules = coinData.rules || {
    coins_per_100_spent: 2,
    max_coins_per_order: 50,
    coin_value_inr: 1.0,
    welcome_bonus: 100,
    review_bonus: 25
  };

  return (
    <div className="uw-wrapper">
      {/* ─────────────────────────────────────────────────────────────────── */}
      {/* TOP DUAL MODE SWITCHER: SEPARATE WALLET (₹) & SUPERCOINS (🪙)      */}
      {/* ─────────────────────────────────────────────────────────────────── */}
      <div className="uw-main-switcher mb-3">
        <button
          type="button"
          className={`uw-switch-tab ${mainTab === "wallet" ? "active-wallet" : ""}`}
          onClick={() => handleSwitchTab("wallet")}
        >
          <FaWallet className={`me-2 ${mainTab === "wallet" ? "uw-tc" : ""}`} />
          <span>Printmont Wallet (₹ Cash)</span>
          <span className="badge ms-2 uw-bg text-white fw-bold">
            ₹{loading ? "..." : walletData.balance.toFixed(2)}
          </span>
        </button>

        <button
          type="button"
          className={`uw-switch-tab ${mainTab === "coins" ? "active-coins" : ""}`}
          onClick={() => handleSwitchTab("coins")}
        >
          <FaCoins className={`me-2 ${mainTab === "coins" ? "text-warning" : ""}`} />
          <span>Printmont SuperCoins</span>
          <span className="badge ms-2 bg-warning text-dark fw-bold">
            {loading ? "..." : coinData.balance} Coins
          </span>
        </button>
      </div>

      {/* ═══════════════════════════════════════════════════════════════════ */}
      {/* 1. PRINTMONT WALLET VIEW (REAL MONEY IN ₹)                          */}
      {/* ═══════════════════════════════════════════════════════════════════ */}
      {mainTab === "wallet" && (
        <div className="uw-wallet-pane">
          {/* Emerald Cash Wallet Hero Card */}
          <div className="uw-hero-card cash-wallet mb-3">
            <div className="uw-hero-ambient-glow"></div>
            <div className="uw-hero-pattern"></div>

            <Row className="align-items-center g-3 position-relative z-1">
              <Col lg={7} xs={12}>
                <div className="d-flex align-items-center gap-2 mb-2 flex-wrap">
                  <span className="badge rounded-pill uw-hero-chip fw-bold" style={{ fontSize: "0.74rem" }}>
                    <FaLock className="me-1" /> Secure Cash Wallet
                  </span>
                  <span className="uw-badge-live">
                    <span className="uw-pulse-dot"></span> 100% Usable Balance
                  </span>
                  <span className="badge rounded-pill bg-white text-dark fw-bold" style={{ fontSize: "0.72rem" }}>
                    1 ₹ Wallet = ₹1.00 Real Cash
                  </span>
                </div>

                <div className="d-flex align-items-center gap-3 my-2">
                  <div className="uw-wallet-badge-3d">
                    <FaWallet style={{ color: "#0c53a1", fontSize: "1.6rem" }} />
                  </div>
                  <div>
                    <div className="uw-balance-amount">
                      ₹{loading ? <Spinner animation="border" size="sm" /> : walletData.balance.toFixed(2)}
                      <span className="uw-balance-label">Cash Balance</span>
                    </div>
                    <div className="uw-balance-sub">
                      Available for instant 1-click checkout on all print orders.
                    </div>
                  </div>
                </div>

                <div className="d-flex gap-2 mt-3 pt-2 border-top border-white border-opacity-10 flex-wrap">
                  <button
                    type="button"
                    className="uw-action-btn emerald"
                    onClick={() => setShowAddMoneyModal(true)}
                  >
                    <FaPlusCircle className="me-1" /> Add Money to Wallet
                  </button>
                  <button
                    type="button"
                    className="uw-action-btn primary"
                    onClick={() => navigate("/allproducts")}
                  >
                    <FaBolt className="me-1" /> Shop with Wallet
                  </button>
                  <button
                    type="button"
                    className="uw-action-btn ghost text-decoration-none"
                    onClick={() => handleSwitchTab("coins")}
                  >
                    <FaCoins className="me-1 text-warning" /> Switch to SuperCoins ({coinData.balance})
                  </button>
                </div>
              </Col>

              <Col lg={5} xs={12}>
                <div className="uw-hero-mini-box">
                  <div className="d-flex justify-content-between align-items-center mb-2">
                    <span className="text-white-50 small fw-semibold">Refund Reversal Protection</span>
                    <span className="uw-hero-active small fw-bold">Active</span>
                  </div>
                  <div className="p-2 rounded bg-white bg-opacity-10 text-white-50 small" style={{ fontSize: "0.78rem" }}>
                    <FaUndoAlt className="text-white me-1" />
                    When an order is cancelled, payment is refunded directly and instantly into this wallet.
                  </div>
                  <div className="d-flex justify-content-between align-items-center mt-2 text-white-50 xsmall" style={{ fontSize: "0.75rem" }}>
                    <span>Total Refunds Credited: <strong>₹{walletData.total_refunded.toFixed(2)}</strong></span>
                  </div>
                </div>
              </Col>
            </Row>
          </div>

          {/* 4 Wallet Metrics Cards */}
          <Row className="g-2 g-md-3 mb-4">
            <Col xs={6} md={3}>
              <div className="uw-metric-card active-balance">
                <div className="uw-metric-icon green">
                  <FaWallet />
                </div>
                <div className="uw-metric-info">
                  <span className="uw-metric-title">Available Cash</span>
                  <h5 className="uw-metric-num uw-tc mb-0">₹{walletData.balance.toFixed(2)}</h5>
                  <span className="uw-metric-sub text-muted">Ready to spend</span>
                </div>
              </div>
            </Col>

            <Col xs={6} md={3}>
              <div className="uw-metric-card">
                <div className="uw-metric-icon blue">
                  <FaArrowUp />
                </div>
                <div className="uw-metric-info">
                  <span className="uw-metric-title">Total Added</span>
                  <h5 className="uw-metric-num text-dark mb-0">₹{walletData.total_added.toFixed(2)}</h5>
                  <span className="uw-metric-sub text-muted">Via Top-ups</span>
                </div>
              </div>
            </Col>

            <Col xs={6} md={3}>
              <div className="uw-metric-card">
                <div className="uw-metric-icon green">
                  <FaUndoAlt />
                </div>
                <div className="uw-metric-info">
                  <span className="uw-metric-title">Refund Reversals</span>
                  <h5 className="uw-metric-num text-dark mb-0">₹{walletData.total_refunded.toFixed(2)}</h5>
                  <span className="uw-metric-sub uw-tc">From Cancellations</span>
                </div>
              </div>
            </Col>

            <Col xs={6} md={3}>
              <div className="uw-metric-card">
                <div className="uw-metric-icon orange">
                  <FaArrowDown />
                </div>
                <div className="uw-metric-info">
                  <span className="uw-metric-title">Total Spent</span>
                  <h5 className="uw-metric-num text-dark mb-0">₹{walletData.total_spent.toFixed(2)}</h5>
                  <span className="uw-metric-sub text-muted">On Orders</span>
                </div>
              </div>
            </Col>
          </Row>

          {/* Order Cancellation Reversal Notice Banner */}
          <div className="uw-reversal-alert d-flex align-items-center gap-3">
            <div className="p-2 rounded-circle uw-bg text-white flex-shrink-0 d-flex align-items-center justify-content-center" style={{ width: 40, height: 40 }}>
              <FaShieldAlt size={20} />
            </div>
            <div className="flex-grow-1">
              <h6 className="fw-bold uw-tc mb-0">Instant Cancellation &amp; Return Reversal Guarantee</h6>
              <p className="small text-muted mb-0">
                Any cancelled order payment or admin refund is credited immediately back to this wallet without bank processing delays. You can use your balance on any future purchase instantly.
              </p>
            </div>
            <button
              type="button"
              className="btn btn-sm uw-btn-outline fw-bold flex-shrink-0"
              onClick={() => setShowAddMoneyModal(true)}
            >
              <FaPlusCircle className="me-1" /> Add Money
            </button>
          </div>

          {/* Wallet Passbook List Card */}
          <div className="uw-passbook-card">
            {/* Filter Bar */}
            <div className="uw-passbook-filter-bar d-flex flex-wrap justify-content-between align-items-center gap-2">
              <div className="d-flex align-items-center gap-2">
                <FaFilter className="text-secondary small" />
                <span className="fw-bold small text-dark">Filter Wallet Passbook:</span>
              </div>
              <div className="d-flex gap-1 flex-wrap">
                <button
                  type="button"
                  className={`uw-filter-btn ${walletFilterType === "all" ? "active" : ""}`}
                  onClick={() => setWalletFilterType("all")}
                >
                  All ({walletTransactions.length})
                </button>
                <button
                  type="button"
                  className={`uw-filter-btn ${walletFilterType === "topup" ? "active" : ""}`}
                  onClick={() => setWalletFilterType("topup")}
                >
                  Money Added (+)
                </button>
                <button
                  type="button"
                  className={`uw-filter-btn ${walletFilterType === "refund" ? "active" : ""}`}
                  onClick={() => setWalletFilterType("refund")}
                >
                  Refund Reversals (↺)
                </button>
                <button
                  type="button"
                  className={`uw-filter-btn ${walletFilterType === "debit" ? "active" : ""}`}
                  onClick={() => setWalletFilterType("debit")}
                >
                  Spends (−)
                </button>
              </div>
            </div>

            {/* Transactions List */}
            <div className="uw-tx-list">
              {filteredWalletTransactions.length === 0 ? (
                <div className="text-center py-5 text-muted">
                  <FaWallet size={40} className="uw-tc opacity-50 mb-2" />
                  <h6 className="fw-bold text-dark">No wallet transactions found</h6>
                  <p className="small mb-3">Add money to your wallet for fast 1-click checkout.</p>
                  <button
                    type="button"
                    className="btn btn-sm uw-btn fw-bold px-3"
                    onClick={() => setShowAddMoneyModal(true)}
                  >
                    + Add Money
                  </button>
                </div>
              ) : (
                filteredWalletTransactions.map((tx, idx) => {
                  const isCredit = tx.type === "credit";
                  const isReversal = tx.category === "refund_reversal";
                  return (
                    <div className="uw-tx-item" key={tx.id || idx}>
                      <div className="d-flex align-items-start gap-3 min-width-0">
                        <div className={`uw-tx-icon-circle ${isCredit ? "credit" : "debit"}`}>
                          {isReversal ? <FaUndoAlt /> : isCredit ? <FaArrowUp /> : <FaArrowDown />}
                        </div>
                        <div className="min-width-0">
                          <h6 className="uw-tx-title text-truncate mb-1">{tx.title}</h6>
                          {tx.description && (
                            <p className="uw-tx-desc text-muted mb-1 text-truncate">{tx.description}</p>
                          )}
                          <div className="d-flex align-items-center gap-2 flex-wrap">
                            <span className="uw-tx-meta">{tx.date}</span>
                            {tx.order_number && (
                              <span className="badge bg-dark font-monospace" style={{ fontSize: "0.68rem" }}>
                                #{tx.order_number}
                              </span>
                            )}
                          </div>
                        </div>
                      </div>

                      <div className="text-end flex-shrink-0">
                        <div className={`uw-tx-amount ${isCredit ? "credit" : "debit"}`}>
                          {isCredit ? `+₹${Math.abs(tx.amount).toFixed(2)}` : `-₹${Math.abs(tx.amount).toFixed(2)}`}
                        </div>
                        <span className={`uw-tx-badge ${isReversal ? "reversal" : isCredit ? "credit" : "debit"}`}>
                          {isReversal ? "Refund Reversed" : isCredit ? "Credited" : "Spent"}
                        </span>
                      </div>
                    </div>
                  );
                })
              )}
            </div>
          </div>
        </div>
      )}

      {/* ═══════════════════════════════════════════════════════════════════ */}
      {/* 2. PRINTMONT SUPERCOINS VIEW (LOYALTY REWARDS & VOUCHERS)           */}
      {/* ═══════════════════════════════════════════════════════════════════ */}
      {mainTab === "coins" && (
        <div className="uw-coins-pane">
          {/* Flipkart-Style Dark Slate & Gold Hero Header */}
          <div className="uw-hero-card mb-3">
            <div className="uw-hero-ambient-glow"></div>
            <div className="uw-hero-pattern"></div>

            <Row className="align-items-center g-3 position-relative z-1">
              <Col lg={7} xs={12}>
                <div className="d-flex align-items-center gap-2 mb-2 flex-wrap">
                  <span className="uw-badge-tier">
                    <FaCrown className="text-warning me-1" /> {coinData.tier || "Gold Member"}
                  </span>
                  <span className="uw-badge-live">
                    <span className="uw-pulse-dot"></span> Live Passbook
                  </span>
                  <span className="uw-badge-rate">1 SuperCoin = ₹1.00</span>
                </div>

                <div className="d-flex align-items-center gap-3 my-2">
                  <div className="uw-coin-badge-3d">
                    <FaCoins className="uw-coin-icon-svg" />
                  </div>
                  <div>
                    <div className="uw-balance-amount">
                      {loading ? <Spinner animation="border" size="sm" /> : coinData.balance}
                      <span className="uw-balance-label">SuperCoins</span>
                    </div>
                    <div className="uw-balance-sub">
                      Usable Value: <strong className="text-warning">₹{coinData.balance * (rules.coin_value_inr || 1)}</strong> (Auto-applies at Checkout)
                    </div>
                  </div>
                </div>

                <div className="d-flex gap-2 mt-3 pt-2 border-top border-white border-opacity-10 flex-wrap">
                  <button
                    type="button"
                    className="uw-action-btn primary"
                    onClick={() => navigate("/allproducts")}
                  >
                    <FaBolt className="me-1" /> Shop &amp; Use Coins
                  </button>
                  <button
                    type="button"
                    className="uw-action-btn secondary"
                    onClick={() => setActiveTab("rewards")}
                  >
                    <FaGift className="me-1" /> Claim Vouchers
                  </button>
                  <button
                    type="button"
                    className="uw-action-btn ghost text-decoration-none"
                    onClick={() => handleSwitchTab("wallet")}
                  >
                    <FaWallet className="me-1 text-white" /> Switch to Wallet (₹{walletData.balance.toFixed(2)})
                  </button>
                </div>
              </Col>

              <Col lg={5} xs={12}>
                <div className="uw-hero-mini-box">
                  <div className="d-flex justify-content-between align-items-center mb-2">
                    <span className="text-white-50 small fw-semibold">Next Tier Progress</span>
                    <span className="text-warning small fw-bold">Platinum Tier</span>
                  </div>
                  <ProgressBar now={85} variant="warning" style={{ height: "7px", borderRadius: "10px" }} />
                  <div className="d-flex justify-content-between align-items-center mt-2 text-white-50 xsmall" style={{ fontSize: "0.75rem" }}>
                    <span>Earn 55 more coins to unlock VIP 3x multiplier</span>
                  </div>
                  
                  <div className="uw-rule-chip mt-2">
                    <FaCoins className="text-warning flex-shrink-0" />
                    <span>Earn <strong>{rules.coins_per_100_spent || 2} SuperCoins</strong> per ₹100 on every order</span>
                  </div>
                </div>
              </Col>
            </Row>
          </div>

          {/* 4 Metric Cards */}
          <Row className="g-2 g-md-3 mb-4">
            <Col xs={6} md={3}>
              <div className="uw-metric-card active-balance">
                <div className="uw-metric-icon gold">
                  <FaCoins />
                </div>
                <div className="uw-metric-info">
                  <span className="uw-metric-title">Available Balance</span>
                  <h5 className="uw-metric-num text-dark mb-0">{coinData.balance}</h5>
                  <span className="uw-metric-sub uw-tc">₹{coinData.balance} Value</span>
                </div>
              </div>
            </Col>

            <Col xs={6} md={3}>
              <div className="uw-metric-card">
                <div className="uw-metric-icon green">
                  <FaArrowUp />
                </div>
                <div className="uw-metric-info">
                  <span className="uw-metric-title">Lifetime Earned</span>
                  <h5 className="uw-metric-num text-dark mb-0">{coinData.lifetime_earned || 100}</h5>
                  <span className="uw-metric-sub text-muted">Total Credited</span>
                </div>
              </div>
            </Col>

            <Col xs={6} md={3}>
              <div className="uw-metric-card">
                <div className="uw-metric-icon orange">
                  <FaArrowDown />
                </div>
                <div className="uw-metric-info">
                  <span className="uw-metric-title">Lifetime Spent</span>
                  <h5 className="uw-metric-num text-dark mb-0">{coinData.lifetime_spent || 0}</h5>
                  <span className="uw-metric-sub text-muted">Redeemed as ₹ Off</span>
                </div>
              </div>
            </Col>

            <Col xs={6} md={3}>
              <div className="uw-metric-card">
                <div className="uw-metric-icon blue">
                  <FaHistory />
                </div>
                <div className="uw-metric-info">
                  <span className="uw-metric-title">Expiring Soon</span>
                  <h5 className="uw-metric-num text-dark mb-0">0</h5>
                  <span className="uw-metric-sub text-muted">Valid for 365 days</span>
                </div>
              </div>
            </Col>
          </Row>

          {/* Navigation Sub-Tabs */}
          <div className="uw-tabs-header mb-3">
            <button
              type="button"
              className={`uw-tab-pill ${activeTab === "passbook" ? "active" : ""}`}
              onClick={() => setActiveTab("passbook")}
            >
              <FaHistory className="me-1.5" /> Coin Activity Passbook
            </button>
            <button
              type="button"
              className={`uw-tab-pill ${activeTab === "rewards" ? "active" : ""}`}
              onClick={() => setActiveTab("rewards")}
            >
              <FaTicketAlt className="me-1.5" /> Reward Vouchers ({coinData.rewards?.length || 6})
            </button>
            <button
              type="button"
              className={`uw-tab-pill ${activeTab === "earn_rules" ? "active" : ""}`}
              onClick={() => setActiveTab("earn_rules")}
            >
              <FaCoins className="me-1.5" /> Ways to Earn More
            </button>
          </div>

          {/* Sub Tab: Coin Activity Passbook */}
          {activeTab === "passbook" && (
            <div className="uw-tab-pane">
              <div className="uw-passbook-card">
                <div className="uw-passbook-filter-bar d-flex flex-wrap justify-content-between align-items-center gap-2">
                  <div className="d-flex align-items-center gap-2">
                    <FaFilter className="text-secondary small" />
                    <span className="fw-bold small text-dark">Filter Transactions:</span>
                  </div>
                  <div className="d-flex gap-1">
                    <button
                      type="button"
                      className={`uw-filter-btn ${coinFilterType === "all" ? "active" : ""}`}
                      onClick={() => setCoinFilterType("all")}
                    >
                      All ({coinData.transactions?.length || 0})
                    </button>
                    <button
                      type="button"
                      className={`uw-filter-btn ${coinFilterType === "credit" ? "active" : ""}`}
                      onClick={() => setCoinFilterType("credit")}
                    >
                      Credited (+)
                    </button>
                    <button
                      type="button"
                      className={`uw-filter-btn ${coinFilterType === "debit" ? "active" : ""}`}
                      onClick={() => setCoinFilterType("debit")}
                    >
                      Debited (−)
                    </button>
                  </div>
                </div>

                <div className="uw-tx-list">
                  {filteredCoinTransactions.length === 0 ? (
                    <div className="text-center py-5 text-muted">
                      <FaCoins size={40} className="text-warning opacity-50 mb-2" />
                      <h6 className="fw-bold text-dark">No transactions found</h6>
                      <p className="small mb-0">No coin records matching the selected filter.</p>
                    </div>
                  ) : (
                    filteredCoinTransactions.map((tx, idx) => {
                      const isCredit = tx.type === "credit" || tx.change > 0;
                      return (
                        <div className="uw-tx-item" key={tx.id || idx}>
                          <div className="d-flex align-items-start gap-3 min-width-0">
                            <div className={`uw-tx-icon-circle ${isCredit ? "credit" : "debit"}`}>
                              {isCredit ? <FaArrowUp /> : <FaArrowDown />}
                            </div>
                            <div className="min-width-0">
                              <h6 className="uw-tx-title text-truncate mb-1">{tx.text}</h6>
                              {tx.description && (
                                <p className="uw-tx-desc text-muted mb-1 text-truncate">{tx.description}</p>
                              )}
                              <span className="uw-tx-meta">{tx.date}</span>
                            </div>
                          </div>

                          <div className="text-end flex-shrink-0">
                            <div className={`uw-tx-amount ${isCredit ? "credit" : "debit"}`}>
                              {isCredit ? `+${Math.abs(tx.change)}` : `-${Math.abs(tx.change)}`} Coins
                            </div>
                            <span className={`uw-tx-badge ${isCredit ? "credit" : "debit"}`}>
                              {isCredit ? "Credited" : "Redeemed"}
                            </span>
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>

                <div className="uw-checkout-notice">
                  <div className="d-flex align-items-center gap-2">
                    <FaBolt className="text-warning flex-shrink-0" />
                    <span className="small text-dark">
                      <strong>Checkout Auto-Apply:</strong> Available SuperCoins can be used directly during checkout to save instant cash on all custom printing orders.
                    </span>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Sub Tab: Rewards */}
          {activeTab === "rewards" && (
            <div className="uw-tab-pane">
              <div className="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <h5 className="fw-bold text-dark mb-0">Exclusive SuperCoin Vouchers</h5>
                  <p className="text-muted small mb-0">Redeem coins for instant discount coupons &amp; special perks.</p>
                </div>
                <div className="uw-coins-available-pill">
                  Balance: <FaCoins className="text-warning mx-1" /> <strong>{coinData.balance}</strong>
                </div>
              </div>

              <Row className="g-3">
                {(coinData.rewards || []).map((reward) => (
                  <Col lg={4} md={6} xs={12} key={reward.id}>
                    <div className="uw-voucher-ticket">
                      <div className="uw-ticket-notch left"></div>
                      <div className="uw-ticket-notch right"></div>

                      <div className="d-flex justify-content-between align-items-center mb-2">
                        <span className="uw-voucher-tag">{reward.category || "Discount"}</span>
                        <span className="uw-voucher-cost">
                          <FaCoins className="text-warning me-1" /> {reward.coins} Coins
                        </span>
                      </div>

                      <h6 className="uw-voucher-title mb-1">{reward.title}</h6>
                      <p className="uw-voucher-desc mb-3">{reward.desc}</p>

                      <div className="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span className="badge bg-light text-dark font-monospace border px-2 py-1">
                          {reward.code}
                        </span>
                        <button
                          type="button"
                          className="btn btn-sm btn-warning text-dark fw-bold px-3 rounded-pill"
                          disabled={coinData.balance < reward.coins || claimingId === reward.id}
                          onClick={() => handleClaimReward(reward)}
                        >
                          {claimingId === reward.id ? (
                            <Spinner animation="border" size="sm" />
                          ) : coinData.balance >= reward.coins ? (
                            "Claim Voucher"
                          ) : (
                            "Need More Coins"
                          )}
                        </button>
                      </div>
                    </div>
                  </Col>
                ))}
              </Row>
            </div>
          )}

          {/* Sub Tab: Earn Rules */}
          {activeTab === "earn_rules" && (
            <div className="uw-tab-pane">
              <Row className="g-3">
                <Col md={6}>
                  <div className="uw-earn-card">
                    <div className="uw-earn-icon blue">
                      <FaUserPlus />
                    </div>
                    <div>
                      <h6 className="fw-bold mb-1">First-Time Welcome Bonus</h6>
                      <p className="text-muted small mb-1">
                        Get <strong>{rules.welcome_bonus || 100} SuperCoins</strong> instantly when you create a new account on Printmont.
                      </p>
                      <span className="badge bg-info-subtle text-info fw-bold">Always Credited to New Users</span>
                    </div>
                  </div>
                </Col>

                <Col md={6}>
                  <div className="uw-earn-card">
                    <div className="uw-earn-icon green">
                      <FaShoppingBag />
                    </div>
                    <div>
                      <h6 className="fw-bold mb-1">Earn on Every Purchase</h6>
                      <p className="text-muted small mb-1">
                        Earn <strong>{rules.coins_per_100_spent || 2} SuperCoins</strong> for every ₹100 spent on completed orders (Max {rules.max_coins_per_order || 50} coins/order).
                      </p>
                      <span className="badge bg-success-subtle text-success fw-bold">Auto-Awarded on Delivery</span>
                    </div>
                  </div>
                </Col>

                <Col md={6}>
                  <div className="uw-earn-card">
                    <div className="uw-earn-icon orange">
                      <FaStar />
                    </div>
                    <div>
                      <h6 className="fw-bold mb-1">Product Reviews &amp; Photos</h6>
                      <p className="text-muted small mb-1">
                        Earn up to <strong>{rules.review_bonus || 25} SuperCoins</strong> when you post a verified review with photos for your delivered item.
                      </p>
                      <span className="badge bg-warning-subtle text-dark fw-bold">Verified Reviews</span>
                    </div>
                  </div>
                </Col>

                <Col md={6}>
                  <div className="uw-earn-card">
                    <div className="uw-earn-icon purple">
                      <FaGift />
                    </div>
                    <div>
                      <h6 className="fw-bold mb-1">Admin Loyalty Gifts</h6>
                      <p className="text-muted small mb-1">
                        Printmont admins reward loyal customers with special festive bonus coins and milestone rewards.
                      </p>
                      <span className="badge bg-primary-subtle text-primary fw-bold">Direct Admin Grants</span>
                    </div>
                  </div>
                </Col>
              </Row>
            </div>
          )}
        </div>
      )}

      {/* ─────────────────────────────────────────────────────────────────── */}
      {/* MODAL: ADD MONEY TO WALLET                                          */}
      {/* ─────────────────────────────────────────────────────────────────── */}
      <Modal 
        show={showAddMoneyModal} 
        onHide={() => setShowAddMoneyModal(false)} 
        centered
        dialogClassName="uw-add-money-modal-dialog"
        contentClassName="uw-add-money-modal-content border-0 shadow-lg"
      >
        {/* Header */}
        <div className="uw-add-money-header">
          <div className="d-flex align-items-center gap-2.5">
            <div className="uw-add-money-header-icon">
              <FaWallet />
            </div>
            <div>
              <h5 className="fs-6 fw-bold text-white mb-0">Add Money to Wallet</h5>
              <div className="text-white-50" style={{ fontSize: "0.74rem" }}>
                Instant top-up &bull; 100% Safe &amp; Secure
              </div>
            </div>
          </div>
          <button 
            type="button" 
            className="uw-add-money-close-btn" 
            onClick={() => setShowAddMoneyModal(false)}
            aria-label="Close"
          >
            <IoClose size={20} />
          </button>
        </div>

        <form onSubmit={handleAddMoneySubmit}>
          <div className="uw-add-money-body">
            {/* Current Balance Card */}
            <div className="uw-add-money-balance-card">
              <div>
                <span className="text-muted d-block" style={{ fontSize: "0.74rem", fontWeight: 600 }}>
                  CURRENT WALLET BALANCE
                </span>
                <div className="uw-add-money-balance-val">
                  ₹{walletData.balance.toFixed(2)}
                </div>
              </div>
              <span className="badge bg-white text-primary border shadow-xs d-flex align-items-center gap-1 py-1.5 px-2.5" style={{ fontSize: "0.72rem" }}>
                <FaBolt className="text-warning" /> 1-Click Pay Ready
              </span>
            </div>

            {/* Top-up Amount Input */}
            <div className="mb-2">
              <div className="d-flex align-items-center justify-content-between mb-1.5">
                <label className="form-label fw-bold small text-dark mb-0">
                  Enter Top-up Amount
                </label>
                <span className="text-muted" style={{ fontSize: "0.72rem" }}>
                  Min ₹10 &bull; Max ₹1,00,000
                </span>
              </div>
              <div className="uw-amount-input-box">
                <span className="uw-amount-currency">₹</span>
                <input
                  type="number"
                  className="uw-amount-input-field"
                  value={addAmount}
                  onChange={(e) => setAddAmount(e.target.value)}
                  placeholder="0"
                  min="10"
                  max="100000"
                  required
                />
                {addAmount !== "" && (
                  <button 
                    type="button" 
                    className="uw-amount-clear-btn" 
                    onClick={() => setAddAmount("")}
                    title="Clear amount"
                  >
                    <IoClose size={14} />
                  </button>
                )}
              </div>
            </div>

            {/* Quick Amount Chips */}
            <div className="uw-quick-chips-grid">
              {[100, 200, 500, 1000, 2000].map((chip) => (
                <button
                  type="button"
                  key={chip}
                  className={`uw-quick-chip-btn ${Number(addAmount) === chip ? "active" : ""}`}
                  onClick={() => setAddAmount(chip)}
                >
                  +₹{chip}
                </button>
              ))}
            </div>

            {/* Payment Mode Selector */}
            <div className="mb-3">
              <label className="form-label fw-bold small text-dark mb-2 d-flex align-items-center justify-content-between">
                <span>Select Payment Method</span>
                <span className="badge bg-success-subtle text-success border border-success-subtle" style={{ fontSize: "0.68rem" }}>
                  Zero Gateway Fee
                </span>
              </label>

              <div className="uw-payment-methods-list">
                {/* 1. UPI */}
                <div
                  className={`uw-payment-method-card ${paymentMode === "upi" ? "active" : ""}`}
                  onClick={() => setPaymentMode("upi")}
                >
                  <div className="uw-pay-icon-box upi">
                    <FaBolt />
                  </div>
                  <div className="flex-grow-1">
                    <div className="d-flex align-items-center gap-1.5 flex-wrap">
                      <span className="fw-bold small text-dark">Instant UPI</span>
                      <div className="d-inline-flex align-items-center gap-1 ms-1 text-muted" style={{ fontSize: "0.95rem" }}>
                        <SiGooglepay title="Google Pay" className="text-secondary" />
                        <SiPhonepe title="PhonePe" className="text-primary" />
                        <SiPaytm title="Paytm" className="text-info" />
                      </div>
                      <span className="badge bg-success text-white ms-auto" style={{ fontSize: "0.62rem", padding: "2px 6px" }}>
                        FASTEST
                      </span>
                    </div>
                    <div className="text-muted" style={{ fontSize: "0.72rem", marginTop: "2px" }}>
                      Google Pay, PhonePe, Paytm, BHIM &amp; Any UPI App
                    </div>
                  </div>
                  <div className="uw-custom-radio-circle">
                    {paymentMode === "upi" && <span className="uw-custom-radio-dot" />}
                  </div>
                </div>

                {/* 2. Card */}
                <div
                  className={`uw-payment-method-card ${paymentMode === "card" ? "active" : ""}`}
                  onClick={() => setPaymentMode("card")}
                >
                  <div className="uw-pay-icon-box card">
                    <FaCreditCard />
                  </div>
                  <div className="flex-grow-1">
                    <div className="fw-bold small text-dark">Credit / Debit Card</div>
                    <div className="text-muted" style={{ fontSize: "0.72rem", marginTop: "2px" }}>
                      Visa, Mastercard, RuPay, Maestro accepted
                    </div>
                  </div>
                  <div className="uw-custom-radio-circle">
                    {paymentMode === "card" && <span className="uw-custom-radio-dot" />}
                  </div>
                </div>

                {/* 3. Net Banking */}
                <div
                  className={`uw-payment-method-card ${paymentMode === "netbanking" ? "active" : ""}`}
                  onClick={() => setPaymentMode("netbanking")}
                >
                  <div className="uw-pay-icon-box bank">
                    <FaUniversity />
                  </div>
                  <div className="flex-grow-1">
                    <div className="fw-bold small text-dark">Net Banking</div>
                    <div className="text-muted" style={{ fontSize: "0.72rem", marginTop: "2px" }}>
                      SBI, HDFC, ICICI, Axis &amp; 50+ Major Indian Banks
                    </div>
                  </div>
                  <div className="uw-custom-radio-circle">
                    {paymentMode === "netbanking" && <span className="uw-custom-radio-dot" />}
                  </div>
                </div>
              </div>
            </div>

            {/* Security Trust Strip */}
            <div className="uw-security-trust-strip">
              <FaShieldAlt size={16} className="text-success flex-shrink-0" />
              <span>
                100% Safe &amp; Secure. Wallet balance never expires and can be used on any order.
              </span>
            </div>
          </div>

          {/* Footer CTA */}
          <div className="uw-add-money-footer">
            <button
              type="submit"
              className="uw-add-money-submit-btn"
              disabled={addingMoney || !addAmount || Number(addAmount) < 10}
            >
              {addingMoney ? (
                <>
                  <Spinner size="sm" animation="border" className="me-2" />
                  <span>Securing Payment Gateway...</span>
                </>
              ) : (
                <>
                  <FaLock size={13} />
                  <span>Proceed to Add ₹{Number(addAmount) || 0}</span>
                </>
              )}
            </button>
            <div className="uw-razorpay-trust-note">
              <FaShieldAlt size={11} className="text-muted" />
              <span>Secured by Razorpay &bull; 256-Bit SSL Encrypted &bull; Instant Credit</span>
            </div>
          </div>
        </form>
      </Modal>

      {/* ─────────────────────────────────────────────────────────────────── */}
      {/* MODAL: CLAIMED VOUCHER SUCCESS                                     */}
      {/* ─────────────────────────────────────────────────────────────────── */}
      <Modal show={claimedModal.show} onHide={() => setClaimedModal({ show: false, code: "", title: "" })} centered>
        <Modal.Body className="text-center p-4">
          <div className="mb-3">
            <FaCheckCircle className="text-success" size={54} />
          </div>
          <h5 className="fw-bold text-dark mb-1">Voucher Claimed! 🎉</h5>
          <p className="text-muted small mb-3">{claimedModal.title}</p>

          <div className="p-3 bg-light rounded-3 border mb-3 d-flex justify-content-between align-items-center">
            <span className="font-monospace fw-bold fs-5 text-dark letter-spacing-1">{claimedModal.code}</span>
            <button
              type="button"
              className="btn btn-sm btn-outline-primary"
              onClick={() => copyToClipboard(claimedModal.code)}
            >
              <FaCopy className="me-1" /> Copy
            </button>
          </div>

          <button
            type="button"
            className="btn btn-primary fw-bold w-100"
            onClick={() => {
              setClaimedModal({ show: false, code: "", title: "" });
              navigate("/allproducts");
            }}
          >
            Shop Now &amp; Apply Coupon
          </button>
        </Modal.Body>
      </Modal>
    </div>
  );
};

export default UserWallet;
