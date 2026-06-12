import React, { useEffect, useState } from 'react'
import Categories from './category-list/Categories.jsx'
import FirstCarousel from './carousel/FirstCarousel.jsx'
import SectionOne from './sections/SectionOne'
import SecondCarousel from './carousel/SecondCarousel'
import SectionTwo from './sections/SectionTwo'
import Banner from './sections/Banner'
import BannerTwo from './sections/BannerTwo'
import { discount, bestsellerProduct, bestsellerProducts, girloutfit, homeDecorItems, tablewareItems, tablewareItems2, homeDecorItems2, dealsandcategories, columns, gridsectionfirst, gridsectionsecond, bannerImages, bannerSet1, sectiontwoimg, bannerTwoDesktop, bannerTwoMobile, threeimgcarousel, fourimgcarousel, sampleItems, imageColumn, } from '../../../data/data'
import Singleproduct from './carousel/Singleproduct'
import SectionFour from './sections/SectionFour'
import SectionFourReverse from './sections/SectionFourReverse'
import SectionGrid from './sections/SectionGrid.jsx'
import BrandDirectory from './sections/BrandDirectory'
import SectionEight from './sections/SectionEight.jsx'
import Slider from './carousel/Slider.jsx'
import BulkOrder from './sections/BulkOrder.jsx'
import MobHome from './MobHome.jsx'
import GiftFinder from './sections/GiftFinder.jsx'
import ThreeImgCarousel from './carousel/ThreeImgCarousel.jsx'
import FourImgCarousel from './carousel/FourImgCarousel.jsx'
import SectionTen from './sections/SectionTen.jsx'

const Home = () => {

const baseURL = import.meta.env.VITE_BASE_URL;
console.log(baseURL)

  return (
    <>
      {/* Desktop Home */}
      <div className='d-none d-lg-block custom-bg w-100'>
        <Categories space={"5px 0px"} bg='white' isSticky={true}/>

        <div className='home-desktop-content mx-auto home-desktop-wrapper'>
          <div className='relative w-100'>
            <div>
              {/* <FirstCarousel images={`${baseURL}api/banner-api.php`} carouselId="mainCarousel" /> */}
              <FirstCarousel apiUrl={`${baseURL}api/banner_api.php`} basePath={`${baseURL}uploads/banners/`}/>
            </div>

          <Slider apiUrl={`${baseURL}api/banner_api.php`} />


          <div className='bg-transparent'>
            {/* <SectionOne banners={bannerSet1}/> */}
            <SectionOne apiUrl={`${baseURL}api/banner_api.php`} />


          </div>
          <div>
            {/* <ThreeImgCarousel images={threeimgcarousel} /> */}
            <ThreeImgCarousel apiUrl={`${baseURL}api/banner_api.php`} />
          </div>
          <div>
            {/* <FourImgCarousel images={fourimgcarousel}/> */}
            <FourImgCarousel apiUrl={`${baseURL}api/banner_api.php`} />
          </div>

          <div className=''>
            {/* <SecondCarousel products={bestsellerProducts} title="Our Bestellers" badgeText="Customizable" /> */}
            <SecondCarousel apiUrl={`${baseURL}api/bestseller-products.php`} title="Our Bestellers" badgeText="Customizable" />
          </div>

          <div className='px-2'>
            {/* <SectionTwo images={sectiontwoimg} /> */}
            <SectionTwo images={sectiontwoimg} apiurl={`${baseURL}api/banner_api.php`} />
          </div>
          <GiftFinder />

          <div className='mt-lg-2 pt-lg-4 pt-1'>
            {/* <SecondCarousel products={bestsellerProduct} title="Top Selection" badgeText="Customizable" /> */}
            <SecondCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`} title="Top Selection" badgeText="Customizable" />
          </div>

          <div className='my-2 mx-0 px-0'>
            <Banner apiUrl={`${baseURL}api/banner_api6.php`} />
          </div>

          <div className='  pt-lg-2 pt-1'>
            <SecondCarousel apiUrl={`${baseURL}api/home-product-api.php?action=discount_for_you`} title="Discount For You" badgeText="Customizable" />
          </div>

          <div>
            <BannerTwo apiUrl={`${baseURL}api/banner_api7.php`} />
          </div>

          <div className='my-1'>
            <SecondCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_rated`} title="Top Rated" badgeText="Customizable" />
          </div>

          <div className='my-1'>
            <BannerTwo apiUrl={`${baseURL}api/banner_api8.php`} />
          </div>

          <div className='my-1'>
            <SecondCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`} title="Top Deals and Categories" badgeText="Customizable" />
          </div>

          <div className='my-1'>
            <BannerTwo apiUrl={`${baseURL}api/banner_api9.php`} />
          </div>

          <div className='my-1'>
            <Singleproduct products={girloutfit} title="Women's Outfits" />
          </div>

          <div>
            <SectionFour
              apiUrl={`${baseURL}api/home-product-api.php?action=grouped_categories`}
              backgroundImageUrl="https://example.com/bg.png"
            />
          </div>

          <div className='mt-1 mx-0'>
            {/* <Banner apiUrl={`${baseURL}api/banner_api10.php`} /> */}
          </div>
          <div className='custom-bg'>
            <SectionFourReverse
              apiUrl={`${baseURL}api/home-product-api.php?action=grouped_categories`}
              backgroundImageUrl="https://example.com/bg.png"
              imageColumn={{
                imageUrl: "/girl-product-img/girl-1.webp",
                alt: "Featured Product",
              }}
            />

          </div>

          <div className='mt-3 mx-0 px-0'>
            <Banner apiUrl={`${baseURL}api/banner_api11.php`} />
          </div>

          <div>
            <SectionEight columns={columns} />
          </div>
          <div className='mt-3 mx-0 px-0'>
            <BannerTwo apiUrl={`${baseURL}api/banner_api14.php`} />
          </div>

          <div>
            <SectionGrid data={gridsectionfirst} />
          </div>
          <div className='mt-3 mx-0 px-0'>
            <Banner apiUrl={`${baseURL}api/banner_api13.php`} />
          </div>
          <div className='my-0 my-lg-4'>
            <SectionGrid data={gridsectionsecond} />
          </div>
          <SectionTwo images={sectiontwoimg} />
          <div>
            <SectionTen  title="Men's" items={sampleItems} imageColumn={imageColumn}/>
          </div>
          <SectionTwo images={sectiontwoimg} />

          <div>
            <SectionTen  title="Women's" items={sampleItems} imageColumn={imageColumn}/>
          </div>

          <div className='mt-1'>
            <Singleproduct products={girloutfit} title="Recently Viewed" />
          </div>


          <div className='mt-0 mt-lg-4'>
            <BrandDirectory />
          </div>

          <BulkOrder />
        </div>
      </div>
    </div>
      <div className='d-block d-lg-none'>
        <MobHome />
      </div>
    </>
  )
}

export default Home
