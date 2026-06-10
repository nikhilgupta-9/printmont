import React from 'react'

import Header from './components/header/Header'
import Home from './components/pages/Home'
import Footer from './components/footer/Footer'
import MobileHeader from './components/header/MobileHeader';
import MobHome from './components/pages/MobHome';


const Pages = () => {
  return (
    <>  
        {/* Desktop Header */}
      <div ><Header/></div>

      <div className='home-main px-1'>
      <div><Home/> </div>
      <div ><MobHome/></div>
      </div>

      <Footer/>
      </>
  )
}

export default Pages