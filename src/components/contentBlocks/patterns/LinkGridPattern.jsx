import React from "react";
import { Link } from "react-router-dom";

/** A heading and a row of labelled links — good for "most searched" style link lists. */
const LinkGridPattern = ({ data }) => {
  const links = Array.isArray(data.links) ? data.links : [];
  const isExternal = (url) => /^https?:\/\//i.test(url || "");

  return (
    <div className="bg-white p-3 rounded-3 border">
      {data.heading && <h6 className="fw-bold text-secondary mb-2">{data.heading}</h6>}
      <div className="d-flex flex-wrap gap-2">
        {links.map((link, idx) =>
          isExternal(link.url) ? (
            <a key={idx} href={link.url} className="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">
              {link.label}
            </a>
          ) : (
            <Link key={idx} to={link.url || "/"} className="btn btn-sm btn-outline-secondary">
              {link.label}
            </Link>
          )
        )}
      </div>
    </div>
  );
};

export default LinkGridPattern;
