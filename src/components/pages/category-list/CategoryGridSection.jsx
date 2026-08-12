import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { categoriesData as staticCategoriesData } from "../../../../data/categoriesdata";
import { API_ENDPOINTS, resolveImageUrl } from "../../../config/apiEndpoints";
import "./CategoryGrid.css";

const CategoryGridSection = ({ categorySlug }) => {
  // Falls back to the static list until the live fetch resolves (or if it fails),
  // so the section is never empty.
  const [categoriesData, setCategoriesData] = useState(staticCategoriesData);

  useEffect(() => {
    const fetchSubcategories = async () => {
      try {
        // With a slug (e.g. /category/eco-friendly-gifts), scope the grid to
        // that category's own children — resolve slug -> id first, since the
        // subcategories endpoint filters by parent_id, not slug. Without one
        // (generic /category page), fall back to the flat sitewide list.
        let parentId = null;
        if (categorySlug) {
          const catRes = await fetch(API_ENDPOINTS.CATEGORY_BY_SLUG(categorySlug));
          const catData = await catRes.json();
          if (catData?.success && catData.data?.id) {
            parentId = catData.data.id;
          }
        }
        console.log(parentId);

        const response = await fetch(API_ENDPOINTS.SUBCATEGORIES(parentId));
        const data = await response.json();
        if (!data || !data.success || !Array.isArray(data.data)) throw new Error('Invalid subcategories response');

        const normalized = data.data.map((cat) => ({
          id: cat.id,
          name: cat.name,
          url: `/category/${cat.slug}`,
          img: resolveImageUrl(cat.images?.image || cat.images?.desktop || '')
        }));

        if (normalized.length > 0) setCategoriesData(normalized);
      } catch (error) {
        console.error("Error fetching subcategories, using static fallback:", error);
      }
    };
    fetchSubcategories();
  }, [categorySlug]);

  return (
    <div className="container-fluid category-grid-section py-4 bg-white">
      <div className="row justify-content-center g-4 mx-auto" style={{ maxWidth: '1440px' }}>
        {categoriesData.map((cat, idx) => (
          <div key={idx} className="col-4 col-md-3 col-lg-2 d-flex flex-column align-items-center text-center">
            <Link to={cat.url || "#"} className="text-decoration-none text-dark w-100 d-flex flex-column align-items-center">
              <div className="cg-image-wrapper mb-2">
                {/* The pill shape background */}
                <div className="cg-bg-shape"></div>
                {/* The category image */}
                <img 
                  src={cat.img && cat.img.startsWith('./') ? cat.img.substring(1) : cat.img} 
                  alt={cat.name} 
                  className="cg-image" 
                />
              </div>
              <span className="fw-medium cg-title text-truncate w-100 px-1">{cat.name}</span>
            </Link>
          </div>
        ))}
      </div>
    </div>
  );
};

export default CategoryGridSection;
