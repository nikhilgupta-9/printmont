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
  GiftFinderSection
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
  const baseURL = import.meta.env.VITE_BASE_URL;

  return (
    <>
      <div className='bg-white w-100 home-mobile-content home-layout-gap'>
        {/* Image 1 Layout Elements */}

        {/* 1. Categories Circles */}
        <Categories isSticky={false} />

        {/* 2. Banner and slider small size */}
        <MobileHeroSlider banners={dummyBanners} />

        {/* 3. Home page slider */}
        <ResponsiveHeroCarousel banners={dummyBanners} />

        {/* 4. Banner and slider small size */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 5. Square size Offer Banner (2x2 Grid) */}
        <BannerGrid banners={dummyFourBanners} columns={2} mobileColumns={2} />

        {/* 6. Banner and slider small size */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 7. Banner and slides small size */}
        <ResponsiveBannerSet banners={bannerImages} />


        {/* Image 2 Layout Elements */}

        {/* 8. New Arivel (Carousel) */}
        <ProductCarousel
          products={dummyProducts}
          title="New Arivel"
          badgeText="CUSTOMIZABLE"
          cardsToShowMobile={1.65}
        />

        {/* 10. Gift Finder (Mobile Dropdown Version) */}
        <GiftFinderSection />

        {/* 11. End of Season Sale (Pink 3-column Category Grid from API) */}
        <CompactCategoryGrid
          title="End of Season Sale"
          bgImage={'./bg/flashsale.png'}
        />


        {/* Image 3 Layout Elements */}

        {/* 12. Banner and slider Square size */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 13. Banner and slider */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 15. Banner and slider */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 16. Bestseller (2 Column Grid) */}
        <CompactProductGrid
          title="Bestseller"
          products={dummyProducts.concat(dummyProducts.slice(0, 2))}
          columns={2}
          limit={8}
          showViewAll={true}
        />


        {/* Image 4 Layout Elements */}

        {/* 17. Banner and slider */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 18. Top Selection (Carousel) */}
        <ProductCarousel
          products={dummyProducts}
          title="Top Selection"
          badgeText="CUSTOMIZABLE"
        />

        {/* 19. Square size Banner and Slider (2 Column Grid) */}
        <BannerGrid banners={bannerImages} columns={2} mobileColumns={2} />

        {/* 20. Discount For You (Carousel) */}
        <ProductCarousel
          products={dummyProducts}
          title="Discount For You"
          badgeText="CUSTOMIZABLE"
        />

        {/* 21. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 22. Square size Banner and Slider (2 Column Grid) */}
        <BannerGrid banners={bannerImages} columns={2} mobileColumns={2} />

        {/* 23. Top Rated (Carousel - Single Card) */}
        <ProductCarousel
          products={dummyProducts}
          title="Top Rated"
          badgeText="CUSTOMIZABLE"
          cardsToShowMobile={1.1}
        />

        {/* 24. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 25. Square size Banner and Slider (1 Column Grid) */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 26. Top Deal and Categories (Carousel) */}
        <ProductCarousel
          products={dummyProducts}
          title="Top Deal and Categories"
          badgeText="CUSTOMIZABLE"
        />

        {/* 27. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 28. Banner and Slider (Widescreen 70/30 Slider) */}
        <MobileHeroSlider
          banners={bannerImages}
          slidesPerView={1.3}
          spaceBetween={8}
        />

        {/* 29. Women's Outfits (Carousel) */}
        <ProductCarousel
          products={womensOutfitsProducts}
          title="Women's Outfits"
        />

        {/* 30. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 31. Square size Banner and Slider (1 Column Grid) */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 32. Home Decor items (Category - 2x2 Grid) */}
        <CompactCategoryGrid
          title="Home Decor items"
          bgColor="#00a8ec"
          columns={2}
          limit={4}
          showViewAll={true}
          showBottomViewAll={true}
          offerText="Min. 30% off"
        />

        {/* 33. Product List (3 Column Grid) */}
        <CompactProductGrid
          title=""
          products={dummyProducts}
          columns={3}
          limit={6}
        />

        {/* 34. Square size Banner and Slider (1 Column Grid) */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 35. Table & Dinnerware (Category - 2x2 Grid) */}
        <CompactCategoryGrid
          title="Table & Dinnerware"
          columns={2}
          limit={4}
          showViewAll={true}
          showBottomViewAll={true}
          offerText="Min. 30% off"
        />

        {/* 36. Product List (3 Column Grid) */}
        <CompactProductGrid
          title=""
          products={dummyProducts}
          columns={3}
          limit={6}
        />

        {/* 37. Square size Banner and Slider (1 Column Grid) */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 38. Buds (Category - 2x2 Grid) */}
        <CompactCategoryGrid
          title="Buds"
          bgColor="#4ba3b0"
          columns={2}
          limit={4}
          showViewAll={true}
          showBottomViewAll={true}
          offerText="Min. 30% off"
        />

        {/* 39. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 40. Square size Banner and Slider (1 Column Grid) */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 41. Mobile (Category - 2x2 Grid) */}
        <CompactCategoryGrid
          title="Mobile"
          bgColor="#c3bfdb"
          columns={2}
          limit={4}
          showViewAll={true}
          showBottomViewAll={true}
          offerText="Min. 30% off"
        />

        {/* 42. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 43. Square size Banner and Slider (1 Column Grid) */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 44. Earphones (Category - 2x2 Grid) */}
        <CompactCategoryGrid
          title="Earphones"
          bgColor="#668bbd"
          columns={2}
          limit={4}
          showViewAll={true}
          showBottomViewAll={true}
          offerText="Min. 30% off"
        />

        {/* 45. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 46. Square size Banner and Slider (1 Column Grid) */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 47. Top Rated (Category - Mosaic Grid) */}
        <FeaturedProductGrid data={[{ ...gridsectionsecond[0], title: `${gridsectionsecond[0].title} (Category)` }]} />

        {/* 48. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 49. Square size Banner and Slider (1 Column Grid) */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 50. Top Deals (Category - Mosaic Grid) */}
        <FeaturedProductGrid data={[{ ...gridsectionsecond[1], title: `${gridsectionsecond[1].title} (Category)` }]} />

        {/* 51. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 52. Square size Banner and Slider (1 Column Grid) */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 53. Top Selection (Category - Mosaic Grid) */}
        <FeaturedProductGrid data={[{ ...gridsectionsecond[2], title: `${gridsectionsecond[2].title} (Category)` }]} />

        {/* 54. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 55. Square size Banner and Slider (1 Column Grid) */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 56. Women's (Category - Mosaic Grid) */}
        <FeaturedProductGrid data={[gridsectionfirst[0]]} />

        {/* 57. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 58. Square size Banner and Slider (1 Column Grid) */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 59. Men's (Category - Mosaic Grid) */}
        <FeaturedProductGrid data={[gridsectionfirst[1]]} />

        {/* 60. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 61. Square size Banner and Slider (1 Column Grid) */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 62. Women's Clothing (Category - Flat Grid) */}
        <CategoryProductMosaic
          title="Women's Clothing"
          apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`}
          imageColumn={imageColumn}
          columns={columns}
          variant="flat"
          bgColor="#ffebee"
        />

        {/* 63. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 64. Square size Banner and Slider (1 Column Grid) */}
        <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />

        {/* 65. Men's Clothing (Category - Flat Grid) */}
        <CategoryProductMosaic
          title="Men's Clothing"
          apiUrl={`${baseURL}api/home-product-api.php?action=top_rated`}
          imageColumn={imageColumn}
          columns={columns}
          variant="flat"
          bgColor="#e8f5e9"
        />

        {/* 66. Product List (2 Column Grid) */}
        <MobileProductList products={dummyProducts} />

        {/* 67. Recently Viewed (Carousel) */}
        <ProductCarousel
          apiUrl={`${baseURL}api/home-product-api.php?action=discount_for_you`}
          title="Your Recently Viewed (Carousel)"
          products={dummyProducts}
        />

        {/* 68. Customer Review Carousel */}
        <CustomerReviewCarousel />

        {/* 69. Brand Directory Section */}
        <BrandDirectorySection />

        {/* 70. Bulk Order Widget */}
        <BulkOrderWidget />

      </div>
    </>
  );
};

export default MobHome;