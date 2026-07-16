import React, { useState } from 'react';
import { FaStar, FaGift } from 'react-icons/fa';

const GiftFinderSection = () => {
  const [pincode, setPincode] = useState('');
  const [occasion, setOccasion] = useState('');
  const [giftType, setGiftType] = useState('');

  const handleFindGift = () => {
    alert(`Pincode: ${pincode || 'N/A'}\nOccasion: ${occasion || 'N/A'}\nGift Type: ${giftType || 'N/A'}`);
  };

  return (
    <div className='find_product_main flex-column px-2 my-2'>
      {/* Desktop Version */}
      <div className="d-none d-md-block">
        <p className='fw-medium ps-3 find-product'>Find Gift for your Business and Loved Ones</p>
        <div className="d-flex justify-content-center my-0 ">
          <div className="bg-white p-3 rounded shadow-sm" style={{ maxWidth: '1100px', width: '100%' }}>
            <div className="d-flex flex-wrap align-items-center gap-3">
              <div className="fw-bold fs-5">GIFT <br /> FINDER</div>
              <input
                type="text"
                className="form-control custom-bg py-3"
                placeholder="Enter Pincode"
                value={pincode}
                onChange={(e) => setPincode(e.target.value)}
                style={{ maxWidth: '190px' }}
              />
              <div className="border rounded p-1 d-flex align-items-center custom-bg" style={{ minWidth: '180px' }}>
                <div className="me-2">
                  <div className="fw-semibold fs-5">Occasion</div>
                  <div className="text-muted small fw-medium">Birthday, Anniversary etc.</div>
                </div>
                <input
                  type="checkbox"
                  className="form-check-input ms-auto border-1 border-dark"
                  checked={!!occasion}
                  onChange={(e) => setOccasion(e.target.checked ? 'Birthday' : '')}
                />
              </div>
              <div className="border rounded p-1 d-flex align-items-center custom-bg" style={{ minWidth: '180px' }}>
                <div className="me-2 ">
                  <div className="fw-semibold fs-5">Gift Type</div>
                  <div className="text-muted small fw-medium">Flowers, Cakes, Plants, etc.</div>
                </div>
                <input
                  type="checkbox"
                  className="form-check-input ms-auto border-1 border-dark"
                  checked={!!giftType}
                  onChange={(e) => setGiftType(e.target.checked ? 'Flowers' : '')}
                />
              </div>
              <div className='bg-theme p-2 rounded'>
                <button className="btn fs-4 fw-semibold px-5 py-1 text-white" onClick={handleFindGift}>
                  FIND GIFT
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Mobile Version (matches Figma layout) */}
      <div className="d-block d-md-none px-2 py-3 bg-white rounded shadow-sm border">
        <p className="text-center fw-semibold mb-3" style={{ fontSize: '1.05rem', color: '#0b53a1' }}>
          Find Gift for your Business and Loved Ones
        </p>
        
        <div className="row g-2 mb-3">
          <div className="col-6">
            <div className="d-flex align-items-center border rounded p-2" style={{ backgroundColor: '#f8fafc' }}>
              <FaStar className="text-primary me-2" size={18} />
              <select 
                className="form-select border-0 bg-transparent p-0 font-medium" 
                style={{ fontSize: '0.85rem', boxShadow: 'none' }}
                value={occasion}
                onChange={(e) => setOccasion(e.target.value)}
              >
                <option value="">Occasion</option>
                <option value="birthday">Birthday</option>
                <option value="anniversary">Anniversary</option>
                <option value="festival">Festival</option>
              </select>
            </div>
          </div>
          
          <div className="col-6">
            <div className="d-flex align-items-center border rounded p-2" style={{ backgroundColor: '#f8fafc' }}>
              <FaGift className="text-primary me-2" size={18} />
              <select 
                className="form-select border-0 bg-transparent p-0 font-medium" 
                style={{ fontSize: '0.85rem', boxShadow: 'none' }}
                value={giftType}
                onChange={(e) => setGiftType(e.target.value)}
              >
                <option value="">Gift Types</option>
                <option value="flowers">Flowers</option>
                <option value="cakes">Cakes</option>
                <option value="plants">Plants</option>
                <option value="customized">Customized Gifts</option>
              </select>
            </div>
          </div>
        </div>

        <button 
          className="btn w-100 text-white fw-bold py-2" 
          style={{ backgroundColor: '#008ecc', borderRadius: '4px', fontSize: '0.95rem', letterSpacing: '0.5px' }}
          onClick={handleFindGift}
        >
          FIND GIFTS
        </button>
      </div>
    </div>
  );
};

export default GiftFinderSection;
