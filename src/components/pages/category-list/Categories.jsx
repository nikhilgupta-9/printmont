import React, { useEffect, useState } from "react";
import { TbCategory2 } from "react-icons/tb";
import { IoIosArrowDown } from "react-icons/io";
import { Link } from "react-router-dom";
import { categoriesData } from "../../../../data/data";

const Categories = () => {
  const [show, setShow] = useState(true);
  const [lastScrollY, setLastScrollY] = useState(0);

  useEffect(() => {
    const handleScroll = () => {
      // Only apply scroll logic on large screens
      if (window.innerWidth >= 992) {
        const currentScrollY = window.scrollY;

        if (currentScrollY > lastScrollY && currentScrollY > 150) {
          // Scrolling down → hide
          setShow(false);
        } else if (currentScrollY < lastScrollY - 20) {
          // Scrolling up → show
          setShow(true);
        }

        setLastScrollY(currentScrollY);
      } else {
        // On small screens, always visible
        setShow(true);
      }
    };

    window.addEventListener("scroll", handleScroll, { passive: true });
    return () => window.removeEventListener("scroll", handleScroll);
  }, [lastScrollY]);

  return (
    <>
      <div
        className="container-fluid bg-transparent px-0 py-0 pb-0 m-0 shadow-sm start-0 w-100 categories-main"
        style={{
          transform:
            show || window.innerWidth < 992
              ? "translateY(0)"
              : "translateY(-100%)",
          transition: "transform 0.4s ease-in-out",
          zIndex: 2,
        }}
      >
        {/* SMALL SCREENS */}
        <div className="d-flex d-lg-none overflow-x-auto gap-2 px-0 align-items-center hide-scrollbar bg-white">
          <div
            className="d-flex flex-column align-items-center justify-content-around text-center flex-shrink-0 bg-white p-1 border-end border border-white me-1"
            style={{
              width: "75px",
              height: "75px",
              boxShadow: "2px 0 5px rgba(0,0,0,0.1)",
            }}
          >
            <TbCategory2 size={24} className="mb-1 text-primary" />
            <small className="fw-semibold text-primary">Category</small>
          </div>

          {categoriesData.map((item, index) => (
            <div
              key={index}
              className="d-flex flex-column align-items-center text-center flex-shrink-0 p-1 categoires-cont-width"
              
            >
              <img
                src={item.img}
                alt={item.name}
                className="rounded mb-1 border categoires-img-width"
                style={{
                 
                  objectFit: "cover",
                }}
              />
              <small className="txex text-truncate w-100 fw-bold text-muted">
                {item.name}
              </small>
            </div>
          ))}
        </div>

        {/* LARGE SCREENS */}
        <div className="d-none d-lg-flex flex-wrap justify-content-around gap-4 gap-lg-0  gap-xl-3 gap-xxl-4 px-0 bg-white w-100 p-1 px-2">
          {categoriesData.map((item, index) => (
            <div
              key={index}
              className="d-flex flex-column align-items-center text-center mb-0 categoires-cont-width"
              style={{ width: "70px" }}
            >
              <img
                src={item.img}
                alt={item.name}
                className="rounded mb-1 border categoires-img-width"
                style={{
                  // width: "70px",
                  // height: "70px",
                  objectFit: "cover",
                }}
              />
              <Link
                to={item.url}
                className="d-flex justify-content-center align-items-start text-decoration-none"
              >
                <small className="w-100 text-body-secondary txex fw-semibold">
                  {item.name}
                </small>
                <IoIosArrowDown />
              </Link>
            </div>
          ))}
        </div>
      </div>
      <div className="d-none d-lg-flex extra-cont-categories"/>

      {/* spacer to push content below fixed bar */}
    </>
  );
};

export default Categories;
