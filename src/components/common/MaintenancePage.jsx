import React, { useState, useEffect } from 'react';
import { resolveImageUrl } from '../../config/apiEndpoints';

const MaintenancePage = ({ maintenanceData, onRefresh }) => {
  const [timeLeft, setTimeLeft] = useState({ hours: 0, minutes: 0, seconds: 0 });
  const [isRefreshing, setIsRefreshing] = useState(false);

  const title = maintenanceData?.title || 'Website Under Scheduled Maintenance';
  const message = maintenanceData?.message || 'We are currently upgrading our systems to bring you a faster and smoother shopping experience. All existing orders are safe and being processed. We will be back online shortly!';
  const contactEmail = maintenanceData?.contact_email || 'support@printmont.com';
  const contactPhone = maintenanceData?.contact_phone || '+91 9876543210';
  const logoUrl = maintenanceData?.logo_url ? resolveImageUrl(maintenanceData.logo_url) : '/PrintLogo.png';
  const showCountdown = maintenanceData?.show_countdown && maintenanceData?.estimated_end_time;

  // Countdown timer calculation
  useEffect(() => {
    if (!showCountdown || !maintenanceData?.estimated_end_time) return;

    const calculateTime = () => {
      const target = new Date(maintenanceData.estimated_end_time).getTime();
      const now = new Date().getTime();
      const difference = target - now;

      if (difference > 0) {
        const hours = Math.floor(difference / (1000 * 60 * 60));
        const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((difference % (1000 * 60)) / 1000);
        setTimeLeft({ hours, minutes, seconds });
      } else {
        setTimeLeft({ hours: 0, minutes: 0, seconds: 0 });
      }
    };

    calculateTime();
    const interval = setInterval(calculateTime, 1000);
    return () => clearInterval(interval);
  }, [showCountdown, maintenanceData?.estimated_end_time]);

  const handleManualRefresh = () => {
    setIsRefreshing(true);
    if (onRefresh) {
      onRefresh();
    } else {
      window.location.reload();
    }
    setTimeout(() => setIsRefreshing(false), 1200);
  };

  const cleanPhone = (contactPhone || '').replace(/[^0-9]/g, '');
  const waPhone = cleanPhone.length === 10 ? `91${cleanPhone}` : cleanPhone;
  const waLink = waPhone ? `https://wa.me/${waPhone}?text=${encodeURIComponent('Hi Printmont Support, I am inquiring about the site maintenance.')}` : '';

  return (
    <div style={{
      minHeight: '100vh',
      width: '100%',
      backgroundColor: '#f0f2f5',
      fontFamily: "'Plus Jakarta Sans', 'Segoe UI', Roboto, sans-serif",
      display: 'flex',
      flexDirection: 'column',
      justifyContent: 'space-between',
      color: '#1e293b'
    }}>
      
      {/* Top Brand Header */}
      <header style={{
        backgroundColor: '#ffffff',
        borderBottom: '1px solid #e2e8f0',
        padding: '14px 24px',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        boxShadow: '0 2px 4px rgba(0,0,0,0.03)'
      }}>
        <div style={{ display: 'flex', alignItems: 'center' }}>
          <img 
            src={logoUrl} 
            alt="Printmont" 
            style={{ height: '38px', maxWidth: '160px', objectFit: 'contain' }}
            onError={(e) => { e.currentTarget.src = '/PrintLogo.png'; }}
          />
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: '16px', fontSize: '13px' }}>
          {contactEmail && (
            <a href={`mailto:${contactEmail}`} style={{ color: '#64748b', textDecoration: 'none', display: 'flex', alignItems: 'center', gap: '6px' }}>
              <i className="ri-mail-line" style={{ color: '#0B53A1' }}></i>
              <span className="d-none d-sm-inline">{contactEmail}</span>
            </a>
          )}
          {contactPhone && (
            <a href={`tel:${contactPhone}`} style={{ color: '#0B53A1', fontWeight: 600, textDecoration: 'none', display: 'flex', alignItems: 'center', gap: '6px' }}>
              <i className="ri-phone-line"></i>
              <span>{contactPhone}</span>
            </a>
          )}
        </div>
      </header>

      {/* Main Content Area */}
      <main style={{
        flex: 1,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '2.5rem 1.25rem'
      }}>
        <div style={{
          maxWidth: '640px',
          width: '100%',
          backgroundColor: '#ffffff',
          borderRadius: '20px',
          border: '1px solid #e2e8f0',
          boxShadow: '0 15px 35px -5px rgba(11, 83, 161, 0.08), 0 0 1px rgba(0,0,0,0.05)',
          padding: 'clamp(2rem, 5vw, 3rem) clamp(1.5rem, 5vw, 2.5rem)',
          textAlign: 'center',
          position: 'relative'
        }}>
          
          {/* Maintenance Icon Badge */}
          <div style={{
            width: '80px',
            height: '80px',
            margin: '0 auto 1.5rem',
            borderRadius: '50%',
            backgroundColor: '#e0edff',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            boxShadow: '0 8px 20px rgba(11, 83, 161, 0.15)'
          }}>
            <svg 
              width="42" 
              height="42" 
              viewBox="0 0 24 24" 
              fill="none" 
              stroke="#0B53A1" 
              strokeWidth="2" 
              strokeLinecap="round" 
              strokeLinejoin="round"
              style={{ animation: 'spin-slow 12s linear infinite' }}
            >
              <circle cx="12" cy="12" r="3" />
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
            </svg>
          </div>

          {/* Status Chip */}
          <div style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '8px',
            backgroundColor: '#eff6ff',
            color: '#0B53A1',
            border: '1px solid #bfdbfe',
            padding: '5px 14px',
            borderRadius: '30px',
            fontSize: '12px',
            fontWeight: 700,
            letterSpacing: '0.02em',
            marginBottom: '1rem'
          }}>
            <span style={{
              width: '8px',
              height: '8px',
              borderRadius: '50%',
              backgroundColor: '#0B53A1',
              display: 'inline-block'
            }} />
            SCHEDULED UPGRADE IN PROGRESS
          </div>

          {/* Headline */}
          <h1 style={{
            fontSize: 'clamp(1.6rem, 3.5vw, 2.1rem)',
            fontWeight: 800,
            color: '#0f172a',
            lineHeight: 1.25,
            marginBottom: '0.85rem'
          }}>
            {title}
          </h1>

          {/* Message Description */}
          <p style={{
            fontSize: '14.5px',
            color: '#475569',
            lineHeight: 1.6,
            maxWidth: '520px',
            margin: '0 auto 1.75rem'
          }}>
            {message}
          </p>

          {/* Live Countdown Timer */}
          {showCountdown && (
            <div style={{
              backgroundColor: '#f8fafc',
              border: '1px solid #e2e8f0',
              borderRadius: '14px',
              padding: '1rem 1.25rem',
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '1.25rem',
              marginBottom: '2rem'
            }}>
              <div style={{ minWidth: '55px' }}>
                <div style={{ fontSize: '26px', fontWeight: 800, color: '#0B53A1', lineHeight: 1 }}>
                  {String(timeLeft.hours).padStart(2, '0')}
                </div>
                <div style={{ fontSize: '11px', color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.05em', marginTop: '4px', fontWeight: 600 }}>
                  Hours
                </div>
              </div>
              <div style={{ color: '#cbd5e1', fontSize: '22px', fontWeight: 300 }}>:</div>
              <div style={{ minWidth: '55px' }}>
                <div style={{ fontSize: '26px', fontWeight: 800, color: '#0B53A1', lineHeight: 1 }}>
                  {String(timeLeft.minutes).padStart(2, '0')}
                </div>
                <div style={{ fontSize: '11px', color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.05em', marginTop: '4px', fontWeight: 600 }}>
                  Mins
                </div>
              </div>
              <div style={{ color: '#cbd5e1', fontSize: '22px', fontWeight: 300 }}>:</div>
              <div style={{ minWidth: '55px' }}>
                <div style={{ fontSize: '26px', fontWeight: 800, color: '#0B53A1', lineHeight: 1 }}>
                  {String(timeLeft.seconds).padStart(2, '0')}
                </div>
                <div style={{ fontSize: '11px', color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.05em', marginTop: '4px', fontWeight: 600 }}>
                  Secs
                </div>
              </div>
            </div>
          )}

          {/* Action Buttons */}
          <div style={{
            display: 'flex',
            flexWrap: 'wrap',
            gap: '10px',
            justifyContent: 'center',
            alignItems: 'center'
          }}>
            <button
              onClick={handleManualRefresh}
              disabled={isRefreshing}
              style={{
                backgroundColor: '#0B53A1',
                border: 'none',
                color: '#ffffff',
                padding: '11px 24px',
                borderRadius: '8px',
                fontSize: '13.5px',
                fontWeight: 600,
                cursor: isRefreshing ? 'wait' : 'pointer',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '8px',
                boxShadow: '0 4px 12px rgba(11, 83, 161, 0.25)',
                transition: 'background 0.2s'
              }}
            >
              <i className={`ri-refresh-line ${isRefreshing ? 'ri-spin' : ''}`}></i>
              {isRefreshing ? 'Checking...' : 'Check Status'}
            </button>

            {waLink && (
              <a 
                href={waLink}
                target="_blank"
                rel="noopener noreferrer"
                style={{
                  backgroundColor: '#22c55e',
                  border: 'none',
                  color: '#ffffff',
                  padding: '11px 20px',
                  borderRadius: '8px',
                  fontSize: '13.5px',
                  fontWeight: 600,
                  textDecoration: 'none',
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '8px',
                  boxShadow: '0 4px 12px rgba(34, 197, 94, 0.2)'
                }}
              >
                <i className="ri-whatsapp-line" style={{ fontSize: '16px' }}></i>
                WhatsApp Support
              </a>
            )}

            {contactEmail && (
              <a 
                href={`mailto:${contactEmail}?subject=${encodeURIComponent('Printmont Storefront Inquiry')}`}
                style={{
                  backgroundColor: '#f8fafc',
                  border: '1px solid #cbd5e1',
                  color: '#334155',
                  padding: '11px 18px',
                  borderRadius: '8px',
                  fontSize: '13.5px',
                  fontWeight: 600,
                  textDecoration: 'none',
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '8px'
                }}
              >
                <i className="ri-mail-line text-primary"></i>
                Email Support
              </a>
            )}
          </div>

          {/* Trust Highlights Strip */}
          <div style={{
            marginTop: '2.5rem',
            paddingTop: '1.5rem',
            borderTop: '1px solid #f1f5f9',
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))',
            gap: '1rem',
            textAlign: 'center'
          }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px', fontSize: '12px', color: '#64748b' }}>
              <i className="ri-shield-check-line" style={{ color: '#0B53A1', fontSize: '15px' }}></i>
              <span>100% Secure Data</span>
            </div>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px', fontSize: '12px', color: '#64748b' }}>
              <i className="ri-truck-line" style={{ color: '#0B53A1', fontSize: '15px' }}></i>
              <span>Orders In Progress</span>
            </div>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px', fontSize: '12px', color: '#64748b' }}>
              <i className="ri-customer-service-2-line" style={{ color: '#0B53A1', fontSize: '15px' }}></i>
              <span>24x7 Help Desk</span>
            </div>
          </div>

        </div>
      </main>

      {/* Footer */}
      <footer style={{
        backgroundColor: '#182338',
        color: '#94a3b8',
        padding: '16px 24px',
        textAlign: 'center',
        fontSize: '12.5px',
        borderTop: '1px solid rgba(255,255,255,0.05)'
      }}>
        <div>&copy; {new Date().getFullYear()} Printmont. All Rights Reserved. &bull; Custom Clothing, Gifts &amp; Corporate Merchandise</div>
      </footer>

      <style>{`
        @keyframes spin-slow {
          100% { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
};

export default MaintenancePage;
