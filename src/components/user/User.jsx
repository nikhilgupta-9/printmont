import React, { useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useNavigate, useLocation, Outlet } from 'react-router-dom';
import AccountSidebar from './AccountSidebar';
import OrdersBannerCarousel from '../orders/OrdersBannerCarousel';
import './UserDashboard.css';

const User = () => {
  const { user } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    if (!user) {
      const redirectUrl = location.pathname + location.search;
      navigate(`/login?redirect=${encodeURIComponent(redirectUrl)}`, { replace: true });
    }
  }, [user, navigate, location]);

  if (!user) {
    return null;
  }

  const isTrackOrderPage = location.pathname.includes('track-order');
  const isOrdersPage = !isTrackOrderPage && (location.pathname.includes('/orders') || location.pathname.includes('/order/'));
  const isOrdersListPage = location.pathname.endsWith('/orders');
  const activeSidebarKey = isTrackOrderPage ? 'track-order' : (isOrdersPage ? 'orders' : undefined);

  return (
    <div className="user-dashboard-canvas">
      <div className="row g-0 g-lg-4">
        {/* Left Sidebar - Wireframe Style */}
        <div className="col-12 col-lg-3 d-none d-lg-block">
          <AccountSidebar activeKey={activeSidebarKey} />
        </div>

        {/* Right Side Content Pane */}
        <div className="col-12 col-lg-9">
          <Outlet />
        </div>
      </div>

      {/* Orders Page Full-Width Bottom Banner Carousel (Desktop Wireframe Image 1) */}
      {isOrdersPage && (
        <div className="d-none d-lg-block mt-4">
          <OrdersBannerCarousel isMobile={false} />
        </div>
      )}
    </div>
  );
};

export default User;
