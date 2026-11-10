import React from "react";

/** A picture beside a heading and rich-text paragraph. */
const ImageTextPattern = ({ data }) => {
  const imageFirst = data.layout !== "image-right";

  const image = data.image_url ? (
    <div className="col-md-5">
      <img src={data.image_url} alt={data.heading || ""} className="img-fluid rounded-3" loading="lazy" />
    </div>
  ) : null;

  const text = (
    <div className="col-md-7">
      {data.heading && <h5 className="fw-bold mb-2">{data.heading}</h5>}
      {data.body_html && (
        <div className="text-secondary" dangerouslySetInnerHTML={{ __html: data.body_html }} />
      )}
    </div>
  );

  return (
    <div className="bg-white p-3 rounded-3 border">
      <div className="row g-3 align-items-center">
        {imageFirst ? (<>{image}{text}</>) : (<>{text}{image}</>)}
      </div>
    </div>
  );
};

export default ImageTextPattern;
