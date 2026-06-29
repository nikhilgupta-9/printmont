// BulkOrderWidget.jsx
import React, { useState } from 'react';
import { ImCancelCircle } from "react-icons/im";
import { IoIosCall } from 'react-icons/io';

const BulkOrderWidget = () => {
    const [showModal, setShowModal] = useState(false);
    const [showFileInput, setShowFileInput] = useState(false);

    return (
        <>
            {/* Fixed Vertical "Bulk Order" Button */}
            <div className="bulk-order-container" onClick={() => setShowModal(true)}>
                <div className="bulk-order-label bg-theme">
                    Bulk Order
                </div>
            </div>

            {/* Modal */}
            {showModal && (
                <div className="modal-overlay">
                    <div className="bulk-form-modal px-3 py-2">
                        
                        <div className='position-relative text-center mb-3 mt-2'>
                            <h5 className="mb-0 fw-bold">Contact For Bulk Requirement</h5>
                            <ImCancelCircle 
                                onClick={() => setShowModal(false)} 
                                size={25} 
                                className='text-light-subtle cursor-pointer position-absolute top-0 end-0' 
                                style={{ transform: 'translate(25%, -25%)' }}
                            />
                        </div>

                        <div className="text-center mb-4">
                            <a href="tel:+919811003511" className="text-dark fw-bold text-decoration-none fs-5">
                                <IoIosCall size={28} className='border border-success text-success rounded-circle me-2 p-1'/>
                                +91-9811003511
                            </a>
                        </div>

                        <form>
                            <div className="mb-2">
                                <label htmlFor='bulk-order-name' className="form-label fw-semibold m-0">Full Name</label>
                                <input name='bulk-order-name' id='bulk-order-name' className="form-control" placeholder="Your Good Name" />
                            </div>

                            <div className="mb-2">
                                <label htmlFor='bulk-order-number' className="form-label fw-semibold m-0">Mobile Number</label>
                                <input name='bulk-order-number' id='bulk-order-number' className="form-control" placeholder="Your Contact Number" />
                            </div>

                            <div className="mb-2">
                                <label htmlFor='bulk-order-email' className="form-label fw-semibold m-0">Email</label>
                                <input name='bulk-order-email' id='bulk-order-email' className="form-control" placeholder="Your Email Id" />
                            </div>

                            <div className="mb-2">
                                <label htmlFor='bulk-order-company' className="form-label fw-semibold m-0">Company</label>
                                <input name='bulk-order-company' id='bulk-order-company' className="form-control" placeholder="Your Company Name" />
                            </div>

                            <div className="mb-2">
                                <label htmlFor='bulk-order-budget' className="form-label fw-semibold m-0">Budget</label>
                                <input name='bulk-order-budget' id='bulk-order-budget' className="form-control" placeholder="Your Budget" />
                            </div>

                            <div className="mb-2">
                                <label htmlFor='bulk-order-requirement' className="form-label fw-semibold m-0">Product Requirement</label>
                                <textarea 
                                    className="form-control" 
                                    name='bulk-order-requirement' 
                                    id='bulk-order-requirement'
                                    placeholder="Your Product Requirement" 
                                    rows={2} 
                                />
                            </div>

                            <div className="mb-2">
                                <div className='d-flex justify-content-start align-items-center gap-2'>
                                    <input 
                                        type="checkbox" 
                                        name='availattachment'
                                        id="availattachment" 
                                        className="form-check-input bd border-2 p-1 shadow m-0"
                                        checked={showFileInput}
                                        onChange={(e) => setShowFileInput(e.target.checked)}
                                    />
                                    <label htmlFor="availattachment" className="form-label fw-semibold m-0 cursor-pointer" style={{ cursor: 'pointer' }}>
                                        Your Attachments (If Available)
                                    </label>
                                </div>

                                {/* Show file input only when checkbox is checked */}
                                {showFileInput && (
                                    <input className="form-control mt-2" type="file" id="attachments" />
                                )}
                            </div>

                            <button type="submit" className="btn btn-primary w-100 mt-2">Submit</button>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
};

export default BulkOrderWidget;
