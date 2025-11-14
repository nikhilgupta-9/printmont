import React, { useState } from 'react';
import { Container, Row, Col, Form, Button, Card, Dropdown, Modal } from 'react-bootstrap';
import { BiPlusCircle } from 'react-icons/bi';
import { BsGeoAltFill, BsThreeDotsVertical } from 'react-icons/bs';
// import { GeoAltFill, ThreeDotsVertical, PlusCircle } from 'react-bootstrap-icons';

// --- INITIAL DATA & STRUCTURE ---
const initialAddresses = [
    {
        id: 1,
        name: "ok singh",
        phone: "1234567890",
        pincode: "110059",
        locality: "Matiyala",
        address: "Block-z/ 401&404, basement , Block C, Sainik Nagar Colony, Matiyala, New Delhi,",
        city: "New Delhi",
        state: "Delhi",
        landmark: "",
        altPhone: "",
        type: "Work",
    },
    {
        id: 2,
        name: "Amit Singh",
        phone: "7392906618",
        pincode: "121005",
        locality: "Sector 23",
        address: "52 ki puliya, Block C, Sanjay Colony,",
        city: "Faridabad",
        state: "Haryana",
        landmark: "",
        altPhone: "",
        type: "Home",
    },
];

const emptyAddress = {
    id: null,
    name: "",
    phone: "",
    pincode: "",
    locality: "",
    address: "",
    city: "",
    state: "--Select State--",
    landmark: "",
    altPhone: "",
    type: "Home",
};

const statesList = ["--Select State--", "Delhi", "Haryana", "Uttar Pradesh", "Maharashtra"];

// --- 1. Saved Address Card Component (Updated) ---
const SavedAddressCard = ({ data, onEdit, onDelete }) => {
    return (
        <Card className="mb-3 p-3 border-0 shadow-sm">
            <div className="d-flex justify-content-between align-items-start">
                <div className="flex-grow-1">
                    <h6 className="fw-bold mb-1 d-flex align-items-center">
                        {data.name} <span className="text-muted ms-2 me-3 small">{data.phone}</span>
                        <span className="badge text-uppercase p-1" style={{ backgroundColor: data.type === 'Home' ? '#e0f7fa' : '#fff3e0', color: data.type === 'Home' ? '#00bcd4' : '#ff9800', fontSize: '0.65rem' }}>
                            {data.type}
                        </span>
                    </h6>
                    <p className="text-muted mb-0 small">
                        {data.address}, {data.locality}, {data.city}, {data.state} - {data.pincode}
                    </p>
                </div>

                {/* Vertical Three Dots Dropdown for Edit/Delete */}
                <Dropdown align="end">
                    <Dropdown.Toggle variant="light" id={`dropdown-${data.id}`} className="bg-white border-0 p-0">
                        <BsThreeDotsVertical size={20} className="text-muted" />
                    </Dropdown.Toggle>

                    <Dropdown.Menu>
                        <Dropdown.Item onClick={() => onEdit(data)}>Edit</Dropdown.Item>
                        <Dropdown.Item onClick={() => onDelete(data.id)}>Delete</Dropdown.Item>
                    </Dropdown.Menu>
                </Dropdown>
            </div>
        </Card>
    );
};

// --- 2. Address Form Component ---
const AddressForm = ({ formData, handleInputChange, handleRadioChange, handleSave, handleCancel }) => {
    return (
        <Card className="mb-4 p-1 border-0 shadow-sm" style={{ borderTop: '3px solid #2196F3', borderRadius: '0.5rem' }}>
            <h6 className="text-uppercase fw-bold text-muted mb-4">
                {formData.id ? 'EDIT ADDRESS' : 'ADD A NEW ADDRESS'}
            </h6>

            {/* Use My Current Location Button */}
            <Button
                variant="primary"
                className="mb-4 d-flex align-items-center justify-content-center"
                style={{ width: 'fit-content', backgroundColor: '#2196F3', borderColor: '#2196F3' }}
            >
                <BsGeoAltFill className="me-2" /> Use my current location
            </Button>

            <Form onSubmit={handleSave}>
                {/* Row 1: Name and Mobile */}
                <Row className="mb-3">
                    <Col md={6}>
                        <Form.Control type="text" name="name" placeholder="Name" value={formData.name} onChange={handleInputChange} required />
                    </Col>
                    <Col md={6}>
                        <Form.Control type="text" name="phone" placeholder="10-digit mobile number" value={formData.phone} onChange={handleInputChange} required />
                    </Col>
                </Row>

                {/* Row 2: Pincode and Locality */}
                <Row className="mb-3">
                    <Col md={6}>
                        <Form.Control type="text" name="pincode" placeholder="Pincode" value={formData.pincode} onChange={handleInputChange} required />
                    </Col>
                    <Col md={6}>
                        <Form.Control type="text" name="locality" placeholder="Locality" value={formData.locality} onChange={handleInputChange} required />
                    </Col>
                </Row>

                {/* Row 3: Address (Area and Street) */}
                <Row className="mb-3">
                    <Col>
                        <Form.Control as="textarea" rows={3} name="address" placeholder="Address (Area and Street)" value={formData.address} onChange={handleInputChange} required />
                    </Col>
                </Row>

                {/* Row 4: City/District/Town and State */}
                <Row className="mb-3">
                    <Col md={6}>
                        <Form.Control type="text" name="city" placeholder="City/District/Town" value={formData.city} onChange={handleInputChange} required />
                    </Col>
                    <Col md={6}>
                        <Form.Select name="state" value={formData.state} onChange={handleInputChange} required>
                            {statesList.map(state => <option key={state} value={state}>{state}</option>)}
                        </Form.Select>
                    </Col>
                </Row>

                {/* Row 5: Landmark and Alternate Phone */}
                <Row className="mb-4">
                    <Col md={6}>
                        <Form.Control type="text" name="landmark" placeholder="Landmark (Optional)" value={formData.landmark} onChange={handleInputChange} />
                    </Col>
                    <Col md={6}>
                        <Form.Control type="text" name="altPhone" placeholder="Alternate Phone (Optional)" value={formData.altPhone} onChange={handleInputChange} />
                    </Col>
                </Row>

                {/* Address Type Radio Buttons */}
                <div className="mb-4">
                    <Form.Label className="fw-bold text-muted small">Address Type</Form.Label>
                    <div className="d-flex">
                        <Form.Check
                            type="radio"
                            id="addressTypeHome"
                            label="Home"
                            name="type"
                            value="Home"
                            checked={formData.type === 'Home'}
                            onChange={handleRadioChange}
                            className="me-4"
                        />
                        <Form.Check
                            type="radio"
                            id="addressTypeWork"
                            label="Work"
                            name="type"
                            value="Work"
                            checked={formData.type === 'Work'}
                            onChange={handleRadioChange}
                        />
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="d-flex align-items-center">
                    <Button
                        type="submit"
                        variant="primary"
                        className="me-3 px-5"
                        style={{ backgroundColor: '#2196F3', borderColor: '#2196F3' }}
                    >
                        SAVE
                    </Button>
                    <Button type="button" variant="link" onClick={handleCancel} className="text-decoration-none text-uppercase text-primary fw-bold p-0">
                        CANCEL
                    </Button>
                </div>
            </Form>
        </Card>
    );
};

// --- 3. Main Manage Address Component ---
const ManageAddress = () => {
    const [addresses, setAddresses] = useState(initialAddresses);
    const [showForm, setShowForm] = useState(false);
    const [formData, setFormData] = useState(emptyAddress);

    // --- FORM HANDLING ---
    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleRadioChange = (e) => {
        setFormData(prev => ({ ...prev, type: e.target.value }));
    };

    const handleCancel = () => {
        setFormData(emptyAddress);
        setShowForm(false);
    };

    const handleSave = (e) => {
        e.preventDefault();
        
        if (formData.id) {
            // EDIT/UPDATE logic
            setAddresses(prev => prev.map(addr => addr.id === formData.id ? formData : addr));
        } else {
            // NEW ADDRESS logic
            const newAddress = { ...formData, id: Date.now() }; // Simple unique ID
            setAddresses(prev => [...prev, newAddress]);
        }

        handleCancel(); // Reset form and hide it
    };

    // --- CRUD OPERATIONS ---
    const handleEdit = (address) => {
        setFormData(address);
        setShowForm(true);
    };

    const handleDelete = (id) => {
        if (window.confirm("Are you sure you want to delete this address?")) {
            setAddresses(prev => prev.filter(addr => addr.id !== id));
        }
    };

    const handleAddAddressClick = () => {
        setFormData(emptyAddress);
        setShowForm(true);
    };

    return (
        <Container className="py-3 bg-white px-2 h-100">
            {/* --- ADD ADDRESS BUTTON --- */}
            <div className="mb-3">
                {!showForm && (
                    <Button 
                        variant="outline-primary" 
                        onClick={handleAddAddressClick}
                        className="d-flex align-items-center fw-bold p-3 bg-theme text-white"
                    >
                        <BiPlusCircle size={20} className="me-2" /> ADD A NEW ADDRESS
                    </Button>
                )}
            </div>

            {/* --- ADDRESS FORM SECTION (Conditional Rendering) --- */}
            {showForm && (
                <AddressForm
                    formData={formData}
                    handleInputChange={handleInputChange}
                    handleRadioChange={handleRadioChange}
                    handleSave={handleSave}
                    handleCancel={handleCancel}
                />
            )}

            {/* --- SAVED ADDRESSES LIST SECTION --- */}
            <div>
                {addresses.map((address) => (
                    <SavedAddressCard
                        key={address.id}
                        data={address}
                        onEdit={handleEdit}
                        onDelete={handleDelete}
                    />
                ))}
            </div>
        </Container>
    );
};

export default ManageAddress;