import React, { useState, useEffect } from 'react';
import { IoSearch } from "react-icons/io5";
import { BsBagX } from "react-icons/bs";
import { Spinner, Alert } from 'react-bootstrap';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { API_ENDPOINTS, resolveImageUrl } from '../../config/apiEndpoints';
import './orders.css';
/**
 * Order statuses, keyed by the value the backend actually stores.
 *
 * The old list read 'On the way', 'Delivered', 'Cancelled', 'Returned' — labels
 * that matched no column value, so even once wired up they would have filtered
 * nothing. These keys mirror the enum used by the admin order screens.
 */
const STATUS_FILTERS = [
  { value: 'pending', label: 'Pending' },
  { value: 'processing', label: 'Processing' },
  { value: 'shipped', label: 'Shipped' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'refunded', label: 'Refunded' },
];

/**
 * Time ranges resolved to real dates at request time.
 *
 * The years used to be hardcoded ('2024', '2023', '2022', '2021'), which both
 * went stale and offered ranges the shopper may have no orders in. These are
 * computed from the current date, and the year options are derived from the
 * orders themselves.
 */
const TIME_FILTERS = [
  { value: 'last30', label: 'Last 30 days' },
  { value: 'last6m', label: 'Last 6 months' },
  { value: 'thisyear', label: 'This year' },
];

const pad = (n) => String(n).padStart(2, '0');
const toSqlDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

/** Turn a time selection into the date_from / date_to the API expects. */
const resolveTimeRange = (value) => {
  const now = new Date();

  if (value === 'last30') {
    const from = new Date(now);
    from.setDate(from.getDate() - 30);
    return { date_from: toSqlDate(from), date_to: toSqlDate(now) };
  }
  if (value === 'last6m') {
    const from = new Date(now);
    from.setMonth(from.getMonth() - 6);
    return { date_from: toSqlDate(from), date_to: toSqlDate(now) };
  }
  if (value === 'thisyear') {
    return { date_from: `${now.getFullYear()}-01-01`, date_to: toSqlDate(now) };
  }
  // A bare year, e.g. "2025".
  if (/^\d{4}$/.test(value)) {
    return { date_from: `${value}-01-01`, date_to: `${value}-12-31` };
  }
  return {};
};

const STATUS_COLOR = {
  delivered: '#198754',
  shipped: '#0b53a1',
  processing: '#fd7e14',
  pending: '#fd7e14',
  cancelled: '#dc3545',
  refunded: '#6c757d',
};

const OrderCard = ({ order }) => {
  const items = Array.isArray(order.items) ? order.items : [];
  const first = items[0] || {};

  // The list endpoint returns order-level rows plus their line items. Fall back
  // through the older field names so an order without items still renders.
  const title = first.product_name || order.title || order.product_name || `Order ${order.order_number || `#${order.id}`}`;
  const image = first.product_image
    ? resolveImageUrl(first.product_image)
    : (order.image || order.product_image || '/default-img.jpg');

  // grand_total is what the customer actually paid; total_amount is the
  // pre-shipping subtotal and was previously shown here instead.
  const price = Number(order.grand_total ?? order.total_amount ?? order.price ?? 0);

  const status = (order.status || order.order_status || 'pending').toLowerCase();
  const tone = STATUS_COLOR[status] || '#fd7e14';
  const extraCount = items.length - 1;

  return (
    <div className="card mb-3 p-3">
      <div className="row g-3 align-items-center">
        <div className="col-auto">
          <img
            src={image}
            alt={title}
            style={{ width: '70px', height: '70px', objectFit: 'contain' }}
            onError={(e) => { e.target.src = '/default-img.jpg'; }}
          />
        </div>

        <div className="col">
          <div className="fw-semibold text-truncate" style={{ maxWidth: '320px' }}>{title}</div>
          {extraCount > 0 && (
            <div className="text-muted" style={{ fontSize: '0.8rem' }}>
              + {extraCount} more item{extraCount > 1 ? 's' : ''}
            </div>
          )}
          <div className="text-muted" style={{ fontSize: '0.8rem' }}>
            Order ID: <span className="fw-semibold text-dark">{order.order_number || `#${order.id}`}</span>
          </div>
          {order.tracking_number && (
            <div className="text-muted" style={{ fontSize: '0.8rem' }}>
              Tracking ID: <span className="fw-semibold text-dark">{order.tracking_number}</span>
            </div>
          )}
        </div>

        <div className="col-auto fw-semibold">₹{price.toLocaleString('en-IN')}</div>

        <div className="col-auto text-end">
          <span className="me-1" style={{ fontSize: '1.2em', color: tone }}>●</span>
          <span className="fw-bold text-capitalize" style={{ color: tone }}>{status}</span>
          {order.tracking_number && (
            <div className="mt-1">
              <Link
                to={`/track-order?order=${encodeURIComponent(order.order_number || '')}`}
                className="text-primary text-decoration-none"
                style={{ fontWeight: 600, fontSize: '0.85rem' }}
              >
                Track this order →
              </Link>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

const Orders = () => {
  const { user, token } = useAuth();
  const navigate = useNavigate();
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [sessionExpired, setSessionExpired] = useState(false);

  const [searchTerm, setSearchTerm] = useState('');
  const [showFilterModal, setShowFilterModal] = useState(false);
  // time is single-select: two date ranges at once cannot both apply.
  const [selectedFilters, setSelectedFilters] = useState({ status: [], time: '' });
  // Remembers whether the account has any orders at all, so selecting a filter
  // that matches none still shows the filter UI instead of the "no orders" state.
  const [everHadOrders, setEverHadOrders] = useState(false);
  const [yearOptions, setYearOptions] = useState([]);

  const statusKey = selectedFilters.status.join(',');

  useEffect(() => {
    if (!user?.id) {
      setLoading(false);
      return;
    }

    let cancelled = false;

    const fetchOrders = async () => {
      try {
        setLoading(true);
        setError(null);

        // Everything is filtered server-side. getOrdersForUser already accepts
        // status / date_from / date_to / search — the UI simply never sent them.
        const params = {
          ...resolveTimeRange(selectedFilters.time),
          status: statusKey,
          search: searchTerm.trim(),
          // There is no pagination UI, and the API defaults to 10 — which
          // silently hid orders 11+ from anyone with a longer history.
          limit: 200,
        };

        const res = await fetch(API_ENDPOINTS.GET_CUSTOMER_ORDERS(params), {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await res.json();
        if (cancelled) return;

        if (data.success || data.status === 'success') {
          const rows = data.data || data.orders || [];
          setOrders(rows);

          const filtersActive = statusKey !== '' || selectedFilters.time !== '' || searchTerm.trim() !== '';
          if (rows.length > 0) {
            setEverHadOrders(true);
            // Offer only years the shopper actually has orders in.
            if (!filtersActive) {
              const years = [...new Set(
                rows.map(o => String(o.created_at || '').slice(0, 4)).filter(y => /^\d{4}$/.test(y))
              )].sort((a, b) => b.localeCompare(a));
              setYearOptions(years);
            }
          }
        } else {
          // A 401 means the stored token has expired or is invalid, which is
          // by far the most common failure here. The API reports it in
          // `error`, not `message`, so reading only `message` always fell
          // through to the generic text and hid the real cause.
          if (res.status === 401) {
            setSessionExpired(true);
            setError("Your session has expired. Please sign in again to see your orders.");
          } else {
            setError(data.error || data.message || "Failed to load orders.");
          }
        }
      } catch (err) {
        if (cancelled) return;
        console.error(err);
        setError("An error occurred while fetching orders.");
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    // Debounced so typing in the search box does not fire a request per keystroke.
    const timer = setTimeout(fetchOrders, searchTerm ? 350 : 0);
    return () => { cancelled = true; clearTimeout(timer); };
  }, [user, token, statusKey, selectedFilters.time, searchTerm]);

  const handleFilterToggle = (type, value) => {
    setSelectedFilters((prev) => {
      if (type === 'time') {
        // Clicking the selected range again clears it.
        return { ...prev, time: prev.time === value ? '' : value };
      }
      const alreadySelected = prev.status.includes(value);
      return {
        ...prev,
        status: alreadySelected
          ? prev.status.filter(item => item !== value)
          : [...prev.status, value],
      };
    });
  };

  const clearFilters = () => {
    setSelectedFilters({ status: [], time: '' });
    setSearchTerm('');
  };

  const filtersActive = statusKey !== '' || selectedFilters.time !== '' || searchTerm.trim() !== '';

  // The server has already applied every filter.
  const filteredOrders = orders;

  // Filters and search only make sense once there is something to filter.
  // Keyed off everHadOrders, not the current result set: a filter that matches
  // nothing must still leave the controls on screen, or the shopper is stranded
  // with no way to undo the selection that emptied the page.
  const hasOrders = orders.length > 0 || everHadOrders;

  // Time options: fixed ranges, plus whichever years this account has orders in.
  const timeOptions = [
    ...TIME_FILTERS,
    ...yearOptions
      .filter(y => y !== String(new Date().getFullYear()))
      .map(y => ({ value: y, label: y })),
  ];

  return (
    <div className="container-fluid pt-3 custom-bg">
      <nav aria-label="breadcrumb" className="mb-3">
        <ol className="breadcrumb">
          <li className="breadcrumb-item"><a href="/">Home</a></li>
          <li className="breadcrumb-item"><a href="/user/profile">My Account</a></li>
          <li className="breadcrumb-item active" aria-current="page">My Orders</li>
        </ol>
      </nav>

      {/* Mobile Filter Button */}
      {hasOrders && (
        <div className="d-md-none d-flex justify-content-end mb-2">
          <button className="btn btn-outline-secondary btn-sm" onClick={() => setShowFilterModal(true)}>
            Filters
          </button>
        </div>
      )}

      <div className="row">
        {/* Desktop Sidebar Filters */}
        {hasOrders && (
        <aside className="col-md-3 d-none d-md-block mb-4">
          <div className="border rounded p-3 bg-white">
            <div className="d-flex justify-content-between align-items-center">
              <h5 className="mb-0">Filters</h5>
              {filtersActive && (
                <button className="btn btn-link btn-sm p-0 text-decoration-none" onClick={clearFilters}>
                  Clear all
                </button>
              )}
            </div>
            <hr />
            <div>
              <h6>ORDER STATUS</h6>
              {STATUS_FILTERS.map(({ value, label }) => (
                <div className="form-check" key={value}>
                  <input
                    className="form-check-input"
                    type="checkbox"
                    id={`status-${value}`}
                    checked={selectedFilters.status.includes(value)}
                    onChange={() => handleFilterToggle('status', value)}
                  />
                  <label className="form-check-label" htmlFor={`status-${value}`}>{label}</label>
                </div>
              ))}
            </div>
            <hr />
            <div>
              <h6>ORDER TIME</h6>
              {timeOptions.map(({ value, label }) => (
                <div className="form-check" key={value}>
                  {/* Radio: two date ranges cannot both apply. */}
                  <input
                    className="form-check-input"
                    type="radio"
                    name="order-time"
                    id={`time-${value}`}
                    checked={selectedFilters.time === value}
                    onChange={() => handleFilterToggle('time', value)}
                    onClick={() => handleFilterToggle('time', value)}
                  />
                  <label className="form-check-label" htmlFor={`time-${value}`}>{label}</label>
                </div>
              ))}
            </div>
          </div>
        </aside>
        )}

        {/* Main Orders Area — spans the full row once the sidebar is hidden. */}
        <main className={hasOrders ? 'col-md-9' : 'col-12'}>
          {hasOrders && (
            <form className="d-flex mb-3" onSubmit={(e) => e.preventDefault()}>
              <div className='d-flex w-100 justify-content-center align-items-center border border-sm-0 bg-white border-bd rounded'>
                <IoSearch className='text-muted ms-2 d-flex d-lg-none' size={25}/>

                <input
                  type="text"
                  className="form-control  px-2 rounded-0 rounded-start border-0"
                  placeholder="Search your orders here"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
                <div className='d-none d-sm-none d-lg-flex'>
                  <button className="btn btn-primary rounded-0 rounded-end px-3 text-nowrap d-flex align-items-center justify-content-center gap-1" type="submit">
                    <IoSearch size={18}/> <span>Search Orders</span>
                  </button>
                </div>
              </div>
            </form>
          )}

          {loading ? (
            <div className="text-center p-5">
              <Spinner animation="border" variant="primary" />
            </div>
          ) : error ? (
            <Alert variant={sessionExpired ? "warning" : "danger"}>
              <div>{error}</div>
              {sessionExpired && (
                <button
                  className="btn btn-sm btn-primary mt-2"
                  onClick={() => navigate("/login?redirect=/orders")}
                >
                  Sign in again
                </button>
              )}
            </Alert>
          ) : !user?.id ? (
            <Alert variant="warning">Please log in to view your orders.</Alert>
          ) : filteredOrders.length ? (
            filteredOrders.map((order, idx) => (
              <OrderCard key={order.id || idx} order={order} />
            ))
          ) : orders.length ? (
            /* There are orders, the search just did not match any of them —
               offering "start shopping" here would be the wrong exit. */
            <div className="orders-empty bg-white text-center p-5 my-3 shadow-sm rounded">
              <IoSearch size={44} className="text-secondary mb-3" />
              <h5 className="fw-bold text-dark mb-2">No matching orders</h5>
              <p className="text-muted mb-4">
                Nothing matched &ldquo;{searchTerm}&rdquo;. Try a different search.
              </p>
              <button className="btn btn-outline-secondary" onClick={() => setSearchTerm('')}>
                Clear search
              </button>
            </div>
          ) : (
            <div className="orders-empty bg-white text-center p-5 my-3 shadow-sm rounded">
              <BsBagX size={44} className="text-secondary mb-3" />
              <h5 className="fw-bold text-dark mb-2">No Orders Yet</h5>
              <p className="text-muted mb-4">
                You haven&rsquo;t placed any orders. Start shopping and they will show up here.
              </p>
              <button className="btn checkout-cta" onClick={() => navigate('/allproducts')}>
                Start Shopping
              </button>
            </div>
          )}
        </main>
      </div>

      {/* Filter Modal for Mobile */}
      {showFilterModal && (
        <div className="filter-modal-overlay" onClick={() => setShowFilterModal(false)}>
          <div className="filter-modal bg-white p-4 rounded-top" onClick={(e) => e.stopPropagation()}>
            <div className="d-flex justify-content-between align-items-center mb-3">
              <h5 className="mb-0">Filters</h5>
              <button className="btn btn-link text-muted p-0" onClick={clearFilters}>Clear Filter</button>
            </div>

            <div className="mb-3">
              <h6>Order Status</h6>
              <div className="d-flex flex-wrap gap-2">
                {STATUS_FILTERS.map(({ value, label }) => {
                  const on = selectedFilters.status.includes(value);
                  return (
                    <button
                      key={value}
                      className={`filter-pill ${on ? 'selected' : ''}`}
                      onClick={() => handleFilterToggle('status', value)}
                    >
                      {label} <span>{on ? '×' : '+'}</span>
                    </button>
                  );
                })}
              </div>
            </div>

            <div className="mb-4">
              <h6>Order Time</h6>
              <div className="d-flex flex-wrap gap-2">
                {timeOptions.map(({ value, label }) => {
                  const on = selectedFilters.time === value;
                  return (
                    <button
                      key={value}
                      className={`filter-pill ${on ? 'selected' : ''}`}
                      onClick={() => handleFilterToggle('time', value)}
                    >
                      {label} <span>{on ? '×' : '+'}</span>
                    </button>
                  );
                })}
              </div>
            </div>

            <div className="d-flex justify-content-between">
              <button className="btn btn-outline-secondary w-45" onClick={() => setShowFilterModal(false)}>
                Cancel
              </button>
              {/* Filters apply as they are picked, so this just closes the sheet. */}
              <button className="btn btn-warning w-45" onClick={() => setShowFilterModal(false)}>
                Done
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Orders;
