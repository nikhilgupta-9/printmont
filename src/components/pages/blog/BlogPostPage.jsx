import React from "react";
import { Container, Row, Col } from "react-bootstrap";
import "./blog.css";
import Categories from "../category-list/Categories";

const BlogPostPage = () => {
  const recentPosts = [
    {
      title: "Unique Children's Day Gifts for School",
      img: "/blog/blog-img-1.png",
    },
    {
      title: "How to Plan a Memorable Kids Fair for National Vise Day",
      img: "/blog/blog-img-1.png",
    },
    {
      title: "Beautiful Ganesh Decoration Ideas for Home",
      img: "/blog/blog-img-1.png",
    },
    {
      title: "Creative Ways to Decorate Your Home for Janmashtami",
      img: "/blog/blog-img-1.png",
    },
    {
      title: "Top 10 Romantic Gifts to Surprise Your Partner",
      img: "/blog/blog-img-1.png",
    },
  ];

  return (
    <>
    <div className="d-block d-lg-none">
      <Categories bg="rgb(11, 83, 161)" color="white" showImages={false} space="15px 0"/>
    </div>
    <Container className="blog-page mt-5">
      <Row>
        {/* LEFT — Blog Content */}
        <Col lg={9} md={12}>
          <div className="blog-content">
            <p className="text-muted small mb-1">Other Occasions</p>
            <h2 className="fw-bold mb-2">
              10 Creative Classroom Activities for Children’s Day
            </h2>
            <p className="text-muted small mb-4">
              November 5, 2025 · by Priya Lamba · 5 min read
            </p>

            <img
              src="/blog/blog-img-2.png"
              alt="Children's Day Activities"
              className="img-fluid rounded mb-4"
            />

            <p className="text-secondary lh-lg">
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

            <p className="text-secondary lh-lg">
              Every classroom carries its own rhythm. Some teachers prefer calm,
              reflective sessions; others, playful chaos. Either way, children’s
              day ideas for school work best when they balance fun and meaning.
              Below are ten activity concepts designed for flexible classrooms,
              adaptable, inexpensive, and full of participation.
            </p>

            <h5 className="fw-semibold mt-4 mb-3">Art Wall Extravaganza</h5>

            <div className="d-flex justify-content-center">
                <img
              src="/blog/blog-img-3.webp"
              alt="Art Wall"
              className="mb-3 rounded-0"
              style={{maxheight:'180px', maxWidth:'180px'}}
            />
            </div>

            <p className="text-secondary lh-lg">
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
        <Col lg={3} md={12}>
          <div className="right-contain">
            <div className="sidebar">
            <img
              src="/blog/banner.webp"
              alt="Ad Banner"
              className="img-fluid rounded mb-4"
              style={{height:'180px', width:'280px', objectFit:'contain'}}
            />
          </div>

          <div className="recent-posts p-2 bg-white rounded shadow-sm">
            <h6 className="fw-bold mb-3">Recent Posts</h6>
            {recentPosts.map((post, i) => (
              <div key={i} className="d-flex mb-0 align-items-center">
                <img
                  src={post.img}
                  alt={post.title}
                  className="recent-img rounded me-3"
                  style={{width:'100px', height:'70px', objectFit:'contain'}}
                />
                <p className="small mb-0 text-dark fw-medium">
                  {post.title}
                </p>
              </div>
            ))}
          </div>
          </div>
        </Col>
      </Row>
    </Container>
    </>
  );
};

export default BlogPostPage;
