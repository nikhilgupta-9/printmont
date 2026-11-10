import React from "react";

/** "A | B | C" -> "A | B | C" with clean spacing, skipping empty parts. */
const formatItems = (raw) =>
  String(raw || "")
    .split("|")
    .map((item) => item.trim())
    .filter(Boolean)
    .join(" | ");

/** Heading + bold "most searched" line + category rows + closing text. */
const BrandDirectoryPattern = ({ data }) => {
  const categories = Array.isArray(data.categories) ? data.categories : [];

  return (
    <div style={{ fontFamily: "Arial", fontSize: "12px" }} className="bg-white p-3 rounded-3 border">
      {(data.heading_label || data.heading_highlight) && (
        <h6 className="text-secondary fw-bold">
          {data.heading_label}: <span className="text-dark">{data.heading_highlight}</span>
        </h6>
      )}

      <div className="d-flex flex-column mb-3 justify-content-center align-items-starstan p-0 m-0">
        {data.most_searched_items && (
          <span>
            <span className="fw-bold">{data.most_searched_label}</span>{" "}
            <span className="text-dark">{formatItems(data.most_searched_items)}</span>
          </span>
        )}
        {categories.map((cat, idx) => (
          <span key={idx}>
            <span className="text-secondary fs-6">{cat.label}</span>{" "}
            <span className="text-dark">{formatItems(cat.items)}</span>
          </span>
        ))}
      </div>

      {(data.footer_heading || data.footer_html) && (
        <>
          <strong className="text-secondary fs-6">{data.footer_heading}</strong>
          <div className="mt-2 text-secondary" dangerouslySetInnerHTML={{ __html: data.footer_html || "" }} />
        </>
      )}
    </div>
  );
};

export default BrandDirectoryPattern;
