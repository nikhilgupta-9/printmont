import React, { useEffect, useState } from 'react';
import { FaChevronRight } from 'react-icons/fa';
import { MdKeyboardArrowRight } from 'react-icons/md';
import { Link } from 'react-router';

const SectionFour = ({ apiUrl, imageColumn, backgroundImageUrl }) => {
  const [columns, setColumns] = useState([]);
  const [loading, setLoading] = useState(true);

  // ✅ Fetch from API URL passed as prop
  useEffect(() => {
    if (!apiUrl) return; // if no api passed, skip
    const fetchData = async () => {
      try {
        const response = await fetch(apiUrl);
        const data = await response.json();
        setColumns(data);
      } catch (error) {
        console.error('Error fetching API:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [apiUrl]);

  const renderCardGrid = (title, items) => (
    <div
      className="border bg-white rounded-3 p-1 h-100 custom-bg-image"
      style={backgroundImageUrl ? { backgroundImage: `url(${backgroundImageUrl})` } : {}}
    >
      <div className="d-flex justify-content-between align-items-center mb-1 mt-2 mt-lg-0 px-1">
        <p className="m-0 section-title fw-semibold text-black">{title}</p>
        <button className="border-0 bg-primary text-white rounded-circle d-flex justify-content-center align-items-center fs-5 p-1">
          <FaChevronRight />
        </button>
      </div>

      <div className="row g-sm-0 g-1 m-0 p-0">
        {items?.map((item, idx) => (
          <div className="col-6 mb-0 mb-lg-0 g-1" key={idx}>
            <Link to={'/'} className="border bg-white rounded-3 p-2 p-lg-1 text-center cus-bg h-100 d-flex justify-content-between align-items-center flex-column text-decoration-none product">
              <div className="three-coontainer-img image-zoom-wrapper">
                <img
                  src={item.image}
                  alt={item.title}
                  className="mb-2 bg-white zoom-hover"
                  style={{
                    objectFit: 'contain',
                    width: '100%',
                    height: '100%',
                  }}
                />
              </div>
              <div>
                <h6 className="fw-semibold section-product-name mb-1">{item.title}</h6>
                <p className="text-lg-muted mb-0 section-product-name-offer text-success fw-bold">
                  {item.discount}
                </p>
              </div>
            </Link>
          </div>
        ))}
      </div>

      <div className="p-1 d-flex d-lg-none">
        <div className="d-flex d-lg-none w-100 justify-content-center align-items-center border bd rounded bg-light">
          <Link
            href="#"
            className="w-100 py-2 shadow-lg text-center text-decoration-none text-dark fs-6 fw-semibold"
          >
            View More <MdKeyboardArrowRight size={18} />
          </Link>
        </div>
      </div>
    </div>
  );

  if (loading) return <p className="text-center py-5">Loading...</p>;
  if (!columns.length) return <p className="text-center py-5">No data found</p>;

  return (
    <div className="container-fluid p-1 m-0">
      <div className="row g-1 align-items-center px-1">
        {columns.map((col, index) => (
          <div className="col-12 col-sm-6 col-md-12 col-lg-4" key={index}>
            {renderCardGrid(col.title, col.items)}
          </div>
        ))}

        {imageColumn && (
          <div className="col-12 col-sm-12 col-md-12 col-lg-4">
            <div className="border rounded-3 h-100 overflow-hidden p-2 cus-bg">
              <img
                src={imageColumn.imageUrl}
                alt={imageColumn.alt || 'Showcase'}
                className="w-100 h-auto"
                style={{
                  objectFit: 'cover',
                  maxHeight: '560px',
                  display: 'block',
                }}
              />
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default SectionFour;
