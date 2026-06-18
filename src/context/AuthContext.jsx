import React, { createContext, useContext, useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Modal, Button } from 'react-bootstrap';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);
  const [showLogoutModal, setShowLogoutModal] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    // Check local storage on initial load
    const storedUser = localStorage.getItem('user');
    const storedToken = localStorage.getItem('token');
    if (storedUser && storedToken) {
      try {
        setUser(JSON.parse(storedUser));
        setToken(storedToken);
      } catch (e) {
        console.error("Failed to parse stored user", e);
      }
    }
  }, []);

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

  const getUsernamePath = () => {
    if (!user) return 'user';
    return (user.first_name || user.firstName || user.name || (user.email ? user.email.split('@')[0] : 'user')).toLowerCase().replace(/\s+/g, '');
  };

  return (
    <AuthContext.Provider value={{ user, token, login, logout, setUser, getUsernamePath }}>
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
