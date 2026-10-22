import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import toast from "react-hot-toast";
import { API_ENDPOINTS, resolveImageUrl } from "../../config/apiEndpoints";
import { useCheckout } from "../../context/CheckoutContext";

// "Frequently bought together": the addon products picked in admin (product.php → section 8).
// Renders nothing when the product has no addons.
const ProductAddons = ({ addonIds = [] }) => {
  const { addToCart } = useCheckout() || {};
  const [items, setItems] = useState([]);
  const idsKey = (addonIds || []).join(",");

  useEffect(() => {
    const ids = (addonIds || []).map(Number).filter(Boolean);
    if (!ids.length) {
      setItems([]);
      return;
    }
    let cancelled = false;
    Promise.all(
      ids.map((id) =>
        fetch(API_ENDPOINTS.PRODUCT_DETAILS(id))
          .then((r) => r.json())
          .then((res) => (res?.success ? res.data || res.product : null))
          .catch(() => null)
      )
    ).then((list) => {
      if (!cancelled) setItems(list.filter((p) => p && p.status !== "inactive"));
    });
    return () => { cancelled = true; };
    // idsKey keeps the effect stable when the parent passes a new array with the same ids
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [idsKey]);

  if (!items.length) return null;

  const handleAdd = (p) => {
    if (!addToCart) return;
    const image = resolveImageUrl(p.primary_image || p.thumbnail);
    const added = addToCart(
      { id: p.id, title: p.name, perPiecePrice: p.selling_price, originalPrice: p.mrp, images: [image], brand: p.brand },
      Math.max(1, Number(p.min_quantity) || 1)
    );
    if (added === false) return; // guest: sent to login
    toast.success(`${p.name} added to cart`);
  };

  return (
    <div className="pdd-block">
      <h2 className="pdd-block__title">Frequently Bought Together</h2>
      <div className="pdd-addons">
      {items.map((p) => {
        const url = p.slug ? `/product/${p.slug}-p${p.id}` : `/product/${p.id}`;
        return (
          <div key={p.id} className="pdd-addon">
            <Link to={url} className="pdd-addon__img">
              <img src={resolveImageUrl(p.primary_image || p.thumbnail)} alt={p.name} loading="lazy" />
            </Link>
            <div className="pdd-addon__body">
              <Link to={url} className="pdd-addon__name" title={p.name}>{p.name}</Link>
              <div className="pdd-addon__price">
                <strong>₹{Number(p.selling_price).toLocaleString("en-IN")}</strong>
                {Number(p.mrp) > Number(p.selling_price) && (
                  <>
                    <span className="pdd-addon__mrp">₹{Number(p.mrp).toLocaleString("en-IN")}</span>
                    <span className="pdd-addon__off">{p.discount_percent}% off</span>
                  </>
                )}
              </div>
            </div>
            <button type="button" className="pdd-outline-btn" onClick={() => handleAdd(p)}>Add</button>
          </div>
        );
      })}
      </div>
    </div>
  );
};

export default ProductAddons;
