import React, { useEffect, useLayoutEffect, useRef, useState } from "react";
import {
  TbCategory2, TbCup, TbBottle, TbBackpack, TbDeviceMobile, TbDeviceLaptop,
  TbDeviceTablet, TbHeadphones, TbDeviceWatch, TbBriefcase, TbShirt, TbGift,
  TbPencil, TbToolsKitchen2, TbHome, TbDiamond, TbBabyCarriage, TbPaw, TbBook2,
  TbMusic, TbCamera, TbPrinter, TbCar, TbPlane, TbBuildingStore,
} from "react-icons/tb";
import { IoIosArrowDown } from "react-icons/io";
import { Link } from "react-router-dom";
import { API_ENDPOINTS, ASSET_URL, resolveImageUrl } from "../../../config/apiEndpoints";
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

const Categories = ({ showImages = true, space="", color = '', bg = '', isSticky = false, categories, limit }) => {
  const [categoriesData, setCategoriesData] = useState(categories || []);
  const [mobileCategoriesData, setMobileCategoriesData] = useState(categories || []);
  const [show, setShow] = useState(true);
  const [categoryHeight, setCategoryHeight] = useState(0);
  const [activeCategory, setActiveCategory] = useState(null);
  const categoryRef = useRef(null);
  const lastScrollYRef = useRef(0);
  const frameRef = useRef(null);

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
        // showImages = the home page icon bar → menu_api.php?type=home (icon-flagged
        // via shown_on_home, split desktop/mobile). Text-only inner bar → ?type=inner
        // (already pruned server-side, single tree used for both screen sizes).
        if (showImages) {
          const response = await fetch(API_ENDPOINTS.HOME_MENU);
          const data = await response.json();
          if (!data || !data.success || !data.data) throw new Error('Invalid home menu response');

          const allDesktop = getArray(data.data.desktop || data.data);
          const allMobile = getArray(data.data.mobile || data.data);

          setCategoriesData(allDesktop);
          setMobileCategoriesData(allMobile);
        } else {
          const response = await fetch(API_ENDPOINTS.INNER_MENU);
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
  const renderCategoryThumb = (item, extraClass = "") => {
    const imgSource = item?.image || item?.desktop_image || item?.mobile_image || item?.images?.image || item?.images?.desktop || item?.desktop?.image || item?.icon;
    if (imgSource) {
      return (
        <img
          src={getImageUrl(imgSource)}
          alt={item?.name || "Category"}
          className={`rounded mb-1 categoires-img-width ${extraClass}`}
          style={{ objectFit: "cover", aspectRatio: "1 / 1" }}
          onError={(e) => { e.target.src = '/default-img.jpg'; }}
        />
      );
    }
    const Icon = getCategoryIcon(item?.name || "");
    return (
      <span
        className={`rounded mb-1 categoires-img-width d-flex align-items-center justify-content-center ${extraClass}`}
        style={{ aspectRatio: "1 / 1", backgroundColor: ICON_BLUE_BG }}
      >
        <Icon size={20} color={ICON_BLUE} />
      </span>
    );
  };

  const safeCategories = getArray(categoriesData);
  const displayCategories = (() => {
    const visible = safeCategories.filter(isDesktopVisible);
    return limit ? visible.slice(0, limit) : visible;
  })();

  const safeMobileCategories = getArray(mobileCategoriesData);
  const mobileCategories = (() => {
    const visible = safeMobileCategories.filter(isMobileVisible);
    return limit ? visible.slice(0, limit) : visible;
  })();
  const half = Math.ceil(mobileCategories.length / 2);
  const columnsData = [];
  for (let i = 0; i < half; i++) {
    columnsData.push({
      top: mobileCategories[i],
      bottom: mobileCategories[i + half] || null
    });
  }

  return (
    <>
      <div
        ref={categoryRef}
        className={`container-fluid bg-transparent px-0 py-0 pb-0 m-0 mx-0 shadow-sm start-0 w-100 ${
          isSticky ? "categories-sticky" : "categories-main"
        }`}
        style={isSticky ? {
          transform: show ? "translateY(0)" : "translateY(-100%)",
          transition: "transform 0.3s ease-in-out",
          pointerEvents: show ? "auto" : "none",
          zIndex: 1020,
        } : {}}
      >
      {/* SMALL SCREENS */}
      <div className="d-flex flex-column d-lg-none px-2 pt-1 pb-1" style={{padding:`${space}`, backgroundColor:`${bg || '#ffffff'}`}}>
        <div className="d-flex overflow-x-auto hide-scrollbar gap-2 py-1 w-100">
          {columnsData.map((col, index) => (
            <div key={index} className="d-flex flex-column gap-2 flex-shrink-0">
              {col.top && (
                <Link
                  to={`/category/${col.top.slug}`}
                  className="d-flex flex-column align-items-center text-center p-1 categoires-cont-width text-decoration-none"
                >
                  {showImages && renderCategoryThumb(col.top, "border")}
                  <small className="text-truncate w-100 fw-bold categories-text" style={{ color: color || '#6c757d' }}>
                    {col.top.name}
                  </small>
                </Link>
              )}
              {col.bottom && (
                <Link
                  to={`/category/${col.bottom.slug}`}
                  className="d-flex flex-column align-items-center text-center p-1 categoires-cont-width text-decoration-none"
                >
                  {showImages && renderCategoryThumb(col.bottom, "border")}
                  <small className="text-truncate w-100 fw-bold categories-text" style={{ color: color || '#6c757d' }}>
                    {col.bottom.name}
                  </small>
                </Link>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* LARGE SCREENS */}
      <div className="d-none d-lg-flex justify-content-center w-100 position-relative text-nowrap small" style={{padding:`${space}`, backgroundColor:`${bg}`}} >
        <div className="category-desktop-row mx-auto px-lg-4 position-relative" style={{ maxWidth: showImages ? '1440px' : '100%' }}>
          {displayCategories.map((item, index) => {
            const itemChildren = getArray(item.children);
            return (
          <div
            key={index}
            className={`category-desktop-item d-flex flex-column align-items-center text-center mb-0 over ${showImages ? "position-relative" : ""}`}
            onMouseEnter={() => {
              setActiveCategory(index);
              if (typeof setActiveSub === 'function') setActiveSub(0);
            }}
            onMouseLeave={() => {
              setActiveCategory(null);
              if (typeof setActiveSub === 'function') setActiveSub(null);
            }}
            style={{ cursor: "pointer" }}
          >
            <Link to={`/category/${item.slug}`} className="d-flex flex-column align-items-center text-decoration-none w-100" title={item.name}>
              {showImages && renderCategoryThumb(item, "category-thumb-desktop")}
              <div className="category-label-row d-flex justify-content-center align-items-center text-decoration-none over">
                <span className="category-name fw-semibold" style={{ color: `${color}` }}>{item.name}</span>
                <IoIosArrowDown
                  className={`ms-1 flex-shrink-0 transition-arrow ${activeCategory === index ? "rotate-arrow" : ""
                    }`} style={{color:`${color}`}}
                />
              </div>
            </Link>

            {/* MEGA MENU DROPDOWN */}
            {activeCategory === index && itemChildren && itemChildren.length > 0 && (
              !showImages ? (
              <div
                className="position-absolute bg-white rounded shadow-lg d-flex text-start py-4 px-4 dropdown-panel active mx-auto"
                style={{
                  top: "100%",
                  left: "0",
                  right: "0",
                  width: "95%",
                  zIndex: 1000,
                  cursor: "default",
                  maxHeight: "70vh",
                  overflowY: "auto",
                  borderTop: "2px solid #f0f0f0",
                }}
              >
                <div className="d-flex flex-wrap flex-grow-1" style={{ gap: "20px" }}>
                  {itemChildren.filter(isDesktopVisible).map((sub, i) => {
                    const subChildren = getArray(sub.children).filter(isDesktopVisible);
                    return (
                    <div key={i} className="d-flex flex-column mb-3" style={{ flex: "1 1 180px", maxWidth: "250px" }}>
                      <Link to={`/category/${sub.slug}`} className="fw-bold text-dark text-decoration-none mb-2 pb-1 border-bottom fs-6 text-wrap">
                        {sub.name}
                      </Link>
                      {subChildren && subChildren.length > 0 && subChildren.map((m, idx) => (
                        <Link
                          key={idx}
                          to={`/category/${m.slug}`}
                          className="text-secondary text-decoration-none py-1 text-wrap"
                          style={{ fontSize: "14px", display: "block" }}
                          onMouseOver={(e) => e.target.style.color = "#007bff"}
                          onMouseOut={(e) => e.target.style.color = "#6c757d"}
                        >
                          {m.name}
                        </Link>
                      ))}
                    </div>
                  )})}
                </div>

                {/* Promotional Image Section - Only show if showImages is true but wait, this is !showImages branch. So we hide it to be safe, or just leave it out. Since the user wants the layout but without images. */}
              </div>
              ) : (
                <div
                  className="position-absolute bg-white rounded shadow-lg d-flex text-start py-4 px-4 dropdown-panel active"
                  style={{
                    top: "100%",
                    left: index > 5 ? "auto" : "0",
                    right: index > 5 ? "0" : "auto",
                    zIndex: 1000,
                    width: "max-content",
                    minWidth: "480px",
                    maxWidth: "760px",
                    maxHeight: "70vh",
                    overflowY: "auto",
                    borderTop: "2px solid #f0f0f0",
                    cursor: "default",
                  }}
                >
                  <div className="d-flex flex-wrap flex-grow-1" style={{ gap: "24px" }}>
                    {itemChildren.filter(isDesktopVisible).map((sub, i) => {
                      const subChildren = getArray(sub.children).filter(isDesktopVisible);
                      return (
                        <div key={i} className="d-flex flex-column mb-3" style={{ flex: "1 1 180px", maxWidth: "220px" }}>
                          <Link to={`/category/${sub.slug}`} className="fw-bold text-dark text-decoration-none mb-2 pb-1 border-bottom fs-6 text-wrap">
                            {sub.name}
                          </Link>
                          {subChildren && subChildren.length > 0 && subChildren.map((m, idx) => (
                            <Link
                              key={idx}
                              to={`/category/${m.slug}`}
                              className="text-secondary text-decoration-none py-1 text-wrap"
                              style={{ fontSize: "14px", display: "block" }}
                              onMouseOver={(e) => e.target.style.color = "#007bff"}
                              onMouseOut={(e) => e.target.style.color = "#6c757d"}
                            >
                              {m.name}
                            </Link>
                          ))}
                        </div>
                      );
                    })}
                  </div>
                </div>
              )
            )}
          </div>
        )})}
        </div>
      </div>
      
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
