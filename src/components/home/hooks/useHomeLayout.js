import { useState, useEffect, useRef, useCallback } from "react";
import { API_ENDPOINTS, BASE_URL } from "../../../config/apiEndpoints";

/**
 * Hook to fetch the home page layout sections.
 * Automatically synchronizes with backend updates in real-time via:
 * 1. Window focus & tab visibility change (instant sync when switching back from Admin tab)
 * 2. Background polling interval (auto-syncs changes without page reload)
 *
 * @param {string} target 'desktop' | 'mobile'
 * @param {string} [baseURL] The base URL of the API.
 * @param {number} [pollingIntervalMs=5000] Polling interval in ms for auto-refresh (default: 5000ms).
 */
export default function useHomeLayout(target, baseURL, pollingIntervalMs = 5000) {
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

      const endpoint = API_ENDPOINTS.HOME_LAYOUT 
        ? API_ENDPOINTS.HOME_LAYOUT(target)
        : `${BASE_URL}/home-layout-api.php?target=${target}`;

      const response = await fetch(
        `${endpoint}&_t=${Date.now()}`,
        { cache: "no-store" }
      );

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
  }, [target, baseURL]);

  useEffect(() => {
    // 1. Initial fetch
    fetchLayout(true);

    // 2. Real-time background polling
    let intervalId = null;
    if (pollingIntervalMs > 0) {
      intervalId = setInterval(() => {
        if (document.visibilityState === "visible") {
          fetchLayout(false);
        }
      }, pollingIntervalMs);
    }

    // 3. Instant refetch when window gets focus (user switches back from Admin panel)
    const handleFocus = () => {
      fetchLayout(false);
    };

    const handleVisibilityChange = () => {
      if (document.visibilityState === "visible") {
        fetchLayout(false);
      }
    };

    window.addEventListener("focus", handleFocus);
    document.addEventListener("visibilitychange", handleVisibilityChange);

    return () => {
      if (intervalId) clearInterval(intervalId);
      window.removeEventListener("focus", handleFocus);
      document.removeEventListener("visibilitychange", handleVisibilityChange);
    };
  }, [fetchLayout, pollingIntervalMs]);

  return { sections, loading, error, refetch: () => fetchLayout(false) };
}


