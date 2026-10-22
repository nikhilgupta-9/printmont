import { useState, useEffect, useMemo, useCallback } from "react";
import { useSearchParams, useNavigate } from "react-router-dom";
import { API_ENDPOINTS } from "../../config/apiEndpoints";

const DEFAULT_LIMIT = 40;

export default function useProductFilters(categoryScope = null) {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const scopeId = categoryScope?.id != null ? String(categoryScope.id) : "";
  const scopeName = categoryScope?.name || "";

  const [rawProducts, setRawProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // URL state sync
  const currentPage = parseInt(searchParams.get("page") || "1", 10);
  const currentSort = searchParams.get("sort") || "Relevance";
  const searchQuery = searchParams.get("q") || searchParams.get("search") || searchParams.get("query") || "";

  // Collection / Section Flags from URL
  const collectionFilter = (searchParams.get("filter") || searchParams.get("collection") || searchParams.get("action") || "").toLowerCase().trim();
  const isBestsellerFilter = searchParams.get("bestseller") === "1" || searchParams.get("bestseller") === "true" || collectionFilter === "bestseller" || collectionFilter === "bestsellers" || collectionFilter === "our_bestseller" || collectionFilter === "our_bestsellers";
  const isTopRatedFilter = searchParams.get("top_rated") === "1" || searchParams.get("top_rated") === "true" || collectionFilter === "top_rated" || collectionFilter === "toprated";
  const isTopDealFilter = searchParams.get("top_deal") === "1" || searchParams.get("top_deal") === "true" || collectionFilter === "top_deal" || collectionFilter === "deals" || collectionFilter === "top_deals";
  const isFeaturedFilter = searchParams.get("featured") === "1" || searchParams.get("featured") === "true" || collectionFilter === "featured";
  const isDiscountFilter = searchParams.get("discount") === "1" || collectionFilter === "discount" || collectionFilter === "special_offer" || collectionFilter === "special_offers" || collectionFilter === "flash_sale";

  // Filter selections from URL or state
  const selectedCategory = scopeName || searchParams.get("category") || "";
  const selectedSubCategory = searchParams.get("sub_category") || "";
  const selectedSubSubCategory = searchParams.get("sub_sub_category") || "";

  const selectedBrands = useMemo(() => (searchParams.get("brand") ? searchParams.get("brand").split(",") : []), [searchParams]);
  const selectedSizes = useMemo(() => (searchParams.get("size") ? searchParams.get("size").split(",") : []), [searchParams]);
  const selectedColors = useMemo(() => (searchParams.get("color") ? searchParams.get("color").split(",") : []), [searchParams]);
  const selectedFabrics = useMemo(() => (searchParams.get("fabric") ? searchParams.get("fabric").split(",") : (searchParams.get("material") ? searchParams.get("material").split(",") : [])), [searchParams]);
  const selectedTypes = useMemo(() => (searchParams.get("type") ? searchParams.get("type").split(",") : []), [searchParams]);
  const selectedPatterns = useMemo(() => (searchParams.get("pattern") ? searchParams.get("pattern").split(",") : (searchParams.get("shape") ? searchParams.get("shape").split(",") : [])), [searchParams]);
  const selectedGenders = useMemo(() => (searchParams.get("gender") ? searchParams.get("gender").split(",") : []), [searchParams]);
  const selectedFits = useMemo(() => (searchParams.get("fit") ? searchParams.get("fit").split(",") : []), [searchParams]);
  const selectedOccasions = useMemo(() => (searchParams.get("occasion") ? searchParams.get("occasion").split(",") : []), [searchParams]);

  const minPriceParam = searchParams.get("minPrice");
  const maxPriceParam = searchParams.get("maxPrice");
  const minDiscountParam = searchParams.get("minDiscount");
  const excludeOutOfStock = searchParams.get("inStock") === "true";
  const assuredOnly = searchParams.get("assured") === "true";
  const minRatingParam = searchParams.get("minRating");
  const minRating = minRatingParam ? parseFloat(minRatingParam) : 0;

  // Fetch initial product dataset
  useEffect(() => {
    let isMounted = true;
    const fetchProducts = async () => {
      try {
        setLoading(true);
        const res = await fetch(API_ENDPOINTS.PRODUCTS);
        if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
        const data = await res.json();

        if (!isMounted) return;

        const list = data && data.success && Array.isArray(data.data)
          ? data.data
          : Array.isArray(data)
            ? data
            : data && Array.isArray(data.products)
              ? data.products
              : [];

        const formatted = list.map((p) => {
          let images = ["https://placehold.co/400x550/f5f5f5/888888?text=Printmont"];
          if (Array.isArray(p.images) && p.images.length > 0) {
            images = p.images.map((img) => (typeof img === "string" ? img : (img.image_url || img.url))).filter(Boolean);
          } else if (p.primary_image) {
            images = [p.primary_image];
          } else if (p.thumbnail) {
            images = [p.thumbnail];
          } else if (p.thumbnail_image) {
            images = [p.thumbnail_image];
          } else if (p.img) {
            images = [p.img];
          }

          // Exact price computations
          const rawSelling = parseFloat(p.selling_price || p.offer_price || p.discount_price || p.price) || 0;
          let rawMrp = parseFloat(p.mrp || p.regular_price || p.original_price) || 0;
          
          if (rawMrp <= rawSelling || rawMrp === 0) {
            rawMrp = rawSelling > 0 ? Math.round(rawSelling * 1.5) : 2999;
          }
          const sellingPrice = rawSelling > 0 ? rawSelling : 1499;
          const mrp = rawMrp;
          const discountPercent = p.discount_percent || p.discount_pct || (mrp > sellingPrice ? Math.round(((mrp - sellingPrice) / mrp) * 100) : 50);

          // Sizes
          let sizes = [];
          if (Array.isArray(p.sizes)) {
            sizes = p.sizes;
          } else if (typeof p.sizes === "string" && p.sizes.trim()) {
            try {
              sizes = JSON.parse(p.sizes);
            } catch {
              sizes = p.sizes.split(",").map((s) => s.trim()).filter(Boolean);
            }
          }
          if (!sizes.length && p.size_attributes) {
            try {
              const parsed = typeof p.size_attributes === "string" ? JSON.parse(p.size_attributes) : p.size_attributes;
              if (Array.isArray(parsed)) sizes = parsed.map((s) => s.size || s.name || s).filter(Boolean);
            } catch (e) { }
          }
          if (!sizes.length) {
            sizes = ["S", "M", "L", "XL", "XXL"];
          }

          // Colors
          let colors = [];
          if (Array.isArray(p.colors)) {
            colors = p.colors.map(c => typeof c === "string" ? c : (c.name || "")).filter(Boolean);
          } else if (typeof p.colors === "string" && p.colors.trim()) {
            try {
              const parsed = JSON.parse(p.colors);
              colors = parsed.map(c => typeof c === "string" ? c : (c.name || "")).filter(Boolean);
            } catch {
              colors = p.colors.split(",").map((c) => c.trim()).filter(Boolean);
            }
          } else if (p.color) {
            colors = p.color.split(",").map((c) => c.trim()).filter(Boolean);
          }
          if (!colors.length) {
            colors = ["Navy Blue", "Black", "White", "Grey"];
          }

          // Fabric / Material
          const fabric = p.material || p.fabric || (p.id % 2 === 0 ? "Cotton" : "Polyester Blend");

          // Type
          const type = p.type || (p.name && p.name.includes("Shirt") ? "T-Shirt" : "Casual Wear");

          // Pattern
          const pattern = p.shape || p.pattern || (p.id % 2 === 0 ? "Solid" : "Graphic Print");

          // Gender
          let gender = (p.gender || "Men").trim();
          if (gender === "male") gender = "Men";
          if (gender === "female") gender = "Women";
          if (gender === "unisex") gender = "Unisex";

          // Fit
          const fit = p.fit || (p.id % 2 === 0 ? "Regular Fit" : "Slim Fit");

          // Occasion
          const occasion = p.occasion || "Casual";

          const minQty = parseInt(p.min_quantity || p.minimum_quantity || p.minQty, 10) || 10;

          // Rating
          const pIdNum = parseInt(p.id, 10) || 1;
          const rating = 4.2 + (pIdNum % 8) * 0.1;
          const ratingCount = 80 + (pIdNum % 40) * 12;

          const catName = p.category_name || p.main_category_name || "";
          const subCatName = p.sub_category_name || "";
          const subSubCatName = p.sub_sub_category_name || "";

          return {
            id: p.id || Math.random().toString(),
            name: p.name || p.title || "Product",
            title: p.name || p.title || "Product",
            brand: (p.brand && !p.brand.includes("Deprecated")) ? p.brand : "Printmont",
            category: catName,
            categoryId: p.category_id || p.main_category_id || "",
            subCategory: subCatName,
            subCategoryId: p.sub_category_id || "",
            subSubCategory: subSubCatName,
            subSubCategoryId: p.sub_sub_category_id || "",
            leafCategory: subSubCatName || subCatName || catName,
            fabric,
            type,
            pattern,
            gender,
            fit,
            occasion,
            giftType: p.gift_type || "",
            images,
            price: sellingPrice,
            discountedPrice: sellingPrice,
            selling_price: sellingPrice,
            offer_price: sellingPrice,
            discount_price: sellingPrice,
            originalPrice: mrp,
            regular_price: mrp,
            mrp: mrp,
            discountPercent: discountPercent,
            discount_percent: discountPercent,
            sizes: sizes,
            colors: colors,
            min_quantity: minQty,
            customization_label: p.customization_label || "CUSTOMIZABLE",
            inStock: p.out_of_stock_status !== "out_of_stock" && (p.stock_quantity === undefined || p.stock_quantity > 0),
            ourBestseller: !!p.our_bestseller || !!p.is_bestseller,
            topRated: !!p.top_rated,
            topDeal: !!p.top_deal,
            featured: !!p.featured || !!p.is_featured,
            isAssured: p.is_assured !== undefined ? Boolean(p.is_assured) : (p.assured !== undefined ? Boolean(p.assured) : true),
            rating: rating,
            ratingCount: ratingCount,
            slug: p.slug || p.product_slug || ""
          };
        });

        setRawProducts(formatted);
        setError(null);
      } catch (err) {
        if (isMounted) setError(err.message || "Failed to load products");
      } finally {
        if (isMounted) setLoading(false);
      }
    };

    fetchProducts();
    return () => {
      isMounted = false;
    };
  }, []);

  // Dynamic Facets calculation
  const facets = useMemo(() => {
    if (!rawProducts.length) {
      return {
        categories: [],
        brands: [],
        sizes: [],
        fabrics: [],
        colors: [],
        types: [],
        patterns: [],
        genders: [],
        fits: [],
        occasions: [],
        minPrice: 0,
        maxPrice: 5000,
      };
    }

    const categoryMap = {};
    const brandMap = {};
    const fabricMap = {};
    const colorMap = {};
    const typeMap = {};
    const patternMap = {};
    const genderMap = {};
    const fitMap = {};
    const occasionMap = {};
    const sizeSet = new Set();
    let minP = Infinity;
    let maxP = 0;

    rawProducts.forEach((p) => {
      if (p.category) categoryMap[p.category] = (categoryMap[p.category] || 0) + 1;
      if (p.brand) brandMap[p.brand] = (brandMap[p.brand] || 0) + 1;
      if (p.fabric) fabricMap[p.fabric] = (fabricMap[p.fabric] || 0) + 1;
      if (p.type) typeMap[p.type] = (typeMap[p.type] || 0) + 1;
      if (p.pattern) patternMap[p.pattern] = (patternMap[p.pattern] || 0) + 1;
      if (p.gender) genderMap[p.gender] = (genderMap[p.gender] || 0) + 1;
      if (p.fit) fitMap[p.fit] = (fitMap[p.fit] || 0) + 1;
      if (p.occasion) occasionMap[p.occasion] = (occasionMap[p.occasion] || 0) + 1;

      (p.colors || []).forEach(c => {
        if (c) colorMap[c] = (colorMap[c] || 0) + 1;
      });
      (p.sizes || []).forEach(s => sizeSet.add(s));

      const effPrice = p.discountedPrice || p.price;
      if (effPrice < minP) minP = effPrice;
      if (effPrice > maxP) maxP = effPrice;
    });

    return {
      categories: Object.entries(categoryMap).map(([name, count]) => ({ name, count })),
      brands: Object.entries(brandMap).map(([name, count]) => ({ name, count })),
      fabrics: Object.entries(fabricMap).map(([name, count]) => ({ name, count })),
      colors: Object.entries(colorMap).map(([name, count]) => ({ name, count })),
      types: Object.entries(typeMap).map(([name, count]) => ({ name, count })),
      patterns: Object.entries(patternMap).map(([name, count]) => ({ name, count })),
      genders: Object.entries(genderMap).map(([name, count]) => ({ name, count })),
      fits: Object.entries(fitMap).map(([name, count]) => ({ name, count })),
      occasions: Object.entries(occasionMap).map(([name, count]) => ({ name, count })),
      sizes: Array.from(sizeSet).filter(Boolean),
      minPrice: Math.floor(minP === Infinity ? 0 : minP),
      maxPrice: Math.ceil(maxP || 5000)
    };
  }, [rawProducts]);

  const priceMin = minPriceParam !== null ? parseFloat(minPriceParam) : facets.minPrice;
  const priceMax = maxPriceParam !== null ? parseFloat(maxPriceParam) : facets.maxPrice;
  const minDiscount = minDiscountParam !== null ? parseInt(minDiscountParam, 10) : 0;

  // Filter Products
  const filteredProducts = useMemo(() => {
    return rawProducts.filter((product) => {
      // Flags
      if (isBestsellerFilter && !product.ourBestseller) return false;
      if (isTopRatedFilter && !product.topRated) return false;
      if (isTopDealFilter && !product.topDeal && product.discountPercent <= 0) return false;
      if (isFeaturedFilter && !product.featured) return false;
      if (isDiscountFilter && product.discountPercent <= 0) return false;

      // Category
      if (scopeId) {
        const idMatch = [product.categoryId, product.subCategoryId, product.subSubCategoryId]
          .some((cid) => cid !== "" && cid != null && String(cid) === scopeId);
        const nameMatch = scopeName && [product.category, product.subCategory, product.subSubCategory]
          .some((n) => n && n.toLowerCase() === scopeName.toLowerCase());
        if (!idMatch && !nameMatch) return false;
      } else if (selectedCategory) {
        const catLower = selectedCategory.toLowerCase();
        const matches = 
          (product.category && product.category.toLowerCase().includes(catLower)) ||
          (product.subCategory && product.subCategory.toLowerCase().includes(catLower)) ||
          (product.subSubCategory && product.subSubCategory.toLowerCase().includes(catLower)) ||
          (product.leafCategory && product.leafCategory.toLowerCase().includes(catLower));
        if (!matches) return false;
      }

      // Brand
      if (selectedBrands.length > 0 && !selectedBrands.some(b => b.toLowerCase() === product.brand.toLowerCase())) return false;
      // Size
      if (selectedSizes.length > 0 && !product.sizes.some(s => selectedSizes.includes(s))) return false;
      // Color
      if (selectedColors.length > 0 && !product.colors.some(c => selectedColors.some(sc => sc.toLowerCase() === c.toLowerCase()))) return false;
      // Fabric
      if (selectedFabrics.length > 0 && !selectedFabrics.some(f => f.toLowerCase() === (product.fabric || "").toLowerCase())) return false;
      // Type
      if (selectedTypes.length > 0 && !selectedTypes.some(t => t.toLowerCase() === (product.type || "").toLowerCase())) return false;
      // Pattern
      if (selectedPatterns.length > 0 && !selectedPatterns.some(p => p.toLowerCase() === (product.pattern || "").toLowerCase())) return false;
      // Gender
      if (selectedGenders.length > 0 && !selectedGenders.some(g => g.toLowerCase() === (product.gender || "").toLowerCase())) return false;
      // Fit
      if (selectedFits.length > 0 && !selectedFits.some(fit => fit.toLowerCase() === (product.fit || "").toLowerCase())) return false;
      // Occasion
      if (selectedOccasions.length > 0 && !selectedOccasions.some(o => o.toLowerCase() === (product.occasion || "").toLowerCase())) return false;

      // Price Range
      const effPrice = product.discountedPrice || product.price;
      if (minPriceParam !== null && effPrice < priceMin) return false;
      if (maxPriceParam !== null && effPrice > priceMax) return false;

      // Discount
      if (minDiscount > 0 && product.discountPercent < minDiscount) return false;
      // In Stock
      if (excludeOutOfStock && !product.inStock) return false;
      // Assured
      if (assuredOnly && !product.isAssured) return false;
      // Rating
      if (minRating > 0 && product.rating < minRating) return false;

      // Search Query
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase();
        const fullText = `${product.title} ${product.brand} ${product.category} ${product.subCategory} ${product.fabric} ${product.type}`.toLowerCase();
        if (!fullText.includes(q)) return false;
      }

      return true;
    });
  }, [
    rawProducts,
    isBestsellerFilter,
    isTopRatedFilter,
    isTopDealFilter,
    isFeaturedFilter,
    isDiscountFilter,
    selectedCategory,
    scopeId,
    scopeName,
    selectedBrands,
    selectedSizes,
    selectedColors,
    selectedFabrics,
    selectedTypes,
    selectedPatterns,
    selectedGenders,
    selectedFits,
    selectedOccasions,
    priceMin,
    priceMax,
    minDiscount,
    excludeOutOfStock,
    assuredOnly,
    minRating,
    searchQuery,
    minPriceParam,
    maxPriceParam
  ]);

  // Sorting
  const sortedProducts = useMemo(() => {
    const list = [...filteredProducts];
    switch (currentSort) {
      case "Price -- Low to High":
        return list.sort((a, b) => (a.discountedPrice || a.price) - (b.discountedPrice || b.price));
      case "Price -- High to Low":
        return list.sort((a, b) => (b.discountedPrice || b.price) - (a.discountedPrice || a.price));
      case "Newest First":
        return list.sort((a, b) => b.id - a.id);
      case "Popularity":
        return list.sort((a, b) => b.rating - a.rating || b.discountPercent - a.discountPercent);
      case "Relevance":
      default:
        return list;
    }
  }, [filteredProducts, currentSort]);

  // Pagination
  const totalItems = sortedProducts.length;
  const totalPages = Math.ceil(totalItems / DEFAULT_LIMIT) || 1;
  const pageIndex = Math.min(Math.max(1, currentPage), totalPages);

  const paginatedProducts = useMemo(() => {
    const start = (pageIndex - 1) * DEFAULT_LIMIT;
    return sortedProducts.slice(start, start + DEFAULT_LIMIT);
  }, [sortedProducts, pageIndex]);

  // Update URL search params helper
  const updateUrlParams = useCallback(
    (updater) => {
      setSearchParams((prevParams) => {
        const nextParams = new URLSearchParams(prevParams);
        updater(nextParams);
        return nextParams;
      });
    },
    [setSearchParams]
  );

  const setCategoryFilter = useCallback((cat) => {
    if (scopeId) {
      navigate(cat ? `/allproducts?category=${encodeURIComponent(cat)}` : "/allproducts");
      return;
    }
    updateUrlParams((params) => {
      if (cat) params.set("category", cat);
      else params.delete("category");
      params.set("page", "1");
    });
  }, [updateUrlParams, scopeId, navigate]);

  const toggleArrayFilter = useCallback((key, value) => {
    updateUrlParams((params) => {
      const current = params.get(key) ? params.get(key).split(",") : [];
      const next = current.includes(value)
        ? current.filter((item) => item !== value)
        : [...current, value];
      if (next.length) params.set(key, next.join(","));
      else params.delete(key);
      params.set("page", "1");
    });
  }, [updateUrlParams]);

  const toggleBrandFilter = useCallback((b) => toggleArrayFilter("brand", b), [toggleArrayFilter]);
  const toggleSizeFilter = useCallback((s) => toggleArrayFilter("size", s), [toggleArrayFilter]);
  const toggleColorFilter = useCallback((c) => toggleArrayFilter("color", c), [toggleArrayFilter]);
  const toggleFabricFilter = useCallback((f) => toggleArrayFilter("fabric", f), [toggleArrayFilter]);
  const toggleTypeFilter = useCallback((t) => toggleArrayFilter("type", t), [toggleArrayFilter]);
  const togglePatternFilter = useCallback((p) => toggleArrayFilter("pattern", p), [toggleArrayFilter]);
  const toggleGenderFilter = useCallback((g) => toggleArrayFilter("gender", g), [toggleArrayFilter]);
  const toggleFitFilter = useCallback((fit) => toggleArrayFilter("fit", fit), [toggleArrayFilter]);
  const toggleOccasionFilter = useCallback((o) => toggleArrayFilter("occasion", o), [toggleArrayFilter]);

  const setPriceRangeFilter = useCallback((min, max) => {
    updateUrlParams((params) => {
      if (min !== undefined && min !== facets.minPrice) params.set("minPrice", min.toString());
      else params.delete("minPrice");
      if (max !== undefined && max !== facets.maxPrice) params.set("maxPrice", max.toString());
      else params.delete("maxPrice");
      params.set("page", "1");
    });
  }, [updateUrlParams, facets]);

  const setMinDiscountFilter = useCallback((discount) => {
    updateUrlParams((params) => {
      if (discount > 0) params.set("minDiscount", discount.toString());
      else params.delete("minDiscount");
      params.set("page", "1");
    });
  }, [updateUrlParams]);

  const toggleInStockFilter = useCallback(() => {
    updateUrlParams((params) => {
      if (params.get("inStock") === "true") params.delete("inStock");
      else params.set("inStock", "true");
      params.set("page", "1");
    });
  }, [updateUrlParams]);

  const toggleAssuredFilter = useCallback(() => {
    updateUrlParams((params) => {
      if (params.get("assured") === "true") params.delete("assured");
      else params.set("assured", "true");
      params.set("page", "1");
    });
  }, [updateUrlParams]);

  const setMinRatingFilter = useCallback((rating) => {
    updateUrlParams((params) => {
      if (rating > 0) params.set("minRating", rating.toString());
      else params.delete("minRating");
      params.set("page", "1");
    });
  }, [updateUrlParams]);

  const setSortOption = useCallback((sort) => {
    updateUrlParams((params) => {
      params.set("sort", sort);
      params.set("page", "1");
    });
  }, [updateUrlParams]);

  const setPageNumber = useCallback((page) => {
    updateUrlParams((params) => {
      params.set("page", page.toString());
    });
  }, [updateUrlParams]);

  const clearCollectionFilter = useCallback(() => {
    updateUrlParams((params) => {
      params.delete("filter");
      params.delete("collection");
      params.delete("action");
      params.delete("bestseller");
      params.delete("top_rated");
      params.delete("top_deal");
      params.delete("featured");
      params.delete("discount");
      params.set("page", "1");
    });
  }, [updateUrlParams]);

  const clearAllFilters = useCallback(() => {
    setSearchParams((prevParams) => {
      const nextParams = new URLSearchParams();
      if (prevParams.get("q")) nextParams.set("q", prevParams.get("q"));
      if (prevParams.get("sort")) nextParams.set("sort", prevParams.get("sort"));
      return nextParams;
    });
  }, [setSearchParams]);

  const displayTitle = useMemo(() => {
    if (searchQuery) return `"${searchQuery}"`;
    if (selectedCategory && isBestsellerFilter) return `${selectedCategory} - Best Sellers`;
    if (selectedCategory && isTopRatedFilter) return `${selectedCategory} - Top Rated`;
    if (selectedCategory && isTopDealFilter) return `${selectedCategory} - Top Deals`;
    if (isBestsellerFilter) return "Best Sellers";
    if (isTopRatedFilter) return "Top Rated Products";
    if (isTopDealFilter) return "Top Deals";
    if (isDiscountFilter) return "Special Offers";
    if (selectedCategory) return selectedCategory;
    return "All Products";
  }, [searchQuery, isBestsellerFilter, isTopRatedFilter, isTopDealFilter, isDiscountFilter, selectedCategory]);

  return {
    products: paginatedProducts,
    allProducts: sortedProducts,
    totalProductsCount: totalItems,
    totalPages,
    currentPage: pageIndex,
    loading,
    error,
    facets,
    displayTitle,
    isBestsellerFilter,
    isTopRatedFilter,
    isTopDealFilter,
    isFeaturedFilter,
    isDiscountFilter,
    selectedCategory,
    selectedSubCategory,
    selectedSubSubCategory,
    selectedBrands,
    selectedSizes,
    selectedColors,
    selectedFabrics,
    selectedTypes,
    selectedPatterns,
    selectedGenders,
    selectedFits,
    selectedOccasions,
    priceMin,
    priceMax,
    minDiscount,
    excludeOutOfStock,
    assuredOnly,
    minRating,
    currentSort,
    searchQuery,
    setCategoryFilter,
    toggleBrandFilter,
    toggleSizeFilter,
    toggleColorFilter,
    toggleFabricFilter,
    toggleTypeFilter,
    togglePatternFilter,
    toggleGenderFilter,
    toggleFitFilter,
    toggleOccasionFilter,
    setPriceRangeFilter,
    setMinDiscountFilter,
    toggleInStockFilter,
    toggleAssuredFilter,
    setMinRatingFilter,
    setSortOption,
    setPageNumber,
    clearCollectionFilter,
    clearAllFilters
  };
}
