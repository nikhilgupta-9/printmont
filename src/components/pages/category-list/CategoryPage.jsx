import React from "react";
import { Link, useParams } from "react-router-dom";
import { API_ENDPOINTS, ROOT_URL, BASE_URL, ASSET_URL, getChildCategoriesUrl, resolveImageUrl } from "../../../config/apiEndpoints";
import AllProducts from "../../products/AllProducts";

// ── Migrated homepage components ──────────
import {
  ResponsiveHeroCarousel,
  ProductCarousel,
  MultiColumnBannerCarousel,
  ResponsiveBannerSet,
  MobileProductList
} from "../../home";
import TwoImgCarousel   from "../carousel/TwoImgCarousel";
import BannerSmall      from "../sections/BannerSmall";
import SmallBannerCarousel from "../../home/banners/SmallBannerCarousel";
import BeforeAfterSlider   from "./BeforeAfterSlider";
import CategoryGridSection  from "./CategoryGridSection";
import MobileBannerCarousel from "./MobileBannerCarousel";
import PersonalizedGifts  from "./PersonalizedGifts";
import StatsBanner        from "../sections/StatsBanner";
import TestimonialCarousel from "../sections/TestimonialCarousel";
import CustomerReviewsSection from "../sections/CustomerReviewsSection";

// ── Static data ───────────────────────────────────────────────────
import {
  categoryPageData,
  newArrivalProducts, bestSellerProducts, specialOfferProducts,
  menSubcategories, womenSubcategories, footwearSubcategories, kidsSubcategories,
  menOfferProducts, womenOfferProducts, footwearOfferProducts, kidsOfferProducts,
  mobileCategoryLinks, categoryReviews, heroBanners,
} from "../../../../data/categoryPageData";
import { categoriesData } from "../../../../data/categoriesdata";
import { sampleItems } from "../../../../data/data";

import "./CategoryPage.css";

/* ─────────────────────────────────────────────────────────────────
   SUB-COMPONENT: Mobile 3-col Category Grid  (Image 1 style)
   Props:
     title      – section heading e.g. "Men's Clothing"
     items      – array of { name, img, url }
     maxItems   – how many to show (default 9)
───────────────────────────────────────────────────────────────── */
const MobileCategoryGrid = ({ title, items = [], maxItems = 9 }) => {
  const visible = items.slice(0, maxItems);
  return (
    <div className="mob-cat-grid-section bg-white pt-3 pb-2 px-2">
      {title && (
        <h2 className="mob-cat-grid-title">{title}</h2>
      )}
      <div className="mob-cat-grid-3col">
        {visible.map((item, i) => (
          <Link
            key={i}
            to={item.url || "#"}
            className="mob-cat-grid-item text-decoration-none"
          >
            <div className="mob-cat-grid-img-wrap">
              <img
                src={item.img && item.img.startsWith("./") ? item.img.substring(1) : item.img}
                alt={item.name}
                className="mob-cat-grid-img"
                loading="lazy"
              />
            </div>
            <span className="mob-cat-grid-label">{item.name}</span>
          </Link>
        ))}
      </div>
    </div>
  );
};

/* ─────────────────────────────────────────────────────────────────
   SUB-COMPONENT: Mobile 2-col Category Grid with "View all" link
   Props:
     title      – section heading e.g. "Kids"
     viewAllUrl – link to full listing
     items      – array of { name, img, url }
     maxItems   – how many to show (default 6)
───────────────────────────────────────────────────────────────── */
const MobileCategoryGrid2Col = ({ title, viewAllUrl = "#", items = [], maxItems = 6 }) => {
  const visible = items.slice(0, maxItems);
  return (
    <div className="mob-cat-grid-section bg-white pt-3 pb-2 px-2">
      <div className="d-flex justify-content-between align-items-center mb-2 px-1">
        {title && <h2 className="mob-cat-grid-title m-0 p-0">{title}</h2>}
        <Link
          to={viewAllUrl}
          className="d-flex align-items-center justify-content-center rounded bg-theme px-2 py-1 text-white text-decoration-none"
          style={{ fontSize: "0.75rem", height: "24px", whiteSpace: "nowrap" }}
        >
          View all
        </Link>
      </div>
      <div className="mob-cat-grid-2col">
        {visible.map((item, i) => (
          <Link
            key={i}
            to={item.url || "#"}
            className="mob-cat-grid-item-2col text-decoration-none"
          >
            <div className="mob-cat-grid-img-wrap-2col">
              <img
                src={item.img && item.img.startsWith("./") ? item.img.substring(1) : item.img}
                alt={item.name}
                className="mob-cat-grid-img"
                loading="lazy"
              />
            </div>
            <span className="mob-cat-grid-label mt-1">{item.name}</span>
          </Link>
        ))}
      </div>
    </div>
  );
};

/* ─────────────────────────────────────────────────────────────────
   SUB-COMPONENT: Mobile Offer Zone  (Image 3 style)
   Props:
     badge      – top pill text  e.g. "🔥 Offer Zone Activated"
     title      – heading        e.g. "Top Discounts on Top Styles"
     products   – fallback static products
     apiUrl     – real-time products API endpoint
───────────────────────────────────────────────────────────────── */
const MobileOfferZone = ({
  badge = "🔥 Offer Zone Activated",
  title = "Top Discounts on Top Styles",
  products: staticProducts = [],
  apiUrl
}) => {
  const [items, setItems] = React.useState(staticProducts);

  React.useEffect(() => {
    if (!apiUrl) return;
    let isMounted = true;
    fetch(apiUrl)
      .then((res) => res.json())
      .then((data) => {
        if (isMounted && data?.success && Array.isArray(data.data) && data.data.length > 0) {
          const mapped = data.data.map((p) => {
            const rawImg =
              p.image ||
              p.primary_image ||
              (Array.isArray(p.images) ? p.images[0]?.image_url || p.images[0] : "") ||
              "/default-img.jpg";
            const price = parseFloat(p.price || p.discount_price || 0);
            const orig = parseFloat(p.original_price || p.originalPrice || 0);
            const disc =
              orig > price && price > 0
                ? `${Math.round(((orig - price) / orig) * 100)}% Off`
                : p.discount || "";
            return {
              id: p.id,
              slug: p.slug || p.product_slug,
              brand: p.brand || p.name || p.title,
              image: resolveImageUrl(rawImg),
              discountedPrice: price,
              originalPrice: orig,
              discountPercent: disc ? parseInt(disc, 10) : p.discountPercent || 35
            };
          });
          setItems(mapped);
        }
      })
      .catch(() => {});

    return () => {
      isMounted = false;
    };
  }, [apiUrl]);

  const displayList = items && items.length > 0 ? items : staticProducts;

  return (
    <div className="mob-offer-zone">
      {/* Header */}
      <div className="mob-offer-zone-header">
        <span className="mob-offer-zone-badge">{badge}</span>
        <p className="mob-offer-zone-title">{title}</p>
      </div>

      {/* Horizontal product carousel */}
      <div className="mob-offer-zone-track">
        {displayList.map((p, i) => {
          const img = Array.isArray(p.image) ? p.image[0] : p.image;
          const targetLink = p.slug ? `/product/${p.slug}` : `/product/${p.id || i}`;
          return (
            <Link key={p.id || i} to={targetLink} className="mob-offer-zone-card text-decoration-none">
              <div className="mob-offer-zone-img-wrap">
                <img src={img} alt={p.brand || "product"} className="mob-offer-zone-img" loading="lazy" />
              </div>
              <p className="mob-offer-zone-name">{p.brand || "Product name....."}</p>
              {p.discountPercent && (
                <p className="mob-offer-zone-disc">{p.discountPercent}% Off</p>
              )}
            </Link>
          );
        })}
      </div>
    </div>
  );
};

/* ── Mobile quick-link grid ──────────────────────────────────────── */
const MobileQuickLinks = ({ links }) => (
  <div className="mob-quicklinks">
    {links.map((l, i) => (
      <Link key={i} to={l.url} className="mob-quicklink-item">{l.name}</Link>
    ))}
  </div>
);

const CategoryInfoTextField = ({ initialText }) => {
  const [text, setText] = React.useState(
    initialText ||
    "Discover our premium selection of custom printed apparel, corporate merchandise, and high-quality clothing. We use state-of-the-art sublimation and embroidery techniques to deliver vibrant, durable prints tailored for your business or personal needs."
  );
  const [isEditing, setIsEditing] = React.useState(false);

  return (
    <div className="mob-section-gap px-3 pb-4">
      <div className="bg-light p-3 border rounded shadow-sm">
        <div className="d-flex justify-content-between align-items-center mb-2">
          <h4 className="fw-bold m-0" style={{ color: "#0b53a1", fontSize: "16px" }}>
            Category Information Text
          </h4>
          <button
            onClick={() => setIsEditing(!isEditing)}
            className="btn btn-sm btn-outline-primary py-0 px-2"
            style={{ fontSize: "12px" }}
          >
            {isEditing ? "Save" : "Edit Info"}
          </button>
        </div>
        
        {isEditing ? (
          <textarea
            className="form-control form-control-sm"
            rows="4"
            value={text}
            onChange={(e) => setText(e.target.value)}
            style={{ fontSize: "13px" }}
          />
        ) : (
          <p className="text-muted mb-0" style={{ fontSize: "13px", lineHeight: "1.6", whiteSpace: "pre-line" }}>
            {text}
          </p>
        )}
      </div>
    </div>
  );
};

const CategoryPage = () => {
  // Route is /category/:id, but every <Categories>/<CategoryGridSection> link
  // in this app builds URLs from a category's slug, not its numeric id — so
  // this param is actually a slug (e.g. "photo-gifts-2" or "same-day-products").
  const { id: categorySlug } = useParams();

  // A category with no subcategories is a leaf (e.g. "Bamboo Bottles") — those
  // render the shared AllProducts listing scoped to the category.
  const [categoryInfo, setCategoryInfo] = React.useState(null);
  const [subcategoriesList, setSubcategoriesList] = React.useState([]);
  const [isLeafCategory, setIsLeafCategory] = React.useState(false);
  const [isResolvingCategory, setIsResolvingCategory] = React.useState(!!categorySlug);

  React.useEffect(() => {
    if (!categorySlug) {
      setCategoryInfo(null);
      setSubcategoriesList([]);
      setIsLeafCategory(false);
      setIsResolvingCategory(false);
      return;
    }

    let cancelled = false;
    setIsResolvingCategory(true);

    (async () => {
      try {
        const catRes = await fetch(API_ENDPOINTS.CATEGORY_BY_SLUG(categorySlug));
        const catData = await catRes.json();
        if (cancelled) return;

        if (!catData?.success || !catData.data?.id) {
          setCategoryInfo(null);
          setSubcategoriesList([]);
          setIsLeafCategory(false);
          return;
        }

        const category = catData.data;
        const childrenUrl = getChildCategoriesUrl(category.id, category.level);

        let hasChildren = false;
        let loadedSubs = [];
        if (childrenUrl) {
          const subRes = await fetch(childrenUrl);
          const subData = await subRes.json();
          if (cancelled) return;
          if (subData?.success && Array.isArray(subData.data) && subData.data.length > 0) {
            hasChildren = true;
            loadedSubs = await Promise.all(
              subData.data.map(async (sub) => {
                const subChildUrl = getChildCategoriesUrl(sub.id, sub.level);
                let items = [];
                if (subChildUrl) {
                  try {
                    const r = await fetch(subChildUrl);
                    const d = await r.json();
                    if (d?.success && Array.isArray(d.data)) {
                      items = d.data.map((c) => ({
                        name: c.name,
                        url: `/category/${c.slug}`,
                        img: resolveImageUrl(c.images?.image || c.images?.desktop || c.image || "")
                      }));
                    }
                  } catch (e) {}
                }
                return {
                  id: sub.id,
                  name: sub.name,
                  slug: sub.slug,
                  url: `/category/${sub.slug}`,
                  img: resolveImageUrl(sub.images?.image || sub.images?.desktop || sub.image || ""),
                  items: items
                };
              })
            );
          }
        }

        setCategoryInfo({ id: category.id, name: category.name, description: category.description });
        setSubcategoriesList(loadedSubs);
        setIsLeafCategory(!hasChildren);
      } catch (error) {
        if (!cancelled) {
          setCategoryInfo(null);
          setSubcategoriesList([]);
          setIsLeafCategory(false);
        }
      } finally {
        if (!cancelled) setIsResolvingCategory(false);
      }
    })();

    return () => { cancelled = true; };
  }, [categorySlug]);

  if (isResolvingCategory) {
    return (
      <div className="d-flex justify-content-center align-items-center" style={{ minHeight: "50vh" }}>
        <div className="spinner-border text-primary" role="status">
          <span className="visually-hidden">Loading...</span>
        </div>
      </div>
    );
  }

  if (isLeafCategory && categoryInfo) {
    return <AllProducts categoryScope={{ id: categoryInfo.id, name: categoryInfo.name }} />;
  }

  // Dynamic Subcategory references with fallbacks
  const sub1 = subcategoriesList[0] || null;
  const sub2 = subcategoriesList[1] || null;
  const sub3 = subcategoriesList[2] || null;
  const sub4 = subcategoriesList[3] || null;

  const sub1Items = (sub1?.items && sub1.items.length > 0) ? sub1.items : categoriesData.slice(0, 9);
  const sub2Items = (sub2?.items && sub2.items.length > 0) ? sub2.items : categoriesData.slice(0, 9);
  const sub3Items = (sub3?.items && sub3.items.length > 0) ? sub3.items : categoriesData.slice(2, 11);
  const sub4Items = (sub4?.items && sub4.items.length > 0) ? sub4.items : categoriesData.slice(0, 6);

  return (
    <div className="category-page">

      {/* Professional Breadcrumb & Title Banner */}
      {categoryInfo && (
        <div className="bg-white border-bottom py-2 px-3 mb-2 shadow-xs">
          <div className="d-flex align-items-center justify-content-between mx-auto" style={{ maxWidth: '1440px' }}>
            <nav aria-label="breadcrumb">
              <ol className="breadcrumb mb-0" style={{ fontSize: '13px' }}>
                <li className="breadcrumb-item"><Link to="/" className="text-secondary text-decoration-none">Home</Link></li>
                <li className="breadcrumb-item"><Link to="/category" className="text-secondary text-decoration-none">Categories</Link></li>
                <li className="breadcrumb-item active fw-bold" aria-current="page" style={{ color: '#00529b' }}>{categoryInfo.name}</li>
              </ol>
            </nav>
            <span className="badge rounded-pill bg-light text-primary border px-3 py-1.5 d-none d-md-inline-block" style={{ fontSize: '11.5px', fontWeight: 600 }}>
              Custom Printing &amp; Personalization
            </span>
          </div>
        </div>
      )}

      {/* ════ DESKTOP LAYOUT (≥ lg) ════════════════════════════════ */}
      <div className="d-none d-lg-block custom-bg w-100">
        <div className="cp-desktop-inner mx-auto home-desktop-wrapper">

          {/* Small Banner Carousel (Configured from Admin) */}
          <div style={{ margin: 0, padding: 0 }}>
            <SmallBannerCarousel page="category" isMobile={false} />
          </div>

          {/* 2. Small Banner (First 2 images) */}
          <div className="cp-card-section">
            <BannerSmall apiUrl={`${BASE_URL}/banner_api.php`} sliceStart={0} sliceEnd={2} />
          </div>

          {/* 3. ── BIG BANNER — Before / After comparison slider ── */}
          <div className="cp-card-section cp-compare-wrap">
            <BeforeAfterSlider
              beforeSrc="/before.png"
              afterSrc="/after.png"
              beforeLabel="Without Printing"
              afterLabel="With Printing"
              height="420px"
            />
          </div>

          {/* 4. Small Banner (Next 2 images) */}
          <div className="cp-card-section">
            <BannerSmall apiUrl={`${BASE_URL}/banner_api.php`} sliceStart={2} sliceEnd={4} />
          </div>

          {/* 4.5. Categories Grid Section */}
          <div className="cp-card-section">
            <CategoryGridSection categorySlug={categorySlug} />
          </div>

          {/* 5. New Arrivals — ProductCarousel */}
          <div className="cp-card-section">
            <ProductCarousel
              apiUrl={`${BASE_URL}/products/products.php?action=top_selection`}
              title="New Arrivals"
              badgeText="NEW ARRIVAL"
            />
          </div>

          {/* 5. Main Slider */}
          <div className="cp-card-section">
            <ResponsiveHeroCarousel
              apiUrl={`${BASE_URL}/banner_api.php`}
              basePath={`${BASE_URL}uploads/banners/`}
              carouselId="catSmallCarousel"
            />
          </div>

          {/* 6. Best Sellers — ProductCarousel */}
          <div className="cp-card-section">
            <ProductCarousel
              apiUrl={`${BASE_URL}/products/products.php?action=top_deal`}
              title="Best Sellers"
              badgeText="BEST SELLER"
            />
          </div>

          {/* 7. ResponsiveBannerSet */}
          <div className="cp-card-section">
            <ResponsiveBannerSet apiUrl={`${BASE_URL}/banner_api.php?section=banner_api7`} />
          </div>

          {/* 7.5. Personalized Gifts Section */}
          <div className="cp-card-section">
            <PersonalizedGifts />
          </div>

          {/* 7.6. Four Image Banner */}
          <div className="cp-card-section">
            <MultiColumnBannerCarousel apiUrl={`${BASE_URL}/banner_api.php`} columns={4} sectionKey="home_mid_section_2" />
          </div>

          {/* Special Offers Carousel */}
          <div className="cp-card-section">
            <ProductCarousel
              apiUrl={`${BASE_URL}/products/products.php?action=discount_for_you`}
              title="Special Offers"
              badgeText="SPECIAL OFFER"
            />
          </div>

          {/* Stats Banner */}
          <div className="cp-card-section">
            <StatsBanner />
          </div>

          {/* Customer Reviews Section */}
          <div className="cp-card-section">
            <CustomerReviewsSection categorySlug={categorySlug} categoryId={categoryInfo?.id} />
          </div>

        </div>
      </div>

      {/* ════ MOBILE LAYOUT (< lg) MATCHING PDF WIREFRAME ════════════ */}
      <div className="d-block d-lg-none bg-white w-100 home-mobile-content">

        {/* Small Banner Carousel (Configurable from Admin) */}
        <div style={{ margin: 0, padding: 0 }}>
          <SmallBannerCarousel page="category" isMobile={true} />
        </div>

        {/* 1 - Slider (Top Single Banner Slider) */}
        <MobileBannerCarousel
          apiUrl={`${BASE_URL}/banner_api.php?section=category_slider`}
          type="single"
        />

        {/* 2 - Banner carousel (Double image side-by-side) */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}/banner_api.php?section=category_double`}
            type="double"
          />
        </div>

        {/* 3 - Banner and slider */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}/banner_api.php?section=category_banner_slider`}
            type="single"
          />
        </div>

        {/* 4 - Best Seller (Carousel) */}
        <div className="mob-section-gap">
          <ProductCarousel
            apiUrl={`${BASE_URL}/products/products.php?action=bestseller`}
            title="Best Seller (Carousel)"
            badgeText="BEST SELLER"
          />
        </div>

        {/* 5 - Banner and slider */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}/banner_api.php?section=category_banner_slider_2`}
            type="single"
          />
        </div>

        {/* 6 - Subcategory 1: 3-column Grid (e.g. Photo Prints / Men's Clothing) */}
        <div className="mob-section-gap">
          <MobileCategoryGrid
            title={sub1?.name || (categoryInfo ? `${categoryInfo.name} Collection` : "Men's Clothing")}
            items={sub1Items}
            maxItems={9}
          />
        </div>

        {/* 7 - 2-Column Product List (6 items with CUSTOMIZABLE blue tag) */}
        <div className="mob-section-gap">
          <MobileProductList
            apiUrl={`${BASE_URL}/products/products.php?action=top_selection`}
            products={menOfferProducts}
          />
        </div>

        {/* 8 - Banner carousel (Double image side-by-side) */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}/banner_api.php?section=category_double_2`}
            type="double"
          />
        </div>

        {/* 9 - Offer Zone Activated (Carousel 1) */}
        <div className="mob-section-gap">
          <MobileOfferZone
            apiUrl={`${BASE_URL}/products/products.php?action=top_deal`}
            badge="🔥 Offer Zone Activated"
            title="Top Discounts on Top Styles"
            products={menOfferProducts}
          />
        </div>

        {/* 10 - Banner and slider */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}/banner_api.php?section=category_banner_slider_3`}
            type="single"
          />
        </div>

        {/* 11 - Subcategory 2: 3-column Grid (e.g. Photo Frames / Women's Clothing) */}
        <div className="mob-section-gap">
          <MobileCategoryGrid
            title={sub2?.name || "Women's Clothing"}
            items={sub2Items}
            maxItems={9}
          />
        </div>

        {/* 12 - Banner carousel (Double image side-by-side) */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}/banner_api.php?section=category_double_3`}
            type="double"
          />
        </div>

        {/* 13 - Offer Zone Activated (Carousel 2) */}
        <div className="mob-section-gap">
          <MobileOfferZone
            apiUrl={`${BASE_URL}/products/products.php?action=discount_for_you`}
            badge="🔥 Offer Zone Activated"
            title="Top Discounts on Top Styles"
            products={womenOfferProducts}
          />
        </div>

        {/* 14 - Banner and slider */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}/banner_api.php?section=category_banner_slider_4`}
            type="single"
          />
        </div>

        {/* 15 - Subcategory 3: 3-column Grid (e.g. Photo Mugs / Footwear) */}
        <div className="mob-section-gap">
          <MobileCategoryGrid
            title={sub3?.name || "Footwears"}
            items={sub3Items}
            maxItems={9}
          />
        </div>

        {/* 16 - Banner carousel (Double image side-by-side) */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}/banner_api.php?section=category_double_4`}
            type="double"
          />
        </div>

        {/* 17 - Offer Zone Activated (Carousel 3) */}
        <div className="mob-section-gap">
          <MobileOfferZone
            apiUrl={`${BASE_URL}/products/products.php?action=top_selection`}
            badge="🔥 Offer Zone Activated"
            title="Top Discounts on Top Styles"
            products={footwearOfferProducts}
          />
        </div>

        {/* 18 - Banner and slider */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}/banner_api.php?section=category_banner_slider_5`}
            type="single"
          />
        </div>

        {/* 19 - Subcategory 4: 2-column Grid with "View all" link (e.g. Kids / Canvas) */}
        <div className="mob-section-gap">
          <MobileCategoryGrid2Col
            title={sub4?.name || "Kids"}
            viewAllUrl={sub4?.url || "/allproducts"}
            items={sub4Items}
            maxItems={6}
          />
        </div>

        {/* 20 - Banner carousel (Double image side-by-side) */}
        <div className="mob-section-gap">
          <MobileBannerCarousel
            apiUrl={`${BASE_URL}/banner_api.php?section=category_double_5`}
            type="double"
          />
        </div>

        {/* 21 - Offer Zone Activated (Carousel 4) */}
        <div className="mob-section-gap">
          <MobileOfferZone
            apiUrl={`${BASE_URL}/products/products.php?action=top_rated`}
            badge="🔥 Offer Zone Activated"
            title="Top Discounts on Top Styles"
            products={kidsOfferProducts}
          />
        </div>

        {/* 22 - Product List 2 ("Kuch Product yaha pe bhi show karna" 6 products in 2 cols) */}
        <div className="mob-section-gap">
          <MobileProductList
            apiUrl={`${BASE_URL}/products/products.php?action=top_deal`}
            products={kidsOfferProducts}
          />
        </div>

        {/* 23 - Main Category text here */}
        <div className="mob-about-section">
          <div className="mob-about-inner">
            <span className="mob-about-tag">About this Category</span>
            <h3 className="mob-about-title">{categoryInfo?.name || categoryPageData.name}</h3>
            <p className="mob-about-desc">{categoryInfo?.description || categoryPageData.description}</p>
            {subcategoriesList.length > 0 && (
              <div className="mob-about-tags">
                {subcategoriesList.map((sub, i) => (
                  <Link key={i} to={sub.url} className="mob-about-pill">{sub.name}</Link>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* 23b - Customer Reviews Section */}
        <div className="mob-section-gap px-2">
          <CustomerReviewsSection categorySlug={categorySlug} categoryId={categoryInfo?.id} />
        </div>

        {/* 24 - Category Information Custom Live Text Field */}
        <CategoryInfoTextField initialText={categoryInfo?.description || categoryPageData.description} />

      </div>
    </div>
  );
};

export default CategoryPage;
