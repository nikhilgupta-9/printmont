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
  MobileProductList,
  CategoryProductMosaic,
  FeaturedProductGrid,
  BrandDirectorySection
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
  sampleProducts
} from '../../../data/data'

const MobHome = () => {
  const baseURL = import.meta.env.VITE_BASE_URL;

  return (    
    <>
      <div className='bg-white w-100 home-mobile-content home-layout-gap'>
        <Categories isSticky={false}/>
        <ResponsiveHeroCarousel apiUrl={`${baseURL}api/banner_api.php`} basePath={`${baseURL}uploads/banners/`}/>
        <MobileHeroSlider apiUrl={`${baseURL}api/banner_api.php`} />
        <BannerGrid apiUrl={`${baseURL}api/banner_api.php`} sectionKey="home_above_fold" columns={4} mobileColumns={2} />
        <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_rated`} title="Recently Viewed" />
        <BannerGrid banners={sectiontwoimg} apiUrl={`${baseURL}api/banner_api.php`} sectionKey="home_mid_section_3" columns={1} mobileColumns={1} />
        <CompactProductGrid title="End of Season Sale" apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`} />
        
        <CategoryProductMosaic
          columns={[
            {
              title: 'Tableware & Dinnerware',
              items: tablewareItems,
            },
          ]}
          backgroundImageUrl="./bg/super.png"
          variant="grouped"
        />
        
        <ProductCarousel products={specialoffer} title='Only for 1 Hour' backgroundImageUrl="./bg/flashsale.png"/>
        <BannerGrid banners={bannerImages} columns={2} mobileColumns={1} />
        <SectionNine products={sampleProducts}/>
        
        <CategoryProductMosaic
          apiUrl={`${baseURL}api/home-product-api.php?action=grouped_categories`}
          backgroundImageUrl="./bg/bg-3.png"
          imageColumn={{
            imageUrl: "/girl-product-img/girl-1.webp",
            alt: "Featured",
          }}
          variant="grouped"
        /> 
        
        <CategoryProductMosaic
          columns={[
            {
              title: "Women's Fashion",
              items: womensFashionItems,
            },
          ]}
          backgroundImageUrl="./bg/bg-4.png"
          variant="grouped"
        />
        
        <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`} title="Top Selection" badgeText="Customizable" />
        <BannerGrid banners={bannerImages} columns={2} mobileColumns={1} />
        <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=discount_for_you`} title="Discount For You" badgeText="Customizable" />
        <BannerGrid banners={bannerImages} columns={2} mobileColumns={1} />
        <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`} title="Top Deals on categories" badgeText="Customizable" />
        <BannerGrid banners={bannerImages} columns={2} mobileColumns={1} />
        <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`} title="Women's" />
        <BannerGrid banners={bannerSet1} columns={4} mobileColumns={2} />
        <ResponsiveBannerSet apiUrl={`${baseURL}api/banner_api.php`} />
        
        <CategoryProductMosaic
          columns={[
            {
              title: 'EarBuds & Headphones',
              items: budsItems,
            },
          ]}
          backgroundImageUrl="./bg/bg-4.png"
          variant="grouped"
        />
        
        <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`} title="Top Deals on categories" badgeText="Customizable" />        
        <MobileProductList apiUrl={`${baseURL}api/home-product-api.php?action=discount_for_you`}/>
        
        <CategoryProductMosaic
          columns={[
            {
              title: 'Home Decor Items',
              items: homeDecorItems,
            },
          ]}
          backgroundImageUrl="./bg/bg-4.png"
          variant="grouped"
        />
        
        <MobileProductList apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`}/>
        <BannerGrid banners={bannerImages} columns={2} mobileColumns={1} />
        
        <CategoryProductMosaic
          columns={[
            {
              title: 'Mobiles',
              items: mobileItems,
            },
          ]}
          backgroundImageUrl="./bg/bg-5.png"
          variant="grouped"
        />
        
        <MobileProductList apiUrl={`${baseURL}api/home-product-api.php?action=top_rated`}/>
        <CompactProductGrid title="Great Choices" apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`} bgImage={'/bg/grid-product-bg.png'} />
        <MobileProductList apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`}/>
        <FeaturedProductGrid />
        
        <CategoryProductMosaic
          columns={[
            {
              title: "Men's Fashion",
              items: mensFashionItems,
            },
          ]}
          backgroundImageUrl="./bg/bg-3.png"
          variant="grouped"
        />
        
        <BannerGrid banners={bannerImages} columns={2} mobileColumns={1} />
        <FeaturedProductGrid data={gridsectionfirst} />
        <StaticResponsiveBanner />
        <FeaturedProductGrid data={gridsectionsecond} />
        
        <CategoryProductMosaic
          columns={[
            {
              title: 'New Fashion',
              items: mensFashionItems,
            },
          ]}
          backgroundImageUrl="./bg/bg-3.png"
          variant="grouped"
        />
        
        <MobileProductList apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`}/>
        <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=discount_for_you`} title="Best of Health & Wellness" badgeText="Customizable" />
        <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_rated`} title="Electronics" badgeText="Customizable" />
        <BannerGrid banners={bannerImages} columns={2} mobileColumns={1} />
        <MobileProductList apiUrl={`${baseURL}api/home-product-api.php?action=top_rated`}/>
        <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`} title="Home Usage" badgeText="Customizable" />
        
        <CategoryProductMosaic
          columns={[
            {
              title: 'Best for Health',
              items: mensFashionItems,
            },
          ]}
          backgroundImageUrl="./bg/bg-3.png"
          variant="grouped"
        />
        
        <MobileProductList apiUrl={`${baseURL}api/home-product-api.php?action=discount_for_you`}/>
        <ProductCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`} title="Recently Viewed" />
        <StaticResponsiveBanner />
        <BannerGrid banners={bannerImages} columns={2} mobileColumns={1} />
        <CategoryProductMosaic title="Women's" apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`} variant="flat" />
        <BrandDirectorySection />
      </div>
    </>
  )
}

export default MobHome;