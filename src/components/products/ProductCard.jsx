import React, { useState, useRef, useEffect } from 'react';
import { GoHeartFill } from "react-icons/go";
import { BsShieldCheck } from 'react-icons/bs';
import { TfiAgenda } from 'react-icons/tfi';
import './Product.css'
import { Link } from 'react-router';

// Utility map for displaying sizes
const SIZES = {
    'S': 'S', 'M': 'M', 'L': 'L', 'XL': 'XL',
    28: '28', 30: '30', 32: '32', 34: '34', 36: '36',
    8: '8', 9: '9', 10: '10', 11: '11', 'XS': 'XS'
};

const generateSlug = (name) => {
    return name ? name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') : 'product';
};

const ProductCard = ({ product }) => {
    const [isWished, setIsWished] = useState(false);
    const [currentImageIndex, setCurrentImageIndex] = useState(0);
    const [isHovering, setIsHovering] = useState(false);
    const intervalRef = useRef(null);

    // Helper function to format price
    const formatPrice = (price) => {
        return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', minimumFractionDigits: 0 }).format(price).replace('₹', '');
    };

    const toggleWishlist = (e) => {
        e.stopPropagation();
        setIsWished(!isWished);
    };

    // --- HOVER SWIPE LOGIC ---

    const startSwipe = () => {
        // 1. Set hovering state
        setIsHovering(true);
        if (!product.image || product.image.length <= 1) return;
        if (intervalRef.current) clearInterval(intervalRef.current);
        intervalRef.current = setInterval(() => {
            setCurrentImageIndex(prevIndex =>
                (prevIndex + 1) % product.image.length
            );
        }, 1000);
    };

    const stopSwipe = () => {
        // Clear the interval
        if (intervalRef.current) {
            clearInterval(intervalRef.current);
            intervalRef.current = null;
        }
        setIsHovering(false);
        setCurrentImageIndex(0);
    };

    useEffect(() => {
        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, []);
    const imageSrc =
        (product.image && product.image[currentImageIndex]) ||
        "https://placehold.co/400x550/cccccc/000?text=Image+Missing";

    // --- YOUR ORIGINAL STYLES ---
    const cardStyle = {
        border: '1px solid #e0e0e0',
        borderRadius: '8px',
        boxShadow: '0 1px 3px rgba(0, 0, 0, 0.1)',
        transition: 'box-shadow 0.3s ease-in-out',
        overflow: 'hidden',
        position: 'relative',
        height: '100%',
        display: 'flex',
        flexDirection: 'column',
        cursor: 'pointer',
    };

    const heartStyle = {
        Color: isWished ? 'red' : '#aaa',
        fill: isWished ? 'red' : 'gray',
        opacity: isWished ? '100%' : '50%'
    };

    const badgeStyle = {
        display: 'inline-block',
        padding: '4px 6px',
        borderRadius: '4px',
        fontSize: '0.5rem',
        fontWeight: '600',
        backgroundColor: '#f1f1f1',
        color: '#333',
        border: '1px solid #ddd',
        marginRight: '4px',
        marginBottom: '4px',
        textTransform: 'uppercase',
    };

    const assuredBadgeStyle = {
        display: 'flex',
        alignItems: 'center',
        padding: '4px 8px',
        borderRadius: '12px',
        fontSize: '0.7rem',
        fontWeight: '600',
        backgroundColor: '#d1fae5',
        color: '#059669',
    };
    // ----------------------------

    const productSlug = generateSlug(product.title);
    const productUrl = `/${productSlug}-p${product.id || '1'}`;

    return (
        <>
            <Link to={productUrl}  // dynamic product link
                 className="text-decoration-none text-dark"
                 style={{ flexGrow: 1 }}>
                <div
                    className="product-card"
                    onMouseEnter={startSwipe}
                    onMouseLeave={stopSwipe}
                >
                    <div
                        className="product-card"
                        style={{ cardStyle }}
                        // Apply hover handlers to the main card container
                        onMouseEnter={startSwipe}
                        onMouseLeave={stopSwipe}
                    >

                        {/* Product Image Container */}
                        <div style={{ overflow: 'hidden', position: 'relative' }}>

                            {/* Wishlist Heart Icon */}
                            <button
                                onClick={toggleWishlist}
                                className='heart-button-style text-muted bg-muted z-1'
                                style={{ position: 'absolute', top: '10px', right: '10px', border: 'none', background: 'none' }}
                            >
                                <GoHeartFill size={28} style={heartStyle} />
                            </button>

                            {/* The Image (Uses dynamic source and lazy loading) */}
                            <img
                                src={imageSrc}
                                alt={product.title}
                                style={{
                                    width: '100%',
                                    height: 'auto',
                                    objectFit: 'cover',
                                    display: 'block',
                                    transition: 'opacity 0.3s ease-in-out',
                                }}
                                className='zoom-hover'
                                onError={(e) => { e.target.onerror = null; e.target.src = "https://placehold.co/400x550/cccccc/000?text=Image+Missing"; }}
                                loading={'lazy'}
                            />

                            {/* Optional: Image Dots Indicator (Only shows when hovering and swiping) */}
                            {isHovering && product.image && product.image.length > 1 && (
                                <div style={{
                                    position: 'absolute',
                                    bottom: '8px',
                                    left: '50%',
                                    transform: 'translateX(-50%)',
                                    zIndex: 10,
                                    display: 'flex'
                                }}>
                                    {product.image.map((_, index) => (
                                        <span
                                            key={index}
                                            style={{
                                                height: '5px',
                                                width: '5px',
                                                backgroundColor: index === currentImageIndex ? '#333' : 'rgba(255,255,255,0.8)',
                                                border: '1px solid #333',
                                                borderRadius: '50%',
                                                margin: '0 3px',
                                            }}
                                        ></span>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Product Details (Your original layout) */}
                        <div style={{ padding: '12px', flexGrow: 1, display: 'flex', flexDirection: 'column' }}>

                            {/* Sponsored Label */}
                            {product.sponsored && (
                                <small style={{ color: '#666', display: 'flex', alignItems: 'center', fontSize: '0.7rem', fontWeight: 500 }}>
                                    <TfiAgenda size={12} style={{ marginRight: '4px', color: '#999' }} />
                                    Sponsored
                                </small>
                            )}

                            {/* Brand and Assured */}
                            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', }}>
                                <span style={{ fontWeight: '700', textTransform: 'uppercase', fontSize: '0.8rem', color: '#111' }}>{product.brand}</span>
                                {product.assured && (
                                    <div style={assuredBadgeStyle}>
                                        <BsShieldCheck size={14} style={{ marginRight: '3px' }} />
                                        Assured
                                    </div>
                                )}
                            </div>

                            {/* Product Title */}
                            <h4 className='pc-title'>
                                {product.title}
                            </h4>

                            {/* Price Section */}
                            <div className='mb-2 d-flex align-items-baseline flex-wrap'>
                                <span style={{ fontWeight: '800', fontSize: '0.8rem', color: '#111', marginRight: '8px' }}>
                                    ₹{formatPrice(product.discountedPrice)}
                                </span>
                                <small style={{ color: '#888', textDecoration: 'line-through', marginRight: '4px', fontSize: '0.70rem' }}>
                                    ₹{formatPrice(product.originalPrice)}
                                </small>
                                <span style={{ color: '#ef4444', fontWeight: '700', fontSize: '0.70rem' }}>
                                    ({product.discountPercent}% OFF)
                                </span>
                            </div>

                            {/* Sizes */}
                            <div className='d-flex flex-wrap align-items-center mt-auto '>
                                <span style={{ color: '#666', fontWeight: '600', fontSize: '0.75rem', marginRight: '4px' }} className='border'>Sizes:</span>
                                {product.sizes.map((size) => (
                                    <span className='sizestyle' key={size}>{size}</span>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </Link>

        </>
    );
};

export default ProductCard;