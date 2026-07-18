import React from "react";
import { Container, Row, Col, Card, Accordion } from "react-bootstrap";
import { Link } from "react-router-dom";

const FranchisePage = () => {
  const franchiseBenefits = [
    {
      title: "Established Brand & Market Demand",
      desc: "Tap into PrintMont’s trusted reputation in custom apparel, personalized gifting, and corporate supply with thousands of active repeat customers."
    },
    {
      title: "High Gross Margin Potential",
      desc: "Enjoy direct factory pricing on raw blanks, printing supplies, and packaging with retail profit margins ranging from 45% to 65%."
    },
    {
      title: "Centralized Supply & Manufacturing",
      desc: "No heavy inventory risk. Access our centralized manufacturing hubs for complex bulk orders while fulfilling instant retail gifts on-site."
    },
    {
      title: "Complete Training & Operations Support",
      desc: "We provide comprehensive training on POS software, printing machinery operation, customer service guidelines, and store management."
    },
    {
      title: "Digital & Marketing Assistance",
      desc: "Benefit from nationwide digital ad campaigns, hyper-local marketing collaterals, and customer lead generation routed directly to your franchise."
    },
    {
      title: "Protected Territory Rights",
      desc: "Exclusive geographical territory rights ensuring zero cannibalization and dedicated customer demand within your assigned district or zone."
    }
  ];

  const franchiseModels = [
    {
      name: "PrintMont Express Kiosk",
      space: "150 – 300 Sq. Ft.",
      investment: "₹5 Lakhs – ₹8 Lakhs",
      bestFor: "Malls, High-Street Retail Outlets, College Campuses",
      features: "Instant mug printing, custom t-shirts, phone covers, and personalized photo gifts for walk-in retail customers."
    },
    {
      name: "PrintMont Experience Center",
      space: "400 – 800 Sq. Ft.",
      investment: "₹10 Lakhs – ₹16 Lakhs",
      bestFor: "Commercial Commercial Hubs, Retail High Streets",
      features: "Full retail store experience featuring live DTG printing, corporate sample display lounge, activewear studio, and bulk inquiry office."
    },
    {
      name: "Master Regional Partner",
      space: "1,000+ Sq. Ft.",
      investment: "₹25 Lakhs – ₹40 Lakhs",
      bestFor: "Tier-1 & Tier-2 City District Masters",
      features: "Regional distribution hub, centralized production setup, sub-franchise onboarding rights, and territorial B2B corporate fulfillment."
    }
  ];

  const applicationSteps = [
    {
      step: "01",
      title: "Expression of Interest",
      desc: "Fill out the online franchise inquiry form with your contact details, proposed location, and preferred investment model."
    },
    {
      step: "02",
      title: "Discovery Call & Screening",
      desc: "Our franchise expansion team conducts an initial telephone screening to evaluate eligibility and align business objectives."
    },
    {
      step: "03",
      title: "Site Approval & Feasibility",
      desc: "Our real estate team conducts footfall analysis and site feasibility evaluation to approve your selected retail store location."
    },
    {
      step: "04",
      title: "Agreement & Onboarding",
      desc: "Signing of legal franchise agreement, payment of franchise fee, and site layout design allocation."
    },
    {
      step: "05",
      title: "Setup, Training & Grand Opening",
      desc: "Store fit-outs, equipment installation, staff training, POS software setup, and initial promotional grand opening launch."
    }
  ];

  const faqs = [
    {
      q: "What is the prior business experience required to open a PrintMont franchise?",
      a: "Prior experience in retail or printing is beneficial but not mandatory. We provide complete operational, technical, and sales training for you and your staff."
    },
    {
      q: "What is the expected Return on Investment (ROI) period?",
      a: "Depending on store location, footfall, and operational execution, our franchise partners typically achieve complete ROI payback within 12 to 18 months."
    },
    {
      q: "Does PrintMont provide the printing machinery and software?",
      a: "Yes! PrintMont supplies all certified commercial printing machinery, heat presses, embroidery setups, POS billing software, and design template portals."
    },
    {
      q: "Are royalty fees charged on monthly store sales?",
      a: "PrintMont operates a transparent franchise model with a fixed flat quarterly brand support fee rather than high percentage sales royalties."
    },
    {
      q: "Can a franchise accept bulk corporate orders directly?",
      a: "Absolutely! Franchise partners are encouraged to build corporate relations in their local area. Large bulk orders can be routed to our central factory while you retain profit commission."
    }
  ];

  return (
    <div className="bg-light py-4 py-md-5">
      <Container>
        {/* HEADER BREADCRUMB & TITLE */}
        <div className="text-center mb-4 mb-md-5">
          <p className="text-primary fw-bold text-uppercase small mb-2">Franchise & Partner Expansion</p>
          <h1 className="fw-bold text-dark display-5 mb-3">Own a PrintMont Franchise</h1>
          <p className="text-secondary mx-auto fs-6" style={{ maxWidth: "750px", lineHeight: "1.7" }}>
            Join India’s fastest growing custom printing, personalized gifting, and corporate merchandise retail network. Partner with PrintMont and build a high-margin scalable business.
          </p>
        </div>

        {/* OVERVIEW SECTION */}
        <Card className="border-0 shadow-sm p-4 p-md-5 mb-4 mb-md-5 rounded-3 bg-white">
          <h3 className="fw-bold text-dark mb-3">The PrintMont Partner Advantage</h3>
          <p className="text-secondary lh-lg mb-3">
            The Indian custom merchandise and personalized gifting market is projected to cross ₹25,000 Crores by 2030, driven by corporate branding needs, event merchandise, D2C fashion brands, and personal gifting occasions.
          </p>
          <p className="text-secondary lh-lg mb-0">
            PrintMont bridges the gap between digital e-commerce ease and hyper-local physical fulfillment. As a franchise owner, you benefit from our centralized supply chain, cutting-edge print tech, national brand marketing, and proven retail store playbook.
          </p>
        </Card>

        {/* FRANCHISE MODELS */}
        <div className="mb-4 mb-md-5">
          <h3 className="fw-bold text-dark mb-4 text-center text-md-start">Explore Franchise Ownership Models</h3>
          <Row className="g-3 g-md-4">
            {franchiseModels.map((model, idx) => (
              <Col md={4} key={idx}>
                <Card className="h-100 border-0 shadow-sm p-4 rounded-3 bg-white d-flex flex-column justify-content-between">
                  <div>
                    <span className="badge bg-primary text-white fw-bold small mb-2">Model 0{idx + 1}</span>
                    <h4 className="fw-bold text-dark mb-3 fs-5">{model.name}</h4>
                    <ul className="list-unstyled text-secondary small mb-4 lh-lg">
                      <li className="mb-1"><strong>Required Space:</strong> {model.space}</li>
                      <li className="mb-1"><strong>Estimated Investment:</strong> {model.investment}</li>
                      <li className="mb-1"><strong>Ideal Locations:</strong> {model.bestFor}</li>
                    </ul>
                    <p className="text-secondary small border-top pt-3 m-0" style={{ lineHeight: "1.6" }}>
                      {model.features}
                    </p>
                  </div>
                </Card>
              </Col>
            ))}
          </Row>
        </div>

        {/* WHY PARTNER WITH PRINTMONT */}
        <div className="mb-4 mb-md-5">
          <h3 className="fw-bold text-dark mb-4 text-center text-md-start">Key Franchise Support & Benefits</h3>
          <Row className="g-3 g-md-4">
            {franchiseBenefits.map((item, idx) => (
              <Col md={6} lg={4} key={idx}>
                <Card className="h-100 border-0 shadow-sm p-4 rounded-3 bg-white">
                  <h5 className="fw-bold text-primary mb-2 fs-6">{item.title}</h5>
                  <p className="text-secondary small m-0" style={{ lineHeight: "1.6" }}>
                    {item.desc}
                  </p>
                </Card>
              </Col>
            ))}
          </Row>
        </div>

        {/* APPLICATION PROCESS */}
        <Card className="border-0 shadow-sm p-4 p-md-5 mb-4 mb-md-5 rounded-3 bg-white">
          <h3 className="fw-bold text-dark mb-4 text-center text-md-start">5-Step Franchise Application Roadmap</h3>
          <Row className="g-3 g-md-4">
            {applicationSteps.map((s, idx) => (
              <Col xs={12} sm={6} lg={2} key={idx} className="flex-grow-1">
                <div className="p-3 border rounded-3 bg-light h-100">
                  <span className="badge bg-primary text-white fw-bold small mb-2">Step {s.step}</span>
                  <h6 className="fw-bold text-dark mb-2 fs-6">{s.title}</h6>
                  <p className="text-secondary" style={{ fontSize: "0.82rem", lineHeight: "1.5" }}>
                    {s.desc}
                  </p>
                </div>
              </Col>
            ))}
          </Row>
        </Card>

        {/* FREQUENTLY ASKED QUESTIONS */}
        <Card className="border-0 shadow-sm p-4 p-md-5 mb-4 mb-md-5 rounded-3 bg-white">
          <h3 className="fw-bold text-dark mb-4">Franchise Ownership FAQs</h3>
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

        {/* FRANCHISE CONTACT TEXT BOX */}
        <Card className="border-0 shadow-sm p-4 p-md-5 rounded-4 text-white text-center" style={{ background: "linear-gradient(135deg, #0b53a1 0%, #002b66 100%)" }}>
          <h3 className="fw-bold text-white mb-3 fs-3">Start Your Entrepreneurial Journey Today</h3>
          <p className="text-white opacity-90 mx-auto mb-4 fs-6" style={{ maxWidth: "680px", lineHeight: "1.7", color: "rgba(255, 255, 255, 0.95)" }}>
            Speak directly with our Franchise Expansion Directors to receive a detailed franchise prospectus kit and location feasibility review.
          </p>
          <div className="d-flex flex-column flex-sm-row justify-content-center align-items-center gap-3">
            <Link to="/contact" className="btn btn-light text-primary fw-bold px-4 py-2.5 rounded-pill shadow-sm">
              Apply For Franchise
            </Link>
            <div className="text-white small fw-semibold">
              Email: <strong className="text-white text-decoration-underline">franchise@printmont.com</strong> | Phone: <strong className="text-white">1800-123-4567</strong>
            </div>
          </div>
        </Card>
      </Container>
    </div>
  );
};

export default FranchisePage;
