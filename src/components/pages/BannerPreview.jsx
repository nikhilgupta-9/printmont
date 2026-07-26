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
} from '../home';

const sampleBanners = [
  {
    large: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=1600&q=80',
    small: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=800&q=80',
    title: 'Offer Banner 1',
    alt: 'Offer Banner 1',
    target: '/allproducts?q=shirt',
  },
  {
    large: 'https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=1600&q=80',
    small: 'https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=800&q=80',
    title: 'Offer Banner 2',
    alt: 'Offer Banner 2',
    target: '/allproducts?category=Fashion',
  },
  {
    large: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=1600&q=80',
    small: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',
    title: 'Offer Banner 3',
    alt: 'Offer Banner 3',
    target: '/allproducts?category=Electronics',
  },
  {
    large: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1600&q=80',
    small: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
    title: 'Offer Banner 4',
    alt: 'Offer Banner 4',
    target: '/allproducts',
  },
  {
    large: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1600&q=80',
    small: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80',
    title: 'Offer Banner 5',
    alt: 'Offer Banner 5',
    target: '/allproducts',
  },
  {
    large: 'https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&w=1600&q=80',
    small: 'https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&w=800&q=80',
    title: 'Offer Banner 6',
    alt: 'Offer Banner 6',
    target: '/allproducts',
  },
];

export default function BannerPreview() {
  return (
    <div className="w-100 min-vh-100 bg-white px-0 mx-0 overflow-x-hidden">
      {/* Top Banner Header */}
      <div className="bg-dark text-white text-center py-4 px-3 mb-4 w-100">
        <h1 className="fw-bold mb-2">🖥️ 100% Full Screen Banner & Slider Layout Preview</h1>
        <p className="text-light fs-5 mb-2">
          Full Width Edge-to-Edge display of all Banner Formats (Image 1 & Image 2 Diagrams)
        </p>
        <span className="badge bg-danger fs-6 px-3 py-2">Full Screen View: /banner-preview</span>
      </div>

      {/* ========================================================= */}
      {/* SECTION 1: STATIC BANNERS (IMAGE 1 DIAGRAM) */}
      {/* ========================================================= */}
      <div className="w-100 mb-5 px-2 px-md-4">
        <div className="bg-danger text-white p-3 rounded mb-4">
          <h2 className="fw-bold m-0">📷 IMAGE 1: STATIC BANNER LAYOUTS (Full Screen)</h2>
        </div>

        {/* 1. 6-Square Grid (3x2 Layout) */}
        <div className="mb-5 border p-3 bg-white rounded shadow-sm">
          <h4 className="fw-bold text-dark border-bottom pb-2">
            1. 6 Square Offer Banner Grid (3 Columns x 2 Rows)
          </h4>
          <p className="text-muted small">Component: <code>FormatDesignBanner (desktop_format_6)</code></p>
          <FormatDesignBanner format="desktop_format_6" banners={sampleBanners} />
        </div>

        {/* 2. 6-Square Grid (2x3 Layout) */}
        <div className="mb-5 border p-3 bg-white rounded shadow-sm">
          <h4 className="fw-bold text-dark border-bottom pb-2">
            2. 6 Square Offer Banner Grid (2 Columns x 3 Rows Vertical Stack)
          </h4>
          <p className="text-muted small">Component: <code>FormatDesignBanner (desktop_format_4)</code></p>
          <FormatDesignBanner format="desktop_format_4" banners={sampleBanners} />
        </div>

        {/* 3. 2 Square Offer Banners */}
        <div className="mb-5 border p-3 bg-white rounded shadow-sm">
          <h4 className="fw-bold text-dark border-bottom pb-2">
            3. 2 Square Offer Banners (Side-by-Side 50/50 Grid)
          </h4>
          <p className="text-muted small">Component: <code>BannerGrid (columns=2)</code></p>
          <BannerGrid banners={sampleBanners.slice(0, 2)} columns={2} mobileColumns={2} />
        </div>

        {/* 4. Single Square Offer Banner */}
        <div className="mb-5 border p-3 bg-white rounded shadow-sm">
          <h4 className="fw-bold text-dark border-bottom pb-2">
            4. Single Square Offer Banner (1:1 Ratio)
          </h4>
          <p className="text-muted small">Component: <code>FormatDesignBanner (desktop_format_1)</code></p>
          <FormatDesignBanner format="desktop_format_1" banners={sampleBanners.slice(0, 1)} />
        </div>

        {/* 5. Offer Banner (Wide Widescreen Banner) */}
        <div className="mb-5 border p-3 bg-white rounded shadow-sm">
          <h4 className="fw-bold text-dark border-bottom pb-2">
            5. Offer Banner (Full Width Wide Widescreen Banner)
          </h4>
          <p className="text-muted small">Component: <code>StaticResponsiveBanner</code></p>
          <StaticResponsiveBanner />
        </div>

        {/* 6. Small Offer Banner Strip */}
        <div className="mb-5 border p-3 bg-white rounded shadow-sm">
          <h4 className="fw-bold text-dark border-bottom pb-2">
            6. Small Offer Banner Strip (Slim Widescreen Offer Strip)
          </h4>
          <p className="text-muted small">Component: <code>FormatDesignBanner (desktop_format_5)</code></p>
          <FormatDesignBanner format="desktop_format_5" banners={sampleBanners.slice(0, 1)} />
        </div>
      </div>

      {/* ========================================================= */}
      {/* SECTION 2: CAROUSEL & SLIDER BANNERS (IMAGE 2 DIAGRAM) */}
      {/* ========================================================= */}
      <div className="w-100 mb-5 px-2 px-md-4">
        <div className="bg-primary text-white p-3 rounded mb-4">
          <h2 className="fw-bold m-0">🎠 IMAGE 2: CAROUSEL & SLIDER BANNER LAYOUTS (Full Screen)</h2>
        </div>

        {/* 7. 2-Square Banner Slider Carousel */}
        <div className="mb-5 border p-3 bg-white rounded shadow-sm">
          <h4 className="fw-bold text-dark border-bottom pb-2">
            7. 2 Format Design: Square Size Banner Slider Carousel (2 Columns Slider)
          </h4>
          <p className="text-muted small">Component: <code>MultiColumnBannerCarousel (columns=2)</code></p>
          <MultiColumnBannerCarousel banners={sampleBanners} columns={2} />
        </div>

        {/* 8. Single Square Offer Banner Slider */}
        <div className="mb-5 border p-3 bg-white rounded shadow-sm">
          <h4 className="fw-bold text-dark border-bottom pb-2">
            8. Square Size Offer Banner Slider (Single Square Touch Slider)
          </h4>
          <p className="text-muted small">Component: <code>MobileHeroSlider (slidesPerView=1)</code></p>
          <MobileHeroSlider banners={sampleBanners} slidesPerView={1} isMobileOnly={false} />
        </div>

        {/* 9. Banner Slider Carousel (Full Width Slider) */}
        <div className="mb-5 border p-3 bg-white rounded shadow-sm">
          <h4 className="fw-bold text-dark border-bottom pb-2">
            9. Banner Slider Carousel (Full Width Hero Slider)
          </h4>
          <p className="text-muted small">Component: <code>ResponsiveHeroCarousel</code></p>
          <ResponsiveHeroCarousel banners={sampleBanners} />
        </div>

        {/* 10. 4 Format Design: 1.5 Card Partial Banner Slider */}
        <div className="mb-5 border p-3 bg-white rounded shadow-sm">
          <h4 className="fw-bold text-dark border-bottom pb-2">
            10. 4 Format Design: Banner Slider Carousel + Partial Next Card Preview (70/30 Slider)
          </h4>
          <p className="text-muted small">Component: <code>OneAndHalfCarousel</code></p>
          <OneAndHalfCarousel banners={sampleBanners} isMobileOnly={false} />
        </div>

        {/* 11. 5 Format Design: Small Banner Carousel */}
        <div className="mb-5 border p-3 bg-white rounded shadow-sm">
          <h4 className="fw-bold text-dark border-bottom pb-2">
            11. 5 Format Design: Small Banner Carousel (Slim Widescreen Slider)
          </h4>
          <p className="text-muted small">Component: <code>MobileHeroSlider (slidesPerView=1.3)</code></p>
          <MobileHeroSlider banners={sampleBanners} slidesPerView={1.3} spaceBetween={8} isMobileOnly={false} />
        </div>
      </div>
    </div>
  );
}
