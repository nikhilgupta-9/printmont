import { Link } from "react-router-dom";

const PageNotFound = () => (
  <div className="text-center py-5">
    <h2 className="fw-bold text-danger mb-3">404 - Page Not Found</h2>
    <p>The page you’re looking for doesn’t exist or has been moved.</p>
    <Link to="/" className="btn btn-dark mt-3">
      Back to Home
    </Link>
  </div>
);

export default PageNotFound;
