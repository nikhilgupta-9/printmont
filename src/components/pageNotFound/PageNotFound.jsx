import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { API_ENDPOINTS, resolveImageUrl } from '../../config/apiEndpoints';

const PageNotFound = () => {
  const navigate = useNavigate();
  const [searchQuery, setSearchQuery] = useState('');
  const [settings, setSettings] = useState({
    badge: '404 Error',
    title: 'Oops! Page Not Found',
    message: 'The page you are looking for has been printed elsewhere, moved, or no longer exists. Let’s get you back on track!',
    image_url: '/assets/404-printmont-error.jpg',
    primary_btn_text: 'Back to Home',
    primary_btn_url: '/',
    secondary_btn_text: 'Explore Top Products',
    secondary_btn_url: '/allproducts',
    show_search_bar: true,
    show_quick_links: true,
    contact_support_text: 'Need Help? Reach our 24x7 Customer Desk',
    support_email: 'support@printmont.com',
    support_phone: '+91 9876543210'
  });

  useEffect(() => {
    fetch(API_ENDPOINTS.ERROR_SETTINGS)
      .then(res => {
        if (!res.ok) throw new Error('Network response was not ok');
        return res.json();
      })
      .then(data => {
        if (data && data.success) {
          setSettings(prev => ({
            ...prev,
            badge: data.badge || prev.badge,
            title: data.title || prev.title,
            message: data.message || prev.message,
            image_url: data.image_url ? resolveImageUrl(data.image_url) : prev.image_url,
            primary_btn_text: data.primary_btn_text || prev.primary_btn_text,
            primary_btn_url: data.primary_btn_url || prev.primary_btn_url,
            secondary_btn_text: data.secondary_btn_text || prev.secondary_btn_text,
            secondary_btn_url: data.secondary_btn_url || prev.secondary_btn_url,
            show_search_bar: data.show_search_bar !== undefined ? Boolean(data.show_search_bar) : prev.show_search_bar,
            show_quick_links: data.show_quick_links !== undefined ? Boolean(data.show_quick_links) : prev.show_quick_links,
            contact_support_text: data.contact_support_text || prev.contact_support_text,
            support_email: data.support_email || prev.support_email,
            support_phone: data.support_phone || prev.support_phone
          }));
        }
      })
      .catch(err => {
        console.warn('Error fetching 404 settings, fallback to defaults:', err);
      });
  }, []);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      navigate(`/search?q=${encodeURIComponent(searchQuery.trim())}`);
    }
  };

  const quickLinks = [
    { label: 'T-Shirts', icon: 'ri-t-shirt-line', url: '/allproducts?category=t-shirts' },
    { label: 'Photo Mugs', icon: 'ri-cup-line', url: '/allproducts?category=mugs' },
    { label: 'Corporate Gifts', icon: 'ri-gift-line', url: '/allproducts?category=gifts' },
    { label: 'Custom Hoodies', icon: 'ri-user-star-line', url: '/allproducts?category=hoodies' },
  ];

  return (
    <div style={{
      minHeight: '80vh',
      width: '100%',
      backgroundColor: '#f8fafc',
      fontFamily: "'Plus Jakarta Sans', 'Segoe UI', Roboto, sans-serif",
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      padding: '3rem 1.25rem'
    }}>
      <div style={{
        maxWidth: '680px',
        width: '100%',
        backgroundColor: '#ffffff',
        borderRadius: '24px',
        border: '1px solid #e2e8f0',
        boxShadow: '0 20px 40px -10px rgba(11, 83, 161, 0.08), 0 1px 3px rgba(0,0,0,0.05)',
        padding: 'clamp(2rem, 5vw, 3.5rem) clamp(1.5rem, 5vw, 2.5rem)',
        textAlign: 'center'
      }}>
        
        {/* 3D Illustration */}
        <div style={{ marginBottom: '1.75rem', position: 'relative' }}>
          <img
            src={settings.image_url}
            alt="Printmont Error Illustration"
            style={{
              maxHeight: '260px',
              maxWidth: '100%',
              width: 'auto',
              objectFit: 'contain',
              borderRadius: '16px',
              transition: 'transform 0.3s ease'
            }}
            onError={(e) => {
              e.currentTarget.src = '/assets/404-printmont-error.jpg';
            }}
          />
        </div>

        {/* 404 Badge */}
        <div style={{
          display: 'inline-flex',
          alignItems: 'center',
          gap: '8px',
          backgroundColor: '#eff6ff',
          color: '#0B53A1',
          border: '1px solid #bfdbfe',
          padding: '5px 16px',
          borderRadius: '30px',
          fontSize: '12.5px',
          fontWeight: 700,
          letterSpacing: '0.04em',
          textTransform: 'uppercase',
          marginBottom: '1rem'
        }}>
          <span style={{
            width: '8px',
            height: '8px',
            borderRadius: '50%',
            backgroundColor: '#0B53A1',
            display: 'inline-block'
          }} />
          {settings.badge}
        </div>

        {/* Title */}
        <h1 style={{
          fontSize: 'clamp(1.75rem, 4vw, 2.35rem)',
          fontWeight: 800,
          color: '#0f172a',
          lineHeight: 1.25,
          marginBottom: '0.75rem'
        }}>
          {settings.title}
        </h1>

        {/* Descriptive Message */}
        <p style={{
          fontSize: '15px',
          color: '#64748b',
          lineHeight: 1.6,
          maxWidth: '520px',
          margin: '0 auto 1.75rem'
        }}>
          {settings.message}
        </p>

        {/* Live Search Bar */}
        {settings.show_search_bar && (
          <form onSubmit={handleSearchSubmit} style={{
            maxWidth: '440px',
            margin: '0 auto 1.5rem',
            position: 'relative'
          }}>
            <div style={{
              display: 'flex',
              alignItems: 'center',
              backgroundColor: '#f8fafc',
              border: '1.5px solid #cbd5e1',
              borderRadius: '40px',
              padding: '6px 8px 6px 18px',
              transition: 'all 0.2s ease',
              boxShadow: '0 2px 6px rgba(0,0,0,0.02)'
            }}>
              <i className="ri-search-line" style={{ color: '#94a3b8', fontSize: '18px', marginRight: '8px' }}></i>
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search customized gifts, mugs, tees..."
                style={{
                  border: 'none',
                  outline: 'none',
                  backgroundColor: 'transparent',
                  width: '100%',
                  fontSize: '13.5px',
                  color: '#1e293b'
                }}
              />
              <button
                type="submit"
                style={{
                  backgroundColor: '#0B53A1',
                  color: '#ffffff',
                  border: 'none',
                  borderRadius: '30px',
                  padding: '8px 16px',
                  fontSize: '12.5px',
                  fontWeight: 600,
                  cursor: 'pointer',
                  whiteSpace: 'nowrap',
                  boxShadow: '0 2px 8px rgba(11, 83, 161, 0.2)'
                }}
              >
                Search
              </button>
            </div>
          </form>
        )}

        {/* Popular Quick Links / Categories */}
        {settings.show_quick_links && (
          <div style={{ marginBottom: '2rem' }}>
            <div style={{ fontSize: '12px', fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '10px' }}>
              Popular Categories
            </div>
            <div style={{
              display: 'flex',
              flexWrap: 'wrap',
              gap: '8px',
              justifyContent: 'center'
            }}>
              {quickLinks.map((item, idx) => (
                <Link
                  key={idx}
                  to={item.url}
                  style={{
                    backgroundColor: '#f1f5f9',
                    color: '#334155',
                    fontSize: '12.5px',
                    fontWeight: 600,
                    padding: '6px 14px',
                    borderRadius: '20px',
                    textDecoration: 'none',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '6px',
                    transition: 'all 0.2s ease',
                    border: '1px solid #e2e8f0'
                  }}
                  onMouseOver={(e) => {
                    e.currentTarget.style.backgroundColor = '#e0edff';
                    e.currentTarget.style.color = '#0B53A1';
                    e.currentTarget.style.borderColor = '#bfdbfe';
                  }}
                  onMouseOut={(e) => {
                    e.currentTarget.style.backgroundColor = '#f1f5f9';
                    e.currentTarget.style.color = '#334155';
                    e.currentTarget.style.borderColor = '#e2e8f0';
                  }}
                >
                  <i className={item.icon}></i>
                  {item.label}
                </Link>
              ))}
            </div>
          </div>
        )}

        {/* Action Buttons */}
        <div style={{
          display: 'flex',
          flexWrap: 'wrap',
          gap: '12px',
          justifyContent: 'center',
          alignItems: 'center',
          marginBottom: '2rem'
        }}>
          {settings.primary_btn_text && (
            <Link
              to={settings.primary_btn_url || '/'}
              style={{
                backgroundColor: '#0B53A1',
                color: '#ffffff',
                padding: '12px 28px',
                borderRadius: '10px',
                fontSize: '14px',
                fontWeight: 700,
                textDecoration: 'none',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '8px',
                boxShadow: '0 4px 14px rgba(11, 83, 161, 0.25)',
                transition: 'all 0.2s'
              }}
            >
              <i className="ri-home-4-line"></i>
              {settings.primary_btn_text}
            </Link>
          )}

          {settings.secondary_btn_text && (
            <Link
              to={settings.secondary_btn_url || '/allproducts'}
              style={{
                backgroundColor: '#ffffff',
                color: '#334155',
                border: '1.5px solid #cbd5e1',
                padding: '11px 24px',
                borderRadius: '10px',
                fontSize: '14px',
                fontWeight: 600,
                textDecoration: 'none',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '8px',
                transition: 'all 0.2s'
              }}
            >
              <i className="ri-shopping-bag-line"></i>
              {settings.secondary_btn_text}
            </Link>
          )}
        </div>

        {/* Support Strip */}
        <div style={{
          borderTop: '1px solid #f1f5f9',
          paddingTop: '1.25rem',
          fontSize: '12.5px',
          color: '#64748b'
        }}>
          <span>{settings.contact_support_text}: </span>
          {settings.support_email && (
            <a
              href={`mailto:${settings.support_email}`}
              style={{ color: '#0B53A1', fontWeight: 600, textDecoration: 'none', marginLeft: '4px' }}
            >
              {settings.support_email}
            </a>
          )}
          {settings.support_phone && (
            <span> &bull; <strong>{settings.support_phone}</strong></span>
          )}
        </div>

      </div>
    </div>
  );
};

export default PageNotFound;
