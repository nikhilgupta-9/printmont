import React from 'react';
import { FaTrashAlt, FaShoppingBag } from 'react-icons/fa';
import { Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useWishlist } from '../../context/WishlistContext';
import { useCheckout } from '../../context/CheckoutContext';

const Wishlist = () => {
  const { wishlist, removeFromWishlist, loading } = useWishlist();
  const { addToCart } = useCheckout();

  const handleAddToCart = (item) => {
    if (addToCart) {
      addToCart(item);
      toast.success('Product added successfully to your cart!');
    }
  };

  const getItemImage = (item) => {
    if (item.image) return item.image;
    if (Array.isArray(item.images) && item.images.length > 0) {
      return typeof item.images[0] === 'string' ? item.images[0] : item.images[0].image_url;
    }
    if (item.primary_image) return item.primary_image;
    if (item.thumbnail) return item.thumbnail;
    return 'https://placehold.co/100x100/f5f5f5/888888?text=Printmont';
  };

  const getItemPrice = (item) => {
    return item.discountedPrice || item.price || item.regular_price || 0;
  };

  const getItemOriginalPrice = (item) => {
    return item.originalPrice || item.regular_price || null;
  };

  return (
    <div className="card shadow-sm border-0 rounded-3">
      <div className="card-header bg-white fw-bold py-3 fs-5 border-bottom d-flex justify-content-between align-items-center">
        <span>My Wishlist ({wishlist.length})</span>
      </div>

      <div className="list-group list-group-flush">
        {loading ? (
          <div className="text-center text-muted p-5">Loading your wishlist...</div>
        ) : wishlist.length === 0 ? (
          <div className="text-center py-5 px-3">
            <div className="mb-3">
              <FaShoppingBag size={48} className="text-muted opacity-50" />
            </div>
            <h5 className="fw-bold text-dark">Your wishlist is empty!</h5>
            <p className="text-muted small mb-4">Explore items and save your favorites here.</p>
            <Link to="/allproducts" className="btn btn-primary px-4 py-2 fw-semibold">
              Continue Shopping
            </Link>
          </div>
        ) : (
          wishlist.map((item, index) => {
            const id = item.id || item.product_id;
            const title = item.name || item.title || 'Product';
            const price = getItemPrice(item);
            const oldPrice = getItemOriginalPrice(item);
            const image = getItemImage(item);
            const isAvailable = item.inStock !== false && item.out_of_stock_status !== 'out_of_stock';

            return (
              <div
                key={id || index}
                className="list-group-item d-flex align-items-center justify-content-between p-3 wish-hov border-bottom"
              >
                {/* Left: Product Image + Info */}
                <div className="d-flex align-items-center">
                  <div className="text-center me-3" style={{ minWidth: '90px' }}>
                    <img
                      src={image}
                      alt={title}
                      className="img-fluid rounded border"
                      style={{ width: '90px', height: '90px', objectFit: 'cover' }}
                      onError={(e) => { e.target.src = 'https://placehold.co/100x100/f5f5f5/888888?text=Printmont'; }}
                    />
                  </div>

                  <div className="d-flex flex-column justify-content-start align-items-start">
                    <Link
                      to={`/product/${item.slug || id}`}
                      className="d-block product-name fw-semibold mb-1 text-decoration-none text-dark fs-6"
                    >
                      {title}
                    </Link>
                    
                    <div className="d-flex align-items-center gap-2 mb-2">
                      <h5 className="mb-0 fs-5 fw-bold text-dark">₹{price}</h5>
                      {oldPrice && oldPrice > price && (
                        <span className="text-muted text-decoration-line-through small">
                          ₹{oldPrice}
                        </span>
                      )}
                    </div>

                    {isAvailable ? (
                      <span className="badge bg-success-subtle text-success small mb-2">In stock</span>
                    ) : (
                      <span className="badge bg-danger-subtle text-danger small mb-2">Out of stock</span>
                    )}

                    <button
                      className="btn btn-sm btn-primary d-flex d-lg-none mt-1"
                      onClick={() => handleAddToCart(item)}
                      disabled={!isAvailable}
                    >
                      Add to Cart
                    </button>
                  </div>
                </div>

                {/* Right: Actions */}
                <div className="d-flex align-items-center gap-2">
                  <button
                    className="btn btn-sm btn-primary d-none d-lg-flex px-3 py-2 fw-semibold"
                    onClick={() => handleAddToCart(item)}
                    disabled={!isAvailable}
                  >
                    Add to Cart
                  </button>
                  <button
                    className="btn btn-outline-danger btn-sm p-2"
                    onClick={() => removeFromWishlist(id)}
                    title="Remove from Wishlist"
                  >
                    <FaTrashAlt />
                  </button>
                </div>
              </div>
            );
          })
        )}
      </div>
    </div>
  );
};

export default Wishlist;
