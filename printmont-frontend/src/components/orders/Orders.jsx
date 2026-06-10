import React, { useState } from 'react';
import { IoSearch } from "react-icons/io5";
import { Completeorders } from '../../../data/data';

const filterOptions = {
  status: ['On the way', 'Delivered', 'Cancelled', 'Returned'],
  time: ['Last 30 days', '2024', '2023', '2022', '2021', 'Older']
};

const OrderCard = ({ order }) => (
  <div className="card mb-3 p-2">
    <div className="row g-2 align-items-center">
      <div className="col-auto">
        <img src={order.image} alt={order.title} style={{ width: '70px', height: '70px', objectFit: 'contain' }} />
      </div>
      <div className="col">
        <div className="fw-semibold text-truncate" style={{ maxWidth: '250px' }}>{order.title}</div>
        <small className="text-muted">Color: {order.color} &nbsp;&nbsp; Size: {order.size}</small>
      </div>
      <div className="col-auto fw-semibold">₹{order.price}</div>
      <div className="col-auto text-success">
        <span className="me-1" style={{ fontSize: '1.2em' }}>●</span>
        <span className="fw-bold">Delivered on {order.deliveryDate}</span>
        <div>Your item has been delivered</div>
        <a href="#" className="text-primary" style={{ fontWeight: '600', fontSize: '0.9rem' }}>
          <span style={{ marginRight: '5px' }}>★</span> Rate & Review Product
        </a>
      </div>
    </div>
  </div>
);

const Orders = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [showFilterModal, setShowFilterModal] = useState(false);
  const [selectedFilters, setSelectedFilters] = useState({ status: [], time: [] });

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

  const filteredOrders = Completeorders.filter(order =>
    order.title.toLowerCase().includes(searchTerm.toLowerCase())
  );

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
      <div className="d-md-none d-flex justify-content-end mb-2">
        <button className="btn btn-outline-secondary btn-sm" onClick={() => setShowFilterModal(true)}>
          Filters
        </button>
      </div>

      <div className="row">
        {/* Desktop Sidebar Filters */}
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

        {/* Main Orders Area */}
        <main className="col-md-9">
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

          {filteredOrders.length ? (
            filteredOrders.map(order => (
              <OrderCard key={order.id} order={order} />
            ))
          ) : (
            <p>No orders found.</p>
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
