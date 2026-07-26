import React from 'react';
import {
  FormatDesignBanner,
  BannerGrid,
  MultiColumnBannerCarousel,
  ResponsiveBannerSet,
  ResponsiveHeroCarousel,
  MobileHeroSlider,
  StaticResponsiveBanner,
  OneAndHalfCarousel,
  SquareSingleImageMobileSlider,
  SmallHeightStaticBanner,
  SmallHeightCarouselSlider,
} from '../home';

const sampleBanners = [
  {
    large: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=1600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=800&h=400&q=80',
    title: 'Offer Banner 1',
    alt: 'Offer Banner 1',
    target: '/allproducts?q=shirt',
  },
  {
    large: 'https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=1600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=800&h=400&q=80',
    title: 'Offer Banner 2',
    alt: 'Offer Banner 2',
    target: '/allproducts?category=Fashion',
  },
  {
    large: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=1600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&h=400&q=80',
    title: 'Offer Banner 3',
    alt: 'Offer Banner 3',
    target: '/allproducts?category=Electronics',
  },
  {
    large: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&h=400&q=80',
    title: 'Offer Banner 4',
    alt: 'Offer Banner 4',
    target: '/allproducts',
  },
  {
    large: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&h=400&q=80',
    title: 'Offer Banner 5',
    alt: 'Offer Banner 5',
    target: '/allproducts',
  },
  {
    large: 'https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&w=1600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&w=800&h=400&q=80',
    title: 'Offer Banner 6',
    alt: 'Offer Banner 6',
    target: '/allproducts',
  },
];

const sampleSquareBanners = [
  {
    large: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=600&h=600&q=80',
    title: 'Square Banner 1',
    alt: 'Square Banner 1',
    target: '/allproducts',
  },
  {
    large: 'https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=600&h=600&q=80',
    title: 'Square Banner 2',
    alt: 'Square Banner 2',
    target: '/allproducts',
  },
  {
    large: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=600&h=600&q=80',
    title: 'Square Banner 3',
    alt: 'Square Banner 3',
    target: '/allproducts',
  },
  {
    large: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&h=600&q=80',
    title: 'Square Banner 4',
    alt: 'Square Banner 4',
    target: '/allproducts',
  },
  {
    large: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&h=600&q=80',
    title: 'Square Banner 5',
    alt: 'Square Banner 5',
    target: '/allproducts',
  },
  {
    large: 'https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&w=600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&w=600&h=600&q=80',
    title: 'Square Banner 6',
    alt: 'Square Banner 6',
    target: '/allproducts',
  },
];

export default function BannerPreview() {
  return (
    <div className="w-100 min-vh-100 bg-white p-0 m-0 overflow-x-hidden">
      {/* Header Banner */}
      <div className="bg-dark text-white text-center py-4 px-3 mb-4 w-100">
        <h1 className="fw-bold mb-2">📌 Banner Layouts Showcase</h1>
        <p className="text-light fs-5 mb-1">
          Top Section: Static Normal Banners | End Section: All Carousel Sliders
        </p>
        <span className="badge bg-danger fs-6 px-3 py-1">Route: /banner-preview</span>
      </div>

      {/* ========================================================= */}
      {/* TOP SECTION: ALL NORMAL / STATIC BANNERS */}
      {/* ========================================================= */}
      <div className="w-100 mb-5">
        <div className="bg-danger text-white p-3 mb-4 w-100 text-center shadow-sm">
          <h2 className="fw-bold m-0 fs-3">📷 PART 1 (TOP): ALL NORMAL STATIC BANNERS</h2>
        </div>

        {/* 1. Single Square Offer Banner */}
        <div className="w-100 mb-5">
          <div className="bg-dark text-white py-2 px-3 fw-bold mb-3 d-flex justify-content-between align-items-center">
            <span>1. Single Square Offer Banner (1:1 Ratio)</span>
            <small className="font-monospace text-warning">FormatDesignBanner (format="desktop_format_1")</small>
          </div>
          <FormatDesignBanner format="desktop_format_1" banners={sampleSquareBanners.slice(0, 1)} />
        </div>

        {/* 2. 2 Square Offer Banners */}
        <div className="w-100 mb-5">
          <div className="bg-dark text-white py-2 px-3 fw-bold mb-3 d-flex justify-content-between align-items-center">
            <span>2. 2 Square Offer Banners (Side-by-Side 50/50 Grid)</span>
            <small className="font-monospace text-warning">BannerGrid (columns=2)</small>
          </div>
          <BannerGrid banners={sampleSquareBanners.slice(0, 2)} columns={2} mobileColumns={2} />
        </div>

        {/* 3. 6-Square Grid (3x2 Layout) */}
        <div className="w-100 mb-5">
          <div className="bg-dark text-white py-2 px-3 fw-bold mb-3 d-flex justify-content-between align-items-center">
            <span>3. 6 Square Offer Banner Grid (3 Columns x 2 Rows)</span>
            <small className="font-monospace text-warning">FormatDesignBanner (format="desktop_format_6")</small>
          </div>
          <FormatDesignBanner format="desktop_format_6" banners={sampleSquareBanners} />
        </div>

        {/* 4. 6-Square Grid (2x3 Layout) */}
        <div className="w-100 mb-5">
          <div className="bg-dark text-white py-2 px-3 fw-bold mb-3 d-flex justify-content-between align-items-center">
            <span>4. 6 Square Offer Banner Grid (2 Columns x 3 Rows Vertical Stack)</span>
            <small className="font-monospace text-warning">FormatDesignBanner (format="desktop_format_4")</small>
          </div>
          <FormatDesignBanner format="desktop_format_4" banners={sampleSquareBanners} />
        </div>

        {/* 5. Offer Banner (Wide Widescreen Banner) */}
        <div className="w-100 mb-5">
          <div className="bg-dark text-white py-2 px-3 fw-bold mb-3 d-flex justify-content-between align-items-center">
            <span>5. 2 Format Design: Offer Banner (Full Width Wide Widescreen Banner)</span>
            <small className="font-monospace text-warning">StaticResponsiveBanner</small>
          </div>
          <StaticResponsiveBanner />
        </div>

        {/* 6. Small Offer Banner Strip */}
        <div className="w-100 mb-5">
          <div className="bg-dark text-white py-2 px-3 fw-bold mb-3 d-flex justify-content-between align-items-center">
            <span>6. 5 Format Design: Small Offer Banner Strip (Slim Widescreen Offer Strip)</span>
            <small className="font-monospace text-warning">SmallHeightStaticBanner</small>
          </div>
          <SmallHeightStaticBanner banners={sampleBanners.slice(0, 1)} maxHeight="85px" />
        </div>
      </div>

      {/* ========================================================= */}
      {/* END SECTION: ALL CAROUSEL / SLIDER BANNERS */}
      {/* ========================================================= */}
      <div className="w-100 mb-5">
        <div className="bg-primary text-white p-3 mb-4 w-100 text-center shadow-sm">
          <h2 className="fw-bold m-0 fs-3">🎠 PART 2 (END): ALL CAROUSEL & SLIDER BANNERS</h2>
        </div>

        {/* 7. Single Square Offer Banner Slider */}
        <div className="w-100 mb-5">
          <div className="bg-dark text-white py-2 px-3 fw-bold mb-3 d-flex justify-content-between align-items-center">
            <span>7. Square Size Offer Banner Slider (Single Square Touch Slider)</span>
            <small className="font-monospace text-warning">SquareSingleImageMobileSlider</small>
          </div>
          <SquareSingleImageMobileSlider banners={sampleSquareBanners} isMobileOnly={false} />
        </div>

        {/* 8. 2-Square Banner Slider Carousel */}
        <div className="w-100 mb-5">
          <div className="bg-dark text-white py-2 px-3 fw-bold mb-3 d-flex justify-content-between align-items-center">
            <span>8. 2 Format Design: Square Size Banner Slider Carousel (2 Columns Slider)</span>
            <small className="font-monospace text-warning">MultiColumnBannerCarousel (columns=2)</small>
          </div>
          <MultiColumnBannerCarousel banners={sampleSquareBanners} columns={2} />
        </div>

        {/* 9. Banner Slider Carousel (Full Width Slider) */}
        <div className="w-100 mb-5">
          <div className="bg-dark text-white py-2 px-3 fw-bold mb-3 d-flex justify-content-between align-items-center">
            <span>9. 2 Format Design: Banner Slider Carousel (Full Width Hero Slider)</span>
            <small className="font-monospace text-warning">ResponsiveHeroCarousel</small>
          </div>
          <ResponsiveHeroCarousel banners={sampleBanners} />
        </div>

        {/* 10. 4 Format Design: 1.5 Card Partial Banner Slider */}
        <div className="w-100 mb-5">
          <div className="bg-dark text-white py-2 px-3 fw-bold mb-3 d-flex justify-content-between align-items-center">
            <span>10. 4 Format Design: Banner Slider Carousel + Partial Next Card Preview (70/30 Slider)</span>
            <small className="font-monospace text-warning">OneAndHalfCarousel</small>
          </div>
          <OneAndHalfCarousel banners={sampleBanners} isMobileOnly={false} />
        </div>

        {/* 11. 5 Format Design: Small Banner Carousel */}
        <div className="w-100 mb-5">
          <div className="bg-dark text-white py-2 px-3 fw-bold mb-3 d-flex justify-content-between align-items-center">
            <span>11. 5 Format Design: Small Banner Carousel (Slim Widescreen Slider)</span>
            <small className="font-monospace text-warning">SmallHeightCarouselSlider</small>
          </div>
          <SmallHeightCarouselSlider banners={sampleBanners} maxHeight="85px" />
        </div>
      </div>
    </div>
  );
}
