import React from 'react'

import Categories from './category-list/Categories'
import BannerTwo from './sections/BannerTwo'
import Bannerthree from './Bannerthree'
import FirstCrousel from './carousel/FirstCarousel'
import Slider from './carousel/Slider'
import SectionOne from './sections/SectionOne'
import SecondCarousel from './carousel/SecondCarousel'


import { bannerImages, bannerSet1, bannerTwoDesktop, bannerTwoMobile, bestHealth, bestsellerProduct, bestsellerProducts, budsItems, dealsandcategories, fristcrouselImg, girloutfit, gridproducts, gridsectionfirst, gridsectionsecond, homeDecorItems, mensFashionItems, mobileItems, productList, sampleProducts, sectiontwoimg, specialoffer, tablewareItems, womensFashionItems } from '../../../data/data'
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
import Header from '../header/Header'
import MobileHeader from '../header/MobileHeader'
import FirstCarousel from './carousel/FirstCarousel'

const MobHome = () => {
  return (
    
    <>
    <div className='bg-white w-100'>
      <div>
        {/* <Header/> */}
      </div>
        <div className='top-5'> <Categories/></div>

        <div><FirstCarousel images={fristcrouselImg} carouselId="mainCarousel" /></div>
        <div><Slider/></div>

        <div><SectionOne banners={bannerSet1}/></div>

        <div className='bg-white'>
            <SingleProduct products={girloutfit} title="Recently Viewed" />
        </div>

        <div><SectionTwo images={sectiontwoimg}/></div>

        <div><ProductGrid title="End of Season Sale" products={gridproducts} /></div>

        <div>
            <SectionFour   columns={[ {
                  title: 'Tableware & Dinnerware',
                  items: tablewareItems,

                },
            ]}
            backgroundImageUrl="./bg/super.png"
            />
        </div>

        <div className='mt-2'>
            <SingleProduct products={specialoffer} title='Only for 1 Hour' backgroundImageUrl="./bg/flashsale.png"/>
        </div>

        <div>
             <Banner images={bannerImages} />
        </div>

        <div><SectionNine products={sampleProducts}/></div>

        <div className='bg-white'>
            <SectionFour   columns={[ {
                  title: 'Men\'s Fashion',
                  items: mensFashionItems,

                },
            ]}
            backgroundImageUrl="./bg/bg-3.png"
            />
        </div>

        <div>
            <SectionFour columns={[ {
                  title: 'Women\'s Fashion',
                  items: womensFashionItems,
                }, ]}
            backgroundImageUrl="./bg/bg-4.png" />
        </div>

        <div className='mt-2'>
        <SecondCarousel products={bestsellerProduct} title="Top Selection" />
      </div>

      <div><Banner images={bannerImages} /></div>

      <div className='mt-2'>
        <SecondCarousel products={bestsellerProducts} title="Discount For You" />
      </div>

      <div><Banner images={bannerImages} /></div>

      <div className='mt-2'>
        <SecondCarousel products={dealsandcategories} title="Top Deals on categories" />
      </div>

      <div><Banner images={bannerImages} /></div>

      <div className=' mt-lg-3 pt-lg-2 pt-1 defc'>
        <SingleProduct products={girloutfit} title="Women's" />
      </div>

      <div><SectionOne banners={bannerSet1}/></div>


      <div>
        <BannerTwo desktopImages={bannerTwoDesktop} mobileImages={bannerTwoMobile} />
      </div>
      
      <div>
            <SectionFour   columns={[ {
                  title: 'EarBuds & Headphones',
                  items: budsItems,
                },]}
            backgroundImageUrl="./bg/bg-4.png"
            />
        </div>

      <div className='mt-2'>
        <SecondCarousel products={dealsandcategories} title="Top Deals on categories" />
      </div>
        <div>
          <ProductList products={productList}/>
        </div>
      <div>
         <SectionFour columns={[ {
                     title: 'Home Decor Items',
                     items: homeDecorItems,
                   },]}
            backgroundImageUrl="./bg/bg-4.png"
            />
      </div>



        <div><ProductList products={productList}/></div>

        <div><Banner images={bannerImages} /></div>

        <div>
            <SectionFour   columns={[ {
                  title: 'Mobiles',
                  items: mobileItems,
                },]}
            backgroundImageUrl="./bg/bg-5.png"
            />
        </div>

        <div><ProductList products={productList}/></div>

        <div><ProductGrid title="Great Choices" products={gridproducts} bgImage={'/bg/grid-product-bg.png'} /></div>

        <div><ProductList products={productList}/></div>

      <div>
        <SectionGrid/>
      </div>

      <div className='mt-2'>
            <SectionFour   columns={[ {
                  title: 'Men\'s Fashion',
                  items: mensFashionItems,

                },
            ]}
            backgroundImageUrl="./bg/bg-3.png"
            />
        </div>
        <div><Banner images={bannerImages} /></div>
        <div className='mt-1'>
          <SectionGrid data={gridsectionfirst} />
        </div>

        <div>
          <Bannerthree/>
        </div>

        <div className='mt-1'>
          <SectionGrid data={gridsectionsecond} />
        </div>

        <div className='mt-2'>
            <SectionFour   columns={[ {
                  title: 'New Fashion',
                  items: mensFashionItems,

                },
            ]}
            backgroundImageUrl="./bg/bg-3.png"
            />
        </div>

        <div><ProductList products={productList}/></div>

        <div>
          <SecondCarousel products={bestHealth} title="Best of Health & Wellness" />
        </div>

        <div>
          <SecondCarousel products={sampleProducts} title="Electronics" />
        </div>

        <div><Banner images={bannerImages} /></div>

        <div><ProductList products={productList}/></div>

        <div>
          <SecondCarousel products={bestHealth} title="Home Usage" />
        </div>

        <div className='mt-2'>
            <SectionFour   columns={[ {
                  title: 'Best for Health',
                  items: mensFashionItems,

                },
            ]}
            backgroundImageUrl="./bg/bg-3.png"
            />
        </div>

        <div><ProductList products={productList}/></div>
        
        <div className='bg-white'>
            <SingleProduct products={girloutfit} title="Recently Viewed" />
        </div>




        <div className=''><Bannerthree/></div>
        
        <div>
            <Banner images={bannerImages} />
        </div>

        <div className='mt-2'>
          <BrandDirectory/>
        </div>
    </div>
    </>
  )
}

export default MobHome