import { useState, useEffect } from "react";
import normalizeBanner from "../utils/normalizeBanner";

/**
 * Hook to fetch and normalize banners from printmont banner API endpoints.
 * @param {string} apiUrl The API endpoint to fetch banners from.
 * @param {string} [sectionKey] Optional key to look up nested banners (e.g. 'home_hero').
 * @param {string} [basePath] Optional base path prefix for relative image URLs.
 */
export default function useHomeBanners(apiUrl, sectionKey = "", basePath = "") {
  const cacheKey = `home_banner_cache_${apiUrl}_${sectionKey}`;

  const [banners, setBanners] = useState(() => {
    if (apiUrl) {
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
    if (!apiUrl) return false;
    try {
      const cached = sessionStorage.getItem(cacheKey);
      if (cached) return false;
    } catch (e) {}
    return true;
  });

  const [error, setError] = useState(null);

  useEffect(() => {
    if (!apiUrl) {
      setLoading(false);
      return;
    }

    let isMounted = true;
    const fetchBanners = async () => {
      try {
        // If no cache, set loading true
        if (!sessionStorage.getItem(cacheKey)) {
          setLoading(true);
        }
        const response = await fetch(apiUrl);
        if (!response.ok) {
          throw new Error(`HTTP Error! Status: ${response.status}`);
        }
        const data = await response.json();

        if (!isMounted) return;

        let bannerList = [];

        // Check success flag standard format
        if (data && data.success && data.data) {
          const payload = data.data;

          if (sectionKey && payload[sectionKey] && payload[sectionKey].banners) {
            bannerList = payload[sectionKey].banners;
          } else if (sectionKey && Array.isArray(payload[sectionKey])) {
            bannerList = payload[sectionKey];
          } else if (Array.isArray(payload)) {
            bannerList = payload;
          } else {
            // Check fallback for first key if sectionKey is not specified or not found
            const keys = Object.keys(payload);
            if (keys.length > 0) {
              const firstKey = keys[0];
              if (payload[firstKey] && payload[firstKey].banners) {
                bannerList = payload[firstKey].banners;
              } else if (Array.isArray(payload[firstKey])) {
                bannerList = payload[firstKey];
              }
            }
          }
        } else if (Array.isArray(data)) {
          bannerList = data;
        } else if (data && Array.isArray(data.data)) {
          bannerList = data.data;
        }

        const normalized = bannerList.map(item => normalizeBanner(item, basePath)).filter(Boolean);
        if (normalized.length > 0) {
          try {
            sessionStorage.setItem(cacheKey, JSON.stringify(normalized));
          } catch (e) {}
        }
        setBanners(normalized);
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

    fetchBanners();

    return () => {
      isMounted = false;
    };
  }, [apiUrl, sectionKey, basePath]);

  return { banners, loading, error };
}
