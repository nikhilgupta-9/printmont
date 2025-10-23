import React from 'react';

// Reusable Component
const SectionOne = ({ banners = [] }) => {
  return (
    <div className="container-fluid m-0 p-0">
      <div className="row g-1 m-0 px-1 container-fluid d-flex">
        {banners.length > 0 ? (
          banners.map((src, index) => (
            <div className="col-6 col-lg-3 rounded-md" key={index}>
              <img
                src={src}
                alt={`Banner ${index + 1}`}
                className="img-fluid rounded shadow-sm w-100"
                style={{
                  height: 'auto',
                  objectFit: 'contain',
                  maxHeight: '350px',
                }}
              />
            </div>
          ))
        ) : (
          <div className="text-center text-muted p-4 w-100">
            No banners available
          </div>
        )}
      </div>
    </div>
  );
};

export default SectionOne;
