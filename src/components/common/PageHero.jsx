import React from 'react';
import { Link } from 'react-router-dom';
import { FaHome, FaChevronRight, FaSearch } from 'react-icons/fa';
import './PageHero.css';

/**
 * Standard Reusable Hero Section for Footer & Content Pages
 *
 * Designed for desktop view with breadcrumb, title, description,
 * optional badge, quick stats, search bar, and action buttons.
 *
 * @param {Array} breadcrumbs - [{ label: 'Home', path: '/' }, { label: 'Sitemap' }]
 * @param {string} tag - Tag badge text (e.g. "SITE DIRECTORY", "COMPANY POLICIES")
 * @param {React.ReactNode} tagIcon - Optional icon inside the tag badge
 * @param {string} title - Main heading (h1)
 * @param {React.ReactNode} titleIcon - Optional icon next to the title
 * @param {string} description - Subtitle / explanatory text
 * @param {Array} stats - [{ label: 'Categories', value: '450+', icon: null }]
 * @param {Object} search - { value, onChange, placeholder, onClear }
 * @param {React.ReactNode} actions - Optional action buttons/links
 * @param {React.ReactNode} children - Optional bottom slot (e.g. filter pills)
 * @param {string} className - Additional CSS class
 * @param {string} variant - 'default' | 'compact' | 'light'
 */
export default function PageHero({
  breadcrumbs = [],
  tag,
  tagIcon,
  title,
  titleIcon,
  description,
  stats = [],
  search,
  actions,
  children,
  className = '',
  variant = 'default',
}) {
  return (
    <section className={`pm-page-hero pm-page-hero--${variant} ${className}`.trim()}>
      <div className="pm-page-hero-container">
        
        {/* 1. Breadcrumbs Row (Only visible on Desktop, Rule 9) */}
        {breadcrumbs && breadcrumbs.length > 0 && (
          <nav aria-label="breadcrumb" className="pm-hero-breadcrumb d-none d-md-block">
            <ol className="pm-bc-list">
              {breadcrumbs.map((crumb, idx) => {
                const isLast = idx === breadcrumbs.length - 1;
                const isFirst = idx === 0;

                return (
                  <li
                    key={idx}
                    className={`pm-bc-item ${isLast ? 'active' : ''}`}
                    aria-current={isLast ? 'page' : undefined}
                  >
                    {!isLast && crumb.path ? (
                      <Link to={crumb.path} className="pm-bc-link">
                        {isFirst && <FaHome className="pm-bc-home-icon" />}
                        <span>{crumb.label}</span>
                      </Link>
                    ) : (
                      <span className="pm-bc-current">
                        {isFirst && <FaHome className="pm-bc-home-icon" />}
                        <span>{crumb.label}</span>
                      </span>
                    )}

                    {!isLast && (
                      <FaChevronRight className="pm-bc-sep" aria-hidden="true" />
                    )}
                  </li>
                );
              })}
            </ol>
          </nav>
        )}

        {/* 2. Main Hero Content Row */}
        <div className="pm-hero-main-row">
          {/* Left Column: Badge, Title, Description */}
          <div className="pm-hero-text-col">
            {tag && (
              <div className="pm-hero-tag-wrap">
                <span className="pm-hero-tag">
                  {tagIcon && <span className="pm-hero-tag-icon">{tagIcon}</span>}
                  {tag}
                </span>
              </div>
            )}

            <h1 className="pm-hero-title">
              {titleIcon && <span className="pm-hero-title-icon">{titleIcon}</span>}
              <span>{title}</span>
            </h1>

            {description && (
              <p className="pm-hero-desc">{description}</p>
            )}
          </div>

          {/* Right Column: Search, Stats, or Actions */}
          {(search || (stats && stats.length > 0) || actions) && (
            <div className="pm-hero-right-col">
              {/* Optional Search Input */}
              {search && (
                <div className="pm-hero-search-box">
                  <FaSearch className="pm-hero-search-icon" aria-hidden="true" />
                  <input
                    type="text"
                    className="pm-hero-search-input"
                    placeholder={search.placeholder || 'Search in this section...'}
                    value={search.value || ''}
                    onChange={search.onChange}
                    aria-label="Search"
                  />
                  {search.value && (
                    <button
                      type="button"
                      className="pm-hero-search-clear"
                      onClick={search.onClear}
                      title="Clear search"
                      aria-label="Clear search"
                    >
                      ✕
                    </button>
                  )}
                </div>
              )}

              {/* Stats Badges */}
              {stats && stats.length > 0 && (
                <div className="pm-hero-stats-wrap">
                  {stats.map((st, i) => (
                    <div key={i} className="pm-hero-stat-pill">
                      {st.icon && <span className="pm-stat-icon">{st.icon}</span>}
                      <span className="pm-stat-value">{st.value}</span>
                      <span className="pm-stat-label">{st.label}</span>
                    </div>
                  ))}
                </div>
              )}

              {/* Actions */}
              {actions && (
                <div className="pm-hero-actions-wrap">
                  {actions}
                </div>
              )}
            </div>
          )}
        </div>

        {/* 3. Children Slot (e.g. Tab Filter Buttons) */}
        {children && (
          <div className="pm-hero-children-wrap">
            {children}
          </div>
        )}

      </div>
    </section>
  );
}
