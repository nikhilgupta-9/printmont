import React, { useState } from 'react';
import { IoSearchSharp } from "react-icons/io5";
import { BsArrowLeft, BsXLg } from "react-icons/bs"; // BsXLg is the close icon
import { GiShoppingCart } from "react-icons/gi";
import { FaRegCircleUser } from "react-icons/fa6";
import { FaHeart, FaShoppingBag } from 'react-icons/fa';
import Dropdown from 'react-bootstrap/Dropdown';
import DropdownButton from 'react-bootstrap/DropdownButton';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import Categories from '../pages/category-list/Categories';
import SearchBar from '../search/SearchBar';
import { useCheckout } from '../../context/CheckoutContext';
import { useAuth } from '../../context/AuthContext';
import axios from 'axios';
import { API_ENDPOINTS, ASSET_URL } from '../../config/apiEndpoints';

const ProductPageHeader = ({ pageTitle = "Cart", showBackButton = true, showCategories = true }) => {
    const navigate = useNavigate();
    const location = useLocation();
    const checkoutContext = useCheckout();
    const { user, logout, getUsernamePath } = useAuth();
    const usernamePath = getUsernamePath();
    const cartItems = checkoutContext ? checkoutContext.cartItems : [];
    const cartCount = cartItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
    
    const isProductPage = location.pathname.startsWith('/product');
    const isInfoOrPolicyPage = location.pathname.includes('policy') || 
                               location.pathname.includes('terms') || 
                               location.pathname.includes('privacy') || 
                               location.pathname.includes('shipping') || 
                               location.pathname.includes('refund') ||
                               location.pathname.includes('bulk') ||
                               location.pathname.includes('franchise') ||
                               location.pathname.includes('affiliate') ||
                               location.pathname.includes('blog') ||
                               location.pathname.includes('seller') ||
                               location.pathname.includes('about') ||
                               location.pathname.includes('careers') ||
                               location.pathname.includes('faq') ||
                               location.pathname.includes('security') ||
                               location.pathname.includes('wallet') ||
                               location.pathname.includes('printmont-coin');

    const shouldShowCategories = showCategories && !isInfoOrPolicyPage;

    // State to toggle between the default header and the active search bar
    const [isSearchActive, setIsSearchActive] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [showSearchDropdown, setShowSearchDropdown] = useState(false);
    const [searchResults, setSearchResults] = useState([]);
    const [isSearching, setIsSearching] = useState(false);
    const [logo, setLogo] = useState("/PrintLogo.png");
    const headerRef = React.useRef(null);
    const searchRef = React.useRef(null);

    React.useEffect(() => {
        const fetchLogo = async () => {
            try {
                const response = await fetch(API_ENDPOINTS.LOGO);
                const data = await response.json();
                if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                    let logoData = data.data.find(l => (l.asset_type === "desktop_logo" || l.asset_type === "header_logo") && (l.is_active == 1 || l.is_active === "1"));
                    if (!logoData) {
                        logoData = data.data.find(l => l.is_active == 1 || l.is_active === "1") || data.data[0];
                    }
                    if (logoData) {
                        const filePath = logoData.file_path.startsWith('/') ? logoData.file_path.substring(1) : logoData.file_path;
                        setLogo(`${ASSET_URL}${filePath}${logoData.file_name}`);
                    }
                }
            } catch (error) {
                console.error("Error fetching logo:", error);
            }
        };
        fetchLogo();
    }, []);

    // Close search dropdown if clicked outside
    React.useEffect(() => {
        const handleClickOutside = (e) => {
            if (searchRef.current && !searchRef.current.contains(e.target)) {
                setShowSearchDropdown(false);
            }
        };
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    // Search API Effect
    React.useEffect(() => {
        if (!searchTerm.trim()) {
            setSearchResults([]);
            return;
        }
        const timer = setTimeout(async () => {
            setIsSearching(true);
            try {
                const response = await fetch(`${API_ENDPOINTS.SEARCH}?q=${encodeURIComponent(searchTerm)}`);
                const data = await response.json();
                if (data.success && data.data) {
                    setSearchResults(data.data);
                } else {
                    setSearchResults([]);
                }
            } catch (error) {
                console.error("Search API Error:", error);
                setSearchResults([]);
            } finally {
                setIsSearching(false);
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [searchTerm]);

    React.useEffect(() => {
        if (!headerRef.current) return;

        const updateHeaderHeight = () => {
            document.documentElement.style.setProperty(
                "--site-header-height",
                `${headerRef.current.offsetHeight}px`
            );
        };

        updateHeaderHeight();
        const resizeObserver = new ResizeObserver(updateHeaderHeight);
        resizeObserver.observe(headerRef.current);
        window.addEventListener("resize", updateHeaderHeight);

        return () => {
            resizeObserver.disconnect();
            window.removeEventListener("resize", updateHeaderHeight);
        };
    }, []);

    const handleBackClick = () => {
        // If search is active, close the search view first
        if (isSearchActive) {
            setIsSearchActive(false);
            setSearchTerm(''); // Clear the search term
        } else {
            // Otherwise, navigate back
            navigate(-1);
        }
    };

    // --- Suggestion Box Component (API Driven) ---
    const SearchSuggestions = () => {
        if (!searchTerm.trim()) return null;

        return (
            <div className="position-absolute bg-white rounded shadow w-100 mt-1" style={{ top: '100%', left: 0, zIndex: '1050', maxHeight: '400px', overflowY: 'auto' }}>
                {isSearching ? (
                    <div className="p-3 text-center text-muted small">Searching...</div>
                ) : searchResults.length > 0 ? (
                    searchResults.map((item, idx) => {
                        let imagePath = "/placeholder.jpg";
                        if (item.images && item.images.length > 0) {
                            const img = item.images[0].image_path || item.images[0].file_path;
                            if (img) imagePath = `${ASSET_URL}${img.startsWith('/') ? img.substring(1) : img}`;
                        } else if (item.image) {
                            imagePath = `${ASSET_URL}${item.image.startsWith('/') ? item.image.substring(1) : item.image}`;
                        } else if (item.thumbnail) {
                            imagePath = `${ASSET_URL}${item.thumbnail.startsWith('/') ? item.thumbnail.substring(1) : item.thumbnail}`;
                        }

                        return (
                            <Link
                                to={`/product/${item.slug || item.id}`}
                                key={idx}
                                className="d-flex align-items-center px-3 py-2 text-decoration-none border-bottom"
                                style={{ cursor: "pointer", transition: "background 0.2s" }}
                                onClick={() => {
                                    setShowSearchDropdown(false);
                                    setIsSearchActive(false);
                                    setSearchTerm(item.name);
                                }}
                                onMouseEnter={(e) => e.currentTarget.style.backgroundColor = "#f8f9fa"}
                                onMouseLeave={(e) => e.currentTarget.style.backgroundColor = "transparent"}
                            >
                                <div className="flex-shrink-0" style={{ width: "35px", height: "35px" }}>
                                    <img src={imagePath} alt={item.name} className="w-100 h-100 object-fit-contain" />
                                </div>
                                <div className="ms-3 flex-grow-1 text-truncate">
                                    <div className="text-dark text-truncate" style={{ fontSize: "14px", fontWeight: "400" }}>{item.name}</div>
                                    {item.category_name && (
                                        <div className="text-primary" style={{ fontSize: "12px", marginTop: "1px" }}>
                                            in {item.category_name}
                                        </div>
                                    )}
                                </div>
                            </Link>
                        );
                    })
                ) : (
                    <div className="p-3 text-center text-muted small">No results found for "{searchTerm}"</div>
                )}
            </div>
        );
    };

    // --- Active Search Bar View ---
    if (isSearchActive) {
        return (
            <div className='bg-white sticky-top z-3'>
                <div className="d-flex align-items-center px-3 py-2 border-bottom">

                    {/* Back Arrow / Close Search */}
                    <BsArrowLeft size={24} onClick={handleBackClick} style={{ cursor: 'pointer' }} className="me-2 " />

                    {/* Search Input */}
                    <input
                        type="search"
                        id='product-search'
                        placeholder="Search for products, Brands and more"
                        className="form-control border-0 flex-grow-1"
                        autoFocus // Automatically focus the input when search opens
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                                console.log("Searching for:", searchTerm);
                                setIsSearchActive(false);
                            }
                        }}
                    />

                </div>
                {/* Search Suggestions Box */}
                {showSearchDropdown && searchTerm && <div className="position-relative w-100" ref={searchRef}><SearchSuggestions /></div>}
            </div>
        );
    }

    // --- Default Header View (from the image) ---
    return (
        <>
            <div ref={headerRef} className='theme shadow-sm position-fixed w-100 py-2 border-bottom px-0 px-lg-5' style={{ zIndex: '1030' }}>
                <div className="d-flex align-items-center justify-content-between p-2 ">
                    {/* LEFT SIDE: Back Arrow, Logo, Title */}
                    <div className="d-flex align-items-center gap-2 flex-grow-1">

                        <div className='d-flex d-lg-none align-items-center'>
                            {showBackButton && (
                                <BsArrowLeft
                                    size={24}
                                    onClick={handleBackClick}
                                    style={{ cursor: 'pointer' }}
                                    color='white'
                                    className='me-3'
                                />
                            )}
                            {/* Logo/Icon */}
                            <Link to="/" className="d-flex align-items-center">
                                <img src="/PrintwhiteLogo.png" alt="Logo" style={{ height: "24px", marginRight: '5px', objectFit: "contain" }} />
                            </Link>

                            {/* Page Title - Hidden on Product Details mobile page */}
                            {!isProductPage && (
                                <span className="fw-medium text-uppercase text-light text-truncate" style={{ fontSize: '15px' }}>
                                    {pageTitle}
                                </span>
                            )}
                        </div>
                        <div className='d-none d-lg-flex align-items-center gap-5 w-100'>
                            <Link to={'/'}>
                                <img src={logo || "/PrintLogo.png"} alt="PrintMont Logo" height={45} style={{ objectFit: "contain" }} onError={(e) => { e.target.src = "/PrintLogo.png"; }} />
                            </Link>
                            <div className="flex-grow-1 mx-4" style={{ maxWidth: '650px' }}>
                                <SearchBar />
                            </div>
                        </div>
                    </div>

                    {/* RIGHT SIDE: Icons (Search, Cart, Login) */}
                    <div className="">
                        <div className='d-flex align-items-center gap-3 px-3 d-lg-none'>
                            {/* Search Icon - Toggles the search state */}
                            <div onClick={() => setIsSearchActive(true)} style={{ cursor: 'pointer' }} className="text-dark">
                                <IoSearchSharp color='white' size={22} />
                            </div>

                            {isProductPage ? (
                                <>
                                    {/* Wishlist Heart Icon with Badge (Mock count: 4 to match Figma design exactly) */}
                                    <Link to="/user/wishlist" className="position-relative text-dark d-flex align-items-center">
                                        <FaHeart color='white' size={20} />
                                        <span className="position-absolute rounded-circle bg-white text-primary fw-bold d-flex align-items-center justify-content-center" 
                                              style={{ 
                                                  top: '-8px', 
                                                  right: '-8px', 
                                                  fontSize: '9px', 
                                                  width: '15px', 
                                                  height: '15px', 
                                                  border: '1px solid rgb(11, 83, 161)' 
                                              }}>
                                            4
                                        </span>
                                    </Link>

                                    {/* Cart Icon with Badge */}
                                    <Link to="/cart" className="position-relative text-dark d-flex align-items-center">
                                        <FaShoppingBag color='white' size={22} />
                                        {cartCount > 0 && (
                                            <span className="position-absolute rounded-circle bg-danger text-white fw-bold d-flex align-items-center justify-content-center" 
                                                  style={{ 
                                                      top: '-8px', 
                                                      right: '-8px', 
                                                      fontSize: '9px', 
                                                      width: '16px', 
                                                      height: '16px', 
                                                      border: '1px solid white' 
                                                  }}>
                                                {cartCount}
                                            </span>
                                        )}
                                    </Link>
                                </>
                            ) : (
                                <>
                                    {/* Cart Icon */}
                                    <Link to="/cart" className="position-relative text-dark d-flex align-items-center">
                                        <FaShoppingBag color='white' size={22} />
                                        {cartCount > 0 && (
                                            <span className="position-absolute rounded-circle bg-danger text-white fw-bold d-flex align-items-center justify-content-center" 
                                                  style={{ 
                                                      top: '-8px', 
                                                      right: '-8px', 
                                                      fontSize: '9px', 
                                                      width: '16px', 
                                                      height: '16px', 
                                                      border: '1px solid white' 
                                                  }}>
                                                {cartCount}
                                            </span>
                                        )}
                                    </Link>

                                    {/* Login/User Text */}
                                    <Link to="/login" className="text-light fw-medium text-decoration-none d-flex align-items-center gap-1 ">
                                        Login
                                    </Link>
                                </>
                            )}
                        </div>
                        <div className='d-none d-lg-flex align-items-center '>
                            <DropdownButton id="dropdown-button-dark-example2"  title={<>
                                <FaRegCircleUser size={20} className="me-1"/> 
                                {user ? (user.first_name || user.firstName || user.name || (user.email ? user.email.split('@')[0] : 'User')) : 'Buyer Name'}
                                </>} className="mt-2 custom-dropdown" data-bs-theme>
                                
                                <Dropdown.Item as={Link} to={user ? `/${usernamePath}/profile` : "/login"}>My Profile</Dropdown.Item>
                                <Dropdown.Item as={Link} to={user ? "/orders" : "/login"}>Orders</Dropdown.Item>
                                <Dropdown.Item as={Link} to="/track-order">Track your Orders</Dropdown.Item>
                                <Dropdown.Item as={Link} to={user ? "/printmont-coin" : "/login"}>Printmont Coins</Dropdown.Item>
                                {user ? (
                                    <>
                                        <Dropdown.Divider />
                                        <Dropdown.Item as="button" onClick={logout} className="text-danger">Log Out</Dropdown.Item>
                                    </>
                                ) : (
                                    <>
                                        <Dropdown.Divider />
                                        <Dropdown.Item as={Link} to="/login">Login / Signup</Dropdown.Item>
                                    </>
                                )}
                            </DropdownButton>
                        </div>
                    </div>
                </div>
            </div>
            {/* Categories section for product pages */}
            {shouldShowCategories && (
                <div className="d-none d-lg-block">
                    <Categories showImages={false} space="5px 0" bg="rgb(11, 83, 161)" color="white" isSticky={true} />
                </div>
            )}
            <div style={{ height: "var(--site-header-height, 65px)" }} className="d-none d-lg-block"></div>
            <div style={{ height: "var(--site-header-height, 55px)" }} className="d-block d-lg-none"></div>
        </>
    );
};

export default ProductPageHeader;