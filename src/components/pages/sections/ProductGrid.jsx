import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import 'bootstrap/dist/css/bootstrap.min.css';
import { Link } from 'react-router-dom';
import { getProductUrl } from '../../../utils/seo';

const ProductGrid = ({ title, products: initialProducts = [], apiUrl, bgImage }) => {
    const [products, setProducts] = useState(initialProducts);
    const [loading, setLoading] = useState(!!apiUrl);

    useEffect(() => {
        if (!apiUrl) {
            setProducts(initialProducts);
            setLoading(false);
            return;
        }

        const fetchProducts = async () => {
            try {
                setLoading(true);
                const res = await fetch(apiUrl);
                if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
                const data = await res.json();
                
                const rawProducts = data && data.success && Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
                
                const formattedProducts = rawProducts.map(p => {
                    let primaryImg = '/default-img.jpg';
                    if (Array.isArray(p.images) && p.images.length > 0) {
                        const primary = p.images.find(img => img.is_primary) || p.images[0];
                        primaryImg = primary.image_url;
                    } else if (p.primary_image) {
                        primaryImg = p.primary_image;
                    } else if (p.thumbnail) {
                        primaryImg = p.thumbnail;
                    } else if (p.image) {
                        primaryImg = p.image;
                    }
                    
                    const price = parseFloat(p.price) || 0;
                    const discPrice = parseFloat(p.discount_price) || 0;
                    let subTitleText = '';
                    if (discPrice > 0) {
                        const maxVal = Math.max(price, discPrice);
                        const minVal = Math.min(price, discPrice);
                        if (maxVal > minVal) {
                            subTitleText = `${Math.round(((maxVal - minVal) / maxVal) * 100)}% Off`;
                        }
                    } else {
                        subTitleText = `₹${price}`;
                    }

                    return {
                        id: p.id,
                        title: p.name || p.title || '',
                        image: primaryImg,
                        subTitle: subTitleText
                    };
                });

                setProducts(formattedProducts.slice(0, 6)); // Grid has columns: 6 cols maximum
            } catch (err) {
                console.error("Error fetching ProductGrid products:", err);
            } finally {
                setLoading(false);
            }
        };
        fetchProducts();
    }, [apiUrl, initialProducts]);

    const containerStyle = {
        backgroundImage: bgImage ? `url(${bgImage})` : 'none',
        backgroundSize: 'contain',
        backgroundPosition: 'center',
        padding: bgImage ? '1rem' : '0',
        borderRadius: bgImage ? '0.375rem' : '0',
    };

    if (loading) {
        return (
            <div className="container-fluid px-1 px-lg-0" style={containerStyle}>
                <h5 className="mb-1 mb-lg-3">{title}</h5>
                <div className="row g-0">
                    {[1, 2, 3, 4, 5, 6].map((idx) => (
                        <div key={idx} className="col-4 col-md-3 col-lg-2 mb-0 d-flex justify-content-center" style={{ maxWidth: '33.333%' }}>
                            <div className="card text-center bd p-1 w-100" style={{ margin: '3px' }}>
                                <div className="shimmer-bg skeleton-img w-100 mb-2" style={{ aspectRatio: '1/1' }} />
                                <div className="shimmer-bg skeleton-title w-75 mx-auto" />
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="container-fluid px-1 px-lg-0" style={containerStyle}>
            <h5 className="mb-1 mb-lg-3">{title}</h5>
            <div className="row g-0"> 
                {products.map((item) => (
                    <div
                        key={item.id}
                        className="col-4 col-md-3 col-lg-2 mb-0 d-flex justify-content-center"
                        style={{ maxWidth: '33.333%', }}
                    >
                        <div 
                           className="card text-center bd p-1" 
                           style={{ 
                               maxWidth: '100%', 
                               margin: '3px', 
                               flex: '1 1 0%',
                               backgroundColor: 'white',
                           }}>
                            <div className="w-100 ratio ratio-1x1 d-flex align-items-center justify-content-center overflow-hidden rounded-top bg-light" style={{ position: 'relative' }}>
                                <img src={item.image} alt={item.title} style={{ width: '100%', height: '100%', objectFit: 'contain', position: 'absolute', top: 0, left: 0 }} />
                            </div>
                            <Link to={getProductUrl(item)} className='text-decoration-none'>
                                <div className="card-body p-1">
                                    <p className="card-title mb-1 txsm text-dark">{item.title}</p>
                                    <p className="card-text text-success fw-bold txex mb-0">{item.subTitle}</p>
                                </div>
                            </Link>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

ProductGrid.propTypes = {
    title: PropTypes.string.isRequired,
    products: PropTypes.arrayOf(
        PropTypes.shape({
            id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
            image: PropTypes.string.isRequired,
            title: PropTypes.string.isRequired,
            subTitle: PropTypes.string,
        })
    ),
    apiUrl: PropTypes.string,
    bgImage: PropTypes.string,
};

export default ProductGrid;