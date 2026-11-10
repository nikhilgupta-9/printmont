import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { API_ENDPOINTS } from '../../config/apiEndpoints';
import { useCheckout } from '../../context/CheckoutContext';
import CustomizerStudioModal from './CustomizerStudioModal';

// Full-page "Create your design" studio — same engine as the modal (CustomizerStudioModal),
// just rendered as its own route instead of popped over the product page, so it behaves like
// Zazzle's /customizer page: its own URL, back button returns to the product.
export default function DesignStudioPage() {
  const { productSlug } = useParams();
  const navigate = useNavigate();
  const { addToCart } = useCheckout();
  const [productData, setProductData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!productSlug) return;
    setLoading(true);
    fetch(API_ENDPOINTS.PRODUCT_DETAILS(productSlug))
      .then((res) => res.json())
      .then((json) => {
        const p = json?.data || json?.product;
        if (!json?.success || !p) throw new Error(json?.message || 'Product not found');
        setProductData(p);
      })
      .catch(() => toast.error('Could not load this product.'))
      .finally(() => setLoading(false));
  }, [productSlug]);

  const goBackToProduct = () => {
    navigate(productData?.slug ? `/product/${productData.slug}` : -1);
  };

  if (loading) {
    return (
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: '100vh' }}>
        <div className="spinner-border text-primary" role="status" />
      </div>
    );
  }

  if (!productData) return null;

  return (
    <CustomizerStudioModal
      show={true}
      onHide={goBackToProduct}
      productData={productData}
      selectedColor={productData?.color || ''}
      quantity={1}
      addToCart={addToCart}
    />
  );
}
