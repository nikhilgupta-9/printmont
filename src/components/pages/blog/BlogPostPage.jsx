import React from "react";
import { Container, Row, Col, Badge } from "react-bootstrap";
import "./blog.css";
import Categories from "../category-list/Categories";
import { Link } from "react-router-dom";
import { MdOutlineKeyboardArrowRight, MdAccessTime, MdPerson, MdCalendarToday } from "react-icons/md";

const BlogPostPage = () => {
  const recentPosts = [
    {
      id: 1,
      title: "Unique Children's Day Gifts for School",
      img: "/blog/blog-img-1.png",
      date: "Nov 10, 2025"
    },
    {
      id: 2,
      title: "How to Plan a Memorable Kids Fair for National Vise Day",
      img: "/blog/blog-img-1.png",
      date: "Nov 08, 2025"
    },
    {
      id: 3,
      title: "Beautiful Ganesh Decoration Ideas for Home",
      img: "/blog/blog-img-1.png",
      date: "Nov 05, 2025"
    },
    {
      id: 4,
      title: "Creative Ways to Decorate Your Home for Janmashtami",
      img: "/blog/blog-img-1.png",
      date: "Oct 28, 2025"
    },
    {
      id: 5,
      title: "Top 10 Romantic Gifts to Surprise Your Partner",
      img: "/blog/blog-img-1.png",
      date: "Oct 20, 2025"
    },
  ];

  return (
    <div className="bg-light py-2 py-md-4">
      <div className="d-block d-lg-none mb-3">
        <Categories bg="rgb(11, 83, 161)" color="white" showImages={false} space="10px 0"/>
      </div>

      <Container className="blog-page">
        <Row className="g-3 g-lg-4">
          {/* LEFT — Blog Content */}
          <Col lg={9} md={12} xs={12}>
            <div className="blog-content bg-white p-3 p-md-4 p-lg-5 rounded-3 shadow-sm border">
              <Badge bg="primary-subtle" className="text-primary fw-bold px-3 py-1 mb-2 fs-7 rounded-pill text-uppercase">
                Other Occasions
              </Badge>

              <h1 className="fw-bold mb-3 text-dark fs-3 fs-md-2" style={{ lineHeight: "1.3" }}>
                10 Creative Classroom Activities for Children’s Day
              </h1>

              <div className="d-flex flex-wrap align-items-center text-muted small mb-4 gap-3 border-bottom pb-3">
                <span className="d-flex align-items-center gap-1">
                  <MdCalendarToday className="text-primary" size={15} /> November 5, 2025
                </span>
                <span className="d-flex align-items-center gap-1">
                  <MdPerson className="text-primary" size={16} /> Priya Lamba
                </span>
                <span className="d-flex align-items-center gap-1">
                  <MdAccessTime className="text-primary" size={15} /> 5 min read
                </span>
              </div>

              {/* Main Banner Image */}
              <div className="mb-4 overflow-hidden rounded-3 shadow-sm" style={{ maxHeight: "420px" }}>
                <img
                  src="/blog/blog-img-2.png"
                  alt="Children's Day Activities"
                  className="w-100 h-100 img-fluid rounded-3"
                  style={{ objectFit: "cover", width: "100%", maxHeight: "420px" }}
                />
              </div>

              <p className="text-secondary lh-lg fs-6 mb-3">
                Teachers and students look forward to the day as a break from
                routine learning. Children’s day is just that: decorating the
                classroom, playing games, and having fun to make this day
                memorable. Children’s day celebration in school appreciates
                curiosity and innocence that shape young minds. The event is often
                a blend of fun and thought, to entertain but to create memories
                within learning spaces. In this blog, you will find some creative
                classroom activities to help you organise a fun celebration for
                kids.
              </p>

              <p className="text-secondary lh-lg fs-6 mb-4">
                Every classroom carries its own rhythm. Some teachers prefer calm,
                reflective sessions; others, playful chaos. Either way, children’s
                day ideas for school work best when they balance fun and meaning.
                Below are ten activity concepts designed for flexible classrooms,
                adaptable, inexpensive, and full of participation.
              </p>

              <h4 className="fw-bold text-dark mt-4 mb-3 fs-5">Art Wall Extravaganza</h4>

              <div className="d-flex justify-content-center my-3">
                <img
                  src="/blog/blog-img-3.webp"
                  alt="Art Wall"
                  className="img-fluid rounded-3 shadow-sm"
                  style={{ maxHeight: "240px", width: "100%", maxWidth: "400px", objectFit: "cover" }}
                />
              </div>

              <p className="text-secondary lh-lg fs-6">
                An entire wall, dedicated to imagination. Give students coloured
                paper, markers, and free rein to depict what joy means to them.
                This activity not only brightens the classroom but lets art speak
                louder than words. Such children’s day activities often reveal
                unexpected talent and foster teamwork as children learn to combine
                their small ideas into something collective.
              </p>
            </div>
          </Col>

          {/* RIGHT — Sidebar */}
          <Col lg={3} md={12} xs={12}>
            <div className="right-contain sticky-top" style={{ top: "135px", zIndex: 10 }}>
              {/* Ad Banner */}
              <div className="sidebar mb-3 text-center">
                <img
                  src="/blog/banner.webp"
                  alt="Ad Banner"
                  className="img-fluid rounded-3 shadow-sm border w-100"
                  style={{ maxHeight: "200px", objectFit: "cover" }}
                />
              </div>

              {/* Recent Posts */}
              <div className="recent-posts p-3 bg-white rounded-3 shadow-sm border">
                <h6 className="fw-bold mb-3 text-dark border-bottom pb-2">Recent Posts</h6>
                {recentPosts.map((post) => (
                  <div key={post.id} className="d-flex align-items-center mb-3">
                    <img
                      src={post.img}
                      alt={post.title}
                      className="recent-img rounded-2 flex-shrink-0 me-3"
                      style={{ width: "70px", height: "55px", objectFit: "cover" }}
                    />
                    <div className="flex-grow-1 min-w-0">
                      <Link to="#" className="small mb-1 text-dark fw-semibold d-block text-decoration-none text-truncate hover-primary" title={post.title}>
                        {post.title}
                      </Link>
                      <span className="text-muted" style={{ fontSize: "0.72rem" }}>{post.date}</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default BlogPostPage;
