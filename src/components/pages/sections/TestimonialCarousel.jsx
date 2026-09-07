import React from "react";
import Slider from "react-slick";
import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import { FaStar, FaQuoteLeft } from "react-icons/fa";

const testimonials = [
  {
    id: 1,
    name: "Amit Singh",
    role: "Verified Buyer",
    text: "Printmont's quality is unmatched. I ordered customized gifts for my team and everyone loved them! The printing is crisp and vibrant.",
    rating: 5,
  },
  {
    id: 2,
    name: "Priya Sharma",
    role: "Verified Buyer",
    text: "I was amazed by how fast my personalized items were delivered. The customer service was incredibly helpful throughout the process.",
    rating: 5,
  },
  {
    id: 3,
    name: "Rahul Verma",
    role: "Verified Buyer",
    text: "Absolutely stunning work! The t-shirt printing exceeded my expectations. The fabric is comfortable and the print hasn't faded after multiple washes.",
    rating: 4,
  },
  {
    id: 4,
    name: "Neha Gupta",
    role: "Verified Buyer",
    text: "I ordered a customized mug for my best friend's birthday. It came out perfectly! Highly recommend Printmont for personalized gifts.",
    rating: 5,
  }
];

const TestimonialCarousel = () => {
  const settings = {
    dots: true,
    infinite: true,
    speed: 500,
    slidesToShow: 3,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 4000,
    arrows: false,
    responsive: [
      {
        breakpoint: 992,
        settings: {
          slidesToShow: 2,
        }
      },
      {
        breakpoint: 768,
        settings: {
          slidesToShow: 1,
        }
      }
    ]
  };

  return (
    <div className="container-fluid py-4" style={{ backgroundColor: "#f8f9fa" }}>
      <div className="mx-auto" style={{ maxWidth: "1440px" }}>
        <div className="text-center mb-4">
          <h3 className="fw-bold text-dark mb-2">What Our Customers Say</h3>
          <p className="text-muted">Real reviews from our happy customers.</p>
        </div>
        
        <div className="px-3 px-md-4">
          <Slider {...settings} className="testimonial-slider">
            {testimonials.map((item) => (
              <div key={item.id} className="p-2">
                <div 
                  className="bg-white p-4 rounded shadow-sm d-flex flex-column h-100"
                  style={{ border: "1px solid #eaeaea", minHeight: "220px" }}
                >
                  <div className="mb-3 text-warning">
                    {[...Array(5)].map((_, i) => (
                      <FaStar key={i} color={i < item.rating ? "#ffc107" : "#e4e5e9"} />
                    ))}
                  </div>
                  <FaQuoteLeft className="text-muted opacity-25 mb-2" size={24} />
                  <p className="text-dark fst-italic mb-4" style={{ fontSize: "0.95rem", flexGrow: 1 }}>
                    "{item.text}"
                  </p>
                  <div className="mt-auto border-top pt-3">
                    <h6 className="fw-bold mb-0 text-primary">{item.name}</h6>
                    <small className="text-muted">{item.role}</small>
                  </div>
                </div>
              </div>
            ))}
          </Slider>
        </div>
      </div>
    </div>
  );
};

export default TestimonialCarousel;
