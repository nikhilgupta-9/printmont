import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';

// /wishlist opens the wishlist inside the account pages (sidebar on desktop, like Flipkart).
// Guests are sent to login and come back here afterwards.
const WishlistRedirect = () => {
  const { user, getUsernamePath } = useAuth();
  const location = useLocation();

  if (!user) {
    return <Navigate to={`/login?redirect=${encodeURIComponent(location.pathname)}`} replace />;
  }
  return <Navigate to={`/${getUsernamePath()}/wishlist`} replace />;
};

export default WishlistRedirect;
