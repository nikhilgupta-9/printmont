import React from "react";
import { Container, Row, Col, Button } from "react-bootstrap";
import { FaPhoneAlt, FaEnvelope, FaBuilding } from "react-icons/fa";
import "./contact.css"; // for small style tweaks
import { IoIosMail } from "react-icons/io";
import Categories from "../pages/category-list/Categories";

const ContactUs = () => {
    return (
        <>
        <Container className="contact-container">
            <Row className="g-3 flex-column-reverse flex-lg-row bg-white">
                {/* Left Section */}
                <Col xs={12} lg={6}>
                    <div className="p-0 p-lg-3 bg-white h-100 px-2">
                        <h5 className="fw-bold mb-3">Contact Details</h5>

                        <div className="d-flex gap-2 mb-3">
                            <Button className="bg-theme" size="sm">
                                TRACK ORDER
                            </Button>
                            <Button className="bg-theme" size="sm">
                                FAQS
                            </Button>
                        </div>

                        <div className="d-flex">
                            <img src="/online-support.png" width={70} height={'auto'} className="me-2 text-secondary contact-icon" />
                            <div>
                                <p className="mb-1">
                                    Helpline no: <strong>+91-9818532463</strong>
                                </p>
                                <p className="text-muted">(Mon - Sat: 10:30 AM - 6:30 PM)</p>
                            </div>
                        </div>

                        <div className="d-flex justify-content-start align-items-start">
                            <span ><IoIosMail className="contact-icon me-2 mt-2 p-0" /></span>
                            <div className="">
                                <p className="fw-semibold text-org mb-1">
                                    Sales enquiries and customer support
                                </p>
                                <p>
                                    <a href="mailto:support@printmont.com">
                                        support@printmont.com
                                    </a>
                                </p>
                            </div>
                        </div>

                        <div className="d-flex justify-content-start align-items-start">
                            <span ><IoIosMail className="contact-icon me-2 mt-2 p-0" /></span>
                            <div className="">
                                <p className="fw-semibold text-org mb-1">
                                    For Corporate Bulk Orders
                                </p>
                                <p>
                                    <a href="mailto:info@printmont.com">info@printmont.com</a>
                                </p>
                            </div>
                        </div>

                        <div className="mt-4">
                            <h6 className="fw-bold">Regd Factory Address.</h6>
                            <div className="d-flex align-items-start">
                                <img src="/store-black.png" width={70} height={'auto'} className="me-2 me-lg-4 text-secondary contact-add-icon mt-2" />
                                <div>
                                    <strong className="mb-1">
                                        Printmont Corporation.
                                    </strong>
                                    <p className="text-muted mb-3">
                                3398, Bagichi Acchi ji, Bara Hindu Rao, Near Filmistan Cinema,
                                Delhi India - 110006
                            </p>
                                </div>
                            </div>
                            
                            

                            <h6 className="fw-bold mt-2 mt-lg-2">Corporate Offices.</h6>
                            <div className="d-flex align-items-start">
                                <img src="/store-black.png" width={70} height={'auto'} className="me-2 me-lg-4 text-secondary contact-add-icon mt-2" />
                                <div>
                                    <strong className="mb-1">
                                        Printmont Corporation.
                                    </strong>
                                    <p className="text-muted mb-3">
                                7855 Nai Basti Bara Hindu Rao, Near Filmistan Cinema, Delhi
                                India - 110006
                            </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </Col>

                {/* Right Section */}
                <Col xs={12} lg={6}>
                    <div className="p-3 bg-white h-100 text-center d-flex flex-column justify-content-start">
                        <h5 className="fw-bold mb-3">Have Questions?</h5>
                        <div className="d-flex flex-column flex-md-row justify-content-center gap-3">
                            <Button className="text-white fw-semibold  bg-org">
                                ORDER RELATED ISSUE?
                            </Button>
                            <Button className="fw-semibold bg-theme">
                                GENERAL QUERY
                            </Button>
                        </div>
                    </div>
                </Col>
            </Row>
        </Container>
        </>
    );
};

export default ContactUs;
