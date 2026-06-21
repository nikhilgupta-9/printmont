import React from 'react'

import Categories from './category-list/Categories'
import BannerTwo from './sections/BannerTwo'
import Bannerthree from './Bannerthree'
import Slider from './carousel/Slider'
import SectionOne from './sections/SectionOne'
import SecondCarousel from './carousel/SecondCarousel'


import { bannerImages, bannerSet1, bannerTwoDesktop, bannerTwoMobile, bestHealth, bestsellerProduct, bestsellerProducts, budsItems, dealsandcategories, girloutfit, gridproducts, gridsectionfirst, gridsectionsecond, homeDecorItems, mensFashionItems, mobileItems, productList, sampleItems, sampleProducts, sectiontwoimg, specialoffer, tablewareItems, womensFashionItems } from '../../../data/data'
import SingleProduct from './carousel/Singleproduct'
import SectionTwo from './sections/SectionTwo'
import ProductGrid from './sections/ProductGrid'
import SectionFour from './sections/SectionFour'
import SectionNine from './sections/SectionNine'
import Banner from './sections/Banner'
import SectionEightSingle from './sections/SectionEightSingle'
import ProductList from './sections/ProductList'
import SectionSix from './sections/SectionGrid'
import SectionGrid from './sections/SectionGrid'
import BrandDirectory from './sections/BrandDirectory'
import FirstCarousel from './carousel/FirstCarousel'
import SectionTen from './sections/SectionTen'

const MobHome = () => {
const baseURL = import.meta.env.VITE_BASE_URL;

  return (    
    <>
    <div className='bg-white w-100 home-mobile-content home-layout-gap'>
        <div className='top-5'> <Categories isSticky={false}/></div>

        <div>
          {/* <FirstCarousel images={fristcrouselImg} carouselId="mainCarousel" /> */}
          <FirstCarousel apiUrl={`${baseURL}api/banner_api.php`} basePath={`${baseURL}uploads/banners/`}/>
        </div>

        <Slider apiUrl={`${baseURL}api/banner_api.php`} />

        <div>
            <SectionOne apiUrl={`${baseURL}api/banner_api.php`}/>
        </div>

        <div className='bg-white'>
            <SingleProduct apiUrl={`${baseURL}api/home-product-api.php?action=top_rated`} title="Recently Viewed" />
        </div>

        <div><SectionTwo images={sectiontwoimg} apiurl={`${baseURL}api/banner_api.php`}/></div>

        <div><ProductGrid title="End of Season Sale" apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`} /></div>

        <div>
            <SectionFour   columns={[ {
                  title: 'Tableware & Dinnerware',
                  items: tablewareItems,
                },
            ]}
            backgroundImageUrl="./bg/super.png"
            />
        </div>

        <div>
            <SingleProduct products={specialoffer} title='Only for 1 Hour' backgroundImageUrl="./bg/flashsale.png"/>
        </div>

        <div>
             <Banner images={bannerImages} />
        </div>

        <div><SectionNine products={sampleProducts}/></div>

        <div className='bg-white'>
            {/* Pass data from API only for one section like home decor or dinnerware */}
            <SectionFour
              apiUrl={`${baseURL}api/home-product-api.php?action=grouped_categories`}
              backgroundImageUrl="./bg/bg-3.png"
              imageColumn={{
                imageUrl: "/girl-product-img/girl-1.webp",
                alt: "Featured",
              }}
            /> 
        </div>

        <div>
            <SectionFour columns={[ {
                  title: 'Women\'s Fashion',
                  items: womensFashionItems,
                }, ]}
            backgroundImageUrl="./bg/bg-4.png" />
        </div>

        <div>
        {/* <SecondCarousel products={bestsellerProduct} title="Top Selection" /> */}
        <SecondCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`} title="Top Selection" badgeText="Customizable" />
      </div>

      <div><Banner images={bannerImages} /></div>

      <div>
        {/* <SecondCarousel products={bestsellerProducts} title="Discount For You" /> */}
        <SecondCarousel apiUrl={`${baseURL}api/home-product-api.php?action=discount_for_you`} title="Discount For You" badgeText="Customizable" />
      </div>

      <div><Banner images={bannerImages} /></div>

      <div>
        {/* <SecondCarousel products={dealsandcategories} title="Top Deals on categories" /> */}
        <SecondCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`} title="Top Deals on categories" badgeText="Customizable" />
      </div>

      <div><Banner images={bannerImages} /></div>

      <div className='defc'>
        <SingleProduct apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`} title="Women's" />
      </div>

      <div><SectionOne banners={bannerSet1}/></div>

      <div>
            <BannerTwo apiUrl={`${baseURL}api/banner_api.php`} />
      </div>
      
      <div>
            <SectionFour   columns={[ {
                  title: 'EarBuds & Headphones',
                  items: budsItems,
                },]}
            backgroundImageUrl="./bg/bg-4.png"
            />
        </div>

      <div>
        <SecondCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`} title="Top Deals on categories" badgeText="Customizable" />        
      </div>
        <div>
          <ProductList apiUrl={`${baseURL}api/home-product-api.php?action=discount_for_you`}/>
        </div>
      <div>
         <SectionFour columns={[ {
                      title: 'Home Decor Items',
                      items: homeDecorItems,
                    },]}
            backgroundImageUrl="./bg/bg-4.png"
            />
      </div>

        <div><ProductList apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`}/></div>

        <div><Banner images={bannerImages} /></div>

        <div>
            <SectionFour   columns={[ {
                  title: 'Mobiles',
                  items: mobileItems,
                },]}
            backgroundImageUrl="./bg/bg-5.png"
            />
        </div>

        <div><ProductList apiUrl={`${baseURL}api/home-product-api.php?action=top_rated`}/></div>

        <div><ProductGrid title="Great Choices" apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`} bgImage={'/bg/grid-product-bg.png'} /></div>

        <div><ProductList apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`}/></div>

      <div>
        <SectionGrid/>
      </div>

      <div>
            <SectionFour   columns={[ {
                  title: 'Men\'s Fashion',
                  items: mensFashionItems,
 
                },
            ]}
            backgroundImageUrl="./bg/bg-3.png"
            />
        </div>
        <div><Banner images={bannerImages} /></div>
        <div>
          <SectionGrid data={gridsectionfirst} />
        </div>

        <div>
          <Bannerthree/>
        </div>

        <div>
          <SectionGrid data={gridsectionsecond} />
        </div>

        <div>
            <SectionFour   columns={[ {
                  title: 'New Fashion',
                  items: mensFashionItems,
 
                },
            ]}
            backgroundImageUrl="./bg/bg-3.png"
            />
        </div>

        <div><ProductList apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`}/></div>

        <div>
          {/* <SecondCarousel products={bestHealth} title="Best of Health & Wellness" /> */}
        <SecondCarousel apiUrl={`${baseURL}api/home-product-api.php?action=discount_for_you`} title="Best of Health & Wellness" badgeText="Customizable" />
        </div>

        <div>
          {/* <SecondCarousel products={sampleProducts} title="Electronics" /> */}
          <SecondCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_rated`} title="Electronics" badgeText="Customizable" />
          
        </div>

        <div><Banner images={bannerImages} /></div>

        <div><ProductList apiUrl={`${baseURL}api/home-product-api.php?action=top_rated`}/></div>

        <div>
          {/* <SecondCarousel products={bestHealth} title="Home Usage" /> */}
          <SecondCarousel apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`} title="Home Usage" badgeText="Customizable" />
        </div>

        <div>
            <SectionFour   columns={[ {
                  title: 'Best for Health',
                  items: mensFashionItems,
 
                },
            ]}
            backgroundImageUrl="./bg/bg-3.png"
            />
        </div>

        <div><ProductList apiUrl={`${baseURL}api/home-product-api.php?action=discount_for_you`}/></div>
        
        <div className='bg-white'>
            <SingleProduct apiUrl={`${baseURL}api/home-product-api.php?action=top_selection`} title="Recently Viewed" />
        </div>

        <div><Bannerthree/></div>
        
        <div>
            <Banner images={bannerImages} />
        </div>
        <div>
            <SectionTen  title="Women's" apiUrl={`${baseURL}api/home-product-api.php?action=top_deal`}/>
        </div>

        <div>
          <BrandDirectory/>
        </div>
    </div>
    </>
  )
}

export default MobHome