import React, { useState, useEffect, useCallback } from "react";
import { useNavigate, Link } from "react-router-dom";
import { Spinner } from "react-bootstrap";
import { toast } from "react-hot-toast";
import {
  FaBell,
  FaBoxOpen,
  FaTags,
  FaUserCircle,
  FaCoins,
  FaCheckDouble,
  FaRegBellSlash,
  FaChevronRight
} from "react-icons/fa";
import { useAuth } from "../../context/AuthContext";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import { updatePageSEO } from "../../utils/seo";
import "./notify.css";

const CATEGORY_META = {
  orders: { label: "Order updates", filter: "Orders", icon: FaBoxOpen, desc: "Order confirmed, shipped, delivered, refunds and returns." },
  offers: { label: "Offers & sales", filter: "Offers & Sales", icon: FaTags, desc: "New sales, coupons, deals and festive offers." },
  account: { label: "Account", filter: "Account", icon: FaUserCircle, desc: "Profile, password and security alerts." },
  rewards: { label: "Rewards & coins", filter: "Rewards", icon: FaCoins, desc: "Printmont Coins earned, redeemed or expiring." }
};

const CHANNELS = [
  { key: "inapp", label: "In-app" },
  { key: "email", label: "Email" },
  { key: "sms", label: "SMS" },
  { key: "whatsapp", label: "WhatsApp" }
];

const FILTERS = [{ key: "all", label: "All" }, ...Object.entries(CATEGORY_META).map(([key, m]) => ({ key, label: m.filter }))];

const timeAgo = (value) => {
  if (!value) return "";
  const then = new Date(String(value).replace(" ", "T"));
  const diff = Math.max(0, Math.floor((Date.now() - then.getTime()) / 1000));
  if (diff < 60) return "Just now";
  if (diff < 3600) return `${Math.floor(diff / 60)} min ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)} hr ago`;
  if (diff < 7 * 86400) return `${Math.floor(diff / 86400)} day${Math.floor(diff / 86400) > 1 ? "s" : ""} ago`;
  return then.toLocaleDateString("en-IN", { day: "numeric", month: "short", year: "numeric" });
};

const NotificationPreferences = () => {
  const { user, token } = useAuth();
  const navigate = useNavigate();

  const [tab, setTab] = useState("inbox");
  const [filter, setFilter] = useState("all");
  const [items, setItems] = useState([]);
  const [unread, setUnread] = useState(0);
  const [hasMore, setHasMore] = useState(false);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);

  const [prefs, setPrefs] = useState(null);
  const [prefsDirty, setPrefsDirty] = useState(false);
  const [savingPrefs, setSavingPrefs] = useState(false);

  // Login required: this page only shows a customer's own notifications.
  useEffect(() => {
    if (!user) {
      navigate(`/login?redirect=${encodeURIComponent("/notification-preference")}`, { replace: true });
    }
  }, [user, navigate]);

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: "Notifications | Printmont",
      description: "Your order updates, new sales and offers, and notification preferences.",
      type: "website"
    });
  }, []);

  const api = useCallback(
    async (query = "", options = {}) => {
      const res = await fetch(`${API_ENDPOINTS.CUSTOMER_NOTIFICATIONS}${query}`, {
        cache: "no-store",
        ...options,
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
          ...(options.headers || {})
        }
      });
      return res.json();
    },
    [token]
  );

  const loadList = useCallback(
    async (type, offset = 0) => {
      const data = await api(`?action=list&type=${type}&limit=20&offset=${offset}`);
      if (!data?.success) throw new Error(data?.error || "Could not load notifications");
      return data;
    },
    [api]
  );

  // Inbox (reloaded whenever the filter changes or the tab is opened, so preference changes show up)
  useEffect(() => {
    if (!user || !token || tab !== "inbox") return undefined;
    let cancelled = false;
    setLoading(true);
    loadList(filter)
      .then((data) => {
        if (cancelled) return;
        setItems(data.notifications);
        setUnread(data.unread_count);
        setHasMore(data.has_more);
      })
      .catch(() => {
        if (!cancelled) toast.error("Could not load your notifications.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [user, token, filter, tab, loadList]);

  // Preferences (loaded once when the tab is first opened)
  useEffect(() => {
    if (tab !== "prefs" || prefs || !token) return;
    api("?action=prefs")
      .then((data) => {
        if (data?.success) setPrefs(data.prefs);
        else toast.error("Could not load your preferences.");
      })
      .catch(() => toast.error("Could not load your preferences."));
  }, [tab, prefs, token, api]);

  const loadMore = async () => {
    try {
      setLoadingMore(true);
      const data = await loadList(filter, items.length);
      setItems((prev) => [...prev, ...data.notifications]);
      setHasMore(data.has_more);
    } catch {
      toast.error("Could not load more notifications.");
    } finally {
      setLoadingMore(false);
    }
  };

  const openNotification = async (item) => {
    if (!item.is_read) {
      setItems((prev) => prev.map((n) => (n.id === item.id ? { ...n, is_read: true } : n)));
      setUnread((c) => Math.max(0, c - 1));
      api("", { method: "POST", body: JSON.stringify({ action: "mark_read", id: item.id }) }).catch(() => {});
    }
    if (item.link) navigate(item.link);
  };

  const markAllRead = async () => {
    try {
      const data = await api("", { method: "POST", body: JSON.stringify({ action: "mark_all_read" }) });
      if (!data?.success) throw new Error();
      setItems((prev) => prev.map((n) => ({ ...n, is_read: true })));
      setUnread(0);
    } catch {
      toast.error("Could not update notifications. Please try again.");
    }
  };

  const togglePref = (category, channel) => {
    setPrefs((prev) => ({ ...prev, [category]: { ...prev[category], [channel]: !prev[category][channel] } }));
    setPrefsDirty(true);
  };

  const savePrefs = async () => {
    try {
      setSavingPrefs(true);
      const data = await api("", { method: "POST", body: JSON.stringify({ action: "save_prefs", prefs }) });
      if (!data?.success) throw new Error(data?.error);
      setPrefs(data.prefs);
      setPrefsDirty(false);
      toast.success("Notification preferences saved.");
    } catch {
      toast.error("Could not save your preferences. Please try again.");
    } finally {
      setSavingPrefs(false);
    }
  };

  if (!user) return null;

  return (
    <div className="np-page">
      <div className="np-container">
        <header className="np-head">
          <div>
            <h1 className="np-title">
              <FaBell /> Notifications
            </h1>
            <p className="np-sub">Order updates, new sales and offers — everything in one place.</p>
          </div>
          {tab === "inbox" && unread > 0 && (
            <button type="button" className="np-btn-outline" onClick={markAllRead}>
              <FaCheckDouble /> Mark all as read
            </button>
          )}
        </header>

        <div className="np-tabs" role="tablist">
          <button type="button" role="tab" aria-selected={tab === "inbox"} className={`np-tab ${tab === "inbox" ? "is-active" : ""}`} onClick={() => setTab("inbox")}>
            Notifications {unread > 0 && <span className="np-count">{unread}</span>}
          </button>
          <button type="button" role="tab" aria-selected={tab === "prefs"} className={`np-tab ${tab === "prefs" ? "is-active" : ""}`} onClick={() => setTab("prefs")}>
            Preferences
          </button>
        </div>

        {tab === "inbox" && (
          <section className="np-card">
            <div className="np-filters">
              {FILTERS.map((f) => (
                <button key={f.key} type="button" className={`np-chip ${filter === f.key ? "is-active" : ""}`} onClick={() => setFilter(f.key)}>
                  {f.label}
                </button>
              ))}
            </div>

            {loading ? (
              <div className="np-empty">
                <Spinner animation="border" size="sm" /> <span>Loading notifications…</span>
              </div>
            ) : items.length === 0 ? (
              <div className="np-empty">
                <span className="np-empty-icon"><FaRegBellSlash /></span>
                <h3>You're all caught up</h3>
                <p>New sales, order updates and offers will show up here.</p>
                <Link to="/allproducts" className="np-btn">Start shopping</Link>
              </div>
            ) : (
              <>
                <ul className="np-list">
                  {items.map((n) => {
                    const meta = CATEGORY_META[n.category] || CATEGORY_META.offers;
                    const Icon = meta.icon;
                    return (
                      <li key={n.id}>
                        <button type="button" className={`np-item ${n.is_read ? "" : "is-unread"}`} onClick={() => openNotification(n)}>
                          <span className={`np-item-icon ${n.category}`}><Icon /></span>
                          <span className="np-item-body">
                            <span className="np-item-title">{n.title}</span>
                            <span className="np-item-msg">{n.message}</span>
                            <span className="np-item-meta">
                              {meta.filter} &middot; {timeAgo(n.created_at)}
                            </span>
                          </span>
                          {!n.is_read && <span className="np-dot" aria-label="Unread" />}
                          {n.link && <FaChevronRight className="np-item-arrow" />}
                        </button>
                      </li>
                    );
                  })}
                </ul>
                {hasMore && (
                  <div className="np-more">
                    <button type="button" className="np-btn-outline" onClick={loadMore} disabled={loadingMore}>
                      {loadingMore ? <Spinner animation="border" size="sm" /> : "Load more"}
                    </button>
                  </div>
                )}
              </>
            )}
          </section>
        )}

        {tab === "prefs" && (
          <section className="np-card">
            <h2 className="np-card-title">Choose how you want to hear from us</h2>
            <p className="np-card-sub">
              Turn on the channels you prefer for each type of notification. Switching off <strong>In-app</strong> hides that type from your notification list.
            </p>

            {!prefs ? (
              <div className="np-empty">
                <Spinner animation="border" size="sm" /> <span>Loading preferences…</span>
              </div>
            ) : (
              <>
                <div className="np-prefs">
                  <div className="np-prefs-head">
                    <span />
                    {CHANNELS.map((c) => (
                      <span key={c.key}>{c.label}</span>
                    ))}
                  </div>

                  {Object.entries(CATEGORY_META).map(([cat, meta]) => {
                    const Icon = meta.icon;
                    return (
                      <div className="np-prefs-row" key={cat}>
                        <div className="np-prefs-info">
                          <span className={`np-item-icon ${cat}`}><Icon /></span>
                          <span>
                            <span className="np-item-title">{meta.label}</span>
                            <span className="np-item-msg">{meta.desc}</span>
                          </span>
                        </div>
                        {CHANNELS.map((c) => (
                          <label className="np-switch" key={c.key}>
                            <span className="np-switch-label">{c.label}</span>
                            <input
                              type="checkbox"
                              checked={!!prefs[cat]?.[c.key]}
                              onChange={() => togglePref(cat, c.key)}
                              aria-label={`${meta.label} via ${c.label}`}
                            />
                            <span className="np-switch-ui" />
                          </label>
                        ))}
                      </div>
                    );
                  })}
                </div>

                <div className="np-save">
                  <button type="button" className="np-btn" onClick={savePrefs} disabled={!prefsDirty || savingPrefs}>
                    {savingPrefs ? <Spinner animation="border" size="sm" /> : "Save preferences"}
                  </button>
                </div>
              </>
            )}
          </section>
        )}
      </div>
    </div>
  );
};

export default NotificationPreferences;
