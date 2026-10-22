import React from 'react';
import { BsCheckCircleFill, BsArchive, BsTrash } from 'react-icons/bs';
import { useCheckout } from '../../context/CheckoutContext';
import { resolveImageUrl } from '../../config/apiEndpoints';

/**
 * @param readOnly  Order-summary variant: same card, without the select
 *                  checkbox and the Save for Later / Remove row.
 */
const CartProductCard = ({ item, isSavedForLater = false, readOnly = false }) => {
  const { updateQuantity, removeItem, saveForLater, moveToCart } = useCheckout();

  const productImg = item.image 
    ? resolveImageUrl(item.image) 
    : (item.product_image ? resolveImageUrl(item.product_image) : "/men_shirt/men-shirt-2.jpeg");

  const formattedPrice = Number(item.price || 0);
  const formattedOriginalPrice = Number(item.originalPrice || item.price * 1.5 || 0);
  const discountPercent = item.discount || (formattedOriginalPrice > formattedPrice ? Math.round(((formattedOriginalPrice - formattedPrice) / formattedOriginalPrice) * 100) : 35);

  return (
    <div className="card border-0 border-bottom rounded-0 mb-2 bg-white shadow-xs">
      <div className="card-body p-0">
        
        {/* DESKTOP VIEW - Kept exactly as required without modifying layout */}
        <div className="d-none d-md-flex row g-0">
          {/* Column 1: Image & Qty */}
          <div className="col-auto p-3 d-flex flex-column align-items-center position-relative" style={{ minWidth: '130px' }}>
            <img 
              src={productImg} 
              alt={item.name} 
              className="img-fluid mb-3" 
              style={{ maxHeight: '120px', objectFit: 'contain' }} 
              onError={(e) => { e.target.src = "/men_shirt/men-shirt-2.jpeg"; }}
            />
            
            <div className="d-flex align-items-center border rounded">
              <button className="btn btn-sm px-2 py-0 border-0 fs-5 text-secondary bg-light" style={{ lineHeight: '1.2' }} onClick={() => updateQuantity(item.id, item.quantity - 1)}>−</button>
              <span className="px-3 fw-semibold border-start border-end">{item.quantity}</span>
              <button className="btn btn-sm px-2 py-0 border-0 fs-5 text-secondary bg-light" style={{ lineHeight: '1.2' }} onClick={() => updateQuantity(item.id, item.quantity + 1)}>+</button>
            </div>
          </div>

          {/* Column 2: Product Details */}
          <div className="col p-3 ps-3 d-flex flex-column">
            <h6 className="mb-1 text-truncate-2 fw-bold text-dark" style={{ fontSize: '15px', lineHeight: '1.4' }}>{item.name}</h6>
            
            {/* Price Row (All inline) */}
            <div className="d-flex align-items-center flex-wrap gap-2 mb-1">
              <span className="fs-6 fw-bold text-dark">₹{formattedPrice.toLocaleString('en-IN')}.</span>
              <span className="text-decoration-line-through text-muted small">₹{formattedOriginalPrice.toLocaleString('en-IN')}</span>
              <span className="text-success fw-bold small">{discountPercent}% off</span>
              {(item.offers > 0 || true) && (
                <span className="text-success small fw-semibold">
                  {item.offers || 4} offers applied {'>'}
                </span>
              )}
            </div>
            
            {/* Size & Color */}
            <div className="text-muted small mb-0" style={{ fontSize: '13px' }}>Size : {item.size || 'M'}</div>
            <div className="text-muted small mb-0" style={{ fontSize: '13px' }}>Colors : {item.color || 'Brown'}</div>
            
            {/* Seller & Assured */}
            <div className="d-flex align-items-center gap-2 mb-3">
              <span className="text-muted small" style={{ fontSize: '13px' }}>Seller : {item.seller || 'Printmont Retail'}</span>
              <img src="/Asured.png" height="24" alt="PM Secured" style={{ objectFit: 'contain' }} onError={(e) => { e.target.style.display = 'none'; }} />
            </div>

            {/* Desktop Action Buttons & Badge (Inline) */}
            <div className="d-flex align-items-center gap-4 mt-auto pt-1">
              {isSavedForLater ? (
                <button className="btn btn-link text-dark text-decoration-none p-0 fw-bold" style={{ fontSize: '14px' }} onClick={() => moveToCart(item.id)}>MOVE TO CART</button>
              ) : (
                <button className="btn btn-link text-dark text-decoration-none p-0 fw-bold" style={{ fontSize: '14px' }} onClick={() => saveForLater(item.id)}>SAVE FOR LATER</button>
              )}
              
              <button className="btn btn-link text-dark text-decoration-none p-0 fw-bold" style={{ fontSize: '14px' }} onClick={() => removeItem(item.id)}>REMOVE</button>
              
              <div className="d-flex align-items-center gap-1 ms-2">
                <BsCheckCircleFill className="text-success" size={12} />
                <span className="small fw-bold text-success" style={{ fontSize: '12px' }}>Customization Saved.</span>
              </div>
            </div>
          </div>

          {/* Column 3: Delivery Info (Desktop Only) */}
          <div className="col-3 p-3 text-end" style={{ borderLeft: '1px solid #f0f0f0' }}>
            <p className="text-uppercase text-dark fw-bold mb-2 small text-start ps-3" style={{ fontSize: '12px' }}>DELIVERY ON</p>
            <p className="small mb-1 text-start ps-3">
              <span className="fw-bold text-dark fs-6">24<sup>th</sup></span> Oct, Friday 2026
            </p>
            <p className="small text-secondary mb-0 text-start ps-3" style={{ fontSize: '12px' }}>
              Delivery charges <span className="fw-bold text-success">Free</span>
            </p>
            <p className="small text-secondary text-start ps-3" style={{ fontSize: '12px' }}>Standard Delivery.</p>
          </div>
        </div>

        {/* MOBILE VIEW - Formatted exactly like user screenshot */}
        <div className="d-block d-md-none p-3 pb-0">
          <div className="d-flex position-relative">
            
            {/* Left Column: Image + Stepper */}
            <div className="d-flex flex-column align-items-center me-3 flex-shrink-0" style={{ width: '92px' }}>
              <div className="position-relative w-100 text-center">
                {!readOnly && (
                  <div 
                    className="position-absolute d-flex align-items-center justify-content-center text-white rounded-1 shadow-xs"
                    style={{ 
                      top: 0, 
                      left: 0, 
                      zIndex: 2, 
                      width: '18px', 
                      height: '18px', 
                      backgroundColor: '#0084ff',
                      fontSize: '11px',
                      fontWeight: 'bold'
                    }}
                  >
                    ✓
                  </div>
                )}
                <img 
                  src={productImg} 
                  alt={item.name} 
                  className="img-fluid mb-2 rounded" 
                  style={{ height: '110px', width: '100%', objectFit: 'contain' }} 
                  onError={(e) => { e.target.src = "/men_shirt/men-shirt-2.jpeg"; }}
                />
              </div>

              {/* Stepper Quantity Underneath Image */}
              <div className="d-flex align-items-center border border-secondary-subtle rounded-1 w-100 justify-content-between mt-1 bg-white">
                <button 
                  className="btn btn-sm px-2 py-0 border-0 fs-5 text-secondary shadow-none" 
                  style={{ lineHeight: '1.2' }} 
                  onClick={() => updateQuantity(item.id, item.quantity - 1)}
                  aria-label="Decrease quantity"
                >
                  −
                </button>
                <span className="px-1 fw-bold text-dark border-start border-end border-secondary-subtle" style={{ fontSize: '13px', width: '28px', textAlign: 'center' }}>
                  {item.quantity}
                </span>
                <button 
                  className="btn btn-sm px-2 py-0 border-0 fs-5 text-secondary shadow-none" 
                  style={{ lineHeight: '1.2' }} 
                  onClick={() => updateQuantity(item.id, item.quantity + 1)}
                  aria-label="Increase quantity"
                >
                  +
                </button>
              </div>
            </div>
            
            {/* Right Column: Details */}
            <div className="flex-grow-1 min-width-0">
              <h6 className="mb-1 text-truncate-2 fw-bold text-dark" style={{ fontSize: '13.5px', lineHeight: '1.35' }}>
                {item.name}
              </h6>
              
              <div className="text-muted mb-0" style={{ fontSize: '11px' }}>Size : {item.size || 'M'}</div>
              <div className="text-muted mb-1" style={{ fontSize: '11px' }}>Colors : {item.color || 'Brown'}</div>
              
              {/* Rating & Reviews + PM Assured */}
              <div className="d-flex align-items-center gap-1 mb-1">
                <span className="text-success" style={{ fontSize: '12px', letterSpacing: '1px' }}>★★★★★</span>
                <span className="text-secondary small ms-1" style={{ fontSize: '11px' }}>18 Reviews</span>
                <img 
                  src="/Asured.png" 
                  height="16" 
                  alt="Assured" 
                  className="ms-1" 
                  style={{ objectFit: 'contain' }} 
                  onError={(e) => { e.target.style.display = 'none'; }} 
                />
              </div>

              {/* Price & Discounts */}
              <div className="d-flex align-items-baseline flex-wrap gap-2 mb-1">
                <span className="fw-bold text-dark fs-6">₹ {formattedPrice.toLocaleString('en-IN')}.</span>
                <span className="text-decoration-line-through text-muted small" style={{ fontSize: '12px' }}>₹{formattedOriginalPrice.toLocaleString('en-IN')}</span>
                <span className="text-success fw-bold small" style={{ fontSize: '12px' }}>{discountPercent}% off</span>
              </div>
              
              {/* Offers applied */}
              <div className="text-success fw-bold" style={{ fontSize: '12px' }}>
                {item.offers || 4} offers applied &gt;
              </div>
            </div>

          </div>
          
          {/* Delivery & Customization Row */}
          <div className="d-flex justify-content-between align-items-center mt-3 mb-2 px-1">
            <span className="text-secondary" style={{ fontSize: '12px' }}>
              Delivery by 24 Oct Friday <span className="text-success fw-bold ms-1">Free</span>
            </span>
            <div className="d-flex align-items-center gap-1">
              <BsCheckCircleFill className="text-success" size={11} />
              <span className="fw-bold text-success" style={{ fontSize: '11px' }}>Customization Saved.</span>
            </div>
          </div>

          {/* Split Buttons (Save for Later & Remove) */}
          <div className={`d-flex border-top mx-n3 mt-2${readOnly ? ' d-none' : ''}`}>
            {isSavedForLater ? (
              <button 
                className="btn flex-fill py-2 rounded-0 border-end border-light text-secondary fw-semibold bg-white d-flex justify-content-center align-items-center gap-2" 
                style={{ fontSize: '13px' }} 
                onClick={() => moveToCart(item.id)}
              >
                MOVE TO CART
              </button>
            ) : (
              <button 
                className="btn flex-fill py-2 rounded-0 border-end border-light text-secondary fw-bold bg-white d-flex justify-content-center align-items-center gap-2" 
                style={{ fontSize: '12px' }} 
                onClick={() => saveForLater(item.id)}
              >
                <BsArchive size={14} />
                <span>Save for Later</span>
              </button>
            )}

            <button 
              className="btn flex-fill py-2 rounded-0 text-secondary fw-bold bg-white d-flex justify-content-center align-items-center gap-2" 
              style={{ fontSize: '12px' }} 
              onClick={() => removeItem(item.id)}
            >
              <BsTrash size={14} />
              <span>Remove</span>
            </button>
          </div>

        </div>

      </div>
    </div>
  );
};

export default CartProductCard;
