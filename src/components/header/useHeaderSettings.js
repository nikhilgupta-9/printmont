import { useState, useEffect, useMemo } from 'react';
import { useLocation } from 'react-router-dom';
import { API_ENDPOINTS } from '../../config/apiEndpoints';

// Module-level cache so we only fetch rules once per session / window reload
let cachedRules = null;
let cachedMenuDesign = 'design1';
let fetchPromise = null;

const DEFAULT_RULES = [
  {
    page_key: 'home',
    route_patterns: '^/$',
    header_type: 'full',
    show_category_bar: 0,
    category_bar_mode: 'with_images',
    is_sticky: 0,
    custom_bg: 'rgb(11, 83, 161)',
    custom_text_color: 'white',
    sort_order: 1
  },
  {
    page_key: 'shop_products',
    route_patterns: '/allproducts,/search,/products',
    header_type: 'inner',
    show_category_bar: 1,
    category_bar_mode: 'text_only',
    is_sticky: 1,
    custom_bg: 'rgb(11, 83, 161)',
    custom_text_color: 'white',
    sort_order: 2
  },
  {
    page_key: 'categories_page',
    route_patterns: '/category,/category/.*',
    header_type: 'inner',
    show_category_bar: 1,
    category_bar_mode: 'text_only',
    is_sticky: 1,
    custom_bg: 'rgb(11, 83, 161)',
    custom_text_color: 'white',
    sort_order: 3
  },
  {
    page_key: 'product_details',
    route_patterns: '/product/.*,/[a-z0-9\\-]+-p[0-9]+',
    header_type: 'inner',
    show_category_bar: 1,
    category_bar_mode: 'text_only',
    is_sticky: 1,
    custom_bg: 'rgb(11, 83, 161)',
    custom_text_color: 'white',
    sort_order: 4
  },
  {
    page_key: 'cart_checkout',
    route_patterns: '/cart,/checkout',
    header_type: 'full',
    show_category_bar: 0,
    category_bar_mode: 'text_only',
    is_sticky: 0,
    custom_bg: 'rgb(11, 83, 161)',
    custom_text_color: 'white',
    sort_order: 5
  },
  {
    page_key: 'company_about',
    route_patterns: '/about,/careers,/careers/.*,/franchise,/franchises,/become-a-seller,/affiliate-program,/business-solutions,/bulk-order,/bulk-orders,/blog,/blog/.*',
    header_type: 'inner',
    show_category_bar: 0,
    category_bar_mode: 'text_only',
    is_sticky: 0,
    custom_bg: 'rgb(11, 83, 161)',
    custom_text_color: 'white',
    sort_order: 7
  },
  {
    page_key: 'support_help',
    route_patterns: '/help-center,/faq,/contact,/support,/track-order,/quick-links',
    header_type: 'inner',
    show_category_bar: 0,
    category_bar_mode: 'text_only',
    is_sticky: 0,
    custom_bg: 'rgb(11, 83, 161)',
    custom_text_color: 'white',
    sort_order: 8
  },
  {
    page_key: 'policies_legal',
    route_patterns: '/security,/policy/.*,/privacy-policy,/terms-of-use,/terms-and-conditions,/shipping-policy,/refund-policy,/return-policy,/sitemap',
    header_type: 'inner',
    show_category_bar: 0,
    category_bar_mode: 'text_only',
    is_sticky: 0,
    custom_bg: 'rgb(11, 83, 161)',
    custom_text_color: 'white',
    sort_order: 9
  },
  {
    page_key: 'default_fallback',
    route_patterns: '.*',
    header_type: 'inner',
    show_category_bar: 0,
    category_bar_mode: 'text_only',
    is_sticky: 1,
    custom_bg: 'rgb(11, 83, 161)',
    custom_text_color: 'white',
    sort_order: 99
  }
];

export const useHeaderSettings = () => {
  const location = useLocation();
  const currentPath = location.pathname;

  const [rules, setRules] = useState(cachedRules || DEFAULT_RULES);
  const [menuDesign, setMenuDesign] = useState(cachedMenuDesign);
  const [loading, setLoading] = useState(!cachedRules);

  useEffect(() => {
    const loadSettings = () => {
      fetch(API_ENDPOINTS.HEADER_SETTINGS)
        .then((res) => res.json())
        .then((json) => {
          if (json?.success && json.data?.rules && Array.isArray(json.data.rules)) {
            cachedRules = json.data.rules;
            cachedMenuDesign = json.data.menu_design || 'design1';
            setRules(cachedRules);
            setMenuDesign(cachedMenuDesign);
          }
        })
        .catch((err) => {
          console.warn('Failed to load header settings from API, using defaults:', err);
        })
        .finally(() => {
          setLoading(false);
        });
    };

    loadSettings();

    // Re-fetch automatically when window regains focus (e.g. user toggles setting in admin tab and switches back to frontend tab)
    const handleFocus = () => loadSettings();
    window.addEventListener('focus', handleFocus);
    return () => window.removeEventListener('focus', handleFocus);
  }, [currentPath]);

  // Match the current pathname against active rules
  const activeRule = useMemo(() => {
    const activeRules = rules.filter((r) => r && (r.is_active === 1 || r.is_active === "1" || r.is_active === true));

    for (const rule of activeRules) {
      if (!rule.route_patterns) continue;
      const patterns = rule.route_patterns.split(',').map((p) => p.trim()).filter(Boolean);

      for (const pattern of patterns) {
        try {
          if (pattern.startsWith('^') || pattern.endsWith('$')) {
            const re = new RegExp(pattern, 'i');
            if (re.test(currentPath)) return rule;
          } else if (pattern.includes('.*')) {
            const re = new RegExp(`^${pattern}$`, 'i');
            if (re.test(currentPath)) return rule;
          } else if (currentPath === pattern || currentPath.startsWith(pattern + '/')) {
            return rule;
          }
        } catch {
          if (currentPath === pattern) return rule;
        }
      }
    }

    // Fallback rule if no patterns match
    return (
      activeRules.find((r) => r.page_key === 'default_fallback') || {
        header_type: currentPath === '/' || currentPath === '/cart' ? 'full' : 'inner',
        show_category_bar: 0,
        category_bar_mode: 'text_only',
        is_sticky: 1,
        custom_bg: 'rgb(11, 83, 161)',
        custom_text_color: 'white',
      }
    );
  }, [rules, currentPath]);

  return {
    headerType: activeRule?.header_type || 'inner',
    showCategoryBar: activeRule ? parseInt(activeRule.show_category_bar, 10) === 1 : false,
    categoryBarMode: activeRule?.category_bar_mode || 'text_only',
    isSticky: activeRule ? parseInt(activeRule.is_sticky, 10) === 1 : true,
    customBg: activeRule?.custom_bg || 'rgb(11, 83, 161)',
    customTextColor: activeRule?.custom_text_color || 'white',
    menuDesign,
    loading,
    activeRule
  };
};

export default useHeaderSettings;
