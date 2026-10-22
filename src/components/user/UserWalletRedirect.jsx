import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';

/**
 * /wallet smart redirect:
 * - If logged in: redirects directly to the user dashboard wallet: /:username/wallet (e.g. /nikhil/wallet).
 * - If guest: redirects to /login?redirect=/user/wallet so user logs in and lands directly on their dashboard wallet.
 */
const UserWalletRedirect = () => {
  const { user, getUsernamePath } = useAuth();
  const location = useLocation();

  if (!user) {
    const redirectTarget = location.pathname.includes('wallet') ? location.pathname : '/user/wallet';
    return <Navigate to={`/login?redirect=${encodeURIComponent(redirectTarget)}`} replace />;
  }

  return <Navigate to={`/${getUsernamePath()}/wallet`} replace />;
};

export default UserWalletRedirect;

