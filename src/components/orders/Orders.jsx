import React, { useState, useEffect } from 'react';
import { IoSearch } from "react-icons/io5";
import { BsBagX } from "react-icons/bs";
import { Spinner, Alert } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import './orders.css';
const filterOptions = {
  status: ['On the way', 'Delivered', 'Cancelled', 'Returned'],
  time: ['Last 30 days', '2024', '2023', '2022', '2021', 'Older']
};

const OrderCard = ({ order }) => {
  const title = order.title || order.product_name || `Order #${order.id || order.order_id}`;
  const image = order.image || order.product_image || 'https://placehold.co/70x70?text=Order';
  const price = order.price || order.total_amount || order.total || '0';
  const status = order.status || order.order_status || 'Processing';

  return (
    <div className="card mb-3 p-2">
      <div className="row g-2 align-items-center">
        <div className="col-auto">
          <img src={image} alt={title} style={{ width: '70px', height: '70px', objectFit: 'contain' }} />
        </div>
        <div className="col">
          <div className="fw-semibold text-truncate" style={{ maxWidth: '250px' }}>{title}</div>
          {(order.color || order.size) && (
              <small className="text-muted">
                  {order.color && `Color: ${order.color}`} 
                  {order.color && order.size && <>&nbsp;&nbsp;</>}
                  {order.size && `Size: ${order.size}`}
              </small>
          )}
          <div className="text-muted" style={{ fontSize: '0.8rem' }}>Order ID: #{order.id || order.order_id}</div>
        </div>
        <div className="col-auto fw-semibold">₹{price}</div>
        <div className="col-auto">
          <span className="me-1" style={{ fontSize: '1.2em', color: status.toLowerCase() === 'delivered' ? 'green' : (status.toLowerCase() === 'cancelled' ? 'red' : 'orange') }}>●</span>
          <span className="fw-bold" style={{ color: status.toLowerCase() === 'delivered' ? 'green' : (status.toLowerCase() === 'cancelled' ? 'red' : 'orange') }}>
            {status} {order.deliveryDate ? `on ${order.deliveryDate}` : ''}
          </span>
          <div className="mt-1">
            <a href="#" className="text-primary" style={{ fontWeight: '600', fontSize: '0.85rem' }}>
              <span style={{ marginRight: '5px' }}>★</span> Rate & Review Product
            </a>
          </div>
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

  const [searchTerm, setSearchTerm] = useState('');
  const [showFilterModal, setShowFilterModal] = useState(false);
  const [selectedFilters, setSelectedFilters] = useState({ status: [], time: [] });

  useEffect(() => {
    const fetchOrders = async () => {
      if (!user?.id) {
        setLoading(false);
        return;
      }
      try {
        setLoading(true);
        const res = await fetch(API_ENDPOINTS.GET_CUSTOMER_ORDERS(user.id), {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await res.json();
        
        if (data.success || data.status === 'success') {
          setOrders(data.data || data.orders || []);
        } else {
          setError(data.message || "Failed to load orders.");
        }
      } catch (err) {
        console.error(err);
        setError("An error occurred while fetching orders.");
      } finally {
        setLoading(false);
      }
    };

    fetchOrders();
  }, [user]);

  const handleFilterToggle = (type, value) => {
    setSelectedFilters((prev) => {
      const alreadySelected = prev[type].includes(value);
      return {
        ...prev,
        [type]: alreadySelected
          ? prev[type].filter(item => item !== value)
          : [...prev[type], value]
      };
    });
  };

  const clearFilters = () => {
    setSelectedFilters({ status: [], time: [] });
  };

  const filteredOrders = orders.filter(order => {
    const searchString = (order.title || order.product_name || `Order #${order.id || order.order_id}` || '').toLowerCase();
    return searchString.includes(searchTerm.toLowerCase());
  });

  // Filters and search only make sense once there is something to filter.
  // Deliberately keyed off `orders`, not `filteredOrders`: when a search
  // matches nothing there are still orders, and hiding the box would strand
  // the user with no way to clear the term they just typed.
  const hasOrders = orders.length > 0;

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
            <h5>Filters</h5>
            <hr />
            <div>
              <h6>ORDER STATUS</h6>
              {filterOptions.status.map(status => (
                <div className="form-check" key={status}>
                  <input
                    className="form-check-input"
                    type="checkbox"
                    id={status}
                    checked={selectedFilters.status.includes(status)}
                    onChange={() => handleFilterToggle('status', status)}
                  />
                  <label className="form-check-label" htmlFor={status}>{status}</label>
                </div>
              ))}
            </div>
            <hr />
            <div>
              <h6>ORDER TIME</h6>
              {filterOptions.time.map(time => (
                <div className="form-check" key={time}>
                  <input
                    className="form-check-input"
                    type="checkbox"
                    id={time}
                    checked={selectedFilters.time.includes(time)}
                    onChange={() => handleFilterToggle('time', time)}
                  />
                  <label className="form-check-label" htmlFor={time}>{time}</label>
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
            <Alert variant="danger">{error}</Alert>
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
                {filterOptions.status.map(status => (
                  <button
                    key={status}
                    className={`filter-pill ${selectedFilters.status.includes(status) ? 'selected' : ''}`}
                    onClick={() => handleFilterToggle('status', status)}
                  >
                    {status} <span>+</span>
                  </button>
                ))}
              </div>
            </div>

            <div className="mb-4">
              <h6>Order Time</h6>
              <div className="d-flex flex-wrap gap-2">
                {filterOptions.time.map(time => (
                  <button
                    key={time}
                    className={`filter-pill ${selectedFilters.time.includes(time) ? 'selected' : ''}`}
                    onClick={() => handleFilterToggle('time', time)}
                  >
                    {time} <span>+</span>
                  </button>
                ))}
              </div>
            </div>

            <div className="d-flex justify-content-between">
              <button className="btn btn-outline-secondary w-45" onClick={() => setShowFilterModal(false)}>
                Cancel
              </button>
              <button className="btn btn-warning w-45" onClick={() => setShowFilterModal(false)}>
                Apply
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Orders;
