import React, { useState, useEffect } from "react";
import { Container, Row, Col, Badge, Spinner, Button } from "react-bootstrap";
import { Link, useParams } from "react-router-dom";
import {
  FaRegClock, FaRegUser, FaArrowLeft, FaArrowRight, FaShareNodes,
  FaFacebookF, FaTwitter, FaLinkedinIn, FaWhatsapp, FaLink,
  FaRegCalendar, FaFolderOpen, FaTag
} from "react-icons/fa6";
import { toast } from "react-hot-toast";
import { API_ENDPOINTS, resolveImageUrl } from "../../../config/apiEndpoints";
import { updatePageSEO } from "../../../utils/seo";
import useHomeBanners from "../../home/hooks/useHomeBanners";
import BlogCard from "./BlogCard";
import "./blog.css";

const formatDate = (value) => {
  if (!value) return "Recently Published";
  const d = new Date(String(value).replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return String(value);
  return d.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" });
};

const stripHtml = (html) => String(html || "").replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();

const readingTime = (post) => {
  const words = stripHtml(post?.content || post?.excerpt || "").split(" ").filter(Boolean).length;
  return `${Math.max(3, Math.round(words / 160))} min read`;
};

const BlogPostPage = () => {
  const { slug } = useParams();
  const [post, setPost] = useState(null);
  const [recentPosts, setRecentPosts] = useState([]);
  const [relatedPosts, setRelatedPosts] = useState([]);
  const [loading, setLoading] = useState(true);

  // Dynamic admin breadcrumb background banner
  const { banners: breadcrumbBanners } = useHomeBanners(API_ENDPOINTS.BLOG_BANNER, "blog_page_banner");
  const breadcrumbBg = breadcrumbBanners[0]?.image || breadcrumbBanners[0]?.image_url_desktop;

  useEffect(() => {
    window.scrollTo(0, 0);
    let cancelled = false;

    const fetchPostDetails = async () => {
      try {
        setLoading(true);
        const [postRes, recentRes, allRes] = await Promise.allSettled([
          fetch(API_ENDPOINTS.BLOG_POST_BY_SLUG(slug)).then((r) => r.json()),
          fetch(API_ENDPOINTS.BLOG_RECENT(5)).then((r) => r.json()),
          fetch(API_ENDPOINTS.BLOG_POSTS).then((r) => r.json())
        ]);

        if (cancelled) return;

        if (postRes.status === "fulfilled" && postRes.value?.success && postRes.value?.data) {
          setPost(postRes.value.data);
        } else if (allRes.status === "fulfilled" && allRes.value?.success && Array.isArray(allRes.value.data)) {
          // Fallback search in all posts by slug or id
          const found = allRes.value.data.find(
            (p) => String(p.slug) === String(slug) || String(p.id) === String(slug)
          );
          if (found) setPost(found);
        }

        if (recentRes.status === "fulfilled" && recentRes.value?.success && Array.isArray(recentRes.value.data)) {
          setRecentPosts(recentRes.value.data);
        }

        if (allRes.status === "fulfilled" && allRes.value?.success && Array.isArray(allRes.value.data)) {
          const others = allRes.value.data.filter((p) => String(p.slug) !== String(slug)).slice(0, 3);
          setRelatedPosts(others);
        }
      } catch (err) {
        console.error("Failed to load post:", err);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    fetchPostDetails();
    return () => {
      cancelled = true;
    };
  }, [slug]);

  // Update dynamic SEO for post
  useEffect(() => {
    if (post) {
      updatePageSEO({
        title: post.meta_title || post.title || "Printmont Blog Article",
        description: post.meta_description || post.excerpt || stripHtml(post.content).slice(0, 160),
        keywords: post.meta_keywords || `${post.category_name || 'gifts'}, printmont blog, corporate gifting`,
        image: post.featured_image ? resolveImageUrl(post.featured_image) : "/PrintLogo.png",
        url: window.location.href,
        type: "article",
      });
    } else {
      updatePageSEO({
        title: "Printmont Blog - Article Details",
        description: "Read curated corporate and personalized gifting articles and insights on Printmont.",
      });
    }
  }, [post]);

  const handleCopyLink = () => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(window.location.href);
      toast.success("Article link copied to clipboard!");
    }
  };

  // Fallback default article content if API returns minimal description
  const articleContent = post?.content || `
    <p>Corporate and personalized gifting is one of the most effective ways to build lasting relationships with employees, clients, and loved ones. A well-curated personalized gift not only expresses gratitude but also elevates brand presence and strengthens emotional connections.</p>
    
    <h3>1. Why Personalization Matters in Modern Gifting</h3>
    <p>Standard generic items often get forgotten. When a product is thoughtfully personalized with a recipient's name, custom company monogram, or a heartfelt message, it transforms an ordinary object into a memorable keepsake.</p>
    
    <h3>2. Quality Meets Aesthetics</h3>
    <p>From premium matte-finish executive notebooks and engraved metal pens to customized vacuum-insulated drinkware and high-grade canvas bags, material quality defines the entire gifting experience. Investing in durable, high-utility products ensures your gift stays in daily use for years to come.</p>
    
    <h3>3. Choosing the Right Gift for Every Milestone</h3>
    <p>Whether it's onboarding a new employee, celebrating work anniversaries, festive Diwali gifting, or sending bespoke client hampers, curating the right set of items tailored to the milestone makes all the difference.</p>
  `;

  return (
    <div className="blog-master-container pb-5">
      <Container className="py-4 py-lg-5">
        {loading ? (
          <div className="text-center py-5 bg-white rounded-3 border p-5">
            <Spinner animation="border" variant="primary" />
            <p className="text-muted mt-2 mb-0">Loading article...</p>
          </div>
        ) : (
          <Row className="g-4 g-lg-5">
            {/* Left Column (8 cols): Article Content */}
            <Col lg={8}>
              <article className="blog-post-wrapper bg-white p-3 p-md-4 p-lg-5 rounded-3 border shadow-xs">
                
                {/* Back Link */}
                <Link to="/blog" className="text-primary small fw-semibold text-decoration-none d-inline-flex align-items-center gap-1.5 mb-3 hover-primary">
                  <FaArrowLeft size={11} /> Back to all articles
                </Link>

                {/* Category Badge */}
                <div className="mb-2">
                  <span className="badge badge-theme-subtle px-3 py-1.5 rounded-pill text-uppercase">
                    {post?.category_name || "Gifting Inspiration"}
                  </span>
                </div>

                {/* Post Title */}
                <h1 className="blog-post-title">
                  {post?.title || "10 Creative Classroom Activities for Children's Day"}
                </h1>

                {/* Metadata Row */}
                <div className="blog-post-meta border-bottom pb-3 mb-4">
                  <span className="d-inline-flex align-items-center gap-1">
                    <FaRegCalendar size={13} className="text-primary" /> {formatDate(post?.published_at || post?.created_at)}
                  </span>
                  <span>•</span>
                  <span className="d-inline-flex align-items-center gap-1">
                    <FaRegClock size={13} className="text-primary" /> {readingTime(post)}
                  </span>
                  {post?.author_name && (
                    <>
                      <span>•</span>
                      <span className="d-inline-flex align-items-center gap-1">
                        <FaRegUser size={12} className="text-primary" /> {post.author_name}
                      </span>
                    </>
                  )}
                </div>

                {/* Featured Image */}
                <div className="mb-4 overflow-hidden rounded-3 shadow-xs" style={{ maxHeight: "420px", backgroundColor: "#f1f5f9" }}>
                  <img
                    src={post?.featured_image ? resolveImageUrl(post.featured_image) : "/banners/banner-30.jpg"}
                    alt={post?.title || "Article Banner"}
                    className="w-100 h-100 object-fit-cover rounded-3"
                    style={{ maxHeight: "420px" }}
                    onError={(e) => {
                      e.currentTarget.src = "/banners/banner-30.jpg";
                    }}
                  />
                </div>

                {/* Article Body */}
                <div
                  className="blog-post-body"
                  dangerouslySetInnerHTML={{ __html: articleContent }}
                />

                {/* Share Strip */}
                <div className="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-4 mt-5 border-top">
                  <div className="d-flex align-items-center gap-2">
                    <FaShareNodes className="text-primary" size={16} />
                    <span className="fw-bold text-dark small">Share this article:</span>
                  </div>
                  <div className="d-flex gap-2">
                    <a
                      href={`https://api.whatsapp.com/send?text=${encodeURIComponent(`${post?.title || 'Printmont Blog'} - ${window.location.href}`)}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="blog-share-btn text-success"
                      title="Share on WhatsApp"
                    >
                      <FaWhatsapp size={15} />
                    </a>
                    <a
                      href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="blog-share-btn text-primary"
                      title="Share on Facebook"
                    >
                      <FaFacebookF size={13} />
                    </a>
                    <a
                      href={`https://twitter.com/intent/tweet?url=${encodeURIComponent(window.location.href)}&text=${encodeURIComponent(post?.title || '')}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="blog-share-btn text-info"
                      title="Share on Twitter"
                    >
                      <FaTwitter size={13} />
                    </a>
                    <a
                      href={`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(window.location.href)}`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="blog-share-btn text-primary"
                      title="Share on LinkedIn"
                    >
                      <FaLinkedinIn size={13} />
                    </a>
                    <button
                      type="button"
                      onClick={handleCopyLink}
                      className="blog-share-btn"
                      title="Copy Link"
                    >
                      <FaLink size={13} />
                    </button>
                  </div>
                </div>

              </article>
            </Col>

            {/* Right Column (4 cols): Sticky Sidebar */}
            <Col lg={4}>
              <div className="sticky-top" style={{ top: "135px", zIndex: 10 }}>
                
                {/* Promo Card */}
                <div className="blog-promo-card">
                  <span className="badge bg-white text-dark fw-bold text-uppercase mb-2" style={{ fontSize: "0.7rem" }}>
                    Corporate Discount
                  </span>
                  <h5 className="fw-bold mb-1.5 text-white fs-6">Custom Printing &amp; Gifting</h5>
                  <p className="small mb-3 text-white-50" style={{ fontSize: "0.82rem" }}>
                    Order corporate merch &amp; personalized employee kits with zero setup fee.
                  </p>
                  <Link to="/bulk-orders" className="btn btn-light text-primary btn-sm fw-bold w-100 rounded-pill py-2 shadow-xs">
                    Get Custom Quote
                  </Link>
                </div>

                {/* Recent Posts Widget */}
                {recentPosts.length > 0 && (
                  <div className="blog-sidebar-widget">
                    <div className="blog-sidebar-title">
                      <FaRegClock className="text-primary" size={15} />
                      <span>Recent Articles</span>
                    </div>
                    <div className="d-flex flex-column gap-3">
                      {recentPosts.map((p) => (
                        <div key={p.id} className="d-flex align-items-center gap-3">
                          <div className="flex-shrink-0 rounded-2 overflow-hidden" style={{ width: "70px", height: "55px", backgroundColor: "#f1f5f9" }}>
                            <Link to={`/blog/${p.slug || p.id}`}>
                              <img
                                src={p.featured_image ? resolveImageUrl(p.featured_image) : "/default-img.jpg"}
                                alt={p.title}
                                className="w-100 h-100 object-fit-cover zoom-hover"
                              />
                            </Link>
                          </div>
                          <div className="flex-grow-1 min-w-0">
                            <h6 className="small fw-bold mb-1 line-clamp-2" style={{ lineHeight: "1.35" }}>
                              <Link to={`/blog/${p.slug || p.id}`} className="hover-primary">
                                {p.title}
                              </Link>
                            </h6>
                            <span className="text-muted" style={{ fontSize: "0.72rem" }}>
                              {formatDate(p.published_at || p.created_at)}
                            </span>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Explore Categories Link */}
                <div className="blog-sidebar-widget text-center">
                  <FaFolderOpen className="text-primary mb-2" size={26} />
                  <h6 className="fw-bold text-dark mb-1">Explore More Topics</h6>
                  <p className="small text-muted mb-3" style={{ fontSize: "0.8rem" }}>
                    Find inspiration across employee gifting, promotional gear &amp; DIY craft ideas.
                  </p>
                  <Link to="/blog" className="btn btn-outline-primary btn-sm rounded-pill fw-bold px-4">
                    Browse All Blogs
                  </Link>
                </div>

              </div>
            </Col>
          </Row>
        )}

        {/* Related Posts Section */}
        {relatedPosts.length > 0 && (
          <section className="mt-5 pt-4 border-top">
            <h4 className="fw-bold text-dark fs-5 mb-3 d-flex align-items-center gap-2">
              <FaTag className="text-primary" /> You Might Also Like
            </h4>
            <Row className="g-3.5">
              {relatedPosts.map((rel) => ({
                id: rel.id,
                slug: rel.slug,
                category: rel.category_name || "Inspiration",
                title: rel.title,
                imageSrc: rel.featured_image ? resolveImageUrl(rel.featured_image) : "/default-img.jpg",
                author: rel.author_name || "Printmont Team",
                date: formatDate(rel.published_at || rel.created_at),
                tags: rel.category_name,
                summary: rel.excerpt || stripHtml(rel.content).slice(0, 120),
              })).map((cardData) => (
                <Col md={4} key={cardData.id}>
                  <BlogCard cardData={cardData} />
                </Col>
              ))}
            </Row>
          </section>
        )}
      </Container>
    </div>
  );
};

export default BlogPostPage;
