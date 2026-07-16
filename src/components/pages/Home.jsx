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
  SectionRenderer
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
              sections.map((section) => (
                <SectionRenderer key={section.id} section={section} baseURL={baseURL} />
              ))
            ) : (
              <>
                <ResponsiveHeroCarousel apiUrl={`${baseURL}api/banner_api.php`} basePath={`${baseURL}uploads/banners/`} banners={bannerImages} />
                <MobileHeroSlider apiUrl={`${baseURL}api/banner_api.php`} banners={bannerImages} />
                <BannerGrid apiUrl={`${baseURL}api/banner_api.php`} sectionKey="home_above_fold" banners={bannerImages} columns={4} mobileColumns={2} />
                <MultiColumnBannerCarousel apiUrl={`${baseURL}api/banner_api.php`} banners={threeimgcarousel} columns={3} sectionKey="home_mid_section_1" />
                <MultiColumnBannerCarousel apiUrl={`${baseURL}api/banner_api.php`} banners={fourimgcarousel} columns={4} sectionKey="home_mid_section_2" />
                <ProductCarousel apiUrl={`${baseURL}api/bestseller-products.php`} title="Our Bestellers" badgeText="Customizable" products={bestsellerProducts} />
                <BannerGrid banners={sectiontwoimg} apiUrl={`${baseURL}api/banner_api.php`} sectionKey="home_mid_section_3" columns={1} mobileColumns={1} />
                <GiftFinderSection />
                <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`} title="Top Selection" badgeText="Customizable" products={sampleItems} />
                <BannerGrid apiUrl={`${baseURL}api/banner_api6.php`} banners={bannerTwoDesktop} columns={3} mobileColumns={1} />
                <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=discount_for_you`} title="Discount For You" badgeText="Customizable" products={bestsellerProducts} />
                <ResponsiveBannerSet apiUrl={`${baseURL}api/banner_api7.php`} banners={bannerTwoDesktop} />
                <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_rated`} title="Top Rated" badgeText="Customizable" products={sampleItems} />
                <ResponsiveBannerSet apiUrl={`${baseURL}api/banner_api8.php`} banners={bannerTwoDesktop} />
                <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`} title="Top Deals and Categories" badgeText="Customizable" products={bestsellerProducts} />
                <ResponsiveBannerSet apiUrl={`${baseURL}api/banner_api9.php`} banners={bannerTwoDesktop} />
                <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`} title="Women's Outfits" products={sampleItems} />

                <CategoryProductMosaic
                  apiUrl={`${baseURL}api/home-product-api.php?action=grouped_categories`}
                  backgroundImageUrl="https://example.com/bg.png"
                  columns={columns}
                  variant="grouped"
                />
                <CategoryProductMosaic
                  apiUrl={`${baseURL}api/home-product-api.php?action=grouped_categories`}
                  backgroundImageUrl="https://example.com/bg.png"
                  imageColumn={{
                    imageUrl: "/girl-product-img/girl-1.webp",
                    alt: "Featured Product",
                  }}
                  columns={columns}
                  variant="grouped"
                  reverse={true}
                />

                <BannerGrid apiUrl={`${baseURL}api/banner_api11.php`} banners={bannerTwoDesktop} columns={3} mobileColumns={1} />
                <CategoryProductMosaic columns={columns} variant="grouped" />
                <ResponsiveBannerSet apiUrl={`${baseURL}api/banner_api14.php`} banners={bannerTwoDesktop} />
                <FeaturedProductGrid data={gridsectionfirst} />
                <BannerGrid apiUrl={`${baseURL}api/banner_api13.php`} banners={bannerTwoDesktop} columns={3} mobileColumns={1} />
                <FeaturedProductGrid data={gridsectionsecond} />
                <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
                <CategoryProductMosaic title="Men's" apiUrl={`${baseURL}api/home-product-api.php?action=top_rated`} imageColumn={imageColumn} columns={columns} variant="flat" />
                <BannerGrid banners={sectiontwoimg} columns={1} mobileColumns={1} />
                <CategoryProductMosaic title="Women's" apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`} imageColumn={imageColumn} columns={columns} variant="flat" />
                <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=discount_for_you`} title="Recently Viewed" products={sampleItems} />
                <BrandDirectorySection />
                <BulkOrderWidget />


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
