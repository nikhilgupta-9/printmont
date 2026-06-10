import React from 'react'

const Bannerthree = () => {
  return (
    <div className='container-fluid p-0 custom-bg'>
        <img src="./section-img/desktop-banner.jpg" className='d-none d-md-block' alt="image not found" width={"100%"}  />
        <img src="./section-img/mobile-banner.jpg" className='d-block d-md-none' alt="image not found" width={"100%"}  />
    </div>
  )
}

export default Bannerthree