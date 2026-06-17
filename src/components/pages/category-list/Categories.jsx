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
        {categoriesData.map((item, index) => (
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
              style={{ objectFit: "cover" }}
            />
            )}
            <small className="text-truncate w-100 fw-bold categories-text" style={{ color: color || '#6c757d' }}>
              {item.name}
            </small>
          </Link>
        ))}
      </div>

      {/* LARGE SCREENS */}
      <div className="d-none d-lg-flex justify-content-center w-100 position-relative text-nowrap small border-2 border border-white" style={{padding:`${space}`, backgroundColor:`${bg}`}} >
        <div className="d-flex justify-content-evenly w-100 mx-auto" style={{ maxWidth: showImages ? '1440px' : '100%' }}>
          {categoriesData.map((item, index) => (
          <div
            key={index}
            className="d-flex flex-column align-items-center text-center mb-0 position-relative over"
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
                style={{ objectFit: "cover" }}
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

            {/* DROPDOWN */}
            {activeCategory === index && item.children && item.children.length > 0 && (
              <div
                className={`position-absolute bg-transparent rounded d-flex dropdown-panel ${activeCategory === index ? "active" : ""
                  }`}
                style={{
                  top: "100%",
                  width: "450px",
                  zIndex: 10,
                  ...(index === 0
                    ? { left: "-55px" }
                    : index === categoriesData.length - 1
                      ? { right: "-65px" }
                      : { left: "50px", transform: "translateX(-50%)" }),
                }}
              >
                {/* LEFT PANEL */}
                <div
                  className="rounded-start categories-shadow bg-white"
                  style={{ width: "50%", overflowY: "auto" }}
                >
                  {item.children.map((sub, i) => (
                    <div
                      key={i}
                      className="px-3 py-2 d-flex justify-content-between align-items-center"
                      style={{
                        cursor: "pointer",
                        backgroundColor: activeSub === i ? "rgb(240, 245, 255)" : "transparent",
                        transition: "background-color 0.3s ease",
                        fontWeight: activeSub === i ? "600" : "400",
                      }}
                      onMouseEnter={() => setActiveSub(i)}
                    >
                      <Link to={`/category/${sub.slug}`} className="text-decoration-none small text-dark d-block w-100 text-start">{sub.name}</Link>
                      <IoIosArrowForward />
                    </div>
                  ))}
                </div>

                {/* RIGHT PANEL */}
                <div
                  className="flex-column d-flex align-items-start justify-content-start border rounded categories-shadow bg-white"
                  style={{
                    width: "50%",
                    overflowY: "auto",
                    marginLeft: "-10px",
                    zIndex: 5,
                  }}
                >
                  {item.children[activeSub] && (
                    <>
                      <div className="px-3 py-2 small text-black fw-semibold">
                        More in {item.children[activeSub].name}
                      </div>
                      {item.children[activeSub].children?.map((m, idx) => (
                        <Link
                          key={idx}
                          to={`/category/${m.slug}`}
                          className="d-block px-3 py-2 text-decoration-none hover-bg-light w-100 d-flex justify-content-start align-items-start text-dark"
                        >
                          {m.name}
                        </Link>
                      ))}
                    </>
                  )}
                </div>
              </div>
            )}
          </div>
        ))}
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
