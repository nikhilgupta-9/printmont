import React, { useState, useEffect } from "react";
import { Accordion, ListGroup, Button, Collapse } from "react-bootstrap";
import "bootstrap/dist/css/bootstrap.min.css";
import './help.css';
import { MdKeyboardArrowDown } from "react-icons/md";
import { API_ENDPOINTS } from "../../config/apiEndpoints";

const HelpCenter = () => {
  const [categories, setCategories] = useState([]);
  const [activeCategory, setActiveCategory] = useState(null);
  const [loading, setLoading] = useState(true);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const fetchHelpCenterData = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.HELP_CENTER);
        if (!response.ok) throw new Error("Network response was not ok");
        const json = await response.json();
        if (json && json.success && json.data && Array.isArray(json.data.categories)) {
          // Include all active categories
          const activeCategories = json.data.categories.filter(
            (cat) => cat.is_active !== false && cat.is_active !== 0
          );
          setCategories(activeCategories);
          if (activeCategories.length > 0) {
            setActiveCategory(activeCategories[0]);
          }
        }
      } catch (error) {
        console.error("Error fetching help center API content:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchHelpCenterData();
  }, []);

  return (
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

        {loading ? (
          <p className="text-muted text-center py-4">Loading help categories...</p>
        ) : categories.length === 0 ? (
          <p className="text-muted text-center py-4">No categories available at the moment.</p>
        ) : (
          <div className="row px-0 mx-0">
            {/* Sidebar */}
            <div className="col-lg-3 mb-3 mb-lg-0">
              {/* Mobile toggle button */}
              <div className="d-lg-none mb-2">
                <Button
                  variant="light"
                  className="w-100 border d-flex justify-content-between align-items-center border-2"
                  onClick={() => setOpen(!open)}
                  aria-controls="faq-collapse"
                  aria-expanded={open}
                >
                  <span>{activeCategory?.name || "Select Category"}</span>
                  <span className={`fs-5 ${open ? "rotate-up" : "rotate-down"}`}><MdKeyboardArrowDown /></span>
                </Button>

                <Collapse in={open}>
                  <div id="faq-collapse">
                    <ListGroup className="mt-2 shadow-sm">
                      {categories.map((cat) => (
                        <ListGroup.Item
                          key={cat.id}
                          action
                          onClick={() => {
                            setActiveCategory(cat);
                            setOpen(false);
                          }}
                          active={activeCategory?.id === cat.id}
                          className="faq-category-item"
                        >
                          {cat.name}
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
                      key={cat.id}
                      action
                      onClick={() => setActiveCategory(cat)}
                      active={activeCategory?.id === cat.id}
                      className="faq-category-item"
                    >
                      {cat.name}
                    </ListGroup.Item>
                  ))}
                </ListGroup>
              </div>
            </div>

            {/* FAQ content */}
            <div className="col-lg-9">
              <h5 className="fw-semibold mb-3">{activeCategory?.name}</h5>
              {activeCategory?.faqs && activeCategory.faqs.length > 0 ? (
                <Accordion alwaysOpen>
                  {activeCategory.faqs.map((faq, index) => (
                    <Accordion.Item className="border-2" eventKey={index.toString()} key={faq.id}>
                      <Accordion.Header>{faq.question}</Accordion.Header>
                      <Accordion.Body>{faq.answer}</Accordion.Body>
                    </Accordion.Item>
                  ))}
                </Accordion>
              ) : (
                <p className="text-muted">No questions found in this category.</p>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default HelpCenter;
