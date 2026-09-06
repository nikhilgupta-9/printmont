import React, { useState, useEffect, useMemo } from 'react';
import { Container, Row, Col } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { API_ENDPOINTS, ASSET_URL } from '../../../config/apiEndpoints';
import './sitemap.css';

const FALLBACK_SECTIONS = [
  {
    category_key: 'main_pages',
    category_name: 'Main Navigation & Shopping',
    icon: 'ri-compass-3-line',
    links: [
      { id: 1, title: 'Home', url: '/' },
      { id: 2, title: 'All Products & Collections', url: '/allproducts' },
      { id: 3, title: 'Shopping Cart', url: '/cart' },
      { id: 4, title: 'Track Your Order', url: '/track-order' },
      { id: 5, title: 'Printmont Coin & Wallet', url: '/wallet' },
    ]
  },
  {
    category_key: 'products_categories',
    category_name: 'Product Categories',
    icon: 'ri-store-2-line',
    links: [
      { id: 'c1', title: 'T-Shirts & Apparel', url: '/category/t-shirts' },
      { id: 'c2', title: 'Custom Drinkware & Mugs', url: '/category/mugs' },
      { id: 'c3', title: 'Business Cards & Stationery', url: '/category/stationery' },
      { id: 'c4', title: 'Custom Phone Cases', url: '/category/phone-cases' },
      { id: 'c5', title: 'Photo Frames & Canvas', url: '/category/photo-frames' },
    ]
  },
  {
    category_key: 'company_info',
    category_name: 'Company & Opportunities',
    icon: 'ri-building-line',
    links: [
      { id: 6, title: 'About Us', url: '/about' },
      { id: 7, title: 'Careers & Vacancies', url: '/careers' },
      { id: 8, title: 'Official Printmont Blog', url: '/blog' },
      { id: 9, title: 'Affiliate Program', url: '/affiliate-program' },
      { id: 10, title: 'Become a Seller / Partner', url: '/become-a-seller' },
      { id: 11, title: 'Business Solutions & Corporate', url: '/business-solutions' },
      { id: 12, title: 'Bulk & Wholesale Orders', url: '/bulk-orders' },
      { id: 13, title: 'Franchise Program', url: '/franchise' },
    ]
  },
  {
    category_key: 'help_support',
    category_name: 'Help & Customer Support',
    icon: 'ri-customer-service-2-line',
    links: [
      { id: 14, title: 'Help Center', url: '/help-center' },
      { id: 15, title: 'Frequently Asked Questions (FAQ)', url: '/faq' },
      { id: 16, title: 'Contact Us', url: '/contact' },
      { id: 17, title: 'Support Ticket Portal', url: '/support' },
      { id: 18, title: 'Quick Navigation Links', url: '/quick-links' },
    ]
  },
  {
    category_key: 'legal_policies',
    category_name: 'Legal & Policies',
    icon: 'ri-shield-check-line',
    links: [
      { id: 19, title: 'Safe & Secure Shopping', url: '/security' },
      { id: 20, title: 'Privacy Policy', url: '/privacy-policy' },
      { id: 21, title: 'Terms & Conditions', url: '/terms-and-conditions' },
      { id: 22, title: 'Terms of Use', url: '/terms-of-use' },
      { id: 23, title: 'Shipping & Delivery Policy', url: '/shipping-policy' },
      { id: 24, title: 'Return & Refund Policy', url: '/refund-policy' },
    ]
  }
];

const ICONS_MAP = {
  main_pages: 'ri-compass-3-line',
  main: 'ri-compass-3-line',
  products_categories: 'ri-store-2-line',
  categories: 'ri-store-2-line',
  company_info: 'ri-building-line',
  company: 'ri-building-line',
  help_support: 'ri-customer-service-2-line',
  support: 'ri-customer-service-2-line',
  legal_policies: 'ri-shield-check-line',
  policies: 'ri-shield-check-line',
  custom: 'ri-links-line'
};

const SitemapPage = () => {
  const [sections, setSections] = useState(FALLBACK_SECTIONS);
  const [searchQuery, setSearchQuery] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchSitemap = async () => {
      try {
        const response = await fetch(API_ENDPOINTS.SITEMAP);
        if (response.ok) {
          const json = await response.json();
          if (json.success && json.data) {
            let apiSections = [];
            if (Array.isArray(json.data.sections)) {
              apiSections = json.data.sections;
            } else if (Array.isArray(json.data)) {
              apiSections = json.data;
            } else if (typeof json.data === 'object') {
              // Convert object map to array
              apiSections = Object.entries(json.data).map(([key, value]) => {
                if (Array.isArray(value)) {
                  return {
                    category_key: key,
                    category_name: key.charAt(0).toUpperCase() + key.slice(1).replace('_', ' '),
                    links: value
                  };
                }
                return value;
              }).filter(item => item && Array.isArray(item.links));
            }

            if (apiSections.length > 0) {
              setSections(apiSections);
            }
          }
        }
      } catch (err) {
        console.warn('Using fallback sitemap data due to API error:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchSitemap();
  }, []);

  // Filter sections by search query
  const filteredSections = useMemo(() => {
    if (!searchQuery.trim()) return sections;

    const query = searchQuery.toLowerCase().trim();

    return sections
      .map((section) => {
        const matchingLinks = (section.links || []).filter(
          (link) =>
            link.title?.toLowerCase().includes(query) ||
            link.url?.toLowerCase().includes(query)
        );
        return {
          ...section,
          links: matchingLinks
        };
      })
      .filter((section) => section.links.length > 0);
  }, [sections, searchQuery]);

  const totalLinksCount = useMemo(() => {
    return filteredSections.reduce(
      (sum, s) => sum + (Array.isArray(s.links) ? s.links.length : 0),
      0
    );
  }, [filteredSections]);

  const xmlUrl = `${ASSET_URL}sitemap.xml`;

  return (
    <div className="sitemap-page">
      <Container>
        {/* Breadcrumb */}
        <div className="mb-3">
          <nav aria-label="breadcrumb">
            <ol className="breadcrumb mb-0" style={{ fontSize: '0.85rem' }}>
              <li className="breadcrumb-item">
                <Link to="/" className="text-decoration-none text-muted">
                  <i className="ri-home-line me-1"></i>Home
                </Link>
              </li>
              <li className="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                Sitemap
              </li>
            </ol>
          </nav>
        </div>

        {/* Hero Section */}
        <div className="sitemap-hero">
          <div className="sitemap-badge">
            <i className="ri-node-tree"></i> Complete Directory
          </div>
          <h1 className="sitemap-hero-title">
            Explore <span>Printmont</span> Sitemap
          </h1>
          <p className="sitemap-hero-desc">
            Quickly navigate to any product category, company page, customer support resource, or legal policy across our entire store.
          </p>

          {/* Live Search */}
          <div className="sitemap-search-wrap">
            <i className="ri-search-line sitemap-search-icon"></i>
            <input
              type="text"
              className="sitemap-search-input"
              placeholder="Search links, categories, policies, help topics..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
          </div>

          {/* Quick Jump Buttons */}
          <div className="sitemap-quick-nav">
            {sections.map((sec) => {
              const iconClass = ICONS_MAP[sec.category_key] || 'ri-folder-line';
              return (
                <a key={sec.category_key} href={`#section-${sec.category_key}`} className="sitemap-quick-btn">
                  <i className={`${iconClass} me-1`}></i>
                  {sec.category_name}
                </a>
              );
            })}
            <a href={xmlUrl} target="_blank" rel="noopener noreferrer" className="sitemap-quick-btn">
              <i className="ri-file-code-line me-1"></i>
              sitemap.xml
            </a>
          </div>
        </div>

        {/* XML Sitemap Info Banner */}
        <div className="sitemap-xml-card">
          <div className="sitemap-xml-info">
            <div className="sitemap-xml-icon">
              <i className="ri-code-s-slash-line"></i>
            </div>
            <div>
              <h6 className="mb-1 fw-bold text-dark">Need Machine-Readable XML Sitemap for Search Engines?</h6>
              <p className="mb-0 text-muted small">
                Our dynamic XML sitemap is actively indexed by Google, Bing, and major search engines for rapid SEO crawling.
              </p>
            </div>
          </div>
          <a
            href={xmlUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="btn btn-outline-danger btn-sm d-flex align-items-center gap-2 fw-semibold px-3 py-2"
          >
            <span>View sitemap.xml</span>
            <i className="ri-external-link-line"></i>
          </a>
        </div>

        {/* Category Cards Grid */}
        {filteredSections.length > 0 ? (
          <Row className="g-4">
            {filteredSections.map((sec) => {
              const iconClass = ICONS_MAP[sec.category_key] || 'ri-folder-line';

              return (
                <Col
                  key={sec.category_key}
                  lg={sec.category_key === 'main_pages' || sec.category_key === 'company_info' ? 6 : 4}
                  md={6}
                  xs={12}
                  id={`section-${sec.category_key}`}
                >
                  <div className="sitemap-section-card">
                    <div className="sitemap-card-header">
                      <h2 className="sitemap-card-title">
                        <i className={`${iconClass} text-theme`}></i>
                        <span>{sec.category_name}</span>
                      </h2>
                      <span className="sitemap-card-count">{sec.links.length} links</span>
                    </div>
                    <div className="sitemap-card-body">
                      <ul className="sitemap-links-list">
                        {sec.links.map((item, idx) => {
                          const isExternal =
                            item.url.startsWith('http://') ||
                            item.url.startsWith('https://');

                          return (
                            <li key={item.id || idx} className="sitemap-link-item">
                              {isExternal ? (
                                <a
                                  href={item.url}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  className="sitemap-link"
                                >
                                  <span>{item.title}</span>
                                  <i className="ri-arrow-right-up-line sitemap-link-icon"></i>
                                </a>
                              ) : (
                                <Link to={item.url} className="sitemap-link">
                                  <span>{item.title}</span>
                                  <i className="ri-arrow-right-line sitemap-link-icon"></i>
                                </Link>
                              )}
                            </li>
                          );
                        })}
                      </ul>
                    </div>
                  </div>
                </Col>
              );
            })}
          </Row>
        ) : (
          <div className="sitemap-empty-state">
            <i className="ri-search-eye-line sitemap-empty-icon"></i>
            <h4 className="fw-bold text-dark mb-2">No Matching Links Found</h4>
            <p className="text-muted mb-3">
              We couldn't find any page matching "<strong>{searchQuery}</strong>". Try a different search term.
            </p>
            <button
              className="btn btn-dark btn-sm px-4 py-2"
              onClick={() => setSearchQuery('')}
            >
              Clear Search
            </button>
          </div>
        )}
      </Container>
    </div>
  );
};

export default SitemapPage;
