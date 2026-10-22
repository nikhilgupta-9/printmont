import { useState, useEffect } from "react";
import normalizeBanner from "../utils/normalizeBanner";

/**
 * Hook to fetch and normalize banners from printmont banner API endpoints.
 * @param {string} apiUrl The API endpoint to fetch banners from.
 * @param {string} [sectionKey] Optional key to look up nested banners (e.g. 'home_hero').
 * @param {string} [basePath] Optional base path prefix for relative image URLs.
 */
export default function useHomeBanners(apiUrl, sectionKey = "", basePath = "") {
  const [banners, setBanners] = useState([]);
  const [loading, setLoading] = useState(!!apiUrl);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!apiUrl) {
      setLoading(false);
      return;
    }

    let isMounted = true;
    const fetchBanners = async (isInitial = true) => {
      try {
        if (isInitial) setLoading(true);
        const sep = apiUrl.includes("?") ? "&" : "?";
        const response = await fetch(`${apiUrl}${sep}_t=${Date.now()}`, {
          cache: "no-store"
        });
        if (!response.ok) {
          throw new Error(`HTTP Error! Status: ${response.status}`);
        }
        const data = await response.json();

        if (!isMounted) return;

        let bannerList = [];

        // Check success flag standard format
        if (data && data.success && data.data) {
          const payload = data.data;

          if (Array.isArray(payload.banners)) {
            bannerList = payload.banners;
          } else {
            const sections =
              payload.sections && typeof payload.sections === "object"
                ? payload.sections
                : payload;

            if (sectionKey && sections[sectionKey] && sections[sectionKey].banners) {
              bannerList = sections[sectionKey].banners;
            } else if (sectionKey && Array.isArray(sections[sectionKey])) {
              bannerList = sections[sectionKey];
            } else if (Array.isArray(sections)) {
              bannerList = sections;
            } else if (!sectionKey) {
              const firstWithBanners = Object.values(sections).find(
                (s) => s && (Array.isArray(s.banners) || Array.isArray(s))
              );
              if (firstWithBanners) {
                bannerList = Array.isArray(firstWithBanners)
                  ? firstWithBanners
                  : firstWithBanners.banners;
              }
            }
          }
        } else if (Array.isArray(data)) {
          bannerList = data;
        } else if (data && Array.isArray(data.data)) {
          bannerList = data.data;
        }

        const normalized = bannerList.map(item => normalizeBanner(item, basePath)).filter(Boolean);
        setBanners(normalized);
        setError(null);
      } catch (err) {
        if (isMounted && isInitial) {
          setError(err.message);
        }
      } finally {
        if (isMounted && isInitial) {
          setLoading(false);
        }
      }
    };

    fetchBanners(true);

    const handleFocus = () => {
      fetchBanners(false);
    };

    const handleVisibilityChange = () => {
      if (typeof document !== "undefined" && document.visibilityState === "visible") {
        fetchBanners(false);
      }
    };

    window.addEventListener("focus", handleFocus);
    document.addEventListener("visibilitychange", handleVisibilityChange);

    const intervalId = setInterval(() => {
      if (typeof document !== "undefined" && document.visibilityState === "visible") {
        fetchBanners(false);
      }
    }, 5000);

    return () => {
      isMounted = false;
      clearInterval(intervalId);
      window.removeEventListener("focus", handleFocus);
      document.removeEventListener("visibilitychange", handleVisibilityChange);
    };
  }, [apiUrl, sectionKey, basePath]);

  return { banners, loading, error };
}
