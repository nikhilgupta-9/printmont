import React, { useEffect, useState } from "react";

const SectionTwo = ({ apiurl }) => {
  const [images, setImages] = useState([]); // fetched image data
  const [loading, setLoading] = useState(true); // loading state
  const [error, setError] = useState(null); // error message

  useEffect(() => {
    if (!apiurl) return; // safeguard if no URL is passed

    const fetchImages = async () => {
      try {
        const res = await fetch(apiurl);
        if (!res.ok) throw new Error(`Failed to fetch (${res.status})`);
        const data = await res.json();
        setImages(data);
      } catch (err) {
        console.error("Error fetching images:", err);
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchImages();
  }, [apiurl]); // ✅ re-run when URL changes

  if (loading) return <p className="text-center p-3">Loading...</p>;
  if (error) return <p className="text-center text-danger p-3">{error}</p>;

  return (
    <div
      className="container-fluid d-flex flex-column align-items-center justify-content-center gap-3 bg-gray mt-2 rounded-md p-0"
      style={{ maxHeight: "350px" }}
    >
      {images.length > 0 ? (
        images.map((imgSrc, index) => (
          <picture key={index} className="col-12">
            {/* Large screen image */}
            <source media="(min-width: 768px)" srcSet={imgSrc.large} />
            {/* Small screen fallback */}
            <img
              src={imgSrc.small}
              alt={`Section Banner ${index + 1}`}
              width="100%"
              style={{ maxHeight: "290px", objectFit: "contain" }}
              className="rounded section-two-img"
            />
          </picture>
        ))
      ) : (
        <div className="text-center text-muted p-4">
          No section images available
        </div>
      )}
    </div>
  );
};

export default SectionTwo;
