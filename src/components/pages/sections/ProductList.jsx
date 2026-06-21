import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { getProductUrl } from '../../../utils/seo';

const ProductList = ({ products: initialProducts = [], apiUrl }) => {
  const [products, setProducts] = useState(initialProducts);
  const [loading, setLoading] = useState(!!apiUrl);

  useEffect(() => {
    if (!apiUrl) {
      setProducts(initialProducts);
      setLoading(false);
      return;
    }

    const fetchProducts = async () => {
      try {
        setLoading(true);
        const res = await fetch(apiUrl);
        if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
        const data = await res.json();
        
        const rawProducts = data && data.success && Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
        
        const formattedProducts = rawProducts.map(p => {
          let primaryImg = '/default-img.jpg';
          if (Array.isArray(p.images) && p.images.length > 0) {
            const primary = p.images.find(img => img.is_primary) || p.images[0];
            primaryImg = primary.image_url;
          } else if (p.primary_image) {
            primaryImg = p.primary_image;
          } else if (p.thumbnail) {
            primaryImg = p.thumbnail;
          } else if (p.img) {
            primaryImg = p.img;
          }
          
          const price = parseFloat(p.price) || 0;
          const discPrice = parseFloat(p.discount_price) || 0;
          let discountText = p.discount || '';
          if (discPrice > 0) {
            const maxVal = Math.max(price, discPrice);
            const minVal = Math.min(price, discPrice);
            if (maxVal > minVal) {
              discountText = `${Math.round(((maxVal - minVal) / maxVal) * 100)}% Off`;
            }
          }

          return {
            id: p.id,
            title: p.name || p.title || '',
            img: primaryImg,
            discount: discountText
          };
        });

        setProducts(formattedProducts.slice(0, 6)); // Display 6 items max on mobile row/grid
      } catch (err) {
        console.error("Error fetching ProductList products:", err);
      } finally {
        setLoading(false);
      }
    };
    fetchProducts();
  }, [apiUrl, initialProducts]);

  if (loading) {
    return (
      <div className="bg-white container-fluid p-0 m-0">
        <div className="row g-1 m-0 p-0 px-1">
          {[1, 2, 3, 4].map((idx) => (
            <div className="col-6 col-md-3 col-lg-2" key={idx}>
              <div className="border rounded p-3 text-center">
                <div className="shimmer-bg skeleton-img w-100 mb-2" style={{ height: '120px' }} />
                <div className="shimmer-bg skeleton-title w-75 mx-auto" />
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white container-fluid p-0 m-0">
      <div className="row g-1 m-0 p-0 px-1">
        {products.map((product, index) => (
          <Link
            to={getProductUrl(product)}
            className="col-6 col-md-3 col-lg-2 text-decoration-none text-dark"
            key={index}
          >
            <div className="border rounded d-flex flex-column justify-content-between align-items-center p-2 h-100 bg-white">
              <div className="w-100 d-flex justify-content-center align-items-center mb-2" style={{ height: '120px', overflow: 'hidden' }}>
                <img
                  src={product.img}
                  className="img-fluid zoom-hover"
                  alt={product.title}
                  style={{ objectFit: 'contain', maxHeight: '100%', maxWidth: '100%' }}
                />
              </div>
              <div className="text-center w-100">
                <p className="fs-6 m-0 p-0 text-truncate">{product.title}</p>
                <p className="text-success txsm m-0 p-0 fw-bold">{product.discount}</p>
              </div>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
};

export default ProductList;
