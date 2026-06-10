import React from 'react';
import PropTypes from 'prop-types';
import 'bootstrap/dist/css/bootstrap.min.css';

// 1. Accept the new `bgImage` prop in the function signature.
const ProductGrid = ({ title, products, bgImage }) => {
    
    // 2. Create a style object for the container.
    // If bgImage has a value, this object will set the background image.
    const containerStyle = {
        backgroundImage: bgImage ? `url(${bgImage})` : 'none',
        backgroundSize: 'contain',
        backgroundPosition: 'center',
        // Optional: Add some padding and rounded corners when an image is present.
        padding: bgImage ? '1rem' : '0',
        borderRadius: bgImage ? '0.375rem' : '0',
    };

    return (
        <div className="container-fluid pt-3 mt-lg-3 px-1 px-lg-0" style={containerStyle}>
            <h5 className="mb-1 mb-lg-3">{title}</h5>
            {/* The 'row' class provides the flex-wrap behavior, and we keep gap/padding adjustments minimal. */}
            <div className="row g-0  "> 
                {products.map((item) => (
                    <div
                        key={item.id}
                        className="col-4 col-md-3 col-lg-2 mb-0 d-flex justify-content-center"
                        style={{ maxWidth: '33.333%', }} // Default for sm
                    >
                        <div 
                           className="card text-center bd p-1" 
                           style={{ 
                               maxWidth: '100%', 
                               margin: '3px', 
                               flex: '1 1 0%',
                           }}>
                            <div className="w-100 ratio ratio-1x1 d-flex align-items-center justify-content-center overflow-hidden rounded-top bg-light" style={{ position: 'relative' }}>
                                <img src={item.image} alt={item.title} style={{ width: '100%', height: '100%', objectFit: 'contain', position: 'absolute', top: 0, left: 0 }} />
                            </div>
                            <a href="#" className='text-decoration-none'>
                                <div className="card-body p-1">
                                    <p className="card-title mb-1 txsm text-dark">{item.title}</p>
                                    <p className="card-text text-success fw-bold txex mb-0">{item.subTitle}</p>
                                </div>
                            </a>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

// 4. (Recommended) Add the new `bgImage` prop to your prop validation.
ProductGrid.propTypes = {
    title: PropTypes.string.isRequired,
    products: PropTypes.arrayOf(
        PropTypes.shape({
            id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
            image: PropTypes.string.isRequired,
            title: PropTypes.string.isRequired,
            subTitle: PropTypes.string,
        })
    ).isRequired,
    bgImage: PropTypes.string, // It's an optional string.
};

export default ProductGrid;