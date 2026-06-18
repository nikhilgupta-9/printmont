import React, { useState } from 'react';
import { IoSearchSharp } from "react-icons/io5";
import { BsArrowLeft, BsXLg } from "react-icons/bs"; // BsXLg is the close icon
import { GiShoppingCart } from "react-icons/gi";
import { FaRegCircleUser } from "react-icons/fa6";
import { FaHeart } from 'react-icons/fa';
import Dropdown from 'react-bootstrap/Dropdown';
import DropdownButton from 'react-bootstrap/DropdownButton';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import Categories from '../pages/category-list/Categories';
import { useCheckout } from '../../context/CheckoutContext';
import axios from 'axios';
import { API_ENDPOINTS, ASSET_URL } from '../../config/apiEndpoints';

const ProductPageHeader = ({ pageTitle = "Cart", showBackButton = true }) => {
    const navigate = useNavigate();
    const location = useLocation();
    const checkoutContext = useCheckout();
    const cartItems = checkoutContext ? checkoutContext.cartItems : [];
    
    const isProductPage = location.pathname.startsWith('/product');

    // State to toggle between the default header and the active search bar
    const [isSearchActive, setIsSearchActive] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [logo, setLogo] = useState(null);
    const headerRef = React.useRef(null);

    React.useEffect(() => {
        const fetchLogo = async () => {
            try {
                const response = await fetch(API_ENDPOINTS.LOGO);
                const data = await response.json();
                if (data.success && data.data.length > 0) {
                    let logoData = data.data.find(l => l.asset_type === "mobile_logo" && l.is_active === "1");
                    if (!logoData) {
                        logoData = data.data.find(l => l.asset_type === "desktop_logo" && l.is_active === "1");
                    }
                    if (!logoData) {
                        logoData = data.data.find(l => l.is_active === "1") || data.data[0];
                    }
                    const filePath = logoData.file_path.startsWith('/') ? logoData.file_path.substring(1) : logoData.file_path;
                    setLogo(`${ASSET_URL}${filePath}${logoData.file_name}`);
                }
            } catch (error) {
                console.error("Error fetching logo:", error);
            }
        };
        fetchLogo();
    }, []);

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

    // --- Mock Suggestion Box Component ---
    const SearchSuggestions = () => {
        const suggestions = ["T-Shirts for Men", "Blue T-Shirts", "Custom T-Shirts", "Men's V-Neck"];

        // Filter suggestions based on current search term
        const filteredSuggestions = searchTerm
            ? suggestions.filter(s => s.toLowerCase().includes(searchTerm.toLowerCase()))
            : suggestions;

        return (
            <div className="position-absolute w-100 bg-white shadow-sm pt-2" style={{ top: '60px', zIndex: '10' }}>
                <ul className="list-group list-group-flush">
                    {filteredSuggestions.length > 0 ? (
                        filteredSuggestions.map((suggestion, index) => (
                            <li
                                key={index}
                                className="list-group-item list-group-item-action d-flex align-items-center"
                                onClick={() => {
                                    setSearchTerm(suggestion);
                                }}
                                style={{ cursor: 'pointer' }}
                            >
                                <IoSearchSharp size={16} className="text-muted me-2" />
                                {suggestion}
                            </li>
                        ))
                    ) : (
                        <li className="list-group-item text-muted">No results found for "{searchTerm}"</li>
                    )}
                </ul>
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
                {searchTerm && <SearchSuggestions />}
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
                                {logo ? (
                                    <img src={logo} alt="Logo" style={{ height: "24px", marginRight: '5px', objectFit: "contain" }} />
                                ) : (
                                    <div style={{ height: "24px", width: "80px", marginRight: '5px' }} className="shimmer-bg"></div>
                                )}
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
                                <img src="/PrintLogo.png" alt="" height={45}/>
                            </Link>
                            <div className='border w-50 rounded light-bg-theme d-flex align-items-center gap-3 px-2'>
                                <button className='border-0'>
                                    <IoSearchSharp size={22} className='' />
                                </button>
                                <input type="text" placeholder='Search for products, Brands and more' className='light-bg-theme border-0  w-100 rounded p-1' />
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

                                    {/* Cart Icon with Badge (count from checkoutContext or fallback to 2 to match Figma) */}
                                    <Link to="/cart" className="position-relative text-dark d-flex align-items-center">
                                        <GiShoppingCart color='white' size={24} />
                                        <span className="position-absolute rounded-circle bg-white text-primary fw-bold d-flex align-items-center justify-content-center" 
                                              style={{ 
                                                  top: '-8px', 
                                                  right: '-8px', 
                                                  fontSize: '9px', 
                                                  width: '15px', 
                                                  height: '15px', 
                                                  border: '1px solid rgb(11, 83, 161)' 
                                              }}>
                                            {cartItems.length > 0 ? cartItems.length : 2}
                                        </span>
                                    </Link>
                                </>
                            ) : (
                                <>
                                    {/* Cart Icon */}
                                    <Link to="/cart" className="position-relative text-dark">
                                        <GiShoppingCart color='white' size={25} />
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
                                {<FaRegCircleUser size={20}/>} Buyer Name
                                </>} className="mt-2 custom-dropdown" data-bs-theme>
                                
                                <Dropdown.Item href="#/action-1 text-danger red" active>Action</Dropdown.Item>
                                <Dropdown.Item href="#/action-2">Another action</Dropdown.Item>
                                <Dropdown.Item href="#/action-3">Something else</Dropdown.Item>
                                <Dropdown.Divider />
                                <Dropdown.Item href="#/action-4">Separated link</Dropdown.Item>
                            </DropdownButton>
                        </div>
                    </div>
                </div>
            </div>
            {/* Hide categories section on mobile screen (< 992px) */}
            <div className="d-none d-lg-block">
                <Categories showImages={false} space="5px 0" bg="rgb(11, 83, 161)" color="white" isSticky={true} />
            </div>
            <div style={{ height: "var(--site-header-height, 65px)" }} className="d-none d-lg-block"></div>
            <div style={{ height: "var(--site-header-height, 55px)" }} className="d-block d-lg-none"></div>
        </>
    );
};

export default ProductPageHeader;