import React from 'react';
import { BsCheckCircleFill, BsArchive, BsTrash } from 'react-icons/bs';
import { useCheckout } from '../../context/CheckoutContext';

/**
 * @param readOnly  Order-summary variant: same card, without the select
 *                  checkbox and the Save for Later / Remove row.
 */
const CartProductCard = ({ item, isSavedForLater = false, readOnly = false }) => {
  const { updateQuantity, removeItem, saveForLater, moveToCart } = useCheckout();

  return (
    <div className="card border-light shadow-sm mb-3 rounded-0">
      <div className="card-body p-0">
        
        {/* DESKTOP VIEW */}
        <div className="d-none d-md-flex row g-0">
          {/* Column 1: Image & Qty */}
          <div className="col-auto p-3 d-flex flex-column align-items-center position-relative" style={{ minWidth: '130px' }}>
            <img src={item.image || "/men_shirt/men-shirt-2.jpeg"} alt={item.name} className="img-fluid mb-3" style={{ maxHeight: '120px', objectFit: 'contain' }} />
            
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
              <span className="fs-6 fw-bold text-dark">₹{item.price.toLocaleString('en-IN')}.</span>
              <span className="text-decoration-line-through text-muted small">₹{item.originalPrice.toLocaleString('en-IN')}</span>
              <span className="text-success fw-bold small">{item.discount}% off</span>
              {item.offers > 0 && (
                <span className="text-success small fw-semibold">
                  {item.offers} offers applied {'>'}
                </span>
              )}
            </div>
            
            {/* Size & Color */}
            <div className="text-muted small mb-0" style={{ fontSize: '13px' }}>Size : {item.size}</div>
            <div className="text-muted small mb-0" style={{ fontSize: '13px' }}>Colors : {item.color}</div>
            
            {/* Seller & Assured */}
            <div className="d-flex align-items-center gap-2 mb-3">
              <span className="text-muted small" style={{ fontSize: '13px' }}>Saller : {item.seller || 'name here'}</span>
              <img src="/Asured.png" height="32" alt="PM Secured" style={{ objectFit: 'contain' }} />
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
              <span className="fw-bold text-dark fs-6">24<sup>th</sup></span> Oct, Friday 2025
            </p>
            <p className="small text-secondary mb-0 text-start ps-3" style={{ fontSize: '12px' }}>
              Delivery charges <span className="fw-bold text-success">Free</span>
            </p>
            <p className="small text-secondary text-start ps-3" style={{ fontSize: '12px' }}>Standard Delivery.</p>
          </div>
        </div>

        {/* MOBILE VIEW */}
        <div className="d-block d-md-none p-2 pb-0">
          <div className="d-flex position-relative">
            {/* Left Column: Image + Qty */}
            <div className="d-flex flex-column align-items-center me-3" style={{ width: '90px' }}>
              <div className="position-relative w-100">
                {!readOnly && (
                  <input type="checkbox" className="position-absolute form-check-input shadow-none rounded-0 border-0" style={{ top: 0, left: 0, zIndex: 1, width: '18px', height: '18px', backgroundColor: '#0084ff' }} defaultChecked />
                )}
                <img src={item.image || "/men_shirt/men-shirt-2.jpeg"} alt={item.name} className="img-fluid mb-2 px-1 pt-1" style={{ maxHeight: '110px', objectFit: 'contain' }} />
              </div>
              <div className="d-flex align-items-center border border-secondary rounded-0 w-100 justify-content-between mt-1">
                <button className="btn btn-sm px-1 py-0 border-0 fs-5 text-secondary bg-white shadow-none" style={{ lineHeight: '1' }} onClick={() => updateQuantity(item.id, item.quantity - 1)}>−</button>
                <span className="px-1 fw-semibold border-start border-end border-secondary" style={{fontSize: '13px', width:'28px', textAlign:'center'}}>{item.quantity}</span>
                <button className="btn btn-sm px-1 py-0 border-0 fs-5 text-secondary bg-white shadow-none" style={{ lineHeight: '1' }} onClick={() => updateQuantity(item.id, item.quantity + 1)}>+</button>
              </div>
            </div>
            
            {/* Right Column: Details */}
            <div className="flex-grow-1 ps-1 pt-1">
              <h6 className="mb-1 text-truncate-2 fw-bold text-dark" style={{ fontSize: '13px', lineHeight: '1.4' }}>{item.name}</h6>
              <div className="text-muted mb-0" style={{ fontSize: '10px' }}>Size : {item.size}</div>
              <div className="text-muted mb-1" style={{ fontSize: '10px' }}>Colors : {item.color}</div>
              
              <div className="d-flex align-items-center gap-1 mb-1">
                <span className="text-success" style={{fontSize: '12px', letterSpacing: '1px'}}>★★★★★</span>
                <span className="text-secondary" style={{fontSize: '11px'}}>18 Reviews</span>
                <img src="/Asured.png" height="28" alt="PM Secured" className="ms-1" style={{ objectFit: 'contain' }} />
              </div>

              <div className="d-flex align-items-center flex-wrap gap-2 mb-1 mt-1">
                <span className="fw-bold text-dark" style={{fontSize: '14px'}}>₹{item.price.toLocaleString('en-IN')}.</span>
                <span className="text-decoration-line-through text-secondary" style={{fontSize: '12px'}}>₹{item.originalPrice.toLocaleString('en-IN')}</span>
                <span className="text-success fw-bold" style={{fontSize: '12px'}}>{item.discount}% off</span>
              </div>
              
              {item.offers > 0 && (
                <div className="text-success fw-bold mt-1" style={{ fontSize: '12px' }}>
                  {item.offers} offers applied {'>'}
                </div>
              )}
            </div>
          </div>
          
          {/* Delivery & Customization Row */}
          <div className="d-flex justify-content-between align-items-center mt-3 mb-2 px-1">
            <span className="text-secondary" style={{ fontSize: '12px' }}>Delivery by 24 Oct Friday <span className="text-success fw-bold ms-1">Free</span></span>
            <div className="d-flex align-items-center gap-1">
              <BsCheckCircleFill className="text-success" size={10} />
              <span className="fw-bold text-success" style={{ fontSize: '10px' }}>Customization Saved.</span>
            </div>
          </div>

          {/* Split Buttons */}
          <div className={`d-flex border-top mx-n2 mt-2${readOnly ? ' d-none' : ''}`}>
            {isSavedForLater ? (
              <button className="btn flex-fill py-2 rounded-0 border-end border-light text-secondary fw-semibold bg-white d-flex justify-content-center align-items-center gap-2" style={{ fontSize: '14px' }} onClick={() => moveToCart(item.id)}>
                 MOVE TO CART
              </button>
            ) : (
              <button className="btn flex-fill py-2 rounded-0 border-end border-light text-secondary fw-bold bg-white d-flex justify-content-center align-items-center gap-2" style={{ fontSize: '12px' }} onClick={() => saveForLater(item.id)}>
                 <BsArchive size={14} />
                 <span>Save for Later</span>
              </button>
            )}

            <button className="btn flex-fill py-2 rounded-0 text-secondary fw-bold bg-white d-flex justify-content-center align-items-center gap-2" style={{ fontSize: '12px' }} onClick={() => removeItem(item.id)}>
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
