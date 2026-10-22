import React from "react";
import ErrorBoundary from "../common/ErrorBoundary";
import {
  ResponsiveHeroCarousel,
  MobileHeroSlider,
  BannerGrid,
  ResponsiveBannerSet,
  MultiColumnBannerCarousel,
  StaticResponsiveBanner,
  ProductCarousel,
  CompactProductGrid,
  CompactCategoryGrid,
  MobileProductList,
  CategoryProductMosaic,
  FeaturedProductGrid,
  CustomerReviewCarousel,
  GiftFinderSection,
  BrandDirectorySection,
  BulkOrderWidget,
  BannerCategorySlider,
  CategorySlideRow,
  MultiRowProductCarousel,
  OneAndHalfCarousel,
  SmallBannerCarousel,
  SmallHeightCarouselSlider,
  RecentlyViewedProducts
} from "./index";




import LazySection from "./LazySection";
import { getSkeletonForType } from "./HomeSkeleton";

import SectionNine from "../pages/sections/SectionNine";
import Categories from "../pages/category-list/Categories";
import { BASE_URL, ASSET_URL, API_ENDPOINTS, resolveImageUrl } from "../../config/apiEndpoints";


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
  tablewareItems2,
  womensFashionItems,
  budsItems,
  homeDecorItems,
  homeDecorItems2,
  mobileItems,
  mensFashionItems,
  kidsItems,
  girloutfit,
  dealsandcategories,
  discount,
  sampleProducts,
  mensFlatMosaicItems,
  womensFlatMosaicItems
} from "../../../data/data";

/**
 * @param {object} props
 * @param {object} props.section — The layout section object from the API.
 * @param {string} [props.baseURL] — The base URL of the API.
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
    product_ids,
    category_ids,
    sub_category_ids,
    badge_text,
    background_image_url,
    banners
  } = section;

  // Build API URLs dynamically if handpicked product_ids or api_action is specified
  let productApiUrl = null;
  if (product_ids && product_ids.trim() !== "") {
    productApiUrl = `${BASE_URL}/products/products.php?action=handpicked&ids=${encodeURIComponent(product_ids.trim())}`;
    if (product_limit) {
      productApiUrl += `&limit=${product_limit}`;
    }
  } else if (api_action) {
    productApiUrl = `${BASE_URL}/products/products.php?action=${api_action}`;
    if (product_limit) {
      productApiUrl += `&limit=${product_limit}`;
    }
  } else if (["product_carousel", "mobile_list", "compact_grid", "compact_product_grid", "multi_row_carousel"].includes(section_type)) {
    const defaultAction = section_key.includes("top_rated") ? "top_rated" : (section_key.includes("top_deal") ? "top_deal" : (section_key.includes("discount") ? "discount_for_you" : "bestseller"));
    productApiUrl = `${BASE_URL}/products/products.php?action=${defaultAction}&limit=${product_limit || 8}`;
  }

  // Prepend ASSET_URL or resolve local public paths for banner image URLs
  const formattedBanners = (banners || []).map(b => {
    const rawLarge = b.large || b.image_url_desktop || (b.images && b.images.desktop) || b.image_url || '';
    const rawSmall = b.small || b.image_url_mobile || (b.images && b.images.mobile) || rawLarge || '';
    const resolvedLarge = rawLarge ? resolveImageUrl(rawLarge) : '';
    const resolvedSmall = rawSmall ? resolveImageUrl(rawSmall) : resolvedLarge;
    return {
      ...b,
      large: resolvedLarge,
      small: resolvedSmall,
      image_url_desktop: resolvedLarge,
      image_url_mobile: resolvedSmall,
      target: b.target || b.target_url || '#',
      alt: b.alt || b.title || ''
    };
  });

  // Sanitize label to remove developer annotations like "(Carousel)" or leading "5. "
  const displayLabel = (label || "")
    .replace(/^\d+\.\s*/, "")
    .replace(/\s*\((Carousel|Category|2-Col|1-Col)\)/gi, "")
    .trim();

  // Resolve the actual section content
  const content = renderSectionContent({
    section,
    section_type,
    section_key,
    label: displayLabel,
    columns_per_row,
    api_action,
    product_limit,
    category_ids,
    sub_category_ids,
    productApiUrl,
    badge_text,
    background_image_url,
    formattedBanners,
    baseURL: BASE_URL,
    isMobile,
  });

  if (!content) return null;

  const skeleton = getSkeletonForType(section_type, isMobile);

  return (
    <ErrorBoundary silent={true}>
      <LazySection skeleton={skeleton} minHeight="100px" eager={!lazyLoad} className="pm-home-section-wrapper w-100">
        {content}
      </LazySection>
    </ErrorBoundary>
  );
}

/**
 * Internal helper — renders the actual section content without lazy-loading concerns.
 */
function renderSectionContent({
  section = {},
  section_type,
  section_key,
  label,
  columns_per_row,
  api_action,
  product_limit,
  category_ids,
  sub_category_ids,
  productApiUrl,
  badge_text,
  background_image_url,
  formattedBanners,
  baseURL,
  isMobile = false,
}) {

  // Handle specific section properties and fallbacks
  switch (section_type) {
    case "slider":
      if (section_key === "home_hero") {
        if (isMobile) {
          return (
            <MobileHeroSlider
              apiUrl={`${BASE_URL}/banner_api.php?section=home_hero`}
              sectionKey="home_hero"
              banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerImages}
            />
          );
        }
        return (
          <ResponsiveHeroCarousel
            apiUrl={`${BASE_URL}/banner_api.php?section=home_hero`}
            basePath={`${ASSET_URL}uploads/banners/`}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerImages}
          />
        );
      }
      if (section_key === "small_banner_slider" || section_key === "small_height_carousel") {
        return (
          <SmallBannerCarousel
            apiUrl={`${BASE_URL}/banner_api.php?section=${section_key}`}
            sectionKey={section_key}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : undefined}
            isMobile={isMobile}
          />
        );
      }
      if (section_key === "mobile_hero_slider" || isMobile) {
        return (
          <MobileHeroSlider
            apiUrl={`${BASE_URL}/banner_api.php?section=${section_key}`}
            sectionKey={section_key}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerImages}
            isMobileOnly={false}
          />
        );
      }
      return (
        <MultiColumnBannerCarousel
          apiUrl={`${BASE_URL}/banner_api.php`}
          banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : undefined}
          columns={columns_per_row || 3}
          sectionKey={section_key}
        />
      );

    case "small_banner_carousel":
    case "small_banner_slider":
    case "small_height_carousel":
      return (
        <SmallBannerCarousel
          apiUrl={`${BASE_URL}/banner_api.php?section=${section_key}`}
          sectionKey={section_key}
          banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : undefined}
          isMobile={isMobile}
        />
      );

    case "one_and_half_carousel":
    case "peeking_slider":
      return (
        <OneAndHalfCarousel
          apiUrl={`${BASE_URL}/banner_api.php`}
          sectionKey={section_key}
          banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : undefined}
          isMobileOnly={false}
        />
      );

    case "banner":
      if (isMobile && section_key === "home_after_rated") {
        return (
          <MobileHeroSlider
            apiUrl={`${BASE_URL}/banner_api.php?section=${section_key}`}
            sectionKey={section_key}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : undefined}
            isMobileOnly={false}
          />
        );
      }
      if (section_key === "mobile_responsive_banner_set") {

        return (
          <ResponsiveBannerSet
            apiUrl={`${BASE_URL}/banner_api.php`}
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
            apiUrl={`${BASE_URL}/banner_api.php?section=${section_key}`}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerTwoDesktop}
          />
        );
      }
      if (section_key.includes("banner_api8")) {
        return (
          <ResponsiveBannerSet
            apiUrl={`${BASE_URL}/banner_api.php?section=${section_key}`}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerTwoDesktop}
          />
        );
      }
      if (section_key.includes("banner_api9")) {
        return (
          <ResponsiveBannerSet
            apiUrl={`${BASE_URL}/banner_api.php?section=${section_key}`}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerTwoDesktop}
          />
        );
      }
      if (section_key.includes("banner_api14")) {
        return (
          <ResponsiveBannerSet
            apiUrl={`${BASE_URL}/banner_api.php?section=${section_key}`}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerTwoDesktop}
          />
        );
      }
      if (section_key.includes("banner_api6") || section_key.includes("banner_api11") || section_key.includes("banner_api13")) {
        return (
          <BannerGrid
            apiUrl={`${BASE_URL}/banner_api.php?section=${section_key}`}
            sectionKey={section_key}
            banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : bannerTwoDesktop}
            columns={columns_per_row || 3}
            mobileColumns={1}
          />
        );
      }

      const isSquareBannerSection =
        section_key.includes("square") ||
        (isMobile && columns_per_row === 2) ||
        (section.layout_format === "2x2" || section.layout_format === "square");
      const resolvedMobileColumns = isMobile
        ? Math.min(columns_per_row || 2, 2)
        : (columns_per_row >= 2 ? 2 : 1);

      return (
        <BannerGrid
          apiUrl={`${BASE_URL}/banner_api.php`}
          sectionKey={section_key}
          banners={formattedBanners && formattedBanners.length > 0 ? formattedBanners : undefined}
          columns={columns_per_row || 2}
          mobileColumns={resolvedMobileColumns}
          aspectRatio={isSquareBannerSection ? "1/1" : undefined}
        />
      );

    case "product_carousel":
      let carouselFallback = bestsellerProducts;
      let defaultBadge = badge_text || "";
      if (section_key.includes("women") || section_key.includes("outfit")) {
        carouselFallback = girloutfit && girloutfit.length > 0 ? girloutfit : bestsellerProducts;
        if (!defaultBadge) defaultBadge = "Priemium";
      } else if (section_key.includes("top_deal")) {
        carouselFallback = dealsandcategories && dealsandcategories.length > 0 ? dealsandcategories : bestsellerProducts;
      } else if (section_key.includes("discount") || section_key.includes("special")) {
        carouselFallback = discount && discount.length > 0 ? discount : bestsellerProducts;
      } else if (section_key.includes("top_rated")) {
        carouselFallback = bestsellerProducts;
      }

      if (section_key.includes("recently_viewed")) {
        return (
          <RecentlyViewedProducts
            title={label || "Recently Viewed"}
            apiUrl={productApiUrl}
          />
        );
      }

      if (isMobile && (section_key.includes("bestseller") || section_key === "mobile_bestsellers")) {
        return (
          <MultiRowProductCarousel
            title={label}
            apiUrl={productApiUrl}
            rows={4}
            showViewAll={true}
            viewAllUrl="/allproducts?filter=bestseller"
            badgeText={defaultBadge || "Priemium"}
          />
        );
      }

      return (
        <ProductCarousel
          apiUrl={productApiUrl}
          products={carouselFallback}
          title={label}
          badgeText={defaultBadge}
          backgroundImageUrl={background_image_url}
        />
      );

    case "multi_row_carousel":
      return (
        <MultiRowProductCarousel
          title={label}
          apiUrl={productApiUrl}
          rows={4}
          showViewAll={true}
          viewAllUrl={api_action ? `/allproducts?filter=${api_action}` : "/allproducts?filter=bestseller"}
          badgeText={badge_text || "Priemium"}
        />
      );

    case "compact_grid":
    case "compact_category_grid": {
      if (section_key.includes("end_of_season") || section_key.includes("sale")) {
        return (
          <CompactCategoryGrid
            title={label || "End of Season Sale"}
            bgImage={background_image_url || "./bg/flashsale.png"}
            columns={3}
            limit={9}
          />
        );
      }

      const isBgColor = background_image_url && (background_image_url.startsWith('#') || background_image_url.startsWith('rgb'));
      const bgImg = isBgColor ? null : background_image_url;
      const bgCol = isBgColor ? background_image_url : (section.bg_color || (section_key.includes("decor") ? "#00a8ec" : null));

      return (
        <CompactCategoryGrid
          title={label || "Home Decor items (Category)"}
          categoryId={category_ids}
          subCategoryIds={sub_category_ids || section.sub_category_ids}
          bgImage={bgImg}
          bgColor={bgCol}
          columns={columns_per_row || 2}
          limit={product_limit || 4}
          showViewAll={true}
          showBottomViewAll={true}
          offerText={badge_text || "Min. 30% off"}
        />
      );
    }

    case "compact_product_grid":
      if (isMobile && (section_key.includes("bestseller") || section_key === "mobile_bestsellers")) {
        return (
          <MultiRowProductCarousel
            title={label}
            apiUrl={productApiUrl}
            rows={4}
            showViewAll={true}
            viewAllUrl="/allproducts?filter=bestseller"
            badgeText={badge_text || "Priemium"}
          />
        );
      }
      return (
        <CompactProductGrid
          title={label}
          apiUrl={productApiUrl}
          bgImage={background_image_url}
          columns={columns_per_row || 3}
          limit={product_limit || 6}
          showViewAll={Boolean(label)}
          showBottomViewAll={Boolean(label)}
          viewAllUrl={api_action ? `/allproducts?filter=${api_action}` : "/allproducts"}
        />
      );


    case "mobile_list":
      let listFallback = undefined;
      if (section_key.includes("women") || section_key.includes("outfit") || section_key.includes("grid_5")) {
        listFallback = girloutfit && girloutfit.length > 0 ? girloutfit : bestsellerProducts;
      }
      return (
        <MobileProductList
          title={label}
          products={listFallback}
          apiUrl={productApiUrl || `${BASE_URL}/products/products.php?action=${api_action || 'top_rated'}&limit=${product_limit || 6}`}
          limit={product_limit || 6}
          columns={columns_per_row || 2}
          badgeText={badge_text}
          bgImage={background_image_url}
          showViewAll={Boolean(label)}
          viewAllUrl={api_action ? `/allproducts?filter=${api_action}` : "/allproducts"}
        />
      );


    case "banner_category_slider":
      return (
        <BannerCategorySlider
          title={label}
          banners={formattedBanners}
          bgImage={background_image_url}
        />
      );

    case "category_slide_row":
      return <CategorySlideRow title={label} />;

    case "category_strip":
      if (isMobile) {
        return <CategorySlideRow title={label} />;
      }
      return <Categories isSticky={false} />;

    case "category_mosaic":


      let mosaicColumns = columns;
      let mosaicImageColumn = null;
      let isFlat = section_key.includes("flat");

      // Desktop specific groupings
      if (section_key === "desktop_mosaic_grouped_1") {
        mosaicColumns = [
          { title: "Women's Fashion", items: womensFashionItems },
          { title: "Men's Fashion", items: mensFashionItems },
          { title: "Kid's Wear", items: kidsItems }
        ];
      } else if (section_key === "desktop_mosaic_grouped_3") {
        mosaicColumns = columns;
      } else if (section_key === "desktop_mosaic_flat_mens") {
        isFlat = true;
        mosaicImageColumn = { imageUrl: "/corporate_apparel.png", alt: "Men's Fashion" };
        mosaicColumns = mensFlatMosaicItems;
      } else if (section_key === "desktop_mosaic_flat_womens") {
        isFlat = true;
        mosaicImageColumn = { imageUrl: "/fashion_brand.png", alt: "Women's Fashion" };
        mosaicColumns = womensFlatMosaicItems;
      }
      // Mobile specific groupings
      else if (section_key.includes("womens_clothing")) {
        isFlat = true;
        mosaicColumns = womensFashionItems;
        mosaicImageColumn = { imageUrl: "/women-dress/women-dress-1.jpeg", alt: "Women's Clothing" };
      } else if (section_key.includes("womens_fashion")) {
        isFlat = false;
        mosaicColumns = [{ title: "Women's Fashion", items: womensFashionItems }];
      } else if (section_key.includes("mens_fashion")) {
        isFlat = false;
        mosaicColumns = [{ title: "Men's Fashion", items: mensFashionItems }];
      } else if (section_key === "mobile_tableware_dinnerware") {
        mosaicColumns = [{ title: "Tableware & Dinnerware", items: tablewareItems }];
      } else if (section_key === "mobile_earbuds_headphones") {
        mosaicColumns = [{ title: "EarBuds & Headphones", items: budsItems }];
      } else if (section_key === "mobile_home_decor") {
        mosaicColumns = [{ title: "Home Decor Items", items: homeDecorItems }];
      } else if (section_key === "mobile_mobiles_mosaic") {
        mosaicColumns = [{ title: "Mobiles", items: mobileItems }];
      }

      if (section_key.includes("grouped_2") && !isMobile) {
        mosaicColumns = [
          { title: "Tableware & Dinnerware", items: tablewareItems2 || tablewareItems },
          { title: "Home Decor Items", items: homeDecorItems }
        ];
        mosaicImageColumn = imageColumn;
      }

      return (
        <CategoryProductMosaic
          title={label}
          apiUrl={isFlat ? productApiUrl : undefined}
          imageColumn={mosaicImageColumn}
          backgroundImageUrl={background_image_url || (section_key.includes("tableware") ? "./bg/super.png" : undefined)}
          columns={mosaicColumns}
          variant={isFlat ? "flat" : "grouped"}
        />
      );

    case "featured_grid":
      let gridData = gridsectionfirst;
      if (section_key.includes("women")) {
        gridData = [{ ...gridsectionfirst[0], title: label || "Women's (Category)" }];
      } else if (section_key.includes("top_selection") || section_key.includes("selection")) {
        gridData = [{ ...gridsectionsecond[2], title: label || "Top Selection (Category)" }];
      } else if (section_key.includes("top_rated")) {
        gridData = [{ ...gridsectionsecond[0], title: label || "Top Rated (Category)" }];
      } else if (section_key.includes("top_deal")) {
        gridData = [{ ...gridsectionsecond[1], title: label || "Top Deals (Category)" }];
      } else if (section_key.includes("men")) {
        gridData = [{ ...gridsectionfirst[1], title: label || "Men's (Category)" }];
      } else if (section_key.includes("2")) {
        gridData = gridsectionsecond;
      } else if (isMobile) {
        gridData = [{ ...gridsectionsecond[2], title: label || "Top Selection (Category)" }];
      }
      if (section_key === "mobile_section_nine") {
        return <SectionNine products={sampleProducts} />;
      }
      return <FeaturedProductGrid data={gridData} section={section} isMobile={isMobile} />;

    case "brand_directory":
    case "why_us":
      return <BrandDirectorySection />;

    case "gift_finder":
      return <GiftFinderSection />;

    case "bulk_widget":
    case "footer":
      return <BulkOrderWidget />;


    case "customer_reviews":
    case "reviews":
    case "customer_review_carousel":
      return (
        <CustomerReviewCarousel
          section={section}
          title={section.label || "Customer Reviews"}
          reviews={section.reviews}
          limit={section.product_limit || 5}
        />
      );

    default:
      return null;
  }
}
