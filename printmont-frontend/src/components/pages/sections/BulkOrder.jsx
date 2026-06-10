import React, { useState } from 'react';
import { ImCancelCircle } from "react-icons/im";
import { IoIosCall } from 'react-icons/io';

const BulkOrder = () => {
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
                        
                        <div className='row p-0'>
                            {/* <div className='col-3 p-0 m-0'> */}
                                <div className='position-relative'>
                                    <ImCancelCircle 
                                        onClick={() => setShowModal(false)} 
                                        size={25} 
                                        className='text-light-subtle cursor-pointer position-absolute end-0 bottom-1' 
                                    />
                                    
                                </div>
                            {/* </div> */}
                            <div className='col-9 m-0 p-0 mb-2'>
                                <div className='d-flex justify-content-end align-items-center'>
                                    <h5 className="mb-0 fw-bold">Contact For Bulk Requirement</h5>
                                </div>
                            </div>
                            {/* dfghjkl */}
                        </div>

                        <div className="text-center mb-4">
                            <i className="bi bi-telephone-fill"></i>{' '}
                            <a href="tel:+919811003511" className="text-primary num"><IoIosCall size={18} className='border border-primary rounded-circle me-2'/>+91-9811003511</a>
                        </div>

                        <form>
                            <div className="mb-3">
                                <label htmlFor='bulk-order-name' className="form-label fw-semibold m-0">Full Name</label>
                                <input name='bulk-order-name' id='bulk-order-name' className="form-control" placeholder="Your Good Name" />
                            </div>

                            <div className="mb-3">
                                <label htmlFor='bulk-order-number' className="form-label fw-semibold m-0">Mobile Number</label>
                                <input name='bulk-order-number' id='bulk-order-number' className="form-control" placeholder="Your Contact Number" />
                            </div>

                            <div className="mb-3">
                                <label htmlFor='bulk-order-email' className="form-label fw-semibold m-0">Email</label>
                                <input name='bulk-order-email' id='bulk-order-email' className="form-control" placeholder="Your Email Id" />
                            </div>

                            <div className="mb-3">
                                <label htmlFor='bulk-order-company' className="form-label fw-semibold m-0">Company</label>
                                <input name='bulk-order-company' id='bulk-order-company' className="form-control" placeholder="Your Company Name" />
                            </div>

                            <div className="mb-3">
                                <label htmlFor='bulk-order-budget' className="form-label fw-semibold m-0">Budget</label>
                                <input name='bulk-order-budget' id='bulk-order-budget' className="form-control" placeholder="Your Budget" />
                            </div>

                            <div className="mb-3">
                                <label htmlFor='bulk-order-requirement'  className="form-label fw-semibold m-0">Product Requirement</label>
                                <input className="form-control" name='bulk-order-requirement' id='bulk-order-requirement'
                                 placeholder="Your Product Requirement" />
                            </div>

                            <div className="mb-3">
                                <div className='d-flex justify-content-start align-items-center gap-3'>
                                    <label htmlFor="availattachment" className="form-label fw-semibold m-0">
                                        Your Attachments (If Available)
                                    </label>
                                    <input 
                                        type="checkbox" 
                                        name='availattachment'
                                        id="availattachment" 
                                        className="form-check-input bd border-2 p-2 shadow"
                                        checked={showFileInput}
                                        onChange={(e) => setShowFileInput(e.target.checked)}
                                    />
                                </div>

                                {/* Show file input only when checkbox is checked */}
                                {showFileInput && (
                                    <input className="form-control mt-2" type="file" id="attachments" />
                                )}
                            </div>

                            <button type="submit" className="btn btn-primary w-100">Submit</button>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
};

export default BulkOrder;
