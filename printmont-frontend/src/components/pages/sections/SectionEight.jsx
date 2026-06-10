import React from 'react';
import { FaChevronRight } from 'react-icons/fa';
import { MdArrowForwardIos, MdKeyboardArrowRight } from 'react-icons/md'; // ⬅️ Imported these from SectionFour
import { Link } from 'react-router';


const renderCardGrid = (title, items) => (
  <div className="border bg-white rounded-3 p-1 h-100 ">
    {/* Title row */}
    <div className="d-flex justify-content-between align-items-center mb-1 mt-2 mt-lg-0 px-1">
      <p className="m-0 section-title fw-semibold text-black">{title}</p>
      <button className='border-0 bg-primary text-white rounded-circle d-flex justify-content-center align-items-center fs-5 p-1'>
        <FaChevronRight size={18} />
      </button>
    </div>

    {/* Grid of product cards */}
    <div className="row g-sm-0 g-1 m-0 p-0">
      {items.map((item, idx) => (
        <div className="col-6 mb-0 mb-lg-0 g-1" key={idx}>

            <Link to={'/'} className="border bg-white rounded-3 p-2 p-lg-1 text-center cus-bg h-100 d-flex justify-content-between align-items-center flex-column text-decoration-none product">

              <div className='three-coontainer-img  image-zoom-wrapper'>
                <img
                src={item.image}
                alt={item.title}

                className="mb-2 bg-white zoom-hover "
                style={{
                  objectFit: 'contain',
                  width:'100%',
                  height:'100%'
                }}
              />
              </div>
              <div>
                <h6 className="fw-semibold section-product-name">{item.title}</h6>
              <p className="text-lg-muted mb-0 section-product-name-offer text-success fw-bold">{item.discount}</p>
              </div>
              
            </Link>
            
          </div>
      ))}
    </div>

    {/* ⬅️ Updated Mobile View Button to match SectionFour structure */}
    <div className='p-1 d-flex d-lg-none'>
      <div className='d-flex d-lg-none w-100 justify-content-center align-items-center border bd rounded bg-light'>
        <Link href="# " className="w-100 py-2 shadow-lg text-center text-decoration-none text-dark fs-6 fw-semibold">View More <span><MdKeyboardArrowRight size={18} /></span></Link>
      </div>
    </div>
  </div>
);

// ---

const SectionEight = ({ columns = [], imageColumn, reverse = false }) => {

  return (
    <div className="container-fluid p-1 m-0">
      {/* ⬅️ Changed p-2 to p-1 to match SectionFour */}
      <div
        className={`row g-1 align-items-stretch ${reverse ? 'flex-lg-row-reverse' : ''
          }`}
      >
        {/* Product Columns */}
        {columns.map((col, index) => (
          <div className="col-12 col-sm-6 col-md-12 col-lg-4" key={index}>
            {renderCardGrid(col.title, col.items)}
          </div>
        ))}

        
      </div>
    </div>
  );
};

export default SectionEight;