import React, { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { 
  FaGift, 
  FaRegCopy, 
  FaCheck, 
  FaShoppingBag, 
  FaTicketAlt, 
  FaClock, 
  FaSearch, 
  FaChevronDown, 
  FaChevronUp, 
  FaPercent, 
  FaTag, 
  FaCheckCircle,
  FaArrowRight
} from "react-icons/fa";
import toast from "react-hot-toast";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import "./GiftCard.css";

const inr = (n) => `₹${Math.round(Number(n) || 0).toLocaleString("en-IN")}`;
const day = (d) => (d ? new Date(d.replace(" ", "T")).toLocaleDateString("en-IN", { day: "numeric", month: "short", year: "numeric" }) : "");

const TABS = [
  { key: "all_active", label: "Available Coupons" },
  { key: "store_coupons", label: "Store Offers" },
  { key: "vouchers", label: "My Gift Vouchers" },
  { key: "closed", label: "Expired & Used" },
];

const GiftCard = () => {
  // Store Coupons from Admin (coupons.php)
  const [coupons, setCoupons] = useState([]);
  // Personal Gift Vouchers earned on orders (gift-voucher-api.php)
  const [vouchers, setVouchers] = useState([]);
  const [voucherSummary, setVoucherSummary] = useState({ active: 0, used: 0, expired: 0, cancelled: 0, active_value: 0 });
  const [offers, setOffers] = useState([]);
  
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("all_active");
  const [searchQuery, setSearchQuery] = useState("");
  const [copied, setCopied] = useState("");
  const [openTerms, setOpenTerms] = useState({});

  // Quick Coupon Checker
  const [checkCode, setCheckCode] = useState("");
  const [checkLoading, setCheckLoading] = useState(false);
  const [checkResult, setCheckResult] = useState(null);

  useEffect(() => {
    let alive = true;
    (async () => {
      setLoading(true);
      try {
        const token = localStorage.getItem("token");
        const headers = token ? { Authorization: `Bearer ${token}` } : {};

        const [couponsRes, mineRes, offersRes] = await Promise.all([
          fetch(API_ENDPOINTS.COUPONS_ALL || `${API_ENDPOINTS.COUPONS_AVAILABLE}`).catch(() => null),
          fetch(API_ENDPOINTS.GIFT_VOUCHERS_MY, { headers }).catch(() => null),
          fetch(API_ENDPOINTS.GIFT_VOUCHER_OFFERS).catch(() => null),
        ]);

        const couponsData = couponsRes ? await couponsRes.json().catch(() => null) : null;
        const mineData = mineRes ? await mineRes.json().catch(() => null) : null;
        const offersData = offersRes ? await offersRes.json().catch(() => null) : null;

        if (!alive) return;

        if (couponsData?.success && Array.isArray(couponsData.data)) {
          setCoupons(couponsData.data);
        }
        if (mineData?.success) {
          setVouchers(mineData.data || []);
          setVoucherSummary(mineData.summary || {});
        }
        if (offersData?.success) {
          setOffers(offersData.data || []);
        }
      } catch (e) {
        console.error("Coupons / vouchers load error:", e);
      } finally {
        if (alive) setLoading(false);
      }
    })();
    return () => { alive = false; };
  }, []);

  // Standardize vouchers to match ticket layout
  const normalizedVouchers = useMemo(() => {
    return vouchers.map(v => ({
      id: `v-${v.id}`,
      originalId: v.id,
      code: v.code,
      title: v.title || "Earned Gift Voucher",
      description: `Valid on any order. Use code ${v.code} at checkout.`,
      discount_format: v.voucher_type === 'percent' ? 'percentage' : 'fixed',
      discount_value: v.voucher_value,
      discount_label: v.voucher_type === 'percent' ? `${Number(v.voucher_value)}% OFF` : inr(v.voucher_value) + ' OFF',
      min_order_value: v.min_order_to_use,
      min_order_label: Number(v.min_order_to_use) > 0 ? `Orders of ${inr(v.min_order_to_use)} or more` : "On any order",
      applies_to: "All Products",
      end_date: v.expires_at,
      status: v.status,
      isVoucher: true,
      earned_order_number: v.earned_order_number,
      used_order_number: v.used_order_number,
      used_at: v.used_at,
      terms: [
        v.voucher_type === "percent" && v.max_discount ? `Up to ${inr(v.max_discount)} discount.` : "",
        Number(v.min_order_to_use) > 0 ? `Minimum order amount ${inr(v.min_order_to_use)}.` : "No minimum order requirement.",
        "One voucher per order.",
        v.earned_order_number ? `Earned on order #${v.earned_order_number}.` : ""
      ].filter(Boolean)
    }));
  }, [vouchers]);

  // Combined items based on current active tab
  const displayedItems = useMemo(() => {
    let list = [];
    const activeCoupons = coupons.filter(c => c.status === 'active');
    const expiredCoupons = coupons.filter(c => c.status !== 'active');
    const activeV = normalizedVouchers.filter(v => v.status === 'active');
    const closedV = normalizedVouchers.filter(v => v.status !== 'active');

    if (activeTab === "all_active") {
      list = [...activeCoupons, ...activeV];
    } else if (activeTab === "store_coupons") {
      list = activeCoupons;
    } else if (activeTab === "vouchers") {
      list = activeV;
    } else if (activeTab === "closed") {
      list = [...expiredCoupons, ...closedV];
    }

    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase().trim();
      list = list.filter(item => 
        (item.code && item.code.toLowerCase().includes(q)) ||
        (item.title && item.title.toLowerCase().includes(q)) ||
        (item.description && item.description.toLowerCase().includes(q)) ||
        (item.discount_label && item.discount_label.toLowerCase().includes(q))
      );
    }

    return list;
  }, [activeTab, coupons, normalizedVouchers, searchQuery]);

  const copyCode = async (code, discountLabel) => {
    try {
      await navigator.clipboard.writeText(code);
    } catch { /* clipboard fallback */ }
    setCopied(code);
    toast.success(`Coupon "${code}" copied! Paste at checkout for ${discountLabel || "discount"}.`);
    setTimeout(() => setCopied(""), 2500);
  };

  const toggleTerms = (id) => {
    setOpenTerms(prev => ({ ...prev, [id]: !prev[id] }));
  };

  // Quick Coupon Validation check
  const handleCheckCoupon = async (e) => {
    e?.preventDefault();
    const clean = checkCode.trim().toUpperCase();
    if (!clean) return;

    setCheckLoading(true);
    setCheckResult(null);

    try {
      const res = await fetch(API_ENDPOINTS.VALIDATE_COUPON, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ code: clean, items: [{ id: 1, quantity: 5 }] })
      });
      const data = await res.json().catch(() => null);

      if (data?.success && data?.coupon?.valid) {
        setCheckResult({
          valid: true,
          code: clean,
          message: `Verified! Coupon ${clean} gives instant discount.`,
          amount: data.coupon.amount
        });
      } else {
        setCheckResult({
          valid: false,
          code: clean,
          message: data?.message || "This coupon code is currently not active or invalid."
        });
      }
    } catch {
      setCheckResult({
        valid: false,
        code: clean,
        message: "Could not verify coupon. Please try again."
      });
    } finally {
      setCheckLoading(false);
    }
  };

  // Counts for tabs
  const counts = useMemo(() => {
    const activeC = coupons.filter(c => c.status === 'active').length;
    const expiredC = coupons.filter(c => c.status !== 'active').length;
    const activeV = voucherSummary.active || 0;
    const closedV = (voucherSummary.used || 0) + (voucherSummary.expired || 0) + (voucherSummary.cancelled || 0);

    return {
      all_active: activeC + activeV,
      store_coupons: activeC,
      vouchers: activeV,
      closed: expiredC + closedV
    };
  }, [coupons, voucherSummary]);

  return (
    <div className="gvp">
      {/* Hero */}
      <section className="gvp-hero">
        <div className="gvp-hero__text">
          <h1 className="gvp-hero__title">
            <FaGift /> Coupons &amp; Offers
          </h1>
          <p className="gvp-hero__sub">
            Save more on custom prints, apparel &amp; merchandise. Copy any verified coupon code and apply it in the coupon box at checkout.
          </p>
        </div>

        <div className="gvp-stats">
          <div className="gvp-stat">
            <span className="gvp-stat__num">{counts.all_active}</span>
            <span className="gvp-stat__label">Available</span>
          </div>
          <div className="gvp-stat">
            <span className="gvp-stat__num">{counts.store_coupons}</span>
            <span className="gvp-stat__label">Store Offers</span>
          </div>
          <div className="gvp-stat">
            <span className="gvp-stat__num">{counts.vouchers}</span>
            <span className="gvp-stat__label">My Vouchers</span>
          </div>
        </div>
      </section>

      {/* Quick coupon check */}
      <section className="gvp-check-card">
        <div className="row align-items-center g-3">
          <div className="col-lg-5 col-md-12">
            <div className="d-flex align-items-center gap-2">
              <span className="gvp-check-icon"><FaPercent /></span>
              <div>
                <h6 className="mb-0 fw-bold text-dark">Have a Coupon Code?</h6>
                <small className="text-muted">Enter any promotional coupon to test validity &amp; discounts.</small>
              </div>
            </div>
          </div>
          <div className="col-lg-7 col-md-12">
            <form onSubmit={handleCheckCoupon} className="d-flex gap-2">
              <div className="position-relative flex-grow-1">
                <input
                  type="text"
                  className="form-control gvp-check-input fw-bold"
                  placeholder="Enter coupon code"
                  value={checkCode}
                  onChange={(e) => {
                    setCheckCode(e.target.value.toUpperCase());
                    setCheckResult(null);
                  }}
                />
              </div>
              <button
                type="submit"
                className="btn gvp-check-btn fw-bold px-3 text-nowrap"
                disabled={checkLoading || !checkCode.trim()}
              >
                {checkLoading ? "Checking..." : "Verify Code"}
              </button>
            </form>
          </div>
        </div>

        {checkResult && (
          <div className={`mt-3 p-2 px-3 rounded-3 d-flex align-items-center justify-content-between ${checkResult.valid ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger'}`}>
            <div className="d-flex align-items-center gap-2 small fw-semibold">
              {checkResult.valid ? <FaCheckCircle /> : <FaTicketAlt />}
              <span>{checkResult.message}</span>
            </div>
            {checkResult.valid && (
              <button 
                type="button" 
                className="btn btn-sm btn-success py-1 px-3 fw-bold"
                onClick={() => copyCode(checkResult.code, "discount")}
              >
                Copy &amp; Use
              </button>
            )}
          </div>
        )}
      </section>

      {/* Main Coupons Listing Section */}
      <section className="gvp-card">
        {/* Navigation Tabs & Search */}
        <div className="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-2 border-bottom">
          <div className="gvp-tabs m-0 p-0 border-0" role="tablist">
            {TABS.map((tab) => (
              <button
                key={tab.key}
                type="button"
                role="tab"
                aria-selected={activeTab === tab.key}
                className={`gvp-tab ${activeTab === tab.key ? "is-active" : ""}`}
                onClick={() => setActiveTab(tab.key)}
              >
                {tab.label} <span className="gvp-tab__count">{counts[tab.key] || 0}</span>
              </button>
            ))}
          </div>

          <div className="gvp-search-wrap">
            <FaSearch className="gvp-search-icon" />
            <input
              type="text"
              className="gvp-search-input"
              placeholder="Search offer or code..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
            {searchQuery && (
              <button 
                type="button" 
                className="gvp-search-clear" 
                onClick={() => setSearchQuery("")}
              >
                &times;
              </button>
            )}
          </div>
        </div>

        {/* Content list */}
        {loading ? (
          <div className="gvp-empty py-5">
            <div className="spinner-border text-primary mb-2" role="status"></div>
            <div>Loading verified offers &amp; coupons…</div>
          </div>
        ) : displayedItems.length === 0 ? (
          <div className="gvp-empty py-5">
            <span className="gvp-empty__icon"><FaTicketAlt size={34} /></span>
            <div className="gvp-empty__title mt-2">
              {searchQuery ? `No offers match "${searchQuery}"` : activeTab === "closed" ? "No expired or used coupons" : "No coupons found in this tab"}
            </div>
            <p className="gvp-empty__text mt-1">
              Check back soon or explore our merchandise to find active promotional deals.
            </p>
            <Link to="/allproducts" className="gvp-btn mt-3">
              <FaShoppingBag className="me-1" /> Explore Store Products
            </Link>
          </div>
        ) : (
          <div className="gvp-list">
            {displayedItems.map((item) => {
              const isClosed = item.status !== 'active';
              const isTermsOpen = Boolean(openTerms[item.id]);

              return (
                <article key={item.id} className={`gvp-ticket ${isClosed ? 'is-closed' : ''}`}>
                  {/* Left Scalloped Value Box */}
                  <div className={`gvp-ticket__value ${isClosed ? 'is-closed-val' : ''}`}>
                    <span className="gvp-ticket__amount">
                      {item.discount_format === 'percentage' 
                        ? `${Math.round(item.discount_value)}%` 
                        : inr(item.discount_value)}
                    </span>
                    <span className="gvp-ticket__off">OFF</span>
                    <span className="gvp-ticket__type-tag">
                      {item.isVoucher ? 'VOUCHER' : 'COUPON'}
                    </span>
                  </div>

                  {/* Body Content */}
                  <div className="gvp-ticket__body">
                    <div className="gvp-ticket__top">
                      <div>
                        <div className="d-flex align-items-center gap-2 mb-1">
                          <span className="gvp-pill-badge">
                            <FaTag style={{ fontSize: "10px" }} /> {item.applies_to}
                          </span>
                          {item.days_left !== null && item.days_left !== undefined && !isClosed && (
                            <span className="gvp-exp-pill">
                              <FaClock style={{ fontSize: "10px" }} /> {item.days_left <= 0 ? 'Expires today' : `${item.days_left}d left`}
                            </span>
                          )}
                        </div>
                        <h2 className="gvp-ticket__title">{item.title}</h2>
                      </div>
                      {isClosed && (
                        <span className={`gvp-badge is-${item.status}`}>
                          {item.status}
                        </span>
                      )}
                    </div>

                    <p className="gvp-ticket__desc">
                      {item.description}
                    </p>

                    <div className="gvp-ticket__min-order">
                      {Number(item.min_order_value) > 0 ? (
                        <span>Min. Order: <strong>{inr(item.min_order_value)}</strong></span>
                      ) : (
                        <span>Applicable on any order value</span>
                      )}
                    </div>

                    {/* Code & Actions Box */}
                    {!isClosed ? (
                      <div className="gvp-actions-row">
                        <div className="gvp-code">
                          <span className="gvp-code__text">{item.code}</span>
                          <button
                            type="button"
                            className="gvp-code__btn"
                            onClick={() => copyCode(item.code, item.discount_label)}
                            title="Copy coupon code"
                          >
                            {copied === item.code ? (
                              <><FaCheck /> Copied</>
                            ) : (
                              <><FaRegCopy /> Copy</>
                            )}
                          </button>
                        </div>
                        <Link to="/allproducts" className="gvp-shop-btn text-decoration-none">
                          Shop Now <FaArrowRight style={{ fontSize: "10px" }} />
                        </Link>
                      </div>
                    ) : (
                      <div className="gvp-code is-muted">
                        <span className="gvp-code__text">{item.code}</span>
                        <span className="small text-muted text-uppercase">{item.status}</span>
                      </div>
                    )}

                    {/* Metadata / Validity */}
                    <div className="gvp-ticket__meta">
                      {item.status === "used" ? (
                        <span>Used{item.used_order_number ? ` on order #${item.used_order_number}` : ""}{item.used_at ? ` · ${day(item.used_at)}` : ""}</span>
                      ) : item.status === "cancelled" ? (
                        <span>Cancelled because order #{item.earned_order_number} was cancelled</span>
                      ) : item.end_date ? (
                        <span>Valid till {day(item.end_date)}</span>
                      ) : (
                        <span>Limited time platform offer</span>
                      )}

                      {/* Terms Toggle */}
                      {Array.isArray(item.terms) && item.terms.length > 0 && (
                        <button
                          type="button"
                          className="gvp-terms-toggle"
                          onClick={() => toggleTerms(item.id)}
                        >
                          {isTermsOpen ? <>Hide T&amp;C <FaChevronUp /></> : <>View T&amp;C <FaChevronDown /></>}
                        </button>
                      )}
                    </div>

                    {/* Expandable Terms Accordion */}
                    {isTermsOpen && Array.isArray(item.terms) && (
                      <div className="gvp-terms-box">
                        <div className="gvp-terms-title">Terms &amp; Conditions:</div>
                        <ul className="gvp-terms-list">
                          {item.terms.map((t, i) => (
                            <li key={i}>{t}</li>
                          ))}
                        </ul>
                      </div>
                    )}
                  </div>
                </article>
              );
            })}
          </div>
        )}
      </section>

      {/* Ways to Earn Gift Vouchers Section (Preserved) */}
      {offers.length > 0 && (
        <section className="gvp-card">
          <h2 className="gvp-h2 d-flex align-items-center gap-2">
            <FaGift className="text-primary" /> Ways to earn more gift vouchers
          </h2>
          <div className="gvp-offers">
            {offers.map((o) => (
              <div key={o.id} className="gvp-offer">
                <span className="gvp-offer__icon"><FaGift /></span>
                <div className="gvp-offer__text">
                  <div className="gvp-offer__title">
                    Buy {inr(o.min_spend)}+ {o.applies_to === "all" ? "of any products" : "of eligible products"} → get a <b>{o.value_label}</b> voucher
                  </div>
                  <div className="gvp-offer__sub">
                    {o.title} · valid {o.validity_days} days
                    {o.min_order_to_use > 0 ? ` · use on orders of ${inr(o.min_order_to_use)}+` : ""}
                  </div>
                </div>
              </div>
            ))}
          </div>
          <p className="gvp-note">Eligible products display a gift voucher offer banner on their product page.</p>
        </section>
      )}

      {/* How it works */}
      <section className="gvp-card">
        <h2 className="gvp-h2">How to use coupons on Printmont</h2>
        <div className="row g-3 gvp-steps-row">
          <div className="col-md-4">
            <div className="gvp-step-card">
              <span className="gvp-step-num">1</span>
              <div>
                <strong>Find &amp; Copy Code</strong>
                <p className="small text-muted mb-0">Browse through available offers above and tap "Copy" on your desired coupon.</p>
              </div>
            </div>
          </div>
          <div className="col-md-4">
            <div className="gvp-step-card">
              <span className="gvp-step-num">2</span>
              <div>
                <strong>Add Eligible Items</strong>
                <p className="small text-muted mb-0">Add custom prints and products meeting the minimum order amount to your cart.</p>
              </div>
            </div>
          </div>
          <div className="col-md-4">
            <div className="gvp-step-card">
              <span className="gvp-step-num">3</span>
              <div>
                <strong>Apply at Checkout</strong>
                <p className="small text-muted mb-0">Paste the code into the "Have a Discount Coupon?" box for instant price reduction.</p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default GiftCard;
