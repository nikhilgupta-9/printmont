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

        const data = await response.json();
        
        let slideList = [];
        if (data && data.success && data.data) {
          if (data.data.home_hero && data.data.home_hero.banners) {
            slideList = data.data.home_hero.banners;
          } else if (Array.isArray(data.data)) {
            slideList = data.data;
          }
        } else if (Array.isArray(data)) {
          slideList = data;
        }

        const formattedSlides = slideList.map((item) => {
          if (item.url) return item;
          let imgUrl = '';
          if (item.images) {
            imgUrl = item.images.mobile || item.images.desktop || '';
          } else {
            imgUrl = item.image_url_mobile || item.image_url_desktop || '';
          }
          return {
            url: imgUrl,
            target: item.target_url || item.target || '#'
          };
        });

        setSlides(formattedSlides);
      } catch (err) {
        console.error("❌ Fetch Error:", err);
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchSlides();
  }, [apiUrl]);

  if (loading) {
    return (
      <div className="d-block d-lg-none px-2">
        <div className="shimmer-bg skeleton-slider-mobile w-100"></div>
      </div>
    );
  }
  if (error) return <div className="text-center text-danger p-5">Error: {error}</div>;

  return (
    <Swiper
      pagination={{ clickable: true }}
      modules={[Pagination]}
      className="mySwiper d-block d-lg-none"
    >
      {slides.map((slide, index) => (
        <SwiperSlide key={index}>
          <img
            src={slide.url}
            alt={`slide-${index + 1}`}
            width="100%"
            className="carousel-img object-cover-fit"
          />
        </SwiperSlide>
      ))}
    </Swiper>
  );
};

export default Slider;
