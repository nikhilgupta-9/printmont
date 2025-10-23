// Banner.jsx
import React from 'react';
import PropTypes from 'prop-types';

const Banner = ({ images = [] }) => {
  return (
   <>
     <div className="container-fluid m-0 p-0  ">
      <div className="row g-1 m-0 px-1 container-fluid d-flex">
        {images.map((image, index) => (
          <div className="col-md-6" style={{maxHeight:"350px"}} key={index}>
            {/* <div className=''> */}
              <img
              src={image.src}
              alt={image.alt || `banner-${index + 1}`}
              className="rounded"
              width="100%"
              height="auto"
              style={{maxHeight:"350px", objectFit:"contain"}}
            />
            {/* </div> */}
          </div>
        ))}
      </div>
    </div>
   </>
  );
};

// Optional: Type-checking with PropTypes
Banner.propTypes = {
  images: PropTypes.arrayOf(
    PropTypes.shape({
      src: PropTypes.string.isRequired,
      alt: PropTypes.string
    })
  ).isRequired
};

export default Banner;
