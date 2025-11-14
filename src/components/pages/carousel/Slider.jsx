import React, { useEffect, useState } from 'react';
import 'swiper/css';
import 'swiper/css/pagination';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Pagination } from 'swiper/modules';

const Slider = ({ apiUrl = '/data/slides.json' }) => {
  const [slides, setSlides] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchSlides = async () => {
      try {
        const response = await fetch(apiUrl);
        if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);

        // Read response as text for debugging
        const text = await response.text();

        // Try to parse JSON
        const data = JSON.parse(text);

        setSlides(data);
      } catch (err) {
        console.error("❌ Fetch Error:", err);
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchSlides();
  }, [apiUrl]);

  if (loading) return <div className="text-center p-5">Loading slider...</div>;
  if (error) return <div className="text-center text-danger p-5">Error: {error}</div>;

  return (
    <Swiper
      pagination={{ clickable: true }}
      modules={[Pagination]}
      className="mySwiper my-1 d-block d-lg-none"
    >
      {slides.map((slide, index) => (
        <SwiperSlide key={index}>
          <img
            src={slide.url}
            alt={`slide-${index + 1}`}
            width="100%"
            className="object-cover-fit"
          />
        </SwiperSlide>
      ))}
    </Swiper>
  );
};

export default Slider;
