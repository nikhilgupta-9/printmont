import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import './App.css';

import { CheckoutProvider } from "./context/CheckoutContext";
import HeaderManager from "./components/header/HeaderManager";
import Home from './components/pages/Home';
import Footer from "./components/footer/Footer";
import Profile from "./components/user/Profile";
import Wishlist from "./components/orders/Wishlist";
import Orders from "./components/orders/Orders";
import User from "./components/user/User";
import Login from "./components/user/Login";
import GiftCard from "./components/orders/GiftCard";
import ProductDetails from "./components/products/ProductDetails";
import AllProducts from "./components/products/AllProducts";
import Cart from "./components/orders/Cart";
import Blog from "./components/pages/blog/Blog";
import BlogPostPage from "./components/pages/blog/BlogPostPage";
import CheckApi from "./components/checkapi";
import HelpCenter from "./components/helpCenter/HelpCenter";
import ContactUs from "./components/contact/ContactUs";
import QuickLinks from "./components/quickLinks/QuickLinks";
import TrackOrder from "./components/trackOrder/TrackOrder";
import AppAnnouncement from "./components/appAnnouncement/AppAnnouncement";
import MyAccount from "./components/myAccount/MyAccount";
import CareerPage from "./components/pages/career/CareerPage";
import AboutPage from "./components/about/AboutPage";
import PolicyPage from "./components/policy/PolicyPage";
import PrintmontFAQ from "./components/printmontFAQ/PrintmontFAQ";
import SecurityInfo from "./components/security/SecurityInfo";
import PageNotFound from "./components/pageNotFound/PageNotFound";
import NotificationPreference from "./components/notificationPre/NotificationPreferences";
import ManageAddress from "./components/manageAddress/ManageAddress";
import SupportPage from "./components/supportpage/SupportPage";
import PrintmontCoin from "./components/prinmontCoin/PrintmontCoin";

function App() {
  return (
    <Router>
      <CheckoutProvider>
        {/* 👇 Use the HeaderManager here instead of Header */}
        <HeaderManager />

        {/* All Routes */}
        <Routes>
          {/* <Route path="/" element={<CheckApi />} /> */}
          <Route path="/" element={<Home />} />
          <Route path="/cart" element={<Cart />} />
          <Route path="/orders" element={<Orders />} />
          <Route path="/login" element={<Login />} />
          <Route path="/allproducts" element={<AllProducts />} />
          <Route path="/product" element={<ProductDetails />} />
          <Route path="/blog" element={<Blog />} />
          <Route path="/blog/:slug" element={<BlogPostPage />} />
          <Route path="/help-center" element={<HelpCenter />} />
          <Route path="/contact" element={<ContactUs />} />
          <Route path="/quick-links" element={<QuickLinks />} />
          <Route path="/my-account" element={<MyAccount />} />
          <Route path="/track-order" element={<TrackOrder />} />
          <Route path="/app-download" element={<AppAnnouncement />} />
          <Route path="/careers" element={<CareerPage />} />
          <Route path="/about" element={<AboutPage />} />
          <Route path="/faq" element={<PrintmontFAQ />} />
          <Route path="/security" element={<SecurityInfo />} />
          <Route path="/account-setting" element={<MyAccount />} />
          <Route path="/notification-preference" element={<NotificationPreference />} />
          <Route path="/manage-address" element={<ManageAddress />} />
          <Route path="/support" element={<SupportPage />} />
          <Route path="/printmont-coin" element={<PrintmontCoin />} />
          <Route path="*" element={<PageNotFound />} />
          <Route path="/policy/*" element={<PolicyPage />} />

          <Route path="/user" element={<User />}>
            <Route index element={<Profile />} />
            <Route path="profile" element={<Profile />} />
            <Route path="wishlist" element={<Wishlist />} />
            <Route path="giftcard" element={<GiftCard />} />
            <Route path="manage-address" element={<ManageAddress  />} />
          </Route>

        </Routes>

        {/* Footer */}
        <Footer />
      </CheckoutProvider>
    </Router>
  );
}

export default App;
