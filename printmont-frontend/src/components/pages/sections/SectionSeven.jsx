import React from 'react';
import { IoIosArrowDroprightCircle } from "react-icons/io";

const SectionSix = () => {
  return (
    <div className='container-fluid p-0 px-2 pt-4 custom-bg'>
      <div className='row'>
        
        {/* Box 1 */}
        <div className='col-12 col-md-6 col-lg-4 mb-4'>
          <div className='px-2 pt-2 d-flex flex-column bg-white rounded-3'>
            <div className='pb-2 d-flex justify-content-between align-items-center'>
              <h4>Mens</h4>
              <div className='me-4'>
                <IoIosArrowDroprightCircle size={30} className='text-primary' />
              </div>
            </div>

            <div className='border-top border-bottom d-flex justify-content-around gap-2 align-items-center mt-2 mb-2'>
              <div className='d-flex flex-column justify-content-center align-items-center'>
                <img src="./section-img/pro20.jpeg" className='img-fluid rounded-3' alt="img" />
                <p className='m-0 p-0 fw-bold product-name-font'>T-shirt</p>
                <p className='m-0 p-0 fs-8 offer'>Min 50% off</p>
              </div>

              <div className='d-flex'>
                <div className='d-flex justify-content-between align-items-center flex-column'>
                  <div className='d-flex flex-column justify-content-center align-items-center p-2 border-start'>
                    <img src="./section-img/pro20.jpeg" className='img-fluid rounded-3' alt="img" />
                    <h6 className='mt-2'>Watch</h6>
                    <p className='me-2'>Top offer</p>
                  </div>
                  <div className='d-flex flex-column justify-content-center align-items-center p-2 border-start'>
                    <img src="./section-img/pro20.jpeg" className='img-fluid rounded-3' alt="img" />
                    <h6 className='mt-2'>Watch</h6>
                    <p className='me-2'>Top offer</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Box 2 */}
        <div className='col-12 col-md-6 col-lg-4 mb-4'>
          <div className='px-2 pt-2 bg-white rounded-3'>
            <div className='pb-2 d-flex justify-content-between align-items-center'>
              <h4>Mens</h4>
              <div className='me-4'>
                <IoIosArrowDroprightCircle size={30} className='text-primary' />
              </div>
            </div>

            <div className='border-top border-bottom d-flex justify-content-around gap-2 align-items-center mt-2 mb-2'>
              <div className='d-flex flex-column justify-content-center align-items-center'>
                <img src="./section-img/pro20.jpeg" className='img-fluid rounded-3' alt="img" />
                <p className='m-0 p-0 fw-bold product-name-font'>T-shirt</p>
                <p className='m-0 p-0 fs-8 offer'>Min 50% off</p>
              </div>

              <div className='d-flex'>
                <div className='d-flex justify-content-between align-items-center flex-column'>
                  <div className='d-flex flex-column justify-content-center align-items-center p-2 border-start'>
                    <img src="./section-img/pro20.jpeg" className='img-fluid rounded-3' alt="img" />
                    <h6 className='mt-2'>Watch</h6>
                    <p className='me-2'>Top offer</p>
                  </div>
                  <div className='d-flex flex-column justify-content-center align-items-center p-2 border-start'>
                    <img src="./section-img/pro20.jpeg" className='img-fluid rounded-3' alt="img" />
                    <h6 className='mt-2'>Watch</h6>
                    <p className='me-2'>Top offer</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Box 3 */}
        <div className='col-12 col-md-6 col-lg-4 mb-4'>
          <div className='px-2 pt-2 d-flex flex-column bg-white rounded-3'>
            <div className='pb-2 d-flex justify-content-between align-items-center'>
              <h4>Mens</h4>
              <div className='me-4'>
                <IoIosArrowDroprightCircle size={30} className='text-primary' />
              </div>
            </div>

            <div className='border-top border-bottom d-flex justify-content-around gap-2 align-items-center mt-2 mb-2'>
              <div className='d-flex flex-column justify-content-center align-items-center'>
                <img src="./section-img/pro20.jpeg" className='img-fluid rounded-3' alt="img" />
                <p className='m-0 p-0 fw-bold product-name-font'>T-shirt</p>
                <p className='m-0 p-0 fs-8 offer'>Min 50% off</p>
              </div>

              <div className='d-flex'>
                <div className='d-flex justify-content-between align-items-center flex-column'>
                  <div className='d-flex flex-column justify-content-center align-items-center p-2 border-start'>
                    <img src="./section-img/pro20.jpeg" className='img-fluid rounded-3' alt="img" />
                    <h6 className='mt-2'>Watch</h6>
                    <p className='me-2'>Top offer</p>
                  </div>
                  <div className='d-flex flex-column justify-content-center align-items-center p-2 border-start'>
                    <img src="./section-img/pro20.jpeg" className='img-fluid rounded-3' alt="img" />
                    <h6 className='mt-2'>Watch</h6>
                    <p className='me-2'>Top offer</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  );
};

export default SectionSix;
