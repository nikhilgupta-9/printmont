import React, { useEffect, useLayoutEffect, useRef, useState } from "react";
import {
  TbCategory2, TbCup, TbBottle, TbBackpack, TbDeviceMobile, TbDeviceLaptop,
  TbDeviceTablet, TbHeadphones, TbDeviceWatch, TbBriefcase, TbShirt, TbGift,
  TbPencil, TbToolsKitchen2, TbHome, TbDiamond, TbBabyCarriage, TbPaw, TbBook2,
  TbMusic, TbCamera, TbPrinter, TbCar, TbPlane, TbBuildingStore,
} from "react-icons/tb";
import { IoIosArrowDown } from "react-icons/io";
import { IoChevronForward } from "react-icons/io5";
import { Link } from "react-router-dom";
import { API_ENDPOINTS, ASSET_URL, resolveImageUrl } from "../../../config/apiEndpoints";
import useHeaderSettings from "../../header/useHeaderSettings";
import "./categories.css";

// Site's blue theme (same blue used on the inner-page category bar background).
const ICON_BLUE = "#0b53a1";
const ICON_BLUE_BG = "#e8f1fb";

// No uploaded image for a category → pick an icon that matches its name instead of a generic one.
const CATEGORY_ICON_RULES = [
  { keywords: ["mug", "cup", "drinkware", "tumbler"], Icon: TbCup },
  { keywords: ["bottle"], Icon: TbBottle },
  { keywords: ["bag", "backpack", "tote", "duffel", "luggage"], Icon: TbBackpack },
  { keywords: ["mobile", "phone"], Icon: TbDeviceMobile },
  { keywords: ["laptop"], Icon: TbDeviceLaptop },
  { keywords: ["tablet"], Icon: TbDeviceTablet },
  { keywords: ["earbud", "headphone", "audio", "buds"], Icon: TbHeadphones },
  { keywords: ["watch"], Icon: TbDeviceWatch },
  { keywords: ["corporate"], Icon: TbBriefcase },
  { keywords: ["shirt", "cloth", "wear", "fashion", "dress", "apparel"], Icon: TbShirt },
  { keywords: ["gift"], Icon: TbGift },
  { keywords: ["stationery", "pen", "pencil"], Icon: TbPencil },
  { keywords: ["kitchen"], Icon: TbToolsKitchen2 },
  { keywords: ["home", "decor", "furniture"], Icon: TbHome },
  { keywords: ["jewel", "diamond"], Icon: TbDiamond },
  { keywords: ["toy", "kid", "baby"], Icon: TbBabyCarriage },
  { keywords: ["pet"], Icon: TbPaw },
  { keywords: ["book"], Icon: TbBook2 },
  { keywords: ["music"], Icon: TbMusic },
  { keywords: ["camera"], Icon: TbCamera },
  { keywords: ["print"], Icon: TbPrinter },
  { keywords: ["car", "auto"], Icon: TbCar },
  { keywords: ["travel", "plane"], Icon: TbPlane },
  { keywords: ["store", "shop"], Icon: TbBuildingStore },
  { keywords: ["electronic", "gadget", "device"], Icon: TbDeviceMobile },
];

const getCategoryIcon = (name = "") => {
  const lower = name.toLowerCase();
  const match = CATEGORY_ICON_RULES.find(rule => rule.keywords.some(k => lower.includes(k)));
  return match ? match.Icon : TbCategory2;
};

const fallbackCategories = [
  { id: 1, name: "Electronics", slug: "electronics", image: "/electro/mobile-1.jpeg" },
  { id: 2, name: "Men's Wear", slug: "mens-wear", image: "/girl-product-img/subsubcat-104.jpeg" },
  { id: 3, name: "Earbuds & Audio", slug: "earbuds-audio", image: "/electro/buds-1.jpeg" },
  { id: 4, name: "Home Decor", slug: "home-decor", image: "/girl-product-img/plant-1.jpeg" },
  { id: 5, name: "Women's Wear", slug: "womens-wear", image: "/women-dress/women-dress-1.jpeg" },
  { id: 6, name: "Smart Watches", slug: "smart-watches", image: "/electro/watch-1.jpeg" },
  { id: 7, name: "Stationery", slug: "stationery", image: "/section-img/pro12.jpeg" },
  { id: 8, name: "Custom Gifts", slug: "custom-gifts", image: "/men_shirt/men-shirt-2.jpeg" }
];

const Categories = ({ 
  showImages = true, 
  space="", 
  color = '', 
  bg = '', 
  isSticky = false, 
  categories, 
  limit = 12,
  desktopDesign,
  mobileDesign
}) => {
  const headerSettings = useHeaderSettings();
  const activeDesktopDesign = desktopDesign || (showImages ? (headerSettings?.homeDesktopDesign || 'design1') : (headerSettings?.innerDesktopDesign || 'design1'));
  const activeMobileDesign = mobileDesign || (showImages ? (headerSettings?.homeMobileDesign || 'design1') : (headerSettings?.innerMobileDesign || 'design1'));

  const categoryImageShape = headerSettings?.categoryImageShape || 'circle';
  const categoryImageSize = Number(headerSettings?.categoryImageSize) || 56;
  const shapeBorderRadius = categoryImageShape === 'square'
    ? '0px'
    : (categoryImageShape === 'rounded_square' ? '10px' : '50%');

  const [categoriesData, setCategoriesData] = useState(categories || []);
  const [mobileCategoriesData, setMobileCategoriesData] = useState(categories || []);
  const [show, setShow] = useState(true);
  const [categoryHeight, setCategoryHeight] = useState(0);
  const [activeCategory, setActiveCategory] = useState(null);
  const [activeSubCategory, setActiveSubCategory] = useState(0);
  const categoryRef = useRef(null);
  const lastScrollYRef = useRef(0);
  const frameRef = useRef(null);
  const hoverTimeoutRef = useRef(null);

  const handleCategoryMouseEnter = (index) => {
    if (hoverTimeoutRef.current) {
      clearTimeout(hoverTimeoutRef.current);
      hoverTimeoutRef.current = null;
    }
    setActiveCategory(index);
  };

  const handleCategoryMouseLeave = () => {
    if (hoverTimeoutRef.current) {
      clearTimeout(hoverTimeoutRef.current);
    }
    hoverTimeoutRef.current = setTimeout(() => {
      setActiveCategory(null);
    }, 180);
  };

  const handleDropdownMouseEnter = () => {
    if (hoverTimeoutRef.current) {
      clearTimeout(hoverTimeoutRef.current);
      hoverTimeoutRef.current = null;
    }
  };

  useEffect(() => {
    return () => {
      if (hoverTimeoutRef.current) {
        clearTimeout(hoverTimeoutRef.current);
      }
    };
  }, []);

  const getArray = (data) => {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    if (typeof data === 'object') return Object.values(data);
    return [];
  };

  useEffect(() => {
    if (categories) {
      setCategoriesData(categories);
      setMobileCategoriesData(categories);
      return;
    }
    const fetchCategories = async () => {
      try {
        const cacheBuster = `_t=${Date.now()}`;
        // showImages = the home page icon bar → menu_api.php?type=home (icon-flagged
        // via shown_on_home / show status, split desktop/mobile). Text-only inner bar → ?type=inner
        // (already pruned server-side, single tree used for both screen sizes).
        if (showImages) {
          const homeUrl = API_ENDPOINTS.HOME_MENU.includes('?') 
            ? `${API_ENDPOINTS.HOME_MENU}&${cacheBuster}`
            : `${API_ENDPOINTS.HOME_MENU}?${cacheBuster}`;
          const response = await fetch(homeUrl, { cache: 'no-store' });
          const data = await response.json();
          if (!data || !data.success || !data.data) throw new Error('Invalid home menu response');

          const allDesktop = getArray(data.data.desktop || data.data);
          const allMobile = getArray(data.data.mobile || data.data);

          const desktopList = allDesktop.filter(c => c && (
            c.shown_on_home == 1 || c.shown_on_home === true || c.shown_on_home === "1" || c.shown_on_home === "yes" ||
            c.desktop?.status === 'show' || c.desktop_menu_status === 'show'
          ));
          const mobileList = allMobile.filter(c => c && (
            c.shown_on_home == 1 || c.shown_on_home === true || c.shown_on_home === "1" || c.shown_on_home === "yes" ||
            c.mobile?.status === 'show' || c.mobile_topbar_status === 'show'
          ));

          const finalDesktop = desktopList.length > 0 ? desktopList : allDesktop;
          const finalMobile = mobileList.length > 0 ? mobileList : allMobile;
          
          setCategoriesData(finalDesktop);
          setMobileCategoriesData(finalMobile);
        } else {
          const innerUrl = API_ENDPOINTS.INNER_MENU.includes('?') 
            ? `${API_ENDPOINTS.INNER_MENU}&${cacheBuster}`
            : `${API_ENDPOINTS.INNER_MENU}?${cacheBuster}`;
          const response = await fetch(innerUrl, { cache: 'no-store' });
          const data = await response.json();
          if (!data || !data.success || !Array.isArray(data.data)) throw new Error('Invalid inner menu response');

          const list = data.data.length > 0 ? data.data : fallbackCategories;
          setCategoriesData(list);
          setMobileCategoriesData(list);
        }
      } catch (error) {
        console.error("Error fetching categories, using fallback:", error);
        setCategoriesData(fallbackCategories);
        setMobileCategoriesData(fallbackCategories);
      }
    };
    fetchCategories();

    // Auto-sync when user switches back to this tab from backend admin
    const handleSync = () => {
      if (document.visibilityState === 'visible') {
        fetchCategories();
      }
    };
    window.addEventListener('focus', handleSync);
    document.addEventListener('visibilitychange', handleSync);

    // Light periodic check every 5 seconds so edits reflect without hard refresh
    const syncInterval = setInterval(() => {
      if (document.visibilityState === 'visible') {
        fetchCategories();
      }
    }, 5000);

    return () => {
      window.removeEventListener('focus', handleSync);
      document.removeEventListener('visibilitychange', handleSync);
      clearInterval(syncInterval);
    };
  }, [categories, limit, showImages]);

  useEffect(() => {
    if (!isSticky) return;

    const handleScroll = () => {
      if (frameRef.current) return;

      frameRef.current = window.requestAnimationFrame(() => {
        const currentScrollY = window.scrollY;
        const threshold = window.innerWidth < 992 ? 80 : 150;

        if (currentScrollY <= 10) {
          setShow(true);
        } else if (
          currentScrollY > lastScrollYRef.current &&
          currentScrollY > threshold
        ) {
          setShow(false);
        } else if (currentScrollY < lastScrollYRef.current) {
          setShow(true);
        }

        lastScrollYRef.current = currentScrollY;
        frameRef.current = null;
      });
    };

    lastScrollYRef.current = window.scrollY;
    window.addEventListener("scroll", handleScroll, { passive: true });
    return () => {
      window.removeEventListener("scroll", handleScroll);
      if (frameRef.current) {
        window.cancelAnimationFrame(frameRef.current);
      }
    };
  }, [isSticky]);

  useLayoutEffect(() => {
    if (!isSticky || !categoryRef.current) return;

    const updateCategoryHeight = () => {
      if (!categoryRef.current) return;
      const h = categoryRef.current.getBoundingClientRect().height;
      setCategoryHeight(h);
      if (show) {
        document.documentElement.style.setProperty("--category-bar-offset", `${h}px`);
      }
    };

    updateCategoryHeight();
    const resizeObserver = new ResizeObserver(updateCategoryHeight);
    resizeObserver.observe(categoryRef.current);

    return () => resizeObserver.disconnect();
  }, [isSticky, showImages, space, categoriesData, show]);

  useEffect(() => {
    if (!isSticky) return;
    document.documentElement.style.setProperty(
      "--category-bar-offset",
      show ? `${categoryHeight || 45}px` : "0px"
    );
  }, [isSticky, show, categoryHeight]);

  const getImageUrl = (imagePath) => {
    return resolveImageUrl(imagePath);
  };

  const isDesktopVisible = (cat) => {
    if (!cat) return false;
    if (cat.status && (cat.status === 'inactive' || cat.status === 'deactive' || cat.status === 'disabled')) return false;
    const deskStatus = cat.desktop?.status ?? cat.desktop_menu_status;
    if (deskStatus === 'hide') return false;
    return true;
  };

  const isMobileVisible = (cat) => {
    if (!cat) return false;
    if (cat.status && (cat.status === 'inactive' || cat.status === 'deactive' || cat.status === 'disabled')) return false;
    const mobStatus = cat.mobile?.status ?? cat.mobile_topbar_status;
    if (mobStatus === 'hide') return false;
    return true;
  };

  // No image uploaded for this category → fall back to a generic icon instead of a broken/blank tile.
  const renderCategoryThumb = (item, extraClass = "", isMobile = false) => {
    const rawImg = isMobile 
      ? (item.mobile?.image || item.mobile_image || item.desktop?.image || item.desktop_image || item.image || item.images?.image)
      : (item.desktop?.image || item.desktop_image || item.image || item.images?.image || item.images?.desktop);

    const sizePx = isMobile ? Math.min(categoryImageSize, 48) : categoryImageSize;
    const dynamicThumbStyle = {
      width: `${sizePx}px`,
      height: `${sizePx}px`,
      maxWidth: `${sizePx}px`,
      maxHeight: `${sizePx}px`,
      borderRadius: shapeBorderRadius,
      aspectRatio: "1 / 1",
      objectFit: "cover",
      transition: "transform 0.25s ease, border-radius 0.25s ease, box-shadow 0.25s ease"
    };

    if (rawImg) {
      return (
        <img
          src={getImageUrl(rawImg)}
          alt={item.name}
          className={`mb-1 ${extraClass}`}
          style={{
            ...dynamicThumbStyle,
            backgroundColor: "#f8fafc"
          }}
          onError={(e) => { e.target.src = '/default-img.jpg'; }}
        />
      );
    }
    const Icon = getCategoryIcon(item.name);
    return (
      <span
        className={`mb-1 d-flex align-items-center justify-content-center ${extraClass}`}
        style={{
          ...dynamicThumbStyle,
          backgroundColor: "#e0f2fe"
        }}
      >
        <Icon size={Math.max(16, Math.round(sizePx * 0.42))} color="#0b53a1" />
      </span>
    );
  };

  const getDesktopOrder = (cat) => {
    if (!cat) return 999999;
    const ord = Number(cat.desktop?.order ?? cat.desktop_menu_order ?? cat.display_order ?? cat.order ?? 0);
    return ord > 0 ? ord : 999999;
  };

  const getMobileOrder = (cat) => {
    if (!cat) return 999999;
    const ord = Number(cat.mobile?.order ?? cat.mobile_topbar_order ?? cat.display_order ?? cat.order ?? 0);
    return ord > 0 ? ord : 999999;
  };

  const safeCategories = getArray(categoriesData);
  const displayCategories = (() => {
    const visible = safeCategories
      .filter(isDesktopVisible)
      .slice()
      .sort((a, b) => {
        const diff = getDesktopOrder(a) - getDesktopOrder(b);
        if (diff !== 0) return diff;
        return (a.name || '').localeCompare(b.name || '');
      });
    return limit ? visible.slice(0, limit) : visible;
  })();

  const safeMobileCategories = getArray(mobileCategoriesData);
  const mobileCategories = (() => {
    const visible = safeMobileCategories
      .filter(isMobileVisible)
      .slice()
      .sort((a, b) => {
        const diff = getMobileOrder(a) - getMobileOrder(b);
        if (diff !== 0) return diff;
        return (a.name || '').localeCompare(b.name || '');
      });
    return limit ? visible.slice(0, limit) : visible;
  })();
  const columnsData = [];
  for (let i = 0; i < mobileCategories.length; i += 2) {
    columnsData.push({
      top: mobileCategories[i],
      bottom: mobileCategories[i + 1] || null
    });
  }

  const effectiveBg = bg || (
    !showImages 
      ? (activeDesktopDesign === 'design2' ? '#0b53a1' : (activeDesktopDesign === 'design3' ? '#f8fafc' : '#ffffff'))
      : '#ffffff'
  );
  const effectiveColor = color || (
    !showImages 
      ? (activeDesktopDesign === 'design2' ? '#ffffff' : '#212121')
      : '#212121'
  );
  const isDarkNav = (
    effectiveColor?.toLowerCase() === '#ffffff' || 
    effectiveBg?.toLowerCase() === '#0b53a1' || 
    effectiveBg?.toLowerCase() === '#085885' ||
    activeDesktopDesign === 'design2'
  );

  return (
    <>
      <div
        ref={categoryRef}
        className={`container-fluid bg-transparent px-0 py-0 pb-0 m-0 mx-0 shadow-sm start-0 w-100 ${
          isSticky ? "categories-sticky" : "categories-main"
        }`}
        style={{
          ...(isSticky ? {
            transform: show ? "translateY(0)" : "translateY(-100%)",
            transition: "transform 0.3s ease-in-out",
            pointerEvents: show ? "auto" : "none",
            zIndex: 1020,
          } : { zIndex: 1020 }),
          overflow: "visible",
        }}
      >
      {/* SMALL SCREENS - 4 Selectable Mobile Layout Designs */}
      <div className="d-flex flex-column d-lg-none" style={{ padding: space || '8px 0', backgroundColor: effectiveBg }}>
        {activeMobileDesign === 'design2' ? (
          /* MOBILE DESIGN 2: 2-Row Compact Grid Pills */
          <div 
            className="d-flex overflow-x-auto hide-scrollbar w-100 py-1 px-2"
            style={{ 
              gap: "8px", 
              WebkitOverflowScrolling: "touch"
            }}
          >
            <div className="d-flex flex-column gap-2">
              <div className="d-flex gap-2">
                {mobileCategories.filter((_, idx) => idx % 2 === 0).map((cat, idx) => (
                  <Link
                    key={cat.id || idx}
                    to={cat.url || `/category/${cat.slug}`}
                    className="d-flex align-items-center gap-2 px-3 py-1 text-decoration-none rounded-pill"
                    style={{
                      backgroundColor: '#f1f5f9',
                      border: '1px solid #e2e8f0',
                      whiteSpace: 'nowrap',
                      flexShrink: 0
                    }}
                    title={cat.mobile?.title || cat.mobile_topbar_title || cat.name}
                  >
                    <div style={{ width: '22px', height: '22px', borderRadius: '50%', overflow: 'hidden', flexShrink: 0 }}>
                      {renderCategoryThumb(cat, "w-100 h-100 object-fit-cover", true)}
                    </div>
                    <span style={{ fontSize: '12px', fontWeight: '600', color: effectiveColor }}>
                      {cat.mobile?.title || cat.mobile_topbar_title || cat.name}
                    </span>
                  </Link>
                ))}
              </div>
              <div className="d-flex gap-2">
                {mobileCategories.filter((_, idx) => idx % 2 !== 0).map((cat, idx) => (
                  <Link
                    key={cat.id || idx}
                    to={cat.url || `/category/${cat.slug}`}
                    className="d-flex align-items-center gap-2 px-3 py-1 text-decoration-none rounded-pill"
                    style={{
                      backgroundColor: '#f1f5f9',
                      border: '1px solid #e2e8f0',
                      whiteSpace: 'nowrap',
                      flexShrink: 0
                    }}
                    title={cat.mobile?.title || cat.mobile_topbar_title || cat.name}
                  >
                    <div style={{ width: '22px', height: '22px', borderRadius: '50%', overflow: 'hidden', flexShrink: 0 }}>
                      {renderCategoryThumb(cat, "w-100 h-100 object-fit-cover", true)}
                    </div>
                    <span style={{ fontSize: '12px', fontWeight: '600', color: effectiveColor }}>
                      {cat.mobile?.title || cat.mobile_topbar_title || cat.name}
                    </span>
                  </Link>
                ))}
              </div>
            </div>
          </div>
        ) : activeMobileDesign === 'design3' ? (
          /* MOBILE DESIGN 3: 1-Row Smooth Horizontal Scroll Avatar Strip */
          <div 
            className="d-flex overflow-x-auto hide-scrollbar w-100 py-1 px-3 align-items-start"
            style={{ 
              gap: "14px", 
              WebkitOverflowScrolling: "touch"
            }}
          >
            {mobileCategories.map((cat, idx) => (
              <Link
                key={cat.id || idx}
                to={cat.url || `/category/${cat.slug}`}
                className="d-flex flex-column align-items-center text-center text-decoration-none flex-shrink-0"
                style={{ width: '64px' }}
                title={cat.mobile?.title || cat.mobile_topbar_title || cat.name}
              >
                <div style={{ width: '56px', height: '56px', borderRadius: '50%', overflow: 'hidden', border: '2px solid #0b53a1', padding: '2px' }}>
                  {renderCategoryThumb(cat, "w-100 h-100 rounded-circle object-fit-cover", true)}
                </div>
                <small
                  className="fw-semibold text-truncate w-100 mt-1"
                  style={{ fontSize: '11px', color: effectiveColor, lineHeight: '1.2' }}
                >
                  {cat.mobile?.title || cat.mobile_topbar_title || cat.name}
                </small>
              </Link>
            ))}
          </div>
        ) : activeMobileDesign === 'design4' ? (
          /* MOBILE DESIGN 4: Sticky Minimal Tab Bar */
          <div 
            className="d-flex overflow-x-auto hide-scrollbar w-100 py-1 px-2"
            style={{ 
              gap: "8px", 
              WebkitOverflowScrolling: "touch",
              borderBottom: '1px solid #e2e8f0'
            }}
          >
            {mobileCategories.map((cat, idx) => (
              <Link
                key={cat.id || idx}
                to={cat.url || `/category/${cat.slug}`}
                className="d-flex align-items-center gap-1 px-3 py-1 text-decoration-none rounded-pill flex-shrink-0"
                style={{
                  backgroundColor: idx === 0 ? '#0b53a1' : '#f1f5f9',
                  color: idx === 0 ? '#ffffff' : effectiveColor,
                  fontSize: '12px',
                  fontWeight: '600'
                }}
                title={cat.mobile?.title || cat.mobile_topbar_title || cat.name}
              >
                {cat.mobile?.title || cat.mobile_topbar_title || cat.name}
              </Link>
            ))}
          </div>
        ) : (
          /* MOBILE DESIGN 1: Standard Circular Avatar 2-Row Grid */
          <div 
            className="d-flex overflow-x-auto hide-scrollbar w-100 align-items-start"
            style={{ 
              gap: "10px", 
              paddingLeft: "12px", 
              paddingRight: "12px",
              WebkitOverflowScrolling: "touch" 
            }}
          >
            {columnsData.map((col, cIdx) => (
              <div 
                key={cIdx} 
                className="d-flex flex-column flex-shrink-0 align-items-center" 
                style={{ 
                  width: 'calc((100vw - 64px) / 4.5)', 
                  minWidth: '60px', 
                  maxWidth: '78px',
                  gap: '10px' 
                }}
              >
                {col.top && (
                  <Link
                    to={col.top.url || `/category/${col.top.slug}`}
                    className="d-flex flex-column align-items-center text-center text-decoration-none w-100"
                    title={col.top.name}
                  >
                    {renderCategoryThumb(col.top, "", true)}
                    <small
                      className="fw-medium text-truncate w-100"
                      style={{
                        fontSize: "11px",
                        color: effectiveColor,
                        lineHeight: "1.2",
                        marginTop: "3px"
                      }}
                    >
                      {col.top.mobile?.title || col.top.mobile_topbar_title || col.top.name}
                    </small>
                  </Link>
                )}
                {col.bottom && (
                  <Link
                    to={col.bottom.url || `/category/${col.bottom.slug}`}
                    className="d-flex flex-column align-items-center text-center text-decoration-none w-100"
                    title={col.bottom.name}
                  >
                    {renderCategoryThumb(col.bottom, "", true)}
                    <small
                      className="fw-medium text-truncate w-100"
                      style={{
                        fontSize: "11px",
                        color: effectiveColor,
                        lineHeight: "1.2",
                        marginTop: "3px"
                      }}
                    >
                      {col.bottom.mobile?.title || col.bottom.mobile_topbar_title || col.bottom.name}
                    </small>
                  </Link>
                )}
              </div>
            ))}
          </div>
        )}
      </div>

      {/* LARGE SCREENS */}
      {showImages ? (
        /* HOME PAGE CATEGORY ROW (Flipkart Style with Dynamic Shape & Size Badges + 2-Column Dropdown) */
        <div 
          className={`d-none d-lg-flex justify-content-center w-100 position-relative design-mode-${activeDesktopDesign}`} 
          style={{ 
            padding: space || '0', 
            backgroundColor: effectiveBg,
            '--cat-img-size': `${categoryImageSize}px`,
            '--cat-img-shape': shapeBorderRadius
          }}
        >
          <div 
            className="category-desktop-row mx-auto px-lg-3 position-relative" 
            style={{ 
              width: '100%', 
              maxWidth: '1440px',
              '--cat-img-size': `${categoryImageSize}px`,
              '--cat-img-shape': shapeBorderRadius
            }}
          >
            {displayCategories.map((item, index) => {
              const itemChildren = getArray(item.children)
                .filter(isDesktopVisible)
                .slice()
                .sort((a, b) => {
                  const diff = getDesktopOrder(a) - getDesktopOrder(b);
                  if (diff !== 0) return diff;
                  return (a.name || '').localeCompare(b.name || '');
                });
              const hasDropdown = itemChildren && itemChildren.length > 0;
              return (
                <div
                  key={index}
                  className="category-desktop-item position-relative"
                  onMouseEnter={() => {
                    setActiveCategory(index);
                    setActiveSubCategory(0);
                  }}
                  onMouseLeave={() => {
                    setActiveCategory(null);
                    setActiveSubCategory(0);
                  }}
                >
                  <Link 
                    to={item.url || `/category/${item.slug}`} 
                    className="category-desktop-card text-decoration-none" 
                    title={item.desktop?.title || item.desktop_menu_title || item.name}
                  >
                    <div className="category-thumb-wrapper">
                      {renderCategoryThumb(item, "category-thumb-desktop categoires-img-width", false)}
                    </div>
                    <div className="category-label-wrapper">
                      <span className="category-name">{item.desktop?.title || item.desktop_menu_title || item.name}</span>
                      {hasDropdown && (
                        <IoIosArrowDown
                          className={`category-arrow-icon ${activeCategory === index ? "rotate-arrow" : ""}`}
                        />
                      )}
                    </div>
                  </Link>

                  {/* FLIPKART STYLE 2-COLUMN DROPDOWN (Matches exact Flipkart UI) */}
                  {activeCategory === index && hasDropdown && (() => {
                    const isRightSide = index >= Math.ceil(displayCategories.length / 2);
                    const selSubIdx = (activeSubCategory !== null && activeSubCategory < itemChildren.length) ? activeSubCategory : 0;
                    const currentSub = itemChildren[selSubIdx];
                    const currentSubChildren = currentSub ? getArray(currentSub.children)
                      .filter(isDesktopVisible)
                      .slice()
                      .sort((a, b) => {
                        const diff = getDesktopOrder(a) - getDesktopOrder(b);
                        if (diff !== 0) return diff;
                        return (a.name || '').localeCompare(b.name || '');
                      }) : [];

                    return (
                      <div
                        className={`fk-2col-dropdown ${isRightSide ? 'align-right' : 'align-left'}`}
                        onMouseLeave={() => setActiveSubCategory(0)}
                      >
                        {/* Left Column: Subcategories list */}
                        <div className="fk-dropdown-left-col">
                          {itemChildren.map((sub, sIdx) => {
                            const subSubs = getArray(sub.children).filter(isDesktopVisible);
                            const isSelected = selSubIdx === sIdx;

                            return (
                              <div
                                key={sub.id || sIdx}
                                className={`fk-subcat-item ${isSelected ? 'active' : ''}`}
                                onMouseEnter={() => setActiveSubCategory(sIdx)}
                              >
                                <Link
                                  to={sub.url || `/category/${sub.slug}`}
                                  className="fk-subcat-link"
                                >
                                  <span>{sub.name}</span>
                                  {subSubs.length > 0 && <IoChevronForward className="fk-subcat-arrow" />}
                                </Link>
                              </div>
                            );
                          })}
                        </div>

                        {/* Right Column: More in [Subcategory] (Sub-subcategories) */}
                        {currentSub && (
                          <div className="fk-dropdown-right-col">
                            <div className="fk-subsub-header">
                              More in {currentSub.name}
                            </div>
                            <Link
                              to={currentSub.url || `/category/${currentSub.slug}`}
                              className="fk-subsub-link fk-subsub-all"
                            >
                              All
                            </Link>
                            {currentSubChildren.map((m, mIdx) => (
                              <Link
                                key={m.id || mIdx}
                                to={m.url || `/category/${m.slug}`}
                                className="fk-subsub-link"
                              >
                                {m.name}
                              </Link>
                            ))}
                          </div>
                        )}
                      </div>
                    );
                  })()}
                </div>
              );
            })}
          </div>
        </div>
      ) : (
        /* INNER PAGE CATEGORY ROW (FNP.com Style Multi-Column Mega Menu) */
        <div 
          className={`d-none d-lg-flex justify-content-center w-100 position-relative fnp-inner-cat-bar ${isDarkNav ? 'fnp-nav-dark' : 'fnp-nav-light'} design-mode-${activeDesktopDesign}`} 
          style={{ 
            backgroundColor: effectiveBg,
            '--nav-bg': effectiveBg,
            '--nav-color': effectiveColor
          }}
        >
          <div 
            className="fnp-inner-category-row mx-auto px-lg-3 position-relative" 
            style={{ width: '100%', maxWidth: '1440px' }}
          >
            {displayCategories.map((item, index) => {
              const itemChildren = getArray(item.children)
                .filter(isDesktopVisible)
                .slice()
                .sort((a, b) => {
                  const diff = getDesktopOrder(a) - getDesktopOrder(b);
                  if (diff !== 0) return diff;
                  return (a.name || '').localeCompare(b.name || '');
                });
              const hasDropdown = itemChildren && itemChildren.length > 0;
              const isHovered = activeCategory === index;

              return (
                <div
                  key={index}
                  className={`fnp-inner-cat-item ${isHovered ? 'active' : ''}`}
                  onMouseEnter={() => handleCategoryMouseEnter(index)}
                  onMouseLeave={handleCategoryMouseLeave}
                >
                  <Link 
                    to={item.url || `/category/${item.slug}`} 
                    className="fnp-inner-cat-link" 
                    title={item.desktop?.title || item.desktop_menu_title || item.name}
                  >
                    <span className="fnp-inner-cat-title">
                      {item.desktop?.title || item.desktop_menu_title || item.name}
                    </span>
                    {hasDropdown && (
                      <IoIosArrowDown
                        className={`fnp-inner-cat-arrow ${isHovered ? "rotate-arrow" : ""}`}
                      />
                    )}
                  </Link>

                  {/* FNP MULTI-COLUMN MEGA MENU DROPDOWN (5 Columns of Subcategories + Right Showcase Banner) */}
                  {isHovered && hasDropdown && (() => {
                    const NUM_COLS = 5;
                    const subcatColumns = Array.from({ length: NUM_COLS }, () => []);
                    itemChildren.forEach((sub, sIdx) => {
                      subcatColumns[sIdx % NUM_COLS].push(sub);
                    });

                    // Determine showcase banner image
                    const bannerImg = item.menu_image || 
                      item.desktop?.menu_image || 
                      item.desktop_menu_image || 
                      item.image || 
                      (itemChildren[0] && (itemChildren[0].menu_image || itemChildren[0].image));

                    return (
                      <div 
                        className="fnp-mega-dropdown-container"
                        onMouseEnter={handleDropdownMouseEnter}
                        onMouseLeave={handleCategoryMouseLeave}
                      >
                        <div className="fnp-mega-columns-wrapper">
                          {subcatColumns.map((columnSubs, colIdx) => {
                            if (!columnSubs || columnSubs.length === 0) return null;

                            return (
                              <div key={colIdx} className="fnp-mega-column">
                                {columnSubs.map((sub, sIdx) => {
                                  const subChildren = getArray(sub.children)
                                    .filter(isDesktopVisible)
                                    .slice()
                                    .sort((a, b) => {
                                      const diff = getDesktopOrder(a) - getDesktopOrder(b);
                                      if (diff !== 0) return diff;
                                      return (a.name || '').localeCompare(b.name || '');
                                    });

                                  return (
                                    <div key={sub.id || sIdx} className="fnp-mega-subcat-group">
                                      <Link to={sub.url || `/category/${sub.slug}`} className="fnp-mega-column-title">
                                        {sub.desktop?.title || sub.desktop_menu_title || sub.name}
                                      </Link>
                                      <div className="fnp-mega-links-list">
                                        {subChildren.map((m, mIdx) => {
                                          const tag = m.desktop?.tag || m.desktop_menu_tag;
                                          const showTag = tag || (mIdx === 0 && (sIdx === 1 || colIdx === 1 || colIdx === 3));
                                          return (
                                            <Link key={m.id || mIdx} to={m.url || `/category/${m.slug}`} className="fnp-mega-link">
                                              <span className="fnp-mega-link-name">{m.desktop?.title || m.desktop_menu_title || m.name}</span>
                                              {showTag && <span className="fnp-tag-pill">{tag || "New"}</span>}
                                            </Link>
                                          );
                                        })}
                                      </div>
                                    </div>
                                  );
                                })}
                              </div>
                            );
                          })}
                        </div>

                        {/* Right Showcase Banners / Image Tiles (Up to 3 Columns) */}
                        {(() => {
                          const banners = (item.showcase_banners && item.showcase_banners.length > 0)
                            ? item.showcase_banners
                            : (item.desktop?.showcase_banners && item.desktop.showcase_banners.length > 0)
                            ? item.desktop.showcase_banners
                            : (bannerImg ? [{
                                image: getImageUrl(bannerImg),
                                title: item.desktop?.title || item.desktop_menu_title || item.name,
                                url: item.url || `/category/${item.slug}`
                              }] : []);

                          if (banners.length === 0) return null;

                          return (
                            <div className={`fnp-mega-showcase fnp-mega-showcase-count-${banners.length}`}>
                              {banners.map((b, bIdx) => (
                                <Link 
                                  key={bIdx} 
                                  to={b.url || item.url || `/category/${item.slug}`} 
                                  className="fnp-mega-banner-card text-decoration-none"
                                >
                                  <div className="fnp-mega-banner-img-wrapper">
                                    <img 
                                      src={getImageUrl(b.image)} 
                                      alt={b.title || item.name} 
                                      className="fnp-mega-banner-img"
                                      onError={(e) => { e.target.src = '/default-img.jpg'; }}
                                    />
                                  </div>
                                  <div className="fnp-mega-banner-footer">
                                    <span>{b.title || item.desktop?.title || item.desktop_menu_title || item.name}</span>
                                  </div>
                                </Link>
                              ))}
                            </div>
                          );
                        })()}
                      </div>
                    );
                  })()}
                </div>
              );
            })}
          </div>
        </div>
      )}
      
      </div>
      {isSticky && (
        <div
          className="categories-sticky-spacer"
          style={{ height: categoryHeight ? `${categoryHeight}px` : undefined }}
          aria-hidden="true"
        />
      )}
    </>
  );
};

export default Categories;
