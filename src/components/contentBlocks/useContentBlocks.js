import { useState, useEffect } from "react";
import { API_ENDPOINTS } from "../../config/apiEndpoints";

/**
 * Active content blocks for one page, admin-managed from Website Content.
 * Each block carries a `pattern` and the `data` that pattern needs —
 * FooterContentBlocks picks the renderer, this hook just fetches.
 *
 * @param {string} pageKey matches the page_key the admin assigned the block to
 */
export default function useContentBlocks(pageKey) {
  const [blocks, setBlocks] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!pageKey) return undefined;

    let cancelled = false;
    setLoading(true);

    const load = async () => {
      try {
        const res = await fetch(API_ENDPOINTS.CONTENT_BLOCKS(pageKey));
        const json = await res.json();
        if (cancelled) return;
        setBlocks(json?.success && Array.isArray(json.data) ? json.data : []);
      } catch (error) {
        console.error(`Failed to load content blocks for "${pageKey}":`, error);
        if (!cancelled) setBlocks([]);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    load();
    return () => { cancelled = true; };
  }, [pageKey]);

  return { blocks, loading };
}
