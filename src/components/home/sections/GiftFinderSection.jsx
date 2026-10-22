import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { FaStar, FaGift, FaMapMarkerAlt, FaSearch } from 'react-icons/fa';
import { API_ENDPOINTS } from '../../../config/apiEndpoints';

const DEFAULT_OCCASIONS = [
  { id: 'corporate', label: 'Corporate & Business', query: 'Corporate' },
  { id: 'birthday', label: 'Birthday Celebration', query: 'Birthday' },
  { id: 'anniversary', label: 'Anniversary & Milestones', query: 'Anniversary' },
  { id: 'festival', label: 'Festivals & Diwali', query: 'Festive' },
  { id: 'employee', label: 'Employee Joining Kit', query: 'Executive' },
  { id: 'farewell', label: 'Farewell & Appreciation', query: 'Gifts' }
];

const DEFAULT_GIFT_TYPES = [
  { id: 'apparel', label: 'Custom Apparel & T-Shirts', category_id: '47', query: 'Shirt' },
  { id: 'tech', label: 'Tech & Smart Gadgets', category_id: '84', query: 'Tech' },
  { id: 'drinkware', label: 'Drinkware & Flasks', category_id: '31', query: 'Mug' },
  { id: 'eco', label: 'Eco-Friendly & Plants', category_id: '115', query: 'Eco' },
  { id: 'personalized', label: 'Personalized Gifts', category_id: '1', query: 'Personalized' },
  { id: 'hampers', label: 'Corporate Gift Hampers', category_id: '17', query: 'Gift Kit' }
];

const GiftFinderSection = () => {
  const navigate = useNavigate();

  const [pincode, setPincode] = useState(() => localStorage.getItem('user_pincode') || '');
  const [occasion, setOccasion] = useState('');
  const [giftType, setGiftType] = useState('');

  const [settings, setSettings] = useState({
    enabled: true,
    title: 'Find Gift for your Business and Loved Ones',
    subtitle: 'Curated custom gifts and corporate merchandise delivered across India',
    occasions: DEFAULT_OCCASIONS,
    gift_types: DEFAULT_GIFT_TYPES
  });

  useEffect(() => {
    let isMounted = true;
    const fetchFinderData = async () => {
      try {
        const res = await fetch(API_ENDPOINTS.GIFT_FINDER);
        if (!res.ok) return;
        const data = await res.json();
        if (isMounted && data && data.success && data.data) {
          setSettings(prev => ({
            ...prev,
            enabled: data.data.enabled !== undefined ? data.data.enabled : true,
            title: data.data.title || prev.title,
            subtitle: data.data.subtitle || prev.subtitle,
            occasions: Array.isArray(data.data.occasions) && data.data.occasions.length > 0 ? data.data.occasions : prev.occasions,
            gift_types: Array.isArray(data.data.gift_types) && data.data.gift_types.length > 0 ? data.data.gift_types : prev.gift_types
          }));
        }
      } catch (err) {
        // Fallback gracefully to default settings
      }
    };

    fetchFinderData();
    return () => { isMounted = false; };
  }, []);

  if (!settings.enabled) {
    return null;
  }

  const handleFindGift = (e) => {
    if (e && e.preventDefault) e.preventDefault();

    if (pincode.trim()) {
      localStorage.setItem('user_pincode', pincode.trim());
    }

    // Determine query and filter params
    const selectedOccObj = settings.occasions.find(o => o.id === occasion || o.label === occasion);
    const selectedGtObj = settings.gift_types.find(g => g.id === giftType || g.label === giftType);

    const queryParts = [];
    if (selectedOccObj && selectedOccObj.query) {
      queryParts.push(selectedOccObj.query);
    } else if (occasion) {
      queryParts.push(occasion);
    }

    if (selectedGtObj && selectedGtObj.query) {
      queryParts.push(selectedGtObj.query);
    } else if (giftType) {
      queryParts.push(giftType);
    }

    const searchQuery = queryParts.join(' ').trim();
    const searchParams = new URLSearchParams();

    if (searchQuery) {
      searchParams.set('q', searchQuery);
    }

    if (selectedGtObj && selectedGtObj.category_id) {
      searchParams.set('category', selectedGtObj.category_id);
    }

    if (selectedOccObj) {
      searchParams.set('occasion', selectedOccObj.label);
    }

    const queryString = searchParams.toString();
    const targetUrl = queryString ? `/allproducts?${queryString}` : '/allproducts';
    navigate(targetUrl);
  };

  return (
    <div className='gift_finder_wrapper d-flex flex-column px-0 my-0 w-100'>
      {/* Desktop Version */}
      <div className="d-none d-md-block w-100">

        <div className="text-center mb-3">
          <h4 className='fw-bold mb-1' style={{ color: '#1f509a', fontSize: '1.35rem' }}>
            {settings.title}
          </h4>
          {settings.subtitle && (
            <p className="text-muted small mb-0">{settings.subtitle}</p>
          )}
        </div>

        <div className="d-flex justify-content-center my-0">
          <div className="bg-white p-3 rounded-4 shadow-sm border" style={{ maxWidth: '1180px', width: '100%' }}>
            <form onSubmit={handleFindGift} className="d-flex flex-wrap align-items-center justify-content-between gap-3">
              
              {/* Badge Header */}
              <div className="d-flex align-items-center gap-2 px-2" style={{ borderRight: '2px solid #e2e8f0' }}>
                <div className="p-2 rounded-3 bg-primary bg-opacity-10 text-primary">
                  <FaGift size={22} />
                </div>
                <div>
                  <div className="fw-bold text-dark" style={{ fontSize: '0.95rem', lineHeight: '1.2' }}>GIFT FINDER</div>
                  <div className="text-muted" style={{ fontSize: '0.75rem' }}>Personal &amp; Bulk</div>
                </div>
              </div>

              {/* 1. Pincode Input */}
              <div className="position-relative" style={{ minWidth: '180px', flex: '1 1 180px' }}>
                <div className="d-flex align-items-center bg-light rounded-3 px-3 py-2 border">
                  <FaMapMarkerAlt className="text-danger me-2" size={16} />
                  <input
                    type="text"
                    className="form-control border-0 bg-transparent p-0 fw-semibold"
                    placeholder="Delivery Pincode"
                    value={pincode}
                    onChange={(e) => setPincode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                    style={{ fontSize: '0.9rem', boxShadow: 'none' }}
                  />
                </div>
              </div>

              {/* 2. Occasion Selector */}
              <div className="position-relative" style={{ minWidth: '220px', flex: '1 1 220px' }}>
                <div className="d-flex align-items-center bg-light rounded-3 px-3 py-2 border">
                  <FaStar className="text-warning me-2" size={16} />
                  <div className="w-100">
                    <select
                      className="form-select border-0 bg-transparent p-0 fw-semibold"
                      style={{ fontSize: '0.9rem', boxShadow: 'none', cursor: 'pointer' }}
                      value={occasion}
                      onChange={(e) => setOccasion(e.target.value)}
                    >
                      <option value="">Select Occasion (All)</option>
                      {settings.occasions.map((occ) => (
                        <option key={occ.id || occ.label} value={occ.id || occ.label}>
                          {occ.label}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>
              </div>

              {/* 3. Gift Type Selector */}
              <div className="position-relative" style={{ minWidth: '220px', flex: '1 1 220px' }}>
                <div className="d-flex align-items-center bg-light rounded-3 px-3 py-2 border">
                  <FaGift className="text-primary me-2" size={16} />
                  <div className="w-100">
                    <select
                      className="form-select border-0 bg-transparent p-0 fw-semibold"
                      style={{ fontSize: '0.9rem', boxShadow: 'none', cursor: 'pointer' }}
                      value={giftType}
                      onChange={(e) => setGiftType(e.target.value)}
                    >
                      <option value="">Select Gift Category (All)</option>
                      {settings.gift_types.map((gt) => (
                        <option key={gt.id || gt.label} value={gt.id || gt.label}>
                          {gt.label}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>
              </div>

              {/* 4. Action Button */}
              <div>
                <button
                  type="submit"
                  className="btn btn-primary fw-bold px-4 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm"
                  style={{
                    backgroundColor: '#1f509a',
                    borderColor: '#1f509a',
                    fontSize: '0.95rem',
                    minHeight: '44px'
                  }}
                >
                  <FaSearch size={14} />
                  <span>FIND GIFTS</span>
                </button>
              </div>

            </form>
          </div>
        </div>
      </div>

      {/* Mobile Version */}
      <div className="d-block d-md-none px-3 py-3 bg-white rounded-3 shadow-sm border">
        <div className="text-center mb-2">
          <p className="fw-bold mb-1" style={{ fontSize: '1.05rem', color: '#1f509a' }}>
            {settings.title}
          </p>
          {settings.subtitle && (
            <p className="text-muted" style={{ fontSize: '0.75rem', marginBottom: '0.75rem' }}>
              {settings.subtitle}
            </p>
          )}
        </div>

        <form onSubmit={handleFindGift}>
          {/* Pincode Input */}
          <div className="mb-2">
            <div className="d-flex align-items-center border rounded p-2" style={{ backgroundColor: '#f8fafc' }}>
              <FaMapMarkerAlt className="text-danger me-2" size={16} />
              <input
                type="text"
                className="form-control border-0 bg-transparent p-0 font-medium"
                placeholder="Enter Pincode (e.g. 110001)"
                value={pincode}
                onChange={(e) => setPincode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                style={{ fontSize: '0.85rem', boxShadow: 'none' }}
              />
            </div>
          </div>

          <div className="row g-2 mb-3">
            {/* Occasion Dropdown */}
            <div className="col-6">
              <div className="d-flex align-items-center border rounded p-2 h-100" style={{ backgroundColor: '#f8fafc' }}>
                <FaStar className="text-warning me-2 flex-shrink-0" size={16} />
                <select 
                  className="form-select border-0 bg-transparent p-0 font-medium" 
                  style={{ fontSize: '0.825rem', boxShadow: 'none' }}
                  value={occasion}
                  onChange={(e) => setOccasion(e.target.value)}
                >
                  <option value="">Occasion</option>
                  {settings.occasions.map((occ) => (
                    <option key={occ.id || occ.label} value={occ.id || occ.label}>
                      {occ.label}
                    </option>
                  ))}
                </select>
              </div>
            </div>
            
            {/* Gift Type Dropdown */}
            <div className="col-6">
              <div className="d-flex align-items-center border rounded p-2 h-100" style={{ backgroundColor: '#f8fafc' }}>
                <FaGift className="text-primary me-2 flex-shrink-0" size={16} />
                <select 
                  className="form-select border-0 bg-transparent p-0 font-medium" 
                  style={{ fontSize: '0.825rem', boxShadow: 'none' }}
                  value={giftType}
                  onChange={(e) => setGiftType(e.target.value)}
                >
                  <option value="">Gift Types</option>
                  {settings.gift_types.map((gt) => (
                    <option key={gt.id || gt.label} value={gt.id || gt.label}>
                      {gt.label}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </div>

          <button 
            type="submit"
            className="btn w-100 text-white fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center gap-2" 
            style={{ backgroundColor: '#1f509a', borderRadius: '6px', fontSize: '0.95rem' }}
          >
            <FaSearch size={14} />
            <span>FIND GIFTS</span>
          </button>
        </form>
      </div>
    </div>
  );
};

export default GiftFinderSection;
