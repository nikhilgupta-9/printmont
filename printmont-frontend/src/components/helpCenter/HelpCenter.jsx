import React, { useState } from "react";
import { Accordion, ListGroup, Button, Collapse } from "react-bootstrap";
import "bootstrap/dist/css/bootstrap.min.css";
import './help.css'
import { MdKeyboardArrowDown } from "react-icons/md";
import Categories from "../pages/category-list/Categories";

const HelpCenter = () => {

  const categories = [
    "My Account",
    "Delivery information",
    "Order modification/cancellation",
    "Designing My product",
    "Products",
    "Payments and Refunds",
  ];

  const faqs = [
    "Do you deliver only within India or overseas?",
    "Can I choose the delivery time?",
    "Can I get my order delivered at midnight?",
    "What are the different modes of delivery?",
    "What are the delivery charges?",
    "I don’t want to disclose my personal information to the recipient. Is this possible?",
    "How do I track my order?",
    "What do the different order statuses mean?",
    "My order is partially delivered.",
    "Date of delivery has lapsed, when will I get my order or refund?",
  ];

  const [activeCategory, setActiveCategory] = useState("Delivery information");
  const [open, setOpen] = useState(false);

  return (
    <>
    <div className="bg-white d-none d-lg-flex py-3 my-1 mb-3">
        {/* <div className="">
        </div> */}
        <Categories space={"15px 0px"} showImages={false} bg="rgb(11, 83, 161)" color="white" />
      </div>
    <div className="container-fluid py-2 py-lg-4 bg-white mt-0 px-0 mx-0">
      {/* --- CONTACT INFORMATION --- */}
      <div className="bg-white shadow-sm p-0 mx-0 mb-3 row px-0 ">

        <div className="col-12 col-lg-4 px-0 px-lg-2">
          <div className="d-flex flex-row flex-lg-column justify-content-start help-bd align-items-center py-3 px-2 px-lg-4 gap-2">
            <div className="me-lg-3 fs-3 bg-secondary-subtle p-3 rounded-circle d-flex align-items-center justify-content-center">
              <img src="/help-desk.png" className="help-icon" alt="" />
            </div>
            <div className="help-text text-start text-lg-center">
              <h6 className="mb-1 fw-semibold fs-6">Call us for Queries</h6>
              <p className="mb-0 text-muted">
                Helpline no: <strong>+91-9818532463</strong>
                <br />
                (Mon - Sat: 10:30 AM - 7:00 PM)
              </p>
            </div>
          </div>
        </div>

        <div className="col-12 col-lg-4 px-0 px-lg-2">
          <div className="d-flex flex-row flex-lg-column justify-content-start help-bd  align-items-center py-3 px-2 px-lg-4 gap-2">
            <div className=" me-lg-3 fs-3 bg-secondary-subtle p-3 rounded-circle d-flex align-items-center justify-content-center">
              <img src="/email.png" className="help-icon" alt="" />
            </div>
            <div className="help-text text-start text-lg-center">
              <h6 className="mb-1 fw-semibold ">E-Mail us</h6>
              <p className="mb-0 text-muted">
                Sales enquiries and customer support:{" "} <br />
                <strong>support@printmont.com</strong>
              </p>
            </div>
          </div>
        </div>

        <div className="col-12 col-lg-4 px-0 px-lg-2">
          <div className="d-flex flex-row flex-lg-column justify-content-start help-bd  align-items-center py-3 px-2 px-lg-4 gap-2">
            <div className=" me-lg-3 fs-3 bg-secondary-subtle p-3 rounded-circle d-flex align-items-center justify-content-center">
              <img src="/store.png" className="help-icon" alt="" />
            </div>
            <div className="help-text text-start text-lg-center">
              <h6 className="mb-1 fw-semibold">Postal Address</h6>
              <p className="mb-0 text-muted">
                Printmont Corporation <br />
                3398, Bagichi Acchi Ji, Bara Hindu Rao,
                <br />
                Near Filmistan Cinema, Delhi, India - 110006
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* --- FAQ SECTION --- */}
      <div className="container py-4">
        <h4 className="fw-semibold mb-4">Frequently Asked Questions</h4>

        <div className="row px-0 mx-0">
          {/* Sidebar */}
          <div className="col-lg-3 mb-3 mb-lg-0">
            {/* Mobile toggle button */}
            <div className="d-lg-none mb-2">
              <Button
                variant="light"
                className="w-100 border d-flex justify-content-between align-items-center border border-2"
                onClick={() => setOpen(!open)}
                aria-controls="faq-collapse"
                aria-expanded={open}
              >
                <span>{activeCategory}</span>
                <span className={`fs-5 ${open ? "rotate-up" : "rotate-down"}`}><MdKeyboardArrowDown />
                </span>
              </Button>

              <Collapse in={open} >
                <div id="faq-collapse">
                  <ListGroup className="mt-2 shadow-sm">
                    {categories.map((cat) => (
                      <ListGroup.Item
                        key={cat}
                        action
                        onClick={() => {
                          setActiveCategory(cat);
                          setOpen(false);
                        }}
                        active={activeCategory === cat}
                        className="faq-category-item"
                      >
                        {cat}
                      </ListGroup.Item>
                    ))}
                  </ListGroup>
                </div>
              </Collapse>
            </div>

            {/* Desktop Sidebar */}
            <div className="d-none d-lg-block">
              <ListGroup>
                {categories.map((cat) => (
                  <ListGroup.Item
                    key={cat}
                    action
                    onClick={() => setActiveCategory(cat)}
                    active={activeCategory === cat}
                    className="faq-category-item"
                  >
                    {cat}
                  </ListGroup.Item>
                ))}
              </ListGroup>
            </div>
          </div>

          {/* FAQ content */}
          <div className="col-lg-9">
            <h5 className="fw-semibold mb-3">{activeCategory}</h5>
            <Accordion alwaysOpen>
              {faqs.map((question, index) => (
                <Accordion.Item className="border-2" eventKey={index.toString()} key={index}>
                  <Accordion.Header className="">{question}</Accordion.Header>
                  <Accordion.Body>
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                    Integer posuere erat a ante.
                  </Accordion.Body>
                </Accordion.Item>
              ))}
            </Accordion>
          </div>
        </div>
      </div>
    </div>
    </>
  );
};

export default HelpCenter;
