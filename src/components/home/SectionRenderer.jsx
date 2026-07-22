import React from "react";
import {
  ResponsiveHeroCarousel,
  MobileHeroSlider,
  BannerGrid,
  ResponsiveBannerSet,
  MultiColumnBannerCarousel,
  StaticResponsiveBanner,
  ProductCarousel,
  CompactProductGrid,
  MobileProductList,
  CategoryProductMosaic,
  FeaturedProductGrid,
  GiftFinderSection,
  BrandDirectorySection,
  BulkOrderWidget
} from "./index";

import LazySection from "./LazySection";
import { getSkeletonForType } from "./HomeSkeleton";

import SectionNine from "../pages/sections/SectionNine";
import { ASSET_URL } from "../../config/apiEndpoints";

// Static data fallbacks to match original hardcoded layouts
import {
  bestsellerProducts,
  columns,
  gridsectionfirst,
  gridsectionsecond,
  bannerImages,
  sectiontwoimg,
  bannerTwoDesktop,
  threeimgcarousel,
  fourimgcarousel,
  sampleItems,
  imageColumn,
  specialoffer,
  tablewareItems,
  womensFashionItems,
  budsItems,
  homeDecorItems,
  mobileItems,
  mensFashionItems,
  sampleProducts
} from "../../../data/data";

/**
 * @param {object} props
 * @param {object} props.section — The layout section object from the API.
 * @param {string} props.baseURL — The base URL of the API.
 * @param {boolean} [props.lazyLoad=true] — Whether to wrap in LazySection.
 * @param {boolean} [props.isMobile=false] — Whether this is a mobile layout.
 */
export default function SectionRenderer({ section, baseURL, lazyLoad = true, isMobile = false }) {
  const {
    section_type,
    section_key,
    label,
    columns_per_row,
    api_action,
    product_limit,
    badge_text,
    background_image_url,
    banners
  } = section;

  // Build API URLs dynamically if an action is specified
  let productApiUrl = null;
  if (api_action) {
    productApiUrl = `${baseURL}api/products/products.php?action=${api_action}`;
    if (product_limit) {
      productApiUrl += `&limit=${product_limit}`;
    }
  }

  // Prepend ASSET_URL to banner image URLs if they are relative
  const formattedBanners = (banners || []).map(b => ({
    ...b,
    large: b.large && !b.large.startsWith("http") ? `${ASSET_URL}${b.large}` : b.large,
    small: b.small && !b.small.startsWith("http") ? `${ASSET_URL}${b.small}` : b.small,
  }));

  // Resolve the actual section content
  const content = renderSectionContent({
    section_type,
    section_key,
    label,
    columns_per_row,
    productApiUrl,
    badge_text,
    background_image_url,
    formattedBanners,
    baseURL,
  });

  if (!content) return null;

  // Wrap in LazySection if lazyLoad is enabled
  if (lazyLoad) {
    const skeleton = getSkeletonForType(section_type, isMobile);
    return (
      <LazySection skeleton={skeleton} minHeight="100px">
        {content}
      </LazySection>
    );
  }

  return content;
}

/**
 * Internal helper — renders the actual section content without lazy-loading concerns.
 */
function renderSectionContent({
  section_type,
  section_key,
  label,
  columns_per_row,
  productApiUrl,
  badge_text,
  background_image_url,
  formattedBanners,
  baseURL,
}) {
  // Handle specific section properties and fallbacks
  switch (section_type) {
    case "slider":
      if (section_key === "home_hero") {
        return (
          <>
            <ResponsiveHeroCarousel
              apiUrl={`${baseURL}api/banner_api.php`}
              basePath={`${baseURL}uploads/banners/`}
              banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerImages}
            />
            {/* Render MobileHeroSlider right after if it is the hero section */}
            <MobileHeroSlider
              apiUrl={`${baseURL}api/banner_api.php`}
              banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerImages}
            />
          </>
        );
      }
      if (section_key === "mobile_hero_slider") {
        return (
          <MobileHeroSlider
            apiUrl={`${baseURL}api/banner_api.php`}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerImages}
          />
        );
      }
      return (
        <MultiColumnBannerCarousel
          apiUrl={`${baseURL}api/banner_api.php`}
          banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : undefined}
          columns={columns_per_row || 3}
          sectionKey={section_key}
        />
      );

    case "banner":
      if (section_key === "mobile_responsive_banner_set") {
        return (
          <ResponsiveBannerSet
            apiUrl={`${baseURL}api/banner_api.php`}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : undefined}
          />
        );
      }
      if (section_key.includes("static_responsive_banner")) {
        return <StaticResponsiveBanner />;
      }
      if (section_key.includes("banner_api7")) {
        return (
          <ResponsiveBannerSet
            apiUrl={`${baseURL}api/banner_api7.php`}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerTwoDesktop}
          />
        );
      }
      if (section_key.includes("banner_api8")) {
        return (
          <ResponsiveBannerSet
            apiUrl={`${baseURL}api/banner_api8.php`}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerTwoDesktop}
          />
        );
      }
      if (section_key.includes("banner_api9")) {
        return (
          <ResponsiveBannerSet
            apiUrl={`${baseURL}api/banner_api9.php`}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerTwoDesktop}
          />
        );
      }
      if (section_key.includes("banner_api14")) {
        return (
          <ResponsiveBannerSet
            apiUrl={`${baseURL}api/banner_api14.php`}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerTwoDesktop}
          />
        );
      }
      if (section_key.includes("banner_api6") || section_key.includes("banner_api11") || section_key.includes("banner_api13")) {
        return (
          <BannerGrid
            apiUrl={`${baseURL}api/${section_key.replace('home_', '')}.php`}
            sectionKey={section_key}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerTwoDesktop}
            columns={columns_per_row || 3}
            mobileColumns={1}
          />
        );
      }

      return (
        <BannerGrid
          apiUrl={`${baseURL}api/banner_api.php`}
          sectionKey={section_key}
          banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : undefined}
          columns={columns_per_row || 2}
          mobileColumns={columns_per_row > 2 ? 2 : 1}
        />
      );

    case "product_carousel":
      // Fallback static products for bestseller/top_selection/recently_viewed if loading fails
      let initialProducts = [];
      if (section_key.includes("bestsellers")) {
        initialProducts = bestsellerProducts;
      } else if (section_key.includes("top_selection")) {
        initialProducts = sampleItems;
      } else if (section_key.includes("flash_sale")) {
        initialProducts = specialoffer;
      }

      return (
        <ProductCarousel
          apiUrl={productApiUrl}
          products={initialProducts}
          title={label}
          badgeText={badge_text || ""}
          backgroundImageUrl={background_image_url}
        />
      );

    case "compact_grid":
      return (
        <CompactProductGrid
          title={label}
          apiUrl={productApiUrl}
          bgImage={background_image_url || "/bg/grid-product-bg.png"}
        />
      );

    case "mobile_list":
      return <MobileProductList apiUrl={productApiUrl} />;

    case "category_mosaic":
      let mosaicColumns = columns;
      // Setup specific columns for mobile categories
      if (section_key === "mobile_tableware_dinnerware") {
        mosaicColumns = [{ title: "Tableware & Dinnerware", items: tablewareItems }];
      } else if (section_key === "mobile_womens_fashion_mosaic") {
        mosaicColumns = [{ title: "Women's Fashion", items: womensFashionItems }];
      } else if (section_key === "mobile_earbuds_headphones") {
        mosaicColumns = [{ title: "EarBuds & Headphones", items: budsItems }];
      } else if (section_key === "mobile_home_decor") {
        mosaicColumns = [{ title: "Home Decor Items", items: homeDecorItems }];
      } else if (section_key === "mobile_mobiles_mosaic") {
        mosaicColumns = [{ title: "Mobiles", items: mobileItems }];
      } else if (section_key === "mobile_mens_fashion_mosaic") {
        mosaicColumns = [{ title: "Men's Fashion", items: mensFashionItems }];
      } else if (section_key === "mobile_new_fashion_mosaic") {
        mosaicColumns = [{ title: "New Fashion", items: mensFashionItems }];
      } else if (section_key === "mobile_best_for_health") {
        mosaicColumns = [{ title: "Best for Health", items: mensFashionItems }];
      }

      if (section_key.includes("grouped_2")) {
        return null;
      }

      const isGrouped = !section_key.includes("flat");

      return (
        <CategoryProductMosaic
          title={label}
          apiUrl={productApiUrl}
          backgroundImageUrl={background_image_url || (section_key.includes("tableware") ? "./bg/super.png" : undefined)}
          columns={mosaicColumns}
          variant={isGrouped ? "grouped" : "flat"}
        />
      );

    case "featured_grid":
      let gridData = gridsectionfirst;
      if (section_key.includes("2")) {
        gridData = gridsectionsecond;
      }
      if (section_key === "mobile_section_nine") {
        return <SectionNine products={sampleProducts} />;
      }
      return <FeaturedProductGrid data={gridData} />;

    case "brand_directory":
      return <BrandDirectorySection />;

    case "gift_finder":
      return <GiftFinderSection />;

    case "bulk_widget":
      return <BulkOrderWidget />;

    default:
      return null;
  }
}
