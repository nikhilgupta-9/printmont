import React, { useEffect, useLayoutEffect, useRef, useState } from "react";
import { TbCategory2 } from "react-icons/tb";
import { IoIosArrowDown, IoIosArrowForward } from "react-icons/io";
import { Link } from "react-router-dom";
import axios from "axios";
import { API_ENDPOINTS, ASSET_URL } from "../../../config/apiEndpoints";

const Categories = ({ showImages = true, space="", color = '', bg = '', isSticky = false }) => {
  const [categoriesData, setCategoriesData] = useState([]);
  const [show, setShow] = useState(true);
  const [categoryHeight, setCategoryHeight] = useState(0);
  const [activeCategory, setActiveCategory] = useState(null);
  const [activeSub, setActiveSub] = useState(null);
  const categoryRef = useRef(null);
  const lastScrollYRef = useRef(0);
  const frameRef = useRef(null);

  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await axios.get(API_ENDPOINTS.CATEGORIES);
        if (response.data.success) {
          setCategoriesData(response.data.data);
        }
      } catch (error) {
        console.error("Error fetching categories:", error);
      }
    };
    fetchCategories();
  }, []);

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
      setCategoryHeight(categoryRef.current.offsetHeight);
    };

    updateCategoryHeight();
    const resizeObserver = new ResizeObserver(updateCategoryHeight);
    resizeObserver.observe(categoryRef.current);

    return () => resizeObserver.disconnect();
  }, [isSticky, showImages, space, categoriesData]);

  const getImageUrl = (imagePath) => {
    if (!imagePath) return '/default-img.jpg';
    if (imagePath.startsWith('http')) return imagePath;
    const cleanPath = imagePath.startsWith('/') ? imagePath.substring(1) : imagePath;
    return `${ASSET_URL}${cleanPath}`;
  };

  const getArray = (data) => {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    if (typeof data === 'object') return Object.values(data);
    return [];
  };

  const safeCategories = getArray(categoriesData);

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
      <div className="d-flex d-lg-none overflow-x-auto gap-2 px-2 align-items-center hide-scrollbar" style={{padding:`${space}`, backgroundColor:`${bg || '#ffffff'}`}}>
        {safeCategories.slice(0, 12).map((item, index) => {
          const itemChildren = getArray(item.children);
          return (
          <Link
            to={`/category/${item.slug}`}
            key={index}
            className="d-flex flex-column align-items-center text-center flex-shrink-0 p-1 categoires-cont-width text-decoration-none"
          >
            {showImages && (
            <img
              src={getImageUrl(item.image)}
              alt={item.name}
              className="rounded mb-1 border categoires-img-width"
              style={{ objectFit: "cover", aspectRatio: "1 / 1" }}
            />
            )}
            <small className="text-truncate w-100 fw-bold categories-text" style={{ color: color || '#6c757d' }}>
              {item.name}
            </small>
          </Link>
        )})}
      </div>

      {/* LARGE SCREENS */}
      <div className="d-none d-lg-flex justify-content-center w-100 position-relative text-nowrap small border-2 border border-white" style={{padding:`${space}`, backgroundColor:`${bg}`}} >
        <div className="d-flex justify-content-between w-100 mx-auto px-lg-4 position-relative" style={{ maxWidth: showImages ? '1440px' : '100%' }}>
          {safeCategories.slice(0, 12).map((item, index) => {
            const itemChildren = getArray(item.children);
            return (
          <div
            key={index}
            className="d-flex flex-column align-items-center text-center mb-0 over"
            onMouseEnter={() => {
              setActiveCategory(index);
              setActiveSub(0);
            }}
            onMouseLeave={() => {
              setActiveCategory(null);
              setActiveSub(null);
            }}
            style={{ width: showImages ? "70px" : "auto", cursor: "pointer", padding: showImages ? "0" : "0 15px" }}
          >
            <Link to={`/category/${item.slug}`} className="d-flex flex-column align-items-center text-decoration-none w-100">
              {showImages && (
              <img
                src={getImageUrl(item.image)}
                alt={item.name}
                className="rounded mb-1 categoires-img-width"
                style={{ objectFit: "cover", aspectRatio: "1 / 1" }}
              />
              )}
              <div className="d-flex justify-content-center align-items-center text-decoration-none text-truncate over">
                <span className="fw-semibold" style={{color:`${color}`}}>{item.name}</span>
                <IoIosArrowDown
                  className={`ms-1 transition-arrow ${activeCategory === index ? "rotate-arrow" : ""
                    }`} style={{color:`${color}`}}
                />
              </div>
            </Link>

            {/* MEGA MENU DROPDOWN */}
            {activeCategory === index && itemChildren && itemChildren.length > 0 && (
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
                  {itemChildren.map((sub, i) => {
                    const subChildren = getArray(sub.children);
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

                {/* Promotional Image Section */}
                <div className="d-flex flex-column gap-3 ms-4 ps-4 border-start" style={{ minWidth: "250px", maxWidth: "280px" }}>
                  <img 
                    src={getImageUrl(item.image)} 
                    alt="Promo" 
                    className="w-100 rounded shadow-sm" 
                    style={{ objectFit: "cover", height: index % 2 === 0 ? "100%" : "180px", minHeight: "180px" }} 
                  />
                  {index % 2 !== 0 && (
                    <img 
                      src={getImageUrl(item.image)} 
                      alt="Promo 2" 
                      className="w-100 rounded shadow-sm" 
                      style={{ objectFit: "cover", height: "180px" }} 
                    />
                  )}
                </div>
              </div>
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
