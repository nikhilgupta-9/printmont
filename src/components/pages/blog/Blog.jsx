import React from "react";
import Categories from "../category-list/Categories";
import { MultiColumnBannerCarousel } from "../../home";
import TwoImgCarousel from "../carousel/TwoImgCarousel";
import { fourimgcarousel, twoimgcarousel } from "../../../../data/data";
import { Row, Col } from "react-bootstrap";
import BlogCard from "./BlogCard"; // ✅ Import reusable card
import { Link } from "react-router";

// --- BLOG DATA ---
const blogData = [
  {
    id: 1,
    category: "Corporate Gifts",
    title: "Title of Blog Post 1",
    imageSrc: "/men_shirt/men-shirt-1.png",
    author: "John Doe",
    date: "October 1, 2024",
    tags: "Design Trends",
    summary:
      "A brief introduction or summary of the blog post that gives readers an idea of what it is about.",
    description: "Short intro for post 1 goes here.",
    readMoreLink: "#",
  },
  {
    id: 2,
    category: "Business Gifts",
    title: "Title of Blog Post 2",
    imageSrc: "/sq/kid1.jpg",
    author: "Jane Smith",
    date: "October 5, 2024",
    tags: "Business Insights",
    summary:
      "A brief introduction or summary of the blog post that gives readers an idea of what it is about.",
    description: "Short intro for post 2 goes here.",
    readMoreLink: "#",
  },
  {
    id: 3,
    category: "Promotional Gifts",
    title: "Title of Blog Post 3",
    imageSrc: "/sq/men1.jpg",
    author: "John Doe",
    date: "October 10, 2024",
    tags: "Marketing Trends",
    summary:
      "A brief introduction or summary of the blog post that gives readers an idea of what it is about.",
    description: "Short intro for post 3 goes here.",
    readMoreLink: "#",
  },
  {
    id: 4,
    category: "Doctor Gifts",
    title: "Title of Blog Post 4",
    imageSrc: "/sq/kid2.jpg",
    author: "Jane Smith",
    date: "October 15, 2024",
    tags: "Business Insights",
    summary:
      "A brief introduction or summary of the blog post that gives readers an idea of what it is about.",
    description: "Short intro for post 4 goes here.",
    readMoreLink: "#",
  },
];

// --- SIDEBAR LINKS ---
const sidebarLinks = [
  { title: "Understanding the Latest Tech Trends", url: "#", img: "/sq/kid1.jpg" },
  { title: "Marketing Strategies for 2024", url: "#", img: "/sq/kid2.jpg" },
  { title: "10 Tips for Remote Work Productivity", url: "#", img: "/sq/kid3.jpg" },
  { title: "The Future of E-commerce Personalization", url: "#", img: "/sq/men1.jpg" },
];

// --- SIDEBAR COMPONENT ---
const Sidebar = () => {
  return (
    <div className="d-none d-lg-block h-100">
      <div
        className="p-3 border rounded bg-white shadow-sm sticky-top"
        style={{ top: "75px" }}
      >
        <h6 className="fw-bold mb-3 text-dark">Trending Topics</h6>
        <ul className="list-unstyled mb-0">
          {sidebarLinks.map((link, index) => (
            <li key={index} className="mb-2 d-flex align-items-center">
              <div className="border border-light mt-1 me-2">
                <img src={link.img} alt="" width={45} height={45} className="rounded" />
              </div>
              <Link
                href={link.url}
                className="text-decoration-none text-muted small d-block p-1 rounded hover-bg-light"
              >
                {link.title}
              </Link>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
};

// --- MAIN BLOG PAGE ---
const Blog = () => {
  return (
    <div className="py-0">
      <div className="bg-white d-block d-lg-none py-0">
        <Categories space={"0px 0px"} showImages={true} color="white" />
        <div className="py-1 py-lg-3 py-xl-3 py-xxl-4"></div>
        <div>
          <Link to={'#'} className="text-decoration-none">
            <img src="/banners/blog-banner-2.png" width={'100%'} height={'100px'} alt="banner-1" />
          </Link>
        </div>
      </div>

      {/* --- TOP CAROUSEL --- */}
      <div className="d-none d-sm-none d-md-flex mb-0 mb-mb-2 mb-lg-4">
        <MultiColumnBannerCarousel banners={fourimgcarousel} columns={4} />
      </div>

      {/* --- MAIN BLOG CONTENT --- */}
      <div className="container bg-white">
        <div className="row mx-0 px-0">
          <div className="col-12 col-lg-9 px-1 pt-1">
            <div>
              <h3 className="text-center fw-normal pt-0 pt-md-4 d-none d-md-block">Latest Blog</h3>
              <TwoImgCarousel images={twoimgcarousel} showDetails={false} />
            </div>
            <div className="mb-1 mb-md-3 d-block d-md-none">
              <Link to={'#'} className="text-decoration-none ">
                <img src="/banners/blog-banner-2.png" width={'100%'} height={'100px'} alt="banner-1" />
              </Link>
            </div>

            {/* --- GOOGLE ADS PLACEHOLDER --- */}
            <div
              className="text-center p-5 mb-2 mb-md-3 border rounded d-none d-md-block"
              style={{ backgroundColor: "#f8f9fa" }}
            >
              <h4 className="text-secondary">Google Ads Banner</h4>
            </div>
            <div className="mt-3">
              <h4 className="text-center fw-semibold">Relcently Posts</h4>
              <TwoImgCarousel images={twoimgcarousel} showDetails={true} />
            </div>
            {/* --- BLOG CARDS (2 per row) --- */}
            <Row className="g-0 g-md-0 px-0 mx-0 mt-1">
              {blogData.map((data) => (
                <Col key={data.id} xs={12} md={6} className="mx-0 px-0">
                  <Link to={`/blog/${data.slug}`} className="text-decoration-none">
                    <BlogCard cardData={data} />
                  </Link>
                </Col>
              ))}
            </Row>

            {/* --- ADDITIONAL BLOG SECTIONS --- */}
            <div
              className="text-center p-5 my-2 my-md-3 mb-md-3 border rounded"
              style={{ backgroundColor: "#f8f9fa" }}
            >
              <h4 className="text-secondary">Google Ads Banner</h4>
            </div>
            <div className="mt-3 mt-md-4 mt-lg-5 d-none d-md-block">
              <h4 className="text-center fw-semibold">Popular Blogs</h4>
              <TwoImgCarousel images={twoimgcarousel} showDetails={true} />
            </div>
            <div className="mt-3 mt-md-4 mt-lg-5 d-none d-md-block">
              <h4 className="text-center fw-semibold">Printing Blogs</h4>
              <TwoImgCarousel images={twoimgcarousel} showDetails={true} />
            </div>
            <div className="mt-3 mt-md-4 mt-lg-5 d-none d-md-block">
              <h4 className="text-center fw-semibold">All Blogs</h4>
              <TwoImgCarousel images={twoimgcarousel} showDetails={true} />
            </div>



          </div>

          {/* --- RIGHT SIDEBAR --- */}
          <div className="col-12 col-lg-3 mt-4 mt-lg-0 z-1">
            <img src="" alt="" />
            <Sidebar />
          </div>
        </div>
      </div>
    </div>
  );
};

export default Blog;
