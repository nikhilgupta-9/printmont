import React, { useEffect, useState } from "react";
import { TbCategory2 } from "react-icons/tb";
import { IoIosArrowDown, IoIosArrowForward } from "react-icons/io";
import { Link } from "react-router-dom";
import { categoriesData } from "../../../../data/categoriesdata";

const Categories = ({ showImages = true, space="", color = '', bg = '' }) => {
  const [showimg, setShowimg] = useState(true);
  const [show, setShow] = useState(true);
  const [lastScrollY, setLastScrollY] = useState(0);
  const [activeCategory, setActiveCategory] = useState(null);
  const [activeSub, setActiveSub] = useState(null);

  useEffect(() => {
    const handleScroll = () => {
      if (window.innerWidth >= 992) {
        const currentScrollY = window.scrollY;
        if (currentScrollY > lastScrollY && currentScrollY > 150) setShow(false);
        else if (currentScrollY < lastScrollY - 20) setShow(true);
        setLastScrollY(currentScrollY);
      } else setShow(true);
    };

    window.addEventListener("scroll", handleScroll, { passive: true });
    return () => window.removeEventListener("scroll", handleScroll);
  }, [lastScrollY]);

  

  return (
    <div
      className="container-fluid bg-transparent px-0 py-0 pb-0 m-0 mx-0 shadow-sm start-0 w-100 categories-main"
      style={{
        transform:
          show || window.innerWidth < 992
            ? "translateY(0)"
            : "translateY(-100%)",
        transition: "transform 0.4s ease-in-out",
        zIndex: 5,
      }}
    >
      {/* SMALL SCREENS */}
      <div className="d-flex d-lg-none overflow-x-auto gap-2 px-2 align-items-center hide-scrollbar bg-white" style={{padding:`${space}`}}>
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
              src={item.img}
              alt={item.name}
              className="rounded mb-1 border categoires-img-width"
              style={{ objectFit: "cover" }}
            />
            )}
            <small className="text-truncate w-100 fw-bold text-muted categories-text">
              {item.name}
            </small>
          </div>
        ))}
      </div>

      {/* LARGE SCREENS */}
      <div className="d-none d-lg-flex justify-content-evenly w-100 position-relative text-nowrap small border-2 border border-white" style={{padding:`${space}`, backgroundColor:`${bg}`}} >
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
            style={{ width: "70px", cursor: "pointer" }}
          >
            {showImages && (
            <img
              src={item.img}
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
  );
};

export default Categories;
