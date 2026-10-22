import React, { useState, useEffect } from 'react';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, Pagination, Navigation } from 'swiper/modules';
import { Link } from 'react-router-dom';
import { BASE_URL, ASSET_URL, assetUrl } from '../../config/apiEndpoints';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';
import './OrdersBannerCarousel.css';

/**
 * OrdersBannerCarousel - Displays the dynamic banner carousel for the Orders page
 * configured via Admin Banner Management (page='orders' / section='orders_page_banner').
 */
const OrdersBannerCarousel = ({ isMobile = false }) => {
  const [banners, setBanners] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    const fetchBanners = async () => {
      try {
        const res = await fetch(`${BASE_URL}/banner_api.php?page=orders`);
        const data = await res.json();
        if (isMounted && data && data.success && data.data) {
          const sections = data.data.sections || {};
          let allBanners = [];
          
          // Check for orders_page_banner section or any section on orders page
          Object.values(sections).forEach(sec => {
            if (sec && Array.isArray(sec.banners)) {
              allBanners.push(...sec.banners);
            }
          });

          if (allBanners.length > 0) {
            setBanners(allBanners);
          } else {
            // Check fallback by section directly
            const secRes = await fetch(`${BASE_URL}/banner_api.php?section=orders_page_banner`);
            const secData = await secRes.json();
            if (isMounted && secData && secData.success && secData.data?.banners?.length > 0) {
              setBanners(secData.data.banners);
            }
          }
        }
      } catch (err) {
        console.warn('OrdersBannerCarousel: error loading banners, using fallback', err);
      } finally {
        if (isMounted) setLoading(false);
      }
    };

    fetchBanners();
    return () => { isMounted = false; };
  }, []);

  // Normalize image url helper
  const getBannerImage = (b) => {
    if (!b) return null;
    let img = isMobile
      ? (b.images?.mobile || b.image_url_mobile || b.images?.desktop || b.image_url_desktop || b.image)
      : (b.images?.desktop || b.image_url_desktop || b.images?.mobile || b.image_url_mobile || b.image);
    
    if (!img) return null;
    if (img.startsWith('http://') || img.startsWith('https://')) return img;
    const cleanImg = img.replace(/^\//, '');
    return assetUrl(cleanImg);
  };

  // Fallback banners if none configured yet in admin
  const displayList = banners.length > 0 ? banners : [
    {
      id: 'fb-1',
      title: 'Exclusive Corporate Orders & Custom Printing',
      description: 'Get special bulk discounts on customized mugs, t-shirts, diaries and gift sets.',
      target_url: '/allproducts',
      image_url_desktop: 'uploads/banners/desktop/banner_row3_single_promo.jpg',
      image_url_mobile: 'uploads/banners/mobile/banner_row3_single_promo.jpg'
    }
  ];

  return (
    <div className="orders-banner-carousel-wrapper">
      <div className="orders-banner-card">
        {loading ? (
          <div className="orders-banner-shimmer shimmer-bg"></div>
        ) : (
          <Swiper
            modules={[Autoplay, Pagination, Navigation]}
            autoplay={{ delay: 3500, disableOnInteraction: false }}
            pagination={{ clickable: true }}
            navigation={displayList.length > 1}
            loop={displayList.length > 1}
            className="orders-banner-swiper"
          >
            {displayList.map((banner, idx) => {
              const imgSrc = getBannerImage(banner);
              const targetUrl = banner.target_url || banner.url || '/allproducts';
              const isExternal = targetUrl.startsWith('http://') || targetUrl.startsWith('https://');

              const content = (
                <div className="orders-banner-slide-inner">
                  {imgSrc ? (
                    <img
                      src={imgSrc}
                      alt={banner.title || 'Orders Promo Banner'}
                      className="orders-banner-img"
                      onError={(e) => {
                        e.target.onerror = null;
                        e.target.style.display = 'none';
                        e.target.parentElement.classList.add('orders-banner-fallback-bg');
                      }}
                    />
                  ) : null}
                  <div className="orders-banner-overlay">
                    <span className="orders-banner-title">{banner.title}</span>
                    {banner.description && (
                      <span className="orders-banner-sub">{banner.description}</span>
                    )}
                  </div>
                </div>
              );

              return (
                <SwiperSlide key={banner.id || idx}>
                  {isExternal ? (
                    <a href={targetUrl} target="_blank" rel="noopener noreferrer" className="orders-banner-link">
                      {content}
                    </a>
                  ) : (
                    <Link to={targetUrl} className="orders-banner-link">
                      {content}
                    </Link>
                  )}
                </SwiperSlide>
              );
            })}
          </Swiper>
        )}
      </div>
    </div>
  );
};

export default OrdersBannerCarousel;
