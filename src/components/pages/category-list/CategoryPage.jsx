import React from "react";
import { Link } from "react-router-dom";

// ── Existing user components ──────────────
import FirstCarousel   from "../carousel/FirstCarousel";
import SecondCarousel  from "../carousel/SecondCarousel";
import Slider          from "../carousel/Slider";
import FourImgCarousel from "../carousel/FourImgCarousel";
import ThreeImgCarousel from "../carousel/ThreeImgCarousel";
import TwoImgCarousel  from "../carousel/TwoImgCarousel";
import Banner          from "../sections/Banner";
import BannerTwo       from "../sections/BannerTwo";
import BannerSmall     from "../sections/BannerSmall";
import SectionTen      from "../sections/SectionTen";
import BeforeAfterSlider from "./BeforeAfterSlider";
import CategoryGridSection from "./CategoryGridSection";
import PersonalizedGifts from "./PersonalizedGifts";
import StatsBanner from "../sections/StatsBanner";
import TestimonialCarousel from "../sections/TestimonialCarousel";

// ── Static data ───────────────────────────────────────────────────
import {
  categoryPageData,
  newArrivalProducts, bestSellerProducts, specialOfferProducts,
  menSubcategories, womenSubcategories, footwearSubcategories, kidsSubcategories,
  menOfferProducts, womenOfferProducts, footwearOfferProducts, kidsOfferProducts,
  mobileCategoryLinks, categoryReviews, heroBanners,
} from "../../../../data/categoryPageData";
import { sampleItems } from "../../../../data/data";

import "./CategoryPage.css";

const BASE_URL = import.meta.env.VITE_BASE_URL;

/* ── Mobile quick-link grid ──────────────────────────────────────── */
const MobileQuickLinks = ({ links }) => (
  <div className="mob-quicklinks">
    {links.map((l, i) => (
      <Link key={i} to={l.url} className="mob-quicklink-item">{l.name}</Link>
    ))}
  </div>
);

const CategoryPage = () => {
  return (
    <div className="category-page">

      {/* ════ DESKTOP LAYOUT (≥ lg) ════════════════════════════════ */}
      <div className="d-none d-lg-block custom-bg w-100">
        <div className="cp-desktop-inner mx-auto home-desktop-wrapper">

          {/* 2. Small Banner (First 2 images) */}
          <div className="cp-card-section">
            <BannerSmall apiUrl={`${BASE_URL}api/banner_api.php`} sliceStart={0} sliceEnd={2} />
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
            <BannerSmall apiUrl={`${BASE_URL}api/banner_api.php`} sliceStart={2} sliceEnd={4} />
          </div>

          {/* 4.5. Categories Grid Section */}
          <div className="cp-card-section">
            <CategoryGridSection />
          </div>


          {/* 5. New Arrivals — SecondCarousel */}
          <div className="cp-card-section">
            <SecondCarousel
              apiUrl={`${BASE_URL}api/home-product-api.php?action=top_selection`}
              title="New Arrivals"
              badgeText="NEW ARRIVAL"
            />
          </div>


          {/* 5. Main Slider */}
          <div className="cp-card-section">
            <FirstCarousel
              apiUrl={`${BASE_URL}api/banner_api.php`}
              basePath={`${BASE_URL}uploads/banners/`}
              carouselId="catSmallCarousel"
            />
          </div>
          

          {/* 6. Best Sellers — SecondCarousel */}
          <div className="cp-card-section">
            <SecondCarousel
              apiUrl={`${BASE_URL}api/home-product-api.php?action=top_deal`}
              title="Best Sellers"
              badgeText="BEST SELLER"
            />
          </div>

          

          {/* 7. BannerTwo */}
          <div className="cp-card-section">
            <BannerTwo apiUrl={`${BASE_URL}api/banner_api7.php`} />
          </div>

          {/* 7.5. Personalized Gifts Section */}
          <div className="cp-card-section">
            <PersonalizedGifts />
          </div>

          {/* 7.6. Four Image Banner */}
          <div className="cp-card-section">
            <FourImgCarousel apiUrl={`${BASE_URL}api/banner_api.php`} />
          </div>

          {/* Special Offers Carousel */}
          <div className="cp-card-section">
            <SecondCarousel
              apiUrl={`${BASE_URL}api/home-product-api.php?action=discount_for_you`}
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

        {/* 1. Mobile Swiper Slider */}
        <Slider apiUrl={`${BASE_URL}api/banner_api.php`} />

        {/* 2. Category quick-link grid */}
        <MobileQuickLinks links={mobileCategoryLinks} />

        {/* 3. Small Banner (First 2 images) */}
        <div className="mt-2">
            <BannerSmall apiUrl={`${BASE_URL}api/banner_api.php`} sliceStart={0} sliceEnd={2} />
        </div>

        {/* 4. Before / After comparison slider */}
        <div className="cp-card-section cp-compare-wrap mt-2 mb-2 px-2">
          <BeforeAfterSlider
            beforeSrc="/before.png"
            afterSrc="/after.png"
            beforeLabel="Without Printing"
            afterLabel="With Printing"
            height="250px"
          />
        </div>

        {/* 5. Small Banner (Next 2 images) */}
        <div className="mb-2">
            <BannerSmall apiUrl={`${BASE_URL}api/banner_api.php`} sliceStart={2} sliceEnd={4} />
        </div>

        {/* 5.5. Categories Grid Section */}
        <div className="mb-2">
            <CategoryGridSection />
        </div>

        {/* 4. ThreeImgCarousel banner */}
        <ThreeImgCarousel apiUrl={`${BASE_URL}api/banner_api.php`} />

        {/* 5. Top Selection */}
        <div className="mt-2">
            <SecondCarousel
            apiUrl={`${BASE_URL}api/home-product-api.php?action=top_selection`}
            title="New Arrivals"
            badgeText="NEW ARRIVAL"
            />
        </div>

        {/* 6. Small Banner (Third banner small height) */}
        <div className="mt-2">
            <BannerSmall apiUrl={`${BASE_URL}api/banner_api7.php`} />
        </div>

        {/* 7. Best Sellers */}
        <div className="mt-2">
            <SecondCarousel
            apiUrl={`${BASE_URL}api/home-product-api.php?action=top_deal`}
            title="Best Sellers"
            badgeText="BEST SELLER"
            />
        </div>


        {/* Banner between sections */}
        <div className="mt-2">
            <BannerTwo apiUrl={`${BASE_URL}api/banner_api7.php`} />
        </div>

        {/* Personalized Gifts Section */}
        <div className="mt-2">
            <PersonalizedGifts />
        </div>

        {/* Four Image Banner */}
        <div className="mt-2 mb-4">
            <FourImgCarousel apiUrl={`${BASE_URL}api/banner_api.php`} />
        </div>

        {/* Special Offers Carousel */}
        <div className="mt-2 mb-4">
            <SecondCarousel
              apiUrl={`${BASE_URL}api/home-product-api.php?action=discount_for_you`}
              title="Special Offers"
              badgeText="SPECIAL OFFER"
            />
        </div>

        {/* Stats Banner */}
        <div className="mt-2 mb-4">
            <StatsBanner />
        </div>

        {/* Testimonials */}
        <div className="mt-2 mb-4">
            <TestimonialCarousel />
        </div>


        {/* Banner between sections */}
        <div className="mt-2">
            <Banner apiUrl={`${BASE_URL}api/banner_api6.php`} />
        </div>

        {/* 10. Footwear */}
        <div className="mt-2">
            <SectionTen title="Footwear" items={sampleItems} />
        </div>

        {/* 11. Kids */}
        <div className="mt-2">
            <SectionTen title="Kids" items={sampleItems} />
        </div>

        {/* 12. Main category watermark label */}
        <div className="mob-cat-label text-center py-4 text-muted fw-bold opacity-25 fs-1">
          {categoryPageData.name}
        </div>

      </div>
    </div>
  );
};

export default CategoryPage;
