import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { Modal, Button } from 'react-bootstrap';

const AuthContext = createContext();

/**
 * Read the persisted session synchronously.
 *
 * This must NOT happen in an effect: route guards (User.jsx) run their effects
 * before the provider's, so restoring later left `user` null on first render and
 * a refresh on any logged-in page bounced profile -> /login -> / (homepage).
 * Reading during useState initialization means `user` is already correct on the
 * very first render, so there is no window for a guard to misfire.
 */
const readStoredAuth = () => {
  if (typeof window === 'undefined' || !window.localStorage) {
    return { user: null, token: null };
  }
  try {
    const storedUser = localStorage.getItem('user');
    const storedToken = localStorage.getItem('token');
    if (storedUser && storedToken) {
      return { user: JSON.parse(storedUser), token: storedToken };
    }
  } catch (e) {
    console.error("Failed to parse stored user", e);
  }
  return { user: null, token: null };
};

export const AuthProvider = ({ children }) => {
  // Lazy initializer: evaluated once on mount, so localStorage is read a single time.
  const [initialAuth] = useState(readStoredAuth);
  const [user, setUser] = useState(initialAuth.user);
  const [token, setToken] = useState(initialAuth.token);
  const [showLogoutModal, setShowLogoutModal] = useState(false);
  const navigate = useNavigate();

  const login = (userData, authToken) => {
    localStorage.setItem('user', JSON.stringify(userData));
    localStorage.setItem('token', authToken);
    setUser(userData);
    setToken(authToken);
  };

  const logout = () => {
    setShowLogoutModal(true);
  };

  const confirmLogout = () => {
    localStorage.removeItem('user');
    localStorage.removeItem('token');
    setUser(null);
    setToken(null);
    setShowLogoutModal(false);
    navigate('/');
  };

  /**
   * Clear the session without the confirmation modal. Used when the server
   * rejects the stored token: the user did not choose to log out, so asking
   * them to confirm makes no sense, but leaving `user` set would keep the
   * header showing them as signed in while every request 401s.
   */
  const clearSession = useCallback(() => {
    localStorage.removeItem('user');
    localStorage.removeItem('token');
    setUser(null);
    setToken(null);
  }, []);

  // The global fetch interceptor raises this on any 401 from our API.
  useEffect(() => {
    const onUnauthorized = () => {
      setUser((current) => {
        if (current) {
          localStorage.removeItem('user');
          localStorage.removeItem('token');
          setToken(null);
        }
        return null;
      });
    };

    window.addEventListener('auth:unauthorized', onUnauthorized);
    return () => window.removeEventListener('auth:unauthorized', onUnauthorized);
  }, []);

  const getUsernamePath = () => {
    if (!user) return 'user';
    return (user.first_name || user.firstName || user.name || (user.email ? user.email.split('@')[0] : 'user')).toLowerCase().replace(/\s+/g, '');
  };

  return (
    <AuthContext.Provider value={{ user, token, login, logout, clearSession, setUser, getUsernamePath }}>
      {children}
      
      <Modal show={showLogoutModal} onHide={() => setShowLogoutModal(false)} centered>
        <Modal.Header closeButton className="border-0 pb-0">
          <Modal.Title className="fw-bold fs-5">Confirm Logout</Modal.Title>
        </Modal.Header>
        <Modal.Body className="text-muted">
          Are you sure you want to log out of your Printmont account?
        </Modal.Body>
        <Modal.Footer className="border-0 pt-0">
          <Button variant="light" className="fw-medium px-4" onClick={() => setShowLogoutModal(false)}>
            Cancel
          </Button>
          <Button variant="danger" className="fw-medium px-4" onClick={confirmLogout}>
            Yes, Log Out
          </Button>
        </Modal.Footer>
      </Modal>
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
