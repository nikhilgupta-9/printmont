import React, { useEffect, useState } from "react";
import { FaGift } from "react-icons/fa";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import "./GiftVoucherOffer.css";

const inr = (n) => `₹${Math.round(Number(n) || 0).toLocaleString("en-IN")}`;

// "Buy ₹X+ → get a ₹Y gift voucher" for products in an admin voucher rule (gift-vouchers.php).
// Renders nothing when the product earns no voucher.
const GiftVoucherOffer = ({ productId, compact = false }) => {
  const [offers, setOffers] = useState([]);

  useEffect(() => {
    if (!productId) return undefined;
    let alive = true;
    fetch(API_ENDPOINTS.GIFT_VOUCHER_PRODUCT(productId))
      .then((r) => r.json())
      .then((d) => { if (alive && d?.success) setOffers(d.data || []); })
      .catch(() => {});
    return () => { alive = false; };
  }, [productId]);

  if (!offers.length) return null;

  return (
    <div className={`gvo ${compact ? "gvo--compact" : ""}`}>
      {offers.map((o) => (
        <div key={o.id} className="gvo-row">
          <span className="gvo-icon"><FaGift /></span>
          <span className="gvo-text">
            Buy {inr(o.min_spend)}+ and get a <b>{o.value_label}</b> gift voucher
            <span className="gvo-sub">
              {o.min_order_to_use > 0 ? `Use on your next order of ${inr(o.min_order_to_use)}+` : "Use on your next order"} · valid {o.validity_days} days
            </span>
          </span>
        </div>
      ))}
    </div>
  );
};

export default GiftVoucherOffer;
