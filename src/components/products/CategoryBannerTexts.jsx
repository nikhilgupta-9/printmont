import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { API_ENDPOINTS, resolveImageUrl } from "../../config/apiEndpoints";
import "./CategoryBannerTexts.css";

// Admin-managed banners (image and/or text) for the product's category (admin: category-banner-texts.php).
// Only shown on the product page, and only for products in that category. Renders nothing otherwise.
const CategoryBannerTexts = ({ productId, isMobile = false, className = "" }) => {
  const [banners, setBanners] = useState([]);

  useEffect(() => {
    if (!productId) return;
    let cancelled = false;
    fetch(API_ENDPOINTS.CATEGORY_BANNER_TEXTS(productId))
      .then((res) => res.json())
      .then((res) => {
        if (!cancelled) setBanners(res?.success && Array.isArray(res.data) ? res.data : []);
      })
      .catch(() => {
        if (!cancelled) setBanners([]);
      });
    return () => { cancelled = true; };
  }, [productId]);

  if (!banners.length) return null;

  const wrapLink = (b, content) => {
    const link = (b.button_link || "").trim();
    if (!link) return content;
    if (/^https?:\/\//i.test(link)) {
      return <a href={link} target="_blank" rel="noopener noreferrer" className="d-block">{content}</a>;
    }
    return <Link to={link.startsWith("/") ? link : `/${link}`} className="d-block">{content}</Link>;
  };

  const renderButton = (b) => {
    if (!b.button_text) return null;
    const style = {
      color: b.text_color,
      borderColor: b.text_color,
      fontSize: isMobile ? "0.75rem" : "0.8rem",
    };
    const className = "btn btn-sm rounded-pill fw-semibold px-3 mt-2 bg-transparent";
    const link = (b.button_link || "").trim();
    if (!link) return <span className={className} style={style}>{b.button_text}</span>;
    if (/^https?:\/\//i.test(link)) {
      return <a href={link} target="_blank" rel="noopener noreferrer" className={className} style={style}>{b.button_text}</a>;
    }
    return <Link to={link.startsWith("/") ? link : `/${link}`} className={className} style={style}>{b.button_text}</Link>;
  };

  // Admin text (title, description and/or button) sits right under the banner image, in the same card
  const hasText = (b) => Boolean((b.title || "").trim() || (b.description || "").trim() || (b.button_text || "").trim());

  const renderText = (b) => (
    <div className="cbt-text" style={{ color: b.text_color || undefined }}>
      {b.title && <div className="cbt-text__title">{b.title}</div>}
      {b.description && <p className="cbt-text__desc">{b.description}</p>}
      {renderButton(b)}
    </div>
  );

  return (
    <div className={`cbt-list ${className || (isMobile ? "mx-2 my-2" : "w-100 my-3")}`}>
      {banners.map((b) => (
        <div key={b.id} className="cbt-card">
          {b.image && wrapLink(
            b,
            <img
              src={resolveImageUrl(b.image)}
              alt={b.title || b.description || "Banner"}
              loading="lazy"
              className="cbt-img"
              onError={(e) => { e.currentTarget.style.display = "none"; }}
            />
          )}
          {hasText(b) && renderText(b)}
        </div>
      ))}
    </div>
  );
};

export default CategoryBannerTexts;
