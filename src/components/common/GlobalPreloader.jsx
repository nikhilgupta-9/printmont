import React from 'react';
import { resolveImageUrl } from '../../config/apiEndpoints';

const GlobalPreloader = ({ loaderData, isVisible }) => {
  if (!isVisible) return null;

  const isEnabled = loaderData?.is_enabled ?? true;
  if (!isEnabled) return null;

  const loaderType = loaderData?.loader_type || 'brand_logo_pulse';
  const loadingText = loaderData?.loading_text || 'Printing Ideas Into Reality...';
  const showText = loaderData?.show_text ?? true;
  const bgColor = loaderData?.bg_color || '#ffffff';
  const accentColor = loaderData?.accent_color || '#0B53A1';
  const secondaryColor = loaderData?.secondary_color || '#f8991d';
  const blurBackdrop = loaderData?.blur_backdrop ?? true;
  const customImg = loaderData?.custom_loader_image ? resolveImageUrl(loaderData.custom_loader_image) : '';
  const logoUrl = loaderData?.logo_url ? resolveImageUrl(loaderData.logo_url) : '/PrintLogo.png';

  const renderAnimation = () => {
    switch (loaderType) {
      case 'brand_logo_pulse':
        return (
          <div style={{ position: 'relative', display: 'inline-flex', alignItems: 'center', justifyContent: 'center' }}>
            <div className="pm-pulse-ring" style={{ borderColor: accentColor }} />
            <div className="pm-pulse-ring pm-pulse-ring-2" style={{ borderColor: secondaryColor }} />
            <div style={{
              background: '#ffffff',
              padding: '14px 22px',
              borderRadius: '20px',
              boxShadow: '0 12px 30px rgba(11, 83, 161, 0.15)',
              position: 'relative',
              zIndex: 2,
              animation: 'pm-scale-breath 2s ease-in-out infinite'
            }}>
              <img 
                src={logoUrl} 
                alt="Printmont" 
                style={{ height: '42px', maxWidth: '170px', objectFit: 'contain' }}
                onError={(e) => { e.currentTarget.src = '/PrintLogo.png'; }}
              />
            </div>
          </div>
        );

      case 'circular_spinner':
        return (
          <div style={{ width: '60px', height: '60px', position: 'relative' }}>
            <svg className="pm-circular-spinner" viewBox="0 0 50 50" style={{ width: '100%', height: '100%' }}>
              <circle 
                className="pm-spinner-path" 
                cx="25" 
                cy="25" 
                r="20" 
                fill="none" 
                stroke={accentColor}
                strokeWidth="4"
              />
            </svg>
          </div>
        );

      case 'dual_ring':
        return (
          <div className="pm-dual-ring" style={{ width: '56px', height: '56px' }}>
            <div style={{ borderTopColor: accentColor, borderBottomColor: secondaryColor }} />
          </div>
        );

      case 'dots_wave':
        return (
          <div style={{ display: 'flex', gap: '8px', alignItems: 'center', justifyContent: 'center' }}>
            <span className="pm-dot pm-dot-1" style={{ backgroundColor: accentColor }} />
            <span className="pm-dot pm-dot-2" style={{ backgroundColor: secondaryColor }} />
            <span className="pm-dot pm-dot-3" style={{ backgroundColor: accentColor }} />
          </div>
        );

      case 'printing_progress_bar':
        return (
          <div style={{ width: '220px', textAlign: 'center' }}>
            <div style={{
              width: '100%',
              height: '6px',
              backgroundColor: '#e2e8f0',
              borderRadius: '10px',
              overflow: 'hidden',
              position: 'relative'
            }}>
              <div className="pm-scan-bar" style={{ background: `linear-gradient(90deg, ${accentColor}, ${secondaryColor})` }} />
            </div>
          </div>
        );

      case 'custom_gif':
        return customImg ? (
          <img 
            src={customImg} 
            alt="Loading..." 
            style={{ maxHeight: '100px', maxWidth: '160px', objectFit: 'contain' }}
          />
        ) : (
          <div className="pm-dual-ring" style={{ width: '50px', height: '50px' }}>
            <div style={{ borderTopColor: accentColor, borderBottomColor: secondaryColor }} />
          </div>
        );

      default:
        return (
          <div className="pm-circular-spinner" style={{ width: '50px', height: '50px' }}>
            <div style={{ borderTopColor: accentColor }} />
          </div>
        );
    }
  };

  return (
    <div style={{
      position: 'fixed',
      top: 0,
      left: 0,
      width: '100vw',
      height: '100vh',
      backgroundColor: bgColor,
      backdropFilter: blurBackdrop ? 'blur(8px)' : 'none',
      WebkitBackdropFilter: blurBackdrop ? 'blur(8px)' : 'none',
      zIndex: 999999,
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      transition: 'opacity 0.35s ease, visibility 0.35s ease',
      fontFamily: "'Plus Jakarta Sans', -apple-system, sans-serif"
    }}>
      <div style={{ marginBottom: '1.25rem' }}>
        {renderAnimation()}
      </div>

      {showText && loadingText && (
        <div style={{
          fontSize: '13.5px',
          fontWeight: 600,
          color: '#475569',
          letterSpacing: '0.03em',
          animation: 'pm-fade-pulse 1.8s ease-in-out infinite',
          marginTop: '6px'
        }}>
          {loadingText}
        </div>
      )}

      <style>{`
        @keyframes pm-scale-breath {
          0%, 100% { transform: scale(1); }
          50% { transform: scale(1.04); }
        }

        .pm-pulse-ring {
          position: absolute;
          width: 140px;
          height: 140px;
          border-radius: 50%;
          border: 2px solid;
          opacity: 0;
          animation: pm-pulse-expand 2.2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        .pm-pulse-ring-2 {
          animation-delay: 0.8s;
        }

        @keyframes pm-pulse-expand {
          0% { transform: scale(0.6); opacity: 0.8; }
          100% { transform: scale(1.6); opacity: 0; }
        }

        .pm-circular-spinner {
          animation: pm-rotate 1.6s linear infinite;
        }
        .pm-spinner-path {
          stroke-linecap: round;
          animation: pm-dash 1.5s ease-in-out infinite;
        }

        @keyframes pm-rotate {
          100% { transform: rotate(360deg); }
        }
        @keyframes pm-dash {
          0% { stroke-dasharray: 1, 150; stroke-dashoffset: 0; }
          50% { stroke-dasharray: 90, 150; stroke-dashoffset: -35; }
          100% { stroke-dasharray: 90, 150; stroke-dashoffset: -124; }
        }

        .pm-dual-ring {
          display: inline-block;
          position: relative;
        }
        .pm-dual-ring div {
          box-sizing: border-box;
          display: block;
          position: absolute;
          width: 100%;
          height: 100%;
          border: 4px solid transparent;
          border-radius: 50%;
          animation: pm-dual-ring-spin 1.1s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        }

        @keyframes pm-dual-ring-spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }

        .pm-dot {
          width: 12px;
          height: 12px;
          border-radius: 50%;
          display: inline-block;
          animation: pm-dots-bounce 1.4s ease-in-out infinite both;
        }
        .pm-dot-1 { animation-delay: -0.32s; }
        .pm-dot-2 { animation-delay: -0.16s; }
        .pm-dot-3 { animation-delay: 0s; }

        @keyframes pm-dots-bounce {
          0%, 80%, 100% { transform: scale(0); opacity: 0.4; }
          40% { transform: scale(1); opacity: 1; }
        }

        .pm-scan-bar {
          width: 45%;
          height: 100%;
          border-radius: 10px;
          position: absolute;
          animation: pm-scan-anim 1.5s ease-in-out infinite;
        }

        @keyframes pm-scan-anim {
          0% { left: -45%; }
          100% { left: 100%; }
        }

        @keyframes pm-fade-pulse {
          0%, 100% { opacity: 0.6; }
          50% { opacity: 1; }
        }
      `}</style>
    </div>
  );
};

export default GlobalPreloader;
