# Printmont — Category Page Implementation Plan
**Desktop + Mobile · React / Next.js · Tailwind CSS**

---

## Table of Contents
1. [Phase 1 — Audit & Setup](#phase-1--audit--setup)
2. [Phase 2 — Component Breakdown](#phase-2--component-breakdown)
3. [Phase 3 — Page Structure (Top to Bottom)](#phase-3--page-structure-top-to-bottom)
4. [Phase 4 — Backend API Requirements](#phase-4--backend-api-requirements)
5. [Phase 5 — Recommended Build Order](#phase-5--recommended-build-order)
6. [Critical Details — Do Not Miss](#critical-details--do-not-miss)

---

## Phase 1 — Audit & Setup

> **Do this before writing a single line of code.**

### Stack Confirmation
| Item | Recommendation |
|------|---------------|
| Frontend Framework | React / Next.js (recommended for SSR + SEO on category pages) |
| CSS Approach | Tailwind CSS — utility classes for responsive breakpoints |
| Breakpoint | Define **768px** (or 1024px) as mobile/desktop split — pick one and commit |

### Pre-Build Checklist

Before coding, answer every item below:

- [ ] Is the Navbar already built? List existing props it accepts
- [ ] Does a ProductCard component exist? Confirm it supports badge, price, discount %, rating
- [ ] Is a Carousel wrapper available, or do we need to build one from scratch?
- [ ] Does the Footer support a mobile variant?
- [ ] Does the Banner component accept 4 layout types?
- [ ] Are all APIs ready? (products, categories, banners, reviews, offer zone)

> **Warning:** Do not start building until API contracts are defined. Banners, product tags (New Arrival / Best Seller / Special Offer), and the Offer Zone toggle all need backend config endpoints agreed first. Building UI with hardcoded data then wiring later creates double the work.

---

## Phase 2 — Component Breakdown

### Shared Components (Desktop + Mobile)

| Component | Details |
|-----------|---------|
| `Navbar` | Logo · Search bar · Wishlist · Cart · Login · **Top Category horizontal menu (14 links)** |
| `Footer` | Links · Trust badges · Social icons · Copyright bar |
| `ProductCard` | Badge (New Arrival / Best Seller / Customizable pill) · Seller name · Product name · Price · Original price (crossed) · Discount % · Star rating · Review count |
| `Carousel` | Reusable wrapper — accepts any children. **Desktop: 5 visible + 6th half-visible. Mobile: 2-col grid** |
| `BannerSlider` | 4 layout types — type selected from admin backend via `layoutType: 1 | 2 | 3 | 4` prop |

### Desktop-Only Components

| Component | Details |
|-----------|---------|
| `TopCategoryMenu` | Horizontal nav: Gift Sets · Event Merch · Clothes & Bags · Trophies & Awards · Stationary · Office Supplies · Pens · Keychain · Drinkware · Personalised Gifts · Tech Products · Diwali Gifts · Occasions · Corporate Gifts |
| `CategoryHeroSection` | Full-width banner + subcategory **outline pills** (border only, no background fill) in a grid |
| `NewArrivalSection` | Carousel · 5 products visible · 6th card half-visible · View All CTA |
| `BestSellerSection` | Same layout as New Arrival — reuse same component with different data prop |
| `SpecialOfferSection` | Products loaded from backend · Same carousel pattern · View All CTA |
| `CustomerReviews` | **5 cards** (NOT 4) · Carousel · Star rating · Reviewer name |
| `StatsBanner` | 1 Million+ Customers · 20,000+ Products · 7 Years of Service Excellence |

### Mobile-Only Components

| Component | Details |
|-----------|---------|
| `MobileHeader` | Category name **centered** · Login icon right · Back arrow left |
| `MobileBannerCarousel` | Full-width stacked/swiped banners — two sizes: full Slider + smaller Banner carousel |
| `MobileProductRow` | **2-column grid: New Arrival LEFT · Best Seller RIGHT** — paired layout, repeats per subcategory |
| `MobileCategoryGrid` | 3-column category name grid (text labels only) |
| `OfferZoneBanner` | "Offer Zone Activated · Top Discounts on Top Styles" · 70% Off badge · Discount carousel · **Repeats for each clothing / footwear / kids category** |
| `MobileFooterProducts` | Extra product block above main footer |
| `MobileFooter` | Trust badges · Contact · Quick Links · Help Center · Copyright bar · Social icons |

> **Reuse tip:** `MobileProductRow` and `OfferZoneBanner` form a repeating pair. Wrap them in a `CategorySection` template that accepts `categoryName`, `subcategories[]`, `newArrivals[]`, `bestSellers[]`, `offerZoneEnabled`. This template repeats for Men's Clothing → Women's Clothing → Footwear → Kids.

---

## Phase 3 — Page Structure (Top to Bottom)

### Desktop Category Page Layout

```
┌─────────────────────────────────────────────────┐
│  Navbar + Top Category Menu (14 links)          │
├─────────────────────────────────────────────────┤
│  Small Banner + Slider × 2 (two rows)           │
├─────────────────────────────────────────────────┤
│  Single Main Category → All Subcategories       │
│  (subcategories as outline pills — no fill)     │
├─────────────────────────────────────────────────┤
│  New Arrival Carousel → [View All]              │
│  (5 visible · 6th half-visible)                 │
├─────────────────────────────────────────────────┤
│  4 Types Banner (backend-selectable design)     │
├─────────────────────────────────────────────────┤
│  Best Seller Carousel → [View All]              │
│  (5 visible · 6th half-visible)                 │
├─────────────────────────────────────────────────┤
│  4 Types Banner (backend-selectable design)     │
├─────────────────────────────────────────────────┤
│  Special Offer Carousel → [View All]            │
│  (extra products loaded from backend)           │
├─────────────────────────────────────────────────┤
│  4 Types Banner (backend-selectable design)     │
├─────────────────────────────────────────────────┤
│  Stats Bar: 1M+ Customers · 20,000+ Products   │
│             · 7 Years of Service               │
├─────────────────────────────────────────────────┤
│  Customer Reviews Carousel (5 cards — NOT 4)   │
├─────────────────────────────────────────────────┤
│  Footer                                         │
└─────────────────────────────────────────────────┘
```

### Mobile Category Page Layout

```
┌──────────────────────────────────┐
│  Mobile Header                   │
│  (Category name centered)        │
├──────────────────────────────────┤
│  Slider (full-width)             │
├──────────────────────────────────┤
│  Category Name Grid (2-col)      │
├──────────────────────────────────┤
│  Banner + Slider                 │
├──────────────────────────────────┤
│  New Arrival | Best Seller       │
│  (2-col product grid)            │
├──────────────────────────────────┤
│  Banner + Slider                 │
├──────────────────────────────────┤
│  ── Men's Clothing ──            │
│  3-col subcategory grid          │
│  New Arrival | Best Seller grid  │
│  Offer Zone Activated            │
│  Discount Carousel (70% off)     │
├──────────────────────────────────┤
│  Banner + Slider                 │
├──────────────────────────────────┤
│  ── Women's Clothing ──          │
│  3-col subcategory grid          │
│  New Arrival | Best Seller grid  │
│  Offer Zone Activated            │
│  Discount Carousel               │
├──────────────────────────────────┤
│  Banner + Slider                 │
├──────────────────────────────────┤
│  ── Footwear ──                  │
│  3-col subcategory grid          │
│  New Arrival | Best Seller grid  │
│  Offer Zone Activated            │
│  Discount Carousel               │
├──────────────────────────────────┤
│  ── Kids ──                      │
│  3-col subcategory grid          │
│  Products → [View All]           │
│  Offer Zone Activated            │
│  Discount Carousel               │
├──────────────────────────────────┤
│  Extra Products Block            │
│  (Kuch Product yahan bhi)        │
├──────────────────────────────────┤
│  Final New Arrival | Best Seller │
│  (2-col grid, repeated)          │
├──────────────────────────────────┤
│  Main Category Label (large)     │
├──────────────────────────────────┤
│  Trust Badges                    │
│  100% Safe · Free Shipping       │
│  100% Original Guarantee         │
├──────────────────────────────────┤
│  Mobile Footer                   │
│  Contact · Quick Links           │
│  Help Center · Copyright         │
└──────────────────────────────────┘
```

---

## Phase 4 — Backend API Requirements

### APIs Needed

| Endpoint | Purpose | Priority |
|----------|---------|----------|
| `GET /category/:id` | Category name, description, subcategories list | Required |
| `GET /products?tag=new-arrival&category=:id` | New Arrival products for this category | Required |
| `GET /products?tag=best-seller&category=:id` | Best Seller products for this category | Required |
| `GET /products?tag=special-offer&category=:id` | Special Offer — extra products from backend | Required |
| `GET /banners?section=:sectionId&category=:id` | Banner image + layout type (1–4) | Required |
| `GET /reviews?category=:id&limit=5` | Exactly 5 reviews for carousel | Required |
| `GET /offer-zone?category=:id` | Offer Zone enabled/disabled + discounted products | Required |

### Admin Panel Controls Needed

**1. Banner Manager**
- Upload banners per section
- Select layout type (1 of 4 designs)
- Assign banner to specific category

**2. Category Page Builder**
- Drag to reorder sections
- Toggle sections on/off per category

**3. Product Tagging**
- Tag products as `New Arrival` / `Best Seller` / `Special Offer` from product admin
- Products can carry multiple tags

**4. Offer Zone Toggle**
- Enable / disable Offer Zone per category
- Mobile-only section — does not appear on desktop

**5. Reviews Feed**
- Pull or curate exactly 5 reviews per category page

> **Implementation note:** Banner `layoutType` should be a field on the banner record (e.g. `layoutType: 1 | 2 | 3 | 4`). The frontend switches between 4 CSS/component variants based on this value — no hardcoding layout per section.

---

## Phase 5 — Recommended Build Order

### Step 1 — ProductCard
Build this **first** — it is used in every section.

Must support all these states:
- Badge: `NEW ARRIVAL` (green) · `BEST SELLER` (amber) · `CUSTOMIZABLE` (pill)
- Seller name
- Product name (truncated to 2 lines)
- Price (current) + Original price (strikethrough)
- Discount % in red (e.g. `35% Off`)
- Star rating (e.g. `5.5`) + review count (e.g. `301 Reviews`)

```tsx
<ProductCard
  badge="new-arrival"          // "new-arrival" | "best-seller" | "customizable" | null
  sellerName="Seller name here"
  productName="Pacific Blue Over Dyed Shirt For Men"
  price={499}
  originalPrice={999}
  discountPercent={35}
  rating={5.5}
  reviewCount={301}
  isCustomizable={true}
/>
```

---

### Step 2 — Carousel Wrapper
Generic carousel accepting any children.

Key requirements:
- **Desktop:** 5 cards fully visible, 6th card half-visible (clips at edge)
- **Mobile:** 2-column grid layout
- Left/right arrow navigation
- Touch/swipe support on mobile
- Accepts `viewAll` link prop

```tsx
// Half-visible 6th card CSS technique:
.carousel-track {
  display: flex;
  width: calc(5 * 220px + 0.5 * 220px); /* 5 full + half */
  overflow: hidden;
}
```

---

### Step 3 — BannerSlider (4 Layout Types)
Build all 4 banner layout variants. Controlled by `layoutType` prop from API.

```tsx
<BannerSlider
  images={banners}
  layoutType={2}   // 1 | 2 | 3 | 4 — admin selects from backend
/>
```

Test all 4 variants with placeholder images before wiring to API.

---

### Step 4 — Navbar (Desktop) + MobileHeader

**Desktop Navbar:**
- Logo left
- Search bar center
- Wishlist · Cart · Login right
- `TopCategoryMenu` below main bar — horizontal scrolling list of 14 category links

**Mobile Header:**
- Back arrow left
- Category name **centered**
- Login icon right

---

### Step 5 — Footer (Desktop) + MobileFooter

**Desktop Footer:** Full links, trust badges, social icons, copyright.

**Mobile Footer:**
- Trust badges row: `100% Safe and Secure Payments` · `Free Shipping` · `100% Original Guarantee`
- Contact Us · Quick Links · Help Center columns
- Social icons (Follow us)
- Copyright bar: `© Copyright 2019–2025 Printmont.com All Rights Reserved`
- `MobileFooterProducts` block sits **above** the footer

---

### Step 6 — CategorySection Template (Mobile)
The most important reusable template on mobile — used 4 times.

```tsx
<CategorySection
  categoryName="Men's Clothing"
  subcategories={[...]}
  newArrivals={[...]}
  bestSellers={[...]}
  offerZoneEnabled={true}
  offerZoneProducts={[...]}
/>
```

Internal structure:
```
BannerSlider
→ 3-col subcategory grid (MobileCategoryGrid)
→ MobileProductRow (New Arrival left | Best Seller right)
→ OfferZoneBanner + Discount Carousel  (if offerZoneEnabled)
```

---

### Step 7 — Desktop Category Page Assembly

Compose the full desktop page using components from Steps 1–5:

```
Navbar
→ BannerSlider (small banner × 2)
→ CategoryHeroSection (outline subcategory pills)
→ NewArrivalSection (Carousel + View All)
→ BannerSlider (4-type)
→ BestSellerSection (Carousel + View All)
→ BannerSlider (4-type)
→ SpecialOfferSection (Carousel + View All)
→ BannerSlider (4-type)
→ StatsBanner (1M+ · 20,000+ · 7 Years)
→ CustomerReviews (5-card Carousel)
→ Footer
```

---

### Step 8 — Mobile Category Page Assembly

```
MobileHeader
→ Slider (full-width)
→ MobileCategoryGrid (2-col quick links)
→ BannerSlider
→ MobileProductRow (New Arrival | Best Seller)
→ BannerSlider
→ CategorySection (Men's Clothing)
→ BannerSlider
→ CategorySection (Women's Clothing)
→ BannerSlider
→ CategorySection (Footwear)
→ CategorySection (Kids + View All)
→ MobileFooterProducts (extra product block)
→ MobileProductRow (final New Arrival | Best Seller)
→ Main Category Label (large text)
→ Trust Badges
→ MobileFooter
```

---

### Step 9 — Backend Integration
Replace all static placeholder data with real API calls:
- Category data (name, description, subcategories)
- Product lists by tag per category
- Banner configs with `layoutType` values
- Reviews (limit 5)
- Offer Zone toggle + products

---

### Step 10 — QA Pass
Before sign-off, test every item:

- [ ] 6th product card is half-visible in all carousels (New Arrival, Best Seller, Special Offer)
- [ ] Subcategory pills show as outline style — border only, no background fill
- [ ] All 4 banner layout types switch correctly from admin
- [ ] Customer Reviews carousel shows **5 cards**, not 4
- [ ] Offer Zone section appears independently under Men's, Women's, Footwear, and Kids on mobile
- [ ] Mobile product grid is always 2-col: New Arrival LEFT, Best Seller RIGHT
- [ ] `CUSTOMIZABLE` pill badge appears on eligible product cards
- [ ] Offer Zone toggle disables the section completely when turned off from admin
- [ ] Stats bar shows on desktop only (hidden on mobile)
- [ ] Trust badges appear in mobile footer

---

## Critical Details — Do Not Miss

### ProductCard — All Badge States

| Badge | Color | When to show |
|-------|-------|-------------|
| `NEW ARRIVAL` | Green | Product tagged as new-arrival in admin |
| `BEST SELLER` | Amber | Product tagged as best-seller in admin |
| `CUSTOMIZABLE` | Blue pill | Product has customization option enabled |
| `35% Off` | Red text | Discount % calculated from original vs sale price |

### Half-Visible 6th Card — Implementation

The 6th card in every carousel must clip at the right edge to signal to users that the list is scrollable.

```css
/* Container shows exactly 5 + half a 6th card */
.carousel-wrapper {
  overflow: hidden;
  width: 100%;
}

.carousel-track {
  display: flex;
  gap: 16px;
  /* 5 cards at 200px each + 16px gaps + half of 6th card */
  width: calc(5 * 200px + 4 * 16px + 100px);
}

.product-card {
  flex: 0 0 200px;
}
```

### Subcategory Pills — Outline Only

```css
.subcategory-pill {
  border: 1px solid currentColor;
  background: transparent;   /* NO fill — design spec is explicit */
  border-radius: 999px;
  padding: 6px 14px;
  font-size: 13px;
}
```

### Banner Layout Types — Switch Logic

```tsx
const BannerSlider = ({ images, layoutType }) => {
  const layouts = {
    1: <LayoutFullWidth images={images} />,
    2: <LayoutSplitTwoCol images={images} />,
    3: <LayoutGridFour images={images} />,
    4: <LayoutSliderAuto images={images} />,
  };
  return layouts[layoutType] ?? layouts[1];
};
```

### Offer Zone — Mobile Repeat Pattern

The Offer Zone repeats **4 times** on mobile — once per clothing/footwear/kids category. Do not build it as a one-off section. Use the `CategorySection` template and pass `offerZoneEnabled={true}` per category.

### Reviews Count — Always 5

The API endpoint must return `limit=5`. The frontend carousel must be configured for exactly 5 cards. This is explicitly called out in the design PDF.

---

## Stats Bar Content (Desktop Only)

| Stat | Value |
|------|-------|
| Customers Served | 1 Million+ |
| Printing & Gifting Products | 20,000+ |
| Years of Service Excellence | 7 Years |

---

## Mobile Trust Badges (Footer)

| Badge | Text |
|-------|------|
| Shield | 100% Safe and Secure Payments |
| Truck | Free Shipping · Online Payment |
| Star | No one rejects, dislikes |
| Check | 100% Original Guarantee for all products |

---

*Printmont Category Page Implementation Plan · Desktop + Mobile Edition · Based on Figma PDF designs*
