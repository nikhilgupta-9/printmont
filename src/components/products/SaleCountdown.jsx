import React, { useEffect, useState } from "react";
import { FaBolt } from "react-icons/fa";
import "./SaleCountdown.css";

// Countdown for a live timed sale (admin: product-sales.php), shown above the product name.
// `sale.ends_at` is epoch seconds from the product API. Calls onEnd once when time runs out.
const pad = (n) => String(n).padStart(2, "0");

const SaleCountdown = ({ sale, onEnd, compact = false }) => {
  const endsAt = Number(sale?.ends_at) * 1000;
  const [left, setLeft] = useState(() => Math.max(0, endsAt - Date.now()));

  useEffect(() => {
    if (!endsAt) return undefined;
    let ended = false;
    const tick = () => {
      const ms = Math.max(0, endsAt - Date.now());
      setLeft(ms);
      if (ms === 0 && !ended) {
        ended = true;
        if (onEnd) onEnd();
      }
    };
    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
    // onEnd is called at most once per sale end time
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [endsAt]);

  if (!sale || !endsAt || left <= 0) return null;

  let s = Math.floor(left / 1000);
  const days = Math.floor(s / 86400); s %= 86400;
  const hours = Math.floor(s / 3600); s %= 3600;
  const mins = Math.floor(s / 60);
  const secs = s % 60;
  const units = [
    ...(days > 0 ? [[days, "Days"]] : []),
    [hours, "Hrs"],
    [mins, "Min"],
    [secs, "Sec"],
  ];

  return (
    <div className={`sale-cd ${compact ? "sale-cd--compact" : ""}`} role="timer" aria-live="off">
      <div className="sale-cd__label">
        <FaBolt />
        <span>{sale.label || "Flash Sale"}</span>
      </div>
      <div className="sale-cd__right">
        <span className="sale-cd__ends">Ends in</span>
        <div className="sale-cd__units">
          {units.map(([value, unit], i) => (
            <React.Fragment key={unit}>
              {i > 0 && <span className="sale-cd__sep">:</span>}
              <span className="sale-cd__unit">
                <b>{pad(value)}</b>
                <small>{unit}</small>
              </span>
            </React.Fragment>
          ))}
        </div>
      </div>
    </div>
  );
};

export default SaleCountdown;
