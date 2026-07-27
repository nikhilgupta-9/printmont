import React from "react";
import { Link } from "react-router-dom";
import { ROOT_URL } from "../../../config/apiEndpoints";

// ── Migrated homepage components ──────────
import {
  ResponsiveHeroCarousel,
  ProductCarousel,
  MultiColumnBannerCarousel,
  ResponsiveBannerSet,
  MobileProductList
} from "../../home";
import TwoImgCarousel   from "../carousel/TwoImgCarousel";
import BannerSmall      from "../sections/BannerSmall";
import BeforeAfterSlider   from "./BeforeAfterSlider";
import CategoryGridSection  from "./CategoryGridSection";
import MobileBannerCarousel from "./MobileBannerCarousel";
import PersonalizedGifts  from "./PersonalizedGifts";
import StatsBanner        from "../sections/StatsBanner";
import TestimonialCarousel from "../sections/TestimonialCarousel";

// ── Static data ───────────────────────────────────────────────────
import {
  categoryPageData,
  newArrivalProducts, bestSellerProducts, specialOfferProducts,
  menSubcategories, womenSubcategories, footwearSubcategories, kidsSubcategories,
  menOfferProducts, womenOfferProducts, footwearOfferProducts, kidsOfferProducts,
  mobileCategoryLinks, categoryReviews, heroBanners,
} from "../../../../data/categoryPageData";
import { categoriesData } from "../../../../data/categoriesdata";
import { sampleItems } from "../../../../data/data";

import "./CategoryPage.css";

/* ─────────────────────────────────────────────────────────────────
   SUB-COMPONENT: Mobile 3-col Category Grid  (Image 1 style)
   Props:
     title      – section heading e.g. "Men's Clothing"
     items      – array of { name, img, url }
     maxItems   – how many to show (default 9)
───────────────────────────────────────────────────────────────── */
const MobileCategoryGrid = ({ title, items = [], maxItems = 9 }) => {
  const visible = items.slice(0, maxItems);
  return (
    <div className="mob-cat-grid-section bg-white pt-3 pb-2 px-2">
      {title && (
        <h2 className="mob-cat-grid-title">{title}</h2>
      )}
      <div className="mob-cat-grid-3col">
        {visible.map((item, i) => (
          <Link
            key={i}
            to={item.url || "#"}
            className="mob-cat-grid-item text-decoration-none"
          >
            <div className="mob-cat-grid-img-wrap">
              <img
                src={item.img && item.img.startsWith("./") ? item.img.substring(1) : item.img}
                alt={item.name}
                className="mob-cat-grid-img"
                loading="lazy"
              />
            </div>
            <span className="mob-cat-grid-label">{item.name}</span>
          </Link>
        ))}
      </div>
    </div>
  );
};

/* ─────────────────────────────────────────────────────────────────
   SUB-COMPONENT: Mobile Offer Zone  (Image 3 style)
   Props:
     badge      – top pill text  e.g. "🔥 Offer Zone Activated"
     title      – heading        e.g. "Top Discounts on Top Styles"
     products   – array of { image, discountedPrice, originalPrice, discountPercent, brand, id }
───────────────────────────────────────────────────────────────── */
const MobileOfferZone = ({ badge = "🔥 Offer Zone Activated", title = "Top Discounts on Top Styles", products = [] }) => {
  return (
    <div className="mob-offer-zone">
      {/* Header */}
      <div className="mob-offer-zone-header">
        <span className="mob-offer-zone-badge">{badge}</span>
        <p className="mob-offer-zone-title">{title}</p>
      </div>

      {/* Horizontal product carousel */}
      <div className="mob-offer-zone-track">
        {products.map((p, i) => {
          const img = Array.isArray(p.image) ? p.image[0] : p.image;
          return (
            <Link key={i} to={`/product/${p.id || i}`} className="mob-offer-zone-card text-decoration-none">
              <div className="mob-offer-zone-img-wrap">
                <img src={img} alt={p.brand || "product"} className="mob-offer-zone-img" loading="lazy" />
              </div>
              <p className="mob-offer-zone-name">{p.brand || "Product name....."}</p>
              {p.discountPercent && (
                <p className="mob-offer-zone-disc">{p.discountPercent}% Off</p>
              )}
            </Link>
          );
        })}
      </div>
    </div>
  );
};

const BASE_URL = ROOT_URL;

/* ── Mobile quick-link grid ──────────────────────────────────────── */
const MobileQuickLinks = ({ links }) => (
  <div className="mob-quicklinks">
    {links.map((l, i) => (
      <Link key={i} to={l.url} className="mob-quicklink-item">{l.name}</Link>
    ))}
  </div>
);

const CategoryInfoTextField = ({ initialText }) => {
  const [text, setText] = React.useState(
    initialText ||
    "Discover our premium selection of custom printed apparel, corporate merchandise, and high-quality clothing. We use state-of-the-art sublimation and embroidery techniques to deliver vibrant, durable prints tailored for your business or personal needs."
  );
  const [isEditing, setIsEditing] = React.useState(false);

  return (
    <div className="mob-section-gap px-3 pb-4">
      <div className="bg-light p-3 border rounded shadow-sm">
        <div className="d-flex justify-content-between align-items-center mb-2">
          <h4 className="fw-bold m-0" style={{ color: "#0b53a1", fontSize: "16px" }}>
            Category Information Text
          </h4>
          <button
            onClick={() => setIsEditing(!isEditing)}
            className="btn btn-sm btn-outline-primary py-0 px-2"
            style={{ fontSize: "12px" }}
          >
            {isEditing ? "Save" : "Edit Info"}
          </button>
        </div>
        
        {isEditing ? (
          <textarea
            className="form-control form-control-sm"
            rows="4"
            value={text}
            onChange={(e) => setText(e.target.value)}
            style={{ fontSize: "13px" }}
          />
        ) : (
          <p className="text-muted mb-0" style={{ fontSize: "13px", lineHeight: "1.6", whiteSpace: "pre-line" }}>
            {text}
          </p>
        )}
      </div>
    </div>
  );
};

const CategoryPage = () => {
  return (
    <div className="category-page">

      {/* ════ DESKTOP LAYOUT (≥ lg) ════════════════════════════════ */}
      <div className="d-none d-lg-block custom-bg w-100">
        <div className="cp-desktop-inner mx-auto home-desktop-wrapper">

          {/* 2. Small Banner (First 2 images) */}
          <div className="cp-card-section">
            <BannerSmall apiUrl={`${BASE_URL}api/banners/banners.php`} sliceStart={0} sliceEnd={2} />
          </div>

          {/* 3. ── BIG BANNER — Before / After comparison slider ── */}
          <div className="cp-card-section cp-compare-wrap">
            <BeforeAfterSlider
              beforeSrc="/before.png"
              afterSrc="/after.png"
              beforeLabel="Without Printing"
              afterLabel="With Printing"
              height="420px"
            />
          </div>

          {/* 4. Small Banner (Next 2 images) */}
          <div className="cp-card-section">
            <BannerSmall apiUrl={`${BASE_URL}api/banners/banners.php`} sliceStart={2} sliceEnd={4} />
          </div>

          {/* 4.5. Categories Grid Section */}
          <div className="cp-card-section">
            <CategoryGridSection />
          </div>


          {/* 5. New Arrivals — ProductCarousel */}
          <div className="cp-card-section">
            <ProductCarousel
              apiUrl={`${BASE_URL}api/products/products.php?action=top_selection`}
              title="New Arrivals"
              badgeText="NEW ARRIVAL"
            />
          </div>


          {/* 5. Main Slider */}
          <div className="cp-card-section">
            <ResponsiveHeroCarousel
              apiUrl={`${BASE_URL}api/banners/banners.php`}
              basePath={`${BASE_URL}uploads/banners/`}
              carouselId="catSmallCarousel"
            />
          </div>
          

          {/* 6. Best Sellers — ProductCarousel */}
          <div className="cp-card-section">
            <ProductCarousel
              apiUrl={`${BASE_URL}api/products/products.php?action=top_deal`}
              title="Best Sellers"
              badgeText="BEST SELLER"
            />
          </div>

          

          {/* 7. ResponsiveBannerSet */}
          <div className="cp-card-section">
            <ResponsiveBannerSet apiUrl={`${BASE_URL}api/banners/banners.php?section=banner_api7`} />
          </div>

          {/* 7.5. Personalized Gifts Section */}
          <div className="cp-card-section">
            <PersonalizedGifts />
          </div>

          {/* 7.6. Four Image Banner */}
          <div className="cp-card-section">
            <MultiColumnBannerCarousel apiUrl={`${BASE_URL}api/banners/banners.php`} columns={4} sectionKey="home_mid_section_2" />
          </div>

          {/* Special Offers Carousel */}
          <div className="cp-card-section">
            <ProductCarousel
              apiUrl={`${BASE_URL}api/products/products.php?action=discount_for_you`}
              title="Special Offers"
              badgeText="SPECIAL OFFER"
            />
          </div>

          {/* Stats Banner */}
          <div className="cp-card-section">
            <StatsBanner />
          </div>

          {/* Testimonials */}
          <div className="cp-card-section">
            <TestimonialCarousel />
          </div>



        </div>
      </div>

      {/* ════ MOBILE LAYOUT (< lg) ════════════════════════════════ */}
      <div className="d-block d-lg-none bg-white w-100 home-mobile-content">

        {/* 1 - Slider */}
        <MobileBannerCarousel
          apiUrl={`${BASE_URL}api/banners/banners.php?section=category_slider`}
          type="single"
        />

        {/* 2 - Banner carousel (Double image side-by-side) */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}api/banners/banners.php?section=category_double`}
            type="double"
          />
        </div>

        {/* 3 - Banner and slider */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}api/banners/banners.php?section=category_banner_slider`}
            type="single"
          />
        </div>

        {/* 4 - Best Seller (Carousel) */}
        <div className="mob-section-gap">
          <ProductCarousel
            apiUrl={`${BASE_URL}api/products/products.php?action=bestseller`}
            title="Best Seller (Carousel)"
            badgeText="BEST SELLER"
          />
        </div>

        {/* 5 - Banner and slider (Sider banner) */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}api/banners/banners.php?section=category_banner_slider_2`}
            type="single"
          />
        </div>

        {/* 6 - Men's Clothing 3-col grid */}
        <div className="mob-section-gap">
          <MobileCategoryGrid
            title="Men's Clothing"
            items={categoriesData.slice(0, 9)}
            maxItems={9}
          />
        </div>

        {/* 7 - Banner carousel (Double image side-by-side) */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}api/banners/banners.php?section=category_double_2`}
            type="double"
          />
        </div>

        {/* 8 - Product List Men */}
        <div className="mob-section-gap">
          <MobileProductList
            apiUrl={`${BASE_URL}api/products/products.php?action=men`}
            products={menOfferProducts}
          />
        </div>

        {/* 8b - Offer Zone Men */}
        <div className="mob-section-gap">
          <MobileOfferZone
            badge="Offer Zone Activated"
            title="Top Discounts on Top Styles"
            products={menOfferProducts}
          />
        </div>

        {/* 9 - Banner and slider */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}api/banners/banners.php?section=category_banner_slider_3`}
            type="single"
          />
        </div>

        {/* 10 - Women's Clothing 3-col grid */}
        <div className="mob-section-gap">
          <MobileCategoryGrid
            title="Women's Clothing"
            items={categoriesData.slice(0, 9)}
            maxItems={9}
          />
        </div>

        {/* 11 - Banner carousel (Double image side-by-side) */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}api/banners/banners.php?section=category_double_3`}
            type="double"
          />
        </div>

        {/* 12 - Offer Zone Women */}
        <div className="mob-section-gap">
          <MobileOfferZone
            badge="Offer Zone Activated"
            title="Top Discounts on Top Styless"
            products={womenOfferProducts}
          />
        </div>

        {/* 13 - Banner and slider */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}api/banners/banners.php?section=category_banner_slider_4`}
            type="single"
          />
        </div>

        {/* 14 - Footwear 3-col grid */}
        <div className="mob-section-gap">
          <MobileCategoryGrid
            title="Footwear"
            items={categoriesData.slice(2, 11)}
            maxItems={9}
          />
        </div>

        {/* 15 - Banner carousel (Double image side-by-side) */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}api/banners/banners.php?section=category_double_4`}
            type="double"
          />
        </div>

        {/* 16 - Offer Zone Footwear */}
        <div className="mob-section-gap">
          <MobileOfferZone
            badge="Footwear Deals"
            title="Step Up with Big Savings"
            products={footwearOfferProducts}
          />
        </div>

        {/* 17 - Banner and slider */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}api/banners/banners.php?section=category_banner_slider_5`}
            type="single"
          />
        </div>

        {/* 18 - Kids 3-col grid */}
        <div className="mob-section-gap">
          <MobileCategoryGrid
            title="Kids"
            items={categoriesData.slice(0, 6)}
            maxItems={6}
          />
        </div>

        {/* 18b - Banner carousel (Double image side-by-side) */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}api/banners/banners.php?section=category_double_5`}
            type="double"
          />
        </div>

        {/* 19 - Offer Zone Kids */}
        <div className="mob-section-gap">
          <MobileOfferZone
            badge="Offer Zone Activated"
            title="Top Discounts on Top Styless"
            products={kidsOfferProducts}
          />
        </div>

        {/* 19b - Product List Kids */}
        <div className="mob-section-gap">
          <MobileProductList
            apiUrl={`${BASE_URL}api/products/products.php?action=kids`}
            products={kidsOfferProducts}
          />
        </div>

        {/* 20 - Kids product carousel */}
        <div className="mob-section-gap">
          <ProductCarousel
            apiUrl={`${BASE_URL}api/products/products.php?action=kids`}
            title="Kids Carousel"
            badgeText="KIDS SPECIAL"
          />
        </div>

        {/* 21 - About Category text section */}
        <div className="mob-about-section">
          <div className="mob-about-inner">
            <span className="mob-about-tag">About this Category</span>
            <h3 className="mob-about-title">{categoryPageData.name}</h3>
            <p className="mob-about-desc">{categoryPageData.description}</p>
            <div className="mob-about-tags">
              {categoryPageData.subcategories?.map((sub, i) => (
                <Link key={i} to="#" className="mob-about-pill">{sub}</Link>
              ))}
            </div>
          </div>
        </div>

        {/* 22 - Category Information Custom Live Text Field */}
        <CategoryInfoTextField initialText={categoryPageData.description} />

      </div>
    </div>
  );
};

export default CategoryPage;
