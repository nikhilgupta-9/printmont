import React from 'react';

const SectionTwo = ({ images = [] }) => {
  return (
    <div className="container-fluid d-flex flex-column align-items-center justify-content-center gap-3 bg-gray mt-2 rounded-md p-0" style={{maxHeight:'350px'}}>
      {images.length > 0 ? (
        images.map((imgSrc, index) => (
          <picture key={index} className="col-12">
            {/* Large screen image */}
            <source media="(min-width: 768px)" srcSet={imgSrc.large} />
            {/* Small screen fallback */}
            <img
              src={imgSrc.small}
              alt={`Section Banner ${index + 1}`}
              width="100%"
              style={{ maxHeight: '350px', objectFit: 'cover' }}
              className="rounded section-two-img"
            />
          </picture>
        ))
      ) : (
        <div className="text-center text-muted p-4">
          No section images available
        </div>
      )}
    </div>
  );
};

export default SectionTwo;
