import React from "react";
import "./PersonalizedGifts.css";

const PersonalizedGifts = () => {
  const gifts = [
    {
      id: 1,
      title: "Gifts for Him",
      img: "https://images.unsplash.com/photo-1549465220-1a8b9238cd48?auto=format&fit=crop&w=800&q=80",
      bgClass: "bg-light-blue",
      shapeClass: "shape-leaf-right",
    },
    {
      id: 2,
      title: "Gifts for Her",
      img: "https://images.unsplash.com/photo-1583847268964-b28e50b7ef1c?auto=format&fit=crop&w=800&q=80",
      bgClass: "bg-light-pink",
      shapeClass: "shape-leaf-right",
    },
    {
      id: 3,
      title: "Gifts for Kids",
      img: "https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?auto=format&fit=crop&w=800&q=80",
      bgClass: "bg-light-yellow",
      shapeClass: "shape-leaf-right",
    },
  ];

  return (
    <div className="container-fluid py-4">
      <div className="mx-auto" style={{ maxWidth: "1440px" }}>
        {/* Header */}
        <div className="mb-4">
          <h3 className="fw-bold text-dark mb-1" style={{ fontSize: "22px" }}>
            Personalized Gifts for All!
          </h3>
          <p className="text-muted mb-0" style={{ fontSize: "14px" }}>
            Thoughtful personalized gifts for boyfriend, girlfriend, kids & others!
          </p>
        </div>

        {/* Grid */}
        <div className="row g-3">
          {gifts.map((gift) => (
            <div key={gift.id} className="col-12 col-md-4">
              <div
                className={`pg-card-wrapper h-100 ${gift.bgClass} ${gift.shapeClass} d-flex flex-column`}
              >
                <div className={`pg-img-container ${gift.shapeClass}`}>
                  <img
                    src={gift.img}
                    alt={gift.title}
                    className="pg-img w-100 h-100 object-fit-cover"
                  />
                </div>
                <div className="pg-title-container text-center mt-3 mb-1">
                  <h5 className="fw-bold text-dark m-0">{gift.title}</h5>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default PersonalizedGifts;
