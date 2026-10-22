import React from "react";
import { Container, Spinner } from "react-bootstrap";
import useLandingPage from "./useLandingPage";
import BusinessSolutionsMobile from "./BusinessSolutionsMobile";
import BusinessSolutionsDesktop from "./BusinessSolutionsDesktop";
import { ASSET_URL, assetUrl } from "../../config/apiEndpoints";
import "./business-solutions.css";

export default function BusinessSolutions() {
  // Fetch device-tailored landing page data
  const { 
    page: mobilePage, 
    sections: mobileSections, 
    faqs: mobileFaqs, 
    loading: mobileLoading,
    error: mobileError
  } = useLandingPage("business-solutions", "mobile");

  const { 
    page: desktopPage, 
    sections: desktopSections, 
    faqs: desktopFaqs, 
    loading: desktopLoading,
    error: desktopError
  } = useLandingPage("business-solutions", "desktop");

  // Helper to format image paths
  const resolveImg = (img) => {
    if (!img) return "/default-img.jpg";
    if (img.startsWith("http://") || img.startsWith("https://")) return img;
    if (img.startsWith("/")) return img;
    return assetUrl(img);
  };

  const isLoading = mobileLoading || desktopLoading;

  if (isLoading) {
    return (
      <div className="bs-page-wrapper py-5 text-center">
        <Container>
          <Spinner animation="border" variant="primary" />
          <p className="text-muted mt-3 small">Loading Business Solutions &amp; Corporate Offerings...</p>
        </Container>
      </div>
    );
  }

  if (mobileError && desktopError) {
    return (
      <div className="bs-page-wrapper py-5 text-center">
        <Container>
          <h4 className="fw-bold text-danger mb-2">Unable to load Business Solutions</h4>
          <p className="text-muted small mb-3">Please check your connection or refresh the page.</p>
          <button 
            type="button" 
            className="btn btn-primary btn-sm rounded-pill px-4" 
            onClick={() => window.location.reload()}
          >
            Retry
          </button>
        </Container>
      </div>
    );
  }

  return (
    <div className="bs-page-wrapper">
      {/* 📱 Mobile Layout: Rendered strictly on screens < lg (1024px) */}
      <div className="d-block d-lg-none">
        <BusinessSolutionsMobile
          sections={mobileSections}
          faqs={mobileFaqs}
          resolveImg={resolveImg}
        />
      </div>

      {/* 💻 Desktop Layout: Rendered strictly on screens >= lg (1024px) */}
      <div className="d-none d-lg-block">
        <BusinessSolutionsDesktop
          sections={desktopSections}
          faqs={desktopFaqs}
          resolveImg={resolveImg}
        />
      </div>
    </div>
  );
}
