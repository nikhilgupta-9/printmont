import React, { useState, useEffect, useRef } from 'react';
import { IoLocationOutline, IoSearchOutline, IoCloseOutline, IoCheckmarkCircle } from 'react-icons/io5';
import { FaCity, FaCrosshairs, FaBolt } from 'react-icons/fa';
import { useLocationContext } from '../../context/LocationContext';
import './LocationModal.css';

const LocationModal = () => {
  const {
    location: currentLocation,
    isLocationModalOpen,
    closeLocationModal,
    selectLocation,
    popularCities,
    isLoadingPopular,
    searchLocations,
    checkPincode
  } = useLocationContext();

  const [query, setQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [isSearching, setIsSearching] = useState(false);
  const [isDetectingGPS, setIsDetectingGPS] = useState(false);
  const [gpsError, setGpsError] = useState('');
  const modalRef = useRef(null);
  const inputRef = useRef(null);

  // Focus input when modal opens
  useEffect(() => {
    if (isLocationModalOpen) {
      setQuery('');
      setSearchResults([]);
      setGpsError('');
      setTimeout(() => {
        if (inputRef.current) inputRef.current.focus();
      }, 100);
    }
  }, [isLocationModalOpen]);

  // Live search debounced
  useEffect(() => {
    if (!query || !query.trim()) {
      setSearchResults([]);
      setIsSearching(false);
      return;
    }

    const timer = setTimeout(async () => {
      setIsSearching(true);
      const results = await searchLocations(query.trim());
      setSearchResults(results);
      setIsSearching(false);
    }, 250);

    return () => clearTimeout(timer);
  }, [query, searchLocations]);

  // Handle GPS location detection
  const handleDetectGPS = () => {
    if (!navigator.geolocation) {
      setGpsError('Geolocation is not supported by your browser.');
      return;
    }

    setIsDetectingGPS(true);
    setGpsError('');

    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        const { latitude, longitude } = pos.coords;
        try {
          // Reverse geocoding via OpenStreetMap free nominatim API
          const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`);
          const data = await res.json();
          if (data && data.address) {
            const detectedCity = data.address.city || data.address.state_district || data.address.town || data.address.county || 'Current Location';
            const detectedState = data.address.state || '';
            const detectedPincode = data.address.postcode || '';

            selectLocation({
              city_name: detectedCity,
              state_name: detectedState,
              pincode: detectedPincode,
              is_same_day: 1,
              is_next_day: 1,
              standard_delivery_days: '1-2 Days'
            });
          } else {
            // Default to Delhi NCR if geocode data is missing
            selectLocation({
              city_name: 'Current Location',
              state_name: 'India',
              pincode: '',
              is_same_day: 1,
              is_next_day: 1,
              standard_delivery_days: '1-2 Days'
            });
          }
        } catch (e) {
          console.error('Error reverse geocoding:', e);
          selectLocation({
            city_name: 'Current Location',
            state_name: 'India',
            pincode: '',
            is_same_day: 1,
            is_next_day: 1,
            standard_delivery_days: '1-2 Days'
          });
        } finally {
          setIsDetectingGPS(false);
        }
      },
      (err) => {
        setIsDetectingGPS(false);
        setGpsError('Unable to retrieve your location. Please select your city below or enter pincode.');
      },
      { timeout: 10000, enableHighAccuracy: true }
    );
  };

  if (!isLocationModalOpen) return null;

  return (
    <div 
      className="location-modal-backdrop" 
      onClick={(e) => {
        if (e.target === e.currentTarget) closeLocationModal();
      }}
    >
      <div className="location-modal-container" ref={modalRef}>
        {/* Header */}
        <div className="loc-modal-header">
          <div>
            <h3 className="loc-modal-title">Select Delivery Location</h3>
            <p className="loc-modal-subtitle">
              Choose your city or enter pincode for fast delivery & personalized offers
            </p>
          </div>
          <button 
            type="button" 
            className="loc-close-btn" 
            onClick={closeLocationModal}
            aria-label="Close"
          >
            <IoCloseOutline />
          </button>
        </div>

        {/* Body */}
        <div className="loc-modal-body">
          {/* Search Input Box */}
          <div className="loc-search-box">
            <IoSearchOutline size={18} color="#64748b" />
            <input
              ref={inputRef}
              type="text"
              className="loc-search-input"
              placeholder="Enter City Name or 6-digit Pincode..."
              value={query}
              onChange={(e) => setQuery(e.target.value)}
            />
            {query && (
              <button 
                type="button" 
                className="btn p-0 border-0 text-muted me-1" 
                onClick={() => setQuery('')}
              >
                <IoCloseOutline size={18} />
              </button>
            )}
            {isSearching && (
              <div className="spinner-border spinner-border-sm text-primary ms-1" role="status"></div>
            )}
          </div>

          {/* Autocomplete Search Results */}
          {searchResults.length > 0 && (
            <div className="loc-search-results">
              {searchResults.map((res, idx) => (
                <div 
                  key={idx} 
                  className="loc-result-item" 
                  onClick={() => selectLocation(res)}
                >
                  <div className="d-flex align-items-center gap-2">
                    <IoLocationOutline size={18} color="#0b53a1" />
                    <div>
                      <div className="fw-semibold text-dark" style={{ fontSize: '13.5px' }}>
                        {res.city_name} {res.pincode ? `(${res.pincode})` : ''}
                      </div>
                      <div className="text-muted" style={{ fontSize: '11.5px' }}>
                        {res.state_name} • {res.standard_delivery_days || 'Standard Delivery'}
                      </div>
                    </div>
                  </div>
                  {res.is_same_day ? (
                    <span className="badge bg-success-subtle text-success border border-success-subtle d-flex align-items-center gap-1" style={{ fontSize: '10px' }}>
                      <FaBolt size={9} /> Same Day
                    </span>
                  ) : (
                    <span className="badge bg-light text-muted border" style={{ fontSize: '10px' }}>
                      Deliverable
                    </span>
                  )}
                </div>
              ))}
            </div>
          )}

          {/* GPS Auto-Detect Button */}
          <button 
            type="button" 
            className="loc-gps-btn" 
            onClick={handleDetectGPS} 
            disabled={isDetectingGPS}
          >
            <FaCrosshairs className={isDetectingGPS ? 'fa-spin' : ''} />
            <span>{isDetectingGPS ? 'Detecting your GPS location...' : 'Use Current Location'}</span>
          </button>

          {gpsError && (
            <div className="alert alert-warning py-1.5 px-2.5 mb-0" style={{ fontSize: '11.5px' }}>
              {gpsError}
            </div>
          )}

          {/* Popular Cities Grid */}
          <div>
            <div className="loc-popular-title">Popular Delivery Cities</div>
            <div className="loc-cities-grid">
              {popularCities.map((city) => {
                const isActive = currentLocation && currentLocation.city_name === city.city_name;
                return (
                  <div
                    key={city.id || city.city_name}
                    className={`loc-city-card ${isActive ? 'active' : ''}`}
                    onClick={() => selectLocation(city)}
                  >
                    <div className="loc-city-icon-wrapper">
                      <FaCity size={16} />
                    </div>
                    <span className="loc-city-name">{city.city_name}</span>
                    {city.is_same_day ? (
                      <span className="loc-city-badge">Same Day</span>
                    ) : null}
                    {isActive && (
                      <IoCheckmarkCircle 
                        className="position-absolute" 
                        style={{ top: '4px', right: '4px', color: '#0b53a1', fontSize: '14px' }} 
                      />
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default LocationModal;
