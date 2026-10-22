import { useState, useEffect, useRef, useCallback } from "react";
import { API_ENDPOINTS, BASE_URL, BACKEND_URL } from "../../config/apiEndpoints";

/**
 * Hook to fetch the product detail page layout sections.
 * Automatically synchronizes with backend updates in real-time via:
 * 1. Window focus & tab visibility change (instant sync when switching back from Admin tab)
 * 2. Background polling interval (auto-syncs changes without page reload)
 *
 * @param {string} target 'mobile' | 'desktop'
 * @param {number} [pollingIntervalMs=4000] Polling interval in ms for auto-refresh (default: 4000ms).
 */
export default function useProductLayout(target = "mobile", pollingIntervalMs = 4000) {
  const [sections, setSections] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const lastFetchedDataRef = useRef("");

  const fetchLayout = useCallback(async (isInitial = false) => {
    if (!target) {
      if (isInitial) setLoading(false);
      return;
    }

    try {
      if (isInitial) {
        setLoading(true);
      }

      const endpoint = API_ENDPOINTS.PRODUCT_LAYOUT 
        ? API_ENDPOINTS.PRODUCT_LAYOUT(target)
        : `${BACKEND_URL}/product-layout-api.php?target=${target}`;

      const sep = endpoint.includes("?") ? "&" : "?";
      const response = await fetch(`${endpoint}${sep}_t=${Date.now()}`);

      if (!response.ok) {
        throw new Error(`HTTP Error! Status: ${response.status}`);
      }

      const data = await response.json();

      if (data && data.success && Array.isArray(data.data)) {
        const serialized = JSON.stringify(data.data);
        if (serialized !== lastFetchedDataRef.current) {
          lastFetchedDataRef.current = serialized;
          setSections(data.data);
        }
        setError(null);
      } else {
        throw new Error(data.error || "Failed to load layout configurations.");
      }
    } catch (err) {
      if (isInitial) {
        setError(err.message);
      }
    } finally {
      if (isInitial) {
        setLoading(false);
      }
    }
  }, [target]);

  useEffect(() => {
    // 1. Initial fetch
    fetchLayout(true);

    // 2. Real-time background polling
    let intervalId = null;
    if (pollingIntervalMs > 0) {
      intervalId = setInterval(() => {
        if (typeof document !== "undefined" && document.visibilityState === "visible") {
          fetchLayout(false);
        }
      }, pollingIntervalMs);
    }

    // 3. Instant refetch when window gets focus (user switches back from Admin panel)
    const handleFocus = () => {
      fetchLayout(false);
    };

    const handleVisibilityChange = () => {
      if (typeof document !== "undefined" && document.visibilityState === "visible") {
        fetchLayout(false);
      }
    };

    window.addEventListener("focus", handleFocus);
    window.addEventListener("visibilitychange", handleVisibilityChange);

    return () => {
      if (intervalId) clearInterval(intervalId);
      window.removeEventListener("focus", handleFocus);
      window.removeEventListener("visibilitychange", handleVisibilityChange);
    };
  }, [fetchLayout, pollingIntervalMs]);

  return { sections, loading, error, refetch: () => fetchLayout(false) };
}
