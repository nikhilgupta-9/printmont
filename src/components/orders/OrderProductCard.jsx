import React from 'react';
import { BsCheckCircleFill } from 'react-icons/bs';

const OrderProductCard = ({ item }) => {
  return (
    <div className="card border-light shadow-sm mb-3 rounded-0">
      <div className="card-body p-0">
        <div className="row g-0">
          
          {/* Column 1: Image & Qty (Mobile: col-4, Desktop: ~120px flex) */}
          <div className="col-4 col-md-auto p-3 d-flex flex-column align-items-center position-relative" style={{ minWidth: '130px' }}>
            <img src={item.image || "/men_shirt/men-shirt-2.jpeg"} alt={item.name} className="img-fluid mb-3" style={{ maxHeight: '120px', objectFit: 'contain' }} />
            
            <div className="d-flex align-items-center border rounded">
              <button className="btn btn-sm px-2 py-0 border-0 fs-5 text-secondary bg-light" style={{ lineHeight: '1.2' }} disabled>−</button>
              <span className="px-3 fw-semibold border-start border-end">{item.quantity}</span>
              <button className="btn btn-sm px-2 py-0 border-0 fs-5 text-secondary bg-light" style={{ lineHeight: '1.2' }} disabled>+</button>
            </div>
          </div>

          {/* Column 2: Product Details */}
          <div className="col-8 col-md p-3 ps-0 ps-md-3 d-flex flex-column">
            <h6 className="mb-1 text-truncate-2" style={{ fontSize: '15px', lineHeight: '1.4' }}>{item.name}</h6>
            
            {/* Price Row */}
            <div className="d-flex align-items-baseline gap-2 mb-1">
              <span className="fs-5 fw-bold">₹{item.price.toLocaleString('en-IN')}</span>
              <span className="text-decoration-line-through text-muted small">₹{item.originalPrice.toLocaleString('en-IN')}</span>
              <span className="text-success fw-bold small">{item.discount}% off</span>
            </div>
            
            {/* Offers applied link */}
            {item.offers > 0 && (
              <div className="small mb-1 fw-semibold" style={{ color: '#1a73e8', fontSize: '12px' }}>
                {item.offers} offers applied &gt;
              </div>
            )}
            
            {/* Size & Color */}
            <div className="text-muted small mb-1" style={{ fontSize: '12px' }}>Size: {item.size}</div>
            <div className="text-muted small mb-1" style={{ fontSize: '12px' }}>Colors: {item.color}</div>
            
            {/* Seller & Assured */}
            <div className="d-flex align-items-center gap-2 mb-2">
              <span className="text-muted small" style={{ fontSize: '12px' }}>Seller: {item.seller || 'name here'}</span>
              <img src="/Asured.png" height="16" alt="Assured" />
            </div>

            {/* Customization Saved Badge (Desktop inline, Mobile below) */}
            <div className="d-none d-md-flex align-items-center gap-1 bg-light rounded px-2 py-1 align-self-start mt-2">
              <BsCheckCircleFill className="text-success" size={12} />
              <span className="small fw-semibold text-muted" style={{ fontSize: '11px' }}>Customization Saved.</span>
            </div>
          </div>

          {/* Column 3: Delivery Info (Desktop Only) */}
          <div className="d-none d-md-block col-md-3 p-3 border-start">
            <p className="text-uppercase text-dark fw-bold mb-2 small" style={{ fontSize: '12px' }}>DELIVERY ON</p>
            <p className="small mb-1">
              <span className="fw-bold text-dark fs-6">24<sup>th</sup></span> Oct, Friday 2025
            </p>
            <p className="small text-secondary mb-0" style={{ fontSize: '12px' }}>
              Delivery charges <span className="fw-bold text-success">Free</span>
            </p>
            <p className="small text-secondary" style={{ fontSize: '12px' }}>Standard Delivery.</p>
          </div>

        </div>

        {/* Mobile Customization Saved Badge */}
        <div className="d-md-none px-3 pb-2">
          <div className="d-inline-flex align-items-center gap-1 bg-light rounded px-2 py-1">
            <BsCheckCircleFill className="text-success" size={12} />
            <span className="small fw-semibold text-muted" style={{ fontSize: '11px' }}>Customization Saved.</span>
          </div>
        </div>

        {/* Mobile Delivery Info */}
        <div className="d-md-none px-3 pb-3">
          <span className="small" style={{ fontSize: '13px' }}>Delivery by 24 Oct Friday <span className="text-success fw-bold ms-1 border-start border-2 border-secondary ps-2">Free</span></span>
        </div>

      </div>
    </div>
  );
};

export default OrderProductCard;
