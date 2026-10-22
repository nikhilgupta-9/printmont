import { useCallback } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useAuth } from '../context/AuthContext';

// Returns requireLogin(message): true when the customer is logged in. Otherwise shows the message,
// sends them to login (coming back to the current page afterwards) and returns false.
const useRequireLogin = () => {
  const { user, token } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  return useCallback((message = 'Please login to continue') => {
    if (user && token) return true;
    toast.error(message, { id: 'require-login' });
    navigate(`/login?redirect=${encodeURIComponent(location.pathname + location.search)}`);
    return false;
  }, [user, token, navigate, location.pathname, location.search]);
};

export default useRequireLogin;
