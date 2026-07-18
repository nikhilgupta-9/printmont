import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Form, Button, Card, Dropdown, Spinner, Badge } from 'react-bootstrap';
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
  FaArrowLeft,
  FaCheck,
  FaSignOutAlt,
  FaBoxOpen,
  FaWallet,
  FaUser
} from 'react-icons/fa';
import { useAuth } from '../../context/AuthContext';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import { Link, useNavigate } from 'react-router-dom';

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
      className="border-0 shadow-sm rounded-4 p-4 mb-3 position-relative overflow-hidden bg-white border-start border-4 transition-all" 
      style={{ 
        borderColor: data.type === 'Home' ? '#0b53a1' : '#f59e0b',
        boxShadow: "0 10px 30px rgba(0, 0, 0, 0.04)"
      }}
    >
      <div className="d-flex justify-content-between align-items-start">
        <div className="flex-grow-1">
          {/* Header row with Name and Type Badge */}
          <div className="d-flex flex-wrap align-items-center gap-2 mb-2">
            <h5 className="fw-bold text-dark mb-0 fs-6">{data.name}</h5>
            <Badge 
              bg={data.type === 'Home' ? 'primary-subtle' : 'warning-subtle'} 
              className={`text-uppercase px-2.5 py-1 rounded-pill fw-bold text-xs d-flex align-items-center gap-1 ${data.type === 'Home' ? 'text-primary' : 'text-warning-emphasis'}`}
            >
              {data.type === 'Home' ? <FaHome size={12} /> : <FaBriefcase size={12} />}
              {data.type}
            </Badge>
          </div>

          {/* Contact Details */}
          <div className="d-flex align-items-center gap-3 text-secondary mb-3 small">
            <span className="d-flex align-items-center gap-1">
              <FaPhoneAlt size={12} className="text-muted" /> {data.phone}
            </span>
            {data.altPhone && (
              <span className="d-flex align-items-center gap-1 border-start ps-3">
                Alt: {data.altPhone}
              </span>
            )}
          </div>

          {/* Detailed Address Block */}
          <p className="text-dark-emphasis mb-2 small" style={{ lineHeight: "1.6" }}>
            <FaMapPin className="text-danger me-1 flex-shrink-0" size={14} />
            <strong>{data.address}</strong>
          </p>
          <div className="text-muted small ps-3">
            {data.locality}, {data.city}, {data.state} — <strong>{data.pincode}</strong>
            {data.landmark && <div className="mt-1 text-xs text-secondary-emphasis">Landmark: <em>{data.landmark}</em></div>}
          </div>
        </div>

        {/* Action Controls */}
        <Dropdown align="end">
          <Dropdown.Toggle variant="light" className="bg-transparent border-0 p-1.5 rounded-circle shadow-none">
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
          className="d-flex align-items-center gap-2 rounded-pill px-4 py-2.5 shadow-sm fw-bold border-0"
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
                className="py-2.5 rounded-3 shadow-none border"
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
                className="py-2.5 rounded-3 shadow-none border"
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
                className="py-2.5 rounded-3 shadow-none border"
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
                className="py-2.5 rounded-3 shadow-none border"
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
                className="py-2.5 rounded-3 shadow-none border"
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
                className="py-2.5 rounded-3 shadow-none border"
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
                className="py-2.5 rounded-3 shadow-none border"
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
                className="py-2.5 rounded-3 shadow-none border"
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
            className="px-5 py-2.5 rounded-pill fw-bold border-0 shadow-sm"
            style={{ backgroundColor: '#0b53a1' }}
          >
            Save Address
          </Button>
          <Button type="button" variant="light" onClick={handleCancel} className="px-4 py-2.5 rounded-pill fw-bold text-secondary border">
            Cancel
          </Button>
        </div>
      </Form>
    </Card>
  );
};

// --- MAIN MANAGE ADDRESS COMPONENT ---
const ManageAddress = () => {
  const navigate = useNavigate();
  const { user, token, logout } = useAuth();
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

  const handleLogout = () => {
    logout();
    navigate("/login");
  };

  // Helper to get initials
  const getUserInitials = () => {
    if (!user) return "U";
    const first = user.firstName || user.first_name || user.name || "U";
    const last = user.lastName || user.last_name || "";
    return (first[0] + (last ? last[0] : "")).toUpperCase();
  };

  // Helper to get display name
  const getUserDisplayName = () => {
    if (!user) return "Store Guest";
    if (user.firstName || user.first_name) {
      return `${user.firstName || user.first_name} ${user.lastName || user.last_name || ""}`.trim();
    }
    return user.name || (user.email ? user.email.split("@")[0] : "User");
  };

  return (
    <div className="bg-light py-4 py-md-5" style={{ minHeight: "85vh" }}>
      <Container>
        <Row className="g-4">
          {/* LEFT USER SIDEBAR NAVIGATION (Desktop Only, matches Flipkart/Amazon account experience) */}
          <Col lg={3} md={4} className="d-none d-md-block">
            {/* User Profile Card */}
            <Card className="border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
              <div className="d-flex align-items-center gap-3">
                <div className="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold fs-5" style={{ width: "50px", height: "50px", backgroundColor: "#0b53a1" }}>
                  {getUserInitials()}
                </div>
                <div className="min-w-0">
                  <div className="text-secondary small">Hello,</div>
                  <h6 className="fw-bold text-dark text-truncate mb-0" style={{ fontSize: "0.95rem" }}>{getUserDisplayName()}</h6>
                </div>
              </div>
            </Card>

            {/* Sidebar Navigation */}
            <Card className="border-0 shadow-sm rounded-4 p-3 bg-white">
              <div className="d-flex flex-column gap-1">
                <Link to="/orders" className="d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 fw-semibold text-secondary text-decoration-none hover-bg-light">
                  <FaBoxOpen className="text-primary" size={18} />
                  <span>My Orders</span>
                </Link>
                <Link to="/user/manage-address" className="d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 fw-semibold text-primary text-decoration-none bg-primary-subtle shadow-sm">
                  <FaMapMarkerAlt className="text-primary" size={18} />
                  <span>Saved Addresses</span>
                </Link>
                <Link to="/printmont-coin" className="d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 fw-semibold text-secondary text-decoration-none hover-bg-light">
                  <FaWallet className="text-primary" size={18} />
                  <span>PrintCoins Wallet</span>
                </Link>
                <Link to="/become-a-seller" className="d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 fw-semibold text-secondary text-decoration-none hover-bg-light">
                  <FaStore className="text-primary" size={18} />
                  <span>Sell on PrintMont</span>
                </Link>
                <hr className="my-2 border-secondary border-opacity-25" />
                <Button variant="link" onClick={handleLogout} className="d-flex align-items-center gap-3 px-3 py-2.5 rounded-3 fw-semibold text-danger text-decoration-none hover-bg-light border-0 text-start">
                  <FaSignOutAlt size={18} />
                  <span>Logout Account</span>
                </Button>
              </div>
            </Card>
          </Col>

          {/* MAIN MANAGE ADDRESS PORTLET */}
          <Col lg={9} md={8} xs={12}>
            {/* Header info bar */}
            <div className="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
              <div>
                <h4 className="fw-bold text-dark mb-1">Manage Delivery Addresses</h4>
                <p className="text-secondary small mb-0">Configure, add, or delete shipping destinations for your orders.</p>
              </div>
              {!showForm && (
                <Button 
                  variant="primary" 
                  onClick={handleAddAddressClick}
                  className="d-flex align-items-center gap-2 rounded-pill px-4 py-2.5 fw-bold border-0 shadow-sm transition-all"
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
              <div>
                {loading ? (
                  <div className="text-center py-5">
                    <Spinner animation="border" variant="primary" />
                    <p className="text-muted small mt-2">Loading addresses...</p>
                  </div>
                ) : addresses.length === 0 ? (
                  <Card className="border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                    <FaMapMarkerAlt size={48} className="text-muted opacity-50 mb-3 mx-auto" />
                    <h5 className="fw-bold text-dark mb-2">No Saved Addresses</h5>
                    <p className="text-secondary small mx-auto mb-4" style={{ maxWidth: "320px" }}>
                      Add your shipping address details to place orders and receive quick delivery estimates.
                    </p>
                    <Button 
                      variant="primary" 
                      onClick={handleAddAddressClick}
                      className="d-inline-flex align-items-center gap-2 rounded-pill px-4 py-2.5 fw-bold mx-auto border-0"
                      style={{ backgroundColor: '#0b53a1' }}
                    >
                      <FaPlus /> Create Address
                    </Button>
                  </Card>
                ) : (
                  <div className="d-flex flex-column gap-1">
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
              </div>
            )}
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default ManageAddress;