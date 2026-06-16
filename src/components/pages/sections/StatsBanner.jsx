import React from "react";

const StatsBanner = () => {
  return (
    <div className="container-fluid" style={{ backgroundColor: "#eaf2fd", padding: "40px 15px" }}>
      <div 
        className="mx-auto d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start"
        style={{ 
          maxWidth: "1440px", 
          border: "2px solid #0056b3", 
          padding: "20px 40px",
          color: "#0056b3"
        }}
      >
        <div className="mb-3 mb-md-0 pe-md-4" style={{ flex: 1 }}>
          <h4 className="fw-bold m-0" style={{ lineHeight: "1.2" }}>
            Numbers Backing Up Our<br />Proposition
          </h4>
        </div>
        
        <div className="mb-3 mb-md-0 px-md-4 text-center" style={{ flex: 1 }}>
          <h4 className="fw-bold m-0">1 Million+</h4>
          <small>Customers Served</small>
        </div>

        <div className="mb-3 mb-md-0 px-md-4 text-center" style={{ flex: 1 }}>
          <h4 className="fw-bold m-0">20,000+</h4>
          <small>Printing & Gifting Products</small>
        </div>

        <div className="px-md-4 text-center" style={{ flex: 1 }}>
          <h4 className="fw-bold m-0">7 Years</h4>
          <small>of Service Excellence</small>
        </div>
      </div>
    </div>
  );
};

export default StatsBanner;
