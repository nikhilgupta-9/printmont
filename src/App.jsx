import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';

import './App.css';
import Header from './components/header/Header';
import Home from './components/pages/Home';
import Footer from './components/footer/Footer';
import AddToCart from "./components/orders/AddToCart";
import Profile from "./components/user/Profile";
import Wishlist from "./components/orders/Wishlist";
import Orders from "./components/orders/Orders";
import User from "./components/user/User";
import Login from "./components/user/Login";
import GiftCard from "./components/orders/GiftCard";
import ProductDetails from "./components/products/ProductDetails";
import AllProducts from "./components/products/AllProducts";

function App() {
  return (
    <Router>
      <Header />
      <Routes>
        <Route path="/" element={<Home />} />
        <Route path="/cart" element={<AddToCart />} />

        <Route path="/user" element={<User />}>
          <Route index element={<Profile />} /> {/*default tab when someone click on user*/}
          <Route path="profile" element={<Profile />} />
          <Route path="wishlist" element={<Wishlist />} />
          <Route path="giftcard" element={<GiftCard />} />
        </Route>
        <Route path="/orders" element={<Orders/>}/>

        <Route path="/login" element={<Login/>}/>
        <Route path="/product" element={<ProductDetails/>}/>
        <Route path="/allproducts" element={<AllProducts/>}/>

      </Routes>
      <Footer />
    </Router>
  );
}

export default App;
