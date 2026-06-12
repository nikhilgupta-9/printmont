import React, { useEffect, useLayoutEffect, useRef, useState } from "react";
import { TbCategory2 } from "react-icons/tb";
import { IoIosArrowDown, IoIosArrowForward } from "react-icons/io";
import { Link } from "react-router-dom";
import { categoriesData } from "../../../../data/categoriesdata";

const Categories = ({ showImages = true, space="", color = '', bg = '', isSticky = false }) => {
  const [show, setShow] = useState(true);
  const [categoryHeight, setCategoryHeight] = useState(0);
  const [activeCategory, setActiveCategory] = useState(null);
  const [activeSub, setActiveSub] = useState(null);
  const categoryRef = useRef(null);
  const lastScrollYRef = useRef(0);
  const frameRef = useRef(null);

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
  }, [isSticky, showImages, space]);

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
        {/* <div
          className="d-flex flex-column align-items-center justify-content-around text-center flex-shrink-0 bg-white p-1 border-end border border-white me-1"
          style={{
            width: "75px",
            height: "75px",
            boxShadow: "2px 0 5px rgba(0,0,0,0.1)",
          }}
        >
          <TbCategory2 size={24} className="mb-1 text-primary" />
          <small className="fw-semibold text-primary">Category</small>
        </div> */}

        {categoriesData.map((item, index) => (
          <div
            key={index}
            className="d-flex flex-column align-items-center text-center flex-shrink-0 p-1 categoires-cont-width"
          >
            {showImages && (
            <img
              src={item.img && item.img.startsWith('./') ? item.img.substring(1) : item.img}
              alt={item.name}
              className="rounded mb-1 border categoires-img-width"
              style={{ objectFit: "cover" }}
            />
            )}
            <small className="text-truncate w-100 fw-bold categories-text" style={{ color: color || '#6c757d' }}>
              {item.name}
            </small>
          </div>
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
            {showImages && (
            <img
              src={item.img && item.img.startsWith('./') ? item.img.substring(1) : item.img}
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

            {/* DROPDOWN */}
            {activeCategory === index && item.subCategories && (
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
                  {item.subCategories.map((sub, i) => (
                    <div
                      key={i}
                      className="px-3 py-2 d-flex justify-content-between align-items-center"
                      style={{
                        cursor: "pointer",
                        backgroundColor: activeSub === i ? "rgb(240, 245, 255)" : "transparent", // 🔴 background
                        transition: "background-color 0.3s ease",
                        fontWeight: activeSub === i ? "600" : "400", // ✅ bold text when active
                      }}
                      onMouseEnter={() => setActiveSub(i)}
                    >
                      <span className="small text-dark">{sub.name}</span>
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
                  {item.subCategories[activeSub] && (
                    <>
                      <div className="px-3 py-2 small text-black fw-semibold">
                        More in {item.subCategories[activeSub].name}
                      </div>
                      {item.subCategories[activeSub].more?.map((m, idx) => (
                        <Link
                          key={idx}
                          to={m.url}
                          className="d-block px-3 py-2 text-decoration-none hover-bg-light w-100 d-flex justify-content-start align-items-start"
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
