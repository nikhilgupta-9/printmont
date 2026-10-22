import { useState, useEffect, useCallback } from "react";
import { API_ENDPOINTS } from "../../config/apiEndpoints";
import { updatePageSEO } from "../../utils/seo";

export default function useLandingPage(slug = "business-solutions", device = "both") {
  const [data, setData] = useState({ page: null, sections: [], faqs: [] });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchData = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const url = API_ENDPOINTS.LANDING_PAGE 
        ? API_ENDPOINTS.LANDING_PAGE(slug, device)
        : `/api/landing-page-api.php?slug=${encodeURIComponent(slug)}&device=${device}`;
      
      const res = await fetch(url);
      const json = await res.json();

      if (json.success) {
        const payload = json.data || json;
        setData({
          page: payload.page || null,
          sections: payload.sections || [],
          faqs: payload.faqs || []
        });

        // Update Page SEO Metadata
        const pageInfo = payload.page || {};
        updatePageSEO({
          title: pageInfo.meta_title || `${pageInfo.page_name || "Business Solutions"} | Printmont`,
          description: pageInfo.meta_description || "Custom printing, corporate merchandise, and enterprise supply solutions by Printmont.",
          keywords: pageInfo.meta_keywords || "business solutions, corporate printing, bulk corporate gifts, custom merchandise India",
          type: "website"
        });
      } else {
        setError(json.message || "Failed to load landing page data");
      }
    } catch (err) {
      console.error("Error fetching landing page:", err);
      setError(err.message || "Network error loading page");
    } finally {
      setLoading(false);
    }
  }, [slug, device]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  return {
    page: data.page,
    sections: data.sections || [],
    faqs: data.faqs || [],
    loading,
    error,
    refetch: fetchData
  };
}
