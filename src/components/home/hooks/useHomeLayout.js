import { useState, useEffect } from "react";
import { API_ENDPOINTS, BASE_URL } from "../../../config/apiEndpoints";

/**
 * Hook to fetch the home page layout sections.
 * Uses sessionStorage caching for instant rendering on revisits.
 * @param {string} target 'desktop' | 'mobile'
 * @param {string} [baseURL] The base URL of the API.
 */
export default function useHomeLayout(target, baseURL) {
  const cacheKey = `home_layout_cache_${target}`;

  const [sections, setSections] = useState(() => {
    if (target) {
      try {
        const cached = sessionStorage.getItem(cacheKey);
        if (cached) {
          const parsed = JSON.parse(cached);
          if (Array.isArray(parsed) && parsed.length > 0) return parsed;
        }
      } catch (e) {}
    }
    return [];
  });

  const [loading, setLoading] = useState(() => {
    if (!target) return false;
    try {
      const cached = sessionStorage.getItem(cacheKey);
      if (cached) return false;
    } catch (e) {}
    return true;
  });

  const [error, setError] = useState(null);

  useEffect(() => {
    if (!target) {
      setLoading(false);
      return;
    }

    let isMounted = true;
    const fetchLayout = async () => {
      try {
        // Only show loading if no cache exists
        if (!sessionStorage.getItem(cacheKey)) {
          setLoading(true);
        }
        const endpoint = API_ENDPOINTS.HOME_LAYOUT 
          ? API_ENDPOINTS.HOME_LAYOUT(target)
          : `${BASE_URL}/home-layout/home-layout.php?target=${target}`;

        const response = await fetch(
          `${endpoint}&_t=${Date.now()}`,
          { cache: "no-store" }
        );
        if (!response.ok) {
          throw new Error(`HTTP Error! Status: ${response.status}`);
        }
        const data = await response.json();

        if (!isMounted) return;

        if (data && data.success && Array.isArray(data.data)) {
          // Cache the layout sections
          try {
            sessionStorage.setItem(cacheKey, JSON.stringify(data.data));
          } catch (e) {}
          setSections(data.data);
        } else {
          throw new Error(data.error || "Failed to load layout configurations.");
        }
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

    fetchLayout();

    return () => {
      isMounted = false;
    };
  }, [target, baseURL]);

  return { sections, loading, error };
}
