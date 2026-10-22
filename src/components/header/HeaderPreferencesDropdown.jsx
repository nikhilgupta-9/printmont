import React, { useState, useRef, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { BsThreeDotsVertical } from 'react-icons/bs';
import { FaBell, FaHeadset, FaHandshake } from 'react-icons/fa';
import { LuChartNoAxesCombined } from 'react-icons/lu';
import { RiDownload2Fill } from 'react-icons/ri';
import './HeaderPreferencesDropdown.css';

const PREFERENCE_ITEMS = [
  {
    title: 'Notification Preferences',
    path: '/notification-preference',
    type: 'bell',
    icon: FaBell,
  },
  {
    title: 'Support',
    path: '/support',
    type: 'circle',
    icon: FaHeadset,
  },
  {
    title: 'Business Solutions',
    path: '/business-solutions',
    type: 'circle',
    icon: LuChartNoAxesCombined,
  },
  {
    title: 'Become a Seller',
    path: '/become-a-seller',
    type: 'circle',
    icon: FaHandshake,
  },
  {
    title: 'Download the App',
    path: '/app-download',
    type: 'circle',
    icon: RiDownload2Fill,
  },
];

const HeaderPreferencesDropdown = () => {
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef(null);
  const timerRef = useRef(null);

  // Close on outside click
  useEffect(() => {
    const handleClickOutside = (e) => {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Smooth hover handling with micro-delay to prevent accidental flickering
  const handleMouseEnter = () => {
    if (timerRef.current) clearTimeout(timerRef.current);
    setIsOpen(true);
  };

  const handleMouseLeave = () => {
    timerRef.current = setTimeout(() => {
      setIsOpen(false);
    }, 150);
  };

  const toggleDropdown = () => {
    setIsOpen((prev) => !prev);
  };

  const handleItemClick = () => {
    setIsOpen(false);
  };

  return (
    <div
      className={`nav-three-dots-wrapper ${isOpen ? 'is-open' : ''}`}
      ref={containerRef}
      onMouseEnter={handleMouseEnter}
      onMouseLeave={handleMouseLeave}
    >
      <button
        type="button"
        className="nav-three-dots-btn"
        onClick={toggleDropdown}
        aria-label="More navigation options"
        aria-expanded={isOpen}
      >
        <BsThreeDotsVertical size={19} />
      </button>

      {isOpen && (
        <div className="nav-pref-dropdown-menu">
          {PREFERENCE_ITEMS.map((item) => {
            const IconComponent = item.icon;
            return (
              <Link
                key={item.title}
                to={item.path}
                className="nav-pref-item"
                onClick={handleItemClick}
              >
                {item.type === 'bell' ? (
                  <div className="nav-pref-bell-icon">
                    <IconComponent size={19} />
                  </div>
                ) : (
                  <div className="nav-pref-circle-icon">
                    <IconComponent size={13} />
                  </div>
                )}
                <span className="nav-pref-text">{item.title}</span>
              </Link>
            );
          })}
        </div>
      )}
    </div>
  );
};

export default HeaderPreferencesDropdown;
