import React from "react";
import { IoIosArrowDroprightCircle } from "react-icons/io";
const SectionGrid = ({ data = [] }) => {
  return (
    <div className="container-fluid custom-bg m-0 p-0">
      <div className="row m-0 p-0 g-1">
        {data.map((cat, idx) => (
          <div key={idx} className="col-12 col-md-12 col-lg-4">
            <div className="px-2 pt-2 d-flex flex-column bg-white rounded-3  border bd">
              {/* Header */}
              <div className="pb-2 d-flex justify-content-between align-items-center ">
                <h4>{cat.title}</h4>
                <IoIosArrowDroprightCircle
                  size={30}
                  className="text-primary me-4"
                />
              </div>

              {/* Content */}
              <div className="border-top border-bottom d-flex justify-content-around align-items-center mt-1 mb-1 custom-space">
                {/* Main Item */}
                <div className="d-flex flex-column justify-content-center align-items-center">
                  <div className="square-container rounded-3">
                    <img src={cat.mainItem.img} alt={cat.mainItem.name} className="zoom-hover "/>
                  </div>
                  <p className="m-0 p-0 product-name-font pri">
                    {cat.mainItem.name}
                  </p>
                  <p className="m-0 p-0 fs-8 offer">{cat.mainItem.offer}</p>
                </div>

                <div className="vr mx-3 bd"></div>

                {/* Side Items */}
                <div className="d-flex flex-column justify-content-center align-items-center gap-3 mt-2">
                  {cat.sideItems.map((item, i) => (
                    <div
                      key={i}
                      className="d-flex flex-column justify-content-center align-items-center"
                    >
                      <div className="square-container rounded-3">
                        <img src={item.img} alt={item.name} className="zoom-hover"/>
                      </div>
                      <p className="m-0 p-0 fw- pri">{item.name}</p>
                      <span className="m-0 p-0 fs-8 offer">{item.offer}</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default SectionGrid;
