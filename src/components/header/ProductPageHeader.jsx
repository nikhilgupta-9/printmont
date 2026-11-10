import React, { useState } from 'react';
import { IoSearchSharp } from "react-icons/io5";
import { BsArrowLeft, BsXLg } from "react-icons/bs"; // BsXLg is the close icon
import { GiShoppingCart } from "react-icons/gi";
import { FaRegCircleUser } from "react-icons/fa6";
import { FaHeart, FaShoppingBag, FaBell } from 'react-icons/fa';
import { GoHeart } from "react-icons/go";
import { BsCart4 } from "react-icons/bs";
import { PiDotsThreeOutlineVerticalFill, PiHeadsetBold } from "react-icons/pi";
import { LuChartNoAxesCombined } from "react-icons/lu";
import { RiDownload2Line } from "react-icons/ri";
import { IoChevronDown } from "react-icons/io5";
import { Navbar, Container, Nav } from "react-bootstrap";
import Dropdown from 'react-bootstrap/Dropdown';
import DropdownButton from 'react-bootstrap/DropdownButton';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import Categories from '../pages/category-list/Categories';
import SearchBar from '../search/SearchBar';
import LoginDropdown from './LoginDropdown';
import HeaderPreferencesDropdown from './HeaderPreferencesDropdown';
import { useCheckout } from '../../context/CheckoutContext';
import { useAuth } from '../../context/AuthContext';
import useAccountNavigation from '../../hooks/useAccountNavigation';
import { useWishlist } from '../../context/WishlistContext';
import { useLocationContext } from '../../context/LocationContext';
import axios from 'axios';
import { API_ENDPOINTS, ASSET_URL, assetUrl } from '../../config/apiEndpoints';
import './Header.css';

const ProductPageHeader = ({ 
    pageTitle = "Cart", 
    showBackButton = true, 
    showCategories, 
    showImages = false, 
    isSticky = true, 
    bg, 
    color,
    desktopDesign = 'design1',
    mobileDesign = 'design1'
}) => {
    const navigate = useNavigate();
    const location = useLocation();
    const checkoutContext = useCheckout();
    const { wishlistCount } = useWishlist();
    const { user, logout, getUsernamePath } = useAuth();
    const { accountPath, goToAccount } = useAccountNavigation();
    const { location: deliveryLocation, openLocationModal } = useLocationContext();
    const usernamePath = getUsernamePath();
    const cartItems = checkoutContext ? checkoutContext.cartItems : [];
    const cartCount = cartItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
    
    const isProductPage = location.pathname.startsWith('/product');
    const isInfoOrPolicyPage = location.pathname.includes('policy') || 
                               location.pathname.includes('terms') || 
                               location.pathname.includes('privacy') || 
                               location.pathname.includes('shipping') || 
                               location.pathname.includes('refund') ||
                               location.pathname.includes('bulk');
    const isContactPage = location.pathname.startsWith('/contact');
    const isBlogPage = location.pathname.startsWith('/blog');
    const isAffiliatePage = location.pathname.startsWith('/affiliate');
    const isBusinessSolutionsPage = location.pathname.startsWith('/business-solutions');
    const isSitemapPage = location.pathname.startsWith('/sitemap');
    const isSecurityPage = location.pathname.startsWith('/security');
    const isCareersPage = location.pathname.startsWith('/careers');
    const isFaqPage = location.pathname.startsWith('/faq');
    const isHelpCenterPage = location.pathname.startsWith('/help-center') || location.pathname === '/help' || location.pathname.startsWith('/help/');
    const isCoinsPage = location.pathname.startsWith('/printmont-coin') || location.pathname.startsWith('/wallet');
    const isQuickLinksPage = location.pathname.startsWith('/quick-links');
    const isAuthPage = location.pathname.startsWith('/login') || 
                       location.pathname.startsWith('/signup') || 
                       location.pathname.startsWith('/register') || 
                       location.pathname.startsWith('/forgot-password');
    const isPolicyPage = location.pathname.includes('policy') || 
                         location.pathname.includes('terms') || 
                         location.pathname.includes('privacy') || 
                         location.pathname.includes('shipping') || 
                         location.pathname.includes('refund');
    const isCategoryPage = location.pathname.startsWith('/category');
    const isAllProductsPage = location.pathname.startsWith('/allproducts');
    const shouldShowCategories = (isAuthPage || isSitemapPage || isSecurityPage) ? false : (
        showCategories !== undefined 
            ? Boolean(showCategories) 
            : (!isInfoOrPolicyPage || isContactPage || isBlogPage || isAffiliatePage || isPolicyPage || isBusinessSolutionsPage || isCareersPage || isFaqPage || isHelpCenterPage || isCoinsPage || isQuickLinksPage || isCategoryPage || isAllProductsPage || isProductPage)
    );

    // State to toggle between the default header and the active search bar
    const [isSearchActive, setIsSearchActive] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [showSearchDropdown, setShowSearchDropdown] = useState(false);
    const [searchResults, setSearchResults] = useState([]);
    const [isSearching, setIsSearching] = useState(false);
    const [showPreferences, setShowPreferences] = useState(false);
    const [showLoginDropdown, setShowLoginDropdown] = useState(false);
    const [logo, setLogo] = useState("/PrintLogo.png");
    const headerRef = React.useRef(null);
    const searchRef = React.useRef(null);
    const preferenceRef = React.useRef(null);
    const loginRef = React.useRef(null);

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
                        setLogo(assetUrl(filePath + logoData.file_name));
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
                const response = await fetch(`${API_ENDPOINTS.SEARCH}?type=suggestions&q=${encodeURIComponent(searchTerm)}`);
                const data = await response.json();
                // search-api.php returns { success, query, suggestions: { products, categories, ... } }
                if (data.success && Array.isArray(data.suggestions?.products)) {
                    setSearchResults(data.suggestions.products);
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
            if (!headerRef.current) return;
            document.documentElement.style.setProperty(
                "--site-header-height",
                `${headerRef.current.getBoundingClientRect().height}px`
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

    // Desktop category bar: hides while scrolling down, comes back on scroll up / at top
    // (same behaviour as the home page category bar).
    const [showCategoryBar, setShowCategoryBar] = useState(true);
    const lastScrollYRef = React.useRef(0);
    const scrollFrameRef = React.useRef(null);

    React.useEffect(() => {
        setShowCategoryBar(true);
        lastScrollYRef.current = window.scrollY;
    }, [location.pathname]);

    React.useEffect(() => {
        if (!shouldShowCategories) return undefined;

        const handleScroll = () => {
            if (scrollFrameRef.current) return;
            scrollFrameRef.current = window.requestAnimationFrame(() => {
                const currentY = window.scrollY;
                const threshold = 150;

                if (currentY <= 10) {
                    setShowCategoryBar(true);
                } else if (currentY > lastScrollYRef.current && currentY > threshold) {
                    setShowCategoryBar(false);
                } else if (currentY < lastScrollYRef.current) {
                    setShowCategoryBar(true);
                }

                lastScrollYRef.current = currentY;
                scrollFrameRef.current = null;
            });
        };

        lastScrollYRef.current = window.scrollY;
        window.addEventListener("scroll", handleScroll, { passive: true });
        return () => {
            window.removeEventListener("scroll", handleScroll);
            if (scrollFrameRef.current) {
                window.cancelAnimationFrame(scrollFrameRef.current);
                scrollFrameRef.current = null;
            }
        };
    }, [shouldShowCategories]);

    const handleBackClick = () => {
        // If search is active, close the search view first
        if (isSearchActive) {
            setIsSearchActive(false);
            setSearchTerm(''); // Clear the search term
            return;
        }

        // If on /cart or /checkout with an active checkout step > 0 (e.g. on Address or Summary), navigate back one step
        if ((location.pathname.startsWith('/cart') || location.pathname.startsWith('/checkout')) && checkoutContext?.checkoutStep > 0) {
            checkoutContext.setCheckoutStep(checkoutContext.checkoutStep - 1);
            window.scrollTo(0, 0);
            return;
        }

        // If on edit profile page, navigate back to profile hub
        if (location.pathname.includes('profile-edit')) {
            navigate(`/${usernamePath}/profile`);
            return;
        }

        // If on account subpages, navigate back to profile hub
        if (location.pathname.includes('manage-address') || location.pathname.includes('change-password') || location.pathname.includes('pan-gst') || location.pathname.includes('pan-card')) {
            navigate(`/${usernamePath}/profile`);
            return;
        }

        // If on signup or register page, go back to login
        if (location.pathname.startsWith('/signup') || location.pathname.startsWith('/register')) {
            navigate('/login');
            return;
        }

        // If on forgot-password page, go back to login
        if (location.pathname.startsWith('/forgot-password')) {
            navigate('/login');
            return;
        }

        // If on auth pages (login), return to origin page, not loop into protected redirectParam
        if (isAuthPage) {
            const lastPage = sessionStorage.getItem('pm_last_visited_page');
            const isProtected = (url) => url && (url.startsWith('/user') || url.includes('profile-edit'));

            if (window.history.length > 1) {
                navigate(-1);
                return;
            }

            if (
                lastPage &&
                lastPage !== location.pathname &&
                !lastPage.startsWith('/login') &&
                !lastPage.startsWith('/signup') &&
                !lastPage.startsWith('/register') &&
                !lastPage.startsWith('/forgot-password') &&
                (!isProtected(lastPage) || user)
            ) {
                navigate(lastPage, { replace: true });
                return;
            }

            navigate('/', { replace: true });
            return;
        }

        // Check if redirect query param or location state exists
        const searchParams = new URLSearchParams(location.search);
        const redirectParam = searchParams.get('redirect') || location.state?.from;
        if (redirectParam && redirectParam !== location.pathname) {
            navigate(redirectParam);
            return;
        }

        // Check tracked origin page in sessionStorage
        const lastPage = sessionStorage.getItem('pm_last_visited_page');
        if (lastPage && lastPage !== location.pathname && !lastPage.startsWith('/login') && !lastPage.startsWith('/signup')) {
            navigate(lastPage);
            return;
        }

        // Fallback to browser history if available
        if (window.history.length > 1) {
            navigate(-1);
        } else {
            navigate('/');
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
                            if (img) imagePath = assetUrl(img.startsWith('/') ? img.substring(1) : img);
                        } else if (item.image) {
                            imagePath = assetUrl(item.image.startsWith('/') ? item.image.substring(1) : item.image);
                        } else if (item.thumbnail) {
                            imagePath = assetUrl(item.thumbnail.startsWith('/') ? item.thumbnail.substring(1) : item.thumbnail);
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
            <div className='bg-white sticky-top z-3 px-3 py-2 border-bottom d-flex align-items-center gap-2 shadow-sm'>
                {/* Back Arrow / Close Search */}
                <BsArrowLeft size={24} onClick={handleBackClick} style={{ cursor: 'pointer' }} className="text-dark flex-shrink-0" />
                <div className="flex-grow-1">
                    <SearchBar isMobileOverlay={true} onCloseMobile={() => setIsSearchActive(false)} />
                </div>
            </div>
        );
    }

    // --- Default Header View ---
    return (
        <>
            <div ref={headerRef} className='position-fixed top-0 start-0 w-100 pm-inner-header-fixed' style={{ zIndex: '1030' }}>
                {/* 1. MOBILE VIEW NAVBAR (Deep Blue #0a54a0) */}
                <div className="d-block d-lg-none header-inner-mobile-wrap" style={{ minHeight: "56px" }}>
                    <div className='d-flex align-items-center w-100 justify-content-between'>
                        <div className="d-flex align-items-center">
                            {showBackButton && (
                                <button
                                    type="button"
                                    onClick={handleBackClick}
                                    onTouchEnd={(e) => {
                                        e.preventDefault();
                                        handleBackClick();
                                    }}
                                    className="btn btn-link p-0 text-white border-0 d-flex align-items-center justify-content-right gap-1"
                                    style={{ 
                                        minWidth: '44px', 
                                        minHeight: '44px', 
                                        textDecoration: 'none',
                                        touchAction: 'manipulation',
                                        WebkitTapHighlightColor: 'rgba(255, 255, 255, 0.2)'
                                    }}
                                    title="Go Back"
                                    aria-label="Go Back"
                                >
                                    <BsArrowLeft
                                        size={24}
                                        color='#ffffff'
                                    />
                                </button>
                            )}
                            {/* Mobile White PM Icon Logo */}
                            <Link to="/" className="d-flex align-items-center me-2">
                                <img 
                                    src="/pm-white-icon.png" 
                                    alt="Printmont Logo" 
                                    style={{ width: "32px", height: "32px", borderRadius: "50%", objectFit: "contain" }} 
                                    onError={(e) => { e.target.src = "/PrintLogo.png"; }} 
                                />
                            </Link>

                            {/* Page Title */}
                            {!isProductPage && (
                                <span className="fw-bold text-white text-truncate ms-1" style={{ fontSize: '17px', letterSpacing: '0.2px' }}>
                                    {pageTitle}
                                </span>
                            )}
                        </div>

                        {/* Mobile Right: Wishlist, Cart, Login */}
                        <div className="d-flex align-items-center gap-3">
                            {!isQuickLinksPage && !isPolicyPage && !isAuthPage && (
                                <>
                                    {/* Wishlist */}
                                    <Link to={`/${usernamePath}/wishlist`} className="text-white text-decoration-none position-relative d-flex align-items-center" title="Wishlist">
                                        <GoHeart color="#ffffff" size={21} />
                                        <span className="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style={{ fontSize: '9px', padding: '2px 4px', lineHeight: 1 }}>
                                            {wishlistCount || 0}
                                        </span>
                                    </Link>

                                    {/* Cart */}
                                    <Link to="/cart" className="text-white text-decoration-none position-relative d-flex align-items-center" title="Cart">
                                        <BsCart4 color="#ffffff" size={20} />
                                        <span className="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style={{ fontSize: '9px', padding: '2px 4px', lineHeight: 1 }}>
                                            {cartCount || 0}
                                        </span>
                                    </Link>
                                </>
                            )}

                            {/* Login / Profile */}
                            <Link to={accountPath} onClick={goToAccount} className="text-white text-decoration-none d-flex align-items-center" title={user ? "My Account" : "Login"}>
                                <FaRegCircleUser color='#ffffff' size={21} />
                            </Link>
                        </div>
                    </div>
                </div>

                {/* 2. DESKTOP VIEW NAVBAR - Clean White enclosed in Container like on Home Page Navbar */}
                <div className="d-none d-lg-block container-fluid bg-white fnp-main-navbar p-0 position-relative" style={{ zIndex: 2 }}>
                    <Navbar expand="lg" className="border-bottom py-2 container-fluid px-4 px-xl-5">
                        <Container className="d-flex align-items-center justify-content-between py-1" style={{ maxWidth: '1440px' }}>
                            {/* Brand Logo, Vertical Divider & Exact FNP Location Block */}
                            <div className="d-flex align-items-center me-2">
                                <Navbar.Brand as={Link} to="/" className="m-0 p-0 d-flex flex-column">
                                    <img 
                                        src={logo || "/PrintLogo.png"} 
                                        alt="PrintMont Logo" 
                                        style={{ height: "42px", objectFit: "contain" }} 
                                        onError={(e) => { e.target.src = "/PrintLogo.png"; }}
                                    />
                                </Navbar.Brand>

                                {/* Vertical Divider Line */}
                                <div style={{ width: "1px", height: "36px", backgroundColor: "#e2e8f0", margin: "0 16px" }} />

                                {/* Location Trigger (Flag + Deliver to + City / Pincode) */}
                                <div 
                                    className="d-flex align-items-center text-nowrap pm-location-trigger"
                                    onClick={openLocationModal}
                                    title="Click to select delivery location"
                                >
                                    {/* Indian Flag */}
                                    <span 
                                        className="d-inline-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" 
                                        style={{ 
                                            width: "22px", 
                                            height: "15px", 
                                            borderRadius: "2.5px", 
                                            boxShadow: "0 1px 2px rgba(0,0,0,0.12)",
                                            border: "1px solid rgba(0,0,0,0.08)"
                                        }}
                                    >
                                        <svg viewBox="0 0 640 480" width="100%" height="100%">
                                            <path fill="#f93" d="M0 0h640v160H0z"/>
                                            <path fill="#fff" d="M0 160h640v160H0z"/>
                                            <path fill="#128807" d="M0 320h640v160H0z"/>
                                            <g transform="matrix(3.2 0 0 3.2 320 240)">
                                                <circle r="20" fill="#008"/>
                                                <circle r="17.5" fill="#fff"/>
                                                <circle r="3.5" fill="#008"/>
                                                <g id="d">
                                                    <g id="c">
                                                        <g id="b">
                                                            <g id="a">
                                                                <path fill="#008" d="M0-17.5L.6-3.5 0 0l-.6-3.5z"/>
                                                            </g>
                                                            <use href="#a" transform="rotate(15)"/>
                                                        </g>
                                                        <use href="#b" transform="rotate(30)"/>
                                                    </g>
                                                    <use href="#c" transform="rotate(60)"/>
                                                </g>
                                                <use href="#d" transform="rotate(120)"/>
                                                <use href="#d" transform="rotate(240)"/>
                                            </g>
                                        </svg>
                                    </span>

                                    {/* Text Column */}
                                    <div className="d-flex flex-column text-start" style={{ lineHeight: "1.2" }}>
                                        <span style={{ fontSize: "11px", fontWeight: "600", color: "#64748b", letterSpacing: "0.2px", textTransform: "uppercase" }}>
                                            Deliver to
                                        </span>
                                        {deliveryLocation?.is_set ? (
                                            <span 
                                                className="d-flex align-items-center gap-1" 
                                                style={{ fontSize: "12.5px", fontWeight: "700", color: "#00529b" }}
                                            >
                                                <span className="text-truncate" style={{ maxWidth: "120px" }}>
                                                    {deliveryLocation.city_name}{deliveryLocation.pincode ? `, ${deliveryLocation.pincode}` : ''}
                                                </span>
                                                <IoChevronDown size={11} style={{ color: "#00529b" }} />
                                            </span>
                                        ) : (
                                            <span 
                                                className="d-flex align-items-center gap-1" 
                                                style={{ fontSize: "12px", fontWeight: "600", color: "#ea580c" }}
                                            >
                                                Select Location
                                                <IoChevronDown size={11} style={{ color: "#ea580c" }} />
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Centered Wide Flipkart Search Bar */}
                            <div className="flex-grow-1 mx-3 mx-xl-4 fnp-search-wrapper">
                                <SearchBar />
                            </div>

                            {/* Action Icons & Links (Flipkart Layout) */}
                            <Nav className="d-flex align-items-center gap-3 gap-xl-4 flex-row flex-nowrap">
                                {/* Wishlist */}
                                <Link
                                    to={`/${usernamePath}/wishlist`}
                                    className="fnp-header-action-item"
                                    title="My Wishlist"
                                >
                                    <div className="position-relative d-flex align-items-center">
                                        <GoHeart size={21} color="#0b53a1" />
                                        {wishlistCount > 0 && (
                                            <span className="fnp-badge-counter">{wishlistCount}</span>
                                        )}
                                    </div>
                                    <span className="fnp-header-action-text">Wishlist</span>
                                </Link>

                                {/* Cart */}
                                <Link
                                    to="/cart"
                                    className="fnp-header-action-item"
                                    title="Shopping Cart"
                                >
                                    <div className="position-relative d-flex align-items-center">
                                        <BsCart4 size={20} color="#0b53a1" />
                                        {cartCount > 0 && (
                                            <span className="fnp-badge-counter">{cartCount}</span>
                                        )}
                                    </div>
                                    <span className="fnp-header-action-text">Cart</span>
                                </Link>

                                {/* User Sign In / Account Dropdown */}
                                <div
                                    className="d-flex flex-column align-items-center gap-1 nav-link text-dark p-0"
                                    ref={loginRef}
                                    onMouseEnter={() => setShowLoginDropdown(true)}
                                    onMouseLeave={() => setShowLoginDropdown(false)}
                                    style={{ cursor: "pointer" }}
                                >
                                    <LoginDropdown isHovered={showLoginDropdown} />
                                </div>

                                {/* Three-Dots More Preferences Dropdown */}
                                <HeaderPreferencesDropdown />
                            </Nav>

                        </Container>
                    </Navbar>
                </div>

                {/* Category Navbar - Theme Blue Background with White Text */}
                {shouldShowCategories && (
                    <div
                        className="d-none d-lg-block w-100 shadow-sm position-relative"
                        style={{
                            backgroundColor: "#0b53a1",
                            zIndex: 1,
                            transform: showCategoryBar ? "translateY(0)" : "translateY(-100%)",
                            transition: "transform 0.3s ease-in-out",
                            pointerEvents: showCategoryBar ? "auto" : "none"
                        }}
                    >
                        <Categories 
                            showImages={showImages} 
                            space="0" 
                            bg="#0b53a1" 
                            color="#ffffff" 
                            isSticky={false} 
                        />
                    </div>
                )}
            </div>

            <div style={{ height: "var(--site-header-height, 104px)" }} className="d-none d-lg-block" aria-hidden="true"></div>
            <div style={{ height: "var(--site-header-height, 56px)" }} className="d-block d-lg-none" aria-hidden="true"></div>
        </>
    );
};

export default ProductPageHeader;