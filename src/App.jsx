import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import './App.css';

import { AuthProvider } from "./context/AuthContext";
import { CheckoutProvider } from "./context/CheckoutContext";
import { WishlistProvider } from "./context/WishlistContext";
import { LocationProvider } from "./context/LocationContext";
import LocationModal from "./components/location/LocationModal";
import HeaderManager from "./components/header/HeaderManager";
import Home from './components/pages/Home';
import Footer from "./components/footer/Footer";
import Profile from "./components/user/Profile";
import Wishlist from "./components/orders/Wishlist";
import WishlistRedirect from "./components/orders/WishlistRedirect";
import Orders from "./components/orders/Orders";
import OrderDetailsPage from "./components/orders/OrderDetailsPage";
import User from "./components/user/User";
import Login from "./components/user/Login";
import ForgotPassword from "./components/user/ForgotPassword";
import ChangePassword from "./components/user/ChangePassword";
import PanGstDetails from "./components/user/PanGstDetails";
import GiftCard from "./components/orders/GiftCard";
import ProductDetails from "./components/products/ProductDetails";
import AllProducts from "./components/products/AllProducts";
import Cart from "./components/orders/Cart";
import Blog from "./components/pages/blog/Blog";
import BlogPostPage from "./components/pages/blog/BlogPostPage";
import CheckApi from "./components/CheckApi";
import HelpCenter from "./components/helpCenter/HelpCenter";
import ContactUs from "./components/contact/ContactUs";
import QuickLinks from "./components/quickLinks/QuickLinks";
import TrackOrder from "./components/trackOrder/TrackOrder";
import AppAnnouncement from "./components/appAnnouncement/AppAnnouncement";
import MyAccount from "./components/myAccount/MyAccount";
import CareerPage from "./components/pages/career/CareerPage";
import VacancyDetails from "./components/pages/career/VacancyDetails";
import AboutPage from "./components/about/AboutPage";
import PolicyPage from "./components/policy/PolicyPage";
import PrintmontFAQ from "./components/printmontFAQ/PrintmontFAQ";
import SecurityInfo from "./components/security/SecurityInfo";
import PageNotFound from "./components/pageNotFound/PageNotFound";
import NotificationPreference from "./components/notificationPre/NotificationPreferences";
import ManageAddress from "./components/manageAddress/ManageAddress";
import SupportPage from "./components/supportpage/SupportPage";
import PrintmontCoin from "./components/prinmontCoin/PrintmontCoin";
import UserWallet from "./components/user/UserWallet";
import UserWalletRedirect from "./components/user/UserWalletRedirect";
import UserReviews from "./components/user/UserReviews";
import BannerPreview from "./components/pages/BannerPreview";
import CategoryPage from "./components/pages/category-list/CategoryPage";
import BusinessSolutions from "./components/businessSolutions/BusinessSolutions";
import Advertising from "./components/advertising/Advertising";
import BecomeASeller from "./components/becomeSeller/BecomeASeller";
import AffiliateProgram from "./components/pages/AffiliateProgram";
import TermsOfUse from "./components/pages/TermsOfUse";
import BulkOrderPage from "./components/pages/BulkOrderPage";
import FranchisePage from "./components/pages/FranchisePage";
import SitemapPage from "./components/pages/sitemap/SitemapPage";
import LiveOrderToast from "./components/common/LiveOrderToast";
import SearchPage from "./pages/SearchPage";
import AllReviewsPage from "./components/pages/reviews/AllReviewsPage";
import MaintenancePage from "./components/common/MaintenancePage";
import GlobalPreloader from "./components/common/GlobalPreloader";
import { API_ENDPOINTS } from "./config/apiEndpoints";
import { initImageProtection } from "./utils/imageProtection";

import { useEffect, useState } from "react";
import { useLocation } from "react-router-dom";

function ScrollToTop() {
  const location = useLocation();

  useEffect(() => {
    window.scrollTo(0, 0);

    // Track last non-auth page so auth flows (login, signup, forgot-password) reliably return to the origin page
    const path = location.pathname;
    const isAuth = path.startsWith('/login') || path.startsWith('/signup') || path.startsWith('/register') || path.startsWith('/forgot-password');
    const isProtected = path.startsWith('/user');
    const hasToken = typeof window !== 'undefined' && Boolean(localStorage.getItem('token'));

    if (!isAuth && (!isProtected || hasToken)) {
      sessionStorage.setItem('pm_last_visited_page', path + location.search);
    }
  }, [location]);

  return null;
}

function App() {
  const [maintenanceData, setMaintenanceData] = useState(null);
  const [isMaintenanceMode, setIsMaintenanceMode] = useState(false);
  const [loaderData, setLoaderData] = useState(null);
  const [isAppLoading, setIsAppLoading] = useState(true);
  const [isBypassed, setIsBypassed] = useState(() => {
    const searchParams = new URLSearchParams(window.location.search);
    const bypassParam = searchParams.get('bypass');
    const previewParam = searchParams.get('preview') || searchParams.get('maintenance');
    if (bypassParam === 'exit' || bypassParam === 'false' || previewParam === 'maintenance' || previewParam === '1' || previewParam === 'true') {
      sessionStorage.removeItem('maintenance_bypass');
      return false;
    }
    return sessionStorage.getItem('maintenance_bypass') === 'true';
  });

  const checkMaintenance = async () => {
    try {
      const res = await fetch(API_ENDPOINTS.MAINTENANCE + '?t=' + Date.now());
      if (res.ok) {
        const data = await res.json();
        if (data && data.success) {
          setMaintenanceData(data);
          
          const searchParams = new URLSearchParams(window.location.search);
          const bypassParam = searchParams.get('bypass');
          const previewParam = searchParams.get('preview') || searchParams.get('maintenance');
          
          // Force preview maintenance page
          if (previewParam === 'maintenance' || previewParam === '1' || previewParam === 'true' || bypassParam === 'exit') {
            sessionStorage.removeItem('maintenance_bypass');
            setIsBypassed(false);
            setIsMaintenanceMode(true);
            return;
          }

          // Check URL bypass key
          if (bypassParam && data.secret_bypass_key && bypassParam === data.secret_bypass_key) {
            sessionStorage.setItem('maintenance_bypass', 'true');
            setIsBypassed(true);
            setIsMaintenanceMode(false);
          } else if (sessionStorage.getItem('maintenance_bypass') === 'true') {
            setIsBypassed(true);
            setIsMaintenanceMode(false);
          } else {
            setIsBypassed(false);
            setIsMaintenanceMode(Boolean(data.is_maintenance));
          }
        }
      }
    } catch (err) {
      console.warn("Could not check maintenance mode:", err);
    }
  };

  const loadLoaderSettings = async () => {
    const startTime = Date.now();
    try {
      const res = await fetch(API_ENDPOINTS.LOADER_SETTINGS + '?t=' + Date.now());
      if (res.ok) {
        const data = await res.json();
        if (data && data.success) {
          setLoaderData(data);
          const minDuration = data.min_duration || 500;
          const elapsed = Date.now() - startTime;
          const remaining = Math.max(0, minDuration - elapsed);

          const searchParams = new URLSearchParams(window.location.search);
          const isLoaderPreview = searchParams.get('preview') === 'loader';

          if (!isLoaderPreview) {
            setTimeout(() => {
              setIsAppLoading(false);
            }, remaining);
          }
          return;
        }
      }
    } catch (err) {
      console.warn("Could not fetch loader settings:", err);
    }
    setIsAppLoading(false);
  };

  useEffect(() => {
    checkMaintenance();
    loadLoaderSettings();
    initImageProtection();
  }, []);

  if (isMaintenanceMode && !isBypassed) {
    return <MaintenancePage maintenanceData={maintenanceData} onRefresh={checkMaintenance} />;
  }

  return (
    <Router>
      <GlobalPreloader loaderData={loaderData} isVisible={isAppLoading} />
      <ScrollToTop />
      {/* Floating Admin Bypass Warning Bar */}
      {isBypassed && maintenanceData?.is_maintenance && (
        <div style={{
          background: 'linear-gradient(90deg, #e11d48, #be123c)',
          color: '#ffffff',
          padding: '8px 16px',
          fontSize: '13px',
          fontWeight: 600,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          position: 'sticky',
          top: 0,
          zIndex: 999999,
          boxShadow: '0 2px 10px rgba(0,0,0,0.25)'
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <span style={{ fontSize: '15px' }}>🚨</span>
            <span><strong>Maintenance Mode is Active</strong> — You are viewing the live storefront via Secret Admin Bypass.</span>
          </div>
          <div style={{ display: 'flex', gap: '8px' }}>
            <button 
              onClick={() => { 
                sessionStorage.removeItem('maintenance_bypass'); 
                setIsBypassed(false); 
                setIsMaintenanceMode(true); 
              }}
              style={{ 
                background: 'rgba(255,255,255,0.2)', 
                border: '1px solid rgba(255,255,255,0.4)', 
                color: '#ffffff', 
                borderRadius: '6px', 
                padding: '4px 12px', 
                fontSize: '12px', 
                cursor: 'pointer', 
                fontWeight: 600 
              }}>
              View Maintenance Page
            </button>
          </div>
        </div>
      )}
      <AuthProvider>
        <LocationProvider>
          <WishlistProvider>
            <CheckoutProvider>
              <Toaster position="top-center" reverseOrder={false} />
              {/* 👇 Use the HeaderManager here instead of Header */}
              <HeaderManager />
              <LocationModal />

        {/* All Routes */}
        <Routes>
          {/* <Route path="/" element={<CheckApi />} /> */}
          <Route path="/" element={<Home />} />
          <Route path="/search" element={<AllProducts />} />
          <Route path="/cart" element={<Cart />} />
          <Route path="/orders" element={<Orders />} />
          <Route path="/orders/:orderId" element={<OrderDetailsPage />} />
          <Route path="/order/:orderId" element={<OrderDetailsPage />} />
          <Route path="/login" element={<Login />} />
          <Route path="/signup" element={<Login isSignupPage={true} />} />
          <Route path="/register" element={<Login isSignupPage={true} />} />
          <Route path="/forgot-password" element={<ForgotPassword />} />
          <Route path="/allproducts" element={<AllProducts />} />
          <Route path="/wishlist" element={<WishlistRedirect />} />
          <Route path="/banner-preview" element={<BannerPreview />} />
          {/* Static Core Pages */}
          <Route path="/blog" element={<Blog />} />
          <Route path="/blog/:slug" element={<BlogPostPage />} />
          <Route path="/help-center" element={<HelpCenter />} />
          <Route path="/help" element={<HelpCenter />} />
          <Route path="/contact" element={<ContactUs />} />
          <Route path="/contact-us" element={<Navigate to="/contact" replace />} />
          <Route path="/quick-links" element={<QuickLinks />} />
          <Route path="/my-account" element={<MyAccount />} />
          <Route path="/track-order" element={<TrackOrder />} />
          <Route path="/track" element={<Navigate to="/track-order" replace />} />
          <Route path="/account" element={<Navigate to="/my-account" replace />} />
          <Route path="/sell" element={<Navigate to="/become-a-seller" replace />} />
          <Route path="/advertising" element={<Advertising />} />
          <Route path="/coins" element={<Navigate to="/printmont-coin" replace />} />
          <Route path="/app-download" element={<AppAnnouncement />} />
          <Route path="/app" element={<Navigate to="/app-download" replace />} />
          <Route path="/mobile-app" element={<Navigate to="/app-download" replace />} />
          <Route path="/careers" element={<CareerPage />} />
          <Route path="/careers/:id" element={<VacancyDetails />} />
          <Route path="/about" element={<AboutPage />} />
          <Route path="/about-us" element={<Navigate to="/about" replace />} />
          <Route path="/faq" element={<PrintmontFAQ />} />
          <Route path="/security" element={<SecurityInfo />} />
          <Route path="/account-setting" element={<MyAccount />} />
          <Route path="/notification-preference" element={<NotificationPreference />} />
          <Route path="/manage-address" element={<ManageAddress />} />
          <Route path="/support" element={<SupportPage />} />
          <Route path="/printmont-coin" element={<PrintmontCoin />} />
          <Route path="/wallet" element={<UserWalletRedirect />} />
          <Route path="/my-wallet" element={<UserWalletRedirect />} />
          <Route path="/business-solutions" element={<BusinessSolutions />} />
          <Route path="/become-a-seller" element={<BecomeASeller />} />
          <Route path="/become-seller" element={<Navigate to="/become-a-seller" replace />} />
          <Route path="/bulk-orders" element={<BulkOrderPage />} />
          <Route path="/bulk-order" element={<BulkOrderPage />} />
          <Route path="/franchise" element={<FranchisePage />} />
          <Route path="/franchises" element={<FranchisePage />} />
          <Route path="/category" element={<CategoryPage />} />
          <Route path="/category/:id" element={<CategoryPage />} />
          <Route path="/category/:id/reviews" element={<AllReviewsPage />} />
          <Route path="/product/:productSlug/reviews" element={<AllReviewsPage />} />
          <Route path="/reviews/product/:productId" element={<AllReviewsPage />} />
          <Route path="/product-reviews/:productSlug" element={<AllReviewsPage />} />
          <Route path="/reviews" element={<AllReviewsPage />} />
          <Route path="/affiliate-program" element={<AffiliateProgram />} />
          <Route path="/sitemap" element={<SitemapPage />} />
          {/* Policy Information Pages (Publicly accessible - No login required) */}
          <Route path="/terms" element={<PolicyPage />} />
          <Route path="/privacy" element={<PolicyPage />} />
          <Route path="/shipping" element={<PolicyPage />} />
          <Route path="/returns" element={<PolicyPage />} />
          <Route path="/refund" element={<PolicyPage />} />
          <Route path="/policy" element={<PolicyPage />} />
          <Route path="/policies" element={<PolicyPage />} />
          <Route path="/policy-info" element={<PolicyPage />} />
          <Route path="/terms-of-use" element={<PolicyPage />} />
          <Route path="/terms-and-conditions" element={<PolicyPage />} />
          <Route path="/terms-conditions" element={<PolicyPage />} />
          <Route path="/privacy-policy" element={<PolicyPage />} />
          <Route path="/shipping-policy" element={<PolicyPage />} />
          <Route path="/refund-policy" element={<PolicyPage />} />
          <Route path="/return-policy" element={<PolicyPage />} />
          <Route path="/cancellation-policy" element={<PolicyPage />} />
          <Route path="/return-and-refund-policy" element={<PolicyPage />} />
          <Route path="/policy/*" element={<PolicyPage />} />
          <Route path="/affiliate" element={<AffiliateProgram />} />

          <Route path="/user" element={<User />}>
            <Route index element={<Profile />} />
            <Route path="profile" element={<Profile />} />
            <Route path="profile-edit" element={<Profile forceEditForm={true} />} />
            <Route path="orders" element={<Orders />} />
            <Route path="orders/:orderId" element={<OrderDetailsPage />} />
            <Route path="order/:orderId" element={<OrderDetailsPage />} />
            <Route path="wishlist" element={<Wishlist />} />
            <Route path="wallet" element={<UserWallet />} />
            <Route path="coins" element={<UserWallet />} />
            <Route path="reviews" element={<UserReviews />} />
            <Route path="giftcard" element={<GiftCard />} />
            <Route path="manage-address" element={<ManageAddress />} />
            <Route path="pan-gst" element={<PanGstDetails />} />
            <Route path="pan-card" element={<PanGstDetails />} />
            <Route path="change-password" element={<ChangePassword />} />
            <Route path="track-order" element={<TrackOrder />} />
          </Route>

          {/* Product Detail dynamic routes */}
          <Route path="/product/:productSlug" element={<ProductDetails />} />
          <Route path="/:productSlug" element={<ProductDetails />} />

          {/* Account pages */}
          <Route path="/:username" element={<User />}>
            <Route index element={<Profile />} />
            <Route path="profile" element={<Profile />} />
            <Route path="profile-edit" element={<Profile forceEditForm={true} />} />
            <Route path="orders" element={<Orders />} />
            <Route path="orders/:orderId" element={<OrderDetailsPage />} />
            <Route path="order/:orderId" element={<OrderDetailsPage />} />
            <Route path="wishlist" element={<Wishlist />} />
            <Route path="wallet" element={<UserWallet />} />
            <Route path="coins" element={<UserWallet />} />
            <Route path="reviews" element={<UserReviews />} />
            <Route path="giftcard" element={<GiftCard />} />
            <Route path="manage-address" element={<ManageAddress />} />
            <Route path="pan-gst" element={<PanGstDetails />} />
            <Route path="pan-card" element={<PanGstDetails />} />
            <Route path="change-password" element={<ChangePassword />} />
            <Route path="track-order" element={<TrackOrder />} />
          </Route>

          <Route path="*" element={<PageNotFound />} />
        </Routes>

        {/* Footer */}
        <Footer />

        {/* Live Order Social Proof Toast (Module 29) */}
        <LiveOrderToast />
            </CheckoutProvider>
          </WishlistProvider>
        </LocationProvider>
      </AuthProvider>
    </Router>
  );
}

export default App;
