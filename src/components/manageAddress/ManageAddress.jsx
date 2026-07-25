import React, { useState, useEffect } from 'react';
import { Row, Col, Form, Button, Card, Dropdown, Spinner, Badge } from 'react-bootstrap';
import { 
  FaHome, 
  FaBriefcase, 
  FaTrashAlt, 
  FaEdit, 
  FaMapMarkerAlt, 
  FaPlus, 
  FaPhoneAlt, 
  FaMapPin, 
  FaEllipsisV,
  FaArrowLeft
} from 'react-icons/fa';
import { useAuth } from '../../context/AuthContext';
import { API_ENDPOINTS } from '../../config/apiEndpoints';

const statesList = [
  "Delhi", 
  "Haryana", 
  "Uttar Pradesh", 
  "Maharashtra",
  "Karnataka",
  "Gujarat",
  "Rajasthan",
  "Punjab",
  "Tamil Nadu",
  "Telangana",
  "West Bengal"
];

const emptyAddress = {
  id: null,
  name: "",
  phone: "",
  pincode: "",
  locality: "",
  address: "",
  city: "",
  state: "Delhi",
  landmark: "",
  altPhone: "",
  type: "Home",
};

// --- SAVED ADDRESS CARD COMPONENT ---
const SavedAddressCard = ({ data, onEdit, onDelete }) => {
  return (
    <Card
      className="address-card border-0 shadow-sm rounded-4 p-4 mb-3 position-relative bg-white border-start border-4"
      style={{
        borderLeftColor: data.type === 'Home' ? '#0b53a1' : '#f59e0b',
        boxShadow: "0 10px 30px rgba(0, 0, 0, 0.04)"
      }}
    >
      <div className="d-flex justify-content-between align-items-start gap-3">
        <div className="flex-grow-1 min-width-0">
          {/* Header row with Name and Type Badge */}
          <div className="d-flex flex-wrap align-items-center gap-2 mb-2">
            <h5 className="fw-bold text-dark mb-0 fs-6">{data.name}</h5>
            <Badge
              bg={data.type === 'Home' ? 'primary-subtle' : 'warning-subtle'}
              className={`text-uppercase px-2 py-1 rounded-pill fw-bold d-inline-flex align-items-center gap-1 ${data.type === 'Home' ? 'text-primary' : 'text-warning-emphasis'}`}
              style={{ fontSize: '0.7rem', letterSpacing: '0.03em' }}
            >
              {data.type === 'Home' ? <FaHome size={11} /> : <FaBriefcase size={11} />}
              {data.type}
            </Badge>
          </div>

          {/* Contact Details */}
          <div className="d-flex flex-wrap align-items-center gap-2 text-secondary mb-3 small">
            <span className="d-inline-flex align-items-center gap-1">
              <FaPhoneAlt size={12} className="text-muted" /> {data.phone}
            </span>
            {data.altPhone && (
              <span className="d-inline-flex align-items-center gap-1 border-start ps-2">
                Alt: {data.altPhone}
              </span>
            )}
          </div>

          {/* Detailed Address Block — icon in its own column so wrapped lines
              stay aligned with the first line instead of sliding under the icon. */}
          <div className="d-flex gap-2 small" style={{ lineHeight: "1.6" }}>
            <FaMapPin className="text-danger flex-shrink-0 mt-1" size={14} />
            <div className="min-width-0">
              <div className="text-dark-emphasis fw-semibold">{data.address}</div>
              <div className="text-muted">
                {data.locality}, {data.city}, {data.state} — <strong>{data.pincode}</strong>
              </div>
              {data.landmark && (
                <div className="mt-1 text-secondary-emphasis" style={{ fontSize: '0.8rem' }}>
                  Landmark: <em>{data.landmark}</em>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Action Controls */}
        <Dropdown align="end" className="flex-shrink-0">
          <Dropdown.Toggle variant="light" className="address-actions bg-transparent border-0 p-2 rounded-circle shadow-none">
            <FaEllipsisV className="text-muted" size={16} />
          </Dropdown.Toggle>

          <Dropdown.Menu className="border-0 shadow rounded-3 overflow-hidden">
            <Dropdown.Item onClick={() => onEdit(data)} className="d-flex align-items-center gap-2 py-2 small">
              <FaEdit className="text-primary" /> Edit Address
            </Dropdown.Item>
            <Dropdown.Divider className="my-0" />
            <Dropdown.Item onClick={() => onDelete(data.id)} className="d-flex align-items-center gap-2 py-2 small text-danger">
              <FaTrashAlt /> Delete Address
            </Dropdown.Item>
          </Dropdown.Menu>
        </Dropdown>
      </div>
    </Card>
  );
};

// --- ADDRESS FORM COMPONENT ---
const AddressForm = ({ formData, handleInputChange, setFormData, handleSave, handleCancel }) => {
  const [locating, setLocating] = useState(false);

  // Geo Location Mock/API Fetch
  const handleGeoLocation = () => {
    if (!navigator.geolocation) {
      alert("Geolocation is not supported by your browser");
      return;
    }
    setLocating(true);
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        try {
          const { latitude, longitude } = position.coords;
          const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`);
          const geoData = await response.json();
          if (geoData && geoData.address) {
            const addr = geoData.address;
            setFormData(prev => ({
              ...prev,
              address: geoData.display_name || "",
              city: addr.city || addr.town || addr.village || "",
              state: addr.state || "Delhi",
              pincode: addr.postcode || "",
              locality: addr.suburb || addr.neighbourhood || ""
            }));
          }
        } catch (error) {
          console.error("Geolocation fetch error:", error);
        } finally {
          setLocating(false);
        }
      },
      () => {
        setLocating(false);
        alert("Unable to retrieve your location. Please input fields manually.");
      }
    );
  };

  return (
    <Card className="border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 bg-white border-top border-4 border-primary">
      <div className="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <h5 className="fw-bold text-dark m-0 fs-5">
          {formData.id ? 'Modify Shipping Address' : 'Create Shipping Address'}
        </h5>
        <Button variant="outline-secondary" size="sm" onClick={handleCancel} className="rounded-pill d-flex align-items-center gap-1">
          <FaArrowLeft size={12} /> Back
        </Button>
      </div>

      {/* Auto Location Helper */}
      <div className="d-flex justify-content-start mb-4">
        <Button
          type="button"
          variant="primary"
          onClick={handleGeoLocation}
          disabled={locating}
          className="d-flex align-items-center gap-2 rounded-pill px-4 py-2 shadow-sm fw-bold border-0"
          style={{ backgroundColor: '#0b53a1' }}
        >
          {locating ? (
            <>
              <Spinner size="sm" animation="border" className="me-1" /> Locating...
            </>
          ) : (
            <>
              <FaMapMarkerAlt /> Use My Current Location
            </>
          )}
        </Button>
      </div>

      <Form onSubmit={handleSave}>
        <Row className="g-3 mb-3">
          <Col md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">Contact Name *</Form.Label>
              <Form.Control 
                type="text" 
                name="name" 
                placeholder="Full Name" 
                value={formData.name} 
                onChange={handleInputChange} 
                className="py-2 rounded-3 shadow-none border"
                required 
              />
            </Form.Group>
          </Col>
          <Col md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">10-Digit Mobile Number *</Form.Label>
              <Form.Control 
                type="tel" 
                name="phone" 
                pattern="[0-9]{10}"
                placeholder="Mobile number" 
                value={formData.phone} 
                onChange={handleInputChange} 
                className="py-2 rounded-3 shadow-none border"
                required 
              />
            </Form.Group>
          </Col>
        </Row>

        <Row className="g-3 mb-3">
          <Col md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">Pincode *</Form.Label>
              <Form.Control 
                type="text" 
                name="pincode" 
                placeholder="6-digit Pincode" 
                value={formData.pincode} 
                onChange={handleInputChange} 
                className="py-2 rounded-3 shadow-none border"
                required 
              />
            </Form.Group>
          </Col>
          <Col md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">Locality / Sector *</Form.Label>
              <Form.Control 
                type="text" 
                name="locality" 
                placeholder="Locality" 
                value={formData.locality} 
                onChange={handleInputChange} 
                className="py-2 rounded-3 shadow-none border"
                required 
              />
            </Form.Group>
          </Col>
        </Row>

        <Form.Group className="mb-3">
          <Form.Label className="small fw-semibold text-secondary">Address details (Flat No, Building, Street) *</Form.Label>
          <Form.Control 
            as="textarea" 
            rows={3} 
            name="address" 
            placeholder="Complete delivery address details" 
            value={formData.address} 
            onChange={handleInputChange} 
            className="rounded-3 shadow-none border"
            required 
          />
        </Form.Group>

        <Row className="g-3 mb-3">
          <Col md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">City / Town *</Form.Label>
              <Form.Control 
                type="text" 
                name="city" 
                placeholder="City/District/Town" 
                value={formData.city} 
                onChange={handleInputChange} 
                className="py-2 rounded-3 shadow-none border"
                required 
              />
            </Form.Group>
          </Col>
          <Col md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">State *</Form.Label>
              <Form.Select 
                name="state" 
                value={formData.state} 
                onChange={handleInputChange} 
                className="py-2 rounded-3 shadow-none border"
                required
              >
                {statesList.map(st => <option key={st} value={st}>{st}</option>)}
              </Form.Select>
            </Form.Group>
          </Col>
        </Row>

        <Row className="g-3 mb-4">
          <Col md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">Landmark (Optional)</Form.Label>
              <Form.Control 
                type="text" 
                name="landmark" 
                placeholder="E.g. Near metro station" 
                value={formData.landmark} 
                onChange={handleInputChange} 
                className="py-2 rounded-3 shadow-none border"
              />
            </Form.Group>
          </Col>
          <Col md={6}>
            <Form.Group>
              <Form.Label className="small fw-semibold text-secondary">Alternate Phone (Optional)</Form.Label>
              <Form.Control 
                type="tel" 
                name="altPhone" 
                placeholder="Alternate phone number" 
                value={formData.altPhone} 
                onChange={handleInputChange} 
                className="py-2 rounded-3 shadow-none border"
              />
            </Form.Group>
          </Col>
        </Row>

        {/* INTERACTIVE TOGGLE PILLS FOR ADDRESS TYPE */}
        <div className="mb-4">
          <Form.Label className="small fw-semibold text-secondary d-block mb-2">Address Classification</Form.Label>
          <div className="d-flex gap-2">
            <Button
              type="button"
              variant={formData.type === 'Home' ? 'primary' : 'outline-secondary'}
              className="d-flex align-items-center gap-2 rounded-pill px-4 py-2 fw-semibold"
              onClick={() => setFormData(prev => ({ ...prev, type: 'Home' }))}
            >
              <FaHome size={16} /> Home
            </Button>
            <Button
              type="button"
              variant={formData.type === 'Work' ? 'warning' : 'outline-secondary'}
              className={`d-flex align-items-center gap-2 rounded-pill px-4 py-2 fw-semibold ${formData.type === 'Work' ? 'text-white' : ''}`}
              onClick={() => setFormData(prev => ({ ...prev, type: 'Work' }))}
            >
              <FaBriefcase size={16} /> Work / Office
            </Button>
          </div>
        </div>

        {/* Action Controls */}
        <div className="d-flex align-items-center gap-3 pt-3 border-top">
          <Button
            type="submit"
            variant="primary"
            className="px-5 py-2 rounded-pill fw-bold border-0 shadow-sm"
            style={{ backgroundColor: '#0b53a1' }}
          >
            Save Address
          </Button>
          <Button type="button" variant="light" onClick={handleCancel} className="px-4 py-2 rounded-pill fw-bold text-secondary border">
            Cancel
          </Button>
        </div>
      </Form>
    </Card>
  );
};

// --- MAIN MANAGE ADDRESS COMPONENT ---
const ManageAddress = () => {
  const { user, token } = useAuth();
  const [addresses, setAddresses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [formData, setFormData] = useState(emptyAddress);

  const fetchAddresses = async () => {
    if (!user?.id) return;
    try {
      setLoading(true);
      const res = await fetch(`${API_ENDPOINTS.GET_ADDRESSES}&user_id=${user.id}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await res.json();
      if (data.success || data.status === 'success') {
        setAddresses(data.data || data.addresses || []);
      }
    } catch (err) {
      console.error("Failed to load addresses", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAddresses();
  }, [user]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleCancel = () => {
    setFormData(emptyAddress);
    setShowForm(false);
  };

  const handleSave = async (e) => {
    e.preventDefault();
    if (!user?.id) {
      alert("You must be logged in to save an address.");
      return;
    }

    const payload = { ...formData, user_id: user.id };
    const isEdit = !!formData.id;
    const endpoint = isEdit ? API_ENDPOINTS.UPDATE_ADDRESS : API_ENDPOINTS.ADD_ADDRESS;

    try {
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      
      if (data.success || data.status === 'success') {
        fetchAddresses();
        handleCancel();
      } else {
        alert(data.message || "Failed to save address");
      }
    } catch (err) {
      console.error("Error saving address", err);
      alert("An error occurred while saving the address.");
    }
  };

  const handleEdit = (address) => {
    setFormData(address);
    setShowForm(true);
  };

  const handleDelete = async (id) => {
    if (window.confirm("Are you sure you want to delete this address?")) {
      try {
        const res = await fetch(API_ENDPOINTS.DELETE_ADDRESS, {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify({ id, user_id: user?.id })
        });
        const data = await res.json();
        if (data.success || data.status === 'success') {
          fetchAddresses();
        } else {
          alert(data.message || "Failed to delete address.");
        }
      } catch (err) {
        console.error("Error deleting address", err);
      }
    }
  };

  const handleAddAddressClick = () => {
    setFormData(emptyAddress);
    setShowForm(true);
  };

  return (
    /* No <Container> / bg wrapper here on purpose: this page renders inside the
       <User /> layout route, which already provides the container, the account
       sidebar and the content column. Nesting another container double-padded the
       content and pushed it out of line with the sibling account pages. */
    <div className="pb-4">
      {/* Header info bar */}
      <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div className="min-width-0">
          <h4 className="fw-bold text-dark mb-1">Manage Delivery Addresses</h4>
          <p className="text-secondary small mb-0">Configure, add, or delete shipping destinations for your orders.</p>
        </div>
        {!showForm && (
          <Button
            variant="primary"
            onClick={handleAddAddressClick}
            className="d-inline-flex align-items-center gap-2 rounded-pill px-4 py-2 fw-bold border-0 shadow-sm flex-shrink-0"
            style={{ backgroundColor: '#0b53a1' }}
          >
            <FaPlus /> Add New Address
          </Button>
        )}
      </div>

      {/* Form Section */}
      {showForm && (
        <AddressForm
          formData={formData}
          handleInputChange={handleInputChange}
          setFormData={setFormData}
          handleSave={handleSave}
          handleCancel={handleCancel}
        />
      )}

      {/* Addresses list */}
      {!showForm && (
        <>
          {loading ? (
            <div className="text-center py-5">
              <Spinner animation="border" variant="primary" />
              <p className="text-muted small mt-3 mb-0">Loading addresses...</p>
            </div>
          ) : addresses.length === 0 ? (
            <Card className="border-0 shadow-sm rounded-4 p-5 bg-white align-items-center text-center">
              <FaMapMarkerAlt size={48} className="text-muted opacity-50 mb-3" />
              <h5 className="fw-bold text-dark mb-2">No Saved Addresses</h5>
              <p className="text-secondary small mb-4" style={{ maxWidth: "320px" }}>
                Add your shipping address details to place orders and receive quick delivery estimates.
              </p>
              <Button
                variant="primary"
                onClick={handleAddAddressClick}
                className="d-inline-flex align-items-center gap-2 rounded-pill px-4 py-2 fw-bold border-0"
                style={{ backgroundColor: '#0b53a1' }}
              >
                <FaPlus /> Create Address
              </Button>
            </Card>
          ) : (
            <div className="d-flex flex-column">
              {addresses.map((address) => (
                <SavedAddressCard
                  key={address.id}
                  data={address}
                  onEdit={handleEdit}
                  onDelete={handleDelete}
                />
              ))}
            </div>
          )}
        </>
      )}
    </div>
  );
};

export default ManageAddress;