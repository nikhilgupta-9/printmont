import React from 'react';
import { Link } from 'react-router-dom';

const IconX = () => {
  return (
    <>
      <div className="feature-strip bg-light py-3 bg-secondary-emphasis">
        <div className="container">
          <div className="row text-center text-md-start justify-content-between">

            {/* Feature 1 */}
            <div className="col-4 col-sm-4 col-md-3 d-flex align-items-center mb-3 mb-md-0">
              <Link to="/shipping" className="d-flex text-decoration-none text-dark align-items-center">
                <img src="/free.png" alt="Free Shipping" className="me-2 feature-icon" />
                <div>
                  <div className="fw-bold">Free Shipping</div>
                  <div className="text-muted small">No one rejects</div>
                </div>
              </Link>
            </div>

            {/* Feature 2 */}
            <div className="col-4 col-sm-4 col-md-3 d-flex align-items-center mb-3 mb-md-0">
              <Link to="/payment-security" className="d-flex text-decoration-none text-dark align-items-center">
                <img src="/secure.png" alt="Secure Payment" className="me-2 feature-icon" />
                <div>
                  <div className="fw-bold">Online Payment</div>
                  <div className="text-muted small">100% Safe and Secure payments</div>
                </div>
              </Link>
            </div>

            {/* Feature 3 */}
            <div className="col-12 col-sm-6 col-md-3 d-flex align-items-center mb-3 mb-md-0 d-none d-lg-flex">
              <Link to="/delivery" className="d-flex text-decoration-none text-dark align-items-center">
                <img src="/fast.png" alt="Fast Delivery" className="me-2 feature-icon" />
                <div>
                  <div className="fw-bold">Fast Delivery</div>
                  <div className="text-muted small">All product fast delivery</div>
                </div>
              </Link>
            </div>

            {/* Feature 4 */}
            <div className="col-4 col-sm-4 col-md-3 d-flex align-items-center">
              <Link to="/original-products" className="d-flex text-decoration-none text-dark align-items-center">
                <img src="/original.png" alt="Original Guarantee" className="me-2 feature-icon" />
                <div>
                  <div className="fw-bold">100% Original Guarantee for</div>
                  <div className="text-muted small">All product at xordox</div>
                </div>
              </Link>
            </div>

          </div>
        </div>
      </div>
    </>
  );
};

export default IconX;
