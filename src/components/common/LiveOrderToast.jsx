import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { BASE_URL, resolveImageUrl } from '../../config/apiEndpoints';
import './LiveOrderToast.css';

const LiveOrderToast = () => {
  const [orders, setOrders] = useState([]);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isVisible, setIsVisible] = useState(false);
  const [isDismissed, setIsDismissed] = useState(false);

  useEffect(() => {
    let isMounted = true;
    const fetchLiveOrders = async () => {
      try {
        const res = await fetch(`${BASE_URL}/home-layout-api.php?action=get_live_orders`);
        if (!res.ok) return;
        const data = await res.json();
        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
          if (isMounted) setOrders(data.data);
        }
      } catch (err) {
        console.warn('Live order notification fetch error:', err);
      }
    };

    fetchLiveOrders();
    return () => { isMounted = false; };
  }, []);

  useEffect(() => {
    if (orders.length === 0 || isDismissed) return;

    // Initial show after 4 seconds
    const initialTimer = setTimeout(() => {
      setIsVisible(true);
    }, 4000);

    // Interval to cycle every 15 seconds
    const interval = setInterval(() => {
      setIsVisible(false); // hide first
      setTimeout(() => {
        setCurrentIndex((prev) => (prev + 1) % orders.length);
        setIsVisible(true);
      }, 800); // fade in next
    }, 15000);

    // Auto-hide each notification after 6.5 seconds
    const hideInterval = setInterval(() => {
      setIsVisible(false);
    }, 15000);

    // Delay hide by 6.5s relative to show
    const hideTimeout = setTimeout(() => {
      setIsVisible(false);
    }, 10500);

    return () => {
      clearTimeout(initialTimer);
      clearTimeout(hideTimeout);
      clearInterval(interval);
      clearInterval(hideInterval);
    };
  }, [orders, isDismissed]);

  if (orders.length === 0 || isDismissed) return null;

  const current = orders[currentIndex];
  if (!current) return null;

  return (
    <div className={`live-order-toast ${isVisible ? 'show' : 'hide'}`}>
      <button 
        type="button" 
        className="live-order-close" 
        onClick={() => setIsDismissed(true)}
        aria-label="Close notification"
      >
        ×
      </button>
      
      <div className="live-order-thumb-wrap">
        <img 
          src={resolveImageUrl(current.thumbnail_image)} 
          alt={current.product_name} 
          className="live-order-thumb"
          onError={(e) => { e.currentTarget.src = '/default-img.jpg'; }}
        />
        <span className="live-order-pulse"></span>
      </div>

      <div className="live-order-content">
        <div className="live-order-header">
          <span className="live-order-tag">Verified Purchase</span>
          <span className="live-order-time">{current.time_ago}</span>
        </div>
        <div className="live-order-user">
          <strong>{current.customer_name}</strong> in <span className="live-order-city">{current.city}</span>
        </div>
        <Link to={`/product/${current.product_slug}`} className="live-order-prod">
          {current.product_name}
        </Link>
      </div>
    </div>
  );
};

export default LiveOrderToast;
