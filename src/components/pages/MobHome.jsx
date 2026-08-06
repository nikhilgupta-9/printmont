import React from 'react'
import Categories from './category-list/Categories'
import {
  ResponsiveHeroCarousel,
  MobileHeroSlider,
  BannerGrid,
  ResponsiveBannerSet,
  StaticResponsiveBanner,
  ProductCarousel,
  CompactProductGrid,
  CompactCategoryGrid,
  MobileProductList,
  CategoryProductMosaic,
  FeaturedProductGrid,
  CustomerReviewCarousel,
  BrandDirectorySection,
  BulkOrderWidget,
  SectionRenderer,
  GiftFinderSection,
  LazySection,
  BannerSkeleton,
  CarouselSkeleton,
  GridSkeleton,
  MosaicSkeleton,
  ProductListSkeleton,
  SmallHeightCarouselSlider,
  SquareSingleImageMobileSlider,
  SmallHeightStaticBanner,
  OneAndHalfCarousel,
  FormatDesignBanner,
} from '../home'
import SectionNine from './sections/SectionNine'
import {
  bannerImages,
  bannerSet1,
  sectiontwoimg,
  specialoffer,
  tablewareItems,
  womensFashionItems,
  budsItems,
  homeDecorItems,
  mobileItems,
  mensFashionItems,
  gridsectionfirst,
  gridsectionsecond,
  sampleProducts,
  columns,
  imageColumn
} from '../../../data/data'
import useHomeLayout from '../home/hooks/useHomeLayout'
import { ROOT_URL } from '../../config/apiEndpoints'

// Static dummy categories matching the Figma category circles
const dummyCategories = [
  { name: "Offer Zone", slug: "offer-zone", image: "/electro/mobile-1.jpeg" },
  { name: "Categories", slug: "categories", image: "/girl-product-img/subsubcat-104.jpeg" },
  { name: "Categories", slug: "categories", image: "/electro/buds-1.jpeg" },
  { name: "Categories", slug: "categories", image: "/girl-product-img/plant-1.jpeg" },
  { name: "Categorie", slug: "categorie", image: "/women-dress/women-dress-1.jpeg" },
  { name: "Categorie", slug: "categorie", image: "/electro/watch-1.jpeg" },
  { name: "Categorie", slug: "categorie", image: "/section-img/pro12.jpeg" },
  { name: "Categorie", slug: "categorie", image: "/men_shirt/men-shirt-2.jpeg" }
];

// Static dummy banners for sliders
const dummyBanners = [
  { small: './section-img/banner-5-mob.png', large: './section-img/banner-5.png', alt: "Slide 1" },
  { small: './section-img/banner-5-mob.png', large: './section-img/banner-5.png', alt: "Slide 2" },
  { small: './section-img/banner-5-mob.png', large: './section-img/banner-5.png', alt: "Slide 3" }
];

const staticWidescreenBanners = [
  {
    large: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=1200&h=450&q=80',
    small: 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=800&h=300&q=80',
    title: 'Custom Printed T-Shirts',
    alt: 'Custom Printed T-Shirts',
    target: '/allproducts?q=shirt',
  },
  {
    large: 'https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=1200&h=450&q=80',
    small: 'https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=800&h=300&q=80',
    title: 'Personalized Gifts & Mugs',
    alt: 'Personalized Gifts & Mugs',
    target: '/allproducts?category=Fashion',
  },
  {
    large: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=1200&h=450&q=80',
    small: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&h=300&q=80',
    title: 'Corporate Merchandise & Tech',
    alt: 'Corporate Merchandise & Tech',
    target: '/allproducts?category=Electronics',
  },
  {
    large: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1200&h=450&q=80',
    small: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&h=300&q=80',
    title: 'Premium Headphones',
    alt: 'Premium Headphones',
    target: '/allproducts',
  },
  {
    large: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1200&h=450&q=80',
    small: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&h=300&q=80',
    title: 'Custom Sneakers',
    alt: 'Custom Sneakers',
    target: '/allproducts',
  },
  {
    large: 'https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&w=1200&h=450&q=80',
    small: 'https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&w=800&h=300&q=80',
    title: 'Stylish Leather Goods',
    alt: 'Stylish Leather Goods',
    target: '/allproducts',
  },
];

const staticSquareBanners = [
  {
    large: 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=600&h=600&q=80',
    title: 'Custom Hoodies',
    alt: 'Custom Hoodies',
    target: '/allproducts?q=hoodie',
  },
  {
    large: 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?auto=format&fit=crop&w=600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?auto=format&fit=crop&w=600&h=600&q=80',
    title: 'Personalized Mugs',
    alt: 'Personalized Mugs',
    target: '/allproducts?q=mug',
  },
  {
    large: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&h=600&q=80',
    title: 'Red Sport Shoes',
    alt: 'Red Sport Shoes',
    target: '/allproducts?q=shoes',
  },
  {
    large: 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?auto=format&fit=crop&w=600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?auto=format&fit=crop&w=600&h=600&q=80',
    title: 'Black Cotton Tees',
    alt: 'Black Cotton Tees',
    target: '/allproducts?q=shirt',
  },
  {
    large: 'https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&w=600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1560343090-f0409e92791a?auto=format&fit=crop&w=600&h=600&q=80',
    title: 'Corporate Accessories',
    alt: 'Corporate Accessories',
    target: '/allproducts',
  },
  {
    large: 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?auto=format&fit=crop&w=600&h=600&q=80',
    small: 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?auto=format&fit=crop&w=600&h=600&q=80',
    title: 'Sunglasses & Goggles',
    alt: 'Sunglasses & Goggles',
    target: '/allproducts',
  },
];

// Static dummy 4-image grid banners (2x2)
const dummyFourBanners = [
  { src: './section-img/add1.png', alt: 'online-shopping' },
  { src: './section-img/add2.png', alt: 'lap-hoodie' },
  { src: './section-img/add1.png', alt: 'online-shopping' },
  { src: './section-img/add2.png', alt: 'lap-hoodie' }
];

// Static dummy product list matching the Navy Blue Sulphur Cotton Shirts in the mockup
const dummyProducts = [
  {
    id: 1,
    title: "Navy Blue Sulphur Cotton Shirts",
    img: "/men_shirt/men-shirt-8.jpeg",
    price: 1499,
    originalPrice: 2999,
    discount: "50% off",
    badge: "Priemium"
  },
  {
    id: 2,
    title: "Navy Blue Sulphur Cotton Shirts",
    img: "/men_shirt/men-shirt-7.jpeg",
    price: 1499,
    originalPrice: 2999,
    discount: "50% off",
    badge: "Priemium"
  },
  {
    id: 3,
    title: "Navy Blue Sulphur Cotton Shirts",
    img: "/men_shirt/men-shirt-6.jpeg",
    price: 1499,
    originalPrice: 2999,
    discount: "50% off",
    badge: "Priemium"
  },
  {
    id: 4,
    title: "Navy Blue Sulphur Cotton Shirts",
    img: "/men_shirt/men-shirt-5.jpeg",
    price: 1499,
    originalPrice: 2999,
    discount: "50% off",
    badge: "Priemium"
  },
  {
    id: 5,
    title: "Navy Blue Sulphur Cotton Shirts",
    img: "/men_shirt/men-shirt-2.jpeg",
    price: 1499,
    originalPrice: 2999,
    discount: "50% off",
    badge: "Priemium"
  },
  {
    id: 6,
    title: "Navy Blue Sulphur Cotton Shirts",
    img: "/men_shirt/men-shirt-3.jpeg",
    price: 1499,
    originalPrice: 2999,
    discount: "50% off",
    badge: "Priemium"
  }
];

// Static dummy products for Women's Outfits carousel (using custom overlay tags like KURTI)
const womensOutfitsProducts = [
  {
    id: 1,
    title: "Women's Designer Outfits",
    img: "/women-dress/women-dress-1.jpeg",
    price: 1499,
    originalPrice: 2999,
    discount: "50% off",
    badge: "Priemium",
    overlayTag: "KURTI"
  },
  {
    id: 2,
    title: "Women's Designer Outfits",
    img: "/women-dress/women-dress-2.jpeg",
    price: 1499,
    originalPrice: 2999,
    discount: "50% off",
    badge: "Priemium",
    overlayTag: "CHECKERKARI KURTI"
  },
  {
    id: 3,
    title: "Women's Designer Outfits",
    img: "/women-dress/women-dress-3.jpeg",
    price: 1499,
    originalPrice: 2999,
    discount: "50% off",
    badge: "Priemium",
    overlayTag: "KURTI"
  },
  {
    id: 4,
    title: "Women's Designer Outfits",
    img: "/women-dress/women-dress-4.jpeg",
    price: 1499,
    originalPrice: 2999,
    discount: "50% off",
    badge: "Priemium",
    overlayTag: "CHECKERKARI KURTI"
  },
  {
    id: 5,
    title: "Women's Designer Outfits",
    img: "/women-dress/women-dress-5.jpeg",
    price: 1499,
    originalPrice: 2999,
    discount: "50% off",
    badge: "Priemium",
    overlayTag: "KURTI"
  },
  {
    id: 6,
    title: "Women's Designer Outfits",
    img: "/women-dress/women-dress-6.jpeg",
    price: 1499,
    originalPrice: 2999,
    discount: "50% off",
    badge: "Priemium",
    overlayTag: "CHECKERKARI KURTI"
  }
];

const MobHome = () => {
  const baseURL = ROOT_URL;

  // Mobile layout is managed from the admin panel (home-layout-manager.php -> Mobile tab).
  // Mirrors the desktop behaviour in Home.jsx: when the API returns sections we render
  // those, otherwise the hardcoded markup below is used as the fallback.
  const { sections, loading } = useHomeLayout('mobile', baseURL);

  return (
    <>
      <div className='bg-white w-100 home-mobile-content home-layout-gap'>
        {/* Image 1 Layout Elements */}

        {/* 1. Categories Circles — Normal flow below header */}
        <Categories isSticky={false} />

        {/* Top Mobile Banners Sequence with Unique Static Images */}
        <SmallHeightCarouselSlider banners={staticWidescreenBanners} maxHeight="85px" isMobileOnly={true} />
        <SquareSingleImageMobileSlider banners={staticSquareBanners} isMobileOnly={true} />
        <SmallHeightCarouselSlider banners={staticWidescreenBanners} maxHeight="85px" isMobileOnly={true} />
        <FormatDesignBanner format="desktop_format_6" banners={staticSquareBanners} />
        <SmallHeightStaticBanner banners={staticWidescreenBanners.slice(3, 4)} maxHeight="85px" />
        <OneAndHalfCarousel banners={staticWidescreenBanners.slice(1, 4)} isMobileOnly={true} />

        {loading ? (
          <div className="shimmer-bg skeleton-banner-hero w-100" />
        ) : sections && sections.length > 0 ? (
          sections.map((section, index) => (
            <SectionRenderer
              key={section.id}
              section={section}
              baseURL={baseURL}
              lazyLoad={index >= 3}
              isMobile={true}
            />
          ))
        ) : (
          <>

            {/* === ABOVE THE FOLD — Eager Load (no LazySection) === */}

            {/* 2. Banner and slider small size */}
            <MobileHeroSlider banners={dummyBanners} />

            {/* 3. Home page slider */}
            <ResponsiveHeroCarousel banners={dummyBanners} />

            {/* 4. Banner and slider small size */}
            <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />


            {/* === BELOW THE FOLD — Lazy Loaded === */}

            {/* 5. Square size Offer Banner (2x2 Grid) */}
            <LazySection skeleton={<GridSkeleton columns={2} rows={2} />} minHeight="200px">
              <BannerGrid banners={dummyFourBanners} columns={2} mobileColumns={2} />
            </LazySection>

            {/* 6. Banner and slider small size */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 7. Banner and slides small size */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 5" />}>
              <ResponsiveBannerSet banners={bannerImages} />
            </LazySection>

            {/* 8. New Arrival (Carousel) */}
            <LazySection skeleton={<CarouselSkeleton mobile={true} />}>
              <ProductCarousel
                apiUrl={`${baseURL}api/products/products.php?action=bestseller`}
                title="New Arrival"
                badgeText="CUSTOMIZABLE"
                cardsToShowMobile={1.65}
              />
            </LazySection>

            {/* 10. Gift Finder (Mobile Dropdown Version) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 5" />}>
              <GiftFinderSection />
            </LazySection>

            {/* 11. End of Season Sale (Pink 3-column Category Grid from API) */}
            <LazySection skeleton={<GridSkeleton columns={3} rows={2} />}>
              <CompactCategoryGrid
                title="End of Season Sale"
                bgImage={'./bg/flashsale.png'}
              />
            </LazySection>

            {/* 12. Banner and slider Square size */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid apiUrl={`${baseURL}api/banner_api.php`} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 13. Banner and slider */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid apiUrl={`${baseURL}api/banner_api.php`} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 15. Banner and slider */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid apiUrl={`${baseURL}api/banner_api.php`} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 16. Bestseller (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <CompactProductGrid
                apiUrl={`${baseURL}api/products/products.php?action=bestseller`}
                title="Bestseller"
                columns={2}
                limit={8}
                showViewAll={true}
              />
            </LazySection>

            {/* 17. Banner and slider */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid apiUrl={`${baseURL}api/banner_api.php`} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 18. Top Selection (Carousel) */}
            <LazySection skeleton={<CarouselSkeleton mobile={true} />}>
              <ProductCarousel
                apiUrl={`${baseURL}api/products/products.php?action=top_selection`}
                title="Top Selection"
                badgeText="CUSTOMIZABLE"
              />
            </LazySection>

            {/* 19. Square size Banner and Slider (2 Column Grid) */}
            <LazySection skeleton={<GridSkeleton columns={2} rows={2} />}>
              <BannerGrid apiUrl={`${baseURL}api/banner_api.php`} columns={2} mobileColumns={2} />
            </LazySection>

            {/* 20. Discount For You (Carousel) */}
            <LazySection skeleton={<CarouselSkeleton mobile={true} />}>
              <ProductCarousel
                apiUrl={`${baseURL}api/products/products.php?action=discount_for_you`}
                title="Discount For You"
                badgeText="CUSTOMIZABLE"
              />
            </LazySection>

            {/* 21. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList apiUrl={`${baseURL}api/products/products.php?action=discount_for_you`} />
            </LazySection>

            {/* 22. Square size Banner and Slider (2 Column Grid) */}
            <LazySection skeleton={<GridSkeleton columns={2} rows={2} />}>
              <BannerGrid apiUrl={`${baseURL}api/banner_api.php`} columns={2} mobileColumns={2} />
            </LazySection>

            {/* 23. Top Rated (Carousel - Single Card) */}
            <LazySection skeleton={<CarouselSkeleton mobile={true} />}>
              <ProductCarousel
                apiUrl={`${baseURL}api/products/products.php?action=top_rated`}
                title="Top Rated"
                badgeText="CUSTOMIZABLE"
                cardsToShowMobile={1.1}
              />
            </LazySection>

            {/* 24. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList apiUrl={`${baseURL}api/products/products.php?action=top_rated`} />
            </LazySection>

            {/* 25. Square size Banner and Slider (1 Column Grid) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid apiUrl={`${baseURL}api/banner_api.php`} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 26. Top Deal and Categories (Carousel) */}
            <LazySection skeleton={<CarouselSkeleton mobile={true} />}>
              <ProductCarousel
                apiUrl={`${baseURL}api/products/products.php?action=top_deal`}
                title="Top Deal and Categories"
                badgeText="CUSTOMIZABLE"
              />
            </LazySection>

            {/* 27. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList apiUrl={`${baseURL}api/products/products.php?action=top_deal`} />
            </LazySection>

            {/* 28. Banner and Slider (Widescreen 70/30 Slider) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 9" />}>
              <MobileHeroSlider
                apiUrl={`${baseURL}api/banner_api.php`}
                slidesPerView={1.3}
                spaceBetween={8}
              />
            </LazySection>

            {/* 29. Women's Outfits (Carousel) */}
            <LazySection skeleton={<CarouselSkeleton mobile={true} />}>
              <ProductCarousel
                apiUrl={`${baseURL}api/products/products.php?action=top_selection`}
                title="Women's Outfits"
              />
            </LazySection>

            {/* 30. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList products={dummyProducts} />
            </LazySection>

            {/* 31. Square size Banner and Slider (1 Column Grid) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 32. Home Decor items (Category - 2x2 Grid) */}
            <LazySection skeleton={<GridSkeleton columns={2} rows={2} />}>
              <CompactCategoryGrid
                title="Home Decor items"
                bgColor="#00a8ec"
                columns={2}
                limit={4}
                showViewAll={true}
                showBottomViewAll={true}
                offerText="Min. 30% off"
              />
            </LazySection>

            {/* 33. Product List (3 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={6} />}>
              <CompactProductGrid
                title=""
                products={dummyProducts}
                columns={3}
                limit={6}
              />
            </LazySection>

            {/* 34. Square size Banner and Slider (1 Column Grid) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 35. Table & Dinnerware (Category - 2x2 Grid) */}
            <LazySection skeleton={<GridSkeleton columns={2} rows={2} />}>
              <CompactCategoryGrid
                title="Table & Dinnerware"
                columns={2}
                limit={4}
                showViewAll={true}
                showBottomViewAll={true}
                offerText="Min. 30% off"
              />
            </LazySection>

            {/* 36. Product List (3 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={6} />}>
              <CompactProductGrid
                title=""
                products={dummyProducts}
                columns={3}
                limit={6}
              />
            </LazySection>

            {/* 37. Square size Banner and Slider (1 Column Grid) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 38. Buds (Category - 2x2 Grid) */}
            <LazySection skeleton={<GridSkeleton columns={2} rows={2} />}>
              <CompactCategoryGrid
                title="Buds"
                bgColor="#4ba3b0"
                columns={2}
                limit={4}
                showViewAll={true}
                showBottomViewAll={true}
                offerText="Min. 30% off"
              />
            </LazySection>

            {/* 39. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList products={dummyProducts} />
            </LazySection>

            {/* 40. Square size Banner and Slider (1 Column Grid) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 41. Mobile (Category - 2x2 Grid) */}
            <LazySection skeleton={<GridSkeleton columns={2} rows={2} />}>
              <CompactCategoryGrid
                title="Mobile"
                bgColor="#c3bfdb"
                columns={2}
                limit={4}
                showViewAll={true}
                showBottomViewAll={true}
                offerText="Min. 30% off"
              />
            </LazySection>

            {/* 42. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList products={dummyProducts} />
            </LazySection>

            {/* 43. Square size Banner and Slider (1 Column Grid) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 44. Earphones (Category - 2x2 Grid) */}
            <LazySection skeleton={<GridSkeleton columns={2} rows={2} />}>
              <CompactCategoryGrid
                title="Earphones"
                bgColor="#668bbd"
                columns={2}
                limit={4}
                showViewAll={true}
                showBottomViewAll={true}
                offerText="Min. 30% off"
              />
            </LazySection>

            {/* 45. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList products={dummyProducts} />
            </LazySection>

            {/* 46. Square size Banner and Slider (1 Column Grid) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 47. Top Rated (Category - Mosaic Grid) */}
            <LazySection skeleton={<MosaicSkeleton />}>
              <FeaturedProductGrid data={[{ ...gridsectionsecond[0], title: `${gridsectionsecond[0].title} (Category)` }]} />
            </LazySection>

            {/* 48. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList products={dummyProducts} />
            </LazySection>

            {/* 49. Square size Banner and Slider (1 Column Grid) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 50. Top Deals (Category - Mosaic Grid) */}
            <LazySection skeleton={<MosaicSkeleton />}>
              <FeaturedProductGrid data={[{ ...gridsectionsecond[1], title: `${gridsectionsecond[1].title} (Category)` }]} />
            </LazySection>

            {/* 51. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList products={dummyProducts} />
            </LazySection>

            {/* 52. Square size Banner and Slider (1 Column Grid) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 53. Top Selection (Category - Mosaic Grid) */}
            <LazySection skeleton={<MosaicSkeleton />}>
              <FeaturedProductGrid data={[{ ...gridsectionsecond[2], title: `${gridsectionsecond[2].title} (Category)` }]} />
            </LazySection>

            {/* 54. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList products={dummyProducts} />
            </LazySection>

            {/* 55. Square size Banner and Slider (1 Column Grid) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 56. Women's (Category - Mosaic Grid) */}
            <LazySection skeleton={<MosaicSkeleton />}>
              <FeaturedProductGrid data={[gridsectionfirst[0]]} />
            </LazySection>

            {/* 57. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList products={dummyProducts} />
            </LazySection>

            {/* 58. Square size Banner and Slider (1 Column Grid) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 59. Men's (Category - Mosaic Grid) */}
            <LazySection skeleton={<MosaicSkeleton />}>
              <FeaturedProductGrid data={[gridsectionfirst[1]]} />
            </LazySection>

            {/* 60. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList products={dummyProducts} />
            </LazySection>

            {/* 61. Square size Banner and Slider (1 Column Grid) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 62. Women's Clothing (Category - Flat Grid) */}
            <LazySection skeleton={<MosaicSkeleton />}>
              <CategoryProductMosaic
                title="Women's Clothing"
                apiUrl={`${baseURL}api/products/products.php?action=top_deal`}
                imageColumn={imageColumn}
                columns={columns}
                variant="flat"
                bgColor="#ffebee"
              />
            </LazySection>

            {/* 63. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList products={dummyProducts} />
            </LazySection>

            {/* 64. Square size Banner and Slider (1 Column Grid) */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
              <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
            </LazySection>

            {/* 65. Men's Clothing (Category - Flat Grid) */}
            <LazySection skeleton={<MosaicSkeleton />}>
              <CategoryProductMosaic
                title="Men's Clothing"
                apiUrl={`${baseURL}api/products/products.php?action=top_rated`}
                imageColumn={imageColumn}
                columns={columns}
                variant="flat"
                bgColor="#e8f5e9"
              />
            </LazySection>

            {/* 66. Product List (2 Column Grid) */}
            <LazySection skeleton={<ProductListSkeleton items={4} />}>
              <MobileProductList products={dummyProducts} />
            </LazySection>

            {/* 67. Recently Viewed (Carousel) */}
            <LazySection skeleton={<CarouselSkeleton mobile={true} />}>
              <ProductCarousel
                apiUrl={`${baseURL}api/products/products.php?action=discount_for_you`}
                title="Your Recently Viewed (Carousel)"
                products={dummyProducts}
              />
            </LazySection>

            {/* 68. Customer Review Carousel */}
            <LazySection skeleton={<CarouselSkeleton mobile={true} />}>
              <CustomerReviewCarousel />
            </LazySection>

            {/* 69. Brand Directory Section */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 3" />}>
              <BrandDirectorySection />
            </LazySection>

            {/* 70. Bulk Order Widget */}
            <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 3" />}>
              <BulkOrderWidget />
            </LazySection>

          </>
        )}

      </div>
    </>
  );
};

export default MobHome;