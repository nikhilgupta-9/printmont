import React from 'react';

const ProductList = ({ products = [] }) => {
  return (
    <div className="bg-white container-fluid p-0 m-0">
      <div className="row g-1 m-0 p-0 px-1">
        {products.map((product, index) => (
          <div className="col-6 col-md-3 col-lg-2 p-" key={index}>
            <div className="border rounded d-flex flex-column justify-content-center align-items-center p-0 h-100">
              <img
                src={product.img}
                className="img-fluid mb-2"
                alt={product.title}
                style={{ objectFit: 'contain', maxHeight: '150px' }}
              />
              <p className="fs-6 m-0 p-0">{product.title}</p>
              <p className="text-success txsm m-0 p-0">{product.discount}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default ProductList;
