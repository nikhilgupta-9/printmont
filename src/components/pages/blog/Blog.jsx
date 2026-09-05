import React, { useState, useEffect, useMemo } from "react";
import { Row, Col, Container, Badge, Button, Form, InputGroup, Spinner } from "react-bootstrap";
import BlogCard from "./BlogCard";
import Categories from "../category-list/Categories";
import { Link } from "react-router-dom";
import {
  FaFire, FaRegClock, FaNewspaper, FaArrowRight, FaCheckCircle, FaTag,
  FaMobileAlt, FaBuilding, FaSearch, FaEye, FaFolderOpen, FaRegFileAlt,
} from "react-icons/fa";
import { API_ENDPOINTS, resolveImageUrl } from "../../../config/apiEndpoints";
import "./blog.css";

/** Site theme blue, as used across the header, carousels and inner pages. */
const THEME = "rgb(11, 83, 161)";

/** How many articles to show before "Load more". */
const PAGE_SIZE = 6;

const formatDate = (value) => {
  if (!value) return "";
  const d = new Date(String(value).replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return "";
  return d.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" });
};

const formatDayMonth = (value) => {
  if (!value) return "";
  const d = new Date(String(value).replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return "";
  return d.toLocaleDateString("en-IN", { day: "2-digit", month: "short" });
};

const stripHtml = (html) => String(html || "").replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();

/** Rough reading time from the post body, at ~200 words per minute. */
const readingTime = (post) => {
  const words = stripHtml(post.content || post.excerpt || "").split(" ").filter(Boolean).length;
  return `${Math.max(1, Math.round(words / 200))} min read`;
};

/** Map an API post onto the shape BlogCard expects. */
const toCardData = (post) => ({
  id: post.id,
  slug: post.slug,
  category: post.category_name || "Blog",
  title: post.title,
  imageSrc: post.featured_image ? resolveImageUrl(post.featured_image) : "/default-img.jpg",
  author: post.author_name || "",
  date: formatDate(post.published_at || post.created_at),
  tags: post.category_name || "Article",
  summary: post.excerpt || stripHtml(post.content).slice(0, 160),
});

/**
 * Panel wrapper. Every block sits in the same white card the rest of the site
 * uses (blog post page, category page, product sections).
 */
const Panel = ({ className = "", children, ...rest }) => (
  <div className={`bg-white rounded-3 shadow-sm border ${className}`} {...rest}>
    {children}
  </div>
);

/** Section heading, matching the product carousels on the homepage. */
const SectionHeading = ({ title, aside }) => (
  <div className="d-flex justify-content-between align-items-center mb-2">
    <p className="fw-semibold fs-5 fs-lg-4 my-2 ms-0 text-dark">{title}</p>
    {aside && <span className="text-muted small">{aside}</span>}
  </div>
);

const CardSkeleton = () => (
  <div className="border rounded p-3 h-100">
    <div className="shimmer-bg rounded mb-3" style={{ aspectRatio: "16/9" }} />
    <div className="shimmer-bg rounded mb-2" style={{ height: 14, width: "85%" }} />
    <div className="shimmer-bg rounded mb-2" style={{ height: 14, width: "60%" }} />
    <div className="shimmer-bg rounded" style={{ height: 30, width: "45%" }} />
  </div>
);

// --- SIDEBAR ---
const Sidebar = ({ trending, categories, postCountFor, onPickCategory, activeCategory }) => {
  const [subscribed, setSubscribed] = useState(false);

  return (
    <div className="h-100">
      <div className="sticky-top" style={{ top: "135px", zIndex: 10 }}>

        {/* PROMO COUPON */}
        <Panel className="p-3 mb-3 text-white overflow-hidden position-relative" style={{ background: THEME }}>
          <div className="d-flex align-items-center gap-2 mb-2">
            <FaTag size={16} />
            <span className="badge bg-white text-dark fw-bold text-uppercase">Exclusive Offer</span>
          </div>
          <h5 className="fw-bold mb-1 text-white fs-6">Save 15% on First Bulk Order</h5>
          <p className="small mb-3 text-white-50" style={{ fontSize: "0.8rem" }}>
            Use code <strong className="text-white">PRINT2026</strong> at checkout.
          </p>
          <Link to="/bulk-orders" className="btn btn-light text-dark btn-sm fw-bold">Claim Offer</Link>
        </Panel>

        {/* BROWSE BY CATEGORY */}
        {categories.length > 0 && (
          <Panel className="p-3 mb-3">
            <div className="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
              <FaFolderOpen size={15} style={{ color: THEME }} />
              <h6 className="fw-bold text-dark m-0">Browse by Category</h6>
            </div>
            <div className="d-flex flex-column">
              {categories.map((cat) => (
                <button
                  key={cat.id}
                  onClick={() => onPickCategory(String(cat.id))}
                  className={`btn btn-sm text-start d-flex justify-content-between align-items-center px-2 py-2 border-0 ${
                    activeCategory === String(cat.id) ? "fw-bold" : "text-secondary"
                  }`}
                  style={activeCategory === String(cat.id) ? { color: THEME } : undefined}
                >
                  <span className="text-truncate">{cat.name}</span>
                  <Badge bg="light" text="dark" className="ms-2 flex-shrink-0">
                    {postCountFor(cat.id)}
                  </Badge>
                </button>
              ))}
            </div>
          </Panel>
        )}

        {/* TRENDING ARTICLES */}
        <Panel className="p-3 mb-3">
          <div className="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
            <FaFire className="text-danger" size={16} />
            <h6 className="fw-bold text-dark m-0">Trending Articles</h6>
          </div>
          {trending.length === 0 ? (
            <p className="text-muted small mb-0">Nothing trending yet.</p>
          ) : (
            <div className="d-flex flex-column gap-3">
              {trending.map((post) => (
                <Link
                  key={post.id}
                  to={`/blog/${post.slug}`}
                  className="text-decoration-none text-dark d-flex align-items-center gap-3"
                >
                  <div className="flex-shrink-0 rounded overflow-hidden border" style={{ width: "60px", height: "55px" }}>
                    <img
                      src={post.featured_image ? resolveImageUrl(post.featured_image) : "/default-img.jpg"}
                      alt={post.title}
                      className="w-100 h-100"
                      style={{ objectFit: "cover" }}
                    />
                  </div>
                  <div className="flex-grow-1 min-w-0">
                    <h6 className="small fw-bold text-dark mb-1 line-clamp-2" style={{ lineHeight: "1.4" }}>
                      {post.title}
                    </h6>
                    <span className="text-muted" style={{ fontSize: "0.72rem" }}>
                      <FaRegClock size={11} className="me-1" />
                      {formatDayMonth(post.published_at || post.created_at)}
                    </span>
                  </div>
                </Link>
              ))}
            </div>
          )}
        </Panel>

        {/* APP DOWNLOAD */}
        <Panel className="p-3 mb-3 text-center">
          <FaMobileAlt size={30} className="mb-2" style={{ color: THEME }} />
          <h6 className="fw-bold text-dark mb-2">Download PrintMont App</h6>
          <p className="small text-secondary mb-3" style={{ fontSize: "0.82rem", lineHeight: "1.5" }}>
            Get ₹500 Welcome PrintCoins &amp; track your orders live on your phone.
          </p>
          <Link to="/app-download" className="btn btn-sm fw-bold w-100 text-white" style={{ backgroundColor: THEME }}>
            Download App
          </Link>
        </Panel>

        {/* NEWSLETTER */}
        <Panel className="p-3 mb-3 text-center">
          <FaNewspaper size={26} className="mb-2 text-secondary" />
          <h6 className="fw-bold text-dark mb-2">PrintMont Insights</h6>
          <p className="small text-secondary mb-3" style={{ fontSize: "0.82rem", lineHeight: "1.5" }}>
            Subscribe for free weekly printing tips &amp; merchandise guides.
          </p>
          {subscribed ? (
            <div className="p-2 rounded bg-success text-white small fw-bold d-flex align-items-center justify-content-center gap-1">
              <FaCheckCircle /> Subscribed!
            </div>
          ) : (
            <Form onSubmit={(e) => { e.preventDefault(); setSubscribed(true); }}>
              <Form.Control type="email" placeholder="Your email address" required className="form-control-sm mb-2 text-center" />
              <Button type="submit" size="sm" className="w-100 fw-bold border-0 text-white" style={{ backgroundColor: THEME }}>
                Subscribe Free
              </Button>
            </Form>
          )}
        </Panel>
      </div>
    </div>
  );
};

// --- MAIN BLOG PAGE ---
const Blog = () => {
  const [posts, setPosts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [popular, setPopular] = useState([]);
  const [loading, setLoading] = useState(true);
  const [failed, setFailed] = useState(false);

  const [selectedCategory, setSelectedCategory] = useState("all");
  const [searchTerm, setSearchTerm] = useState("");
  const [visibleCount, setVisibleCount] = useState(PAGE_SIZE);

  useEffect(() => {
    let cancelled = false;

    const load = async () => {
      try {
        // One failing endpoint should not blank the whole page, so each is
        // settled independently and missing data degrades to an empty section.
        const [postsRes, catsRes, popularRes] = await Promise.allSettled([
          fetch(API_ENDPOINTS.BLOG_POSTS).then((r) => r.json()),
          fetch(API_ENDPOINTS.BLOG_CATEGORIES).then((r) => r.json()),
          fetch(API_ENDPOINTS.BLOG_POPULAR).then((r) => r.json()),
        ]);
        if (cancelled) return;

        const pick = (res) =>
          res.status === "fulfilled" && res.value?.success && Array.isArray(res.value.data)
            ? res.value.data
            : [];

        const loadedPosts = pick(postsRes);
        setPosts(loadedPosts);
        setCategories(pick(catsRes));
        setPopular(pick(popularRes));

        if (postsRes.status !== "fulfilled" || !postsRes.value?.success) setFailed(true);
      } catch (err) {
        console.error("Failed to load blog:", err);
        if (!cancelled) setFailed(true);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    load();
    return () => { cancelled = true; };
  }, []);

  const postCountFor = (categoryId) =>
    posts.filter((p) => String(p.category_id) === String(categoryId)).length;

  const filteredPosts = useMemo(() => {
    const term = searchTerm.trim().toLowerCase();

    return posts.filter((post) => {
      const inCategory =
        selectedCategory === "all" || String(post.category_id) === selectedCategory;
      if (!inCategory) return false;
      if (!term) return true;

      const haystack = [
        post.title,
        post.excerpt,
        post.category_name,
        stripHtml(post.content),
      ].join(" ").toLowerCase();

      return haystack.includes(term);
    });
  }, [posts, selectedCategory, searchTerm]);

  // Reset paging whenever the result set changes underneath it.
  useEffect(() => { setVisibleCount(PAGE_SIZE); }, [selectedCategory, searchTerm]);

  // Newest post drives the spotlight; the rest fill the grid.
  const featured = useMemo(() => {
    if (!posts.length) return null;
    return [...posts].sort((a, b) =>
      String(b.published_at || b.created_at).localeCompare(String(a.published_at || a.created_at))
    )[0];
  }, [posts]);

  const trending = useMemo(() => (popular.length ? popular : posts).slice(0, 4), [popular, posts]);
  const mostRead = useMemo(
    () => [...popular].sort((a, b) => Number(b.view_count || b.views || 0) - Number(a.view_count || a.views || 0)).slice(0, 3),
    [popular]
  );

  const visiblePosts = filteredPosts.slice(0, visibleCount);
  const filtersActive = selectedCategory !== "all" || searchTerm.trim() !== "";

  return (
    <div className="bg-light py-2 py-md-4" style={{ minHeight: "85vh", overflowX: "hidden" }}>

      {/* Same category strip the homepage and blog post page use. */}
      <div className="d-block d-lg-none mb-3">
        <Categories bg={THEME} color="white" showImages={false} space="10px 0" />
      </div>

      <Container className="blog-page">

        {/* PAGE HEADER + SEARCH + BLOG CATEGORIES */}
        <Panel className="p-3 p-md-4 mb-3">
          <div className="text-center">
            <Badge bg="primary-subtle" className="text-primary fw-bold px-3 py-2 mb-2 text-uppercase fs-7">
              PrintMont Knowledge Hub
            </Badge>
            <h1 className="fw-bold text-dark fs-3 fs-md-2 mb-2">PrintMont Blog &amp; Guides</h1>
            <p className="text-secondary mx-auto mb-3 small" style={{ maxWidth: "680px", lineHeight: "1.6" }}>
              Expert insights on custom apparel printing, corporate merchandise strategies,
              design trends, and personalized gifting ideas.
            </p>

            {/* SEARCH ARTICLES */}
            <div className="mx-auto" style={{ maxWidth: "480px" }}>
              <InputGroup>
                <InputGroup.Text className="bg-white border-end-0 text-muted">
                  <FaSearch size={13} />
                </InputGroup.Text>
                <Form.Control
                  type="text"
                  placeholder="Search articles by title, topic or keyword..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="border-start-0 shadow-none small"
                />
                {searchTerm && (
                  <Button variant="outline-secondary" onClick={() => setSearchTerm("")}>Clear</Button>
                )}
              </InputGroup>
            </div>
          </div>

          {/* BLOG CATEGORIES */}
          <div className="d-flex flex-wrap justify-content-center gap-2 mt-3 pt-3 border-top">
            <Button
              size="sm"
              variant={selectedCategory === "all" ? "primary" : "outline-secondary"}
              className={`px-3 fw-semibold ${selectedCategory === "all" ? "" : "bg-white"}`}
              style={selectedCategory === "all" ? { backgroundColor: THEME, borderColor: THEME } : undefined}
              onClick={() => setSelectedCategory("all")}
            >
              All <span className="opacity-75">({posts.length})</span>
            </Button>

            {categories.map((cat) => {
              const on = selectedCategory === String(cat.id);
              return (
                <Button
                  key={cat.id}
                  size="sm"
                  variant={on ? "primary" : "outline-secondary"}
                  className={`px-3 fw-semibold ${on ? "" : "bg-white"}`}
                  style={on ? { backgroundColor: THEME, borderColor: THEME } : undefined}
                  onClick={() => setSelectedCategory(String(cat.id))}
                >
                  {cat.name} <span className="opacity-75">({postCountFor(cat.id)})</span>
                </Button>
              );
            })}
          </div>
        </Panel>

        {/* FEATURED SPOTLIGHT ARTICLE */}
        {featured && (
          <Panel className="overflow-hidden mb-3">
            <Row className="g-0 align-items-center">
              <Col lg={7} md={12} className="p-0">
                <div style={{ maxHeight: "340px", overflow: "hidden" }}>
                  <img
                    src={featured.featured_image ? resolveImageUrl(featured.featured_image) : "/b2b_hero.png"}
                    alt={featured.title}
                    className="w-100 h-100 img-fluid zoom-hover"
                    style={{ objectFit: "cover", minHeight: "260px", maxHeight: "340px", width: "100%" }}
                  />
                </div>
              </Col>
              <Col lg={5} md={12} className="p-3 p-md-4">
                <Badge bg="primary-subtle" className="text-primary fw-bold px-3 py-1 mb-2 text-uppercase fs-7">
                  Featured Spotlight
                </Badge>
                <h3 className="fw-bold text-dark mb-3 fs-5 line-clamp-3" style={{ lineHeight: "1.35" }}>
                  {featured.title}
                </h3>
                <p className="text-secondary small mb-4 line-clamp-3" style={{ lineHeight: "1.6" }}>
                  {featured.excerpt || stripHtml(featured.content).slice(0, 200)}
                </p>
                <div className="d-flex align-items-center justify-content-between pt-3 border-top">
                  <span className="text-muted small fw-semibold">
                    {readingTime(featured)} · {formatDate(featured.published_at || featured.created_at)}
                  </span>
                  <Link
                    to={`/blog/${featured.slug}`}
                    className="btn btn-sm px-3 fw-bold text-white"
                    style={{ backgroundColor: THEME }}
                  >
                    Read Article <FaArrowRight className="ms-1" size={12} />
                  </Link>
                </div>
              </Col>
            </Row>
          </Panel>
        )}

        {/* MAIN CONTENT GRID */}
        <Row className="g-3">
          <Col xs={12} lg={9}>

            {/* BLOGS — LATEST ARTICLES */}
            <Panel className="p-3 p-md-4 mb-3">
              <SectionHeading
                title="Latest Articles"
                aside={loading ? "Loading…" : `${filteredPosts.length} article${filteredPosts.length === 1 ? "" : "s"} found`}
              />

              {loading ? (
                <Row className="g-3">
                  {[0, 1, 2, 3].map((i) => (
                    <Col key={i} xs={12} sm={6}><CardSkeleton /></Col>
                  ))}
                </Row>
              ) : failed ? (
                <div className="text-center py-4">
                  <h6 className="fw-bold text-dark mb-1">Articles are unavailable right now</h6>
                  <p className="text-secondary small mb-0">Please refresh in a moment.</p>
                </div>
              ) : filteredPosts.length === 0 ? (
                <div className="text-center py-4">
                  <FaRegFileAlt size={34} className="text-secondary mb-3" />
                  <h6 className="fw-bold text-dark mb-1">
                    {filtersActive ? "No articles match your filters" : "No articles published yet"}
                  </h6>
                  <p className="text-secondary small mb-3">
                    {filtersActive
                      ? "Try a different category, or clear your search."
                      : "New guides and insights will appear here soon."}
                  </p>
                  {filtersActive && (
                    <Button
                      size="sm"
                      variant="outline-secondary"
                      onClick={() => { setSelectedCategory("all"); setSearchTerm(""); }}
                    >
                      Clear filters
                    </Button>
                  )}
                </div>
              ) : (
                <>
                  <Row className="g-3">
                    {visiblePosts.map((post) => (
                      <Col key={post.id} xs={12} sm={6}>
                        <BlogCard cardData={toCardData(post)} />
                      </Col>
                    ))}
                  </Row>

                  {/* LOAD MORE */}
                  {visibleCount < filteredPosts.length && (
                    <div className="text-center mt-3">
                      <Button
                        variant="outline-primary"
                        size="sm"
                        className="px-4 fw-semibold"
                        onClick={() => setVisibleCount((n) => n + PAGE_SIZE)}
                      >
                        Load more articles ({filteredPosts.length - visibleCount} left)
                      </Button>
                    </div>
                  )}
                </>
              )}
            </Panel>

            {/* IN-FEED BANNER 1: CUSTOM APPAREL */}
            <Panel className="p-3 p-md-4 mb-3 text-white" style={{ background: THEME }}>
              <Row className="align-items-center g-3">
                <Col md={8}>
                  <Badge bg="light" className="text-dark fw-bold px-3 py-1 mb-2 text-uppercase">
                    Custom Apparel Supply
                  </Badge>
                  <h4 className="fw-bold text-white mb-2 fs-5">Build Custom T-Shirts &amp; Hoodies for Your Brand</h4>
                  <p className="mb-0 small text-white-50" style={{ lineHeight: "1.5" }}>
                    High-density DTG printing &amp; embroidery with 0% defect guarantee.
                    Minimum order starting at 25 pcs.
                  </p>
                </Col>
                <Col md={4} className="text-md-end">
                  <Link to="/business-solutions" className="btn btn-light text-dark fw-bold px-4 py-2">
                    View Apparel Blanks
                  </Link>
                </Col>
              </Row>
            </Panel>

            {/* MOST READ — driven by view counts */}
            {mostRead.length > 0 && (
              <Panel className="p-3 p-md-4 mb-3">
                <SectionHeading title="Most Read" aside="By reader views" />
                <div className="d-flex flex-column gap-3">
                  {mostRead.map((post, idx) => (
                    <Link
                      key={post.id}
                      to={`/blog/${post.slug}`}
                      className="d-flex align-items-center gap-3 text-decoration-none border rounded p-2 hover-shadow"
                    >
                      <span
                        className="fw-bold flex-shrink-0 d-flex align-items-center justify-content-center rounded"
                        style={{ width: 34, height: 34, backgroundColor: "#eef3fa", color: THEME }}
                      >
                        {idx + 1}
                      </span>
                      <div className="flex-shrink-0 rounded overflow-hidden border d-none d-sm-block" style={{ width: 70, height: 52 }}>
                        <img
                          src={post.featured_image ? resolveImageUrl(post.featured_image) : "/default-img.jpg"}
                          alt={post.title}
                          className="w-100 h-100"
                          style={{ objectFit: "cover" }}
                        />
                      </div>
                      <div className="flex-grow-1 min-w-0">
                        <h6 className="fw-bold text-dark mb-1 line-clamp-2 small">{post.title}</h6>
                        <span className="text-muted d-flex align-items-center gap-2" style={{ fontSize: "0.72rem" }}>
                          <span><FaEye size={11} className="me-1" />{post.view_count || post.views || 0} views</span>
                          <span>·</span>
                          <span>{readingTime(post)}</span>
                        </span>
                      </div>
                    </Link>
                  ))}
                </div>
              </Panel>
            )}

            {/* IN-FEED BANNER 2: FRANCHISE PARTNER */}
            <Panel className="p-3 p-md-4 mb-3">
              <Row className="align-items-center g-3">
                <Col md={8}>
                  <div className="d-flex align-items-center gap-2 mb-2">
                    <FaBuilding style={{ color: THEME }} size={18} />
                    <Badge bg="primary-subtle" className="text-primary fw-bold px-2 py-1">
                      Franchise Opportunity
                    </Badge>
                  </div>
                  <h5 className="fw-bold text-dark mb-1 fs-6">Own a PrintMont Franchise Outlet in Your City</h5>
                  <p className="text-secondary small mb-0" style={{ lineHeight: "1.5" }}>
                    Tap into the ₹25,000 Cr gifting industry with high gross margins (45%-65%)
                    and full factory supply support.
                  </p>
                </Col>
                <Col md={4} className="text-md-end">
                  <Link to="/franchise" className="btn btn-sm px-4 py-2 fw-bold text-white" style={{ backgroundColor: THEME }}>
                    Apply For Franchise
                  </Link>
                </Col>
              </Row>
            </Panel>
          </Col>

          {/* RIGHT SIDEBAR */}
          <Col xs={12} lg={3}>
            <Sidebar
              trending={trending}
              categories={categories}
              postCountFor={postCountFor}
              activeCategory={selectedCategory}
              onPickCategory={setSelectedCategory}
            />
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default Blog;
