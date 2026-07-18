import React from "react";
import { Container, Row, Col, Card, Accordion } from "react-bootstrap";
import { Link } from "react-router-dom";

const BulkOrderPage = () => {
  const bulkBenefits = [
    {
      title: "Tiered Wholesale Pricing",
      desc: "Enjoy steep volume discounts starting at just 25 units. The larger your order quantity, the lower your unit cost."
    },
    {
      title: "Multiple Custom Printing Technologies",
      desc: "Choose from Direct-to-Garment (DTG), High-Density Screen Printing, Sublimation, Laser Engraving, and Premium Foil Debossing."
    },
    {
      title: "Dedicated Account Management",
      desc: "Every bulk order customer is paired with a dedicated corporate account manager to oversee artwork approval, sampling, and delivery timelines."
    },
    {
      title: "Nationwide & Multi-Location Shipping",
      desc: "We ship directly to single locations or handle split-destination drop-shipping across 10,000+ pin codes in India."
    },
    {
      title: "GST Invoicing & PO Billing",
      desc: "Full GST tax invoice compliance for seamless corporate accounting and input tax credit claims. PO-based credit terms available for verified corporate clients."
    },
    {
      title: "Sample Approval Guarantee",
      desc: "Receive pre-production digital proofs or physical sample prototypes before full production run commitment."
    }
  ];

  const orderSteps = [
    {
      step: "Step 01",
      title: "Submit Inquiry",
      desc: "Provide details about your required product, estimated quantities, preferred customization, and target delivery date."
    },
    {
      step: "Step 02",
      title: "Get Custom Quote",
      desc: "Our bulk team evaluates your requirements and sends a formal quotation with transparent tiered pricing within 4 hours."
    },
    {
      step: "Step 03",
      title: "Approve Digital Mockup",
      desc: "Review and approve high-resolution 3D digital artwork proofs. Physical sample dispatch is available for large orders."
    },
    {
      step: "Step 04",
      title: "Production & Quality Check",
      desc: "Your order undergoes precision printing and 100% quality inspection for color accuracy, print durability, and finishing."
    },
    {
      step: "Step 05",
      title: "Dispatch & Live Tracking",
      desc: "Items are packed securely and dispatched via premier logistics partners with real-time tracking until safe delivery."
    }
  ];

  const faqs = [
    {
      q: "What is the Minimum Order Quantity (MOQ) for bulk orders?",
      a: "Our standard bulk order pricing starts at 25 units for custom apparel, drinkware, and accessories. For corporate diaries and pen sets, MOQ starts at 50 units."
    },
    {
      q: "What artwork file formats should I provide for custom printing?",
      a: "For optimal print results, please share artwork in vector format (.AI, .EPS, .SVG, .PDF) or high-resolution transparent PNG files with a minimum of 300 DPI."
    },
    {
      q: "Can I request physical samples before approving the entire batch?",
      a: "Yes! We provide digital mockups for all orders. For bulk orders over 100 units, physical pre-production samples can be produced and dispatched for physical review."
    },
    {
      q: "How long does bulk production and delivery take?",
      a: "Standard bulk order processing and production takes 4 to 7 business days, depending on order complexity and quantity. Transit time takes 2 to 4 business days."
    },
    {
      q: "Do you offer drop-shipping to individual employee home addresses?",
      a: "Yes! We specialize in employee onboarding and remote team gifting. Provide us your employee address spreadsheet, and we will package and deliver individual kits directly to their doorstep."
    }
  ];

  return (
    <div className="bg-light py-4 py-md-5">
      <Container>
        {/* HEADER BREADCRUMB & TITLE */}
        <div className="text-center mb-4 mb-md-5">
          <p className="text-primary fw-bold text-uppercase small mb-2">Corporate & Enterprise Supply</p>
          <h1 className="fw-bold text-dark display-5 mb-3">Bulk Orders & Wholesale Printing</h1>
          <p className="text-secondary mx-auto fs-6" style={{ maxWidth: "750px", lineHeight: "1.7" }}>
            PrintMont provides end-to-end custom printing, corporate merchandise, and bulk promotional product solutions for companies, events, startups, and institutions across India.
          </p>
        </div>

        {/* OVERVIEW SECTION */}
        <Card className="border-0 shadow-sm p-4 p-md-5 mb-4 mb-md-5 rounded-3 bg-white">
          <h3 className="fw-bold text-dark mb-3">Comprehensive Bulk Merchandise Solutions</h3>
          <p className="text-secondary lh-lg mb-3">
            Whether you are planning an annual corporate summit, onboarding hundreds of new employees, stocking promotional merchandise for a trade fair, or sourcing customized uniforms for your workforce, PrintMont offers scalable manufacturing and printing infrastructure tailored to your timeline and budget.
          </p>
          <p className="text-secondary lh-lg mb-0">
            Our state-of-the-art facilities combine modern Direct-to-Garment (DTG), automatic screen printing carousels, laser engraving, and UV printing capabilities to deliver consistent quality across orders ranging from 25 to 50,000+ units.
          </p>
        </Card>

        {/* WHY CHOOSE PRINTMONT FOR BULK */}
        <div className="mb-4 mb-md-5">
          <h3 className="fw-bold text-dark mb-4 text-center text-md-start">Why Partner with PrintMont for Bulk Supply</h3>
          <Row className="g-3 g-md-4">
            {bulkBenefits.map((item, idx) => (
              <Col md={6} lg={4} key={idx}>
                <Card className="h-100 border-0 shadow-sm p-4 rounded-3 bg-white">
                  <div className="d-flex align-items-center mb-3">
                    <span className="badge bg-primary-subtle text-primary fw-bold fs-6 rounded-circle p-2 me-3" style={{ width: "36px", height: "36px", display: "inline-flex", justifyContent: "center", alignItems: "center" }}>
                      {idx + 1}
                    </span>
                    <h5 className="fw-bold text-dark m-0 fs-6">{item.title}</h5>
                  </div>
                  <p className="text-secondary small m-0" style={{ lineHeight: "1.6" }}>
                    {item.desc}
                  </p>
                </Card>
              </Col>
            ))}
          </Row>
        </div>

        {/* HOW TO PLACE A BULK ORDER (STEP BY STEP TEXT) */}
        <Card className="border-0 shadow-sm p-4 p-md-5 mb-4 mb-md-5 rounded-3 bg-white">
          <h3 className="fw-bold text-dark mb-4 text-center text-md-start">How Our Bulk Ordering Process Works</h3>
          <Row className="g-3 g-md-4">
            {orderSteps.map((s, idx) => (
              <Col xs={12} sm={6} lg={2} key={idx} className="flex-grow-1">
                <div className="p-3 border rounded-3 bg-light h-100">
                  <span className="badge bg-primary text-white fw-bold small mb-2">{s.step}</span>
                  <h6 className="fw-bold text-dark mb-2 fs-6">{s.title}</h6>
                  <p className="text-secondary" style={{ fontSize: "0.82rem", lineHeight: "1.5" }}>
                    {s.desc}
                  </p>
                </div>
              </Col>
            ))}
          </Row>
        </Card>

        {/* BULK PRODUCT CATEGORIES TEXT */}
        <Card className="border-0 shadow-sm p-4 p-md-5 mb-4 mb-md-5 rounded-3 bg-white">
          <h3 className="fw-bold text-dark mb-4">Bulk Product Categories We Supply</h3>
          <Row className="g-4">
            <Col md={6}>
              <h5 className="fw-bold text-primary mb-2">1. Custom Corporate Apparel</h5>
              <p className="text-secondary small mb-3 lh-base">
                Combed cotton round neck t-shirts, dry-fit polo t-shirts, fleece hoodies, windcheater jackets, chef coats, and custom team uniforms with chest & back logo placement.
              </p>

              <h5 className="fw-bold text-primary mb-2">2. Custom Drinkware & Bottles</h5>
              <p className="text-secondary small mb-0 lh-base">
                Glossy & matte ceramic mugs, stainless steel temperature-display vacuum flasks, travel tumblers, and aluminum sports bottles with laser engraved logos.
              </p>
            </Col>

            <Col md={6}>
              <h5 className="fw-bold text-primary mb-2">3. Executive Stationeries & Kits</h5>
              <p className="text-secondary small mb-3 lh-base">
                Hardbound A5 leatherette diaries, dated planners, metal rollerball pens, desk organizers, cardholders, and multi-piece onboarding gift hampers.
              </p>

              <h5 className="fw-bold text-primary mb-2">4. Tech Gadgets & Giveaways</h5>
              <p className="text-secondary small mb-0 lh-base">
                Power banks (10,000mAh & 20,000mAh), Bluetooth speakers, USB flash drives, wireless charging pads, keychains, and tote bags for corporate events.
              </p>
            </Col>
          </Row>
        </Card>

        {/* FREQUENTLY ASKED QUESTIONS */}
        <Card className="border-0 shadow-sm p-4 p-md-5 mb-4 mb-md-5 rounded-3 bg-white">
          <h3 className="fw-bold text-dark mb-4">Bulk Ordering FAQs</h3>
          <Accordion defaultActiveKey="0" className="border-0">
            {faqs.map((faq, idx) => (
              <Accordion.Item eventKey={String(idx)} key={idx} className="border-0 mb-3 rounded overflow-hidden shadow-xs">
                <Accordion.Header className="fw-semibold text-dark">{faq.q}</Accordion.Header>
                <Accordion.Body className="text-secondary small lh-lg bg-light">
                  {faq.a}
                </Accordion.Body>
              </Accordion.Item>
            ))}
          </Accordion>
        </Card>

        {/* BULK CONTACT TEXT BOX */}
        <Card className="border-0 shadow-sm p-4 p-md-5 rounded-4 text-white text-center" style={{ background: "linear-gradient(135deg, #0b53a1 0%, #002b66 100%)" }}>
          <h3 className="fw-bold text-white mb-3 fs-3">Ready to Discuss Your Bulk Requirement?</h3>
          <p className="text-white opacity-90 mx-auto mb-4 fs-6" style={{ maxWidth: "680px", lineHeight: "1.7", color: "rgba(255, 255, 255, 0.95)" }}>
            Our corporate bulk experts are standing by to assist with instant price quotes, digital mockups, and technical guidance.
          </p>
          <div className="d-flex flex-column flex-sm-row justify-content-center align-items-center gap-3">
            <Link to="/contact" className="btn btn-light text-primary fw-bold px-4 py-2.5 rounded-pill shadow-sm">
              Contact Bulk Support
            </Link>
            <div className="text-white small fw-semibold">
              Email: <strong className="text-white text-decoration-underline">bulk@printmont.com</strong> | Call: <strong className="text-white">1800-123-4567</strong>
            </div>
          </div>
        </Card>
      </Container>
    </div>
  );
};

export default BulkOrderPage;
