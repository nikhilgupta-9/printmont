import React from 'react';
import { FaTrash, FaStar, FaRegHeart } from 'react-icons/fa';
import { BsCart4 } from 'react-icons/bs';
import { Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useWishlist } from '../../context/WishlistContext';
import { useCheckout } from '../../context/CheckoutContext';
import { resolveImageUrl } from '../../config/apiEndpoints';
import './Wishlist.css';

const PLACEHOLDER = 'https://placehold.co/200x200/f5f5f5/888888?text=Printmont';

const itemImage = (item) => {
  const src = item.image || item.image_url
    || (Array.isArray(item.images) && item.images.length > 0
      ? (typeof item.images[0] === 'string' ? item.images[0] : item.images[0].image_url)
      : null)
    || item.primary_image || item.thumbnail;
  return src ? resolveImageUrl(src) : PLACEHOLDER;
};

// Wishlist in Flipkart style: one row per product (image, title, rating, price, remove)
const Wishlist = () => {
  const { wishlist, removeFromWishlist, loading } = useWishlist();
  const { addToCart } = useCheckout();

  const handleAddToCart = async (item) => {
    if (!addToCart) return;
    const productId = item.product_id || item.id;
    const price = Number(item.discountedPrice || item.price || item.regular_price || 0);
    const mrp = Number(item.originalPrice || item.regular_price || price || 0);
    const discount = item.discount || (mrp > price && price > 0 ? Math.round(((mrp - price) / mrp) * 100) : 0);

    const added = addToCart({
      ...item,
      id: productId,
      productId: productId,
      name: item.name || item.title || 'Product',
      title: item.name || item.title || 'Product',
      price: price,
      originalPrice: mrp,
      discount: discount,
      image: itemImage(item),
      slug: item.slug
    });

    if (added === false) return; // guest: sent to login

    if (removeFromWishlist && productId) {
      await removeFromWishlist(productId, { silent: true });
    }

    toast.success('Item moved to Cart!');
  };

  return (
    <div className="wl">
      <div className="wl__head">
        My Wishlist <span className="wl__count">({wishlist.length})</span>
      </div>

      {loading && wishlist.length === 0 ? (
        <div className="wl__loading">Loading your wishlist…</div>
      ) : wishlist.length === 0 ? (
        <div className="wl-empty">
          <span className="wl-empty__icon"><FaRegHeart size={40} /></span>
          <h2 className="wl-empty__title">Empty Wishlist</h2>
          <p className="wl-empty__text">You have no items in your wishlist. Start adding!</p>
          <Link to="/allproducts" className="wl-empty__btn">Continue Shopping</Link>
        </div>
      ) : (
        <ul className="wl__list">
          {wishlist.map((item, index) => {
            const id = item.id || item.product_id;
            const title = item.name || item.title || 'Product';
            const price = Number(item.discountedPrice || item.price || item.regular_price || 0);
            const mrp = Number(item.originalPrice || item.regular_price || 0);
            const off = mrp > price && price > 0 ? Math.round(((mrp - price) / mrp) * 100) : 0;
            const rating = Number(item.rating) || 0;
            const ratingCount = Number(item.rating_count) || 0;
            const available = item.in_stock !== false && item.inStock !== false && item.out_of_stock_status !== 'out_of_stock';
            const url = `/product/${item.slug || id}`;

            return (
              <li key={id || index} className={`wl-item ${available ? '' : 'is-unavailable'}`}>
                <Link to={url} className="wl-item__img">
                  <img
                    src={itemImage(item)}
                    alt={title}
                    loading="lazy"
                    onError={(e) => { e.currentTarget.src = PLACEHOLDER; }}
                  />
                </Link>

                <div className="wl-item__body">
                  <Link to={url} className="wl-item__title">{title}</Link>

                  {(ratingCount > 0 || rating > 0) && (
                    <div className="wl-item__rating">
                      <span className="wl-item__badge">{rating.toFixed(1)} <FaStar size={9} /></span>
                      <span className="wl-item__reviews">({ratingCount.toLocaleString('en-IN')})</span>
                      <img src="/Asured.png" alt="Printmont Assured" className="wl-item__assured" onError={(e) => { e.currentTarget.style.display = 'none'; }} />
                    </div>
                  )}

                  {available ? (
                    <div className="wl-item__price">
                      <span className="wl-item__sp">₹{price.toLocaleString('en-IN')}</span>
                      {off > 0 && (
                        <>
                          <span className="wl-item__mrp">₹{mrp.toLocaleString('en-IN')}</span>
                          <span className="wl-item__off">{off}% off</span>
                        </>
                      )}
                    </div>
                  ) : (
                    <div className="wl-item__na">Currently unavailable</div>
                  )}

                  {available && (
                    <button type="button" className="wl-item__cart" onClick={() => handleAddToCart(item)}>
                      <BsCart4 size={14} /> Add to Cart
                    </button>
                  )}
                </div>

                <button
                  type="button"
                  className="wl-item__remove"
                  onClick={() => removeFromWishlist(id)}
                  title="Remove from wishlist"
                  aria-label={`Remove ${title} from wishlist`}
                >
                  <FaTrash size={14} />
                </button>
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
};

export default Wishlist;
