import React from 'react';
import { ASSET_URL } from '../../config/apiEndpoints';
import { BiCategory } from 'react-icons/bi';
import { FiPackage } from 'react-icons/fi';

const SearchSuggestion = ({ item, isSelected, onClick, onMouseEnter }) => {
  const isProduct = item.itemType === 'product';
  
  // Format Image URL
  let imageUrl = null;
  if (isProduct && item.image) {
    imageUrl = item.image.startsWith('http') ? item.image : `${ASSET_URL}${item.image}`;
  } else if (!isProduct && item.image) {
    imageUrl = item.image.startsWith('http') ? item.image : `${ASSET_URL}${item.image}`;
  }

  // Type Badge Color styling
  const getTypeBadgeClass = (type) => {
    switch (type) {
      case 'product': return 'bg-primary-subtle text-primary border-primary-subtle';
      case 'category': return 'bg-success-subtle text-success border-success-subtle';
      case 'subCategory': return 'bg-info-subtle text-info border-info-subtle';
      case 'productType': return 'bg-warning-subtle text-warning border-warning-subtle';
      default: return 'bg-secondary-subtle text-secondary border-secondary-subtle';
    }
  };

  const typeLabel = item.type || (isProduct ? 'Product' : 'Category');

  return (
    <div
      className={`d-flex align-items-center justify-content-between p-2.5 rounded-3 transition-colors cursor-pointer ${
        isSelected ? 'bg-primary bg-opacity-10' : 'hover-bg-light'
      }`}
      onClick={() => onClick(item)}
      onMouseEnter={onMouseEnter}
      style={{
        cursor: 'pointer',
        transition: 'background-color 0.15s ease',
        borderLeft: isSelected ? '3px solid #3b7ddd' : '3px solid transparent'
      }}
    >
      <div className="d-flex align-items-center gap-3 overflow-hidden me-2">
        {/* Thumbnail Image or Icon */}
        <div 
          className="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0 bg-light border"
          style={{ width: '42px', height: '42px', overflow: 'hidden' }}
        >
          {imageUrl ? (
            <img 
              src={imageUrl} 
              alt={item.name} 
              className="w-100 h-100 object-fit-cover"
              onError={(e) => {
                e.target.style.display = 'none';
                e.target.nextSibling.style.display = 'flex';
              }}
            />
          ) : null}
          <div 
            className="text-secondary align-items-center justify-content-center w-100 h-100"
            style={{ display: imageUrl ? 'none' : 'flex' }}
          >
            {isProduct ? <FiPackage style={{ fontSize: '1.2rem' }} /> : <BiCategory style={{ fontSize: '1.2rem' }} />}
          </div>
        </div>

        {/* Text Content: Name */}
        <div className="text-truncate">
          <div className="fw-medium text-dark text-truncate" style={{ fontSize: '0.9rem' }}>
            {item.name}
          </div>
        </div>
      </div>
    </div>
  );
};

export default SearchSuggestion;
