import { useState, useEffect } from "react";
import normalizeProduct from "../utils/normalizeProduct";

/**
 * Hook to fetch and normalize products from printmont product API endpoints.
 * @param {string} [apiUrl] Optional API endpoint to fetch products from.
 * @param {Array} [initialProducts] Initial products list to fallback to if no API url is provided or during load.
 * @param {number} [limit] Optional limit to crop the returned products list.
 */
export default function useHomeProducts(apiUrl, initialProducts = [], limit) {
  const serializedInitial = JSON.stringify(initialProducts || []);
  
  const [products, setProducts] = useState(() => {
    try {
      const parsed = JSON.parse(serializedInitial);
      return parsed.map(normalizeProduct).filter(Boolean);
    } catch {
      return [];
    }
  });
  const [loading, setLoading] = useState(!!apiUrl);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!apiUrl) {
      try {
        const parsed = JSON.parse(serializedInitial);
        setProducts(parsed.map(normalizeProduct).filter(Boolean));
      } catch {
        setProducts([]);
      }
      setLoading(false);
      return;
    }

    let isMounted = true;
    const fetchProducts = async () => {
      try {
        setLoading(true);
        const response = await fetch(apiUrl);
        if (!response.ok) {
          throw new Error(`HTTP Error! Status: ${response.status}`);
        }
        const data = await response.json();

        if (!isMounted) return;

        const rawProducts = data && data.success && Array.isArray(data.data) 
          ? data.data 
          : (Array.isArray(data) ? data : []);

        let normalized = rawProducts.map(normalizeProduct).filter(Boolean);
        
        if (typeof limit === "number" && limit > 0) {
          normalized = normalized.slice(0, limit);
        }

        setProducts(normalized);
        setError(null);
      } catch (err) {
        if (isMounted) {
          setError(err.message);
        }
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    };

    fetchProducts();

    return () => {
      isMounted = false;
    };
  }, [apiUrl, serializedInitial, limit]);

  return { products, loading, error };
}
