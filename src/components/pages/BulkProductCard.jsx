import React from "react";
import { Link } from "react-router-dom";
import "./BulkProductCard.css";

/**
 * BulkProductCard
 * Pixel-perfect product card matching the Printmont Bulk Orders design.
 * Features clean photo framing, centered typography, and formatted wholesale price.
 */
const BulkProductCard = ({ product, resolveImg, onQuoteClick }) => {
  if (!product) return null;

  const img = resolveImg(product.image_path) || "/placeholder.jpg";
  
  // Extract category and slug if extra is formatted as "Category | slug"
  const extraParts = (product.extra || "").split("|");
  const slugTag = extraParts[1]?.trim() || (extraParts[0]?.includes("-") ? extraParts[0].trim() : "");
  const productUrl = slugTag ? `/product/${slugTag}` : null;

  // Parse pricing e.g. "₹599 each for 100 pieces"
  const rawPrice = product.content || "";
  const priceMatch = rawPrice.match(/^([₹$€£]|Rs\.?\s*)?(\d+[\d,.]*)\s*(.*)$/i);
  const priceSymbol = priceMatch ? (priceMatch[1] || "₹") : "₹";
  const priceValue = priceMatch ? priceMatch[2] : rawPrice;
  const priceSuffix = priceMatch ? priceMatch[3] : "";

  const CardContent = (
    <div className="bk-card-inner">
      {/* Top Image Box */}
      <div className="bk-card-img-wrap">
        <img
          src={img}
          alt={product.title}
          className="bk-card-img"
          loading="lazy"
          onError={(e) => {
            e.target.src = "/placeholder.jpg";
          }}
        />
      </div>

      {/* Bottom Text Box */}
      <div className="bk-card-info">
        <h4 className="bk-card-title">{product.title}</h4>
        {rawPrice && (
          <div className="bk-card-price">
            <span className="bk-card-price-val">{priceSymbol}{priceValue}</span>
            {priceSuffix && <span className="bk-card-price-sub"> {priceSuffix}</span>}
          </div>
        )}
      </div>
    </div>
  );

  return (
    <div className="bk-clean-card">
      {productUrl ? (
        <Link to={productUrl} className="bk-card-link">
          {CardContent}
        </Link>
      ) : (
        <div 
          className="bk-card-clickable" 
          onClick={() => onQuoteClick && onQuoteClick(product.title)}
          role="button"
          tabIndex={0}
        >
          {CardContent}
        </div>
      )}
    </div>
  );
};

export default BulkProductCard;
