import React, { useState } from 'react';
import { FaTrashAlt } from 'react-icons/fa';
import toast, { Toaster } from 'react-hot-toast';
import { CartData } from '../../../data/data';

const Wishlist = () => {
  const [wishlist, setWishlist] = useState(CartData);

  const handleRemove = (indexToRemove) => {
    const updatedWishlist = wishlist.filter((_, index) => index !== indexToRemove);
    setWishlist(updatedWishlist);
    toast.error('Item removed from wishlist!');
  };

  const handleAddToCart = (item) => {
    toast.success('Product added successfully to your cart!');
    // You can later add real cart logic here
  };

  return (
    <>
      {/* Toast container */}
      <Toaster position="bottom-right" />

      <div className="card shadow-sm">
        <div className="card-header bg-white fw-bold">
          My Wishlist ({wishlist.length})
        </div>

        <div className="list-group list-group-flush">
          {wishlist.map((item, index) => (
            <div
              key={index}
              className="list-group-item d-flex align-items-start justify-content-between wish-hov"
            >
              {/* Left: Product Image + Info */}
              <div className="d-flex">
                <div className="text-center me-3">
                  <img
                    src={item.image}
                    alt={item.title}
                    className="img-fluid"
                    style={{ maxWidth: '100px' }}
                  />
                  {item.available ? (
                    <p className="text-success small mb-0">In stock</p>
                  ) : (
                    <p className="text-danger small mb-0">
                      Currently <br /> unavailable
                    </p>
                  )}
                </div>

                <div className="d-flex flex-column justify-content-start align-items-start">
                  <a
                    href={item.link}
                    className="d-block product-name fw-semibold mb-1 text-decoration-none"
                  >
                    {item.title}
                  </a>
                  <div className="d-flex align-items-center gap-2">
                    <h5 className="mb-0 fs-4 text-dark">₹{item.price}</h5>
                    {item.oldPrice && (
                      <span className="text-muted text-decoration-line-through small">
                        ₹{item.oldPrice}
                      </span>
                    )}
                    {item.discount && (
                      <span className="text-success fw-bold txsm">{item.discount}</span>
                    )}
                  </div>
                </div>
              </div>

              {/* Right: Buttons */}
              <div className="d-flex align-items-center gap-2">
                <button
                  className="btn btn-sm btn-primary " onClick={() => handleAddToCart(item)}
                >
                  Add to Cart
                </button>
                <button
                  className="btn btn-sm text-muted"
                  onClick={() => handleRemove(index)}
                >
                  <FaTrashAlt />
                </button>
              </div>
            </div>
          ))}

          {wishlist.length === 0 && (
            <div className="text-center text-muted p-4">Your wishlist is empty.</div>
          )}
        </div>
      </div>
    </>
  );
};

export default Wishlist;
