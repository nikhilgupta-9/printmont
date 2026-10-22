import React, { useState } from 'react';
import { Modal, Form, Button } from 'react-bootstrap';
import { IoLocationSharp, IoClose, IoFlashOutline, IoCheckmarkCircle } from 'react-icons/io5';
import { useLocationContext, POPULAR_CITIES } from '../../context/LocationContext';

const DeliveryLocationModal = () => {
  const { location, updateLocation, showLocationModal, setShowLocationModal } = useLocationContext();
  const [pincodeInput, setPincodeInput] = useState('');
  const [error, setError] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  const handleApplyPincode = (e) => {
    e?.preventDefault();
    const cleanPin = pincodeInput.trim();
    if (!/^\d{6}$/.test(cleanPin)) {
      setError('Please enter a valid 6-digit PIN code.');
      return;
    }
    setError('');
    
    // Find matching city if any, or default
    const matchedCity = POPULAR_CITIES.find(c => c.pincode === cleanPin);
    const cityName = matchedCity ? matchedCity.name : `Pincode ${cleanPin}`;
    
    setSuccessMsg(`Delivering to ${cityName} (${cleanPin})`);
    setTimeout(() => {
      updateLocation(cityName, cleanPin);
      setSuccessMsg('');
      setPincodeInput('');
    }, 400);
  };

  const handleSelectCity = (city) => {
    setError('');
    updateLocation(city.name, city.pincode);
  };

  return (
    <Modal
      show={showLocationModal}
      onHide={() => setShowLocationModal(false)}
      centered
      className="delivery-location-modal"
      backdrop="static"
    >
      <Modal.Header className="border-0 pb-0 pt-4 px-4 position-relative">
        <div className="d-flex align-items-center gap-2">
          <div 
            className="rounded-circle d-flex align-items-center justify-content-center"
            style={{ width: '40px', height: '40px', backgroundColor: '#e8f1fb', color: '#0b53a1' }}
          >
            <IoLocationSharp size={22} />
          </div>
          <div>
            <Modal.Title className="fs-5 fw-bold text-dark m-0">Select Delivery Location</Modal.Title>
            <small className="text-muted">Personalized gift delivery & corporate shipping speed</small>
          </div>
        </div>
        <button
          type="button"
          className="btn-close position-absolute top-0 end-0 m-3"
          aria-label="Close"
          onClick={() => setShowLocationModal(false)}
        />
      </Modal.Header>

      <Modal.Body className="p-4">
        {/* Current Location Badge */}
        <div className="p-3 mb-3 rounded-3 bg-light border d-flex align-items-center justify-content-between">
          <div className="d-flex align-items-center gap-2">
            <IoLocationSharp className="text-danger" size={18} />
            <div>
              <span className="d-block text-xs text-muted fw-semibold" style={{ fontSize: '11px', textTransform: 'uppercase' }}>Current Location</span>
              <strong className="text-dark fs-6">{location.city} {location.pincode ? `(${location.pincode})` : ''}</strong>
            </div>
          </div>
          <span className="badge bg-success-subtle text-success px-2 py-1 rounded-pill d-flex align-items-center gap-1" style={{ fontSize: '12px' }}>
            <IoFlashOutline /> Express Active
          </span>
        </div>

        {/* Pincode Input Form */}
        <Form onSubmit={handleApplyPincode} className="mb-4">
          <Form.Label className="fw-semibold text-dark fs-7">Enter Delivery Pincode</Form.Label>
          <div className="input-group">
            <Form.Control
              type="text"
              placeholder="e.g. 110001, 400001"
              value={pincodeInput}
              maxLength={6}
              onChange={(e) => {
                setPincodeInput(e.target.value.replace(/\D/g, ''));
                if (error) setError('');
              }}
              className="py-2"
              style={{ fontSize: '15px', letterSpacing: '1px' }}
              autoFocus
            />
            <Button 
              type="submit" 
              variant="primary" 
              className="px-4 fw-semibold"
              style={{ backgroundColor: '#0b53a1', borderColor: '#0b53a1' }}
            >
              Apply
            </Button>
          </div>
          {error && <div className="text-danger mt-1 small">{error}</div>}
          {successMsg && (
            <div className="text-success mt-1 small d-flex align-items-center gap-1">
              <IoCheckmarkCircle /> {successMsg}
            </div>
          )}
        </Form>

        {/* Popular Cities */}
        <div>
          <div className="fw-semibold text-dark fs-7 mb-2">Or Select Popular Cities</div>
          <div className="d-flex flex-wrap gap-2">
            {POPULAR_CITIES.map((c, i) => {
              const isSelected = location.city === c.name || location.pincode === c.pincode;
              return (
                <button
                  key={i}
                  type="button"
                  onClick={() => handleSelectCity(c)}
                  className={`btn btn-sm text-nowrap rounded-pill px-3 py-1 transition-all ${
                    isSelected 
                      ? 'btn-primary shadow-sm' 
                      : 'btn-outline-secondary border-secondary-subtle bg-white text-dark'
                  }`}
                  style={isSelected ? { backgroundColor: '#0b53a1', borderColor: '#0b53a1' } : { fontSize: '13px' }}
                >
                  {c.name}
                </button>
              );
            })}
          </div>
        </div>

        {/* Delivery Guarantee Info */}
        <div className="mt-4 pt-3 border-top d-flex align-items-center justify-content-between text-muted" style={{ fontSize: '12px' }}>
          <span>⚡ Same-Day Delivery available in select metro cities</span>
          <span>🔒 100% On-Time Assurance</span>
        </div>
      </Modal.Body>
    </Modal>
  );
};

export default DeliveryLocationModal;
