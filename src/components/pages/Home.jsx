import React, { useEffect, useState } from 'react'
import Categories from './category-list/Categories.jsx'
import FirstCarousel from './carousel/FirstCarousel.jsx'
import SectionOne from './sections/SectionOne'
import SecondCarousel from './carousel/SecondCarousel'
import SectionTwo from './sections/SectionTwo'
import Banner from './sections/Banner'
import BannerTwo from './sections/BannerTwo'
import { discount, bestsellerProduct, bestsellerProducts, girloutfit, homeDecorItems, tablewareItems, tablewareItems2, homeDecorItems2, dealsandcategories, columns, gridsectionfirst, gridsectionsecond, bannerImages, bannerSet1, fristcrouselImg, sectiontwoimg, bannerTwoDesktop, bannerTwoMobile, } from '../../../data/data'
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

const Home = () => {


  

  return (
    <>
      {/* Desktop Home */}
      <div className='d-none d-lg-flex custom-bg'>
        <div className='relative w-100'>
          {/* <Header/> */}

          <div>
            <Categories />
          </div>

          <div>
            <FirstCarousel images={fristcrouselImg} carouselId="mainCarousel" />
          </div>

          <Slider />

          <div className='bg-transparent'>
            <SectionOne banners={bannerSet1}/>
          </div>

          <div className=''>
            <SecondCarousel products={bestsellerProducts} title="Our Bestellers" badgeText="Customizable" />
          </div>

          <div className='px-2'>
            <SectionTwo images={sectiontwoimg} />
            </div>
          <GiftFinder />

          <div className='mt-lg-2 pt-lg-4 pt-1'>
            <SecondCarousel products={bestsellerProduct} title="Top Selection" badgeText="Customizable" />
          </div>

          <div className='my-2 mx-0 px-0'>
            <Banner images={bannerImages} />
          </div>

          <div className='  pt-lg-2 pt-1'>
            <SecondCarousel products={discount} title="Discount For You" badgeText="Customizable" />
          </div>

          <div>
            <BannerTwo desktopImages={bannerTwoDesktop} mobileImages={bannerTwoMobile} />
          </div>

          <div className='my-1'>
            <SecondCarousel products={bestsellerProduct} title="Top Rated" badgeText="Customizable" />
          </div>

          <div className='my-1'>
            <BannerTwo desktopImages={bannerTwoDesktop} mobileImages={bannerTwoMobile} />
          </div>

          <div className='my-1'>
            <SecondCarousel products={dealsandcategories} title="Top Deals on Catgories" badgeText="Customizable" />
          </div>

          <div className='my-1'>
            <BannerTwo desktopImages={bannerTwoDesktop} mobileImages={bannerTwoMobile} />
          </div>

          <div className='my-1'>
            <Singleproduct products={girloutfit} title="Women's Outfits" />
          </div>

          <div>
            <SectionFour columns={[
              {
                title: 'Home Decor Items',
                items: homeDecorItems,
              },
              {
                title: 'Tableware & Dinnerware',
                items: tablewareItems,
              },
            ]}
              imageColumn={{
                imageUrl: '/girl-product-img/girl-1.webp',
                alt: 'Home Decor Showcase',
              }} />
          </div>

          <div className='mt-1 mx-0'>
            <Banner images={bannerImages} />
          </div>
          <div className='custom-bg'>
            <SectionFourReverse columns={[
              {
                type: 'image',
                imageUrl: './card/bear.jpeg',
                alt: 'Girl with Product',
              },
              {
                type: 'grid',
                title: 'Tableware & Dinnerware',
                items: tablewareItems2,
              },
              {
                type: 'grid',
                title: 'Home Decor Items',
                items: homeDecorItems2,
              },
            ]} />

          </div>

          <div className='mt-3 mx-0 px-0'>
            <Banner images={bannerImages} />
          </div>

          <div>
            <SectionEight columns={columns} />
          </div>
          <div className='mt-3 mx-0 px-0'>
            <BannerTwo desktopImages={bannerTwoDesktop} mobileImages={bannerTwoMobile} />
          </div>

          <div>
            <SectionGrid data={gridsectionfirst} />
          </div>
          <div className='mt-3 mx-0 px-0'>
            <Banner images={bannerImages} />
          </div>
          <div className='my-0 my-lg-4'>
            <SectionGrid data={gridsectionsecond} />
          </div>
          <SectionTwo />

          <div>
            <SectionFourReverse columns={[
              {
                type: 'image',
                imageUrl: '/girl-product-img/girl-1.webp',
                alt: 'Girl with Product',
              },
              {
                type: 'grid',
                title: 'Women',
                items: tablewareItems2,
              },
              {
                type: 'grid',
                title: '',
                items: homeDecorItems2,
              },
            ]} />
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
      <div className='d-block d-lg-none'>
        <MobHome />
      </div>
    </>
  )
}

export default Home
