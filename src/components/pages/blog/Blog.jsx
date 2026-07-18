import React, { useState } from "react";
import { Row, Col, Container, Card, Badge, Button, Form } from "react-bootstrap";
import BlogCard from "./BlogCard";
import { Link } from "react-router-dom";
import { FaFire, FaRegClock, FaNewspaper, FaArrowRight, FaCheckCircle, FaTag, FaMobileAlt, FaBuilding, FaPercentage } from "react-icons/fa";

// --- BLOG ARTICLES DATA ---
const blogData = [
  {
    id: 1,
    slug: "10-creative-classroom-activities-for-childrens-day",
    category: "Corporate & School",
    title: "10 Creative Classroom Activities & Custom Gifts for Children's Day",
    imageSrc: "/blog/blog-img-2.png",
    author: "Priya Lamba",
    date: "Nov 05, 2025",
    readTime: "5 min read",
    tags: "School Events",
    summary:
      "Discover creative classroom activity ideas and customized giveaway gifts to celebrate innocence and foster teamwork in learning spaces."
  },
  {
    id: 2,
    slug: "corporate-gifting-trends-for-modern-workplaces",
    category: "Corporate Gifts",
    title: "Top Corporate Gifting Trends for Modern Workplaces & Employee Kits",
    imageSrc: "/corporate_apparel.png",
    author: "Rahul Sharma",
    date: "Oct 28, 2025",
    readTime: "6 min read",
    tags: "Workplace",
    summary:
      "Explore eco-friendly onboarding hampers, custom drinkware, and executive stationeries that elevate brand identity and remote team culture."
  },
  {
    id: 3,
    slug: "how-to-choose-the-best-custom-apparel-printing",
    category: "Printing Tech",
    title: "DTG vs Screen Printing: Which Garment Printing Tech is Best?",
    imageSrc: "/men_shirt/men-shirt-1.png",
    author: "Amit Roy",
    date: "Oct 18, 2025",
    readTime: "4 min read",
    tags: "Apparel Guide",
    summary:
      "A comprehensive comparison between Direct-to-Garment (DTG) and high-density screen printing for custom t-shirts, hoodies, and polo uniforms."
  },
  {
    id: 4,
    slug: "promotional-merchandise-strategies-for-startups",
    category: "Business Growth",
    title: "How Startups Can Build Brand Loyalty with Custom Promotional Products",
    imageSrc: "/startup_hoodies.png",
    author: "Neha Kapoor",
    date: "Oct 10, 2025",
    readTime: "7 min read",
    tags: "Marketing",
    summary:
      "High-impact promotional merchandise ideas for trade shows, investor summits, and customer giveaways that deliver long-term brand recall."
  }
];

// --- TRENDING TOPICS DATA ---
const sidebarLinks = [
  { title: "Unique Children's Day Gifts for School", url: "/blog/10-creative-classroom-activities-for-childrens-day", img: "/blog/blog-img-1.png", date: "Nov 05" },
  { title: "How to Plan a Corporate Gifting Strategy", url: "/blog/corporate-gifting-trends-for-modern-workplaces", img: "/corporate_apparel.png", date: "Oct 28" },
  { title: "DTG vs Screen Printing Comparison", url: "/blog/how-to-choose-the-best-custom-apparel-printing", img: "/men_shirt/men-shirt-1.png", date: "Oct 18" },
  { title: "Startup Merchandise That Drives Sales", url: "/blog/promotional-merchandise-strategies-for-startups", img: "/startup_hoodies.png", date: "Oct 10" },
];

// --- SIDEBAR COMPONENT ---
const Sidebar = () => {
  const [subscribed, setSubscribed] = useState(false);

  return (
    <div className="h-100">
      <div className="sticky-top" style={{ top: "90px", zIndex: 10 }}>
        {/* SIDEBAR AD BANNER 1: PROMO COUPON */}
        <Card className="border-0 shadow-sm rounded-4 p-3 mb-4 text-white overflow-hidden position-relative" style={{ background: "linear-gradient(135deg, #11998e 0%, #38ef7d 100%)" }}>
          <div className="d-flex align-items-center gap-2 mb-2">
            <FaTag size={18} />
            <span className="badge bg-white text-dark fw-bold text-uppercase">Exclusive Offer</span>
          </div>
          <h5 className="fw-bold mb-1 text-white fs-6">Save 15% on First Bulk Order</h5>
          <p className="small text-white-50 mb-3" style={{ fontSize: "0.8rem" }}>Use code <strong>PRINT2026</strong> at checkout.</p>
          <Link to="/bulk-orders" className="btn btn-light text-dark btn-sm rounded-pill fw-bold">
            Claim Offer
          </Link>
        </Card>

        {/* TRENDING TOPICS */}
        <Card className="border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
          <div className="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
            <FaFire className="text-danger" size={18} />
            <h6 className="fw-bold text-dark m-0">Trending Articles</h6>
          </div>
          <div className="d-flex flex-column gap-3">
            {sidebarLinks.map((link, index) => (
              <Link key={index} to={link.url} className="text-decoration-none text-dark d-flex align-items-center gap-3">
                <div className="flex-shrink-0 rounded-3 overflow-hidden border" style={{ width: "60px", height: "55px" }}>
                  <img src={link.img} alt={link.title} className="w-100 h-100" style={{ objectFit: "cover" }} />
                </div>
                <div className="flex-grow-1 min-w-0">
                  <h6 className="small fw-bold text-dark mb-1 line-clamp-2" style={{ lineHeight: "1.4" }}>
                    {link.title}
                  </h6>
                  <span className="text-muted" style={{ fontSize: "0.72rem" }}>
                    <FaRegClock size={11} className="me-1" /> {link.date}
                  </span>
                </div>
              </Link>
            ))}
          </div>
        </Card>

        {/* SIDEBAR AD BANNER 2: APP DOWNLOAD */}
        <Card className="border-0 shadow-sm rounded-4 p-4 text-white text-center mb-4" style={{ background: "linear-gradient(135deg, #0b53a1 0%, #002b66 100%)" }}>
          <FaMobileAlt size={36} className="mx-auto mb-2 text-warning" />
          <h5 className="fw-bold text-white mb-2 fs-6">Download PrintMont App</h5>
          <p className="small text-white-50 mb-3" style={{ fontSize: "0.82rem", lineHeight: "1.5" }}>
            Get ₹500 Welcome PrintCoins & track your orders live on your phone.
          </p>
          <Link to="/app-download" className="btn btn-warning text-dark btn-sm rounded-pill fw-bold w-100">
            Download App
          </Link>
        </Card>

        {/* NEWSLETTER SUBSCRIBE CARD */}
        <Card className="border-0 shadow-sm rounded-4 p-4 text-white text-center mb-4 bg-dark">
          <FaNewspaper size={28} className="mx-auto mb-2 text-white-50" />
          <h5 className="fw-bold text-white mb-2 fs-6">PrintMont Insights</h5>
          <p className="small text-white-50 mb-3" style={{ fontSize: "0.82rem", lineHeight: "1.5" }}>
            Subscribe for free weekly printing tips & merchandise guides.
          </p>
          {subscribed ? (
            <div className="p-2 rounded bg-success text-white small fw-bold d-flex align-items-center justify-content-center gap-1">
              <FaCheckCircle /> Subscribed!
            </div>
          ) : (
            <Form onSubmit={(e) => { e.preventDefault(); setSubscribed(true); }}>
              <Form.Control type="email" placeholder="Your email address" required className="form-control-sm mb-2 rounded-pill text-center border-0" />
              <Button type="submit" variant="primary" size="sm" className="w-100 rounded-pill fw-bold">
                Subscribe Free
              </Button>
            </Form>
          )}
        </Card>
      </div>
    </div>
  );
};

// --- MAIN BLOG PAGE ---
const Blog = () => {
  const [selectedCategory, setSelectedCategory] = useState("All");

  const categories = ["All", "Corporate Gifts", "Printing Tech", "Business Growth", "School Events"];

  const filteredBlogs = selectedCategory === "All" 
    ? blogData 
    : blogData.filter(b => b.category === selectedCategory);

  return (
    <div className="bg-light py-3 py-md-5" style={{ minHeight: "85vh", overflowX: "hidden" }}>
      <Container>
        {/* BLOG HERO & SEARCH */}
        <div className="text-center mb-4 mb-md-5">
          <Badge bg="primary-subtle" className="text-primary fw-bold px-3 py-2 mb-2 rounded-pill text-uppercase fs-7">
            PrintMont Knowledge Hub
          </Badge>
          <h1 className="fw-bold text-dark display-6 display-md-5 mb-2">PrintMont Blog & Guides</h1>
          <p className="text-secondary mx-auto fs-6" style={{ maxWidth: "680px", lineHeight: "1.6" }}>
            Expert insights on custom apparel printing, corporate merchandise strategies, design trends, and personalized gifting ideas.
          </p>

          {/* CATEGORY FILTER PILLS */}
          <div className="d-flex flex-wrap justify-content-center gap-2 mt-4">
            {categories.map((cat) => (
              <Button
                key={cat}
                variant={selectedCategory === cat ? "primary" : "outline-secondary"}
                size="sm"
                className={`rounded-pill px-3 py-1.5 fw-semibold ${selectedCategory === cat ? "shadow-xs" : "bg-white"}`}
                onClick={() => setSelectedCategory(cat)}
              >
                {cat}
              </Button>
            ))}
          </div>
        </div>

        {/* FEATURED SPOTLIGHT ARTICLE */}
        <Card className="border-0 shadow-sm rounded-4 overflow-hidden mb-4 mb-md-5 bg-white">
          <Row className="g-0 align-items-center">
            <Col lg={7} md={12} className="p-0">
              <div style={{ maxHeight: "360px", overflow: "hidden" }}>
                <img
                  src="/b2b_hero.png"
                  alt="Featured Article"
                  className="w-100 h-100 img-fluid zoom-hover"
                  style={{ objectFit: "cover", minHeight: "280px", maxHeight: "360px", width: "100%" }}
                />
              </div>
            </Col>
            <Col lg={5} md={12} className="p-4 p-md-5">
              <Badge bg="primary-subtle" className="text-primary fw-bold px-3 py-1 mb-2 rounded-pill text-uppercase fs-7">
                Featured Spotlight
              </Badge>
              <h3 className="fw-bold text-dark mb-3 fs-4 fs-md-3" style={{ lineHeight: "1.3" }}>
                The Ultimate 2026 Guide to Corporate Gifting & Brand Merchandise
              </h3>
              <p className="text-secondary small mb-4" style={{ lineHeight: "1.6" }}>
                Learn how top enterprises build lasting business relationships and remote employee culture through high-quality custom apparel, laser-engraved drinkware, and tech hampers.
              </p>
              <div className="d-flex align-items-center justify-content-between pt-3 border-top">
                <span className="text-muted small fw-semibold">8 min read · Oct 2026</span>
                <Link to="/blog/10-creative-classroom-activities-for-childrens-day" className="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-xs">
                  Read Article <FaArrowRight className="ms-1" />
                </Link>
              </div>
            </Col>
          </Row>
        </Card>

        {/* MAIN CONTENT GRID */}
        <Row className="g-4">
          <Col xs={12} lg={9}>
            {/* FEATURED ARTICLES GRID */}
            <div className="mb-4">
              <div className="d-flex justify-content-between align-items-center mb-3">
                <h4 className="fw-bold text-dark m-0 fs-5">Latest Articles</h4>
                <span className="text-muted small">{filteredBlogs.length} articles found</span>
              </div>
              <Row className="g-3 g-md-4">
                {filteredBlogs.map((data) => (
                  <Col key={data.id} xs={12} sm={6}>
                    <BlogCard cardData={data} />
                  </Col>
                ))}
              </Row>
            </div>

            {/* IN-FEED NATIVE AD BANNER 1: CUSTOM APPAREL */}
            <Card className="border-0 shadow-sm rounded-4 p-4 my-4 text-white overflow-hidden" style={{ background: "linear-gradient(135deg, #11998e 0%, #38ef7d 100%)" }}>
              <Row className="align-items-center g-3">
                <Col md={8}>
                  <Badge bg="white" className="text-dark fw-bold px-3 py-1 mb-2 rounded-pill text-uppercase">
                    Custom Apparel Supply
                  </Badge>
                  <h4 className="fw-bold text-white mb-2 fs-5">Build Custom T-Shirts & Hoodies for Your Brand</h4>
                  <p className="text-white-50 small mb-0" style={{ lineHeight: "1.5" }}>
                    High-density DTG printing & embroidery with 0% defect guarantee. Minimum order starting at 25 pcs.
                  </p>
                </Col>
                <Col md={4} className="text-md-end">
                  <Link to="/business-solutions" className="btn btn-light text-dark fw-bold px-4 py-2 rounded-pill shadow-sm">
                    View Apparel Blanks
                  </Link>
                </Col>
              </Row>
            </Card>

            {/* SECONDARY ARTICLES SECTION */}
            <div className="mt-4 mb-4">
              <h4 className="fw-bold text-dark mb-3 fs-5">Printing & Packaging Insights</h4>
              <Row className="g-3 g-md-4">
                {blogData.slice(0, 2).map((data) => (
                  <Col key={`sec-${data.id}`} xs={12} sm={6}>
                    <BlogCard cardData={{...data, title: `Packaging & Print: ${data.title}`}} />
                  </Col>
                ))}
              </Row>
            </div>

            {/* IN-FEED NATIVE AD BANNER 2: FRANCHISE PARTNER */}
            <Card className="border-0 shadow-sm rounded-4 p-4 my-4 bg-white border">
              <Row className="align-items-center g-3">
                <Col md={8}>
                  <div className="d-flex align-items-center gap-2 mb-2">
                    <FaBuilding className="text-primary" size={20} />
                    <Badge bg="primary-subtle" className="text-primary fw-bold px-2 py-1 rounded">
                      Franchise Opportunity
                    </Badge>
                  </div>
                  <h5 className="fw-bold text-dark mb-1">Own a PrintMont Franchise Outlet in Your City</h5>
                  <p className="text-secondary small mb-0" style={{ lineHeight: "1.5" }}>
                    Tap into the ₹25,000 Cr gifting industry with high gross margins (45%-65%) and full factory supply support.
                  </p>
                </Col>
                <Col md={4} className="text-md-end">
                  <Link to="/franchise" className="btn btn-primary btn-sm rounded-pill px-4 py-2 fw-bold">
                    Apply For Franchise
                  </Link>
                </Col>
              </Row>
            </Card>
          </Col>

          {/* RIGHT SIDEBAR */}
          <Col xs={12} lg={3}>
            <Sidebar />
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default Blog;
