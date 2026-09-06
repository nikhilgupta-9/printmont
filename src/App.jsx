import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import './App.css';

import { AuthProvider } from "./context/AuthContext";
import { CheckoutProvider } from "./context/CheckoutContext";
import { WishlistProvider } from "./context/WishlistContext";
import HeaderManager from "./components/header/HeaderManager";
import Home from './components/pages/Home';
import Footer from "./components/footer/Footer";
import Profile from "./components/user/Profile";
import Wishlist from "./components/orders/Wishlist";
import Orders from "./components/orders/Orders";
import User from "./components/user/User";
import Login from "./components/user/Login";
import ForgotPassword from "./components/user/ForgotPassword";
import ChangePassword from "./components/user/ChangePassword";
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
import BannerPreview from "./components/pages/BannerPreview";
import CategoryPage from "./components/pages/category-list/CategoryPage";
import BusinessSolutions from "./components/businessSolutions/BusinessSolutions";
import BecomeASeller from "./components/becomeSeller/BecomeASeller";
import AffiliateProgram from "./components/pages/AffiliateProgram";
import TermsOfUse from "./components/pages/TermsOfUse";
import BulkOrderPage from "./components/pages/BulkOrderPage";
import FranchisePage from "./components/pages/FranchisePage";
import SitemapPage from "./components/pages/sitemap/SitemapPage";
import SearchPage from "./pages/SearchPage";

import { useEffect } from "react";
import { useLocation } from "react-router-dom";

function ScrollToTop() {
  const { pathname } = useLocation();

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [pathname]);

  return null;
}

function App() {
  return (
    <Router>
      <ScrollToTop />
      <AuthProvider>
        <WishlistProvider>
          <CheckoutProvider>
            <Toaster position="top-center" reverseOrder={false} />
        {/* 👇 Use the HeaderManager here instead of Header */}
        <HeaderManager />

        {/* All Routes */}
        <Routes>
          {/* <Route path="/" element={<CheckApi />} /> */}
          <Route path="/" element={<Home />} />
          <Route path="/search" element={<AllProducts />} />
          <Route path="/cart" element={<Cart />} />
          <Route path="/orders" element={<Orders />} />
          <Route path="/login" element={<Login />} />
          <Route path="/forgot-password" element={<ForgotPassword />} />
          <Route path="/allproducts" element={<AllProducts />} />
          <Route path="/wishlist" element={<Wishlist />} />
          <Route path="/banner-preview" element={<BannerPreview />} />
          {/* Static Core Pages */}
          <Route path="/blog" element={<Blog />} />
          <Route path="/blog/:slug" element={<BlogPostPage />} />
          <Route path="/help-center" element={<HelpCenter />} />
          <Route path="/contact" element={<ContactUs />} />
          <Route path="/contact-us" element={<Navigate to="/contact" replace />} />
          <Route path="/quick-links" element={<QuickLinks />} />
          <Route path="/my-account" element={<MyAccount />} />
          <Route path="/track-order" element={<TrackOrder />} />
          <Route path="/app-download" element={<AppAnnouncement />} />
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
          <Route path="/wallet" element={<PrintmontCoin />} />
          <Route path="/business-solutions" element={<BusinessSolutions />} />
          <Route path="/become-a-seller" element={<BecomeASeller />} />
          <Route path="/bulk-orders" element={<BulkOrderPage />} />
          <Route path="/bulk-order" element={<BulkOrderPage />} />
          <Route path="/franchise" element={<FranchisePage />} />
          <Route path="/franchises" element={<FranchisePage />} />
          <Route path="/category" element={<CategoryPage />} />
          <Route path="/category/:id" element={<CategoryPage />} />
          <Route path="/affiliate-program" element={<AffiliateProgram />} />
          <Route path="/sitemap" element={<SitemapPage />} />
          <Route path="/terms-of-use" element={<PolicyPage />} />
          <Route path="/terms-and-conditions" element={<PolicyPage />} />
          <Route path="/privacy-policy" element={<PolicyPage />} />
          <Route path="/shipping-policy" element={<PolicyPage />} />
          <Route path="/refund-policy" element={<PolicyPage />} />
          <Route path="/return-policy" element={<PolicyPage />} />
          <Route path="/policy/*" element={<PolicyPage />} />

          <Route path="/user" element={<User />}>
            <Route index element={<Profile />} />
            <Route path="profile" element={<Profile />} />
            <Route path="wishlist" element={<Wishlist />} />
            <Route path="wallet" element={<PrintmontCoin />} />
            <Route path="giftcard" element={<GiftCard />} />
            <Route path="manage-address" element={<ManageAddress  />} />
            <Route path="change-password" element={<ChangePassword />} />
          </Route>

          {/* Product Detail dynamic routes */}
          <Route path="/product/:productSlug" element={<ProductDetails />} />
          <Route path="/:productSlug" element={<ProductDetails />} />

          {/* Account pages */}
          <Route path="/:username" element={<User />}>
            <Route path="profile" element={<Profile />} />
            <Route path="wishlist" element={<Wishlist />} />
            <Route path="wallet" element={<PrintmontCoin />} />
            <Route path="giftcard" element={<GiftCard />} />
            <Route path="manage-address" element={<ManageAddress />} />
            <Route path="change-password" element={<ChangePassword />} />
          </Route>

          <Route path="*" element={<PageNotFound />} />
        </Routes>

        {/* Footer */}
        <Footer />
        </CheckoutProvider>
        </WishlistProvider>
      </AuthProvider>
    </Router>
  );
}

export default App;
