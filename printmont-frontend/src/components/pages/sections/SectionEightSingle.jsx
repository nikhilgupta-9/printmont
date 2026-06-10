import React from 'react';
import { FaChevronRight } from 'react-icons/fa';

const SectionEightSingle = ({ title, items }) => {
  return (
    <div className="container-fluid px-md-3 py-3 custom-bg">
      <div className="row justify-content-center">
        <div className="col-12 col-md-10 col-lg-6">
          <div className="border bg-white rounded-3 p-3 h-100">
            {/* Header */}
            <div className="d-flex justify-content-between align-items-center mb-3 px-1">
              <h5 className="mb-0 fw-bold">{title}</h5>
              <button
                className="border-0 bg-primary text-white rounded-circle d-flex justify-content-center align-items-center"
                style={{ width: '32px', height: '32px' }}
              >
                <FaChevronRight size={16} />
              </button>
            </div>

            {/* Grid of cards */}
            <div className="row g-2">
              {items.map((item, idx) => (
                <div className="col-6" key={idx}>
                  <div className="border rounded-3 p-2 text-center bg-white h-100">
                    <img
                      src={item.image}
                      alt={item.title}
                      className="img-fluid mb-2"
                      style={{
                        maxHeight: '150px',
                        objectFit: 'contain',
                      }}
                    />
                    <h6 className="fw-bold mb-1">{item.title}</h6>
                    <p className="text-muted mb-0">{item.discount}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Mobile-only "View All" button */}
          <div className="mt-3 d-lg-none">
            <button className="btn btn-primary w-100 d-flex justify-content-center align-items-center">
              View All
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SectionEightSingle;
