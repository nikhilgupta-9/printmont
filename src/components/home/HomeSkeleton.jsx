import React from "react";

/**
 * BannerSkeleton — single shimmer bar matching banner aspect ratio.
 * @param {string} [aspectRatio='16 / 5'] — CSS aspect-ratio value.
 */
export function BannerSkeleton({ aspectRatio = "16 / 5" }) {
  return (
    <div
      className="shimmer-bg w-100"
      style={{
        aspectRatio,
        borderRadius: "8px",
      }}
    />
  );
}

/**
 * CarouselSkeleton — row of card-shaped shimmer boxes.
 * @param {number} [cards=5] — Number of card placeholders to show.
 * @param {boolean} [mobile=false] — If true, show fewer cards for mobile.
 */
export function CarouselSkeleton({ cards = 5, mobile = false }) {
  const count = mobile ? 2 : cards;
  return (
    <div style={{ padding: "12px 0" }}>
      {/* Title placeholder */}
      <div
        className="shimmer-bg"
        style={{
          height: "22px",
          width: "180px",
          borderRadius: "4px",
          margin: "0 12px 14px",
        }}
      />
      {/* Card row */}
      <div
        style={{
          display: "flex",
          gap: "12px",
          padding: "0 12px",
          overflow: "hidden",
        }}
      >
        {Array.from({ length: count }).map((_, i) => (
          <div
            key={i}
            style={{
              flex: `0 0 ${mobile ? "44%" : `${Math.floor(100 / count) - 2}%`}`,
              borderRadius: "8px",
              background: "#fff",
              border: "1px solid #e2e8f0",
              overflow: "hidden",
            }}
          >
            <div
              className="shimmer-bg"
              style={{ width: "100%", aspectRatio: "1 / 1" }}
            />
            <div style={{ padding: "10px" }}>
              <div
                className="shimmer-bg"
                style={{
                  height: "14px",
                  width: "75%",
                  borderRadius: "4px",
                  marginBottom: "8px",
                }}
              />
              <div
                className="shimmer-bg"
                style={{
                  height: "12px",
                  width: "50%",
                  borderRadius: "4px",
                }}
              />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

/**
 * GridSkeleton — multi-column shimmer grid.
 * @param {number} [columns=2] — Number of grid columns.
 * @param {number} [rows=2] — Number of grid rows.
 */
export function GridSkeleton({ columns = 2, rows = 2 }) {
  return (
    <div
      style={{
        display: "grid",
        gridTemplateColumns: `repeat(${columns}, 1fr)`,
        gap: "10px",
        padding: "8px 12px",
      }}
    >
      {Array.from({ length: columns * rows }).map((_, i) => (
        <div
          key={i}
          className="shimmer-bg"
          style={{
            aspectRatio: "1 / 1",
            borderRadius: "8px",
          }}
        />
      ))}
    </div>
  );
}

/**
 * MosaicSkeleton — mixed-height grid shimmer layout.
 */
export function MosaicSkeleton() {
  return (
    <div style={{ padding: "12px" }}>
      {/* Title placeholder */}
      <div
        className="shimmer-bg"
        style={{
          height: "22px",
          width: "200px",
          borderRadius: "4px",
          marginBottom: "14px",
        }}
      />
      <div
        style={{
          display: "grid",
          gridTemplateColumns: "1fr 1fr 1fr",
          gridTemplateRows: "auto auto",
          gap: "10px",
        }}
      >
        {/* Large left item */}
        <div
          className="shimmer-bg"
          style={{
            gridRow: "1 / 3",
            aspectRatio: "3 / 4",
            borderRadius: "8px",
          }}
        />
        {/* Top right items */}
        <div
          className="shimmer-bg"
          style={{ aspectRatio: "1 / 1", borderRadius: "8px" }}
        />
        <div
          className="shimmer-bg"
          style={{ aspectRatio: "1 / 1", borderRadius: "8px" }}
        />
        {/* Bottom right items */}
        <div
          className="shimmer-bg"
          style={{ aspectRatio: "1 / 1", borderRadius: "8px" }}
        />
        <div
          className="shimmer-bg"
          style={{ aspectRatio: "1 / 1", borderRadius: "8px" }}
        />
      </div>
    </div>
  );
}

/**
 * CategoryCircleSkeleton — row of circular shimmer placeholders.
 * @param {number} [count=8] — Number of circle placeholders.
 */
export function CategoryCircleSkeleton({ count = 8 }) {
  return (
    <div
      style={{
        display: "flex",
        gap: "12px",
        padding: "16px 12px",
        overflowX: "hidden",
        justifyContent: "center",
      }}
    >
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} style={{ textAlign: "center", flex: "0 0 auto" }}>
          <div
            className="shimmer-bg"
            style={{
              width: "56px",
              height: "56px",
              borderRadius: "50%",
            }}
          />
          <div
            className="shimmer-bg"
            style={{
              width: "48px",
              height: "10px",
              borderRadius: "4px",
              margin: "6px auto 0",
            }}
          />
        </div>
      ))}
    </div>
  );
}

/**
 * ProductListSkeleton — 2-column grid of product card placeholders (mobile).
 * @param {number} [items=4] — Number of product cards.
 */
export function ProductListSkeleton({ items = 4 }) {
  return (
    <div
      style={{
        display: "grid",
        gridTemplateColumns: "1fr 1fr",
        gap: "10px",
        padding: "8px 12px",
      }}
    >
      {Array.from({ length: items }).map((_, i) => (
        <div
          key={i}
          style={{
            borderRadius: "8px",
            background: "#fff",
            border: "1px solid #e2e8f0",
            overflow: "hidden",
          }}
        >
          <div
            className="shimmer-bg"
            style={{ width: "100%", aspectRatio: "1 / 1" }}
          />
          <div style={{ padding: "8px" }}>
            <div
              className="shimmer-bg"
              style={{
                height: "12px",
                width: "80%",
                borderRadius: "4px",
                marginBottom: "6px",
              }}
            />
            <div
              className="shimmer-bg"
              style={{
                height: "10px",
                width: "50%",
                borderRadius: "4px",
              }}
            />
          </div>
        </div>
      ))}
    </div>
  );
}

/**
 * Returns the appropriate skeleton component based on section type.
 * @param {string} sectionType — One of the layout section_type values.
 * @param {boolean} [isMobile=false] — Whether to use mobile variant.
 */
export function getSkeletonForType(sectionType, isMobile = false) {
  switch (sectionType) {
    case "slider":
      return <BannerSkeleton aspectRatio={isMobile ? "16 / 9" : "16 / 5"} />;
    case "banner":
      return <BannerSkeleton aspectRatio={isMobile ? "16 / 7" : "16 / 5"} />;
    case "product_carousel":
      return <CarouselSkeleton mobile={isMobile} />;
    case "compact_grid":
      return <GridSkeleton columns={isMobile ? 2 : 3} rows={2} />;
    case "mobile_list":
      return <ProductListSkeleton items={4} />;
    case "category_mosaic":
      return <MosaicSkeleton />;
    case "featured_grid":
      return <GridSkeleton columns={isMobile ? 2 : 3} rows={2} />;
    case "brand_directory":
      return <BannerSkeleton aspectRatio="16 / 3" />;
    case "gift_finder":
      return <BannerSkeleton aspectRatio="16 / 5" />;
    case "bulk_widget":
      return <BannerSkeleton aspectRatio="16 / 3" />;
    default:
      return <BannerSkeleton aspectRatio="16 / 5" />;
  }
}
