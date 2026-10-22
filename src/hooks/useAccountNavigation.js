import { useCallback } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { API_ENDPOINTS } from '../config/apiEndpoints';

// Seconds left on a JWT, from its own "exp" (0 when unreadable)
const secondsLeft = (token) => {
  try {
    const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')));
    return payload.exp ? payload.exp - Date.now() / 1000 : 0;
  } catch {
    return 0;
  }
};

/**
 * User icon / "My Account": opens the profile only for a real, live session; otherwise login.
 * A still-valid token goes straight to the profile. An expired one is checked with the server
 * (fetchInterceptor renews it with the refresh token); if that fails the session is cleared → login.
 */
const useAccountNavigation = () => {
  const { user, token, getUsernamePath } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const profilePath = `/${getUsernamePath()}/profile`;
  const loginPath = `/login?redirect=${encodeURIComponent(location.pathname + location.search)}`;
  const accountPath = user && token ? profilePath : loginPath;

  const goToAccount = useCallback(async (e) => {
    if (e && e.preventDefault) e.preventDefault();
    const storedToken = token || localStorage.getItem('token');

    if (!user || !storedToken) {
      navigate(loginPath);
      return;
    }
    if (secondsLeft(storedToken) > 30) {
      navigate(profilePath);
      return;
    }
    try {
      const res = await fetch(API_ENDPOINTS.PROFILE, {
        headers: { Authorization: `Bearer ${localStorage.getItem('token') || storedToken}` },
      });
      navigate(res.ok ? profilePath : loginPath);
    } catch {
      navigate(loginPath);
    }
  }, [user, token, navigate, profilePath, loginPath]);

  return { accountPath, goToAccount, isLoggedIn: Boolean(user && token) };
};

export default useAccountNavigation;
