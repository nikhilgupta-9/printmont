import React from "react";

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null, errorInfo: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    this.setState({ errorInfo });
    console.group("🚨 [React ErrorBoundary Caught Error]");
    console.error("Error:", error);
    console.error("Component Stack:", errorInfo?.componentStack);
    console.groupEnd();
  }

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) {
        return this.props.fallback;
      }

      // Compact section fallback
      if (this.props.silent) {
        return null;
      }

      return (
        <div style={{ padding: "20px", margin: "20px auto", maxWidth: "800px", border: "1px solid #f5c6cb", backgroundColor: "#f8d7da", color: "#721c24", borderRadius: "8px" }}>
          <h4>Something went wrong loading this component</h4>
          <p style={{ fontFamily: "monospace", fontSize: "14px", whiteSpace: "pre-wrap" }}>
            {this.state.error?.toString()}
          </p>
          <button
            onClick={() => window.location.reload()}
            style={{ padding: "8px 16px", backgroundColor: "#0b53a1", color: "#fff", border: "none", borderRadius: "4px", cursor: "pointer" }}
          >
            Reload Page
          </button>
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
