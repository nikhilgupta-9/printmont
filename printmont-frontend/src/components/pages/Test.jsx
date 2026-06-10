import React from 'react';
import { FaChevronRight } from 'react-icons/fa';

const Test = ({ title, featuredProduct, sideProducts }) => {
  return (
    <div className="container-fluid py-3">
      <div className="border bg-white rounded-3 p-3">
        {/* Header */}
        <div className="d-flex justify-content-between align-items-center mb-3">
          <h5 className="mb-0 fw-bold">{title}</h5>
          <FaChevronRight />
        </div>

        {/* Layout */}
        <div className="row">
          {/* Featured Product (Left) */}
          <div className="col-md-6 mb-3 mb-md-0 d-flex align-items-center justify-content-center">
            <div className="text-center">
              <img
                src={featuredProduct.image}
                alt={featuredProduct.title}
                className="img-fluid mb-2"
                style={{ maxHeight: 200, objectFit: 'contain' }}
              />
              <h6 className="fw-bold">{featuredProduct.title}</h6>
              <p className="mb-0">
                <span className="fw-bold text-dark me-2">₹{featuredProduct.price}</span>
                <del className="text-muted me-2">₹{featuredProduct.originalPrice}</del>
                <span className="text-success">{featuredProduct.discount}</span>
              </p>
            </div>
          </div>

          {/* Side Products (Right) */}
          <div className="col-md-6">
            <div className="row g-3">
              {sideProducts.map((product, index) => (
                <div className="col-12" key={index}>
                  <div className="d-flex align-items-center gap-3">
                    <img
                      src={product.image}
                      alt={product.title}
                      className="img-fluid"
                      style={{ width: 100, height: 100, objectFit: 'cover', borderRadius: '5px' }}
                    />
                    <div>
                      <h6 className="fw-bold mb-1">{product.title}</h6>
                      <p className="mb-0">
                        <span className="fw-bold text-dark me-2">₹{product.price}</span>
                        <del className="text-muted me-2">₹{product.originalPrice}</del>
                        <span className="text-success">{product.discount}</span>
                      </p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Test;
