import React from "react";

/** A heading and a rich-text paragraph — the general-purpose pattern. */
const TextPattern = ({ data }) => (
  <div className="bg-white p-3 rounded-3 border">
    {data.heading && <h5 className="fw-bold mb-2">{data.heading}</h5>}
    {data.body_html && (
      <div className="text-secondary" dangerouslySetInnerHTML={{ __html: data.body_html }} />
    )}
  </div>
);

export default TextPattern;
