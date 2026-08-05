import { useState, useEffect, useMemo, useCallback } from "react";
import { useSearchParams } from "react-router-dom";
import { API_ENDPOINTS } from "../../config/apiEndpoints";

const DEFAULT_LIMIT = 40;

export default function useProductFilters() {
  const [searchParams, setSearchParams] = useSearchParams();

  const [rawProducts, setRawProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // URL state sync
  const currentPage = parseInt(searchParams.get("page") || "1", 10);
  const currentSort = searchParams.get("sort") || "Popularity";
  const searchQuery = searchParams.get("q") || searchParams.get("search") || searchParams.get("query") || "";

  // Filter selections from URL or state
  const selectedCategory = searchParams.get("category") || "";
  const selectedBrands = useMemo(
    () => (searchParams.get("brand") ? searchParams.get("brand").split(",") : []),
    [searchParams]
  );
  const selectedSizes = useMemo(
    () => (searchParams.get("size") ? searchParams.get("size").split(",") : []),
    [searchParams]
  );
  const selectedColors = useMemo(
    () => (searchParams.get("color") ? searchParams.get("color").split(",") : []),
    [searchParams]
  );
  const minPriceParam = searchParams.get("minPrice");
  const maxPriceParam = searchParams.get("maxPrice");
  const minDiscountParam = searchParams.get("minDiscount");
  const excludeOutOfStock = searchParams.get("inStock") === "true";

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
            images = p.images.map((img) => (typeof img === "string" ? img : img.image_url)).filter(Boolean);
          } else if (p.primary_image) {
            images = [p.primary_image];
          } else if (p.thumbnail) {
            images = [p.thumbnail];
          } else if (p.img) {
            images = [p.img];
          }

          const price = parseFloat(p.price || p.regular_price) || 0;
          const discountPrice = parseFloat(p.discount_price || p.offer_price) || price;
          const discountPercent =
            price > 0 && discountPrice < price
              ? Math.round(((price - discountPrice) / price) * 100)
              : 0;

          // Parse sizes & colors array intelligently per product
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

          const catLower = (p.category_name || p.main_category_name || p.category || "").toLowerCase();
          const nameLower = (p.name || p.title || "").toLowerCase();

          // Infer realistic apparel/footwear sizes if missing
          if (!sizes.length) {
            if (catLower.includes("jeans") || nameLower.includes("jeans") || catLower.includes("pant") || nameLower.includes("trouser")) {
              sizes = [28, 30, 32, 34, 36];
            } else if (catLower.includes("shoe") || nameLower.includes("shoe") || catLower.includes("footwear") || nameLower.includes("sneaker")) {
              sizes = [7, 8, 9, 10];
            } else if (
              catLower.includes("cloth") || catLower.includes("fashion") || catLower.includes("shirt") || catLower.includes("wear") || catLower.includes("outfit") || catLower.includes("kurti") || catLower.includes("dress") ||
              nameLower.includes("shirt") || nameLower.includes("t-shirt") || nameLower.includes("polo") || nameLower.includes("hoodie") || nameLower.includes("dress") || nameLower.includes("top") || nameLower.includes("kurti") || nameLower.includes("jacket")
            ) {
              const sizeVariants = [
                ["S", "M", "L", "XL", "XXL"],
                ["M", "L", "XL", "XXL"],
                ["S", "M", "L", "XL"],
                ["M", "L", "XL"],
                ["XS", "S", "M", "L", "XL"]
              ];
              const pIdNum = parseInt(p.id, 10) || 0;
              sizes = sizeVariants[pIdNum % sizeVariants.length];
            }
          }

          let colors = [];
          if (Array.isArray(p.colors)) colors = p.colors;
          else if (typeof p.colors === "string" && p.colors.trim()) {
            try {
              colors = JSON.parse(p.colors);
            } catch {
              colors = p.colors.split(",").map((c) => c.trim()).filter(Boolean);
            }
          }

          // Product Highlights for non-clothing products
          let highlightText = "";
          if (catLower.includes("electronic") || nameLower.includes("laptop") || nameLower.includes("keyboard") || nameLower.includes("mouse")) {
            highlightText = "1 Year Warranty • Free Shipping";
          } else if (catLower.includes("earbud") || nameLower.includes("headphone") || nameLower.includes("earbud")) {
            highlightText = "Active Noise Cancellation • Wireless";
          } else if (catLower.includes("mobile") || nameLower.includes("phone") || nameLower.includes("cable")) {
            highlightText = "High Speed • Premium Quality";
          } else if (catLower.includes("gift") || catLower.includes("stationery") || catLower.includes("mug") || catLower.includes("decor")) {
            highlightText = "Customizable • Premium Print";
          } else {
            highlightText = "Printmont Quality Guaranteed";
          }

          return {
            id: p.id || Math.random().toString(),
            name: p.name || p.title || "Product",
            title: p.name || p.title || "Product",
            brand: (p.brand && !p.brand.includes("Deprecated")) ? p.brand : "Printmont",
            category: p.category_name || p.main_category_name || "General",
            images,
            price: price,
            discountedPrice: discountPrice,
            originalPrice: price,
            discountPercent: discountPercent,
            sizes: sizes,
            colors: colors,
            highlightText: highlightText,
            inStock: p.out_of_stock_status !== "out_of_stock" && (p.stock_quantity === undefined || p.stock_quantity > 0),
            ourBestseller: !!p.our_bestseller,
            topRated: !!p.top_rated,
            featured: !!p.featured,
            rating: 4.2 + ((parseInt(p.id, 10) || 1) % 8) * 0.1,
            ratingCount: 15 + ((parseInt(p.id, 10) || 1) % 50) * 12,
            slug: p.slug || ""
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

  // Calculate dynamic facets from raw dataset
  const facets = useMemo(() => {
    if (!rawProducts.length) {
      return {
        categories: [],
        brands: [],
        sizes: [],
        colors: [],
        minPrice: 0,
        maxPrice: 5000,
        priceBreakpoints: [0, 500, 1000, 2000, 3000, 5000]
      };
    }

    const categoryMap = {};
    const brandMap = {};
    const sizeSet = new Set();
    const colorSet = new Set();
    let minP = Infinity;
    let maxP = 0;

    rawProducts.forEach((p) => {
      if (p.category) {
        categoryMap[p.category] = (categoryMap[p.category] || 0) + 1;
      }
      if (p.brand) {
        brandMap[p.brand] = (brandMap[p.brand] || 0) + 1;
      }
      p.sizes.forEach((s) => sizeSet.add(s));
      p.colors.forEach((c) => colorSet.add(c));

      const effectivePrice = p.discountedPrice || p.price;
      if (effectivePrice < minP) minP = effectivePrice;
      if (effectivePrice > maxP) maxP = effectivePrice;
    });

    const floorMin = Math.floor(minP === Infinity ? 0 : minP);
    const ceilMax = Math.ceil(maxP || 5000);

    return {
      categories: Object.entries(categoryMap).map(([name, count]) => ({ name, count })),
      brands: Object.entries(brandMap).map(([name, count]) => ({ name, count })),
      sizes: Array.from(sizeSet).filter(Boolean),
      colors: Array.from(colorSet).filter(Boolean),
      minPrice: floorMin,
      maxPrice: ceilMax
    };
  }, [rawProducts]);

  // Active price boundaries
  const priceMin = minPriceParam !== null ? parseFloat(minPriceParam) : facets.minPrice;
  const priceMax = maxPriceParam !== null ? parseFloat(maxPriceParam) : facets.maxPrice;
  const minDiscount = minDiscountParam !== null ? parseInt(minDiscountParam, 10) : 0;

  // Filter & Search Logic with Relevance Scoring
  const filteredProducts = useMemo(() => {
    if (!searchQuery.trim()) {
      return rawProducts.filter((product) => {
        if (selectedCategory && product.category.toLowerCase() !== selectedCategory.toLowerCase()) return false;
        if (selectedBrands.length > 0 && !selectedBrands.some((b) => b.toLowerCase() === product.brand.toLowerCase())) return false;
        if (selectedSizes.length > 0 && !product.sizes.some((s) => selectedSizes.includes(s))) return false;
        if (selectedColors.length > 0 && !product.colors.some((c) => selectedColors.includes(c))) return false;
        const effPrice = product.discountedPrice || product.price;
        if (effPrice < priceMin || effPrice > priceMax) return false;
        if (minDiscount > 0 && product.discountPercent < minDiscount) return false;
        if (excludeOutOfStock && !product.inStock) return false;
        return true;
      }).map((p) => ({ ...p, relevanceScore: 0 }));
    }

    const rawQ = searchQuery.trim().toLowerCase();
    const normalizedQ = rawQ.replace(/-/g, " ");
    const collapsedQ = rawQ.replace(/[- ]/g, "");
    const tokens = normalizedQ.split(/\s+/).filter(Boolean);

    return rawProducts
      .map((product) => {
        const titleLower = (product.title || "").toLowerCase();
        const brandLower = (product.brand || "").toLowerCase();
        const categoryLower = (product.category || "").toLowerCase();
        const highlightLower = (product.highlightText || "").toLowerCase();
        const slugLower = (product.slug || "").toLowerCase();

        const combinedText = `${titleLower} ${brandLower} ${categoryLower} ${highlightLower} ${slugLower}`;
        const combinedCollapsed = combinedText.replace(/[- ]/g, "");

        // Count how many tokens match
        let matchedTokensCount = 0;
        tokens.forEach((token) => {
          const singular = token.length > 3 && token.endsWith("s") ? token.slice(0, -1) : token;
          const plural = !token.endsWith("s") ? token + "s" : token;
          if (
            combinedText.includes(token) ||
            combinedText.includes(singular) ||
            combinedText.includes(plural) ||
            combinedCollapsed.includes(token.replace(/[- ]/g, ""))
          ) {
            matchedTokensCount++;
          }
        });

        // Require at least one token match when query is present
        if (matchedTokensCount === 0) return null;

        // If specific category was explicitly selected in URL, check if product category matches
        // But if user searches a global query, allow matching across categories if score is high
        if (selectedCategory && categoryLower !== selectedCategory.toLowerCase() && !titleLower.includes(rawQ) && !titleLower.includes(normalizedQ)) {
          return null;
        }

        if (selectedBrands.length > 0 && !selectedBrands.some((b) => b.toLowerCase() === brandLower)) return null;
        if (selectedSizes.length > 0 && !product.sizes.some((s) => selectedSizes.includes(s))) return null;
        if (selectedColors.length > 0 && !product.colors.some((c) => selectedColors.includes(c))) return null;
        const effPrice = product.discountedPrice || product.price;
        if (priceMinParam !== null && effPrice < priceMin) return null;
        if (priceMaxParam !== null && effPrice > priceMax) return null;
        if (minDiscount > 0 && product.discountPercent < minDiscount) return null;
        if (excludeOutOfStock && !product.inStock) return null;

        // Calculate relevance score
        let score = matchedTokensCount * 30;
        if (titleLower === rawQ || titleLower === normalizedQ) score += 200;
        else if (titleLower.startsWith(rawQ) || titleLower.startsWith(normalizedQ)) score += 100;
        else if (titleLower.includes(rawQ) || titleLower.includes(normalizedQ) || combinedCollapsed.includes(collapsedQ)) score += 60;

        if (categoryLower.includes(rawQ) || categoryLower.includes(normalizedQ)) score += 40;
        if (brandLower.includes(rawQ)) score += 25;

        tokens.forEach((t) => {
          if (titleLower.includes(t)) score += 15;
          if (categoryLower.includes(t)) score += 10;
        });

        return { ...product, relevanceScore: score };
      })
      .filter(Boolean);
  }, [
    rawProducts,
    searchQuery,
    selectedCategory,
    selectedBrands,
    selectedSizes,
    selectedColors,
    priceMin,
    priceMax,
    minDiscount,
    excludeOutOfStock
  ]);

  // Sorted Products
  const sortedProducts = useMemo(() => {
    const list = [...filteredProducts];
    const hasSearch = !!searchQuery.trim();

    switch (currentSort) {
      case "Price -- Low to High":
        return list.sort((a, b) => a.discountedPrice - b.discountedPrice);
      case "Price -- High to Low":
        return list.sort((a, b) => b.discountedPrice - a.discountedPrice);
      case "Newest First":
        return list.sort((a, b) => b.id - a.id);
      case "Discount":
        return list.sort((a, b) => b.discountPercent - a.discountPercent);
      case "Popularity":
      default:
        if (hasSearch) {
          return list.sort((a, b) => b.relevanceScore - a.relevanceScore || b.rating - a.rating);
        }
        return list.sort((a, b) => b.rating - a.rating || b.discountPercent - a.discountPercent);
    }
  }, [filteredProducts, currentSort, searchQuery]);

  // Pagination
  const totalItems = sortedProducts.length;
  const totalPages = Math.ceil(totalItems / DEFAULT_LIMIT) || 1;
  const pageIndex = Math.min(Math.max(1, currentPage), totalPages);

  const paginatedProducts = useMemo(() => {
    const start = (pageIndex - 1) * DEFAULT_LIMIT;
    return sortedProducts.slice(start, start + DEFAULT_LIMIT);
  }, [sortedProducts, pageIndex]);

  // Helper actions to update URL search params seamlessly
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

  const setCategoryFilter = useCallback(
    (cat) => {
      updateUrlParams((params) => {
        if (cat) params.set("category", cat);
        else params.delete("category");
        params.set("page", "1");
      });
    },
    [updateUrlParams]
  );

  const toggleBrandFilter = useCallback(
    (brand) => {
      updateUrlParams((params) => {
        const current = params.get("brand") ? params.get("brand").split(",") : [];
        const next = current.includes(brand)
          ? current.filter((b) => b !== brand)
          : [...current, brand];
        if (next.length) params.set("brand", next.join(","));
        else params.delete("brand");
        params.set("page", "1");
      });
    },
    [updateUrlParams]
  );

  const toggleSizeFilter = useCallback(
    (size) => {
      updateUrlParams((params) => {
        const current = params.get("size") ? params.get("size").split(",") : [];
        const next = current.includes(size)
          ? current.filter((s) => s !== size)
          : [...current, size];
        if (next.length) params.set("size", next.join(","));
        else params.delete("size");
        params.set("page", "1");
      });
    },
    [updateUrlParams]
  );

  const setPriceRangeFilter = useCallback(
    (min, max) => {
      updateUrlParams((params) => {
        if (min !== undefined && min !== facets.minPrice) params.set("minPrice", min.toString());
        else params.delete("minPrice");

        if (max !== undefined && max !== facets.maxPrice) params.set("maxPrice", max.toString());
        else params.delete("maxPrice");

        params.set("page", "1");
      });
    },
    [updateUrlParams, facets]
  );

  const setMinDiscountFilter = useCallback(
    (discount) => {
      updateUrlParams((params) => {
        if (discount > 0) params.set("minDiscount", discount.toString());
        else params.delete("minDiscount");
        params.set("page", "1");
      });
    },
    [updateUrlParams]
  );

  const toggleInStockFilter = useCallback(
    () => {
      updateUrlParams((params) => {
        if (params.get("inStock") === "true") params.delete("inStock");
        else params.set("inStock", "true");
        params.set("page", "1");
      });
    },
    [updateUrlParams]
  );

  const setSortOption = useCallback(
    (sortOption) => {
      updateUrlParams((params) => {
        params.set("sort", sortOption);
        params.set("page", "1");
      });
    },
    [updateUrlParams]
  );

  const setPageNumber = useCallback(
    (p) => {
      updateUrlParams((params) => {
        params.set("page", p.toString());
      });
    },
    [updateUrlParams]
  );

  const clearAllFilters = useCallback(() => {
    setSearchParams(new URLSearchParams());
  }, [setSearchParams]);

  return {
    rawProducts,
    products: paginatedProducts,
    totalProductsCount: totalItems,
    totalPages,
    currentPage: pageIndex,
    loading,
    error,
    facets,
    // Filters & Sorting state values
    selectedCategory,
    selectedBrands,
    selectedSizes,
    selectedColors,
    priceMin,
    priceMax,
    minDiscount,
    excludeOutOfStock,
    currentSort,
    searchQuery,
    // Action methods
    setCategoryFilter,
    toggleBrandFilter,
    toggleSizeFilter,
    setPriceRangeFilter,
    setMinDiscountFilter,
    toggleInStockFilter,
    setSortOption,
    setPageNumber,
    clearAllFilters
  };
}
