import React, { useState, useEffect, useMemo } from 'react';
import { Spinner } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { FaFolderOpen, FaSitemap, FaFileCode } from 'react-icons/fa';
import { API_ENDPOINTS } from '../../../config/apiEndpoints';
import { updatePageSEO } from '../../../utils/seo';
import PageHero from '../../common/PageHero';
import './sitemap.css';

const FILTER_TABS = [
  { key: 'all', label: 'All Categories & Sections' },
  { key: 'products_categories', label: 'Categories' },
  { key: 'main_pages', label: 'Shopping & Account' },
  { key: 'company_info', label: 'B2B & Corporate' },
  { key: 'blog_articles', label: 'Blogs & Guides' },
  { key: 'help_support', label: 'Help Center' },
  { key: 'legal_policies', label: 'Policies & Legal' }
];

export default function SitemapPage() {
  const [sections, setSections] = useState([]);
  const [stats, setStats] = useState(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeTab, setActiveTab] = useState('all');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: 'Online Printing Products, Custom Visiting Cards, Apparel & Merchandise Directory @ Printmont.com',
      description: 'Explore the complete directory of Printmont custom printing categories, corporate gifting, wholesale products, policies, and blogs.',
      keywords: 'printmont sitemap, printmont directory, custom printing categories, bulk merchandise list',
      type: 'website'
    });

    const fetchSitemap = async () => {
      try {
        setLoading(true);
        const res = await fetch(API_ENDPOINTS.SITEMAP || '/api/sitemap-api.php');
        const json = await res.json();

        if (json.success && json.data) {
          setSections(json.data.sections || []);
          setStats(json.data.stats || null);
        }
      } catch (err) {
        console.error('Error loading sitemap:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchSitemap();
  }, []);

  // Filter sections by search query and active tab
  const filteredSections = useMemo(() => {
    let result = sections;

    if (activeTab !== 'all') {
      result = result.filter(s => s.category_key === activeTab);
    }

    if (!searchQuery.trim()) {
      return result;
    }

    const q = searchQuery.toLowerCase().trim();

    return result.map(section => {
      // Filter links and subcategories
      const filteredLinks = (section.links || []).filter(link => {
        const titleMatch = link.title?.toLowerCase().includes(q);
        const urlMatch = link.url?.toLowerCase().includes(q);
        
        // Deep search in level 2 & level 3 subcategories
        const subMatch = (link.subcategories || []).some(sub => {
          const sMatch = sub.name?.toLowerCase().includes(q) || sub.url?.toLowerCase().includes(q);
          const childMatch = (sub.subcategories || []).some(child => 
            child.name?.toLowerCase().includes(q) || child.url?.toLowerCase().includes(q)
          );
          return sMatch || childMatch;
        });

        return titleMatch || urlMatch || subMatch;
      });

      return {
        ...section,
        links: filteredLinks
      };
    }).filter(section => section.links.length > 0);
  }, [sections, activeTab, searchQuery]);

  return (
    <div className="fk-sitemap-page">
      {/* 1. Standard Reusable Hero Section with Breadcrumbs for Desktop & Mobile */}
      <PageHero
        breadcrumbs={[
          { label: 'Home', path: '/' },
          { label: 'Sitemap' }
        ]}
        tag="SITE DIRECTORY"
        tagIcon={<FaFolderOpen />}
        title="Printmont Site Directory & Catalog"
        titleIcon={<FaSitemap />}
        description="Explore the complete hierarchical index of custom printing products, corporate gifting, personalized merchandise, policies, and company links."
        stats={[
          { label: 'Categories', value: stats?.categories_count ? `${stats.categories_count}` : '450+' },
          { label: 'Products', value: stats?.products_count ? `${stats.products_count}` : '370+' },
          { label: 'Total Links', value: stats?.total_urls ? `${stats.total_urls}+` : '880+' }
        ]}
        search={{
          placeholder: 'Search 880+ categories, products, and links...',
          value: searchQuery,
          onChange: (e) => setSearchQuery(e.target.value),
          onClear: () => setSearchQuery('')
        }}
        actions={
          <a
            href="/sitemap.xml"
            target="_blank"
            rel="noopener noreferrer"
            className="pm-hero-action-link"
            title="View XML feed for search engines"
          >
            <FaFileCode /> XML Sitemap
          </a>
        }
      >
        {/* Quick Filter Section Tabs */}
        <div className="fk-tab-pills-wrap">
          {FILTER_TABS.map(tab => (
            <button
              key={tab.key}
              type="button"
              className={`fk-tab-pill ${activeTab === tab.key ? 'active' : ''}`}
              onClick={() => setActiveTab(tab.key)}
            >
              {tab.label}
            </button>
          ))}
        </div>
      </PageHero>

      {/* 2. Main Flipkart-Style Tree Container */}
      <main className="fk-sitemap-container">
        <div className="fk-tree-card">
          {loading ? (
            <div className="text-center py-5">
              <Spinner animation="border" variant="primary" />
              <p className="text-muted mt-3 small">Loading Printmont Directory...</p>
            </div>
          ) : filteredSections.length === 0 ? (
            <div className="fk-sitemap-empty">
              <h3>No categories found matching "{searchQuery}"</h3>
              <p>Please try a different search keyword or browse all sections.</p>
              <button 
                type="button" 
                className="fk-reset-btn"
                onClick={() => { setSearchQuery(''); setActiveTab('all'); }}
              >
                Reset Search
              </button>
            </div>
          ) : (
            <>
              {filteredSections.map(sec => {
                const isProductCategories = sec.category_key === 'products_categories';

                return (
                  <div key={sec.category_key} className="fk-section-group">
                    {/* Section H1 / Group Header (e.g. Categories, Company Info, Policies) */}
                    <h1 className="fk-main-title">
                      {sec.category_name}
                    </h1>

                    {isProductCategories ? (
                      /* Category Tree Hierarchy (Level 1 H2 -> Level 2/3 inline cluster) */
                      sec.links.map(cat => {
                        const hasSubs = cat.subcategories && cat.subcategories.length > 0;

                        return (
                          <div key={cat.id} className="fk-category-block">
                            {/* H2 Category Heading matching Flipkart: <h2><a href="...">Books</a></h2> */}
                            <h2 className="fk-sub-heading">
                              <Link to={cat.url}>{cat.title}</Link>
                            </h2>

                            {/* Subcategories Cluster (Flipkart pipe-separated links) */}
                            {hasSubs ? (
                              <div className="fk-links-cluster">
                                {cat.subcategories.map(sub => {
                                  const hasChildren = sub.subcategories && sub.subcategories.length > 0;

                                  if (hasChildren) {
                                    return (
                                      <div key={sub.id} className="fk-subgroup-row">
                                        <div className="fk-subgroup-title">
                                          <Link to={sub.url}>{sub.name}</Link>
                                        </div>
                                        <div className="fk-links-cluster">
                                          {sub.subcategories.map(child => (
                                            <Link key={child.id} to={child.url} className="link">
                                              {child.name}
                                            </Link>
                                          ))}
                                        </div>
                                      </div>
                                    );
                                  }

                                  return (
                                    <Link key={sub.id} to={sub.url} className="link">
                                      {sub.name}
                                    </Link>
                                  );
                                })}
                              </div>
                            ) : null}
                          </div>
                        );
                      })
                    ) : (
                      /* Flat Multi-link cluster for Main pages, Corporate, Policies, Blogs */
                      <div className="fk-links-cluster">
                        {sec.links.map(link => (
                          <Link 
                            key={link.id} 
                            to={link.url} 
                            className="link"
                            target={link.url.endsWith('.xml') ? '_blank' : undefined}
                          >
                            {link.title}
                          </Link>
                        ))}
                      </div>
                    )}
                  </div>
                );
              })}
            </>
          )}
        </div>
      </main>
    </div>
  );
}
