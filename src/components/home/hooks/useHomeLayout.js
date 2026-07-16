import { useState, useEffect } from "react";

/**
 * Hook to fetch the home page layout sections.
 * @param {string} target 'desktop' | 'mobile'
 * @param {string} baseURL The base URL of the API.
 */
export default function useHomeLayout(target, baseURL) {
  const [sections, setSections] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!target || !baseURL) {
      setLoading(false);
      return;
    }

    let isMounted = true;
    const fetchLayout = async () => {
      try {
        setLoading(true);
        const response = await fetch(
          `${baseURL}api/home-layout-api.php?target=${target}&_t=${Date.now()}`,
          { cache: "no-store" }
        );
        if (!response.ok) {
          throw new Error(`HTTP Error! Status: ${response.status}`);
        }
        const data = await response.json();

        if (!isMounted) return;

        if (data && data.success && Array.isArray(data.data)) {
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
