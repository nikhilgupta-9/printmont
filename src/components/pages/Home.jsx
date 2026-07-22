import React from 'react'
import Categories from './category-list/Categories.jsx'
import {
  ResponsiveHeroCarousel,
  MobileHeroSlider,
  BannerGrid,
  ResponsiveBannerSet,
  MultiColumnBannerCarousel,
  ProductCarousel,
  CategoryProductMosaic,
  FeaturedProductGrid,
  GiftFinderSection,
  BrandDirectorySection,
  BulkOrderWidget,
  SectionRenderer,
  LazySection,
  BannerSkeleton,
  CarouselSkeleton,
  GridSkeleton,
  MosaicSkeleton,
} from '../home'
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
} from '../../../data/data'
import useHomeLayout from '../home/hooks/useHomeLayout'
import MobHome from './MobHome.jsx'

const Home = () => {
  const baseURL = import.meta.env.VITE_BASE_URL;
  const { sections, loading } = useHomeLayout('desktop', baseURL);

  return (
    <>
      {/* Desktop Home */}
      <div className='d-none d-lg-block custom-bg w-100'>
        <Categories space={"5px 0px"} bg='white' isSticky={true} />

        <div className='home-desktop-content mx-auto home-desktop-wrapper'>
          <div className='relative w-100 home-layout-gap'>
            {loading ? (
              <div className="container-fluid m-0 p-0 text-center py-5">
                <div className="shimmer-bg skeleton-banner-hero w-100" />
              </div>
            ) : sections && sections.length > 0 ? (
              sections.map((section, index) => (
                <SectionRenderer
                  key={section.id}
                  section={section}
                  baseURL={baseURL}
                  lazyLoad={index >= 3}
                  isMobile={false}
                />
              ))
            ) : (
              <>
                 {/* === ABOVE THE FOLD — Eager Load (no LazySection) === */}
                 <ResponsiveHeroCarousel apiUrl={`${baseURL}api/banners/banners.php`} basePath={`${baseURL}uploads/banners/`} banners={bannerImages} />
                 <MobileHeroSlider apiUrl={`${baseURL}api/banners/banners.php`} banners={bannerImages} />
                 <BannerGrid apiUrl={`${baseURL}api/banners/banners.php`} sectionKey="home_above_fold" banners={bannerImages} columns={4} mobileColumns={2} />

                 {/* === BELOW THE FOLD — Lazy Loaded === */}
                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 4" />}>
                   <MultiColumnBannerCarousel apiUrl={`${baseURL}api/banners/banners.php`} banners={threeimgcarousel} columns={3} sectionKey="home_mid_section_1" />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 4" />}>
                   <MultiColumnBannerCarousel apiUrl={`${baseURL}api/banners/banners.php`} banners={fourimgcarousel} columns={4} sectionKey="home_mid_section_2" />
                 </LazySection>

                 <LazySection skeleton={<CarouselSkeleton />}>
                   <ProductCarousel apiUrl={`${baseURL}api/products/products.php?action=bestseller`} title="Our Bestellers" badgeText="Customizable" products={bestsellerProducts} />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
                   <BannerGrid banners={sectiontwoimg} apiUrl={`${baseURL}api/banners/banners.php`} sectionKey="home_mid_section_3" columns={1} mobileColumns={1} />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 5" />}>
                   <GiftFinderSection />
                 </LazySection>

                 <LazySection skeleton={<CarouselSkeleton />}>
                   <ProductCarousel apiUrl={`${baseURL}api/products/products.php?action=top_selection`} title="Top Selection" badgeText="Customizable" products={sampleItems} />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 4" />}>
                   <BannerGrid apiUrl={`${baseURL}api/banners/banners.php?section=banner_api6`} banners={bannerTwoDesktop} columns={3} mobileColumns={1} />
                 </LazySection>

                 <LazySection skeleton={<CarouselSkeleton />}>
                   <ProductCarousel apiUrl={`${baseURL}api/products/products.php?action=discount_for_you`} title="Discount For You" badgeText="Customizable" products={bestsellerProducts} />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 5" />}>
                   <ResponsiveBannerSet apiUrl={`${baseURL}api/banners/banners.php?section=banner_api7`} banners={bannerTwoDesktop} />
                 </LazySection>

                 <LazySection skeleton={<CarouselSkeleton />}>
                   <ProductCarousel apiUrl={`${baseURL}api/products/products.php?action=top_rated`} title="Top Rated" badgeText="Customizable" products={sampleItems} />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 5" />}>
                   <ResponsiveBannerSet apiUrl={`${baseURL}api/banners/banners.php?section=banner_api8`} banners={bannerTwoDesktop} />
                 </LazySection>

                 <LazySection skeleton={<CarouselSkeleton />}>
                   <ProductCarousel apiUrl={`${baseURL}api/products/products.php?action=top_deal`} title="Top Deals and Categories" badgeText="Customizable" products={bestsellerProducts} />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 5" />}>
                   <ResponsiveBannerSet apiUrl={`${baseURL}api/banners/banners.php?section=banner_api9`} banners={bannerTwoDesktop} />
                 </LazySection>

                 <LazySection skeleton={<CarouselSkeleton />}>
                   <ProductCarousel apiUrl={`${baseURL}api/products/products.php?action=top_selection`} title="Women's Outfits" products={sampleItems} />
                 </LazySection>

                 <LazySection skeleton={<MosaicSkeleton />}>
                   <CategoryProductMosaic
                     apiUrl={`${baseURL}api/products/products.php?action=grouped_categories`}
                     backgroundImageUrl="https://example.com/bg.png"
                     columns={columns}
                     variant="grouped"
                   />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 4" />}>
                   <BannerGrid apiUrl={`${baseURL}api/banners/banners.php?section=banner_api11`} banners={bannerTwoDesktop} columns={3} mobileColumns={1} />
                 </LazySection>

                 <LazySection skeleton={<MosaicSkeleton />}>
                   <CategoryProductMosaic columns={columns} variant="grouped" />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 5" />}>
                   <ResponsiveBannerSet apiUrl={`${baseURL}api/banners/banners.php?section=banner_api14`} banners={bannerTwoDesktop} />
                 </LazySection>

                 <LazySection skeleton={<GridSkeleton columns={3} rows={2} />}>
                   <FeaturedProductGrid data={gridsectionfirst} />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 4" />}>
                   <BannerGrid apiUrl={`${baseURL}api/banners/banners.php?section=banner_api13`} banners={bannerTwoDesktop} columns={3} mobileColumns={1} />
                 </LazySection>

                 <LazySection skeleton={<GridSkeleton columns={3} rows={2} />}>
                   <FeaturedProductGrid data={gridsectionsecond} />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
                   <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
                 </LazySection>

                 <LazySection skeleton={<MosaicSkeleton />}>
                   <CategoryProductMosaic title="Men's" apiUrl={`${baseURL}api/products/products.php?action=top_rated`} imageColumn={imageColumn} columns={columns} variant="flat" />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 6" />}>
                   <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
                 </LazySection>

                 <LazySection skeleton={<MosaicSkeleton />}>
                   <CategoryProductMosaic title="Women's" apiUrl={`${baseURL}api/products/products.php?action=top_deal`} imageColumn={imageColumn} columns={columns} variant="flat" />
                 </LazySection>

                 <LazySection skeleton={<CarouselSkeleton />}>
                   <ProductCarousel apiUrl={`${baseURL}api/products/products.php?action=discount_for_you`} title="Recently Viewed" products={sampleItems} />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 3" />}>
                   <BrandDirectorySection />
                 </LazySection>

                 <LazySection skeleton={<BannerSkeleton aspectRatio="16 / 3" />}>
                   <BulkOrderWidget />
                 </LazySection>

              </>
            )}
          </div>
        </div>
      </div>
      <div className='d-block d-lg-none'>
        <MobHome />
      </div>
    </>
  )
}

export default Home;
