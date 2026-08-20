import { useState, useEffect } from "react";
import { API_ENDPOINTS } from "../../config/apiEndpoints";

/**
 * Sections for a CMS-driven marketing page (affiliate, business-solutions).
 *
 * The API already groups rows by section_type, so callers get `sections`
 * keyed by type plus helpers rather than regrouping each time.
 *
 * @param {string} pageKey the page_key column value
 */
export default function usePageSections(pageKey) {
  const [sections, setSections] = useState({});
  const [loading, setLoading] = useState(true);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    if (!pageKey) return undefined;

    let cancelled = false;
    setLoading(true);
    setFailed(false);

    const load = async () => {
      try {
        const res = await fetch(API_ENDPOINTS.PAGE_SECTIONS(pageKey));
        const json = await res.json();
        if (cancelled) return;

        if (!json?.success) {
          setFailed(true);
          setSections({});
          return;
        }
        setSections(json.sections || {});
      } catch (error) {
        console.error(`Failed to load "${pageKey}" page sections:`, error);
        if (!cancelled) {
          setFailed(true);
          setSections({});
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    load();
    return () => { cancelled = true; };
  }, [pageKey]);

  /** Every row of a type, in display order. */
  const many = (type) => sections[type] || [];

  /** First row of a type, for single-instance blocks like the hero. */
  const one = (type) => (sections[type] || [])[0] || null;

  return { sections, many, one, loading, failed };
}
