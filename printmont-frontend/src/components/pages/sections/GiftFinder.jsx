// GiftFinder.js
import React, { useState } from 'react';

const GiftFinder = () => {
  const [pincode, setPincode] = useState('');
  const [occasion, setOccasion] = useState('');
  const [giftType, setGiftType] = useState('');

  const handleFindGift = () => {
    alert(`Pincode: ${pincode}\nOccasion: ${occasion}\nGift Type: ${giftType}`);
  };

  return (
    <div className='find_product_main flex-column'>
      <p className='fw-medium ps-3 find-product d-none d-md-block'>Find Gift for your Business and Loved Ones</p>
    <div className="d-flex justify-content-center my-0 ">
      <div className="bg-white p-3 rounded shadow-sm" style={{ maxWidth: '1100px', width: '100%' }}>
        <div className="d-flex flex-wrap align-items-center gap-3">

          {/* Label */}
          <div className="fw-bold fs-5">GIFT <br /> FINDER</div>

          {/* Pincode Input */}
          <input
            type="text"
            className="form-control custom-bg py-3"
            placeholder="Enter Pincode"
            value={pincode}
            onChange={(e) => setPincode(e.target.value)}
            style={{ maxWidth: '190px' }}
          />

          {/* Occasion Checkbox Container */}
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

          {/* Gift Type Checkbox Container */}
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

          {/* Find Gift Button */}
          <div className='bg-theme p-2 rounded'>
            <button className="btn fs-4 fw-semibold px-5 py-1 text-white"  onClick={handleFindGift} >
            FIND GIFT
          </button>
          </div>
        </div>
      </div>
    </div>
    </div>
  );
};

export default GiftFinder;
