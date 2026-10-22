import React, { useState, useEffect, useMemo, useRef, useCallback } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { Spinner } from "react-bootstrap";
import {
  FaChevronLeft,
  FaChevronRight,
  FaUser,
  FaCalendarAlt,
  FaTag,
  FaArrowRight
} from "react-icons/fa";
import { API_ENDPOINTS, resolveImageUrl } from "../../../config/apiEndpoints";
import { updatePageSEO } from "../../../utils/seo";
import useHomeBanners from "../../home/hooks/useHomeBanners";
import "./blog.css";

// Helper to strip HTML tags for excerpt preview
const stripHtml = (html) =>
  String(html || "")
    .replace(/<[^>]*>/g, " ")
    .replace(/\s+/g, " ")
    .trim();

// Format date nicely (e.g. October 1, 2024)
const formatDate = (value) => {
  if (!value) return "October 1, 2024";
  const d = new Date(String(value).replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return String(value);
  return d.toLocaleDateString("en-US", {
    month: "long",
    day: "numeric",
    year: "numeric"
  });
};

// Fallback curated banners for top slider
const DEFAULT_TOP_SLIDERS = [
  {
    id: 1,
    title: "Corporate Gift Hampers",
    image: "/banners/blog-banner-1.png",
    link: "/category/corporate-gifts"
  },
  {
    id: 2,
    title: "Personalised Print Kits",
    image: "/banners/blog-banner-2.png",
    link: "/category/personalised-gifts"
  },
  {
    id: 3,
    title: "Promotional Drinkware & Pens",
    image: "/banners/banner-901.png",
    link: "/category/drinkware"
  },
  {
    id: 4,
    title: "Doctor & Healthcare Gifts",
    image: "/banners/banner-902.png",
    link: "/category/doctor-gifts"
  },
  {
    id: 5,
    title: "Event Merch & Trophies",
    image: "/banners/banner-904.png",
    link: "/category/trophies"
  },
  {
    id: 6,
    title: "Customized Festive Packaging",
    image: "/banners/banner-905.png",
    link: "/category/gift-sets"
  }
];

// Reusable Wireframe Blog Card (Matches exact PDF specification)
const WireframeBlogCard = ({ post }) => {
  if (!post) return null;

  const targetUrl = post.slug ? `/blog/${post.slug}` : `/blog/${post.id || 1}`;
  const imgUrl = post.featured_image
    ? resolveImageUrl(post.featured_image)
    : "/banners/banner-30.jpg";
  const authorName = post.author_name || "John Doe";
  const catName = post.category_name || "Design Trends";
  const postDate = formatDate(post.published_at || post.created_at);
  const excerptText =
    post.excerpt ||
    stripHtml(post.content).slice(0, 130) ||
    "A brief introduction or summary of the blog post that gives readers an idea of what it is about.";

  return (
    <article className="pm-wf-blog-card">
      <Link to={targetUrl} className="pm-wf-card-img-box">
        <img
          src={imgUrl}
          alt={post.title}
          className="pm-wf-card-img"
          onError={(e) => {
            e.currentTarget.src = "/banners/banner-30.jpg";
          }}
        />
        {catName && <span className="pm-wf-card-tag">{catName}</span>}
      </Link>

      <div className="pm-wf-card-body">
        <h3 className="pm-wf-card-title">
          <Link to={targetUrl}>{post.title}</Link>
        </h3>

        <div className="pm-wf-card-meta">
          <span className="pm-wf-meta-item">
            <FaUser className="pm-wf-meta-icon" /> {authorName}
          </span>
          <span className="pm-wf-meta-item">
            <FaCalendarAlt className="pm-wf-meta-icon" /> {postDate}
          </span>
          <span className="pm-wf-meta-item">
            <FaTag className="pm-wf-meta-icon" /> {catName}
          </span>
        </div>

        <p className="pm-wf-card-excerpt">{excerptText}</p>

        <Link to={targetUrl} className="pm-wf-card-readmore">
          Read More <FaArrowRight size={10} />
        </Link>
      </div>
    </article>
  );
};

const Blog = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const selectedCategoryParam = searchParams.get("category") || "all";

  const [posts, setPosts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [recentPosts, setRecentPosts] = useState([]);
  const [popularPosts, setPopularPosts] = useState([]);
  const [loading, setLoading] = useState(true);

  // Sub-Slider Index States
  const [latestIdx, setLatestIdx] = useState(0);
  const [popularIdx, setPopularIdx] = useState(0);
  const [printingIdx, setPrintingIdx] = useState(0);

  // Dynamic Banners from API (scoping to page=blog via BLOG_BANNER endpoint)
  const { banners: blogHeroBanners } = useHomeBanners(API_ENDPOINTS.BLOG_BANNER, "blog_hero");
  const { banners: blogTopBanners } = useHomeBanners(API_ENDPOINTS.BLOG_BANNER, "blog_top_strip");
  const { banners: blogMidBanners } = useHomeBanners(API_ENDPOINTS.BLOG_BANNER, "blog_mid_strip");
  const { banners: blogAdBanners } = useHomeBanners(API_ENDPOINTS.BLOG_BANNER, "blog_ad_banner");

  useEffect(() => {
    window.scrollTo(0, 0);
    updatePageSEO({
      title: "Printmont Blog | Corporate Gifting, Promotional Items & Design Ideas",
      description: "Discover expert articles on customized printing, corporate employee kits, personalized occasion gifts, and branding solutions with Printmont.",
      keywords: "printmont blog, corporate gifts, promotional gifts, business gift ideas, printing blogs",
      type: "blog"
    });

    let cancelled = false;

    const loadBlogData = async () => {
      try {
        setLoading(true);
        const [postsRes, catsRes, recentRes, popRes] = await Promise.allSettled([
          fetch(API_ENDPOINTS.BLOG_POSTS).then((r) => r.json()),
          fetch(API_ENDPOINTS.BLOG_CATEGORIES).then((r) => r.json()),
          fetch(API_ENDPOINTS.BLOG_RECENT(6)).then((r) => r.json()),
          fetch(API_ENDPOINTS.BLOG_POPULAR(6)).then((r) => r.json())
        ]);

        if (cancelled) return;

        if (postsRes.status === "fulfilled" && postsRes.value?.success) {
          const list = Array.isArray(postsRes.value.data) ? postsRes.value.data : [];
          setPosts(list.filter((p) => p.status !== "inactive"));
        }

        if (catsRes.status === "fulfilled" && catsRes.value?.success) {
          setCategories(Array.isArray(catsRes.value.data) ? catsRes.value.data : []);
        }

        if (recentRes.status === "fulfilled" && recentRes.value?.success) {
          setRecentPosts(Array.isArray(recentRes.value.data) ? recentRes.value.data : []);
        }

        if (popRes.status === "fulfilled" && popRes.value?.success) {
          setPopularPosts(Array.isArray(popRes.value.data) ? popRes.value.data : []);
        }
      } catch (err) {
        console.error("Error loading blog data:", err);
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    loadBlogData();
    return () => {
      cancelled = true;
    };
  }, []);

  // Top Sliders list (Powered dynamically by Admin banners: blog_hero & blog_top_strip)
  const topSliders = useMemo(() => {
    const apiItems = [];

    // 1. Pull banners uploaded to blog_hero ("Main Hero Slider & Banners")
    if (Array.isArray(blogHeroBanners) && blogHeroBanners.length > 0) {
      blogHeroBanners.forEach((b, i) => {
        apiItems.push({
          id: b.id ? `hero-${b.id}-${i}` : `hero-${i}`,
          title: b.title || "Slider & banner",
          image: b.image || b.large || b.image_url_desktop || b.image_url || "/banners/blog-banner-1.png",
          link: b.target_url || b.target || "/blog"
        });
      });
    }

    // 2. Pull banners uploaded to blog_top_strip ("Top Small Banner Strip / Slider")
    if (Array.isArray(blogTopBanners) && blogTopBanners.length > 0) {
      blogTopBanners.forEach((b, i) => {
        apiItems.push({
          id: b.id ? `top-${b.id}-${i}` : `top-${i}`,
          title: b.title || "Slider & banner",
          image: b.image || b.large || b.image_url_desktop || b.image_url || "/banners/blog-banner-2.png",
          link: b.target_url || b.target || "/blog"
        });
      });
    }

    // If 5 or more banners configured in Admin, use all Admin banners
    if (apiItems.length >= 5) {
      return apiItems;
    }

    // If Admin has 1 to 4 banners, show Admin banners first, then fill remainder from DEFAULT_TOP_SLIDERS
    if (apiItems.length > 0) {
      const remaining = 5 - apiItems.length;
      return [...apiItems, ...DEFAULT_TOP_SLIDERS.slice(0, remaining)];
    }

    // Fallback if no Admin banners configured yet
    return DEFAULT_TOP_SLIDERS;
  }, [blogHeroBanners, blogTopBanners]);

  // Sidebar banner item (Dynamic from Admin blog_mid_strip / blog_hero)
  const sidebarBanner = useMemo(() => {
    if (Array.isArray(blogMidBanners) && blogMidBanners.length > 0) {
      const b = blogMidBanners[0];
      return {
        title: b.title || "Slider & banner",
        image: b.image || b.large || b.image_url_desktop || "/banners/pdp_small_banner_1.jpg",
        link: b.target_url || b.target || "/category/corporate-gifts"
      };
    }
    if (Array.isArray(blogHeroBanners) && blogHeroBanners.length > 1) {
      const b = blogHeroBanners[1];
      return {
        title: b.title || "Slider & banner",
        image: b.image || b.large || b.image_url_desktop || "/banners/pdp_small_banner_1.jpg",
        link: b.target_url || b.target || "/category/corporate-gifts"
      };
    }
    return {
      title: "Slider & banner",
      image: "/banners/pdp_small_banner_1.jpg",
      link: "/category/corporate-gifts"
    };
  }, [blogMidBanners, blogHeroBanners]);

  // Fallback posts if API returns limited rows
  const activePosts = useMemo(() => {
    if (posts.length > 0) return posts;
    return [
      {
        id: 1,
        slug: "luxury-corporate-gifting-guide",
        title: "Title of Blog Post 1",
        category_name: "Design Trends",
        author_name: "John Doe",
        published_at: "2024-10-01",
        featured_image: "/banners/banner-30.jpg",
        excerpt: "A brief introduction or summary of the blog post that gives readers an idea of what it is about."
      },
      {
        id: 2,
        slug: "modern-business-hampers-trends",
        title: "Title of Blog Post 2",
        category_name: "Business Insights",
        author_name: "Jane Smith",
        published_at: "2024-10-05",
        featured_image: "/banners/banner11.jpg",
        excerpt: "A brief introduction or summary of the blog post that gives readers an idea of what it is about."
      }
    ];
  }, [posts]);

  // Find posts by category name or keyword
  const getPostForCategory = (keywords = []) => {
    const found = activePosts.find((p) => {
      const c = (p.category_name || "").toLowerCase();
      const t = (p.title || "").toLowerCase();
      return keywords.some((k) => c.includes(k) || t.includes(k));
    });
    return found || activePosts[0];
  };

  const corporatePost = useMemo(() => getPostForCategory(["corporate"]), [activePosts]);
  const businessPost = useMemo(() => getPostForCategory(["business"]), [activePosts]);
  const promoPost = useMemo(() => getPostForCategory(["promotional", "merch"]), [activePosts]);
  const doctorPost = useMemo(() => getPostForCategory(["doctor", "healthcare"]), [activePosts]);
  const noveltiesPost = useMemo(() => getPostForCategory(["relationship", "novelties", "activities"]), [activePosts]);
  const personalizedPost = useMemo(() => getPostForCategory(["personalized", "personalise"]), [activePosts]);

  // Carousels data slices
  const latestPostsSlice = useMemo(() => {
    const list = activePosts;
    const start = latestIdx % Math.max(1, list.length);
    return [list[start], list[(start + 1) % list.length]].filter(Boolean);
  }, [activePosts, latestIdx]);

  const popularPostsSlice = useMemo(() => {
    const list = popularPosts.length >= 2 ? popularPosts : activePosts;
    const start = popularIdx % Math.max(1, list.length);
    return [list[start], list[(start + 1) % list.length]].filter(Boolean);
  }, [popularPosts, activePosts, popularIdx]);

  const printingPostsSlice = useMemo(() => {
    const list = activePosts.filter(p => (p.title || "").toLowerCase().includes("print") || (p.category_name || "").toLowerCase().includes("print"));
    const source = list.length >= 2 ? list : activePosts;
    const start = printingIdx % Math.max(1, source.length);
    return [source[start], source[(start + 1) % source.length]].filter(Boolean);
  }, [activePosts, printingIdx]);

  // Smooth Infinite Hero Carousel Setup
  const baseTopSliders = useMemo(() => {
    if (!topSliders || topSliders.length === 0) return [];
    let list = [...topSliders];
    while (list.length < 5) {
      list = [...list, ...topSliders];
    }
    return list;
  }, [topSliders]);

  const carouselTrackItems = useMemo(() => {
    if (baseTopSliders.length === 0) return [];
    return [...baseTopSliders, ...baseTopSliders, ...baseTopSliders];
  }, [baseTopSliders]);

  const [slideIdx, setSlideIdx] = useState(0);
  const [isTransitioning, setIsTransitioning] = useState(true);
  const [isTopSliderHovered, setIsTopSliderHovered] = useState(false);
  const viewportRef = useRef(null);
  const [viewportWidth, setViewportWidth] = useState(() => {
    if (typeof window !== "undefined") {
      return Math.max(320, window.innerWidth - 28);
    }
    return 1200;
  });

  // Start in the middle set of cloned items for infinite scrolling
  useEffect(() => {
    if (baseTopSliders.length > 0) {
      setSlideIdx(baseTopSliders.length);
      setIsTransitioning(false);
    }
  }, [baseTopSliders.length]);

  // Re-enable transition smoothly after silent index wraps
  useEffect(() => {
    if (!isTransitioning) {
      const raf = requestAnimationFrame(() => {
        setIsTransitioning(true);
      });
      return () => cancelAnimationFrame(raf);
    }
  }, [isTransitioning]);

  // Track responsive viewport width dynamically
  useEffect(() => {
    if (!viewportRef.current) return;
    const updateWidth = () => {
      if (viewportRef.current) {
        setViewportWidth(viewportRef.current.clientWidth);
      }
    };
    updateWidth();
    window.addEventListener("resize", updateWidth);
    const ro = new ResizeObserver(updateWidth);
    ro.observe(viewportRef.current);
    return () => {
      window.removeEventListener("resize", updateWidth);
      ro.disconnect();
    };
  }, []);

  const visibleCount = useMemo(() => {
    if (viewportWidth < 576) return 1;
    if (viewportWidth < 768) return 2;
    if (viewportWidth < 992) return 3;
    if (viewportWidth < 1200) return 4;
    return 5;
  }, [viewportWidth]);

  const gapSize = useMemo(() => {
    if (viewportWidth < 576) return 8;
    if (viewportWidth < 768) return 8;
    if (viewportWidth < 1200) return 10;
    return 12;
  }, [viewportWidth]);

  const cardWidth = useMemo(() => {
    if (!viewportWidth || visibleCount <= 0) return 240;
    return (viewportWidth - gapSize * (visibleCount - 1)) / visibleCount;
  }, [viewportWidth, visibleCount, gapSize]);

  const slideNext = useCallback(() => {
    setIsTransitioning(true);
    setSlideIdx((prev) => prev + 1);
  }, []);

  const slidePrev = useCallback(() => {
    setIsTransitioning(true);
    setSlideIdx((prev) => (prev > 0 ? prev - 1 : 0));
  }, []);

  const handleTransitionEnd = useCallback(() => {
    const len = baseTopSliders.length;
    if (len === 0) return;
    if (slideIdx >= len * 2) {
      setIsTransitioning(false);
      setSlideIdx((prev) => prev - len);
    } else if (slideIdx < len) {
      setIsTransitioning(false);
      setSlideIdx((prev) => prev + len);
    }
  }, [baseTopSliders.length, slideIdx]);

  // Dynamic backend controls (arrows enable/disable, auto-running enable/disable, speed)
  const carouselConfig = useMemo(() => {
    const first = (Array.isArray(blogHeroBanners) && blogHeroBanners[0]) || {};
    return {
      showArrows: first.button_arrows !== "disable",
      autoRunning: first.auto_running !== "disable",
      autoRunSpeed: Number(first.auto_run_speed || first.auto_play_speed || 3500),
    };
  }, [blogHeroBanners]);

  // Auto carousel runs smoothly respecting admin speed & auto_running toggle, pauses on hover
  useEffect(() => {
    if (!carouselConfig.autoRunning || isTopSliderHovered || baseTopSliders.length <= 1) return;
    const interval = setInterval(() => {
      slideNext();
    }, carouselConfig.autoRunSpeed);
    return () => clearInterval(interval);
  }, [carouselConfig.autoRunning, carouselConfig.autoRunSpeed, isTopSliderHovered, baseTopSliders.length, slideNext]);

  const searchQueryParam = searchParams.get("search") || "";

  // Filtered view when user clicks topic in sub-navigation or searches
  const isFiltered = selectedCategoryParam !== "all" || Boolean(searchQueryParam.trim());
  const filteredPosts = useMemo(() => {
    let list = activePosts;
    if (selectedCategoryParam !== "all") {
      const key = selectedCategoryParam.toLowerCase().replace(/-gifts$/, "").replace(/-/g, " ");
      list = list.filter((p) => {
        const c = (p.category_name || "").toLowerCase();
        const t = (p.title || "").toLowerCase();
        return c.includes(key) || t.includes(key);
      });
    }
    if (searchQueryParam.trim()) {
      const q = searchQueryParam.toLowerCase().trim();
      list = list.filter((p) => {
        const t = (p.title || "").toLowerCase();
        const c = (p.category_name || "").toLowerCase();
        const e = (p.excerpt || "").toLowerCase();
        const a = (p.author_name || "").toLowerCase();
        return t.includes(q) || c.includes(q) || e.includes(q) || a.includes(q);
      });
    }
    return list;
  }, [activePosts, selectedCategoryParam, searchQueryParam]);

  return (
    <div className="blog-master-container">
      {/* =========================================================================
          HERO SECTION: FULL-SCREEN TOP SLIDER & BANNERS (Edge-to-Edge Full Width)
          ========================================================================= */}
      <section className="pm-blog-hero-section pm-blog-hero-fullscreen" aria-label="Featured Promo Sliders">
        <div 
          className="pm-blog-slider-wrap"
          onMouseEnter={() => setIsTopSliderHovered(true)}
          onMouseLeave={() => setIsTopSliderHovered(false)}
        >
          {carouselConfig.showArrows && (
            <button
              type="button"
              className="pm-blog-carousel-arrow left"
              onClick={slidePrev}
              aria-label="Previous Slide"
            >
              <FaChevronLeft />
            </button>
          )}

          <div className="pm-blog-slider-viewport" ref={viewportRef}>
            <div
              className="pm-blog-slider-track"
              style={{
                display: "flex",
                gap: `${gapSize}px`,
                transform: `translateX(-${slideIdx * (cardWidth + gapSize)}px)`,
                transition: isTransitioning ? "transform 0.55s cubic-bezier(0.25, 1, 0.5, 1)" : "none",
                willChange: "transform"
              }}
              onTransitionEnd={handleTransitionEnd}
            >
              {carouselTrackItems.map((item, idx) => (
                <div
                  key={item.id ? `${item.id}-track-${idx}` : `track-${idx}`}
                  style={{ width: `${cardWidth}px`, flexShrink: 0 }}
                  className="pm-blog-slider-item"
                >
                  <Link to={item.link || "/blog"} className="pm-blog-banner-card">
                    <div className="pm-blog-banner-img-box">
                      <img
                        src={resolveImageUrl(item.image)}
                        alt={item.title}
                        className="pm-blog-banner-img"
                        onError={(e) => {
                          e.currentTarget.src = "/banners/blog-banner-1.png";
                        }}
                      />
                    </div>
                    <div className="pm-blog-banner-label">
                      {item.title || "Slider & banner"}
                    </div>
                  </Link>
                </div>
              ))}
            </div>
          </div>

          {carouselConfig.showArrows && (
            <button
              type="button"
              className="pm-blog-carousel-arrow right"
              onClick={slideNext}
              aria-label="Next Slide"
            >
              <FaChevronRight />
            </button>
          )}
        </div>
      </section>

      {/* =========================================================================
          MAIN CONTAINER (Standard 1400px Grid per Rule 7)
          ========================================================================= */}
      <div className="blog-main-container">
        
        {/* =========================================================================
            FILTER NOTICE (Active only when a topic in header is selected)
            ========================================================================= */}
        {isFiltered && (
          <div className="alert alert-primary d-flex align-items-center justify-content-between my-3 py-2 px-3 rounded-2">
            <div>
              {searchQueryParam.trim() ? (
                <>
                  <strong>Search results for: </strong>
                  <span className="fst-italic">"{searchQueryParam}"</span>
                </>
              ) : (
                <>
                  <strong>Filtered by topic: </strong>
                  <span className="text-capitalize">{selectedCategoryParam.replace(/-/g, " ")}</span>
                </>
              )}
              <span className="text-muted ms-2">({filteredPosts.length} posts found)</span>
            </div>
            <button
              type="button"
              className="btn btn-sm btn-outline-primary py-0 px-2 fw-semibold"
              onClick={() => setSearchParams({})}
            >
              Show Full Blog Page
            </button>
          </div>
        )}

        {/* =========================================================================
            SECTION 2: MAIN 2-COLUMN SECTION (Latest Blogs + Sidebar)
            ========================================================================= */}
        {/* =========================================================================
            SECTION 2: MAIN 2-COLUMN SECTION (Scrollable Left Column + Stable Right Sidebar)
            ========================================================================= */}
        <section className="pm-blog-main-grid">
          {/* LEFT COLUMN: Scrollable Content (Latest Blogs, Categories & Grids) */}
          <div className="pm-blog-main-col">
            
            {/* 1. Latest Blogs 2-Card Carousel */}
            <div className="pm-blog-section-block">
              <h2 className="pm-blog-section-title">Latest Blogs</h2>
              <div className="pm-blog-latest-wrap">
                <button
                  type="button"
                  className="pm-blog-carousel-arrow left"
                  onClick={() => setLatestIdx((prev) => (prev > 0 ? prev - 1 : activePosts.length - 1))}
                  aria-label="Previous Latest Post"
                >
                  <FaChevronLeft />
                </button>

                <div className="pm-blog-two-cards-row">
                  {latestPostsSlice.map((post) => (
                    <WireframeBlogCard key={post.id} post={post} />
                  ))}
                </div>

                <button
                  type="button"
                  className="pm-blog-carousel-arrow right"
                  onClick={() => setLatestIdx((prev) => (prev + 1) % activePosts.length)}
                  aria-label="Next Latest Post"
                >
                  <FaChevronRight />
                </button>
              </div>
            </div>

            {/* 2. CATEGORY-WISE SHOWCASE GRIDS (Inside container) */}
            {/* Row 1: Corporate Gifts > & Business Gifts > */}
            <div className="pm-blog-cat-grid-row">
              <div className="pm-blog-cat-box">
                <Link to="/blog?category=corporate-gifts" className="pm-cat-box-header">
                  <span>Corporate Gifts</span>
                  <span className="pm-cat-arrow">&gt;</span>
                </Link>
                <div className="pm-cat-box-content">
                  <WireframeBlogCard post={corporatePost} />
                </div>
              </div>

              <div className="pm-blog-cat-box">
                <Link to="/blog?category=business-gifts" className="pm-cat-box-header">
                  <span>Business Gifts</span>
                  <span className="pm-cat-arrow">&gt;</span>
                </Link>
                <div className="pm-cat-box-content">
                  <WireframeBlogCard post={businessPost} />
                </div>
              </div>
            </div>

            {/* Row 2: Promotional Gifts > & Doctor Gifts > */}
            <div className="pm-blog-cat-grid-row">
              <div className="pm-blog-cat-box">
                <Link to="/blog?category=promotional-gifts" className="pm-cat-box-header">
                  <span>Promotional Gifts</span>
                  <span className="pm-cat-arrow">&gt;</span>
                </Link>
                <div className="pm-cat-box-content">
                  <WireframeBlogCard post={promoPost} />
                </div>
              </div>

              <div className="pm-blog-cat-box">
                <Link to="/blog?category=doctor-gifts" className="pm-cat-box-header">
                  <span>Docter Gifts</span>
                  <span className="pm-cat-arrow">&gt;</span>
                </Link>
                <div className="pm-cat-box-content">
                  <WireframeBlogCard post={doctorPost} />
                </div>
              </div>
            </div>

            {/* Row 3: Novelties Gifts > & Personalised Gifts > */}
            <div className="pm-blog-cat-grid-row">
              <div className="pm-blog-cat-box">
                <Link to="/blog?category=relationship-gifts" className="pm-cat-box-header">
                  <span>Novelties Gifts</span>
                  <span className="pm-cat-arrow">&gt;</span>
                </Link>
                <div className="pm-cat-box-content">
                  <WireframeBlogCard post={noveltiesPost} />
                </div>
              </div>

              <div className="pm-blog-cat-box">
                <Link to="/blog?category=personalized-gifts" className="pm-cat-box-header">
                  <span>Personalised Gifts</span>
                  <span className="pm-cat-arrow">&gt;</span>
                </Link>
                <div className="pm-cat-box-content">
                  <WireframeBlogCard post={personalizedPost} />
                </div>
              </div>
            </div>

            {/* 3. POPULAR BLOGS CAROUSEL ("Populer Blogs") */}
            <section className="pm-blog-carousel-section" aria-label="Popular Blogs">
              <h2 className="pm-blog-section-title">Populer Blogs</h2>
              <div className="pm-blog-latest-wrap">
                <button
                  type="button"
                  className="pm-blog-carousel-arrow left"
                  onClick={() => setPopularIdx((prev) => (prev > 0 ? prev - 1 : popularPosts.length - 1))}
                  aria-label="Previous Popular Post"
                >
                  <FaChevronLeft />
                </button>

                <div className="pm-blog-two-cards-row">
                  {popularPostsSlice.map((post) => (
                    <WireframeBlogCard key={`pop-${post.id}`} post={post} />
                  ))}
                </div>

                <button
                  type="button"
                  className="pm-blog-carousel-arrow right"
                  onClick={() => setPopularIdx((prev) => (prev + 1) % Math.max(1, popularPosts.length))}
                  aria-label="Next Popular Post"
                >
                  <FaChevronRight />
                </button>
              </div>
            </section>

            {/* 4. PRINTING BLOGS CAROUSEL ("Printing Blogs") */}
            <section className="pm-blog-carousel-section" aria-label="Printing Blogs">
              <h2 className="pm-blog-section-title">Printing Blogs</h2>
              <div className="pm-blog-latest-wrap">
                <button
                  type="button"
                  className="pm-blog-carousel-arrow left"
                  onClick={() => setPrintingIdx((prev) => (prev > 0 ? prev - 1 : activePosts.length - 1))}
                  aria-label="Previous Printing Post"
                >
                  <FaChevronLeft />
                </button>

                <div className="pm-blog-two-cards-row">
                  {printingPostsSlice.map((post) => (
                    <WireframeBlogCard key={`print-${post.id}`} post={post} />
                  ))}
                </div>

                <button
                  type="button"
                  className="pm-blog-carousel-arrow right"
                  onClick={() => setPrintingIdx((prev) => (prev + 1) % activePosts.length)}
                  aria-label="Next Printing Post"
                >
                  <FaChevronRight />
                </button>
              </div>
            </section>

            {/* 5. ALL BLOGS GRID (2-Column Grid) */}
            <section className="pm-blog-carousel-section" aria-label="All Blogs">
              <h2 className="pm-blog-section-title">All Blogs</h2>
              <div className="pm-blog-all-grid">
                {(isFiltered ? filteredPosts : activePosts).slice(0, 6).map((post) => (
                  <WireframeBlogCard key={`all-${post.id}`} post={post} />
                ))}
              </div>
            </section>

          </div>

          {/* RIGHT COLUMN: Stable / Sticky Sidebar (Banner + Recent Posts) */}
          <aside className="pm-blog-sidebar-col" aria-label="Blog Sidebar">
            {/* Sidebar Tall Banner */}
            <Link to={sidebarBanner.link} className="pm-blog-sidebar-banner-card">
              <img
                src={sidebarBanner.image}
                alt={sidebarBanner.title}
                className="pm-blog-sidebar-banner-img"
                onError={(e) => {
                  e.currentTarget.src = "/banners/pdp_small_banner_1.jpg";
                }}
              />
              <div className="pm-blog-sidebar-banner-title">
                Slider &amp; banner
              </div>
            </Link>

            {/* Recent Posts Widget (Stable / Sticky) */}
            <div className="pm-blog-recent-card">
              <h3 className="pm-blog-recent-title">Recent Posts</h3>
              <div className="pm-blog-recent-list">
                {(recentPosts.length > 0 ? recentPosts.slice(0, 6) : activePosts.slice(0, 6)).map((item) => (
                  <Link
                    key={item.id}
                    to={item.slug ? `/blog/${item.slug}` : `/blog/${item.id}`}
                    className="pm-blog-recent-item"
                  >
                    <img
                      src={item.featured_image ? resolveImageUrl(item.featured_image) : "/banners/banner-30.jpg"}
                      alt={item.title}
                      className="pm-blog-recent-thumb"
                      onError={(e) => {
                        e.currentTarget.src = "/banners/banner-30.jpg";
                      }}
                    />
                    <div className="pm-blog-recent-info">
                      <h4 className="pm-blog-recent-post-title">{item.title}</h4>
                      <span className="pm-blog-recent-date">
                        {formatDate(item.published_at || item.created_at)}
                      </span>
                    </div>
                  </Link>
                ))}
              </div>
            </div>
          </aside>
        </section>

      </div>
    </div>
  );
};

export default Blog;
