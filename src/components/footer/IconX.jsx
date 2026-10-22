import React from 'react';
import { Link } from 'react-router-dom';

const IconX = () => {
  return (
    <>
      <div className="feature-strip bg-light py-3 border-bottom">
        <div className="container-fluid px-4 px-md-5">
          <div className="row text-center text-md-start justify-content-between g-2">

            {/* Feature 1 */}
            <div className="col-4 col-sm-4 col-md-3 d-flex align-items-center mb-3 mb-md-0">
              <Link to="/shipping" className="d-flex text-decoration-none text-dark align-items-center">
                <img src="/free.png" alt="Free Shipping" className="me-2 feature-icon" />
                <div>
                  <div className="fw-bold">Free Shipping</div>
                  <div className="text-muted small">On orders above standard cart</div>
                </div>
              </Link>
            </div>

            {/* Feature 2 */}
            <div className="col-4 col-sm-4 col-md-3 d-flex align-items-center mb-3 mb-md-0">
              <Link to="/security" className="d-flex text-decoration-none text-dark align-items-center">
                <img src="/secure.png" alt="Secure Payment" className="me-2 feature-icon" />
                <div>
                  <div className="fw-bold">Online Payment</div>
                  <div className="text-muted small">100% Safe and Secure payments</div>
                </div>
              </Link>
            </div>

            {/* Feature 3 */}
            <div className="col-12 col-sm-6 col-md-3 d-flex align-items-center mb-3 mb-md-0 d-none d-lg-flex">
              <Link to="/shipping" className="d-flex text-decoration-none text-dark align-items-center">
                <img src="/fast.png" alt="Fast Delivery" className="me-2 feature-icon" />
                <div>
                  <div className="fw-bold">Fast Delivery</div>
                  <div className="text-muted small">Express nationwide logistics</div>
                </div>
              </Link>
            </div>

            {/* Feature 4 */}
            <div className="col-4 col-sm-4 col-md-3 d-flex align-items-center">
              <Link to="/about" className="d-flex text-decoration-none text-dark align-items-center">
                <img src="/original.png" alt="Original Guarantee" className="me-2 feature-icon" />
                <div>
                  <div className="fw-bold">100% Quality Guaranteed</div>
                  <div className="text-muted small">Premium customized prints</div>
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
