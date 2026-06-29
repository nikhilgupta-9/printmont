# Homepage Component Structure Plan

This plan is based on the current desktop homepage in `src/components/pages/Home.jsx`, the mobile homepage in `src/components/pages/MobHome.jsx`, and the imported banner, carousel, product, and section components under `src/components/pages`.

## Current Homepage Inventory

Desktop homepage currently uses:

- `category-list/Categories.jsx`
- `carousel/FirstCarousel.jsx`
- `carousel/Slider.jsx`
- `sections/SectionOne.jsx`
- `carousel/ThreeImgCarousel.jsx`
- `carousel/FourImgCarousel.jsx`
- `carousel/SecondCarousel.jsx`
- `sections/SectionTwo.jsx`
- `sections/GiftFinder.jsx`
- `sections/Banner.jsx`
- `sections/BannerTwo.jsx`
- `carousel/Singleproduct.jsx`
- `sections/SectionFour.jsx`
- `sections/SectionFourReverse.jsx`
- `sections/SectionEight.jsx`
- `sections/SectionGrid.jsx`
- `sections/SectionTen.jsx`
- `sections/BrandDirectory.jsx`
- `sections/BulkOrder.jsx`

Mobile homepage currently uses:

- `category-list/Categories.jsx`
- `carousel/FirstCarousel.jsx`
- `carousel/Slider.jsx`
- `sections/SectionOne.jsx`
- `carousel/Singleproduct.jsx`
- `sections/SectionTwo.jsx`
- `sections/ProductGrid.jsx`
- `sections/SectionFour.jsx`
- `sections/Banner.jsx`
- `sections/SectionNine.jsx`
- `carousel/SecondCarousel.jsx`
- `sections/BannerTwo.jsx`
- `sections/ProductList.jsx`
- `sections/SectionGrid.jsx`
- `Bannerthree.jsx`
- `sections/SectionTen.jsx`
- `sections/BrandDirectory.jsx`

## Problems To Fix

- Component names like `SectionOne`, `SectionTwo`, `SectionFour`, `SectionEight`, and `SectionTen` do not explain the rendered layout.
- Banner API parsing is repeated across `FirstCarousel`, `Slider`, `ThreeImgCarousel`, `FourImgCarousel`, `Banner`, `BannerTwo`, `BannerSmall`, `SectionOne`, and `SectionTwo`.
- Product API normalization is repeated across `SecondCarousel`, `Singleproduct`, `ProductGrid`, `ProductList`, and `SectionTen`.
- Product card markup has multiple slightly different versions with inconsistent spacing and image sizing.
- Some components use internal row gaps, margins, or inline styles that can fight the desired global 3px homepage spacing.
- Banner height is partially centralized in `carousel.css`, but some banners still override height with inline `maxHeight`, fixed skeleton heights, or separate classes.

## Target Folder Structure

Create a dedicated homepage module:

```text
src/components/home/
  HomePage.jsx
  MobileHomePage.jsx
  home.config.js
  home.css

  banners/
    BannerImage.jsx
    BannerGrid.jsx
    BannerCarousel.jsx
    ResponsiveHeroCarousel.jsx
    MultiColumnBannerCarousel.jsx
    StaticResponsiveBanner.jsx

  products/
    ProductCard.jsx
    ProductCarousel.jsx
    ProductRail.jsx
    ProductGrid.jsx
    ProductMosaic.jsx
    FeaturedProductGrid.jsx

  sections/
    CategoryStripSection.jsx
    GiftFinderSection.jsx
    BrandDirectorySection.jsx
    BulkOrderWidget.jsx

  hooks/
    useHomeBanners.js
    useHomeProducts.js
    useHorizontalScroll.js
    useBreakpoint.js

  utils/
    normalizeBanner.js
    normalizeProduct.js
```

Keep existing files during migration, then move or replace them section by section. Avoid a single huge rename-only change.

## Component Rename Map

Use these names when refactoring:

- `Home.jsx` -> `HomePage.jsx`
- `MobHome.jsx` -> `MobileHomePage.jsx`
- `FirstCarousel.jsx` -> `ResponsiveHeroCarousel.jsx`
- `Slider.jsx` -> `MobileHeroSlider.jsx` or fold into `ResponsiveHeroCarousel.jsx`
- `ThreeImgCarousel.jsx` -> `MultiColumnBannerCarousel.jsx` with `columns={3}`
- `FourImgCarousel.jsx` -> `MultiColumnBannerCarousel.jsx` with `columns={4}`
- `Banner.jsx` -> `BannerGrid.jsx` with `columns={{ desktop: 2, mobile: 1 }}`
- `BannerTwo.jsx` -> `ResponsiveBannerSet.jsx`
- `Bannerthree.jsx` -> `StaticResponsiveBanner.jsx`
- `SectionOne.jsx` -> `FourTileBannerGrid.jsx`
- `SectionTwo.jsx` -> `FullWidthResponsiveBanner.jsx`
- `SecondCarousel.jsx` -> `ProductCarousel.jsx`
- `Singleproduct.jsx` -> `ProductRail.jsx`
- `ProductList.jsx` -> `MobileProductList.jsx`
- `ProductGrid.jsx` -> `CompactProductGrid.jsx`
- `SectionFour.jsx` -> `CategoryProductMosaic.jsx`
- `SectionFourReverse.jsx` -> `CategoryProductMosaicReverse.jsx`, or one `CategoryProductMosaic` with `imagePosition="start"`
- `SectionEight.jsx` -> `CategoryProductColumnGrid.jsx`
- `SectionGrid.jsx` -> `FeaturedCategoryGrid.jsx`
- `SectionNine.jsx` -> `PromoProductTriplet.jsx`
- `SectionTen.jsx` -> `FeaturedProductGrid.jsx`
- `GiftFinder.jsx` -> `GiftFinderSection.jsx`
- `BrandDirectory.jsx` -> `BrandDirectorySection.jsx`
- `BulkOrder.jsx` -> `BulkOrderWidget.jsx`

## Import Compatibility Plan

Some homepage components are also used by other pages, so do not move or rename files in a way that breaks existing imports in one step.

Known cross-page usage:

- `src/components/products/ProductDetails.jsx` imports `pages/carousel/SecondCarousel` and `pages/carousel/Singleproduct`.
- `src/components/pages/category-list/CategoryPage.jsx` imports `FirstCarousel`, `SecondCarousel`, `Slider`, `FourImgCarousel`, `ThreeImgCarousel`, `BannerTwo`, `BannerSmall`, and `SectionTen`.
- `src/components/pages/blog/Blog.jsx` imports `FourImgCarousel`.
- `src/components/pages/carousel/TabCarousel.jsx` imports `SecondCarousel`.

Migration rule:

- First create new components under `src/components/home/`.
- Keep the old file paths as compatibility wrappers until every import is updated.
- Each old component file should re-export the new component with the same default export name and compatible props.
- After all pages are updated and verified, remove the compatibility wrappers in a final cleanup commit.

Example compatibility wrapper:

```jsx
// src/components/pages/carousel/SecondCarousel.jsx
export { default } from "../../home/products/ProductCarousel";
```

Example direct import after migration:

```jsx
import ProductCarousel from "../../home/products/ProductCarousel";
```

Use a temporary barrel file to make import updates cleaner:

```text
src/components/home/index.js
```

Recommended exports:

```js
export { default as ResponsiveHeroCarousel } from "./banners/ResponsiveHeroCarousel";
export { default as MultiColumnBannerCarousel } from "./banners/MultiColumnBannerCarousel";
export { default as BannerGrid } from "./banners/BannerGrid";
export { default as ResponsiveBannerSet } from "./banners/ResponsiveBannerSet";
export { default as StaticResponsiveBanner } from "./banners/StaticResponsiveBanner";
export { default as ProductCarousel } from "./products/ProductCarousel";
export { default as ProductRail } from "./products/ProductRail";
export { default as CompactProductGrid } from "./products/CompactProductGrid";
export { default as FeaturedProductGrid } from "./products/FeaturedProductGrid";
```

Import update order:

1. Migrate `SecondCarousel` to `ProductCarousel`, keep `pages/carousel/SecondCarousel.jsx` as a wrapper, then update `Home.jsx`, `MobHome.jsx`, `ProductDetails.jsx`, `CategoryPage.jsx`, and `TabCarousel.jsx`.
2. Migrate `Singleproduct` to `ProductRail`, keep `pages/carousel/Singleproduct.jsx` as a wrapper, then update `Home.jsx`, `MobHome.jsx`, and `ProductDetails.jsx`.
3. Migrate `FirstCarousel`, `Slider`, `ThreeImgCarousel`, and `FourImgCarousel` to the new banner components, keep wrappers, then update `Home.jsx`, `MobHome.jsx`, `CategoryPage.jsx`, and `Blog.jsx`.
4. Migrate `BannerTwo`, `BannerSmall`, `SectionOne`, `SectionTwo`, and `SectionTen`, keep wrappers, then update homepage and category page imports.
5. Run a repo-wide import check:

```powershell
rg -n "pages/(carousel|sections)/(FirstCarousel|SecondCarousel|Singleproduct|Slider|ThreeImgCarousel|FourImgCarousel|BannerTwo|BannerSmall|SectionOne|SectionTwo|SectionTen)" src
```

Only remove old wrapper files when this command returns no real page imports, or when the remaining matches are intentionally kept as compatibility exports.

## Spacing Rule

Use one homepage spacing class everywhere:

```css
.home-layout-gap {
  --home-gap: 3px;
  display: flex;
  flex-direction: column;
  gap: var(--home-gap);
  padding: var(--home-gap);
}
```

Rules:

- Apply `home-layout-gap` only to the desktop and mobile homepage content wrapper.
- Do not add section-to-section `margin-top`, `margin-bottom`, `py-*`, or extra wrapper gaps for homepage sections.
- Inside homepage components, use `var(--home-gap)` for row/column gutters when the visual gap must match the page gap.
- Remove Bootstrap gap utilities like `g-1`, `gap-1`, `px-1`, and inline `margin: 3px` from migrated home components when they create extra spacing beyond the one global class.
- The same `3px` padding from `.home-layout-gap` should create left and right homepage spacing.

## Internal Component Gap Rule

Homepage components can still have small internal spacing, but it must be intentional and token-based.

Use the same shared gap value:

```css
:root {
  --home-gap: 3px;
  --home-component-gap: var(--home-gap);
}
```

Rules:

- Section-to-section spacing: only `.home-layout-gap`.
- Homepage left and right side spacing: only `.home-layout-gap` padding.
- Internal gaps inside product sections: use `gap: var(--home-component-gap)` only where cards need breathing room.
- Avoid mixed Bootstrap spacing utilities like `g-1`, `gap-1`, `px-1`, `py-2`, `m-1`, and inline `margin: 3px` inside migrated homepage components.
- If a component needs more than 3px for readable content, keep that spacing inside the card body only, not outside the component wrapper.
- Product card image, title, and price spacing can be minimal, but should not affect the outer section rhythm.

## Banner And Carousel Spacing Rule

Banners and banner carousels should not have outer padding. Only multi-image layouts should have space between images, and that space must equal the homepage gap.

Rules:

- Single banner: no outer padding, no internal row padding, full width inside the homepage wrapper.
- Hero carousel: no outer padding around the image.
- Static responsive banner: no outer padding around the image.
- Multi-image banner grid: no outer padding, only `gap: var(--home-gap)` between images.
- Multi-image banner carousel: no outer padding, only slide/image gap equal to `var(--home-gap)`.
- Two-column, three-column, and four-column banner layouts must all use the same `var(--home-gap)` image-to-image spacing.
- Do not use Bootstrap `row`, `g-1`, `px-1`, or slide `padding-left/right` for banner spacing after migration.
- Banner skeletons must follow the same no-padding rule and use the same image gap when multiple skeleton banners are shown.

Recommended structure:

```jsx
<section className="home-banner-section">
  <div className="home-banner-grid" data-columns="3">
    <BannerImage src={image.src} alt={image.alt} />
  </div>
</section>
```

Recommended CSS:

```css
.home-banner-section {
  padding: 0;
  margin: 0;
}

.home-banner-grid {
  display: grid;
  gap: var(--home-gap);
}

.home-banner-grid[data-columns="2"] {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.home-banner-grid[data-columns="3"] {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.home-banner-grid[data-columns="4"] {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

@media (max-width: 767.98px) {
  .home-banner-grid[data-mobile-columns="1"] {
    grid-template-columns: 1fr;
  }
}
```

## Banner Height Rule

Keep one shared banner height system in `home.css` or the existing `carousel.css`:

```css
:root {
  --home-banner-height-xl: 280px;
  --home-banner-height-desktop: 240px;
  --home-banner-height-tablet: 180px;
  --home-banner-height-mobile: 150px;
}

.home-banner-img {
  width: 100%;
  height: var(--home-banner-height-desktop);
  object-fit: cover;
  display: block;
}
```

Rules:

- Every banner, banner carousel, and carousel banner image must render through `BannerImage.jsx` or use the same `home-banner-img` class.
- Replace `carousel-img`, `carousel-img-mobile`, inline `height`, inline `maxHeight`, and component-specific banner sizing during migration.
- Skeleton loaders should use the same height variables so loading and loaded states do not jump.
- Use `object-fit: cover` for promotional banners and `object-fit: contain` only for rare artwork that must not crop.

## Shared Data Hooks

Add one banner hook:

```js
useHomeBanners(apiUrl, sectionKey)
```

It should support current API shapes:

- `data.success === true`
- `data.data.home_hero.banners`
- `data.data.home_above_fold.banners`
- `data.data.home_mid_section_1.banners`
- `data.data.home_mid_section_2.banners`
- `data.data.home_mid_section_3.banners`
- direct `data.data` arrays
- direct root arrays

Add one product hook:

```js
useHomeProducts(apiUrl, initialProducts, limit)
```

It should normalize:

- `id`
- `title`
- `img` or `image`
- `price`
- `originalPrice`
- `discount`
- `badge`

## Product Card Variants

Use one `ProductCard.jsx` with variants instead of separate card markup:

- `variant="carousel"` for horizontal carousels.
- `variant="rail"` for `Singleproduct` style rows.
- `variant="compact-grid"` for mobile product grids/lists.
- `variant="mosaic"` for category blocks like `SectionFour`, `SectionEight`, and `SectionTen`.

Shared product card rules:

- Product image area uses a stable square ratio.
- Product image uses `object-fit: contain`.
- Product title is one line with ellipsis unless the design explicitly needs two lines.
- Price/discount rendering is centralized so all product sections match.

## Suggested Migration Order

1. Add `src/components/home/home.css` and move `.home-layout-gap`, banner height variables, skeleton banner sizes, and shared homepage-only styles there.
2. Add `normalizeBanner.js`, `normalizeProduct.js`, `useHomeBanners.js`, and `useHomeProducts.js`.
3. Add `BannerImage.jsx`, then migrate `Banner`, `BannerTwo`, `SectionOne`, `SectionTwo`, `ThreeImgCarousel`, and `FourImgCarousel` to shared banner rendering.
4. Add `ProductCard.jsx`, then migrate `SecondCarousel`, `Singleproduct`, `ProductList`, `ProductGrid`, and `SectionTen`.
5. Add compatibility wrappers at old import paths before updating page imports.
6. Replace `Home.jsx` and `MobHome.jsx` imports with the new homepage module imports.
7. Update non-homepage imports in `ProductDetails.jsx`, `CategoryPage.jsx`, `Blog.jsx`, and `TabCarousel.jsx`.
8. Run the repo-wide import check from the Import Compatibility Plan.
9. Run `npm run lint` and `npm run build`.
10. Visually test desktop and mobile homepage to confirm every section gap is exactly 3px and every banner/carousel banner image has the same height for its breakpoint.
11. Remove compatibility wrappers only after all pages have been updated and verified.

## Google Antigravity Agent Execution Steps

Use this plan as small sequential tasks. Do not ask the agent to refactor everything in one pass.

### Step 1 - Audit Imports And Usage

Agent task:

```text
Read docs/homepage-component-structure-plan.md. Scan all imports of homepage carousel, banner, product, and section components. Create a short report listing every file that imports components from src/components/pages/carousel and src/components/pages/sections. Do not edit code in this step.
```

Expected result:

- Confirms cross-page imports in `ProductDetails.jsx`, `CategoryPage.jsx`, `Blog.jsx`, and `TabCarousel.jsx`.
- Confirms homepage imports in `Home.jsx` and `MobHome.jsx`.

### Step 2 - Add Shared Home CSS Tokens

Agent task:

```text
Create src/components/home/home.css with --home-gap, --home-component-gap, .home-layout-gap, banner height variables, .home-banner-section, .home-banner-grid, and .home-banner-img. Move homepage-only spacing rules out of global CSS only when safe. Keep behavior unchanged except spacing tokens. Run npm run build.
```

Acceptance:

- `.home-layout-gap` controls section spacing and homepage side spacing.
- Banner classes have no outer padding.
- Build passes.

### Step 3 - Add Normalizers And Hooks

Agent task:

```text
Add normalizeBanner.js, normalizeProduct.js, useHomeBanners.js, and useHomeProducts.js under src/components/home. Support all current API response shapes listed in the plan. Do not replace UI components yet. Add small focused tests only if the project already has a test setup; otherwise verify by build.
```

Acceptance:

- Product and banner parsing logic exists in one place.
- Existing pages still compile.

### Step 4 - Migrate Banner Foundation

Agent task:

```text
Add BannerImage.jsx, BannerGrid.jsx, MultiColumnBannerCarousel.jsx, ResponsiveHeroCarousel.jsx, ResponsiveBannerSet.jsx, and StaticResponsiveBanner.jsx. Use home-banner-img for all banner images. Ensure single banners have no padding. Ensure multi-image banners and carousel slides use only var(--home-gap) between images.
```

Acceptance:

- No `px-1`, `g-1`, slide padding, or inline outer spacing in new banner components.
- Multi-image image-to-image spacing equals `3px`.
- Banner skeletons match real banner heights and spacing.

### Step 5 - Add Banner Compatibility Wrappers

Agent task:

```text
Replace old banner/carousel component implementations with compatibility wrappers that export the new components while preserving old default imports and current props. Start with FirstCarousel, ThreeImgCarousel, FourImgCarousel, BannerTwo, BannerSmall, SectionOne, SectionTwo, and Bannerthree. Do not update consuming pages yet. Run npm run build.
```

Acceptance:

- Old import paths still work.
- Build passes.

### Step 6 - Migrate Product Foundation

Agent task:

```text
Add ProductCard.jsx, ProductCarousel.jsx, ProductRail.jsx, CompactProductGrid.jsx, MobileProductList.jsx, CategoryProductMosaic.jsx, FeaturedProductGrid.jsx, and useHorizontalScroll.js. Use one ProductCard with variants. Keep internal card spacing minimal and token-based. Do not use outer margins for section spacing.
```

Acceptance:

- Product cards use stable square image areas.
- Internal product section gaps use `var(--home-component-gap)`.
- No product component adds section-to-section margin.

### Step 7 - Add Product Compatibility Wrappers

Agent task:

```text
Replace old product component implementations with compatibility wrappers for SecondCarousel, Singleproduct, ProductGrid, ProductList, SectionFour, SectionFourReverse, SectionEight, SectionGrid, SectionNine, and SectionTen where possible. Preserve current props. Run npm run build.
```

Acceptance:

- Old imports still work in product details, category page, blog, tab carousel, desktop home, and mobile home.
- Build passes.

### Step 8 - Update Homepage Imports

Agent task:

```text
Update Home.jsx and MobHome.jsx to import from src/components/home or src/components/home/index.js. Keep rendered order the same. Remove extra wrapper div padding/margins that fight .home-layout-gap. Run npm run build.
```

Acceptance:

- Desktop and mobile home use one `.home-layout-gap` wrapper.
- Section order is unchanged.
- Build passes.

### Step 9 - Update Non-Homepage Imports

Agent task:

```text
Update ProductDetails.jsx, CategoryPage.jsx, Blog.jsx, and TabCarousel.jsx to import the new home components or shared product/banner components directly. Preserve behavior and props. Run the repo-wide rg import check from the plan, then run npm run build.
```

Acceptance:

- No page imports old `pages/carousel` or `pages/sections` paths unless intentionally using a temporary wrapper.
- Build passes.

### Step 10 - Visual Spacing Verification

Agent task:

```text
Start the Vite dev server. Check desktop and mobile homepage. Verify: 3px between sections, 3px left/right homepage padding, no padding around single banners or hero carousels, 3px only between images in multi-image banners/carousels, and consistent banner heights per breakpoint. Fix any component that violates this.
```

Acceptance:

- All homepage section gaps match.
- All multi-banner image gaps match homepage side spacing.
- Single banners and hero banners touch the inside edge of the homepage wrapper with no extra component padding.
- Banner heights are consistent for each breakpoint.

### Step 11 - Cleanup Old Wrappers

Agent task:

```text
After all imports point to src/components/home and visual verification passes, remove old compatibility wrappers that are no longer imported. Run npm run build and npm run lint.
```

Acceptance:

- No dead old component wrappers remain.
- Build and lint pass.

## Acceptance Checklist

- Desktop homepage uses one `home-layout-gap` wrapper for all section-to-section spacing.
- Mobile homepage uses one `home-layout-gap` wrapper for all section-to-section spacing.
- Homepage left and right spacing comes from the same `home-layout-gap` class.
- Single banners and hero carousels have no extra outer padding.
- Multi-image banners and banner carousels use only `var(--home-gap)` between images.
- Internal component gaps are minimal and token-based.
- Banner images, banner carousel images, and static responsive banners use one shared height class.
- Product cards are rendered through one shared `ProductCard` with variants.
- Banner API parsing lives in one utility/hook.
- Product API parsing lives in one utility/hook.
- Component names explain layout and purpose without needing to open the file.
- No homepage component adds extra external margins that break the 3px rhythm.
- Existing pages that reused homepage components keep working during migration through compatibility wrappers.
- Final imports point to `src/components/home/` or `src/components/home/index.js`, not old `pages/carousel` or `pages/sections` paths.
