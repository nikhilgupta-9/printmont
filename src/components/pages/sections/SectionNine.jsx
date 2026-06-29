import React from "react";
import "bootstrap/dist/css/bootstrap.min.css";
import { Link } from "react-router-dom";
import { getProductUrl } from "../../../utils/seo";
import { sampleProducts, topImages } from "../../../../data/data";

const SectionNine = () => {
  return (
    <div className="container mx-0 px-0 py-3 section-nine-bg">
      {/* Top 3 images */}
      <div className="row mb-3 mx-0 px-1">
        {topImages.map((image) => (
          <div className="col-4 mt-3 p-0 p-1" key={image.id}>
            <img
              src={image.src}
              alt={image.alt}
              className="rounded"
              style={{ objectFit: "cover", width: "100%", height: "100%" }}
            />
          </div>
        ))}
      </div>

      {/* Bottom 3 cards */}
      <div className="row px-2 mx-0">
        {sampleProducts.map((product) => (
          <div className="col-4 m-0 p-0 " key={product.id}>
            <Link to={getProductUrl(product)} className="text-decoration-none text-dark">
              <div className="card h-100 bd mx-1 mt-1 mb-0 zoom-hover">
                <img
                  src={product.img}
                  className="card-img-top object-fit-contain section-nine-card"
                  alt={product.title}
                  style={{ height: "120px" }}
                />
                <div className="card-body text-center bg-danger-emphasis m-0 py-2 w-100 yellow-bg">
                  <p className="card-title m-0 p-0 txsm">{product.title}</p>
                  <p className="card-text p-0 m-0 txsm fw-bold">{product.discount}</p>
                </div>
              </div>
            </Link>
          </div>
        ))}
      </div>
    </div>
  );
};

export default SectionNine;
